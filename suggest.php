<?php
$list = array();
include '../scripts/db.php';
include '../scripts/login.php';
include '../scripts/user_profile.php';
include '../scripts/rooms.php';
$conn = connetti();
$user = login($conn);
if(!isset($user)) die('Not logged.');
if ($user == "guest") die();
$query ="SELECT * FROM listeners WHERE user_id = ".$user['id_users'];
$num = 0;
$res = mysql_query($query);
while($singolo = mysql_fetch_assoc($res)) {
	$list[$num] = $singolo;
	$num++;
}
$query_sugg = "SELECT * FROM listeners WHERE user_id != ".$user['id_users']." AND listener_id != ".$user['id_users']." AND ";
for($ct = 0; $ct< $num; $ct++) {
	if ($ct != $num-1)
		$query_sugg = $query_sugg. "`listener_id`  != '".$list[$ct]['listener_id']."' AND ";
	else
		$query_sugg = $query_sugg. "`listener_id`  != '".$list[$ct]['listener_id']."'";
}

$query_sugg = $query_sugg." GROUP BY listener_id ORDER BY RAND() LIMIT 0, 50";

$listeners_sql = mysql_query($query_sugg,$conn) or die("No listeners");
$num = mysql_num_rows($listeners_sql);
for ($ct = 0; $ct < $num; $ct++)
{
	$utente = mysql_fetch_assoc($listeners_sql);
	$dati_user = get_profile($utente['listener_id'],$conn);
	$avatar = sha1($utente['listener_id']);
	if (($dati_user != 'false')&&($dati_user['active']=='1')) {
		if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/avatars/thumb_'.$avatar.'.jpg'))
			$ris_json[$ct]['avatar'] = $avatar;
		else
			$ris_json[$ct]['avatar'] = 0;
		//echo '<a href="profile.php?id='.$dati_user['id_users'].'">
				//<img src="'.$avatar.'"class="mini" rel="tooltip" data-original-title="'.str_replace('"',"&quot;", $dati_user['name']).' '.$dati_user['surname'].'"/></a>';

	$ris_json[$ct]['type'] = '1';
	$ris_json[$ct]['user_id'] = $utente['listener_id'];
	$ris_json[$ct]['fullname'] = $dati_user['name'] . " " . $dati_user['surname'];
	}
}
//room suggestion
//cerca le room dei tuoi amici e vede quelle dove non sei iscritto e te le suggerisce
$query ="SELECT * FROM listeners AS l RIGHT JOIN room_users AS u ON u.user_id = l.listener_id GROUP BY room_id";
$num = 0;
$res = mysql_query($query);
while($singolo =mysql_fetch_assoc($res)) {
	$room_amici = get_room($singolo['room_id'],$conn);
	if (($room_amici != 'false')&&($room_amici['active']=='1')) {
		$query_follow = "SELECT * FROM room_users WHERE user_id = ".$user['id_users']." AND room_id = ".$singolo['room_id'];
		$ris = mysql_query($query_follow,$conn);
		$num = mysql_num_rows($ris);
		$avatar = sha1($room_amici['room_id']);
		if ($num == 0) {
			if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/avatars/room_thumb_'.$avatar.'.jpg'))
			$ris_json[$ct]['avatar'] = $avatar;
		else
			$ris_json[$ct]['avatar'] = 0;
		//echo '<a href="room.php?id='.$room_amici['room_id'].'">
				//<img src="'.$avatar.'"class="mini" rel="tooltip" data-original-title="'.str_replace('"',"&quot;", $room_amici['title']).'"/></a>';
		
		$ris_json[$ct]['type'] = '2';
		$ris_json[$ct]['user_id'] = $singolo['room_id'];
		$ris_json[$ct]['fullname'] = $room_amici['title'];
		$ct++;
		}
	}
}
$response = $_GET["callback"] . "(" . json_encode($ris_json) . ")";
echo $response;
?>
