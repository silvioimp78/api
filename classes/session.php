<?php
/*
 *	Sessione - Classe Astratta
 *
 */
class session {
	static private $current_logged = 'guest';
	static private $followed = array();
	static private $rooms = array();
	public static function getSession() {
		global $db;
		@session_start();
		if (isset($_SESSION['secret']))
			return 'guest';
		if (isset($_COOKIE['p'])) {
			if (($_COOKIE['p'] == 'logout') && (empty($_SESSION['email'])) && (empty($_SESSION['password'])))
				return 'guest';
		}
		if ((!isset($_SESSION['email'])) && (!isset($_SESSION['password']))) {

			if (isset($_COOKIE['p']) && ($_COOKIE['p'] != 'logout')) {
				$login_data = explode('%%', $_COOKIE['p']);
				$db -> doQuery("SELECT * FROM `users` WHERE `id_users`  = '" . $login_data[0] . "' AND `password` = '" . $login_data[1] . "'", 1);
				if ($db -> getNumRows() == 0)
					return 'guest';
				$data_user = $db -> getResult();
				/* Lang inclusione - funzionizzare*/
				$base_dir = $_SERVER['DOCUMENT_ROOT'] . '/';
				if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
					$lang = 'it';
				else
					$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
				if (!file_exists($base_dir . 'languages/' . $lang . '.php'))
					$lang = 'en';
				/* ---------*/
				$db -> doQuery("UPDATE `users` SET `session`= " . time() . ", `last_ip` = '" . $_SERVER['REMOTE_ADDR'] . "', `lang` = '" . $lang . "' WHERE `id_users` = " . $data_user['id_users']);
				$_SESSION['email'] = $data_user['email'];
				$_SESSION['password'] = $data_user['password'];
				include_once "KLogger.php";
				date_default_timezone_set("Europe/Rome");
				global $base_dir;
				$log = new KLogger($base_dir . "logs/logins_cookie/" . date('d-m-y') . ".txt", KLogger::INFO);
				$log -> LogInfo("" . $data_user['email'] . " s'� ricollegato con IP : " . $_SERVER['REMOTE_ADDR'] . " usando 'Ricordami' con User Agent : " . $_SERVER['HTTP_USER_AGENT'] . "");
				/*self::$current_logged = $data_user['id_users'];
				 $utente = new users(self::$current_logged);
				 self::$followed = $utente -> getFollowed();
				 self::$rooms = $utente -> getFollowedRooms();*/ //non serve

				return $data_user;
			} else
				return "guest";
		} else {

			//sei collegato
			$db -> doQuery("SELECT * FROM `users` WHERE `email`  = '" . $_SESSION['email'] . "' AND password = '" . $_SESSION['password'] . "'", 1);
			if ($db -> getNumRows() == 0)
				return 'guest';
			$data_user = $db -> getResult();
			/* Lang inclusione - funzionizzare*/
			$base_dir = $_SERVER['DOCUMENT_ROOT'] . '/';
			if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
				$lang = 'it';
			else
				$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
			if (!file_exists($base_dir . 'languages/' . $lang . '.php'))
				$lang = 'en';
			/* ---------*/
			$db -> doQuery("UPDATE `users` SET `session`= " . time() . ", `last_ip` = '" . $_SERVER['REMOTE_ADDR'] . "', `lang` = '" . $lang . "'  WHERE `id_users` = " . $data_user['id_users']);
			/*self::$current_logged = $data_user['id_users'];
			 $utente = new users(self::$current_logged);
			 self::$followed = $utente -> getFollowed();
			 self::$rooms = $utente -> getFollowedRooms();*/ //non serve
			return $data_user;

		}
	}

	/* Non servono, vanno invocate sulla classe Utente e non qui.
	 public static function getFollowed() {
	 return self::$followed;
	 }

	 public static function getFollowedRooms() {
	 return self::$rooms;
	 }
	 */
	public static function getRequests(){
	 global $db;	
	 $logged_users = session::getSession();
	 if ($logged_users == "guest") throw new Exception("ERR NOT LOGGED");
	 $logged_id = $logged_users['id_users'];
	 $db -> doQuery("SELECT * FROM listeners WHERE listener_id = ".$logged_id." AND confirmed = 0");
     $num_requests = $db -> getNumRows();
	 if ($num_requests == 0 ) return false; else 
	 return $db -> getResult();	
	} 
	
	public static function confirmRequest($id){
	 global $db;
	 global $mc;	
	 $logged_users = session::getSession();
	 if ($logged_users == "guest") throw new Exception("ERR NOT LOGGED");
	 $logged_id = $logged_users['id_users'];
	 $db -> doQuery("SELECT * FROM listeners WHERE user_id = ".$id." AND listener_id = ".$logged_id." AND confirmed = 0",1);
     $num_requests = $db -> getNumRows();
	 if ($num_requests == 0) throw new Exception("ERR REQUEST NON FOUND");
	 $db -> doQuery("UPDATE listeners SET `confirmed` = 1 WHERE user_id = ".$id." AND listener_id = ".$logged_id);
	 //memcached update
	 $following = $mc -> get("following_".$logged_id);
	 if ($following){
	 	for($ct = 0; $ct < count($following);$ct++){
			 if (($following[$ct]['user_id']==$id)&&($following[$ct]['listener_id']==$logged_id)) $following[$ct]['confirmed']='1';
		 }
		$mc -> replace("following_".$logged_id,$following);
	 }
	 
	 return true;
	}
	
	public static function doLogin($user, $password) {
		global $db;
		//message codes error
		// 0 Login effettuata
		// 1 Form Incompleta
		// 2 Email sbagliata
		// 3 email e password sbagliate
		// 4 gia loggato
		// 5 Utente non attivo

		$base_dir = $_SERVER['DOCUMENT_ROOT'] . '/';
		if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
			$lang = 'it';
		else
			$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		if (!file_exists($base_dir . 'languages/' . $lang . '.php'))
			$lang = 'en';
		/* -- */
		include $base_dir . 'languages/' . $lang . '.php';
		if (!isset($user) || !isset($password)) {
			return 1;
		}
		/*Check mail */
		$pattern = "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})^";
		if (!preg_match($pattern, $user)) {//ho passato una mail malformata all'atto del login
			return 2;
		}

		$pass = sha1($password);
		//La SHA1 impedisce una SQL injection

		/* Avvio una sessione*/
		session_start();
		/*Se già connesso invio un messaggio d'errore */
		if (isset($_SESSION['email']) || isset($_SESSION['password'])) {
			return 4;
		}
		/* ---- */

		$db -> doQuery("SELECT * FROM `users` WHERE `email`  = '" . $user . "' AND `password` = '" . $pass . "'", 1);

		if ($db -> getNumRows() == 1) {//se ho trovato l'utente
			//creo un cookie che vale fino alla chiusura del browser
			$data_user = $db -> getResult();
			/*Verifico se l'utente è attivo */
			if ($data_user['active'] == 0) {
				return 5;
			}
			date_default_timezone_set("Europe/Rome");
			$dati['data_last'] = date('d/m/y', time());
			$dati['ora_last'] = date('H:i:s', time());
			$db -> doQuery("UPDATE `users` SET `last_ip`= '" . $_SERVER['REMOTE_ADDR'] . "', `cur_channel_id`= '" . cripta_safer(uniqid()) . "', `last_date` = '" . $dati['data_last'] . "', `last_time` = '" . $dati['ora_last'] . "', `user_agent` = '" . $_SERVER['HTTP_USER_AGENT'] . "' WHERE `email`  = '" . $user . "'");

			/* Verifico se ha chiesto di ricordare la sessione */
			if (isset($_POST['ricorda'])) {
				if ($_POST['ricorda'] == "ok")
					setcookie('p', ($data_user['id_users'] . '%%' . $data_user['password']), time() + 2629800, '/');
			}
			$_SESSION['email'] = $data_user['email'];
			$_SESSION['password'] = $pass;
			/* Loggo l'accesso come standard(desktop) */
			$log = new KLogger("../logs/logins/" . date('d-m-y', time()) . ".txt", KLogger::INFO);
			$log -> LogInfo("Standard " . $data_user['email'] . " collegato con IP : " . $_SERVER['REMOTE_ADDR'] . " e User Agent : " . $_SERVER['HTTP_USER_AGENT'] . "");
			/* Notifico l'avvenuto login */
			//self::$current_logged = $data_user['id_users']; //non serve nemmeno questo
			return 0;
		} else {//se fallisce la query
			return 3;
		}

	}

	/* Non serve
	 static public function getCurrentLoggedId() {
	 return self::$current_logged;
	 }
	 */

	static public function doLogout() {
		global $db;
		$session = self::getSession();
		print_r($session);
		if ($session == "guest")
			return false;
		$db -> doQuery("UPDATE users SET session = '" . (time() - 700) . "' WHERE id_users = '" . $session . "'");
		echo "UPDATE users SET session = '" . (time() - 700) . "' WHERE id_users = '" . self::$current_logged . "'";
		date_default_timezone_set("Europe/Rome");
		//$log = new KLogger ( "../logs/logouts/". date('d-m-y') .".txt", KLogger::INFO );
		//$log->LogInfo("".$_SESSION['email']. " s'� disconnesso con IP : " . $_SERVER['REMOTE_ADDR'] . " e User Agent : " . $_SERVER['HTTP_USER_AGENT'] . "");
		$_SESSION['email'] = '';
		$_SESSION['password'] = '';
		session_unset();
		session_destroy();
		if (isset($_COOKIE['p']))
			setcookie('p', 'logout', 0, '/');
		return true;
	}

}
?>