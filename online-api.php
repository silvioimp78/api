<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
 header('Content-type: application/json');

require_once $_SERVER['DOCUMENT_ROOT']."/scripts/db.php";
require_once $_SERVER['DOCUMENT_ROOT']."/scripts/login.php";

$conn = connetti();
$user = login($conn);
if (!isset($user) || $user == "guest") {
	header('HTTP/1.1 403 Forbidden');
	die();
}
$online_people = array();

	$query = "SELECT * FROM `listeners` as l RIGHT JOIN `users`
			as u ON l.listener_id = u.id_users WHERE l.user_id = ".$user['id_users'].'
					AND l.blocked = 0 ORDER BY RAND()';
	$listeners_sql = mysql_query($query,$conn) or die("No listeners");
	$num = mysql_num_rows($listeners_sql);
	global $online_people;
	global $offline_people;
	for ($ct = 0; $ct < $num; $ct++)
	{
		$utente = mysql_fetch_assoc($listeners_sql);
		$current =time();
		if (abs($current - $utente['session']) < 30)
		{
			array_push($online_people, $utente);
		}
	}

	$count =0;
	foreach($online_people as $utente ) {
		if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/avatars/thumb_'.sha1($utente['id_users']).'.jpg'))
			$avatar = 'http://' . $_SERVER['SERVER_NAME'] .'/avatars/thumb_'.sha1($utente['id_users']).'.jpg';
		else
			$avatar = 'http://' . $_SERVER['SERVER_NAME'] .'/avatars/noavatar_min.png';
			$array_online[$count]['id'] = $utente['id_users'];
	    	$array_online[$count]['name'] = str_replace('"',"&quot;", $utente['name']).' '.$utente['surname'];
	        $array_online[$count]['avatar'] = $avatar;
		$count++;
		//if($count == 12) break;
	}

$response = '';
if ($count != 0)
{
$response = $_GET["callback"] . "(" . json_encode($array_online) . ")";
} else {
	$array_online[0]['id']= '0';
	$response = $_GET["callback"] . "(" . json_encode($array_online) . ")";
}
echo $response;

?>