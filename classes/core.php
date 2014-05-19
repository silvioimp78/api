<?php 
$base = $_SERVER['DOCUMENT_ROOT'].'/api/classes/';
require $base.'db.php'; 
require $base.'users.php';
require $base.'rooms.php';
require $base.'session.php';
require $base.'tape.php';
require $base."KLogger.php";
require $base.'private.php';
require $base.'bottle.php';
require $base.'notify.php';
global $db;
$db = new db();


function cripta($input)
{
	$key = '3717ca5321fef065655a9db67ee28d6f28d6fb93d72c2';
	$td = mcrypt_module_open(MCRYPT_BLOWFISH, '', 'ecb', '');
	$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	mcrypt_generic_init($td, $key, $iv);
	$encrypted_data = bin2hex(mcrypt_generic($td, $input));
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	return $encrypted_data;
}
function decripta($encrypted_data)
{
	$key = '3717ca5321fef065655a9db67ee28d6f28d6fb93d72c2';
	$td = mcrypt_module_open(MCRYPT_BLOWFISH, '', 'ecb', '');
	$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	mcrypt_generic_init($td, $key, $iv);
	$temp =pack("H*" , $encrypted_data);
	$dati_decriptati = mdecrypt_generic($td, $temp);
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	return $dati_decriptati;
}
function cripta_safer($input)
{
	$key = '3717ca5321fef065655b93d72c2';
	$encrypted_data = mcrypt_ecb(MCRYPT_RIJNDAEL_128, $key, $input, MCRYPT_ENCRYPT);
	return bin2hex($encrypted_data);
}

function numeric($parameter){
	if (isset($parameter)){
		if (is_numeric($parameter)) return true; else return false;
	} return false;

}

?>

