<?php
/*
 * Revisionato e corretto da MetalMario
 */
//messaggio di risposta
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');

$message['id'] = '0';
$message['reason'] = '';

if (!isset($_POST['email']) || !isset($_POST['password'])) {
	header('HTTP/1.1 400 Bad Request');
	$message['id'] = '0';
	$message['reason'] = 'par_invalid';
	echo json_encode($message);
	die();
}
$pattern = "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})^";
//pattern mail
if (!preg_match($pattern, $_POST['email'])) {
	header('HTTP/1.1 400 Bad Request');
	$message['reason'] = 'mail_error';
	$message['id'] = '0';
	echo json_encode($message);
	die();
}
$email = $_POST['email'];
$pass = sha1($_POST['password']);
//evito l'injection automaticamente effettuando una sha1 sul parametro inviato
include '../scripts/db.php';
// connette al database la connessione ha la variabile $conn
include '../scripts/login.php';

$conn = connetti();

$query_login = "SELECT * FROM `users` WHERE `email`  = '" . $email . "' AND `password` = '" . $pass . "'";

$user_data_res = mysql_query($query_login, $conn);
if (mysql_num_rows($user_data_res) == 1) {//utente trovato
	$dati_utente = mysql_fetch_assoc($user_data_res);
	$login_data_string = $dati_utente['id_users'] . '%%' . $dati_utente['password'];
	setcookie("p", $login_data_string);
	$message['id'] = $dati_utente['id_users'];
	$message['fullname'] = $dati_utente['name'] . ' ' . $dati_utente['surname'];
	$message['name'] = $dati_utente['name'];
	$message['surname'] = $dati_utente['surname'];
	$message['mail'] = $dati_utente['email'];
	$message['presence'] = $login_data_string;
	$avatar = '';
	if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/avatars/thumb_' . sha1($dati_utente['id_users']) . '.jpg'))
		$avatar = '/avatars/thumb_' . sha1($dati_utente['id_users']) . '.jpg';
	else
		$avatar = '/avatars/noavatarmin.png';
	$message['avatar'] = $avatar;
	
	echo json_encode($message);
} else {//utente non trvato
	header('HTTP/1.1 400 Bad Request');
	$message['id'] = '0';
	$message['reason'] = 'login_error';
	echo json_encode($message);
	die('');
}
?>