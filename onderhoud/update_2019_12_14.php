<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$vars[] = array('EBDeclaratieAddress', 'nog invullen', 'Mail-adres om direct bijlages (facturen ed) in EB te schieten');
$vars[] = array('VersionCount', 1, 'Counter voor sub-versie binnen software-versie');

foreach($vars as $var) {
	$sql = "INSERT INTO $TableConfig ($ConfigName, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES ('". $var[0] ."', '". $var[1] ."', '". $var[2] ."', ". time() .");";
	if(mysqli_query($db, $sql)) {
		echo $var[0] .' toegevoegd<br>';
	}
}


?>