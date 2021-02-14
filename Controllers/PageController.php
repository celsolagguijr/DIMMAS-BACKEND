<?php

$root_dir = $_SERVER['DOCUMENT_ROOT'].'/DIMMAS_ENDPOINTS';
require_once($root_dir.'/includes/Database.php');

class PageController extends Database {

    private $data = null;

    public function __construct($data = ''){
        $this->data = $data;
    }
    
    public function getContentCategories(){
        return $this->setquery("SELECT * FROM content_categories")->get();
    }


    public function show(){

        $type = $this->data["type"] !== ''  ? "pc.content_category_id = '".$this->data["type"]."' AND" : "";
        $getContent = $this->data["type"] !== ''  ?  "" : "pc.`content` AS 'CONTENT', " ;
        
       return $this->setquery("SELECT 
                                    pc.id,
                                    pc.`title` AS 'TITLE',
                                    $getContent
                                    pc.`content_category_id` AS 'category_id',
                                    cc.`name` AS 'CATEGORY_NAME',
                                    s.`name` AS 'POSTED_BY',
                                    DATE_FORMAT(pc.created_at,'%M %d, %Y @ %h:%i %p') AS 'DATE_CREATED'
                                
                                FROM page_contents pc
                                    LEFT JOIN content_categories cc
                                        ON(pc.`content_category_id` = cc.`id`)
                                    LEFT JOIN users s
                                        ON(s.`id` = pc.`id`)
                                            WHERE $type pc.`deleted_at` IS NULL ")->get();
    }

    public function create(){

        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        try {

            $query = $this->insert([
                'title'     => $this->data['title'],
                'content' => $this->filterData($this->data['content']),
                'content_category_id' => $this->data['content_category_id'],
                'user_id' => $this->data['user_id'],
                'created_at'   => $dateTime,
                'updated_at'   => $dateTime
            ],'page_contents');

            $this->setquery($query)->save();

            return [
                "status" => 200,
                "message" => "Successfully saved!",
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
                'title'     => $this->data['title'],
                'content' => $this->filterData($this->data['content']),
                'content_category_id' => $this->data['content_category_id'],
                'updated_at'   => $dateTime
            ],'page_contents', $this->data['id']);

            $this->setquery($query)->save();

            return [
                "status" => 200,
                "message" => "Successfully saved!",
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

        try {

            $query = $this->update([
                'deleted_at'   => $dateTime
            ],'page_contents', $this->data['id']);

            $this->setquery($query)->save();

            return [
                "status" => 200,
                "message" => "Successfully deleted!",
            ];

        } catch (Exception $e) {
            return [
                "status" => 401,
                "message" => "Something went wrong. Please contact your support"
            ];
        }
    }

    public function viewInformation(){
        return $this->setquery("SELECT
                                    pc.`title`,
                                    pc.`content`,
                                    cc.name AS 'category',
                                    s.`name` AS 'posted_by',
                                    DATE_FORMAT(pc.`created_at`,'%M %d, %Y') AS 'created_at'
                                
                                FROM page_contents pc
                                    LEFT JOIN users s
                                        ON(pc.`user_id` = s.`id`)
                                    LEFT JOIN content_categories cc
                                        ON(pc.`content_category_id` = cc.`id`) WHERE pc.id ='".$this->data['id']."'")->get()[0];
    }
}