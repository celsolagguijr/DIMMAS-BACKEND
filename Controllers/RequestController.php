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
                        DATE_FORMAT(r.created_at,'%M %d, %Y') AS 'DATE_CREATED',
                        r.scheduled_date AS 'RAW_SCHEDULE'
                    
                    FROM requests r
                    
                        LEFT JOIN users u
                            ON(r.`user_id` = u.`id`)
                        LEFT JOIN request_types rt
                            ON(r.`request_type_id` = rt.`id`)
                        LEFT JOIN barangays b
                            ON(u.`barangay_id` = b.`id`)
                                WHERE r.`isApproved` = 0 AND r.`is_expired` IS NULL AND r.deleted_at IS NULL".$whereClause;

        $requests =  $this->setquery($query)->get();

        $valiRequests = [];
        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');


        foreach ($requests as $request) {
            $requestDateTime = strtotime($request->RAW_SCHEDULE);
            $dateTimeNow = strtotime("now");

            if($requestDateTime < $dateTimeNow) {
                
                $query = $this->update([
                    'is_expired' => $dateTime,
                    'updated_at' => $dateTime
                ],$this->table, $request->id);

                $this->setquery($query)->save();
                continue;
            }

            $valiRequests[] = $request;


        }


        return $valiRequests;
        


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

    public function requestReport(){

        $barangay_id = $this->data['barangay_id'];
        $from = $this->data['from'];
        $to = $this->data['to'];

        $filterBarangay = $barangay_id != null ? ' u.barangay_id = '.$barangay_id.' AND' : '';


        $query = "SELECT
                        req_types.`name` AS 'request_type',
                        barangay.`name` AS 'requested_by',
                        req.`isApproved` AS 'request_status',
                        req.`is_expired` AS 'expired_date',
                        req.`remarks` AS 'remarks',
                        DATE_FORMAT(req.`scheduled_date`, '%M %d, %Y @ %h:%i %p') AS 'scheduled_date'
                        
                        
                    FROM requests req
                    
                        LEFT JOIN request_types req_types
                            ON(req.`request_type_id` = req_types.`id`)
                        LEFT JOIN users u
                            ON(req.`user_id` = u.`id`)
                        LEFT JOIN barangays barangay
                            ON(u.`barangay_id` = barangay.id)
                                
                                WHERE $filterBarangay DATE_FORMAT(req.`scheduled_date`, '%Y-%m-%d') BETWEEN '$from' AND '$to' AND req.`deleted_at` IS NULL";
        return $this->setquery($query)->get();
    }

}