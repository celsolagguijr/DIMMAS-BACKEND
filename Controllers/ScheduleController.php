<?php

$root_dir = $_SERVER['DOCUMENT_ROOT'].'/DIMMAS_ENDPOINTS';
require_once($root_dir.'/includes/Database.php');
require_once($root_dir.'/Controllers/NotificationController.php');

class ScheduleController extends Database{

    private $data = null;

    public function __construct($data = ''){
        $this->data = $data;
    }
    
    public function show(){

        $filterByUser = $this->data['userType'] === 3 ? " AND s.`schedule_to` = ".$this->data["id"] : "";

        $query = "SELECT
                    s.id,
                    scheduler.`name` AS 'SCHEDULER',
                    b.`name` AS 'BARANGAY_SCHED',
                    rt.`name` AS 'SCHEDULED_FOR',
                    s.`remarks` AS 'REMARKS',
                    DATE_FORMAT(s.schedule,'%Y-%m-%d') AS 'FORMAT_DATE_SCHED',
                    DATE_FORMAT(s.schedule,'%H:%i') AS 'FORMAT_TIME_SCHED',
                    DATE_FORMAT(s.`schedule`, '%M %d, %Y @ %h:%i %p') AS 'SCHEDULED_DATE',
                    DATE_FORMAT(s.`created_at`, '%M %d, %Y') AS 'CREATED_AT'

                    FROM schedules s

                        LEFT JOIN users scheduler
                            ON(s.`schedule_by` = scheduler.`id`)
                        LEFT JOIN users scheduled_to
                            ON(s.`schedule_to`=scheduled_to.`id`)
                        LEFT JOIN request_types rt
                            ON(s.`schedule_for` = rt.`id`)
                        LEFT JOIN barangays b
                            ON(scheduled_to.`barangay_id` = b.`id`)
                                WHERE s.`isDone` = 0 ".$filterByUser." AND s.`deleted_at` IS NULL ORDER BY s.`schedule` ASC ";

        return $this->setquery($query)->get();
    }

    public static function add($data){

        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        $query = self::insert([
            "schedule_for" => $data["schedule_for"],
            "schedule"    => $data["schedule"],
            "schedule_to" => $data["schedule_to"],
            "remarks"     => $data["remarks"],
            "schedule_by" => $data["schedule_by"],
            "isDone"      => $data["isDone"],
            "created_at" => $dateTime,
            "updated_at" => $dateTime,
         ],'schedules');

        return self::setquery($query)->save();
    }


    public function create(){

        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        try {

            $query = self::insert([
                "schedule_for" => $this->data["schedule_for"],
                "schedule"    => $this->data["date"]." ".$this->data["time"],
                "schedule_to" => $this->data["id"],
                "remarks"     => $this->data["remarks"],
                "schedule_by" => $this->data["schedule_by"],
                "isDone"      => 0,
                "created_at" => $dateTime,
                "updated_at" => $dateTime,
             ],'schedules');

             Notification::create([
                "title"   => "New Schedule",
                "content" => "New schedule added to your barangay",
                "created_by" => $this->data["schedule_by"],
                "notify_to"  => [$this->data["id"]]
            ]);

             self::setquery($query)->save();

            return [
                "status" => 200,
                "message" => "Successfully saved!"
            ];
    
        } catch (Exception $e) {
            
            return [
                "status" => 401,
                "message" => "Something went wrong. Please contact your support"
            ];
        }


    }


    public function reSchedule(){

        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        try {

        $query = self::update([
            "schedule"    => $this->data["schedule"],
            "updated_at" => $dateTime,
         ],'schedules',$this->data['id']);

        self::setquery($query)->save();

        

         $activity = $this->setquery("SELECT 
                                        rt.`name` AS 'SCHEDULE_FOR',
                                        DATE_FORMAT(s.`schedule`,'%M %d, %Y @ %h:%i %p') AS 'DATE_SCHED',
                                        s.`schedule_to` AS 'SCHEDULE_TO'
                                    FROM schedules s 
                                        LEFT JOIN request_types rt 
                                            ON(s.`schedule_for` = rt.`id`) WHERE s.id = ".$this->data['id'])->get();


        Notification::create([
            "title"   => "Reschedule of activity",
            "content" => $activity[0]->SCHEDULE_FOR." activity reschedule to ".$activity[0]->DATE_SCHED,
            "created_by" => $this->data["schedule_by"],
            "notify_to"  => [$activity[0]->SCHEDULE_TO] 
        ]);


        return [
            "status" => 200,
            "message" => "Successfully re-scheduled!"
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
        ],'schedules', $this->data['id']);

        try {

            $this->setquery($query)->save();

            $activity = $this->setquery("SELECT 
                                rt.`name` AS 'SCHEDULE_FOR',
                                DATE_FORMAT(s.`schedule`,'%M %d, %Y') AS 'DATE_SCHED',
                                s.`schedule_to` AS 'SCHEDULE_TO'
                            FROM schedules s 
                                LEFT JOIN request_types rt 
                                    ON(s.`schedule_for` = rt.`id`) WHERE s.id = ".$this->data['id'])->get();


            Notification::create([
                "title"   => "Cancelling of activity",
                "content" => $activity[0]->SCHEDULE_FOR." on ".$activity[0]->DATE_SCHED ." has been cancelled.",
                "created_by" => $this->data["schedule_by"],
                "notify_to"  => [$activity[0]->SCHEDULE_TO]
            ]);


            return [
                "status" => 200,
                "message" => "Cancelled Successfully!"
            ];

        } catch (Exception $e) {
            
            return [
                "status" => 401,
                "message" => "Something went wrong. Please contact your support"
            ];
        }
    }

    public function isDone(){

        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        try {

            
            $query = $this->update([
                'isDone'     => 1,
                'updated_at' => $dateTime
            ],'schedules', $this->data['id']);

            $this->setquery($query)->save();


            return [
                "status" => 200,
                "message" => "Scheduled activity done"
            ];

        } catch (Exception $e) {
            
            return [
                "status" => 401,
                "message" => "Something went wrong. Please contact your support"
            ];
        }
    }
}

?>