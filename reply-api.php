<?php 
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
 header('Content-type: application/json');

  //reply di un post
  if ((!isset($_GET['id']))&&(!is_numeric($_GET['id'])))
    {
	 $message['err'] = 'err_id';
	 echo json_encode($message);
	 die();	
	}
	
include '../scripts/db.php'; // connette al database la connessione ha la variabile $conn
include '../scripts/login.php';
include '../scripts/user_profile.php';
$conn = connetti();
$user = login($conn);
if (!isset($user) || $user == "guest"){
	header('HTTP/1.1 403 Forbidden');
	die();
}
$query_post = "SELECT * FROM posts WHERE parent_post_id = 0 AND idposts = ".$_GET['id'];

$exists = mysql_num_rows(mysql_query($query_post,$conn));
$query_post = '';
if ($exists == 0)
{ 
 $message['err'] = 'no_post';
 echo json_encode($message);
 die();
}
if (isset($_GET['update']))
{
 if($_GET['update'] == 'latest') 
 {
  if (isset($_GET['start'])) 
  {
   if (is_numeric($_GET['start']))
   { 
   $start = $_GET['start'];
   $query_post = "SELECT * FROM posts WHERE parent_post_id = ".$_GET['id']." AND type = 1 AND idposts > ".$start." ORDER BY idposts ASC";
   }
  }	else die ('Parameter error');
 }
} else $query_post = "SELECT * FROM posts WHERE parent_post_id = ".$_GET['id']." AND type = 1 ORDER BY idposts ASC";
$ris_query = mysql_query($query_post,$conn);
$totale_risposte = mysql_num_rows($ris_query);
if ($totale_risposte == 0)
 {
 $message['info'] = 'no_reply';
 echo json_encode($message);
 die(); 
 }
 $ct=0;
 while($post = mysql_fetch_assoc($ris_query))
 {
	  
$author_data = get_profile($post['id_autore'],$conn);	
 if(file_exists($base_dir.'avatars/thumb_'.sha1($author_data['id_users']).'.jpg')) $avatar = '/avatars/thumb_'.sha1($author_data['id_users']).'.jpg';
   else $avatar  = '/avatars/noavatar_min.png'; 
 
 $message[$ct]['id'] = $post['idposts']; 
 $message[$ct]['author_id'] = $author_data['id_users'];
 if ($user['id_users'] == $post['id_autore']) $message[$ct]['isdeletable'] = '1'; else $message[$ct]['isdeletable'] = '0';
 $message[$ct]['avatar'] =$avatar;
 $message[$ct]['data_time'] = $post['hermes_time'];
 $message[$ct]['full_name'] = $author_data['name'].' '.$author_data['surname'];
 $message[$ct]['voice_id'] = $post['voice_id'];
 $message[$ct]['positive_vote'] = $post['positive_agree'];
 $message[$ct]['negative_vote'] = $post['negative_agree'];
 $message[$ct]['redo'] = '0';
 $query_esiste_voto = 'SELECT * FROM agree WHERE user_id ='.$user['id_users'].' AND post_id = '.$post['idposts'];
 $ris_agree = mysql_query($query_esiste_voto,$conn);
 if($ris_agree != false) {
 $voto_esiste = mysql_num_rows($ris_agree);
 }  else $voto_esiste = 0;
  if ($voto_esiste!=0) $message[$ct]['redo'] = '1';

 $ct++;

 }
if ($_GET['source']== "android") $response = json_encode($message); else
$response = $_GET["callback"] . "(" . json_encode($message) . ")";
echo $response;
?>