<?php

$root_dir = $_SERVER['DOCUMENT_ROOT'].'/DIMMAS_ENDPOINTS';
require_once($root_dir.'/includes/Database.php');

class UserController extends Database{

    private $data = null;


    public function __construct($data = ''){
        $this->data = $data;
    }


    public function auth(){
         

        $username = $this->filterData($this->data['username']);
        $password = $this->filterData($this->data['password']);

        $fetch_user = $this->setquery("SELECT * FROM users WHERE username = '$username'")->get();

        if(!$fetch_user){

            return [
                "status" => 404,
                "message" => "No Found User"
            ];

        }

        $fetch_user = $fetch_user[0];

        if(password_verify($password,$fetch_user->password)) {

            $barangay_info = !$fetch_user->barangay_id ? null :
                             $this->setquery("SELECT `name`,latitude,longtitude FROM barangays WHERE id='".$fetch_user->barangay_id."'")
                            ->get()[0];

            

            return [
                "status"    => 200,
                "message"   => "Access Granted",
                "payload"   => [
                    "id"    => $fetch_user->id,
                    "name"  => $fetch_user->name,
                    "username"    => $fetch_user->username,
                    "role_id"     => $fetch_user->user_type_id,
                    "role_name"   => $this->setquery("SELECT `name` FROM user_types WHERE id='".$fetch_user->user_type_id."'")->getField('name'),
                    "barangay_id" => $fetch_user->barangay_id,
                    "barangay_info" => $barangay_info

                ]
            ];
        }


        return [
            "status"  => 401,
            "message" => "Incorrect Username or Password"
        ];

    }



    public function create(){

        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        $options = [
            'cost' => 10,
        ];

        if($this->validateUsername()) return [
                "status"    => 401,
                "message"   => "Username Already Exist!"
        ];
        
        if($this->data["password"] !== $this->data["confirmPassword"]) return [
            "status"    => 401,
            "message"   => "Password and Confirm Password not match!"
        ];


        $barangay_id = $this->data['user_type_id'] == 3 ?  $this->data['barangay_id'] : 'NULL';

        

        $datas = $this->insert([
            'name'     => $this->data['name'],
            'username' => $this->data['username'],
            'password' => password_hash($this->data['password'], PASSWORD_BCRYPT, $options),
            'user_type_id' => $this->data['user_type_id'],
            'barangay_id'  => $barangay_id,
            'created_at'   => $dateTime,
            'updated_at'   => $dateTime
        ],'users');


       if($this->setquery($datas)->save()){
            return [
                "status" => 200,
                "message" => "Successfully Created"
            ];
       }


       return [
            "status" => 401,
            "message" => "Incorrect data entry"
       ];

    }

    public function getAllUser(){
        return $this->setquery("SELECT
                                    u.`id`,
                                    u.`name` AS 'FULLNAME',
                                    u.`user_type_id` as 'USER_TYPE_ID',
                                    u.`barangay_id` as 'BARANGAY_ID',
                                    u.`username` AS 'USERNAME',
                                    ut.`name` AS 'USERTYPE',
                                    b.`name` AS 'BARANGAY'
                                FROM users u
                                    LEFT JOIN user_types ut
                                        ON (u.user_type_id = ut.`id`)
                                    LEFT JOIN barangays b
                                        ON(u.`barangay_id`=b.`id`)")->get();
    }

    public function getBarangayUser(){
        return $this->setquery("SELECT 
                                    s.`id`,
                                    b.`name` AS 'BARANGAY',
                                    s.`name` AS 'USER_NAME'
                                FROM users s
                                    LEFT JOIN barangays b
                                        ON(s.`barangay_id` = b.`id`)
                                    WHERE s.`user_type_id` = 3")->get();
    }


    public function validateUsername(){
        $username = $this->data['username'];
        $fetchExistUser = $this->setquery("SELECT id from users WHERE username ='".$username."'")->get();
        if(isset($this->data['id'])) return (count($fetchExistUser) > 0 && $fetchExistUser[0]->id != $this->data['id']);
        return count($fetchExistUser) > 0;
    }

    public function changePassword(){

        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');
        
        $oldPassword = $this->setquery("SELECT `password` from users WHERE id=".$this->data['id'])
                            ->getField("password");

        if(!password_verify($this->data['oldPassword'],$oldPassword)) return [
            "status"    => 401,
            "message"   => "Incorrect Password!"
        ];


        $options = [
            'cost' => 10,
        ];

        $query = $this->update([
            'password'   => password_hash($this->data['newPassword'], PASSWORD_BCRYPT, $options),
            'updated_at' => $dateTime
        ],"users", $this->data['id']);


        if($this->setquery($query)->save()){
            return [
                "status" => 201,
                "message" => "Successfully Changed!"
            ];
       }


       return [
            "status" => 401,
            "message" => "Incorrect data entry"
       ];

    }

    public function edit(){

        date_default_timezone_set('Asia/Manila');
        $dateTime = date('Y-m-d H:i:s');

        $options = [
            'cost' => 10,
        ];

        if($this->validateUsername()) return [
            "status"    => 401,
            "message"   => "Username Already Exist!"
        ];

        $barangay_id = $this->data['user_type_id'] == 3 ?  $this->data['barangay_id'] : 'NULL';

        $datas = $this->update([
            'name'     => $this->data['name'],
            'username' => $this->data['username'],
            'user_type_id' => $this->data['user_type_id'],
            'barangay_id'  => $barangay_id,
            'updated_at'   => $dateTime
        ],'users',$this->data['id']);


       if($this->setquery($datas)->save()){
            return [
                "status" => 200,
                "message" => "Successfully Changed!"
            ];
       }


       return [
            "status" => 401,
            "message" => "Incorrect data entry"
       ];

    }

    public function getUserType(){
        return $this->setquery("SELECT id,`name` AS 'USERTYPES' FROM user_types")->get();
    }
}