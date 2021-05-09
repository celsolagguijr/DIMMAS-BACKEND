<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$data = json_decode(file_get_contents('php://input'), true);

$root_dir = $_SERVER['DOCUMENT_ROOT'].'/DIMMAS_ENDPOINTS';
require_once($root_dir.'/Controllers/DengueCaseController.php');
require_once($root_dir.'/includes/functions.php');

$request_type = $_SERVER['REQUEST_METHOD'];
$request = getRequest($request_type === 'GET' ? $_GET['request'] : $data ['request']);

switch ($request_type) {
    case 'PUT':

        $request_map = ['auth'];

        if(!validateRequest($request,$request_map)) return;

        processRequest(DengueCaseController::class,["data"=>$data,"request"=>$request]);

    break;

    case 'POST':

        $request_map = ['addCase'];

        if(!validateRequest($request,$request_map)) return;

        processRequest(DengueCaseController::class,["data"=>$data,"request"=>$request]);

    break;
    
    case 'GET':

        $request_map = ['dashboardDatas','getYears','getRecords','getReportData'];

        if(!validateRequest($request,$request_map)) return;
        
        processRequest(DengueCaseController::class,["data"=>$_GET,"request"=>$request]);

    break;

    default :

        toJson([
            "status" => 404,
            "message" => "Request method not found"
        ]);

    break;
}
