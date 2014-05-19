<?php
class notify {

	public static function readNotify($start = 0) {
		
		global $db;
		$user = session::getSession();
		if (!isset($user) || $user == "guest")
			throw new Exception("ERR:NOT_LOGGED");

		if (!isset($start))
			throw new Exception("ERR:NOT_LOGGED");
		if (!numeric($start))
			throw new Exception("ERR:NOT_LOGGED");

		$notify = array();
		if ($start == '0') {
			$query = "SELECT n.type, n.from_id, n.dest_id, n.wall_id, n.room_id, n.post_id, n.date, n.read, w.name,w.surname, p.title as post_title, r.title as page_title FROM notify as n LEFT JOIN users as w ON w.id_users = n.from_id LEFT JOIN rooms as r ON n.room_id = r.room_id LEFT JOIN posts as p ON p.idposts = n.post_id WHERE n.dest_id = " . $user['id_users'] . " ORDER BY date DESC";
		
			$db -> doQuery($query);
			$notify = $db -> getResult();
		} else {
			$query = 'SELECT n.type, n.from_id, n.dest_id, n.wall_id, n.room_id, n.post_id, n.date, n.read, w.name,w.surname, p.title as post_title, r.title as page_title FROM notify as n LEFT JOIN users as w ON w.id_users = n.from_id LEFT JOIN rooms as r ON n.room_id = r.room_id LEFT JOIN posts as p ON p.idposts = n.post_id FROM notify WHERE n.dest_id = ' . $user['id_users'] . ' AND date < ' . $_GET['start'] . " ORDER BY date DESC LIMIT 0, 10";
			$db -> doQuery($query);
			$notify = $db -> getResult();
		}
		$limit = 10;
        if (count($notify) <  10) $limit = count($notify);
		   $total_unread = 0;
			for ($ct = 0; $ct < count($notify);$ct++) {
				if ($notify[$ct]['read'] == '0')
					$total_unread++;
			}
		$total_notify = count($notify);	
         
		for ($ct = 0; $ct < $limit;$ct++) {
			$wall_name = '';
			$exclude = false;
			if ($notify[$ct]['wall_id'] != 0) {

				if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/avatars/thumb_' . sha1($notify[$ct]['from_id']) . '.jpg'))
					$avatar = 'avatars/thumb_' . sha1($notify[$ct]['from_id']) . '.jpg?rand=' . rand(1, 1000000);
				else
					$avatar = 'avatars/noavatar_min.png';

				$notify[$ct]['from_avatar'] = '/' . $avatar;

			}
		}
     $header = array();
	 $header['total_notify'] = $total_notify;
	 $header['unread'] = $total_unread;
	 $header['notify'] = $notify;
     
    return json_encode($header);
	}

}
?>