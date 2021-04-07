<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();	

$sql[] = "CREATE TABLE `$TableOpenKerkRooster` (`$OKRoosterTijd` int(11) NOT NULL, `$OKRoosterPos` int(2) NOT NULL, `$OKRoosterPersoon` text NOT NULL)";
$sql[] = "CREATE TABLE `$TableOpenKerkTemplate` (`$OKTemplateWeek` int(1) NOT NULL, `$OKTemplateDag` int(1) NOT NULL, `$OKTemplateTijd` int(2) NOT NULL, `$OKTemplatePos` int(11) NOT NULL,`$OKTemplatePersoon` text NOT NULL)";

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