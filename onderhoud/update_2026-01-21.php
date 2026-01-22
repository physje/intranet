<?php
include_once('../Classes/Mysql.php');

$sql[] = "UPDATE `predikanten` SET `kerk` = 'NGK' WHERE `kerk` like 'GKV'";
$sql[] = "ALTER TABLE `predikanten` ADD `honorarium_2026` INT(5) NOT NULL DEFAULT '12500' AFTER `honorarium_2023`";
$sql[] = "ALTER TABLE `predikanten` DROP `honorarium_2020`";
$sql[] = "UPDATE `predikanten` SET `honorarium_2026` = '12500' WHERE `kerk` like 'NGK'";
$sql[] = "UPDATE `predikanten` SET `honorarium_2026` = '12500' WHERE `kerk` like 'CGK'";
$sql[] = "UPDATE `predikanten` SET `honorarium_special` = '25000' WHERE `kerk` like 'NGK'";
$sql[] = "UPDATE `predikanten` SET `honorarium_special` = '25000' WHERE `kerk` like 'CGK'";
$sql[] = "UPDATE `predikanten` SET `km_vergoeding` = '35' WHERE `kerk` like 'NGK'";
$sql[] = "UPDATE `predikanten` SET `km_vergoeding` = '29' WHERE `kerk` like 'CGK'";

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