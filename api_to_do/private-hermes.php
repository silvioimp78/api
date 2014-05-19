<?php

 die();

if ((!isset($_GET['user']))||(!is_numeric($_GET['user'])))
 {
  $message['status'] = 'user_err';
  echo json_encode($message);
  die();	 
 }
 
include '../scripts/db.php'; // connette al database la connessione ha la variabile $conn
include '../scripts/login.php';
include '../scripts/user_profile.php';

$conn = connetti();

$utente= $_GET['user'];


if (isset($_GET['view'])) 
{
 $view = $_GET['view'];
 if ($view == 'inbox') {
  
if(!isset($_GET['show'])) $query_post = "SELECT * FROM posts WHERE wall_id = ".$utente." AND  type = 2 AND parent_post_id = 0 ORDER BY idposts DESC LIMIT 0,10"; 
else if($_GET['show'] == 'last') $query_post = "SELECT * FROM posts WHERE wall_id = ".$utente." AND type = 2 AND idposts > ".$_GET['start']." AND parent_post_id = 0 ORDER BY idposts DESC LIMIT 0,10"; else 
 if ($_GET['show'] == 'load') $query_post = "SELECT * FROM posts WHERE wall_id = ".$utente." AND type = 2 AND idposts < ".$_GET['start']." AND parent_post_id = 0 ORDER BY idposts DESC LIMIT 0,10"; else die('par_err');
 } else if($view == 'outbox')
   {
   if(!isset($_GET['show'])) $query_post = "SELECT * FROM posts WHERE id_autore = ".$utente." AND  type = 2 AND parent_post_id = 0 ORDER BY idposts DESC LIMIT 0,10"; 
else if($_GET['show'] == 'last') $query_post = "SELECT * FROM posts WHERE id_autore = ".$utente." AND type = 2 AND idposts > ".$_GET['start']." AND parent_post_id = 0 ORDER BY idposts DESC LIMIT 0,10"; else 
 if ($_GET['show'] == 'load') $query_post = "SELECT * FROM posts WHERE id_autore = ".$utente." AND type = 2 AND idposts < ".$_GET['start']." AND parent_post_id = 0 ORDER BY idposts DESC LIMIT 0,10"; else die('par_err');
   }
   }
$ris_query = mysql_query($query_post,$conn);
$tot_post = mysql_num_rows($ris_query);
if ($tot_post == 0)
 {
  $message['status'] = 'no_hermes';
  echo json_encode($message);
  die();
 }

$ct = 0;

while($post = mysql_fetch_assoc($ris_query))
{
 $author_data = get_profile($post['id_autore'],$conn);	
 if(file_exists($base_dir.'avatars/thumb_'.sha1($author_data['id_users']).'.jpg')) $avatar = $url.'avatars/thumb_'.sha1($author_data['id_users']).'.jpg';
   else $avatar  = $url.'avatars/noavatar_min.png'; 
  $query_reply = "SELECT * FROM posts WHERE parent_post_id = ".$post['idposts'];
  $num_replies = mysql_num_rows(mysql_query ($query_reply,$conn));
  

 
 $message[$ct]['author_id'] = $author_data['id_users'];
 $message[$ct]['post_id'] = $post['idposts'];
 $message[$ct]['avatar'] =$avatar;
 $message[$ct]['title'] = $post['title'];
 $message[$ct]['data_time'] = $post['time_created'];
 $message[$ct]['full_name'] = $author_data['name'].' '.$author_data['surname'];
 $message[$ct]['positive'] = $post['positive_agree'];
 $message[$ct]['negative'] = $post['negative_agree'];
 $message[$ct]['file_name'] = $post['voice_id'];
 $message[$ct]['total_replies']  =$num_replies;
 $ct++;


}

 echo json_encode($message);
?>