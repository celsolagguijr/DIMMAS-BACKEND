<?php

    $root_dir = $_SERVER['DOCUMENT_ROOT'].'/DIMMAS_ENDPOINTS';
    require_once($root_dir.'/includes/Database.php');

    class Notification extends Database{

        private $data = null;

        public function __construct($data = null){
            $this->data = $data;
        }

        public static function create($datas = []){

            date_default_timezone_set('Asia/Manila');
            $dateTime = date('Y-m-d H:i:s');

            if(count($datas["notify_to"]) <= 0 ) return;
            
            for($i=0; $i < count($datas["notify_to"]); $i++){

                $query = self::insert([
                   "title"   => $datas["title"],
                   "content" => $datas["content"],
                   "created_by" => $datas["created_by"],
                   "notify_to"  => $datas["notify_to"][$i],
                   "isSeen"     => false,
                   "created_at" => $dateTime,
                   "updated_at" => $dateTime,
                ],'notifications');

                self::setquery($query)->save();
            }

        }

        public static function count($id){
            return self::setquery("SELECT COUNT(*) as 'TOTAL_NEW_NOTIFICATION' FROM `notifications` WHERE notify_to='$id' AND isSeen = 0")
                    ->getField('TOTAL_NEW_NOTIFICATION');
        }

        public function showNotification(){

            $result = [];

            $whereClause = isset($this->data['from']) && isset($this->data['to']) ?  
              " WHERE notify_to = ".$this->data["id"]."
                AND DATE_FORMAT(created_at,'%Y-%m-%d') BETWEEN 
                DATE_FORMAT('".$this->data['from']."','%Y-%m-%d') AND DATE_FORMAT('".$this->data['to']."','%Y-%m-%d') ORDER BY created_at DESC"
            : " WHERE notify_to = ".$this->data['id']." AND isSeen = FALSE ORDER BY created_at DESC";


            $monthlyNotifications = $this->setquery("SELECT DISTINCT(DATE_FORMAT(created_at, '%Y-%m')) AS 'MONTH_YEAR_DISPLAY' FROM notifications ".$whereClause)
                ->getArray();

            $months = [
                'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December'
            ];

            $filterBySeen = isset($this->data['from']) && isset($this->data['to']) ? "": "AND isSeen = 0";

            for($i=0; $i < count($monthlyNotifications); $i++){
                $result [] = [
                    "months" => $months[explode('-',$monthlyNotifications[$i])[1] - 1 ]." ".explode('-',$monthlyNotifications[$i])[0],
                    "notifications" => $this->setquery("SELECT
                                                        title,
                                                        content,
                                                        created_by.`name` AS 'CREATED_BY',
                                                        DATE_FORMAT(n.created_at,'%M %d, %Y') AS 'CREATED_AT'
                                                        
                                                    FROM notifications n
                                                        LEFT JOIN users created_by
                                                            ON(n.`created_by` = created_by.`id`)
                                                                
                                                            WHERE n.notify_to = ".$this->data['id']." ".$filterBySeen." AND DATE_FORMAT(n.created_at,'%Y-%m') = '".$monthlyNotifications[$i]."' ORDER BY n.`created_at` ASC")->get() 
                ];
            }


            if(!isset($this->data['from']) && !isset($this->data['to'])){
                $this->setquery("UPDATE notifications SET isSeen = 1 WHERE notify_to =".$this->data['id'])->save();
            }

            return $result;
            
        }

    }

?>