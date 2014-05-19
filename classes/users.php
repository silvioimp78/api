<?php
class users {
	private $logged;
	private $currentUser = 0;
	private $current_logged = "guest";
	private $user_array;
	private $following;
	private $followed;
	private $followed_rooms;
	private $num_following = 0;
	private $num_followed = 0;
	private $num_follower_rooms = 0;

	function __construct($id) {

		
		global $db;
		$dati_user = '';
	
			if (!$db -> doQuery("SELECT * FROM `users` WHERE `id_users` = '" . $id . "'", 1))
				throw new Exception("ERR::NOPROFILE");
			$this -> user_array = $db -> getResult();
			$this -> current_user = $id;
	
		//followed
		$num = '';
			if ($db -> doQuery('SELECT l . * , u.name, u.surname FROM listeners AS l JOIN users AS u ON l.listener_id = u.id_users WHERE l.user_id ='.$id.' AND l.blocked =0 AND l.confirmed =1 ORDER BY u.reputation DESC')) {
				$this -> followed = $db -> getResult();
				$this -> num_followed = count($this -> followed);
			} else $this -> followed = false;
		    

			
   
				if ($db -> doQuery('SELECT l. * , u.name, u.surname FROM listeners AS l JOIN users AS u ON l.user_id = u.id_users WHERE l.listener_id ='.$id.' AND l.blocked =0 AND l.confirmed =1 ORDER BY u.reputation DESC')) {
					$this -> following = $db -> getResult();
					$this -> num_following = count($this -> following);
					
				} else
					$this -> following = false;
		
				//get rooms followed by the users
				 if ($db -> doQuery("SELECT * FROM room_users AS ru RIGHT JOIN rooms AS r ON ru.room_id = r.room_id WHERE ru.user_id = " . $id . " AND r.active = 1 ORDER BY r.total_users DESC")) {
					$this -> followed_rooms = $db -> getResult();
					$this -> num_followed_rooms = count($this -> followed_rooms);
				}

		
		$this -> currentUser = $id;
	}

	public function followedFrom($from) {
		global $db;
		$listener_data = $db -> doQuery("SELECT * FROM listeners WHERE user_id = " . $from . ' AND listener_id = ' . $this -> current_user);
		$num = $db -> getNumRows();
		if ($num == 0)
			return false;
		else if ($num == 1)
			return $listener_data;

	}

	public function getFollowedRooms() {
		return $this -> followed_rooms;
	}

	public function getUserData() {

		return $this -> user_array;
	}

	public function getCurrentID() {
		return $this -> currentUser;
	}

	public function getFollowed() {
		return $this -> followed;
	}

	public function getFollowing() {
		return $this -> following;

	}

	public function getFullName() {

		return $this -> user_array['name'] . ' ' . $this -> user_array['surname'];
	}

	public function getEmail() {
		return $this -> user_array['email'];
	}

	public function getIp() {
		return $this -> user_array['last_ip'];
	}

	public function getDream() {
		return $this -> user_array['dream'];
	}

	public function getDescription() {
		return $this -> user_array['dream'];
	}

	public function getSex() {
		return $this -> user_array['sex'];
	}

	public function getLoginKey() {
		return $this -> login_key;
	}
	
	public function isPublicProfile(){
		return $this -> user_array['publicprofile'];
	}

	// [login_key] => 1231091049507ca79c6317c1.83827411 [active] => 1 [last_hermes_time] => 1393858428 [notifications] => 1 [publicprofile] => 1 [cur_channel_id] => f9a8239e2dcb53e4d0c668ccb2955ee4 [fb_id] => 100003058039332 [reputation_update] => 0 [reputation] => 161 [status] => Mario Villani Ã¨ una recchia! )

	public function Follow() {
		$logged_user = session::getSession();
		if ($logged_user == "guest")
			throw new Exception("ERR NOT LOGGED");
		$logged_id = $logged_user['id_users'];
		if ($logged_id == $logged_user)
			throw new Exception("ERR CANNOT ADD YOURSELF");
		$id_to_follow = $this -> currentUser;
		global $db;
		global $mc;
		$time_reg = time();
		$privacy = $this -> user_array['publicprofile'];
        $db -> startTransaction();
		$db -> doQuery("INSERT INTO `listeners` (`user_id`, `listener_id`, `time_reg`,`confirmed`) VALUES ('$logged_id', '$id_to_follow','$time_reg','$privacy')");
		//get_data followed
		$name = '';
		$surname = '';
		$profile = $mc -> get("profile_".$id_to_follow);
		if ($profile){
			$name = $profile['name'];
			$surname = $profile['surname'];
            $name = $profile['name'];
			$surname = $profile['surname'];
		} else {
			$db -> doQuery("SELECT name,surname FROM users WHERE id_users = ".$id_to_follow,1);
			$profile = $db -> getResult();
			
		}
		$following = $mc -> get("following_" . $logged_id);
		if ($following) {
			$new_row = array();
			$new_row['user_id'] = $logged_id;
			$new_row['listener_id'] = $id_to_follow;
			$new_row['time_reg'] = $time_reg;
			$new_row['block'] = '0';
			$new_row['priori_bloc'] = '0';
			$new_row['publicprofile'] = $privacy;
			$new_row['name'] = $name;
			$new_row['surname'] = $surname;
			//name e surname da aggiungere
			array_push($following, $new_row);
		}

		$db -> doQuery("SELECT * FROM listeners WHERE listener_id = " . $id_to_follow);
		$num_ascoltano = $db -> getNumRows();

		$num_ascoltano++;
		$resto = $num_ascoltano % 5;
		echo 'Resto num ti seguogno:' . $resto;

		if ($resto == 0) {
			echo 'Aggiorno la reuputazione utente';
			//$query_update = "UPDATE `users` SET `reputation` =  `reputation`  + 5 WHERE `id_users` = '" . $id_to_listen . "'";
			//mysql_query($query_update, $conn) or die ('Errore aggiornamento reputazione');

		}
        $db -> commit();
		return true;
	}

	public function unFollow() {
		$logged_user = session::getSession();
		if ($logged_user == "guest")
			throw new Exception("ERR NOT LOGGED");
		$logged_id = $logged_user['id_users'];
		if ($logged_id == $logged_user)
			throw new Exception("ERR CANNOT ADD YOURSELF");
		$id_to_follow = $this -> currentUser;
		global $db;
		global $mc;
		$db -> startTransaction();
		$db -> doQuery("SELECT *  FROM `listeners` WHERE `user_id` = " . $logged_id . " AND `listener_id` = " . $id_to_follow);
		if ($db -> getNumRows() == 0)
			throw new Exception("ERR NOT FOLLOWED");

		$time_reg = time();

		$db -> doQuery("DELETE FROM `listeners` WHERE `user_id` = " . $logged_id . " AND `listener_id` = " . $id_to_follow);
		$following = $mc -> get("following_" . $logged_id);
		if ($following) {
			$mc -> delete("following_" . $logged_id);
		}

		$db -> doQuery("SELECT * FROM listeners WHERE listener_id = " . $id_to_follow);
		$num_ascoltano = $db -> getNumRows();
		$num_ascoltano--;
		$resto = $num_ascoltano % 5;
		echo 'Resto num ti seguogno:' . $resto;

		if ($resto == 0) {
			echo 'Aggiorno la reuputazione utente';
			//$query_update = "UPDATE `users` SET `reputation` =  `reputation`  + 5 WHERE `id_users` = '" . $id_to_listen . "'";
			//mysql_query($query_update, $conn) or die ('Errore aggiornamento reputazione');

		}
        $db -> commit();
		return true;
	}

	public function Ban() {
		$ban_success = false;
		$logged_user = session::getSession();
		if ($logged_user == "guest")
			throw new Exception("ERR NOT LOGGED");
		$logged_id = $logged_user['id_users'];
		if ($logged_id == $logged_user)
			throw new Exception("ERR CANNOT BAN YOURSELF");
		$id_to_ban = $this > currentUser;
		global $db;
		global $mc;
		$db -> doQuery("SELECT * FROM listeners WHERE user_id = ".$id_to_ban." AND listener_id = ".$logged_id."",1);
		$esiste = $db -> getNumRows();
		
		
		if ($esiste == 0){
			//l'utente non esiste caso priori-block
			 $db -> doQuery("INSERT INTO `listeners` (`user_id`, `listener_id`,`blocked`,`priori_block`) VALUES ('$id_to_ban', '".$logged_id."','1','1')");	  
			 $ban_success= true;  
			
		} else if ($esiste == 1) {
			$dati_banned  = $db -> getResult();
			if ($dati_banned['block']=='0'){
			$db -> doQuery("UPDATE `listeners` SET `blocked` = 1 WHERE user_id = ".$id_to_ban." AND listener_id = ".$logged_id);
			$ban_success = true;	
			} else $ban_success = false;
		} 
		$db -> commit();
		//è necessario gestire la reputazione della persona bannata
		
		return $ban_success;
		
	}

function unBan(){
	$unban_success = false;
	$logged_user = session::getSession();
		if ($logged_user == "guest")
			throw new Exception("ERR NOT LOGGED");
		$logged_id = $logged_user['id_users'];
		if ($logged_id == $logged_user)
			throw new Exception("ERR CANNOT BAN YOURSELF");
		$id_to_ban = $this > currentUser;
		global $db;
		global $mc;
		$db -> doQuery("SELECT * FROM listeners WHERE user_id = ".$id_to_ban." AND listener_id = ".$logged_id." AND block = 1",1);
		$esiste = $db -> getNumRows();
		if ($esiste == 1){
			 $dati_banned = $db -> getResult();
			 $priori_block = $dati_banned['priori_block'];
			 if ($priori_blocl == '1'){
			 	$db-> doQuery("DELETE FROM `listeners` WHERE `user_id` = ".$id_to_ban." AND `listener_id` = ".$logged_id);
			 } else {
			 	$db -> doQuery("UPDATE `listeners` SET `blocked` = 0 WHERE user_id = ".$id_to_ban." AND listener_id = ".$logged_id);
			 }
			 
		} else return false;
		//è necessario gestire la reputazione della persona che viene sbloccata
		$db -> commit();
		return true;
}

static function getUserNameByID($id){
	global $db;
	global $mc;
	$cache = $mc -> get("profile_".$id);
	if ($cache) return $cache['name'].' '.$cache['surname']; else {
		$db -> doQuery("SELECT name,surname FROM users WHERE id_users = ".$id,1);
		$name_data = $db -> getResult();
		return $name_data['name'].' '.$name_data['surname'];
	}
}
}
?>
