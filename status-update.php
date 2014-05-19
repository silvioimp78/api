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
	$message['cookie'] = $_COOKIE['p'];
	$message['id'] = '0';
	$message['reason'] = 'Not Logged';
	echo json_encode($message);
	die('');
}
$message = strip_tags(addslashes(stripslashes($_POST['message'])));
$query_update = 'UPDATE `users` SET `status`="'.$message.'" WHERE id_users = '.$user['id_users'];
mysql_query($query_update,$conn) or die('Errore Query');
?>