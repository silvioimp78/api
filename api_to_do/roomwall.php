<?php
die();
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
if ((!isset($_GET['id'])) || (!is_numeric($_GET['id']))) {
	header('HTTP/1.1 400 Bad Request');
	$message['status'] = 'id_err';
	echo json_encode($message);
	die();

}

include '../scripts/db.php';
// connette al database la connessione ha la variabile $conn
include '../scripts/login.php';
include '../scripts/user_profile.php';
require_once '../scripts/rooms.php';

$conn = connetti();
$user = login($conn);

$id = $_GET['id'];

$query_listener = 'SELECT * FROM room_users WHERE user_id = ' . $user['id_users'] . ' AND room_id = ' . $id;
$ris_data = mysql_query($query_listener, $conn);
if (mysql_num_rows($ris_data) == 0) {
	header('HTTP/1.1 400 Bad Request');
	$message['status'] = 'add_err';
	echo json_encode($message);
	die();
}
$data_room = mysql_fetch_assoc($ris_data);
if ($data_room['is_admin'] == '1')
	$room_admin = true;
else
	$room_admin = false;

if (!isset($_GET['show']))
	$query_post = "SELECT * FROM posts WHERE room_id = " . $id . " AND parent_post_id = 0 ORDER BY idposts DESC LIMIT 0,10";
else if ($_GET['show'] == 'last')
	$query_post = "SELECT * FROM posts WHERE room_id = " . $id . " AND idposts > " . $_GET['start'] . " AND parent_post_id = 0 ORDER BY idposts DESC LIMIT 0,10";
else if ($_GET['show'] == 'old')
	$query_post = "SELECT * FROM posts WHERE room_id = " . $id . " AND idposts < " . $_GET['start'] . " AND parent_post_id = 0 ORDER BY idposts DESC LIMIT 0,10";
else {
	header('HTTP/1.1 400 Bad Request');
	$message['status'] = 'par_error';
	echo json_encode($message);
	die();
}
$ris_query = mysql_query($query_post, $conn);
$tot_post = mysql_num_rows($ris_query);
if ($tot_post == 0) {
	$message['status'] = 'no_hermes';
	echo json_encode($message);
	die();
}

$ct = 0;

while ($post = mysql_fetch_assoc($ris_query)) {
	$author_data = get_profile($post['id_autore'], $conn);
	if (file_exists($base_dir . 'avatars/thumb_' . sha1($author_data['id_users']) . '.jpg'))
		$avatar = $url . 'avatars/thumb_' . sha1($author_data['id_users']) . '.jpg';
	else
		$avatar = $url . 'avatars/noavatar_min.png';
	$query_reply = "SELECT * FROM posts WHERE parent_post_id = " . $post['idposts'];
	$num_replies = mysql_num_rows(mysql_query($query_reply, $conn));
	if ($post['id_autore'] == $user['id_users'])
		$message[$ct]['isdeletable'] = '1';
	else
		$message[$ct]['isdeletable'] = '0';
	if ($room_admin == true)
		$message[$ct]['isdeletable'] = '1';
	else
		$message[$ct]['isdeletable'] = '0';
	$message[$ct]['id'] = $post['idposts'];
	$message[$ct]['author_id'] = $author_data['id_users'];

	$room_data = get_room($post['room_id'], $conn);
	$title_room = $room_data['title'];
	$message[$ct]['otherwall'] = '1';
	$message[$ct]['author_id'] = $author_data['id_users'];
	$message[$ct]['author_name'] = $author_data['name'];
	$message[$ct]['author_surname'] = $author_data['surname'];
	$message[$ct]['wall_id'] = $post['room_id'];
	$message[$ct]['in_room'] = '1';
	$message[$ct]['wall_name'] = $title_room;

	/* if ($post['sharer_id'] != 0) {
	 $message[$ct]['is_echo'] = '1';
	 $message[$ct]['otherwall'] ='0';
	 } else*/
	$message[$ct]['is_echo'] = '0';

	if (substr($post['voice_id'], 0, 4) != 'none')
		$message[$ct]['istext'] = '0';
	else
		$message[$ct]['istext'] = '1';
	$message[$ct]['voice_id'] = $post['voice_id'];
	$message[$ct]['avatar'] = $avatar;
	$title_hermes = $post['title'];

	$parts = explode(' ', $title_hermes);

	$parte = 0;
	$titolo_print = '';

	for ($cx = 0; $cx < count($parts); $cx++) {
		if (preg_match("%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i", $parts[$cx])) {

			if (substr($parts[$cx], 0, 4) != 'http')
				$parts[$ct] = 'http://' . $parts[$cx];

			$titolo_print = $titolo_print . '<a href="' . $parts[$cx] . '" target="_blank" style = "color:#009345">' . $parts[$cx] . '</a> ';
		} else
			$titolo_print = $titolo_print . $parts[$cx] . ' ';
	}

	$message[$ct]['title'] = $titolo_print;
	$message[$ct]['data_time'] = $post['hermes_time'];
	$message[$ct]['positive_vote'] = $post['positive_agree'];
	$message[$ct]['negative_vote'] = $post['negative_agree'];
	$message[$ct]['otherwall'] = '0';
	if ($user != 'guest')
		$message[$ct]['logged'] = '1';
	else
		$message[$ct]['logged'] = '0';
	$message[$ct]['redo'] = '0';
	$query_esiste_voto = 'SELECT * FROM agree WHERE user_id =' . $user['id_users'] . ' AND post_id = ' . $post['idposts'];
	$ris_agree = mysql_query($query_esiste_voto, $conn);
	if ($ris_agree != false) {
		$voto_esiste = mysql_num_rows($ris_agree);
	} else
		$voto_esiste = 0;
	if ($voto_esiste != 0)
		$message[$ct]['redo'] = '1';
	if ($_GET['show'] == 'last')
		$message[$ct]['is_new'] = '1';
	else
		$message[$ct]['is_new'] = '0';

	$message[$ct]['count'] = $post['count'];
	$message[$ct]['replies'] = $num_replies;
	$ct++;

	if ((!isset($_GET['show'])) || ($_GET['show'] == 'old')) {

		if ($ct == 10)
			break;
	}

}

$response = $_GET["callback"] . "(" . json_encode($message) . ")";
echo $response;
?>