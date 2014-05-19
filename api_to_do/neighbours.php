<?php
die();
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');

if ((!isset($_GET['id'])) || (!is_numeric($_GET['id']))) {
	header('HTTP/1.1 400 Bad Request');
	$message['status'] = 'user_err';
	echo json_encode($message);
	die();
}

include '../scripts/db.php';
// connette al database la connessione ha la variabile $conn
include '../scripts/login.php';
include '../scripts/user_profile.php';

$conn = connetti();
$user = login($conn);
if (!isset($user) || $user == "guest") {
	header('HTTP/1.1 403 Forbidden');
	die();
}
$utente = $_GET['id'];

$query_listener = 'SELECT * FROM listeners WHERE user_id = ' . $utente;
$ris_data = mysql_query($query_listener, $conn);
if (mysql_num_rows($ris_data) == 0) {
	header('HTTP/1.1 400 Bad Request');
	$message['status'] = 'no_listener';
	echo json_encode($message);
	die();
}

$ct = 0;

while ($neighbours = mysql_fetch_assoc($ris_data)) {
	$author_data = get_profile($neighbours['listener_id'], $conn);
	if (file_exists($base_dir . 'avatars/thumb_' . sha1($author_data['id_users']) . '.jpg'))
		$avatar = $url . '/avatars/thumb_' . sha1($author_data['id_users']) . '.jpg';
	else
		$avatar = $url . '/avatars/noavatar_min.png';

	$message[$ct]['user_id'] = $author_data['id_users'];
	$message[$ct]['name'] = $author_data['name'];
	$message[$ct]['surname'] = $author_data['surname'];
	$message[$ct]['birth_date'] = $author_data['birth_date'];
	$message[$ct]['avatar'] = $avatar;
	if ($user != $author_data['id_users']) {
		$query_listneers = "SELECT * FROM `listeners` WHERE `user_id` = " . $user . " AND `listener_id` = " . $author_data['id_users'];
		$listener_ris = mysql_query($query_listneers, $conn);
		if (mysql_num_rows($listener_ris) != 0)
			$message[$ct]['neighbours'] = '1';
		else
			$message[$ct]['neighbours'] = '0';
	} else
		$message[$ct]['neighbours'] = '-1';
	$ct++;

}
echo json_encode($message);
?>