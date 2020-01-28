<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();

$temp  = "CREATE TABLE $TableArchief (";
$temp .= " $ArchiefID varchar(12) NOT NULL,";
$temp .= " $ArchiefJaar int(1) NOT NULL,";
$temp .= " $ArchiefNr int(1) NOT NULL,";
$temp .= " $ArchiefDownload int(4) NOT NULL,";
$temp .= " $ArchiefPubDate int(11) NOT NULL,";
$temp .= " $ArchiefName text NOT NULL,";
$temp .= " PRIMARY KEY (`id`),";
$temp .= " UNIQUE KEY `id` (`id`)";
$temp .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1";
$sql[] = $temp;

$temp = "CREATE TABLE $TablePlainText (";
$temp .= " $PlainTextID varchar(12) NOT NULL,";
$temp .= " $PlainTextText text NOT NULL";
$temp .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1";
$sql[] = $temp;

$vars[] = array(4, 'FinAdminAddress', '', 'nog invullen', 'mailadres van de financiele administratie');

foreach($vars as $var) {
	$sql[] = "INSERT INTO $TableConfig ($ConfigGroep, $ConfigName, $ConfigKey, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES ('". $var[0] ."', '". $var[1] ."', '". $var[2] ."', '". $var[3] ."', '". $var[4] ."', ". time() .");";
}

foreach($sql as $query) {
	echo $query;
	if(mysqli_query($db, $query)) {
		echo " -> gelukt<br>";	
	} else {
		echo "<b> -> mislukt</b><br>";	
	}
}



# Na uitvoeren bestand verwijderen
if($productieOmgeving) {
	$delen = explode('/', parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH));
	unlink(end($delen));
}

?>