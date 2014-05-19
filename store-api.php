<?php
require_once '../scripts/db.php';
require_once '../scripts/login.php';
require_once '../scripts/mail_notify.php';
require_once '../scripts/notify.php';
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');

$conn = connetti();
$user = login($conn);

if (!isset($user) || $user == "guest") {
	header('HTTP/1.1 403 Forbidden');
	$message['id'] = '0';
	$message['reason'] = 'Not Logged';
	echo json_decode($message);
	die();
}

$time_last_hermes = $user['last_hermes_time'] - time();
$time_last_hermes = abs($time_last_hermes);
if ($time_last_hermes < 15) {
	header('HTTP/1.1 403 Forbidden');
	$message['id'] = '2';
	$message['reason'] = 'Flood';
	echo json_decode($message);
	die();
}
$wall = 0;
$room = 0;
$source = "";

if (isset($_POST['source']) || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'ios')) {
	$source = 'ios';
} else if (isset($_POST['source']) && ($_POST['source'] == "android"))
	$source = "android";

$id_user = $user['id_users'];
if (isset($_POST['wall'])) {
	if (!is_numeric($_POST['wall']))
		$message['id'] = '1';
	if ($_POST['wall'] >= 0) {
		$wall = $_POST['wall'];
	} else
		$wall = 0;
}
if (isset($_POST['room'])) {
	if (!is_numeric($_POST['room']))
		$message['id'] = '1';
	if ($_POST['room'] >= 0) {
		$room = $_POST['room'];
	} else
		$room = 0;
}
if (isset($_POST['parent'])) {
	if (!is_numeric($_POST['parent'])) {
		$message['id'] = '1';
	} else {
		$parent = $_POST['parent'];
	}
}
if (isset($_POST['type'])) {
	if (!is_numeric($_POST['type'])) {
		$message['id'] = '1';
	}
	if (($_POST['type'] > 0) && ($_POST['type'] < 4)) {
		$type = $_POST['type'];
	}
}
if ( isset($message['id']) && ($message['id'] == '1') ) {
	header('HTTP/1.1 400 Bad Request');
	$message['reason'] = 'Parameter error';
	echo json_decode($message);
	die();
}
$title = strip_tags(addslashes(stripslashes($_POST['title'])));
if (is_uploaded_file($_FILES['uploadedfile']['tmp_name'])) {

	$hash = sha1($user['id_users'] . rand(1, 100000000) . time());
	$base = $_SERVER['DOCUMENT_ROOT'] . '/post/posts/';
	$contents = file_get_contents($_FILES['uploadedfile']['tmp_name']);
	//$decoded = base64_decode($contents);
	$ext = '';

	chdir($_SERVER['DOCUMENT_ROOT'] . '/post/posts/');
	if ($_POST['source'] == "android") {
		$fp = fopen($base . $hash . '.3gp', 'wb');
		fwrite($fp, $contents);
		fclose($fp);
		exec('ffmpeg -i ' . $hash . '.3gp -ab 32k ' . $hash . '.mp3');
		exec('rm ' . $hash . '.3gp');
	} else {
		$fp = fopen($base . $hash . '.aac', 'wb');
		fwrite($fp, $contents);
		fclose($fp);
		exec('ffmpeg -i ' . $hash . '.aac -ab 32k ' . $hash . '.mp3');
		exec('rm ' . $hash . '.aac');
	}

	if (isset($room) && ($room == 48))
		$hash = 'gff2012_' . $hash;

	$categoria = 1;
	mysql_query("START TRANSACTION", $conn);
	if (($user['id_users'] != $wall) && ($room == 0)) {
		$query_listeners = "SELECT * FROM `listeners` WHERE `user_id` = " . $user['id_users'] . " AND `listener_id` = " . $wall . " AND blocked = 0";
		$listener_ris = mysql_query($query_listeners);
		$dati_list = mysql_fetch_assoc($listener_ris);
		if ((mysql_num_rows($listener_ris) == 0)) {
			header('HTTP/1.1 401 Unauthorized');
			$message['id'] = '4';
			$message['reason'] = 'No listener';
			echo json_decode($message);
			die();
		}
	}
	if ($parent != 0) {
		$query_post = "SELECT * FROM posts WHERE idposts = " . $parent;
		$ris_esiste = mysql_query($query_post, $conn);
		if (mysql_num_rows($ris_esiste) == 0) {
			header('HTTP/1.1 400 Bad Request');
			mysql_query("COMMIT", $conn);
			die('');
		}
		$dati_post_padre = mysql_fetch_assoc($ris_esiste);
		//invia una notifica all'autore
		if ($dati_post_padre['type'] == 2)
			$type = 2;
		if ($user['id_users'] != $dati_post_padre['id_autore']) {
			$autore_dati = get_profile($dati_post_padre['id_autore'], $conn);

			send_push_notify($user, $autore_dati, 3, $dati_post_padre['title'], $dati_post_padre['idposts'], $dati_post_padre['wall_id'], $dati_post_padre['room_id']);
			reply_message_notify($user, $dati_post_padre['id_autore'], $dati_post_padre['title'], $dati_post_padre['wall_id'], $dati_post_padre['room_id']);
		}
	}

	$query_sess = 'UPDATE `users` SET `session`= ' . time() . ',`last_hermes_time` = ' . time() . ' WHERE `id_users` = ' . $user['id_users'];
	mysql_query($query_sess, $conn);

	//verfifica se gia esiste il file in modo da prevenire sovrascritture
	//fwrite($fp, $post_inviare);
	//fclose($fp);

	// determina il tipo di hermes
	if ($type == 2) {
		// inserisce post per messaggio privato
		// wall_id rappresenta il destinatario del messaggio
		// la variabile read rappresenta la lettura del nuovo messaggio

		$sql_url = "INSERT INTO `private_messages` (`id_message`, `id_autore`, `id_dest`, `hermes_time`, `parent_post_id`, `title`, `voice_id`,`post_ip`) VALUES (NULL, '$id_user', '$wall','" . time() . "', '$parent','$title', '$hash','" . $_SERVER['REMOTE_ADDR'] . "');";
		mysql_query($sql_url, $conn);
		mysql_query("COMMIT");
		mysql_close($conn);
	} else if ($type == 3) {

		$pres_query = "SELECT * FROM posts WHERE type = 3 AND id_autore = " . $user['id_users'];
		$ris_pres = mysql_query($pres_query, $conn);
		if (mysql_num_rows($ris_pres) != 0) {
			$file_arr = mysql_fetch_assoc($ris_pres);
			$file = $file_arr['voice_id'];
			//@unlink($base_dir.$file.'.mp3');
			$update = "UPDATE `posts` SET `voice_id`='$hash' WHERE id_autore = " . $user['id_users'] . " AND type = 3";
			mysql_query($update, $conn);
		} else {
			$sql_url = "INSERT INTO `posts` (`idposts`, `id_autore`, `wall_id`, `hermes_time`, `type`,`parent_post_id`, `parent_user_id`, `category`, `title`, `voice_id`, `url`,`post_ip`, `positive_agree`, `negative_agree`) VALUES (NULL, '$id_user', '$wall','" . time() . "', '$type', '$parent', '0', '$categoria', '$title', '$hash', '0','" . $_SERVER['REMOTE_ADDR'] . "', '0', '0');";
			mysql_query($sql_url, $conn);
		}

	} else {

		if ($room != 0) {
			$sql_url = "INSERT INTO `posts` (`idposts`, `id_autore`, `room_id`, `hermes_time`, `type`,`parent_post_id`, `parent_user_id`, `category`, `title`, `voice_id`, `url`,`post_ip`, `positive_agree`, `negative_agree`) VALUES (NULL, '$id_user', '$room','" . time() . "', '$type', '$parent', '0', '$categoria', '$title', '$hash', '0','" . $_SERVER['REMOTE_ADDR'] . "', '0', '0');";

		} else {
			$sql_url = "INSERT INTO `posts` (`idposts`, `id_autore`, `wall_id`, `hermes_time`, `type`,`parent_post_id`, `parent_user_id`, `category`, `title`, `voice_id`, `url`,`post_ip`, `positive_agree`, `negative_agree`) VALUES (NULL, '$id_user', '$wall','" . time() . "', '$type', '$parent', '0', '$categoria', '$title', '$hash', '0','" . $_SERVER['REMOTE_ADDR'] . "', '0', '0');";

		}
		mysql_query($sql_url, $conn);
	}
	mysql_query("COMMIT", $conn);
	//email_notify
	if ($wall != 0) {
		if (($user['id_users'] != $wall) && ($parent == 0) && ($room == 0)) {
			$wall_row = get_profile($wall, $conn);
			send_push_notify($user, $wall_row, 1, $title, 0, $wall, 0);
			send_wall_notify($user, $wall, $title);

		}
	}

}
mysql_close($conn);
echo json_decode($message);
?>