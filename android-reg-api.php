<?php
include '../scripts/db.php';
include '../scripts/login.php';
$conn = connetti();
$dati_utente = login($conn);
if (!isset($dati_utente) || ($dati_utente == "guest")) {
	mysql_close($conn);
	die('Not Logged');
}

if (isset($_POST['android_id']) && isset($_POST['auth'])) {
	$correct_key =  md5($_POST['android_id'] . "semsgpdcl33cm");
	if($_POST['auth'] != $correct_key)
		die('Invalid android_id');
	$android_id = base64_encode($_POST['android_id']);
	$query = "SELECT * FROM `mobile_id` WHERE `id_user` = " . $dati_utente['id_users']." AND `mobile_id` = '".$android_id."'";
	$res = mysql_query($query, $conn);
	if ($res == false) {
		//non è presente un device con quell'id
		mysql_query("START TRANSACTION");
		$query_insert = "INSERT INTO `mobile_id`(`id_user`, `device_id`) VALUES ('" . $dati_utente['id_users'] . "','" . $android_id . "')";
		mysql_query($query_insert, $conn);
		mysql_query("COMMIT");
		mysql_close($conn);
	}
	/* else {
		$data = mysql_fetch_assoc($res);
		if ($data['device_id'] != $android_id) {
			mysql_query("START TRANSACTION");
			$update_id = "UPDATE `mobile_id` SET `device_id` = " . $android_id . " WHERE id_user = " . $dati_utente['id_users'];
			mysql_query($update_id, $conn);
			mysql_query("COMMIT");
			mysql_close($conn);
		}
	}*/
} else {
	mysql_close($conn);
	die("Parameter error");
}
?>