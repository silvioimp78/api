<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
require_once '../scripts/db.php';
require_once '../scripts/login.php';
if (!isset($_GET['post']) && !isset($_GET['vote']) && !isset($_GET['act'])) {
	header('HTTP/1.1 400 Bad Request');
	$message['id'] = '0';
	$message['reason'] = 'par_invalid';
	echo json_encode($message);
	die();
}
$post_id = $_GET['post'];
if (!is_numeric($post_id)) {
	header('HTTP/1.1 400 Bad Request');
	$message['id'] = '0';
	$message['reason'] = 'par_invalid';
	echo json_encode($message);
	die();
}
$vote = $_GET['vote'];
if (!is_numeric($vote)) {
	header('HTTP/1.1 400 Bad Request');
	$message['id'] = '0';
	$message['reason'] = 'par_invalid';
	echo json_encode($message);
	die();
}
$act = $_GET['act'];
$conn = connetti();
$user = login($conn);
if (!isset($user) || $user == "guest") {
	header('HTTP/1.1 403 Forbidden');
	die();
}
include_once '../languages/' . $lang . '.php';
$voto = 0;
mysql_query("START TRANSACTION", $conn);
$query_post = "SELECT * FROM posts WHERE idposts = '" . $post_id . "' AND type = 1";
$res = mysql_query($query_post, $conn);
if (mysql_num_rows($res) == 0) {
	header('HTTP/1.1 500 Internal Server Error');
	$message['id'] = '1';
	$message['reason'] = 'internal_error';
	echo json_encode($message);
	die();
}
$post_data = mysql_fetch_assoc($res);
$positive = $post_data['positive_agree'];
$negative = $post_data['negative_agree'];
$title = $post_data['title'];

if ($act == 'vote') {
	$query_voto_attuale = 'SELECT * FROM agree WHERE user_id =' . $user['id_users'] . ' AND post_id = ' . $post_id;
	$ris_agree = mysql_query($query_voto_attuale, $conn);
	$voto_esiste = mysql_num_rows($ris_agree);
	if ($voto_esiste != 0) {
		// se esiste il voto attuale lo recupera
		$temp = mysql_fetch_assoc($ris_agree);
		$voto_attuale = $temp['agree_status'];
	}
	//recupera numero di voti dal post
	$query_post = "SELECT * FROM posts WHERE idposts = " . $post_id;
	$post_data = mysql_fetch_assoc(mysql_query($query_post, $conn));
	$positive = $post_data['positive_agree'];
	$negative = $post_data['negative_agree'];
	$reputation = $post_data['reputation'];
	$title = $post_data['title'];

	// agggiorno il db
	if ($voto_esiste == 0) {
		//aggiungie un nuovo voto al dB

		if ($vote == 1) {
			$query_update_post = "UPDATE posts SET positive_agree = " . ($positive + 1) . ", reputation = " . ($reputation + 1) . " WHERE idposts = " . $post_id;
			$query_modifica_agree = "INSERT INTO agree (user_id, post_id, agree_status) VALUES (" . $user['id_users'] . "," . $post_id . "," . $vote . ")";
			$voto_esiste = 1;
		}
		if ($vote == -1) {
			$query_modifica_agree = "INSERT INTO agree (user_id, post_id, agree_status) VALUES (" . $user['id_users'] . "," . $post_id . "," . $vote . ")";
			$query_update_post = "UPDATE posts SET negative_agree = " . ($negative + 1) . ", reputation = " . ($reputation - 1) . " WHERE idposts = " . $post_id;
			$voto_esiste = 1;
		}

	} else {
		if ($vote == 0) {
			if ($voto_attuale == 1) {
				$query_modifica_agree = "DELETE FROM `agree` WHERE `user_id` = " . $user['id_users'] . " AND `post_id` = " . $post_id;
				$query_update_post = "UPDATE posts SET positive_agree = " . ($positive - 1) . ", reputation = " . ($reputation - 1) . " WHERE idposts = " . $post_id;
			} else if ($voto_attuale == -1) {
				$query_modifica_agree = "DELETE FROM `agree` WHERE `user_id` = " . $user['id_users'] . " AND `post_id` = " . $post_id;
				$query_update_post = "UPDATE posts SET negative_agree = " . ($negative - 1) . ", reputation = " . ($reputation + 1) . " WHERE idposts = " . $post_id;
			}
		}

		if (($vote == 1) && ($voto_attuale == -1)) {
			$query_update_post = "UPDATE posts SET positive_agree = " . ($positive + 1) . ", negative_agree = " . ($negative - 1) . ", reputation = " . ($reputation + 2) . " WHERE idposts = " . $post_id;
			$query_modifica_agree = "UPDATE `agree` SET agree_status = " . $vote . " WHERE user_id =" . $user['id_users'] . " AND post_id = " . $post_id;
		}

		if (($vote == -1) && ($voto_attuale == 1)) {
			$query_modifica_agree = "UPDATE `agree` SET agree_status = " . $vote . " WHERE user_id =" . $user['id_users'] . " AND post_id = " . $post_id;
			$query_update_post = "UPDATE posts SET negative_agree = " . ($negative + 1) . ", positive_agree = " . ($positive - 1) . ", reputation = " . ($reputation - 2) . " WHERE idposts = " . $post_id;
		}
	}

	if (isset($query_modifica_agree))
		mysql_query($query_modifica_agree);
	if (isset($query_update_post))
		mysql_query($query_update_post);
	mysql_query("COMMIT");
	//riesegue la query sul post per ottenere i nuovi valori
	$query_post = "SELECT * FROM posts WHERE idposts = '" . $post_id . "' AND type = 1";
	$res = mysql_query($query_post, $conn);
	if (mysql_num_rows($res) == 0) {
		header('HTTP/1.1 500 Internal Server Error');
		$message['id'] = '1';
		$message['reason'] = 'internal_error';
		echo json_encode($message);
		die();
	}
	$post_data = mysql_fetch_assoc($res);
	$message = array();
	$message['positive'] = $post_data['positive_agree'];
	$message['negative'] = $post_data['negative_agree'];

	$query_voto_attuale = 'SELECT * FROM agree WHERE user_id =' . $user['id_users'] . ' AND post_id = ' . $post_id;
	$ris_agree = mysql_query($query_voto_attuale, $conn);
	$voto_esiste = mysql_num_rows($ris_agree);
	if ($voto_esiste != 0) {
		$message['redo'] = "1";

	} else
		$message['redo'] = "0";

	echo json_encode($message);

	mysql_close($conn);
}
?>