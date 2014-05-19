<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
//header('Content-type: application/json');
require_once ('core.php');
/*try {
 $utente = new users(33);
 } catch (exception $e){
 echo 'Profilo inesistente:'.$e -> getMessage();
 die('');
 }*/
//$logged = session::getSession();
if (isset($_GET['act'])) {
	if ($_GET['act'] == "login") {
		echo session::doLogin("metalmario90@gmail.com", "castoro");
	} else {
		$result = session::doLogout();
		if ($result == true)
			header("Location:stub.php");
	}
}
//bottle::send();
//bottle::newBottle();
//bottle::newBottle();
echo bottle::getPath(65);
//bottle::getAllNames(4510);
//bottle::makeList(0,4510);
//echo notify::readNotify();
//bottle::resend(4512);
//session::getSession();
//$utente = new users(33);
//print_r($utente ->getFollowed());
//echo tape::getMainWallTapes(0, "current",0);
//privateTapes::getMessages();
// tape::getProfileTapes(0,36 ,"current") 
//echo tape::getRoomTapes($start = 0, 10, $show = "current")
//tape::getProfileTapes($start = 0, $id = 0, $show = "current");
//session::confirmRequest(202);
//print_r($utente -> getFollowed());
//$utente -> Follow();
//print_r(session::getRequests());
//$utente_log = session::getSession();
//print_r($_SESSION);
//echo 'Sessione istanziata - Dio cane, ma in sessione non abbiamo mai salvato l\'ID utente?';
//print_r($utente_array-> getFollowedRooms());
/*$dati = $utente -> getUserData();
 $seguiti = $utente -> getFollowed();
 $seguaci = $utente -> getFollowing();
 print_r($dati);
 print_r($seguiti);
 print_r($seguaci);

 //print_r($utente -> getUserData());
 //print_r($utente -> getFollowed());

 //$stanza = new room(10);
 //print_r($stanza -> getRoomData());

 //print_r($stanza -> getRoomUsers());

 //print_r($stanza -> getRoomAdmin());

 /*$data = $database -> doQuery("SELECT * FROM users LIMIT 0,20");
 echo $database -> getNumRows();
 print_r($data);*/
?>
