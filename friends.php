<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
include '../scripts/db.php';
include '../scripts/login.php';
$conn = connetti();
$user = login($conn);
if (!isset($user) || $user == "guest") {
	header('HTTP/1.1 403 Forbidden');
	$message['id'] = '0';
	$message['reason'] = 'Not Logged';
	echo json_encode($message);
	die('');
}

$action = '';

if (isset($_GET['action'])) {
	$action = strtolower($_GET['action']);

	if (($action != 'followed') && ($action != 'following')) {
		header('HTTP/1.1 403 Forbidden');
		$message['id'] = '0';
		$message['reason'] = 'Wrong parameter';
		echo json_encode($message);
		die('');
	}
}
$id = '0';
if (isset($_GET['id'])) {
	if (is_numeric($_GET['id']))
		$id = $_GET['id'];

} else {
	header('HTTP/1.1 403 Forbidden');
	$message['id'] = '0';
	$message['reason'] = 'Wrong parameter';
	echo json_encode($message);
	die('');
}
$message=array();
if ($id == '0') $id = $user['id_users'];
if ($action == 'followed')
{
   
	$num = '';
	$mc = new Memcached();
    $mc->addServer("127.0.0.1", 11211);
	$dati_user = '';
	$risultato = $mc -> get("followed_".$id);
	if ($risultato){
		shuffle($risultato);
		$num = count($risultato);
	} else {
	$risultato = array();
	$query_listeners = 'SELECT * FROM listeners WHERE  user_id ='.$id.' AND blocked = 0 ORDER BY RAND()';
	$ris = mysql_query($query_listeners,$conn);
	while ($singolo = mysql_fetch_assoc($ris)){
	      array_push($risultato, $singolo);	
	   }
	}
	$mc -> set("followed_".$id,$risultato);
	$num = count($risultato);
	if ($num == 0){ 
	     $message[0]['id']=$user['id_users'];
		 $message[0]['followed']='0';
		 $message[0]['name'] = 'no';
		 $message[0]['avatar']='no';
		
	}
	
	
    
	for ($i = 0; $i < $num;$i++)
	{
		$utente = get_profile($risultato[$i]['listener_id'],$conn);
	
		if(file_exists($_SERVER['DOCUMENT_ROOT'].'/avatars/thumb_'.sha1($utente['id_users']).'.jpg')) $avatar = '/avatars/thumb_'.sha1($utente['id_users']).'.jpg';
		else $avatar  = '/avatars/noavatar_min.png';
        $message[$i]['id'] = $id;
		$message[$i]['followed'] = $utente['id_users'];
		$message[$i]['name'] = $utente['name'].' '.$utente['surname'];
		$message[$i]['avatar'] = $avatar;                              
	
	}	
	
}else if ($action == 'following')

{
	$num = '';
	$mc = new Memcached();
    $mc->addServer("127.0.0.1", 11211);
	$dati_user = '';
	$risultato = $mc -> get("following_".$id);
	if ($risultato){
		shuffle($risultato);
		$num = count($risultato);
	} else {
	$risultato = array();
	$query_listeners = 'SELECT * FROM listeners WHERE  listener_id ='.$id.' AND blocked = 0 ORDER BY RAND()';
	$ris = mysql_query($query_listeners,$conn);
	while ($singolo = mysql_fetch_assoc($ris)){
	      array_push($risultato, $singolo);	
	   }
	}
	$num = count($risultato);
	$mc -> set("following_".$id,$risultato);
	if ($num == 0){ 
	     $message[0]['id']=$user['id_users'];
		 $message[0]['followed']='0';
		 $message[0]['name'] = 'no';
		 $message[0]['avatar']='no';
		
	}
	
    
	for ($i = 0; $i < $num;$i++)
	{
		
		$utente = get_profile($risultato[$i]['user_id'],$conn);
		if(file_exists($_SERVER['DOCUMENT_ROOT'].'/avatars/thumb_'.sha1($utente['id_users']).'.jpg')) $avatar = '/avatars/thumb_'.sha1($utente['id_users']).'.jpg';
		else $avatar  = '/avatars/noavatar_min.png';
		$message[$i]['id'] = $utente['id_users'];
		$message[$i]['followed'] = $id;
		$message[$i]['name'] = $utente['name'].' '.$utente['surname'];
		$message[$i]['avatar'] = $avatar;         

			}	
	
	
	}
echo json_encode($message);

function get_profile($id,$conn)
{
    $mc = new Memcached();
    $mc->addServer("127.0.0.1", 11211);
	$dati_user = '';
	$risultato = $mc -> get("profile_".$id);
	if ($risultato){
		
		return $risultato;
		
	} else {
	$query_current_user = "SELECT * FROM `users` WHERE `id_users` = '".$id."'";
	$ris = mysql_query($query_current_user,$conn);
	if (mysql_num_rows($ris) == 0)
		return 'false';
	else {
		$dati_user = mysql_fetch_assoc($ris);
		$mc -> set("profile_".$id,$dati_user);
		return $dati_user;
	}

	}
}
?>
