<?php
 header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
 include '../scripts/db.php';
 $conn = connetti();
 $ct = 0;
 $query = "SELECT * FROM users ";
 $res = mysql_query($query);
 $tot_utenti = mysql_num_rows($res);
 $query = "SELECT * FROM countries WHERE users != 0 ORDER BY users DESC";
 
 $res = mysql_query($query,$conn);
 while ($singolo = mysql_fetch_assoc($res)){
 	 $json[$ct]['code'] = $singolo['code'];
	 $json[$ct]['name'] = $singolo['name_en'];
	 $json[$ct]['users'] = $singolo['users'];
	 $json[$ct]['percent'] = (100*$json[$ct]['users'])/$tot_utenti;
	 $ct++;
	
 }
echo json_encode($json); 
 
?>