<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();

$temp = "CREATE TABLE $TableMail (";
$temp .= " $MailID INT(6) NOT NULL,";
$temp .= " $MailTime INT(11) NOT NULL,";
$temp .= " $MailMail TEXT NOT NULL";
$temp .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1";
$sql[] = $temp;

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