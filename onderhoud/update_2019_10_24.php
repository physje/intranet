<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$sql = "ALTER TABLE $TableVoorganger ADD $VoorgangerHonorarium INT(5) NOT NULL DEFAULT '9500' AFTER $VoorgangerDeclaratie, ADD $VoorgangerKM INT(3) NOT NULL DEFAULT '35' AFTER $VoorgangerHonorarium, ADD $VoorgangerVertrekpunt TEXT NOT NULL AFTER $VoorgangerKM, ADD $VoorgangerEBRelatie INT(3) NOT NULL AFTER $VoorgangerVertrekpunt;";
mysqli_query($db, $sql);

echo $sql;

?>