<?php

    abstract class Configuration{

        private static $configDatas = array (
                                                'host'       => 'localhost',
                                                'username'   => 'root',
                                                'password'   => '',
                                                'db'         => 'dimmas'
                                            );


        public static function dataBaseData(){
            return self::$configDatas;
        }


    }





?>