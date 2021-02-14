<?php

$root_dir = $_SERVER['DOCUMENT_ROOT'].'/DIMMAS_ENDPOINTS';
require_once($root_dir.'/includes/Database.php');
require_once($root_dir.'/Controllers/NotificationController.php');
require_once($root_dir.'/Controllers/UserController.php');
require_once($root_dir.'/Controllers/ScheduleController.php');


class RequestController extends Database {

    private $data = null;
    private $table = "requests";

    public function __construct($data = ''){
        $this->data = $data;
    }
    
    public function show(){
        
        $whereClause = $this->data['userType'] === 3 ? " AND r.user_id = ".$this->data["id"] : "";

        $query = "SELECT 	
                        r.id,
                        rt.`name` AS 'REQUEST_TYPE',
                        r.`request_type_id` AS 'REQUEST_TYPE_ID',
                        b.`name` AS 'BARANGAY',
                        u.`name` AS 'CREATED_BY',
                        DATE_FORMAT(r.scheduled_date,'%M %d, %Y @ %h:%i %p') AS 'DATE_SCHEDULE',
                        DATE_FORMAT(r.scheduled_date,'%Y-%m-%d') AS 'FORMAT_DATE_SCHED',
                        DATE_FORMAT(r.scheduled_date,'%H:%i') AS 'FORMAT_TIME_SCHED',
                        DATE_FORMAT(r.created_at,'%M %d, %Y') AS 'DATE_CREATED'
                    
                    FROM requests r
                    
                        LEFT JOIN users u
                            ON(r.`user_id` = u.`id`)
                        LEFT JOIN request_types rt
                            ON(r.`request_type_id` = rt.`id`)
                        LEFT JOIN barangays b
                            ON(u.`barangay_id` = b.`id`)
                                WHERE r.`isApproved` = 0 AND r.deleted_at IS NULL".$whereClause;

        return $this->setquery($query)->get();


    }

    public function create(){

        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        try {

            $query = $this->insert([
                'request_type_id' => $this->data['request_type_id'],
                'user_id'         => $this->data['id'],
                'scheduled_date'  => $this->data['scheduled_date'],
                'isApproved' => 0,
                'created_at' => $dateTime,
                'updated_at' => $dateTime
            ],$this->table);

           $response = $this->setquery($query)->save();

           Notification::create([
                "title"   => "New Request Created",
                "content" => "New request added by BRGY.".$this->setquery("SELECT `name` FROM users WHERE id =".$this->data['id'])->getField('name'),
                "created_by" => $this->data['id'],
                "notify_to"  => $this->setquery("SELECT id FROM users WHERE user_type_id = 1")->getArray()
            ]);

            return [
                "status" => 200,
                "message" => "Successfully Saved!"
            ];

        } catch (Exception $e) {
            
            return [
                "status" => 401,
                "message" => "Something went wrong. Please contact your support"
            ];
        }
    }

    public function edit(){

        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        try {

            $query = $this->update([
                'request_type_id' => $this->data['request_type_id'],
                'scheduled_date'  => $this->data['scheduled_date'],
                'updated_at' => $dateTime
            ],$this->table, $this->data['id']);

            $this->setquery($query)->save();

            return [
                "status" => 200,
                "message" => "Successfully Saved!"
            ];

        } catch (Exception $e) {
            
            return [
                "status" => 401,
                "message" => "Something went wrong. Please contact your support"
            ];
        }
    }

    public function destroy(){

        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        $query = $this->update([
            'deleted_at' => $dateTime
        ],$this->table, $this->data['id']);

        try {

            $this->setquery($query)->save();

            return [
                "status" => 200,
                "message" => "Successfully Deleted!"
            ];

        } catch (Exception $e) {
            
            return [
                "status" => 401,
                "message" => "Something went wrong. Please contact your support"
            ];
        }
    }

    public function requestAction(){
        
        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        try {

            $query = $this->update([
                'isApproved' => $this->data['action'],
                'remarks'    => $this->data['remarks'],
                'updated_at' => $dateTime
            ],$this->table, $this->data['id']);


            $this->setquery($query)->save();

            $request_details = $this->setquery("SELECT * FROM requests WHERE id=".$this->data['id'])->get()[0];

            if($this->data['action'] === 1){
                ScheduleController::add([
                    "schedule_for" => $request_details->request_type_id,
                    "schedule"    => $request_details->scheduled_date,
                    "schedule_to" => $request_details->user_id,
                    "schedule_by" => $this->data['approver_id'],
                    "remarks"     => $this->data['remarks'],
                    "isDone"      => 0
                ]);
            }

            $content = $this->data['action'] === 1 ? 'Approved' : 'Disapproved due to '.$this->data['remarks'];
            
            Notification::create([
                "title"   => "Request action",
                "content" => "Your request has been ".$content,
                "created_by" => $this->data['approver_id'],
                "notify_to"  => $this->setquery("SELECT `user_id` FROM requests WHERE id =".$this->data['id'])->getArray()
            ]);

            return [
                "status" => 200,
                "message" => "Request has been".($this->data['action'] === 1 ? ' Approved' : ' Disapproved'),
            ];


        } catch (Exception $e) {
            
            return [
                "status" => 401,
                "message" => "Something went wrong. Please contact your support"
            ];
        }

    }

    public function requestTypes(){
        return $this->setquery("SELECT * FROM request_types")->get();
    }

}