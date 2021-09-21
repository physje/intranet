<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$sql = "CREATE TABLE `$TableGebedKalMailOverzicht` (
    `$GebedsKalId` INT NOT NULL AUTO_INCREMENT,
    `$GebedKalCategorie` TEXT NOT NULL,
    `$GebedKalContactPersoon` TEXT NOT NULL,
    `$GebedKalMailadres` TEXT NOT NULL,
    `$GebedKalOpmerkingen` TEXT NOT NULL,
    PRIMARY KEY (`$GebedsKalId`) ) ENGINE=InnoDB DEFAULT CHARSET=latin1";

mysqli_query($db, $sql);

?>