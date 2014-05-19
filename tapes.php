<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
require_once 'classes/core.php';

//mainwall
$pagina = $_GET['pages'];


if (isset($pagina)){
if ($pagina == "main"){
	$start = $_GET['start'];
	$show = $_GET['show'];
	$important = $_GET['important']; 
	
	echo tape::getMainWallTapes($start, $show , $important);
} else if ($pagina == "profile"){
	$start = $_GET['start'];
	$show = $_GET['show'];
	$id = $_GET['id']; 
	echo tape::getProfileTapes($start = 0, $id, $show = "current");
} else if ($pagina == "group"){
    $start = $_GET['start'];
	$show = $_GET['show'];
	$id = $_GET['id']; 
	echo tape::getRoomTapes($start = 0, $id, $show = "current");
} 
} else die('error');





?>