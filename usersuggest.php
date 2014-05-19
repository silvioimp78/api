<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
include '../scripts/db.php';
include '../scripts/login.php';
include '../scripts/user_profile.php';


$conn = connetti();
$user = login($conn);
if (!isset($user) || $user == "guest") {
	header('HTTP/1.1 403 Forbidden');
	$message['id'] = '0';
	$message['reason'] = 'Not Logged';
	echo json_encode($message);
	die('');
}

$id = $user['id_users'];
$miei = my_hashtags($conn, $id);
$altri = others_hashtags($conn, $id);
$tot_sugg = 0;
$esclusioni = '';
//cerca i tag
$similar_tag = '';
if (($miei != 'empty') && ($altri != 'empty')) {
	for ($my = 0; $my < count($miei); $my++) {
		for ($oth = 0; $oth < count($altri); $oth++) {
			$mio_tag = $miei[$my]['tag'];
			$altro_tag = $altri[$oth]['tag'];
			$contiene = strpos($mio_tag, $altro_tag);

			if (!($contiene === false)) {
				$similar_tag[$tot_sugg]['tag'] = $altri[$oth]['tag'];
				$similar_tag[$tot_sugg]['id'] = $altri[$oth]['id'];
				$bar = user_reputation($altri[$oth]['reputation']);		
				$similar_tag[$tot_sugg]['percent'] = $bar['percent'];
				$similar_tag[$tot_sugg]['star'] = $bar['star'];
				$similar_tag[$tot_sugg]['fullname'] = $altri[$oth]['fullname'];
				$tot_sugg++;
			}
		}

	}
	//order array by reputation
	function cmp($a, $b) {
		if ($a['reputation'] == $b['reputation']) {
			return 0;
		}
		return ($a['reputation'] > $b['reputation']) ? -1 : 1;
	}

	usort($similar_tag, "cmp");
}

$ct_follow = 0;
$followed = Array();
$bestfollowed = Array();
$query_part_exclude = 'l.listener_id != ' . $id . ' AND l.listener_id != 294 AND ';
if (is_array($similar_tag)){
for ($a = 0; $a < count($similar_tag); $a++)
	$query_part_exclude = $query_part_exclude . 'l.listener_id != ' . $similar_tag[$a]['id'] . ' AND ';
}
$query_part_include = '(';
// end of ordering
if ($tot_sugg < 50) {
	//find other user by reputation
	//fird first three followed people
	$query = "SELECT * FROM listeners AS l LEFT JOIN users AS u ON l.listener_id = u.id_users WHERE l.user_id = " . $id . " AND l.listener_id !=294 " . $esclusioni . " ORDER BY u.reputation DESC";
	$res = mysql_query($query);
	while ($singolo = mysql_fetch_assoc($res)) {
		if ($ct_follow < 3) {
			array_push($bestfollowed, $singolo);
			if ($ct_follow < 2)
				$query_part_include = $query_part_include . ' l.user_id = ' . $singolo['listener_id'] . ' OR ';
			else
				$query_part_include = $query_part_include . ' l.user_id = ' . $singolo['listener_id'] . ') AND (';
		}
		$query_part_exclude = $query_part_exclude . ' l.listener_id != ' . $singolo['listener_id'] . ' AND ';
		$ct_follow++;
	}
}

$query = "SELECT DISTINCT listener_id, reputation, name, surname FROM listeners as l LEFT JOIN users as u ON listener_id = u.id_users WHERE " . $query_part_include . $query_part_exclude . ' l.blocked = 0 AND l.priori_block = 0) ORDER BY u.reputation DESC LIMIT 0,' . (50 - $tot_sugg);

$ris_suggerimenti = mysql_query($query);
while ($singolo = mysql_fetch_assoc($ris_suggerimenti)) {
	$similar_tag[$tot_sugg]['tag'] = '%';
	$similar_tag[$tot_sugg]['id'] = $singolo['listener_id'];
	$bar = user_reputation($singolo['reputation']);		
	$similar_tag[$tot_sugg]['percent'] = $bar['percent'];
	$similar_tag[$tot_sugg]['star'] = $bar['star'];
	$similar_tag[$tot_sugg]['fullname'] = $singolo['name'] . ' ' . $singolo['surname'];
	$tot_sugg++;

}

echo json_encode($similar_tag);

function my_hashtags($conn, $id) {
	$query = "SELECT title FROM posts WHERE id_autore = " . $id . " AND (title LIKE '#%' OR title LIKE '% #%') ";
	$res = mysql_query($query);

	$ct_arg = 0;
	if ($res != null) {
		while ($singolo = mysql_fetch_assoc($res)) {
			preg_match_all("/(#\w+)/", $singolo['title'], $matches);
			if ($matches) {
				$hashtagsArray = array_count_values($matches[0]);
				$hashtags = array_keys($hashtagsArray);
			}
			foreach ($hashtags as $singlo[0] => $temp) {

				$argomento[$ct_arg]['tag'] = substr($temp, 1);
				$argomento[$ct_arg]['id'] = 33;

				$ct_arg++;
			}

		}
	} else
		return 'empty';
	return $argomento;
}

function others_hashtags($conn, $id) {
	$query = "SELECT title,id_autore, name,surname FROM posts AS p JOIN users AS u ON p.id_autore = u.id_users WHERE p.id_autore != " . $id . " AND (p.title LIKE '#%' OR p.title LIKE '% #%') ";
	$res = mysql_query($query);

	$ct_arg = 0;
	if ($res != null) {
		While ($singolo = mysql_fetch_assoc($res)) {
			preg_match_all("/(#\w+)/", $singolo['title'], $matches);
			if ($matches) {
				$hashtagsArray = array_count_values($matches[0]);
				$hashtags = array_keys($hashtagsArray);
			}
			foreach ($hashtags as $singlo[0] => $temp) {
				//vedi se segue

				$segui_gia = is_listener($id, $singolo['id_autore'], $conn);
				if ($segui_gia == 'false') {
					$utente = get_profile($singolo['id_autore'], $conn);
					$reputazione = $utente['reputation'];
					$argomento[$ct_arg]['tag'] = substr($temp, 1);
					$argomento[$ct_arg]['id'] = $singolo['id_autore'];
					$argomento[$ct_arg]['reputation'] = $reputazione;
					$argomento[$ct_arg]['fullname'] = $singolo['name'] . ' ' . $singolo['surname'];

					$ct_arg++;
				}

			}

		}
	}

	if ($ct_arg == 0)
		return 'empty';
	else
		return $argomento;
}
?>