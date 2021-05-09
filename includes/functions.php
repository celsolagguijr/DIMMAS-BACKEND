<?php

function toJson($data){
    echo json_encode($data);
}

function validateRequest($request,$request_map){

    if(!$request ||!in_array($request, $request_map)){
        toJson([
            "status" => 404,
            "message" => "Request not Found"
        ]);
        return false;
    }

    return true;
}

function processRequest($ref,$data=[]){
    
    $_class = new $ref($data["data"]);
    $request = $data["request"];
    toJson($_class->$request());
}


function getRequest($data){
    return isset($data) ? $data: false;
}

?>