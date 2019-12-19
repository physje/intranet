<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$vars[] = array('tagGeslacht', 'M', 'nog invullen', 'Mailchimp tag');
$vars[] = array('tagGeslacht', 'V', 'nog invullen', 'Mailchimp tag');
$vars[] = array('tagStatus', 'belijdend lid', 'nog invullen', 'Mailchimp tag');
$vars[] = array('tagStatus', 'betrokkene', 'nog invullen', 'Mailchimp tag');
$vars[] = array('tagStatus', 'dooplid', 'nog invullen', 'Mailchimp tag');


foreach($vars as $var) {
	$sql = "INSERT INTO $TableConfig ($ConfigName, $ConfigKey, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES ('". $var[0] ."', '". $var[1] ."', '". $var[2] ."', '". $var[3] ."', ". time() .");";
	if(mysqli_query($db, $sql)) {
		echo $var[0] .' toegevoegd<br>';
	}
}

$sql = "CREATE TABLE IF NOT EXISTS $TableEBBoekstuk (";
$sql .= "  $EBBoekstukJaar int(4) NOT NULL,";
$sql .= "  $EBBoekstukVolgNr int(4) NOT NULL,";
$sql .= "  UNIQUE KEY $EBBoekstukJaar ($EBBoekstukJaar)";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=latin1;";
mysqli_query($db, $sql);


?>