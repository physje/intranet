<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$sql = "CREATE TABLE `$TableEBoekhouden` (`$EBoekhoudenID` int(7) NOT NULL, `$EBoekhoudenCode` int(3) NOT NULL, `$EBoekhoudenIBAN` TEXT NOT NULL,`$EBoekhoudenNaam` TEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1";
mysqli_query($db, $sql);

?>