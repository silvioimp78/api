<?php
class tape {

	private static $tape;
	private static $limit = 10;
	private static $cache_at_once = 20;
	private static $utente;
	private $tape_array = array(0 => "empty");
	private $tape_id_cache = array();
	private $followed_users = array();
	private $followed_rooms = array();
	private static $query;
	static public function getMainWallTapes($start = 0, $show = "current", $important = 0) {
		//se start ed end uguali a 0 mostra i primi 10
		//il flag importat se settato a 1 mostra solo i post di utenti o room con una certa reputazione
		//test esclundento il 5% del totale utenti con reputazione bassa
		
		$user = session::getSession();
		if (!isset($user) || $user == "guest")
			throw new Exception("ERR:NOT_LOGGED");
        if (isset($important)){
			if (!is_numeric($id)) throw new Exception("ERR:WRONG PARAMETERS");
		} else throw new Exception("ERR:WRONG PARAMETERS");
		if(($important != 0)&&($important!=1) )throw new Exception("ERR:WRONG PARAMETERS");
		if (isset($start)){
			if (!is_numeric($start)) throw new Exception("ERR:WRONG PARAMETERS");
		}else throw new Exception("ERR:WRONG PARAMETERS");
		$tape_data = array();
		
		global $db;
		$tape_data = array();
        self::$query = self::create_main_wall_query($start,$show,$important);		
		$db -> doQuery(self::$query);
		$tape_data = $db -> getResult();
	    return self::create_json($tape_data);

	}

	static public function getProfileTapes($start = 0, $id, $show = "current") {
		//se start ed end uguali a 0 mostra i primi 10
		global $db;
		$tape_data = array();

		//check memcached for posts in specified interval

		$query_post = '';
		$user = session::getSession();
		if (!isset($user) || $user == "guest")
			throw new Exception("ERR:NOT_LOGGED");
		
		if (isset($id)){
			if (!is_numeric($id)) throw new Exception("ERR:WRONG PARAMETERS");
		} else throw new Exception("ERR:WRONG PARAMETERS");
		
		if (isset($start)){
			if (!is_numeric($start)) throw new Exception("ERR:WRONG PARAMETERS");
		}else throw new Exception("ERR:WRONG PARAMETERS");
			
		$tape_data = array();
		$profile = '';
		$viewable = false;
		if ($id != 0) {
			$profile = new users($id);
			$logged_id = $user['id_users'];
			$db -> doQuery("SELECT * FROM listeners WHERE user_id = " . $logged_id . " AND listener_id = " . $id . " AND confirmed = 1");
			$followed = $db -> getNumRows();
			if ($followed == 1){
				$viewable = true;
			}
			else {
				$public = $profile -> isPublicProfile();
				if ($public == 1)
					$viewable = true;
				else
					$viewable = false;
			}
		} else {
			$viewable = true;
		}
		$result = '';
		if ($id == 0) $id = $user['id_users'];
		if ($viewable == true) {
		
			$ct = 0;
			if (($show == "current") || ($show == "latest") || ($show == "oldest")) {
			
				if (($show == 'current') && ($start == 0)) {
						
					$db -> doQuery("SELECT p. * , u.name, u.surname, u.id_users FROM posts AS p JOIN users AS u ON p.id_autore = u.id_users WHERE type = 1 AND wall_id = ".$id." AND parent_post_id = 0 ORDER BY idposts DESC LIMIT 0,".self::$limit);
					$result = $db -> getResult();
				} else if ($start != 0) {
					if ($show == "latest") {
						$db ->doQuey("SELECT p. * , u.name, u.surname FROM posts AS p JOIN users AS u ON p.id_autore = u.id_users WHERE type = 1 AND wall_id = ".$id." AND parent_post_id = 0 AND idposts > ".$start." ORDER BY idposts DESC");
						      $result = $db -> doQuery();
							} else if ($show == "oldest"){
						      $db -> doQuery($query_post = "SELECT p. * , u.name, u.surname FROM posts AS p JOIN users AS u ON p.id_autore = u.id_users WHERE type = 1 AND wall_id = ".$id." AND parent_post_id = 0 AND idposts < ".$start." ORDER BY idposts DESC LIMIT 0,".self::$limit);
							  $result = $db -> getResult();	
							}
				} else
					throw new Exception("ERR:WRONG PARAMETERS");
			} else
				throw new Exception("ERR:WRONG PARAMETERS");
		}
	
    return self::create_json($result);
	}
	

static public function getRoomTapes($start = 0, $id, $show = "current") {
		//se start ed end uguali a 0 mostra i primi 10
		global $db;
		//verifica correttezza parametri
		if (isset($id)){
			if (!is_numeric($id)) throw new Exception("ERR:WRONG PARAMETERS");
		} else throw new Exception("ERR:WRONG PARAMETERS");
		
		if (isset($start)){
			if (!is_numeric($start)) throw new Exception("ERR:WRONG PARAMETERS");
		}else throw new Exception("ERR:WRONG PARAMETERS");
		$tape_data = array();

		//check memcached for posts in specified interval

		$query_post = '';
		$user = session::getSession();
		if (!isset($user) || $user == "guest")
			throw new Exception("ERR:NOT_LOGGED");
	         
		
			$ct = 0;
			if (($show == "current") || ($show == "latest") || ($show == "oldest")) {
			
				if (($show == 'current') && ($start == 0)) {
						
					$db -> doQuery("SELECT p.idposts, p.id_autore, p.wall_id, p.room_id, p.sharer_id, p.shared_post_id, p.title as tape_title, p.voice_id, p.positive_agree, p.negative_agree, p.time, p.reply_num, u.name, u.surname, u.id_users FROM posts AS p JOIN users AS u ON p.id_autore = u.id_users WHERE type = 1 AND room_id = ".$id." AND parent_post_id = 0 ORDER BY idposts DESC LIMIT 0,".self::$limit);
					$result = $db -> getResult();
				} else if ($start != 0) {
					if ($show == "latest") {
						$db ->doQuey("SELECT p.idposts, p.id_autore, p.wall_id, p.room_id, p.sharer_id, p.shared_post_id, p.title as tape_title, p.voice_id, p.positive_agree, p.negative_agree, p.time, p.reply_num, u.name, u.surname FROM posts AS p JOIN users AS u ON p.id_autore = u.id_users WHERE type = 1 AND room_id = ".$id." AND parent_post_id = 0 AND idposts > ".$start." ORDER BY idposts DESC");
						      $result = $db -> doQuery();
							} else if ($show == "oldest"){
						      $db -> doQuery("SELECT p.idposts, p.id_autore, p.wall_id, p.room_id, p.sharer_id, p.shared_post_id, p.title as tape_title, p.voice_id, p.positive_agree, p.negative_agree, p.time, p.reply_num, u.name, u.surname FROM posts AS p JOIN users AS u ON p.id_autore = u.id_users WHERE type = 1 AND room_id = ".$id." AND parent_post_id = 0 AND idposts < ".$start." ORDER BY idposts DESC LIMIT 0,".self::$limit);
							  $result = $db -> getResult();	
							}
				} else
					throw new Exception("ERR:WRONG PARAMETERS");
			} else
				throw new Exception("ERR:WRONG PARAMETERS");
	
	
        return self::create_json($result);
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
	if($array_tape[$ct]['wall_id']!=0){
				
	if (file_exists($base_dir . '/avatars/thumb_' . sha1($array_tape[$ct]['wall_id']) . '.jpg'))
				$array_tape[$ct]['wall_avatar'] = '/avatars/thumb_' . sha1($array_tape[$ct]['wall_id']) . '.jpg';
			else
				$array_tape[$ct]['wall_avatar']= 0;
	} else {
		if (file_exists($base_dir . '/avatars/room_thumb_' . sha1($array_tape[$ct]['wall_id']) . '.jpg'))
				$array_tape[$ct]['wall_avatar'] = '/avatars/room_thumb_' . sha1($array_tape[$ct]['wall_id']) . '.jpg';
			else
				$array_tape[$ct]['wall_avatar']= 0;
	}
			
			
}

return json_encode($array_tape);

}

static private function create_main_wall_query($start = 0, $show, $important = 0){
	    //se update = 1 allore crea query per aggiornare i rimanenti
	    $query = '';
	 
	    
		$query_post = '';
		self::$utente = session::getSession();
		if (!isset(self::$utente) || self::$utente == "guest")
			throw new Exception("ERR:NOT_LOGGED");
		
		$utente_object = new users(self::$utente['id_users']);
	    $followed_users = $utente_object -> getFollowed();
		$num_user_followed = count($followed_users);
		$followed_rooms = $utente_object -> getFollowedRooms();
		$num_followed_rooms = count($followed_rooms);
		$num_to_remove = 0;
	
	
		//if ($important == 1) echo '1';
		
	
	$ct = 0;
		if (($show == "current") || ($show == "latest") || ($show == "oldest")) {
			if (($show == 'current') && ($start == 0)) {
				$query_post = 'SELECT p.idposts, p.id_autore, p.wall_id, p.room_id, p.sharer_id, p.shared_post_id, p.title as tape_title, p.voice_id, p.positive_agree, p.negative_agree, p.time, p.reply_num, u.name as author_name, u.surname as author_surname,u.reputation as user_reputation , r.title as room_name, r.reputation as room_reputation, w.id_users as wall_id, w.name as wall_name, w.surname as wall_surname,r.room_id,s.name as sharer_name, s.surname as sharer_surname FROM posts as p LEFT JOIN users as u ON p.id_autore = u.id_users  LEFT JOIN users as w ON p.wall_id = w.id_users  LEFT JOIN rooms as r ON (p.room_id != 0 AND p.room_id = r.room_id) LEFT JOIN users as s ON p.sharer_id = s.id_users WHERE parent_post_id = 0 AND type = 1 AND inactive = 0 AND (id_autore = ' . self::$utente['id_users'] . '';
	
			} else if ($start != 0) {
				if ($show == "latest") {
					$query_post = 'SELECT p.idposts, p.id_autore, p.wall_id, p.room_id, p.sharer_id, p.shared_post_id, p.title as tape_title, p.voice_id, p.positive_agree, p.negative_agree, p.time, p.reply_num, u.name as author_name, u.surname as author_surname,u.reputation as user_reputation , r.title as room_name, r.reputation as room_reputation, w.id_users as wall_id, w.name as wall_name, w.surname as wall_surname,r.room_id,s.name as sharer_name, s.surname as sharer_surname FROM posts as p LEFT JOIN users as u ON p.id_autore = u.id_users  LEFT JOIN users as w ON p.wall_id = w.id_users  LEFT JOIN rooms as r ON (p.room_id != 0 AND p.room_id = r.room_id) LEFT JOIN users as s ON p.sharer_id = s.id_users  WHERE parent_post_id = 0 AND type = 1 AND inactive = 0 AND idposts > ' . $start . '  AND (id_autore = ' . self::$utente['id_users'] . '';
				} else if ($show == "oldest")
					$query_post = 'SELECT p.idposts, p.id_autore, p.wall_id, p.room_id, p.sharer_id, p.shared_post_id, p.title as tape_title, p.voice_id, p.positive_agree, p.negative_agree, p.time, p.reply_num, u.name as author_name, u.surname as author_surname,u.reputation as user_reputation , r.title as room_name, r.reputation as room_reputation, w.id_users as wall_id, w.name as wall_name, w.surname as wall_surname,r.room_id,s.name as sharer_name, s.surname as sharer_surname FROM posts as p LEFT JOIN users as u ON p.id_autore = u.id_users  LEFT JOIN users as w ON p.wall_id = w.id_users  LEFT JOIN rooms as r ON (p.room_id != 0 AND p.room_id = r.room_id) LEFT JOIN users as s ON p.sharer_id = s.id_users WHERE parent_post_id = 0 AND type = 1 AND inactive = 0 AND idposts < ' . $start . ' AND (id_autore = ' . self::$utente['id_users'] . '';

			} else
				throw new Exception("ERR:WRONG PARAMETERS");
		} else
			throw new Exception("ERR:WRONG PARAMETERS");
		$userpart = '';
		$admin_room = array();
		if ($num_user_followed != 0) {
			$userpart = $userpart . ' OR ';
			foreach ($followed_users as $ris) {
				$ct++;
				$autore_id = $ris['listener_id'];
				if ($ct != $num_user_followed)
					$userpart = $userpart . 'p.id_autore = ' . $autore_id . ' OR ';
				else
					$userpart = $userpart . 'p.id_autore = ' . $autore_id;
			}
		}
        $roompart ='';
		if ($num_followed_rooms != 0) {
			$roompart = $roompart . ' OR ';
			$ct_room = 0;
			foreach ($followed_rooms as $room_data) {
				$ct_room++;
				if ($ct_room != $num_followed_rooms)
					$roompart = $roompart . 'r.room_id = ' . $room_data['room_id'] . ' OR ';
				else
					$roompart = $roompart . 'r.room_id = ' . $room_data['room_id'];
			}
		}
		if (($num_user_followed == 0) && ($num_followed_rooms == 0))
			$query = 'SELECT * FROM posts WHERE parent_post_id = 0 AND type = 1 AND id_autore = ' . self::$utente['id_users'] . ' ORDER by idposts DESC';
		
else
		$query = $query_post . $userpart.$roompart . ') ORDER BY';
if ($important == 0) $query = $query.' idposts DESC'; else $query = $query . ' user_reputation DESC';
if ($show == "current") $query = $query.' LIMIT 0,'.self::$limit; 
if ($show == "oldest") $query = $query.' LIMIT 0, '.self::$limit;  			
return $query;
}


}
?>