<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('shared.php');

$db = connect_db();

$tijd = mktime(16,0,0,10,7,2022);
$stap = 0.25*60*60;

$sql = "SELECT * FROM `votingcodes` ORDER BY `time` DESC";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);

$max = $row['time'];

do {
    $sql = "SELECT * FROM `votingcodes` WHERE `time` BETWEEN $tijd AND ". ($tijd + $stap - 1);
    $aantal = mysqli_num_rows(mysqli_query($db, $sql));
    
    echo date('d-m-Y H:i', $tijd) .';'. date('d-m-Y H:i', ($tijd + $stap - 1)) .';'. $aantal ."\n";
    $tijd = $tijd + $stap;
    
} while($tijd < $max);


?>