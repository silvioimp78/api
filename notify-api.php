<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
require_once 'classes/core.php';
$start = 0;
if (isset($_GET['start'])) {
if (!is_numeric($start)) die('error'); else $start = $_GET['start'];
} else $start = 0;
$response = notify::readNotify($start);
echo json_encode($response);
?>