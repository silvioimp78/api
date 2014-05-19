<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
require_once '../scripts/db.php';
require_once '../scripts/login.php';
require_once '../scripts/rooms.php';
$conn = connetti();
$user = login($conn);
if (!isset($user) || ($user == "guest")) {
	header('HTTP/1.1 403 Forbidden');
	die('');
}
$message['status'] = '';
$message['reason'] = '';
if (!isset($_POST['post_id'])) {
	header('HTTP/1.1 400 Bad Request');
	$message['status'] = "err";
	$message['reason'] = 'Invalid request';
	echo json_encode($message);
	die('');
}
if (!is_numeric($_POST['post_id'])) {
	header('HTTP/1.1 400 Bad Request');
	$message['status'] = "err";
	$message['reason'] = 'Invalid request';
	echo json_encode($message);
	die('');
}

mysql_query("START TRANSACTION", $conn);
// inizia transazione
$query_post = "SELECT * FROM posts WHERE idposts = '" . $_POST['post_id'] . "'";
$to_delete = mysql_query($query_post, $conn) or die('Errore Query');
$dati_delete_orig = mysql_fetch_assoc($to_delete);
if (mysql_num_rows($to_delete) != 0) {
	$query_post = "SELECT * FROM posts WHERE idposts = '" . $_POST['post_id'] . "' AND (id_autore = '" . $user['id_users'] . "' OR wall_id = '" . $user['id_users'] . "')";
	$post_data = mysql_query($query_post, $conn);
	$dati_delete = mysql_fetch_assoc($post_data);
	if (mysql_num_rows($post_data) == 0) {
		if ($dati_delete_orig['room_id'] != 0) {
			$admin_query = "SELECT * FROM room_users WHERE room_id = " . $dati_delete_orig['room_id'] . " AND user_id  = " . $user['id_users'] . " AND is_admin = 1";
			$ris_admin = mysql_query($admin_query, $conn);
			$user_room = mysql_fetch_assoc($ris_admin);
			if (mysql_num_rows($ris_admin) == 1)
				$dati_delete = $dati_delete_orig;
			else {
				$message['status'] = "err";
				$message['reason'] = 'Not allowed';
				echo json_encode($message);
				die('');
				//die('Not allowed : you\'re not admin of the room.');
			}
		} else {
			$message['status'] = "err";
			$message['reason'] = 'Not allowed';
			echo json_encode($message);
			die('');
		}
	}
} else {
	header('HTTP/1.1 403 Forbidden');
	$message['status'] = "err";
	$message['reason'] = 'Unauthorized';
	die('');
}
$parent_id = $dati_delete['parent_post_id'];
if ($parent_id != 0) {
	$voice_id = $dati_delete['voice_id'];
	$query_delete = "DELETE FROM posts WHERE idposts = " . $dati_delete['idposts'];
	mysql_query($query_delete, $conn) or die('Query error 1');
	@unlink($_SERVER['DOCUMENT_ROOT'] . "/post/posts/" . $voice_id . ".mp3");
	$message['status'] = "ok";
	$message['reason'] = '';
	echo json_encode($message);

} else if ($parent_id == 0) {
	//verifica figli
	$query_figli = "SELECT * FROM posts WHERE parent_post_id = " . $dati_delete['idposts'];
	$ris_figli = mysql_query($query_figli, $conn) or die('Query error 2');
	$num_figli = mysql_num_rows($ris_figli);
	if ($num_figli == 0) {
		$voice_id = $dati_delete['voice_id'];
		$query_delete = "DELETE FROM posts WHERE idposts = " . $dati_delete['idposts'];
		mysql_query($query_delete, $conn) or die('Query error 3');
		if ((substr($voice_id, 0, 4) != 'none') && ($dati_delete['sharer_id'] == 0)) {
			rename($_SERVER['DOCUMENT_ROOT'] . "/post/posts/" . $voice_id . ".mp3", $_SERVER['DOCUMENT_ROOT'] . "/post/posts/del_iduser_" . $dati_delete['id_autore'] . "_wall_" . $dati_delete['wall_id'] . "_" . $dati_delete['voice_id'] . ".mp3");
		}
		//@unlink($_SERVER['DOCUMENT_ROOT']."/post/posts/".$voice_id.".mp3");
		$message['status'] = "ok";
		$message['reason'] = '';
		echo json_encode($message);
	} else if ($num_figli != 0) {
		// elimina figli
		$query_delete_figli = "DELETE FROM posts WHERE parent_post_id = '" . $dati_delete['idposts'] . "'";
		mysql_query($query_delete_figli, $conn) or die('Query error 4');
		while ($dati_singolo = mysql_fetch_assoc($ris_figli)) {
			rename($_SERVER['DOCUMENT_ROOT'] . "/post/posts/" . $dati_singolo['voice_id'] . ".mp3", $_SERVER['DOCUMENT_ROOT'] . "/post/posts/del_iduser_" . $dati_singolo['id_autore'] . "_wall_" . $dati_singolo['wall_id'] . "_" . $dati_singolo['voice_id'] . ".mp3");
			//@unlink($_SERVER['DOCUMENT_ROOT']."/post/posts/".$dati_singolo['voice_id'].".mp3");
		}
		$voice_id = $dati_delete['voice_id'];
		$query_delete = "DELETE FROM posts WHERE idposts = '" . $dati_delete['idposts'] . "'";
		mysql_query($query_delete, $conn) or die('Query error 5');
		if ((substr($voice_id, 0, 4) != 'none') && ($dati_delete['sharer_id'] == 0)) {
			rename($_SERVER['DOCUMENT_ROOT'] . "/post/posts/" . $voice_id . ".mp3", $_SERVER['DOCUMENT_ROOT'] . "/post/posts/del_iduser_" . $dati_delete['id_autore'] . "_wall_" . $dati_delete['wall_id'] . "_" . $dati_delete['voice_id'] . ".mp3");
		}
		//@unlink($_SERVER['DOCUMENT_ROOT']."/post/posts/".$voice_id.".mp3");
		$message['status'] = "ok";
		$message['reason'] = '';
		echo json_encode($message);
	}
}
mysql_query("COMMIT", $conn);
mysql_close($conn);
?>