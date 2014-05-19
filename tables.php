<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
require_once '../scripts/db.php';
require_once '../scripts/login.php';
require_once '../scripts/user_profile.php';
require_once '../scripts/rooms.php';

$conn = connetti();
$user = login($conn);
if (!isset($user) || $user == "guest") {
	header('HTTP/1.1 403 Forbidden');
	$message['id'] = '0';
	$message['reason'] = 'Not Logged';
	echo json_encode($message);
	die('');
}
$query_ascolti = "SELECT * FROM listeners AS l LEFT JOIN users AS u ON u.id_users = l.listener_id WHERE (l.user_id = ".$user['id_users']." AND l.listener_id != 294) ORDER BY u.reputation DESC LIMIT 0 , 3";

$res = mysql_query($query_ascolti,$conn);
$pos = 1;
while ($singolo = mysql_fetch_assoc($res)){
	$message[$pos-1]['position'] = $pos;
	$message[$pos-1]['id' ] = $singolo['id_users'];
	$message[$pos-1]['fullname'] = $singolo['name'].' '.$singolo['surname'];
	$pos++;
}
echo json_encode($message);
?>