<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$vars[] = array('configGroups', '1', 'Algemeen', 'Groep in de config-file');
$vars[] = array('configGroups', '2', 'Site', 'Groep in de config-file');
$vars[] = array('configGroups', '3', 'Voorganger', 'Groep in de config-file');
$vars[] = array('configGroups', '4', 'Declaratie', 'Groep in de config-file');
$vars[] = array('configGroups', '5', 'Mailchimp', 'Groep in de config-file');
$vars[] = array('productieOmgeving', '', 'true', 'Test- of productie-omgeving');

foreach($vars as $var) {
	$sql = "INSERT INTO $TableConfig ($ConfigName, $ConfigKey, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES ('". $var[0] ."', '". $var[1] ."', '". $var[2] ."', '". $var[3] ."', ". time() .");";
	if(mysqli_query($db, $sql)) {
		echo $var[0] .' toegevoegd<br>';
	}
}


?>