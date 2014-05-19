<?php
 set_time_limit(0);
 include 'classes/db.php';
 $db = new db();
 $db -> doQuery("SELECT * FROM posts WHERE parent_post_id = 0");
 $main_tapes = $db -> getResult();
 for($ct = 0; $ct < count($main_tapes);$ct++){
	
 	$db -> doQuery("SELECT * FROM posts WHERE parent_post_id = ".$main_tapes[$ct]['idposts']);
	$num_reply = $db -> getNumRows();
	echo "UPDATE `posts` SET `reply_num` = ".$num_reply." WHERE idposts = ".$main_tapes[$ct]['idposts'];
	$db -> doQuery("UPDATE `posts` SET `reply_num` = ".$num_reply." WHERE idposts = ".$main_tapes[$ct]['idposts']); 
 }
?>