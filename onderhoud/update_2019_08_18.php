<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$sql = "ALTER TABLE $TableRoosters ADD $RoostersAlert SET('0','1','2','3','4') NOT NULL DEFAULT '2' AFTER $RoostersTextOnly;";
mysqli_query($db, $sql);

?>