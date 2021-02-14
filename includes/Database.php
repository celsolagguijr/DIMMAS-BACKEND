<?php

    require_once 'Configuration.php';

    class Database extends Configuration{


        private static $query="";
        private  $connection = null;

        //private
         public function __construct(){
           $this->connect();
         }

        private function connect(){
             $this->connection= new mysqli  (
                                                self::dataBaseData()['host'],
                                                self::dataBaseData()['username'],
                                                self::dataBaseData()['password'],
                                                self::dataBaseData()['db']
                                            );
			 if(!$this->connection){
                die("<h4 style='color:red;'>Database Connection Failed! Please Contact your Administrator.</h4> <h4 style='color:red;'>Error: ".$this->connection->connect_error."</h4>");
                exit;
             }
        }

        public static function setquery($query=""){
             self::$query=$query;
             //return $this;
             return new self;
        }

        private function executequery(){
            return $this->connection->query(self::$query);
        }

        public function get(){
           $res=$this->executequery();
           $array=array();
           while($data=mysqli_fetch_object($res)){
               $array[]=$data;
           }
           return $array;
        }


        public function getArray(){
            $res=$this->executequery();
            $array=array();

            while($data=mysqli_fetch_row($res)){
               array_push($array,$data[0]);
            }
            return $array;
         }

        public function getField($field){
           $res=$this->executequery();
           $result="";
           while($data=mysqli_fetch_object($res)){
               $result=$data->$field;
           }
           return $result;
        }

        public function getFields(){
            $res=$this->executequery();
            $array=array();
            while($data=mysqli_fetch_field($res)){
                $array[]=$data->name;
            }
            return $array;
        }

        public function save(){
            $res = $this->executequery();

            $id = $this->getLastId() ? $this->getLastId() : null;
            

            return $res && ($id!==null) ? ["obj" => $this,"id" => $id] : $res;
        }

        public function dbConnection(){
            return $this->connection;
        }


        public function getLastId(){
            return mysqli_insert_id($this->connection);
        }

        public function security(){
            return base64_decode("Q2Vsc28gTGFnZ3VpIGpyLiBQT2dpIQ==");
        }

        protected function filterData($data){

            if(!$this->connection) $this->connect();

            $data = trim($data);
            $data = mysqli_real_escape_string($this->connection,$data);
            return $data;
        }


        protected static function backUpDataBase(){

            $host     = self::dataBaseData()['host'];
            $usernme  = self::dataBaseData()['username'];
            $password = self::dataBaseData()['password'];
            $dbName   = self::dataBaseData()['db'];
            $dirs     = self::backUpDirectories();
            $filename = "/ecis_backup_".date('Ymdhis').".sql";
            $resultList = [];

            date_default_timezone_set('Asia/Manila');

            for($i = 0; $i < count($dirs); $i++){

                $returnVar = null;
                $output = null;

                $backupDir = $dirs[$i].$filename;
                exec("C:/xampp/mysql/bin/mysqldump --routines --host=$host --user=$usernme --password=$password $dbName > ".$backupDir);
                array_push($resultList,$dirs[$i]);

            }
            return array("result"=>$resultList,"fileName"=>$filename);

        }



        protected function insert($datas= [] , $table = ""){

            $fields = "";
            $values = "";
            $counter = 1;

            foreach ($datas as $key => $value) {

                if($counter != count($datas)){
                    $fields .= "`".$key."`".",";
                    $values .= $value === 'NULL' ? "NULL," :"'".$value."'".",";
                }else{
                    $fields .= "`".$key."`";
                    $values .= $value === 'NULL' ? "NULL" :  "'".$value."'";
                }

                $counter++;
            }


            return ("INSERT INTO `$table` ($fields)VALUES($values)");

        }


        protected function update($datas=[], $table, $recordID = NULL){

            $setters = "";
            $counter = 1;

            foreach ($datas as $key => $value) {

                if($counter != count($datas)){
                    $setters .= "`".$key."`"." = ".($value === 'NULL' ?  "NULL," : "'$value',");
                }else{
                    $setters .= "`".$key."`"." = ".($value === 'NULL' ?  "NULL" : "'$value'");
                }

                $counter++;
            }

            $condition = "id = '".$recordID."'";

            return ("UPDATE `$table` SET $setters WHERE $condition");
        }

    }

    //Created By: Celso Laggui Jr.

?>