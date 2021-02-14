<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

$root_dir = $_SERVER['DOCUMENT_ROOT'].'/DIMMAS_ENDPOINTS';
require_once($root_dir.'/Controllers/NotificationController.php');

$id = $_GET["id"];

$response = json_encode(["result" => Notification::count($id)]);

echo "retry: 5000\n\n";

echo "data: {$response} \n\n";

flush();

?>