<?php
# https://www.codexworld.com/autocomplete-textbox-using-jquery-php-mysql/

include_once('include/functions.php');
include_once('include/config.php');
include_once('Classes/Mysql.php');
include_once('Classes/Member.php');

$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php"); 
 
// Get search term 
$searchTerm = $_GET['term']; 
 
// Fetch matched data from the database 
$db = new Mysql();
$query = $db->select("SELECT `scipio_id` FROM `leden` WHERE (`achternaam` LIKE '".$searchTerm."%' OR `meisjesnaam` LIKE '".$searchTerm."%' OR `tussenvoegsel` LIKE '".$searchTerm."%' OR `voornaam` LIKE '".$searchTerm."%') AND `status` like 'actief' ORDER BY `achternaam` ASC", true); 
$data = array_column($query, "scipio_id");

// Generate array with skills data 
$namenData = array(); 
foreach($data as $lid) {
    $person = new Member($lid);
    $dataArray = array();    
    $dataArray['value'] = $person->getName();
    $person->nameType = 3;
    $dataArray['selector'] = $person->getName() .'|'. $lid;
    array_push($namenData, $dataArray); 
}
 
// Return results as json encoded array 
echo json_encode($namenData); 
?>