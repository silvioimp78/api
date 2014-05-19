<?php
die();
include '../scripts/db.php'; // connette al database la connessione ha la variabile $conn

include '../scripts/user_profile.php';
$conn = connetti();

  $id = $_GET['id'];
  $query_listeners = 'SELECT * FROM room_users WHERE  room_id ='.$id;
  $ris = mysql_query($query_listeners,$conn);	 
  $num = mysql_num_rows($ris);
  $ct = 0;
  
   while($listener = mysql_fetch_assoc($ris))
   {
   $utente = get_profile($listener['user_id'],$conn);
   if(file_exists($_SERVER['DOCUMENT_ROOT'].'/avatars/thumb_'.sha1($utente['id_users']).'.jpg')) $avatar = 'http://'.$_SERVER['SERVER_NAME'].'/avatars/thumb_'.sha1($utente['id_users']).'.jpg';
   else $avatar  = 'http://www.sniffroom.com/avatars/noavatar_min.png';
   $users[$ct]['user_id'] = $utente['id_users'];
   $users[$ct]['name'] = $utente['name'];
   $users[$ct]['surname'] = $utente['surname'];
   $users[$ct]['birth_date'] = $utente['birth_date']; 
   if($listener['is_admin'] != 1) $users[$ct]['admin'] = '0';	
   else $users[$ct]['admin'] = '1';
   $ct++;
  }
  echo json_encode($users);
?>