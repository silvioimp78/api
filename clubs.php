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
    $admin = false;
	if (isset($_GET['admin'])){
	  if ($_GET['admin'] =="true") $admin = true;
	}
    $user_id = $user['id_users'];
	$id = Array();
   if ($admin == false){
      $query = "SELECT * FROM room_users AS ru RIGHT JOIN rooms AS r ON ru.room_id = r.room_id WHERE ru.user_id = ".$user_id." AND r.active = 1 ORDER BY r.total_users DESC";
       } else  $query = "SELECT * FROM room_users AS ru RIGHT JOIN rooms AS r ON ru.room_id = r.room_id WHERE ru.user_id = ".$user_id." AND r.active = 1 AND ru.is_admin = 1 ORDER BY r.total_users DESC";
	$room_user = mysql_query($query,$conn);
	if($room_user == false ) return;
	if(mysql_num_rows($room_user) == 0) {
		$message['id'] = 0;
		$message['reason'] = 'norooms';
		echo json_encode($message);
	}
	$ct = 0;
	while($rooms = mysql_fetch_assoc($room_user))
	{
		$id = $rooms['room_id'];
		$query_reputation = "SELECT SUM(reputation) AS rep_utente ,id_autore FROM posts WHERE `room_id` = ".$id.' GROUP BY id_autore';
		$temp_rep = mysql_query($query_reputation,$conn);
		
		$message[$ct]['room_id'] = $id;
        $message[$ct]['title'] = $rooms['title'];
		$message[$ct]['num_users'] = $rooms['total_users'];
		$query_num_messages =" SELECT COUNT(*) as tot_messaggi FROM posts WHERE room_id = ".$id;
		$res = mysql_query($query_num_messages,$conn);
		$res_assoc = mysql_fetch_assoc($res);
		$message[$ct]['tot_tapes']= $res_assoc['tot_messaggi'];
		$query_admin =" SELECT * FROM room_users WHERE room_id = ".$id." AND user_id = ".$user_id;
		$res = mysql_query($query_admin,$conn);
		$res_assoc = mysql_fetch_assoc($res);
		$message[$ct]['admin']= $res_assoc['is_admin'];
		$avatar = "";
		 if (file_exists($_SERVER['DOCUMENT_ROOT'].'/avatars/room_avat_'.sha1($rooms['room_id']).'.jpg'))
	 $avatar = '/avatars/room_avat_'.sha1($rooms['room_id']).'.jpg';
	 else $avatar = "/avatars/noavatar.png";
	    $message[$ct]['avatar'] = $avatar; 
		$ct++;
	 
	 
	

	
	
}
echo $_GET["callback"] . "(" . json_encode($message) . ")";
?>