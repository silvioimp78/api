<?php
/*
 * Revisionato da MetalMario
 * Aggiunti controlli antiinjection 
 * 
 * Gestisce la login a facebook
 * Parametri via POST 
 * @email La mail dell'utente
 * @id L'id Facebook dell'utente
 * 
 */
include_once "../scripts/db.php";
require_once '../scripts/crypt.php';

$conn = connetti();
if (!isset($_POST['email']))
	die("Mail error");
if (!isset($_POST['id']))
	die("Id error");
$pattern = "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})^";
//pattern mail
if (!preg_match($pattern, $_POST['email'])) {
	die('Wrong mail format');
}
if(!is_numeric($_POST['id'])) {
	die('Wrong id format');
}
$utente['email'] = $_POST['email'];
$utente['id'] = $_POST['id'];
$query_res = mysql_query("SELECT * FROM users WHERE email = '" . $utente['email'] . "'", $conn);
$result = mysql_fetch_array($query_res);
if (empty($result)) {
	/*
	 * Ho cercato l'utente tramite la mail e non l'ho trovato. 
	 * Verifico se c'è qualche utente con il FB_ID associato
	 * Questo caso si verifica se per caso l'utente avesse cambiato la mail su SniffRoom 
	 * e vuole tenerla distinta da quella di Facebook.
	*/
	$query_id = "SELECT * FROM users WHERE fb_id = '" . $utente['id'] . "'";
	$query_id_res = mysql_query($query_id, $conn) or die('fb_id query error');
	$dati_login = mysql_fetch_assoc($query_id_res);
	$esiste = mysql_num_rows($query_id_res);
	if ($esiste == 1) {
		/*
		 * Ho trovato un account con l'user ID Facebook richiesto. 
		 * Effettuo diverse verifiche.
		*/
		date_default_timezone_set("Europe/Rome");
		if ($dati_login['active'] == 0) {
			/*
			 * L'account trovato ha una mail che non è stata attivata su SniffRoom 
			 * ed è diversa da quella di Facebook. 
			 * Non possiamo fare i buoni poichè l'utente potrebbe aver messo una mail farlocca
			 * e usato l'autenticazione di Facebook nella speranza di mettercelo a quel posto!
			*/
			$message['error'] = "2";
			echo json_encode($message);
			die('');
		}
		date_default_timezone_set("Europe/Rome");
		$dati['data_last'] = date('d/m/y', time());
		$dati['ora_last'] = date('H:i:s', time());
		/*
		 * 	Il logger stampa la mail di SniffRoom con la quale l'utente si collega
		 *  e la mail associata alla login Facebook. 
		*/
		$query = "UPDATE `users` SET `last_ip`= '" . $_SERVER['REMOTE_ADDR'] . "', `cur_channel_id`= '" . cripta_safer(uniqid()) . "', `last_date` = '" . $dati['data_last'] . "', `last_time` = '" . $dati['ora_last'] . "', `user_agent` = '" . $_SERVER['HTTP_USER_AGENT'] . "' WHERE `email`  = '" . $dati_login['email'] . "'";
		mysql_query($query, $conn) or die('Error.');
		
		include "../scripts/KLogger.php";
		$log = new KLogger("../logs/logins/" . date('d-m-y', time()) . ".txt", KLogger::INFO);
		$log -> LogInfo("Facebook Mobile API " . $dati_login['email'] . "collegato con Facebook mail : " . $utente['email'] ." e IP : " . $_SERVER['REMOTE_ADDR'] . " e User Agent : " . $_SERVER['HTTP_USER_AGENT'] . "");
		returnUserInfo($dati_login['id_users'], $dati_login['password'], $dati_login['name'] . " " .$dati_login['surname'], sha1($dati_login['id_users']),$dati_login['email']);
		die('');
	} 
	else {
		/*
		 *	Non ho trovato alcuna corrispondenza. 
		 *  Creiamo un bell'accountone nuovo!
		*/
		//Inizializzazione parametri per la registrazione
		$creation['nome'] = $utente["first_name"];
		$creation['cognome'] = $utente['last_name'];
		$creation['password_reg'] = sha1($utente['id'] + "21f3vVazZs3");
		$creation['fb_id'] = $utente['id'];
		$creation['email_reg'] = $utente['email'];
		if ($utente["gender"] == "male")
			$creation['sesso'] = 1;
		else
			$creation['sesso'] = 2;
		$dob = $utente["birthday"];
		$data = explode('/', $dob);
		$data_nascita = $data[1] . '/' . $data[0] . '/' . $data[2];
		$creation['nascita'] = $data_nascita;
		//Delego tutto ad una bella funzione per non scamazzare la leggibilità
		createNewUser($creation,$conn);
		die('');
	}
} else {
	/*
	 * Questo è il caso in cui ho trovato la mail nel database 
	*/
	if ($result['active'] == 0) {
		/*
		 * Se la mail è validata da Facebook, siccome siamo bravi waglioni 
		 * assumiamo che sia valida anche su SniffRoom. 
		*/
		$query = "UPDATE `users` SET `active` = 1 WHERE `email`  = '" . $utente['email'] . "'";
		mysql_query($query, $conn) or die('Errore di attivazione dell\' account');
	}
	date_default_timezone_set("Europe/Rome");
	$dati['data_last'] = date('d/m/y', time());
	$dati['ora_last'] = date('H:i:s', time());
	if ($result['fb_id'] == 'no') {
		/*
		 * In questo caso sto collegando il Fb_Id all'utente che già esisteva su SniffRoom. 
		 * L'utente non aveva la corrispondenza con il suo Facebook ID.
		 * La password resta la predefinita dell'utente.
		*/
		$query = "UPDATE `users` SET `fb_id`= '" . $utente['id'] . "', `last_ip`= '" . $_SERVER['REMOTE_ADDR'] . "', `cur_channel_id`= '" . cripta_safer(uniqid()) . "', `last_date` = '" . $dati['data_last'] . "', `last_time` = '" . $dati['ora_last'] . "', `user_agent` = '" . $_SERVER['HTTP_USER_AGENT'] . "' WHERE `email`  = '" . $utente['email'] . "'";

		mysql_query($query, $conn) or die('Error.');
		
		include "../scripts/KLogger.php";
		$log = new KLogger("../logs/logins/" . date('d-m-y', time()) . ".txt", KLogger::INFO);
		$log -> LogInfo("Facebook Mobile API" . $utente['email'] . " collegato con IP : " . $_SERVER['REMOTE_ADDR'] . " e User Agent : " . $_SERVER['HTTP_USER_AGENT'] . " Settato il FB Id.");
		returnUserInfo($result['id_users'], $result['password'], $result['name'] . " " .$result['surname'], sha1($result['id_users']),$result['email']);
		die('');
	} 
	else if ($result['fb_id'] == $utente['id']) {
		/* 
		 * In questo caso l'utente era già associato al suo ID Facebook.
		 * Procedo regolarmente con l'autenticazione.
		*/
		$query = "UPDATE `users` SET `last_ip`= '" . $_SERVER['REMOTE_ADDR'] . "', `cur_channel_id`= '" . cripta_safer(uniqid()) . "', `last_date` = '" . $dati['data_last'] . "', `last_time` = '" . $dati['ora_last'] . "', `user_agent` = '" . $_SERVER['HTTP_USER_AGENT'] . "' WHERE `email`  = '" . $utente['email'] . "'";
		mysql_query($query, $conn) or die('Error.');
		
		include "../scripts/KLogger.php";
		$log = new KLogger("../logs/logins/" . date('d-m-y', time()) . ".txt", KLogger::INFO);
		$log -> LogInfo("Facebook Mobile API" . $utente['email'] . " collegato con IP : " . $_SERVER['REMOTE_ADDR'] . " e User Agent : " . $_SERVER['HTTP_USER_AGENT'] . "");
		returnUserInfo($result['id_users'], $result['password'], $result['name'] . " " .$result['surname'], sha1($result['id_users']),$result['email']);
		die('');
	} 
	else {
		/*
		 * Impossibile loggarsi perché è già settato un fb id e non è il proprio.
		 * Un caso di violazione che probabilmente non può verificarsi...ma meglio gestirlo.
		 */
		$message['error'] = "1";
		echo json_encode($message);
		die('');
	}
}

function returnUserInfo($id,$pwd,$fullname,$avatar,$email) {
	$message['id'] = $id;
	$message['email'] = $email;
	$message['password'] = $pwd;
	$message['fullname'] = $fullname;
	$message['avatar'] = "avatars/".$avatar.".jpg";
	echo json_encode($message);
}

function createNewUser($parametri,$conn) {
		/*
		 * Importazioni Lingua
		 */
		if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
			$lang = 'it';
		else
			$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		
		$base_dir = $_SERVER['DOCUMENT_ROOT'] . '/';
		if (!file_exists($base_dir . 'languages/' . $lang . '.php'))
			$lang = 'en';
		include_once '../languages/' . $lang . '.php';
		/* Procedo con la creazione */
		$dati = $parametri;
		$activation_key = GetKey();
		mysql_query("START TRANSACTION", $conn);
		$query = "INSERT INTO `users`(`email`, `password`, `last_ip`, `last_date`, `last_time`, `user_agent`, `birth_date`, `sex`, `name`, `surname`, `lang`,`login_key`,`active`,`cur_channel_id`,`fb_id`) VALUES ('" . $dati['email_reg'] . "','" . $dati['password_reg'] . "','" . $_SERVER['REMOTE_ADDR'] . "','" . $dati['data_last'] . "','" . $dati['ora_last'] . "','" . $_SERVER['HTTP_USER_AGENT'] . "','" . $dati['nascita'] . "','" . $dati['sesso'] . "','" . $dati['nome'] . "','" . $dati['cognome'] . "', '$lang' , '$activation_key','1','".cripta_safer(uniqid())."','".$dati['fb_id']."')";;
		if (!mysql_query($query, $conn)) {
			die('Errore generico');
		}
		$id_utente = mysql_insert_id();
		$sql_url = "INSERT INTO `listeners` (`user_id`, `listener_id`) VALUES ('294', '" . $id_utente . "')";
		mysql_query($sql_url) or die("Listening failed.");
		$sql_url = "INSERT INTO `listeners` (`user_id`, `listener_id`) VALUES ('" . $id_utente . "', '294')";
		mysql_query($sql_url) or die("Listening failed.");
		mysql_query("COMMIT", $conn);
		date_default_timezone_set("Europe/Rome");
		//Includo il KLogger
			include "../scripts/KLogger.php";
		//------------//
		$log = new KLogger("../logs/new_regs/" . date('d-m-y') . ".txt", KLogger::INFO);
		$log -> LogInfo("Social " . $dati['email_reg'] . " ID in database : " . $id_utente . " registrato con IP : " . $_SERVER['REMOTE_ADDR'] . " e User Agent : " . $_SERVER['HTTP_USER_AGENT'] . "");
		$bodyregmail['en'] = 'Thank you for registering on SniffRoom.From now your posts become voice!. You successfully registered with your Facebook email.<p>Have fun!</p>
					<p> Greetings from www.sniffroom.com</p></body></html>';
		$bodyregmail['it'] = 'Grazie per esserti registrato a SniffRoom. Da oggi i tuoi post diventano voce.Ti sei registrato con Facebook,potrai entrare effettuando la login con Facebook.<p>Divertiti!</p>
					<p> Saluti da www.sniffroom.com</p></body></html>';
		$message = '<html><body><p>' . $bodyregmail[$lang];
		$to = $dati['email_reg'];
		$subject['it'] = 'Registrazione a SniffRoom';
		$subject['en'] = 'SniffRoom Registration';
		$from = "register@sniffroom.com";
		//Mailer
		require "../scripts/sendmail.php";
		//-----------------//
		sendmail_smtp($from, $to, $subject[$lang], $message, $lang);
		$query_user = "SELECT * FROM users WHERE id_users = ".$id_utente;
		$query_res = mysql_query($query_user,$conn) or die("Errore login dopo registrazione.");
	    $utente = mysql_fetch_assoc($query_res);
		//Loggo su file il login
		$log = new KLogger("../logs/logins/" . date('d-m-y', time()) . ".txt", KLogger::INFO);
		$log -> LogInfo("Facebook " . $utente['email'] . " collegato con IP : " . $_SERVER['REMOTE_ADDR'] . " e User Agent : " . $_SERVER['HTTP_USER_AGENT'] . "");
		//Restituisco informazioni per la login
		returnUserInfo($utente['id_users'], $utente['password'], $utente['name'] . " " .$utente['surname'], sha1($utente['id_users']));
		die('');
}

function GetKey() {
	//Funzione di generazione hash personale.
	$car = "aAbBcCdDeEfFgGhHiIlLjJkKmMnNoOpPqQrRsStTuUvVwWxXyYzZ0123456789";
	$dim = 40;
	srand((double)microtime() * 1000000);
	$string = '';
	for ($inc = 0; $inc < $dim; $inc++) {
		$rand = rand(0, strlen($car) - 1);
		$scar = substr($car, $rand, 1);
		$string = $string . $scar;
	}
	return $string;
}
?>