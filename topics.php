<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
//header('Content-type: application/json');
require_once '../scripts/db.php';
require_once '../scripts/login.php';
require_once '../scripts/user_profile.php';
require_once '../scripts/rooms.php';

$conn = connetti();
$user = login($conn);
if (!isset($user) || $user == "guest") {
	header('HTTP/1.1 403 Forbidden');
	$message['cookie'] = $_COOKIE['p'];
	$message['id'] = '0';
	$message['reason'] = 'Not Logged';
	echo json_encode($message);
	die('');
}
date_default_timezone_set('Europe/Rome'); 
$weekStart = strtotime( "-1 Sunday 00:00" );
$weekEnd = strtotime( "+1 Saturday 23:59" );
$todayStart = strtotime( "00:00" );
$todayEnd = strtotime( "23:59" );
$query_trends = "SELECT * FROM `arguments` WHERE `time` < ".$weekEnd." ORDER BY `total_post` DESC LIMIT 0, 5";
$query_res = mysql_query($query_trends,$conn);
while ($singolo = mysql_fetch_assoc($query_res)){
	echo '<p>Argomento:'.$singolo['name'].'</p>';
}

?>