<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
require_once '../scripts/db.php';
require_once '../scripts/login.php';
include_once '../languages/' . $lang . '.php';
$conn = connetti();
$user = login($conn);
if (!isset($user) || $user == "guest") {
	header('HTTP/1.1 403 Forbidden');
	$message['id'] = '0';
	$message['reason'] = 'Not Logged';
	echo json_encode($message);
	die('');
}
if (!isset($_POST['search'])) {
	header('HTTP/1.1 400 Bad Request');
	$message['id'] = '0';
	$message['reason'] = 'par_invalid';
	echo json_encode($message);
	die();
}
$search = strip_tags(addslashes(stripslashes($_POST['search'])));
$wildcards = array("%","_");
$search=str_replace($wildcards,"",$search);
if(empty($search)) {
	header('HTTP/1.1 400 Bad Request');
	$message['id'] = '0';
	$message['reason'] = 'empty_search';
	echo json_encode($message);
	die();
}
//Prototipino per ricerca utente.
$parts = explode(' ', $search);
$num = count($parts);

$query = "SELECT * FROM users WHERE active = 1 AND (CONCAT_WS(' ',name, surname) RLIKE '[[:<:]]" . trim($search) . "[A-z]' OR CONCAT_WS(' ',name, surname) RLIKE '[[:<:]]" . trim($search) . "') LIMIT 0,3";

$ris_query = mysql_query($query, $conn);
$num_people = mysql_num_rows($ris_query);

$query = "SELECT * FROM posts WHERE sharer_id = 0 AND (title LIKE ' " . $search . "%'  OR title LIKE '%" . $search . "%') AND type = 1 ORDER BY count DESC LIMIT 0,3";
$ris_post = mysql_query($query, $conn);
$num_hermes = mysql_num_rows($ris_post);

$query = "SELECT * FROM rooms WHERE active = 1 AND (title LIKE '" . $search . "%' OR title LIKE '% " . $search . "%') LIMIT 0,3";
$ris_rooms = mysql_query($query, $conn);
$num_rooms = mysql_num_rows($ris_rooms);
$ct = 0;

if ($num_people == 0) {
	$ris_json[$ct]['type'] = '1';
	$ris_json[$ct]['is_result'] = '0';
	$ct++;
} else {
	while ($profile = mysql_fetch_assoc($ris_query)) {
		$is_listener = false;
		$blocked = false;
		if ($profile['id_users'] != $user['id_users']) {
			$query_listeners = "SELECT * FROM `listeners` WHERE `user_id` = " . $user['id_users'] . " AND `listener_id` = " . $profile['id_users'];
			$listener_ris = mysql_query($query_listeners);
			$dati_list = mysql_fetch_assoc($listener_ris);
			if ((mysql_num_rows($listener_ris) == 0))
				$is_listerner = false;
			else
				$is_listener = true;
		}
		if (file_exists('../avatars/thumb_' . sha1($profile['id_users']) . '.jpg'))
			$avatar = '../avatars/thumb_' . sha1($profile['id_users']) . '.jpg';
		else
			$avatar = '../avatars/noavatar_min.png';
		if (strlen($profile['dream']) > 25)
			$dream = substr($profile['dream'], 0, 25) . '...';
		else
			$dream = substr($profile['dream'], 0, 25);
		if (strlen($dream) == 0)
			$dream = '&nbsp';
		//struttura  di array di risposta
		$ris_json[$ct]['type'] = '1';
		$ris_json[$ct]['is_result'] = '1';
		$ris_json[$ct]['id'] = $profile['id_users'];
		$ris_json[$ct]['avatar'] = $avatar;
		$ris_json[$ct]['full_name'] = $profile['name'] . ' ' . $profile['surname'];
		$ris_json[$ct]['dream_title'] = $dream;
		if ($user['id_users'] != $profile['id_users']) {
			if ($is_listener == true)
				$ris_json[$ct]['added'] = 1;
			else
				$ris_json[$ct]['added'] = 0;
		} else
			$ris_json[$ct]['added'] = 'self';
		$ris_json[$ct]['in_room'] = '-1';
		//valore di default per persone
		$ris_json[$ct]['dest_id'] = '-1';
		$ris_json[$ct]['target'] = '-1';
		$ct++;
	}
}

// RICERCA POSTS //
//Vecchia query per posts

if ($num_hermes == 0) {
	$ris_json[$ct]['type'] = '2';
	$ris_json[$ct]['is_result'] = '0';
	$ct++;
} else {
	while ($ris_post_ser = mysql_fetch_assoc($ris_post)) {
		if ($ris_post_ser['type'] == 1) {
			$target_wall = $ris_post_ser['wall_id'];
			$room_id = $ris_post_ser['room_id'];
			$query_user = "SELECT * FROM users WHERE  id_users = " . $ris_post_ser['id_autore'];
			$profile = mysql_fetch_assoc(mysql_query($query_user, $conn));
			if (file_exists('../avatars/thumb_' . sha1($profile['id_users']) . '.jpg'))
				$avatar = '../avatars/thumb_' . sha1($profile['id_users']) . '.jpg';
			else
				$avatar = '../avatars/noavatar_min.png';
			$titolo_ris = $ris_post_ser['title'];
			if ($titolo_ris == '')
				$titolo_ris = 'Nessun titolo';
			else {
				$len = strlen($titolo_ris);
				if ($len > 22)
					$titolo_ris = substr($titolo_ris, 0, 22) . '...';
			}
			if ($ris_post_ser['parent_post_id'] != 0) {
				$post_link = $ris_post_ser['parent_post_id'];
			} else {
				$post_link = $ris_post_ser['idposts'];
			}
			$ris_json[$ct]['type'] = '2';
			$ris_json[$ct]['is_result'] = '1';
			$ris_json[$ct]['id'] = $profile['id_users'];
			$ris_json[$ct]['avatar'] = $avatar;
			$ris_json[$ct]['full_name'] = $profile['name'] . ' ' . $profile['surname'];
			$ris_json[$ct]['dream_title'] = $titolo_ris;
			$ris_json[$ct]['added'] = '-1';
			//default per post
			if ($target_wall != 0) {
				$ris_json[$ct]['in_room'] = '0';
				$ris_json[$ct]['dest_id'] = $ris_post_ser['wall_id'];
			} else {
				$ris_json[$ct]['in_room'] = '1';
				$ris_json[$ct]['dest_id'] = $ris_post_ser['room_id'];
			}

			if ($ris_post_ser['parent_post_id'] != 0) {

				$ris_json[$ct]['target'] = $ris_post_ser['parent_post_id'];
			} else {
				$ris_json[$ct]['target'] = $ris_post_ser['idposts'];
			}

			$ct++;

		}
	}
}

//RICERCA ROOM//
//Vecchia query rooms

if ($num_rooms == 0) {
	$ris_json[$ct]['type'] = '3';
	$ris_json[$ct]['is_result'] = '0';
	$ct++;
} else {
	while ($room = mysql_fetch_assoc($ris_rooms)) {
		$query_room = "SELECT  * FROM `room_users` WHERE `room_id` = " . $room['room_id'] . " AND `user_id` = " . $user['id_users'];
		$ris_data = mysql_query($query_room, $conn);
		$user_room_data = mysql_fetch_assoc($ris_data);
		if (mysql_num_rows($ris_data) != 0)
			$in_room = true;
		else
			$in_room = false;
		$name = $room['title'];
		if (file_exists('../avatars/room_thumb_' . sha1($room['room_id']) . '.jpg'))
			$avatar = '../avatars/room_thumb_' . sha1($room['room_id']) . '.jpg';
		else
			$avatar = '../avatars/noavatar_min.png';
		$ris_json[$ct]['type'] = '3';
		$ris_json[$ct]['is_result'] = '1';
		$ris_json[$ct]['id'] = $room['room_id'];
		$ris_json[$ct]['avatar'] = $avatar;
		$ris_json[$ct]['full_name'] = $name;
		$ris_json[$ct]['dream_title'] = '-1';

		if ($in_room == true) {
			if ($user_room_data['is_admin'] == '0')
				$ris_json[$ct]['added'] = 1;
			else if ($user_room_data['is_admin'] == '1')
				$ris_json[$ct]['added'] = 'admin';
		} else
			$ris_json[$ct]['added'] = 0;
		$ris_json[$ct]['in_room'] = '-1';
		//valore di default per persone
		$ris_json[$ct]['target'] = '-1';
		$ct++;
	}
}
$response = $_GET["callback"] . "(" . json_encode($ris_json) . ")";
echo $response;
?>