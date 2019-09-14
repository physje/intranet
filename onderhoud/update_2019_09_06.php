<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$sql = "ALTER TABLE $TableMC ADD $MCgeslacht SET('M', 'V') NOT NULL DEFAULT '' AFTER $MCID;";
mysqli_query($db, $sql);

echo $sql;

?>