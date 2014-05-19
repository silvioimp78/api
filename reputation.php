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
//calccola la reputazione settimanle dell'utente ed 
//aggiorna quella globale
mysql_query("START TRASANCTION",$conn);
$query_users = "SELECT id_users FROM users";
$res = mysql_query($query_users,$conn);

while($singolo = mysql_fetch_assoc($res)){
	$reputation = get_reputation($singolo,$conn);

//update user row
$query_update = "UPDATE `users` SET `reputation` =  `reputation`  + ".$reputation." WHERE `id_users` = '" . $singolo['id_users'] . "'";
mysql_query($query_update,$conn);
echo '<p>Reputazione per id:'.$singolo['id_users'].'</p>';
}
mysql_query("COMMIT",$conn);
?>