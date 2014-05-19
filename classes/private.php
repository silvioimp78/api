<?php
class privateTapes {
    static private $utente;
	static public function getMessages($start = 0, $show = "current", $id = 0,$act="all") {
		global $db;
		$post_ris = '';
		$query = '';
		self::$utente = session::getSession();
		if (!isset(self::$utente) || self::$utente == "guest")
			throw new Exception("ERR:NOT_LOGGED");
		if (($show == "current") || ($show == "latest") || ($show == "oldest")) {
			if (($show == 'current') && ($start == 0)) {
				if ($act == "all") {
	if ($id == 0) {
		$query = 'SELECT * FROM private_messages WHERE ((id_dest1 = ' . self::$utente['id_users'] . ' OR id_dest2 = ' . self::$utente['id_users'] . ' OR id_dest3 = ' . self::$utente['id_users'] . ' OR id_dest4 = ' . self::$utente['id_users'] . ' OR id_dest5 = ' . self::$utente['id_users'] . ') OR (id_autore = ' . self::$utente['id_users'] . ' AND auth_deleted = 0)) ORDER BY idposts DESC LIMIT 0,10';
	} else {
		$auth_part = '';
		$dest_part = '';
		for ($ct = 0; $ct < count($user_list); $ct++) {
			if ($ct != count($user_list) - 1) {
				$auth_part = $auth_part . ' id_autore = ' . $user_list[$ct] . ' OR ';
				$dest_part = $dest_part . ' id_dest1 = ' . $user_list[$ct] . ' OR id_dest2 = ' . $user_list[$ct] . ' OR id_dest3 = ' . $user_list[$ct] . ' OR id_dest4 = ' . $user_list[$ct] . ' OR id_dest5 = ' . $user_list[$ct] . ' OR ';
			} else {
				$auth_part = $auth_part . ' id_autore = ' . $user_list[$ct];
				$dest_part = $dest_part . ' id_dest1 = ' . $user_list[$ct] . ' OR id_dest2 = ' . $user_list[$ct] . ' OR id_dest3 = ' . $user_list[$ct] . ' OR id_dest4 = ' . $user_list[$ct] . ' OR id_dest5 = ' . $user_list[$ct];
			}
		}
		$query = 'SELECT * FROM private_messages WHERE (' . $auth_part . ' OR id_autore = ' . self::$utente['id_users'] . ' AND auth_deleted = 0) AND  (' . $dest_part . ' OR id_dest1 = ' . self::$utente['id_users'] . ' OR id_dest2 = ' . self::$utente['id_users'] . ' OR id_dest3 = ' . self::$utente['id_users'] . ' OR id_dest4 = ' . self::$utente['id_users'] . ' OR id_dest5 = ' . self::$utente['id_users'] . ') ORDER BY idposts DESC LIMIT 0,10';
	}
} else if ($act == "rec") {
	if ($id == 0) {
		$query = 'SELECT * FROM private_messages WHERE (id_dest1 = ' . self::$utente['id_users'] . ' OR id_dest2 = ' . self::$utente['id_users'] . ' OR id_dest3 = ' . self::$utente['id_users'] . ' OR id_dest4 = ' . self::$utente['id_users'] . ' OR id_dest5 = ' . self::$utente['id_users'] . ')  ORDER BY idposts DESC LIMIT 0,10';
	} else {
		$auth_part = '';
		for ($ct = 0; $ct < count($user_list); $ct++) {
			if ($ct != count($user_list) - 1) {
				$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct] . ' OR ';
			} else {
				$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct];
			}
		}
		$query = 'SELECT * FROM private_messages WHERE (id_dest1 = ' . self::$utente['id_users'] . ' OR id_dest2 = ' . self::$utente['id_users'] . ' OR id_dest3 = ' . self::$utente['id_users'] . ' OR id_dest4 = ' . self::$utente['id_users'] . ' OR id_dest5 = ' . self::$utente['id_users'] . ') AND (' . $auth_part . ')  ORDER BY idposts DESC LIMIT 0,10';
	}
} else if ($act == "sent") {
	if ($id == 0) {
		$query = 'SELECT * FROM private_messages WHERE (id_autore = ' . self::$utente['id_users'] . ')  ORDER BY idposts DESC LIMIT 0,10';
	} else {
		$auth_part = '';
		$dest_part = '';
		for ($ct = 0; $ct < count($user_list); $ct++) {
			if ($ct != count($user_list) - 1) {
				$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct] . ' OR ';
				$dest_part = $dest_part . '( id_dest1 = ' . $user_list[$ct] . ' OR id_dest2 = ' . $user_list[$ct] . ' OR id_dest3 = ' . $user_list[$ct] . ' OR id_dest4 = ' . $user_list[$ct] . ' OR id_dest5 = ' . $user_list[$ct] . ') OR ';
			} else {
				$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct];
				$dest_part = $dest_part . '( id_dest1 = ' . $user_list[$ct] . ' OR id_dest2 = ' . $user_list[$ct] . ' OR id_dest3 = ' . $user_list[$ct] . ' OR id_dest4 = ' . $user_list[$ct] . ' OR id_dest5 = ' . $user_list[$ct] . ')';
			}
		}
		$query = 'SELECT * FROM private_messages WHERE (id_autore = ' . self::$utente['id_users'] . ' AND auth_deleted = 0 AND (' . $dest_part . '))  ORDER BY idposts DESC LIMIT 0,10';

	}
} 



			} else if ($start != 0) {
				if ($show == "latest") {
					//
					$user_list = array();
					if (isset($_GET['id'])) {
						$id = $_GET['id'];
						
							$user_list = explode('-', $id);
							for ($ct = 0; $ct < count($user_list); $ct++) {
								if (!is_numeric($user_list[$ct]))
									die('Parameter error');
							}
						} else
							$user_list[0] = $_GET['id'];
					

					if ($act == "all") {
						if ($id == 0) {
							$query = 'SELECT * FROM private_messages WHERE ((id_dest1 = ' . self::$utente['id_users'] . ' OR id_dest2 = ' . self::$utente['id_users'] . ' OR id_dest3 = ' . self::$utente['id_users'] . ' OR id_dest4 = ' . self::$utente['id_users'] . ' OR id_dest5 = ' . self::$utente['id_users'] . ') OR (id_autore = ' . self::$utente['id_users'] . ' AND auth_deleted = 0)) AND idposts > ' . $start . ' ORDER BY idposts DESC ';
						} else {
							$auth_part = '';
							$dest_part = '';
							for ($ct = 0; $ct < count($user_list); $ct++) {
								if ($ct != count($user_list) - 1) {
									$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct] . ' OR ';
									$dest_part = $dest_part . ' id_dest1 = ' . $user_list[$ct] . ' OR id_dest2 = ' . $user_list[$ct] . ' OR id_dest3 = ' . $user_list[$ct] . ' OR id_dest4 = ' . $user_list[$ct] . ' OR id_dest5 = ' . $user_list[$ct] . ' OR ';
								} else {
									$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct];
									$dest_part = $dest_part . 'id_dest1 = ' . $user_list[$ct] . ' OR id_dest2 = ' . $user_list[$ct] . ' OR id_dest3 = ' . $user_list[$ct] . ' OR id_dest4 = ' . $user_list[$ct] . ' OR id_dest5 = ' . $user_list[$ct];
								}
							}
							$query = 'SELECT * FROM private_messages WHERE ((' . $auth_part . ' OR id_autore = ' . $user['id_users'] . ' AND auth_deleted = 0) AND (' . $dest_part . ' OR id_dest1 = ' . self::$utente['id_users'] . ' OR id_dest2 = ' . self::$utente['id_users'] . ' OR id_dest3 = ' . self::$utente['id_users'] . ' OR id_dest4 = ' . self::$utente['id_users'] . ' OR id_dest5 = ' . self::$utente['id_users'] . '))  AND idposts > ' . $start . ' ORDER BY idposts DESC ';

						}
					} else if ($act == "rec") {
						if ($id == 0) {
							$query = 'SELECT * FROM private_messages WHERE (id_dest1 = ' . self::$utente['id_users'] . ' OR id_dest2 = ' . self::$utente['id_users'] . ' OR id_dest3 = ' . self::$utente['id_users'] . ' OR id_dest4 = ' . self::$utente['id_users'] . ' OR id_dest5 = ' . self::$utente['id_users'] . ')  AND idposts > ' . $start . ' ORDER BY idposts DESC ';
						} else {
							$auth_part = '';
							for ($ct = 0; $ct < count($user_list); $ct++) {
								if ($ct != count($user_list) - 1) {
									$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct] . ' OR ';
								} else {
									$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct];
								}
							}
							$query = 'SELECT * FROM private_messages WHERE (id_dest1 = ' . self::$utente['id_users'] . ' OR id_dest2 = ' . self::$utente['id_users'] . ' OR id_dest3 = ' . self::$utente['id_users'] . ' OR id_dest4 = ' . self::$utente['id_users'] . ' OR id_dest5 = ' . self::$utente['id_users'] . ') AND (' . $auth_part . ')  AND idposts > ' . $start . ' ORDER BY idposts DESC ';
						}
					} else if ($act == "sent") {
						if ($id == 0) {
							$query = 'SELECT * FROM private_messages WHERE (id_autore = ' . self::$utente['id_users'] . ')  AND idposts > ' . $start . ' ORDER BY idposts DESC ';
						} else {
							$auth_part = '';
							$dest_part = '';
							for ($ct = 0; $ct < count($user_list); $ct++) {
								if ($ct != count($user_list) - 1) {
									$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct] . ' OR ';
									$dest_part = $dest_part . '( id_dest1 = ' . $user_list[$ct] . ' OR id_dest2 = ' . $user_list[$ct] . ' OR id_dest3 = ' . $user_list[$ct] . ' OR id_dest4 = ' . $user_list[$ct] . ' OR id_dest5 = ' . $user_list[$ct] . ') OR ';
								} else {
									$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct];
									$dest_part = $dest_part . '( id_dest1 = ' . $user_list[$ct] . ' OR id_dest2 = ' . $user_list[$ct] . ' OR id_dest3 = ' . $user_list[$ct] . ' OR id_dest4 = ' . $user_list[$ct] . ' OR id_dest5 = ' . $user_list[$ct] . ')';
								}
							}
							$query = 'SELECT * FROM private_messages WHERE (id_autore = ' . self::$utente['id_users'] . ' AND auth_deleted = 0 AND (' . $dest_part . '))  AND idposts > ' . $start . ' ORDER BY idposts DESC ';

						}
					} else
						die("Invalid act.");
					
					$post_ris = $db -> getResult();
				
				
					$num_rows = $db -> getNumRows();
				

					//
				} else if ($show == "oldest")
				
				if ($act == "all") {
	if ($id == 0) {
		$query = 'SELECT * FROM private_messages WHERE ((id_dest1 = ' . $user['id_users'] . ' OR id_dest2 = ' . $user['id_users'] . ' OR id_dest3 = ' . $user['id_users'] . ' OR id_dest4 = ' . $user['id_users'] . ' OR id_dest5 = ' . $user['id_users'] . ') OR (id_autore = ' . $user['id_users'] . ' AND auth_deleted = 0)) AND idposts < ' . $start . ' ORDER BY idposts DESC LIMIT 0,10';
	} else {
		$auth_part = '';
		$dest_part = '';
		for ($ct = 0; $ct < count($user_list); $ct++) {
			if ($ct != count($user_list) - 1) {
				$auth_part = $auth_part . ' id_autore = ' . $user_list[$ct] . ' OR ';
				$dest_part = $dest_part . ' id_dest1 = ' . $user_list[$ct] . ' OR id_dest2 = ' . $user_list[$ct] . ' OR id_dest3 = ' . $user_list[$ct] . ' OR id_dest4 = ' . $user_list[$ct] . ' OR id_dest5 = ' . $user_list[$ct] . ' OR ';
			} else {
				$auth_part = $auth_part . ' id_autore = ' . $user_list[$ct];
				$dest_part = $dest_part . ' id_dest1 = ' . $user_list[$ct] . ' OR id_dest2 = ' . $user_list[$ct] . ' OR id_dest3 = ' . $user_list[$ct] . ' OR id_dest4 = ' . $user_list[$ct] . ' OR id_dest5 = ' . $user_list[$ct];
			}
		}
		$query = 'SELECT * FROM private_messages WHERE (' . $auth_part . ' OR id_autore = ' . $user['id_users'] . ' AND auth_deleted = 0) AND  (' . $dest_part . ' OR id_dest1 = ' . $user['id_users'] . ' OR id_dest2 = ' . $user['id_users'] . ' OR id_dest3 = ' . $user['id_users'] . ' OR id_dest4 = ' . $user['id_users'] . ' OR id_dest5 = ' . $user['id_users'] . ') AND idposts < ' . $start . ' ORDER BY idposts DESC LIMIT 0,10';
	}
} else if ($act == "rec") {
	if ($id == 0) {
		$query = 'SELECT * FROM private_messages WHERE (id_dest1 = ' . $user['id_users'] . ' OR id_dest2 = ' . $user['id_users'] . ' OR id_dest3 = ' . $user['id_users'] . ' OR id_dest4 = ' . $user['id_users'] . ' OR id_dest5 = ' . $user['id_users'] . ')  AND idposts< ' . $start . ' ORDER BY idposts DESC LIMIT 0,10';
	} else {
		$auth_part = '';
		for ($ct = 0; $ct < count($user_list); $ct++) {
			if ($ct != count($user_list) - 1) {
				$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct] . ' OR ';
			} else {
				$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct];
			}
		}
		$query = 'SELECT * FROM private_messages WHERE (id_dest1 = ' . $user['id_users'] . ' OR id_dest2 = ' . $user['id_users'] . ' OR id_dest3 = ' . $user['id_users'] . ' OR id_dest4 = ' . $user['id_users'] . ' OR id_dest5 = ' . $user['id_users'] . ') AND (' . $auth_part . ')  AND idposts< ' . $start . ' ORDER BY idposts DESC LIMIT 0,10';
	}
} else if ($act == "sent") {
	if ($id == 0) {
		$query = 'SELECT * FROM private_messages WHERE (id_autore = ' . $user['id_users'] . ')  AND idposts< ' . $start . ' ORDER BY idposts DESC LIMIT 0,10';
	} else {
		$auth_part = '';
		$dest_part = '';
		for ($ct = 0; $ct < count($user_list); $ct++) {
			if ($ct != count($user_list) - 1) {
				$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct] . ' OR ';
				$dest_part = $dest_part . '( id_dest1 = ' . $user_list[$ct] . ' OR id_dest2 = ' . $user_list[$ct] . ' OR id_dest3 = ' . $user_list[$ct] . ' OR id_dest4 = ' . $user_list[$ct] . ' OR id_dest5 = ' . $user_list[$ct] . ') OR ';
			} else {
				$auth_part = $auth_part . 'id_autore = ' . $user_list[$ct];
				$dest_part = $dest_part . '( id_dest1 = ' . $user_list[$ct] . ' OR id_dest2 = ' . $user_list[$ct] . ' OR id_dest3 = ' . $user_list[$ct] . ' OR id_dest4 = ' . $user_list[$ct] . ' OR id_dest5 = ' . $user_list[$ct] . ')';
			}
		}
		$query = 'SELECT * FROM private_messages WHERE (id_autore = ' . $user['id_users'] . ' AND auth_deleted = 0 AND (' . $dest_part . '))  AND idposts < ' . $start . ' ORDER BY idposts DESC LIMIT 0,10';

	}
} 
			} else
				throw new Exception("ERR:WRONG PARAMETERS");
		} else
			throw new Exception("ERR:WRONG PARAMETERS");
		echo $query;
		
		$db -> doQuery($query);
		$post_ris = $db -> getResult();
        $num_rows = $db -> getNumRows();
		echo 'Stampa post';
		print_r($post_ris);

	}

}
?>