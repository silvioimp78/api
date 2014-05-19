<?php

class bottle {
	
static public function checkBottle()
{
 global $db;
 $utente = session::getSession();
 if($utente == "guest") throw new Exception("NOT LOGGED");
 if ($utente['daybottle']<2) return true; else return false;	
}

static public function newBottle()
{
		
 global $db;
 $utente = session::getSession();
 if($utente == "guest") throw new Exception("NOT LOGGED");
 $id = $utente['id_users'];
 $me = new users($id);
 $follower = $me -> getFollowing();
 $part_exclude = '';
 
 for ($ct = 0; $ct < count($follower);$ct++) {
     if ($ct != (count($follower)-1)) $part_exclude = $part_exclude.' id_users != '.$follower[$ct]['user_id'].' AND '; else $part_exclude = $part_exclude.' id_users != '.$follower[$ct]['user_id'];
 }

 //crea_e_registra_tape(): //
 $db -> startTransaction(); 
 $db -> doQuery("INSERT INTO `posts` (`id_autore`, `room_id`, `time`, `type`,`parent_post_id`, `parent_user_id`, `category`, `title`, `voice_id`, `url`,`post_ip`, `positive_agree`, `negative_agree`) VALUES ('$id', '0','" . time() . "', '4', '0', '0', '5', 'Bottiglia di prova', 'hashbottiglia', '0','" . $_SERVER['REMOTE_ADDR'] . "', '0', '0');");
  echo "INSERT INTO `posts` (`id_autore`, `room_id`, `time`, `type`,`parent_post_id`, `parent_user_id`, `category`, `title`, `voice_id`, `url`,`post_ip`, `positive_agree`, `negative_agree`) VALUES ('$id','0','" . time() . "', '4', '0', '0', '5', 'Bottiglia di prova', 'hashbottiglia', '0','" . $_SERVER['REMOTE_ADDR'] . "', '0', '0');";
 
 $idtape = $db -> getLastId();
 $db -> doQuery("UPDATE `users` SET `daybottle` =  `daybottle`  + 1 WHERE `id_users` = '" . $id . "'");
 //seleziona destinatari
 //escludi follower


 
 $db -> doQuery("SELECT * FROM users WHERE id_users != ".$id." AND ".$part_exclude." AND receivedbottle < 3 ORDER BY RAND() LIMIT 0,10");
 $destinatari = $db -> getResult();
 $ct= 1;
 foreach ($destinatari as $singolo){
  $to = $singolo['id_users'];
  $db -> doQuery("INSERT INTO `bottles`(`idposts`, `fromuser`, `author_id`,`path_id`,`touser`, `time`, `opened`) VALUES ('$idtape',$id,$id,$ct,'$to','".time()."','0')");
  $db -> doQuery("UPDATE `users` SET `receivedbottle` =  `receivedbottle`  + 1 WHERE `id_users` = '" . $to . "'");
  $ct++;
 }
  $db-> commit();

}


static public function markRead($id_tape,$id_dest)
{
 global $db; 
 $db-> doQuery("UPDATE bottles SET `opened` = 1 WHERE idposts = ".$id_tape." AND touser = ".$id_dest);
}

static  public function resend($id)
{
 global $db;
 //verifica la sessione utente
 //per test ragioneremo sull'utente 350
 $id_resend = 264;
 
 $db -> doQuery("SELECT * FROM bottles WHERE idposts = ".$id);
 if ($db -> getNumRows()== 0) throw new Exception("ERR:BOTTLE NOT FOUND");
 
 $lista_dest = $db -> getResult();
 print_r($lista_dest);
$part_exclude = '';
 $presente = false;
 $path_id = 0;
 $author_id = 0;
 for($ct= 0; $ct < count($lista_dest);$ct++){
 	if ($lista_dest[$ct]['touser'] == $id_resend) {
 		$path_id = $lista_dest[$ct]['path_id'];
 		$author_id = $lista_dest[$ct]['author_id'];
 		$presente = true;
 		}
 	$part_exclude = $part_exclude.' id_users != '.$lista_dest[$ct]['touser'].' AND ';
	 
 }
 
 if ($presente == false) throw new Exception("ERR:CANNOT RESEND");
 
 $me = new users($id_resend);
 $follower = $me -> getFollowing();
 
 for ($ct = 0; $ct < count($follower);$ct++) {
     if ($ct != (count($follower)-1)) $part_exclude = $part_exclude.' id_users != '.$follower[$ct]['user_id'].' AND '; 
 }
 
 $me = new users($lista_dest[0]['fromuser']);
 $follower = $me -> getFollowing();
 
 for ($ct = 0; $ct < count($follower);$ct++) {
     if ($ct != (count($follower)-1)) $part_exclude = $part_exclude.' id_users != '.$follower[$ct]['user_id'].' AND '; else $part_exclude = $part_exclude.' id_users != '.$follower[$ct]['user_id'];
 } 
$part_exclude = $part_exclude." AND id_users != ".$lista_dest[0]['fromuser'];
echo "SELECT * FROM users WHERE ".$part_exclude." LIMIT 0,1";
$db -> doQuery("SELECT * FROM users WHERE ".$part_exclude." LIMIT 0,1");
$dati = $db -> getResult();
$to = $dati[0]['id_users'];
$selezionato = $db -> getResult();
  $db -> doQuery("INSERT INTO `bottles`(`author_id`,`idposts`, `fromuser`, `path_id`,`touser`, `time`, `opened`) VALUES ('$author_id','$id',$id_resend,'$path_id','$to','".time()."','0')");
 }


 static public function received($start = 0, $show = "current") {
		//se start ed end uguali a 0 mostra i primi 10
		global $db;
		//verifica correttezza parametri
	
		
		if (isset($start)){
			if (!is_numeric($start)) throw new Exception("ERR:WRONG PARAMETERS");
		}else throw new Exception("ERR:WRONG PARAMETERS");
		$tape_data = array();

		//check memcached for posts in specified interval

		$query_post = '';
		$user = session::getSession();
		
		if (!isset($user) || $user == "guest")
			throw new Exception("ERR:NOT_LOGGED");
	        $id = $user['id_users'];      
		  
			$ct = 0;
			if (($show == "current") || ($show == "latest") || ($show == "oldest")) {
			
				if (($show == 'current') && ($start == 0)) {
						
					$db -> doQuery("SELECT p.idposts, p.id_autore, p.wall_id, p.room_id, p.sharer_id, p.shared_post_id, p.title as tape_title, p.voice_id, p.positive_agree, p.negative_agree, p.time, p.reply_num, a.name as auth_name, a.surname as auth_surname, f.name as from_name, f.surname as from_surname, t.name as to_name, t.surname as to_surname, b.author_id as author_id, b.fromuser as from_id, b.touser as to_id, b.time as received_time, b.opened FROM posts as p JOIN bottles as b ON p.idposts = b.idposts JOIN users as a ON a.id_users = b.author_id JOIN users as f ON f.id_users = b.fromuser JOIN users as t ON t.id_users = b.touser WHERE b.touser = ".$id." ORDER BY b.time DESC LIMIT 0,10"); 
					$result = $db -> getResult();
				} else if ($start != 0) {
					if ($show == "latest") {
					$db -> doQuery("SELECT p.idposts, p.id_autore, p.wall_id, p.room_id, p.sharer_id, p.shared_post_id, p.title as tape_title, p.voice_id, p.positive_agree, p.negative_agree, p.time, p.reply_num, a.name as auth_name, a.surname as auth_surname, f.name as from_name, f.surname as from_surname, t.name as to_name, t.surname as to_surname, b.author_id as author_id, b.fromuser as from_id, b.touser as to_id, b.time as received_time, b.opened FROM posts as p JOIN bottles as b ON p.idposts = b.idposts JOIN users as a ON a.id_users = b.author_id JOIN users as f ON f.id_users = b.fromuser JOIN users as t ON t.id_users = b.touser WHERE b.touser = ".$id." AND b.transaction_id > ".$start." ORDER BY b.time DESC"); 
						      $result = $db -> doQuery();
							} else if ($show == "oldest"){
					$db -> doQuery("SELECT p.idposts, p.id_autore, p.wall_id, p.room_id, p.sharer_id, p.shared_post_id, p.title as tape_title, p.voice_id, p.positive_agree, p.negative_agree, p.time, p.reply_num, a.name as auth_name, a.surname as auth_surname, f.name as from_name, f.surname as from_surname, t.name as to_name, t.surname as to_surname, b.author_id as author_id, b.fromuser as from_id, b.touser as to_id, b.time as received_time, b.opened FROM posts as p JOIN bottles as b ON p.idposts = b.idposts JOIN users as a ON a.id_users = b.author_id JOIN users as f ON f.id_users = b.fromuser JOIN users as t ON t.id_users = b.touser WHERE b.touser = ".$id." AND b.transaction_id < ".$start." ORDER BY b.time DESC LIMIT 0,10"); 
							  $result = $db -> getResult();	
							}
				} else
					throw new Exception("ERR:WRONG PARAMETERS");
			} else
				throw new Exception("ERR:WRONG PARAMETERS");
	
	
        echo self::create_json($result);
	}		
	 
	static public function send($start = 0, $show = "current") {
		//se start ed end uguali a 0 mostra i primi 10
		global $db;
		//verifica correttezza parametri
	
		
		if (isset($start)){
			if (!is_numeric($start)) throw new Exception("ERR:WRONG PARAMETERS");
		}else throw new Exception("ERR:WRONG PARAMETERS");
		$tape_data = array();

		//check memcached for posts in specified interval

		$query_post = '';
		$user = session::getSession();
		
		if (!isset($user) || $user == "guest")
			throw new Exception("ERR:NOT_LOGGED");
	        $id = $user['id_users'];      
		  
			$ct = 0;
			if (($show == "current") || ($show == "latest") || ($show == "oldest")) {
			
				if (($show == 'current') && ($start == 0)) {
						
					$db -> doQuery("SELECT p.idposts, p.id_autore, p.wall_id, p.room_id, p.sharer_id, p.shared_post_id, p.title as tape_title, p.voice_id, p.positive_agree, p.negative_agree, p.time, p.reply_num, a.name as auth_name, a.surname as auth_surname, f.name as from_name, f.surname as from_surname, t.name as to_name, t.surname as to_surname, b.author_id as author_id, b.fromuser as from_id, b.touser as to_id, b.time as received_time, b.opened FROM posts as p JOIN bottles as b ON p.idposts = b.idposts JOIN users as a ON a.id_users = b.author_id JOIN users as f ON f.id_users = b.fromuser JOIN users as t ON t.id_users = b.touser WHERE b.author_id = ".$id." ORDER BY b.time DESC LIMIT 0,10"); 
					$result = $db -> getResult();
				} else if ($start != 0) {
					if ($show == "latest") {
					$db -> doQuery("SELECT p.idposts, p.id_autore, p.wall_id, p.room_id, p.sharer_id, p.shared_post_id, p.title as tape_title, p.voice_id, p.positive_agree, p.negative_agree, p.time, p.reply_num, a.name as auth_name, a.surname as auth_surname, f.name as from_name, f.surname as from_surname, t.name as to_name, t.surname as to_surname, b.author_id as author_id, b.fromuser as from_id, b.touser as to_id, b.time as received_time, b.opened FROM posts as p JOIN bottles as b ON p.idposts = b.idposts JOIN users as a ON a.id_users = b.author_id JOIN users as f ON f.id_users = b.fromuser JOIN users as t ON t.id_users = b.touser WHERE b.author_id = ".$id." AND b.transaction_id > ".$start." ORDER BY b.time DESC"); 
						      $result = $db -> doQuery();
							} else if ($show == "oldest"){
					$db -> doQuery("SELECT p.idposts, p.id_autore, p.wall_id, p.room_id, p.sharer_id, p.shared_post_id, p.title as tape_title, p.voice_id, p.positive_agree, p.negative_agree, p.time, p.reply_num, a.name as auth_name, a.surname as auth_surname, f.name as from_name, f.surname as from_surname, t.name as to_name, t.surname as to_surname, b.author_id as author_id, b.fromuser as from_id, b.touser as to_id, b.time as received_time, b.opened FROM posts as p JOIN bottles as b ON p.idposts = b.idposts JOIN users as a ON a.id_users = b.author_id JOIN users as f ON f.id_users = b.fromuser JOIN users as t ON t.id_users = b.touser WHERE b.author_id = ".$id." AND b.transaction_id < ".$start." ORDER BY b.time DESC LIMIT 0,10"); 
							  $result = $db -> getResult();	
							}
				} else
					throw new Exception("ERR:WRONG PARAMETERS");
			} else
				throw new Exception("ERR:WRONG PARAMETERS");
	
	
        echo self::create_json($result);
	}	 
  
static private function create_json($array_tape){
	
//aggiunge al json le informazioni supplementeri
// 1) Agree e disagree propri 
// 2) Numero di commenti
// 3) Foto utente, wall_id, room_id
$base_dir = $_SERVER['DOCUMENT_ROOT'];
global $db;
$user = session::getSession();
for($ct = 0; $ct < count($array_tape);$ct++){
	// agree e disagree propri
	$db -> doQuery('SELECT * FROM agree WHERE user_id ='.$user['id_users'].' AND post_id = '.$array_tape[$ct]['idposts'],1);
	$num = $db -> getNumRows();
	$data = '';
	if ($num != 0){
	$data = $db -> getResult();
	$status = $data['agree_status'];
	}
	if ($num == 0) $array_tape[$ct]['my_vote'] = 0; else $array_tape[$ct]['my_vote']= $status;
	if (file_exists($base_dir . '/avatars/thumb_' . sha1($array_tape[$ct]['id_autore']) . '.jpg'))
				$array_tape[$ct]['author_avatar'] = '/avatars/thumb_' . sha1($array_tape[$ct]['id_autore']) . '.jpg';
			else
				$array_tape[$ct]['author_avatar']= 0;
	
				
	if (file_exists($base_dir . '/avatars/thumb_' . sha1($array_tape[$ct]['from_id']) . '.jpg'))
				$array_tape[$ct]['from_avatar'] = '/avatars/thumb_' . sha1($array_tape[$ct]['from_id']) . '.jpg';
			else
				$array_tape[$ct]['from_avatar']= 0;
	
		if (file_exists($base_dir . '/avatars/thumb_' . sha1($array_tape[$ct]['to_id']) . '.jpg'))
				$array_tape[$ct]['to_avatar'] = '/avatars/thumb_' . sha1($array_tape[$ct]['to_id']) . '.jpg';
			else
				$array_tape[$ct]['to_avatar']= 0;
	}
			
	return json_encode($array_tape);		
}


public static function getPath($transaction_id){
	global $db;
	$base_dir = $_SERVER['DOCUMENT_ROOT'];
	if (isset($transaction_id)){
			if (!is_numeric($transaction_id)) throw new Exception("ERR:WRONG PARAMETERS");
		}else throw new Exception("ERR:WRONG PARAMETERS");		
	$db -> doQuery("SELECT u.id_users, u.name, u.surname, b.* FROM bottles as b JOIN users as u ON b.author_id = u.id_users WHERE b.transition_id = ".$transaction_id);
	$data = $db -> getResult();
	$path = $data[0]['path_id'];
	$full_result[0]['user_id'] = $data[0]['author_id'];
	$full_result[0]['name'] = $data[0]['name']; 
	$full_result[0]['surname'] = $data[0]['surname'];
	$db -> doQuery("SELECT b.touser as user_id , u.name, u.surname  FROM bottles AS b JOIN users as u ON u.id_users = b.touser WHERE path_id = ".$path);
	$result_second = $db -> getResult();
	for ($ct = 0; $ct < count($result_second); $ct++) {
          $full_result[$ct+1] = $result_second[$ct];		
	}
	for ($ct = 0; $ct < count($full_result);$ct++){
		if (file_exists($base_dir . '/avatars/thumb_' . sha1($full_result[$ct]['user_id']) . '.jpg'))
				$full_result[$ct]['user_avatar'] = '/avatars/thumb_' . sha1($full_result[$ct]['user_id']) . '.jpg';
			else
				$full_result[$ct]['user_avatar']= 0;
	}
	
  return json_encode($full_result);
}




 
public static function makeList($par_id = 0,$post_id ) {
	global $db;
    //your sql code here
   if ($par_id == 0) $db -> doQuery("SELECT * FROM bottles WHERE author_id = 33 AND idposts = ".$post_id); else
   $db -> doQuery("SELECT * FROM bottles WHERE fromuser = ".$par_id." AND idposts = ".$post_id);

    if ($db -> getNumRows()!= 0) {
    	   $pages = $db -> getResult();
        echo '<ul>';
        foreach ($pages as $page) {
            echo '<li>', $page['touser'];
    		self::makeList($page['touser'],$post_id);
    		echo '</li>';
        }
        echo '</ul>';
    }
} 

  
//query per conoscere primo intermediario e ultimo 
//SELECT * FROM `bottles` as pd JOIN bottles as pr ON pd.touser = pr.fromuser WHERE pr.touser = 660 
/*
function_ritira(id_t)
{
 if (retapes < 10) return false; else delete_bottle_entries();
}

}
 * 
 */
 
 }
?>