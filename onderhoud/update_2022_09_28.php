<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();	

$sql[] = "CREATE TABLE `$TablePastorConvert` (`$PastorConvertFamID` int(11) NOT NULL, `$PastorConvertFamName` text NOT NULL, `$PastorConvertScipioID` int(11) NOT NULL, `$PastorConvertWijk` text NOT NULL);";
$sql[] = "CREATE TABLE `$TablePastorConvertPas` (`$PastorConvertPastor` int(11) NOT NULL, `$PastorConvertPastorName` text NOT NULL, `$PastorConvertPastorScipio` int(11) NOT NULL);";
$sql[] = "ALTER TABLE `$TablePastorConvertPas` ADD PRIMARY KEY (`$PastorConvertPastor`);";

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