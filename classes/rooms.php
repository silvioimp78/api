<?php 
class room {
	private $room_data ='';
	private $room_users = '';
	private $room_admin = array();
	private $current_room = 0;
	private $followed_room = array();
	
	function __construct($id){
		global $db;
		global $mc;
		$this -> room_data = $mc -> get("room_" . $id);
		if (!$this -> room_data) {
			//non in cache
			$this -> room_data = $db -> doQuery("SELECT * FROM `rooms` WHERE `room_id` = '" . $id . "' AND active = 1");
			if ($this -> room_data == false)
				throw new Exception("ERR::NOPROFILE");
			$this -> current_room = $id;
			$mc -> set("room_" . $id, $this -> room_data);

		}
	 //room_users and admin
	 $this -> room_users = $mc -> get("roomusers_" . $id);
		if (!$this -> room_users) {
			//non in cache
			$this -> room_users = $db -> doQuery('SELECT * FROM room_users WHERE  room_id ='.$id);	
			$mc -> set("roomusers_" . $id, $this -> room_users);

		}
	   print_r($this -> room_users);
	   foreach ($this -> room_users as $singolo) {
		   if ($singolo['is_admin']=='1') array_push($this->room_admin,$singolo);
	   }
	}

public function getRoomData()
{
 return $this -> room_data;	
}	

public function getRoomUsers(){
	return $this -> room_users;
}
public function getRoomAdmin(){
	return $this -> room_admin;
}

public function getRoomFollowed($id){
	global $mc;
	global $db;
    $user_id = users::getCurrentLoggedId();
    if ($user_id == "guest") throw new Exception("ERR:NOT_LOGGED");    
    $this -> followed_room = $db -> doQuery("SELECT * FROM room_users AS ru RIGHT JOIN rooms AS r ON ru.room_id = r.room_id WHERE ru.user_id = ".$user_id." AND r.active = 1 ORDER BY r.total_users DESC");
    return $this -> followed_room;
    	
}

static function getRoomNameByID($id){
	global $db;
	global $mc;
	$cache = $mc -> get("room_".$id);
	if ($cache) return $cache['title']; else {
		$db-> doQuery("SELECT title FROM rooms WHERE room_id  = ".$id,1);
		$room_data = $db -> getResult();
		return $room_data['title'];
	}
}
	  
	  
}
?>