<?php

$root_dir = $_SERVER['DOCUMENT_ROOT'].'/DIMMAS_ENDPOINTS';
require_once($root_dir.'/includes/Database.php');

class BarangayController extends Database {

    private $data = null;
    private $table = "barangays";

    public function __construct($data = ''){
        $this->data = $data;
    }
    
    public function show(){
        return $this->setquery("SELECT id,`name`,latitude,longtitude FROM barangays WHERE deleted_at IS NULL;")->get();
    }

    public function create(){

        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        $query = $this->insert([
                    'name'       => $this->data['name'],
                    'latitude'   => $this->data['lat'],
                    'longtitude' => $this->data['lng'],
                    'created_at' => $dateTime,
                    'updated_at' => $dateTime
                ],$this->table);

        try {

           $response = $this->setquery($query)->save();

            return [
                "status" => 200,
                "message" => "Successfully Saved!",
                "savedData"    => [
                    'id'   => $response["id"],
                    'name'  => $this->data['name'],
                    'latitude' => $this->data['lat'],
                    'longtitude' => $this->data['lng'],
                ]
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

        $query = $this->update([
            'name'       => $this->data['name'],
            'latitude'   => $this->data['lat'],
            'longtitude' => $this->data['lng'],
            'updated_at' => $dateTime
        ],$this->table, $this->data['id']);

        try {

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

}