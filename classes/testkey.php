<?php
if (!isset($_GET['act'])){
	 echo 'Usare act=set oppure act=verify...';
die('');
}
$act = $_GET['act'];

if ($act == 'set'){


$trusted_string = "TaPebOxtRuSted451245";
$timestamp = time();

$id = 33;
$ip = $_SERVER['REMOTE_ADDR'];
$browser_array = getBrowser();
$browser_name = $browser_array['name'];
$user_os = $browser_array['platform'];
$string= $id.'@'.($timestamp*rand(1,1000)).'@'.$browser_name.'@'.$ip.'@'.$trusted_string.'@'.$user_os;
$key = '34kdfsjklòjuiòlafj78994554892530837';

$encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));


echo'<p>Cookie String:'.$encrypted.'</p>';
setcookie("l",$encrypted,time()+ 1296000,"/");
echo 'Cookie Settato';
} else  if ($act=='verify'){
	
	if (!isset($_COOKIE['l'])) {
		echo 'Cookie non presente';
		die('');
	}
	$encrypted = $_COOKIE['l'];
	
	$key = '34kdfsjklòjuiòlafj78994554892530837';
	$decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($encrypted), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
	$array_verify = explode("@", $decrypted);
	$count = count($array_verify);
	if($count != 6) 
		die( $count . ' Cookie rifiutato'); 
	print_r($array_verify);
	$trusted_string = $array_verify[4];
	
	if ($trusted_string=="TaPebOxtRuSted451245") {
		echo '<p>Stringa trusted verificata';
        $browser_array = getBrowser();
        $browser_name = $browser_array['name'];
        $user_os = $browser_array['platform'];
        $ip = $_SERVER['REMOTE_ADDR'];
		if (!is_numeric($array_verify[0])) die('WRONG ID');
		if (($browser_name==$array_verify[2])&&($ip==$array_verify[3])&&($user_os==$array_verify[5])) 
		               echo 'Cookie accettato'; else echo 'Cookie rifiutato'; 
		
	} else echo 'Cookie rifiutato'; 
	
}

function getBrowser()
{
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version= "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }
   
    // Next get the name of the useragent yes seperately and for good reason
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
    {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    }
    elseif(preg_match('/Firefox/i',$u_agent))
    {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    }
    elseif(preg_match('/Chrome/i',$u_agent))
    {
        $bname = 'Google Chrome';
        $ub = "Chrome";
    }
    elseif(preg_match('/Safari/i',$u_agent))
    {
        $bname = 'Apple Safari';
        $ub = "Safari";
    }
    elseif(preg_match('/Opera/i',$u_agent))
    {
        $bname = 'Opera';
        $ub = "Opera";
    }
    elseif(preg_match('/Netscape/i',$u_agent))
    {
        $bname = 'Netscape';
        $ub = "Netscape";
    }
   
    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }
   
    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= $matches['version'][0];
        }
        else {
            $version= $matches['version'][1];
        }
    }
    else {
        $version= $matches['version'][0];
    }
   
    // check if we have a number
    if ($version==null || $version=="") {$version="?";}
   
    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'    => $pattern
    );
}



?>
