<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
require_once '../scripts/db.php';
$base_dir = $_SERVER['DOCUMENT_ROOT']."/";
	require_once '../scripts/db.php';
	require_once '../scripts/login.php';
	require_once '../scripts/user_profile.php';
	
	include $base_dir.'languages/'.$lang.'.php';
	$message = array();
	$id = '';
	$start = '';
	$conn = connetti();
	$user = login($conn);
	$utente_row ='';
	if(isset($_GET['id']))
	 {
	 if (is_numeric($_GET['id'])) {
	 	$id = $_GET['id'];
		$utente_row = get_profile($id,$conn);
	 } else die('Param Err');

	 } else {
	 	$id = $user['id_users'];
		$utente_rom = $user;
	 }

	$is_listener = false;
	if($user== "guest") {
		unset($user);
		$user['id_users'] = 0;
	}

	$query_listener = 'SELECT * FROM listeners WHERE user_id = '.$user['id_users'].' AND listener_id = '.$id;
	$ris_data = mysql_query($query_listener,$conn);
	$ris_data_row = mysql_fetch_assoc($ris_data);
	$public = false;
	if (mysql_num_rows($ris_data) == 1)
	{
		$is_listener = true;
	}
	$ris_data_row = mysql_fetch_assoc($ris_data);
	if ($user['id_users'] == $id)
		$is_listener = true;
	if(($utente_row['publicprofile'] == 1)) {
		$is_listener = true;
		$public = true;
	}
	if($ris_data_row['blocked'] == 1) {
		$array_data[0]['err']='nolistener';
		$message = json_encode($array_data);
		$is_listener = false;
	}

	if ($is_listener == true)
	{
		if(isset($post_id)) {	
			if ($post_id != 0) {
				$query_post = "SELECT * FROM posts WHERE wall_id = ".$id." AND parent_post_id = 0 AND idposts =".$post_id;
			}
		} 
		else
		{
			if (!isset($_GET['show'])) {
				
				$query_post = "SELECT * FROM posts WHERE type = 1 AND wall_id = ".$id." AND parent_post_id = 0 ORDER BY idposts DESC LIMIT 0, 30";
			} else {
				if ($_GET['show'] == 'last'){
					   if (isset($_GET['start']))
					      {
					      $start = $_GET['start'];
					      if (is_numeric($_GET['start'])) $query_post = "SELECT * FROM posts WHERE type = 1 AND wall_id = ".$id." AND parent_post_id = 0 AND idposts > ".$start." ORDER BY idposts DESC"; else die('Param err');
						  } else die('Param err');
			} else 
			if ($_GET['show'] == 'old'){
					   if (isset($_GET['start']))
					      {
					      $start = $_GET['start'];
					      if (is_numeric($_GET['start'])) $query_post = "SELECT * FROM posts WHERE type = 1 AND wall_id = ".$id." AND parent_post_id = 0 AND idposts < ".$start." ORDER BY idposts DESC LIMIT 0,30"; else die('Param err');
						  } else die('Param err');
			}
			}
			}
		
		$ris_query = mysql_query($query_post,$conn);
		$tot_post = mysql_num_rows($ris_query);

		if(isset($end)) {
			if ($end != 0) {
				$new = true;
			}
		}
		$num_posts = mysql_num_rows($ris_query);
        $ct = 0;
        if ($num_posts == 0) {
        	$message['status'] = "nohermes";
	         $message['id'] ="-1";
	        echo json_encode($message);
	        die('');
           }
		while($post = mysql_fetch_assoc($ris_query))
		{
		$author_data = get_profile($post['id_autore'],$conn);	
        if ($post['id_autore']!=$post['wall_id']) $target_data = get_profile($post['wall_id'],$conn); else $target_data = $author_data;
        if(file_exists($base_dir.'avatars/thumb_'.sha1($author_data['id_users']).'.jpg')) $avatar = '/avatars/thumb_'.sha1($author_data['id_users']).'.jpg'; else $avatar  = '/avatars/noavatar_min.png'; 
        $query_reply = "SELECT * FROM posts WHERE parent_post_id = ".$post['idposts'];
        $num_replies = mysql_num_rows(mysql_query ($query_reply,$conn));
         if (($post['id_autore'] == $user['id_users'])||($post['wall_id'] == $user['id_users'])) $message[$ct]['isdeletable'] = '1'; else $message[$ct]['isdeletable']='0';
 $message[$ct]['id'] = $post['idposts']; 
 $message[$ct]['author_id'] = $author_data['id_users'];
  $message[$ct]['wall_name'] = $target_data['name'].' '.$target_data['surname'];
 if (($author_data['id_users'] != $post['wall_id'])&&($post['room_id']=='0')) 
     {
      $message[$ct]['otherwall'] = '0';
      $message[$ct]['author_id'] = $author_data['id_users'];
      $message[$ct]['author_name'] = $author_data['name'];
	  $message[$ct]['author_surname'] =  $author_data['surname'];
      $message[$ct]['wall_id'] = $post['wall_id'];
     
	  $message[$ct]['in_room'] = '0';
      
     } else if (($author_data['id_users'] == $post['wall_id'])&&($post['room_id']=='0')) 
     {
      $message[$ct]['otherwall'] = '0';
      $message[$ct]['author_id'] = $author_data['id_users'];
      $message[$ct]['author_name'] = $author_data['name'];
	  $message[$ct]['author_surname'] =  $author_data['surname'];
	  $message[$ct]['in_room'] = '0';
     }
  if ($post['sharer_id'] != 0) {
  	$message[$ct]['is_echo'] = '1'; 
	$message[$ct]['otherwall'] ='0';
	$message[$ct]['sharer_id'] = $post['sharer_id'];
  	} else {
  		$message[$ct]['is_echo'] = '0';
		$message[$ct]['sharer_id'] = '0';
	}
if(isset($_GET['show'])) {
	if ($_GET['show'] == 'last') 
	$message[$ct]['is_new'] = '1'; 
}
else 
$message[$ct]['is_new'] = '0';

if (substr($post['voice_id'],0,4) != 'none') $message[$ct]['istext'] = '0'; else $message[$ct]['istext'] = '1';
 $message[$ct]['voice_id'] = $post['voice_id'];
 $message[$ct]['avatar'] =$avatar;
 $title_hermes = $post['title'];

$parts = explode(' ',$title_hermes);

$parte = 0;
$titolo_print = '';

for ($cx = 0; $cx < count($parts); $cx++)
{
	if (preg_match("%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i",$parts[$cx]))
	{

		if (substr($parts[$cx],0,4)!= 'http') $parts[$cx] = 'http://'.$parts[$cx];

	 $titolo_print = $titolo_print.'<a href="'.$parts[$cx].'" target="_blank" style = "color:#009345">'.$parts[$cx].'</a> ';
	} else $titolo_print = $titolo_print.$parts[$cx].' ';
}
 
 $message[$ct]['title'] = $titolo_print;
 $message[$ct]['data_time'] = $post['hermes_time'];
 $message[$ct]['positive_vote'] = $post['positive_agree'];
 $message[$ct]['negative_vote'] = $post['negative_agree'];
 if ($public == false) $message[$ct]['logged'] = '1'; else $message[$ct]['logged'] = '0';
 if ($user != 'guest') $message[$ct]['logged'] = '1';
 $message[$ct]['redo'] = '0';
  $query_esiste_voto = 'SELECT * FROM agree WHERE user_id ='.$user['id_users'].' AND post_id = '.$post['idposts'];
  $ris_agree = mysql_query($query_esiste_voto,$conn);
  if($ris_agree != false) {
  $voto_esiste = mysql_num_rows($ris_agree);
  }
  else $voto_esiste = 0;
  if ($voto_esiste!=0) $message[$ct]['redo'] = '1';
 
 
 $message[$ct]['count'] = $post['count'];
 $message[$ct]['replies']  =$num_replies;
 $ct++;




 } 
 $response = json_encode($message);
 //print_r($message);
echo $response;
 }  else{
	$message['status'] = "nolistener";
	$message['id'] ="-1";
	echo json_encode($message);
	}
			
			
			
	
?>