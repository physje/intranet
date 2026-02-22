<?php
include_once('../Classes/Mysql.php');

$sql[] = "ALTER TABLE `agenda` ADD `stagesign` SET('0', '1') NOT NULL DEFAULT '0' AFTER `eind`";

$db = new Mysql();
foreach($sql as $query) {
    if($db->query($query)) {
        echo $query .' -> succesvol';
    } else {
        echo $query .' -> <b>NIET succesvol</b>';
    }
    echo '<br>';
}

# Na uitvoeren bestand verwijderen
if($productieOmgeving) {
	$delen = explode('/', parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH));
	unlink(end($delen));
}
?>