<?php
# https://www.codexworld.com/autocomplete-textbox-using-jquery-php-mysql/

$VersionCount = 1;
include_once('include/functions.php');
include_once('include/config.php');

$db = connect_db();

$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php"); 
 
// Get search term 
$searchTerm = $_GET['term']; 
 
// Fetch matched data from the database 
$query = $db->query("SELECT * FROM $TableUsers WHERE ($UserAchternaam LIKE '".$searchTerm."%' OR $UserMeisjesnaam LIKE '".$searchTerm."%' OR $UserTussenvoegsel LIKE '".$searchTerm."%' OR $UserVoornaam LIKE '".$searchTerm."%') AND $UserStatus like 'actief' ORDER BY $UserAchternaam ASC"); 
 
// Generate array with skills data 
$namenData = array(); 
if($query->num_rows > 0){ 
    while($row = $query->fetch_assoc()){ 
        #$data['id'] = $row[$UserID];
        $data['value'] = makeName($row[$UserID], 6).seniorJunior($row[$UserID]);
        $data['selector'] = makeName($row[$UserID], 5) .'|'. $row[$UserID];
        array_push($namenData, $data); 
    } 
} 
 
// Return results as json encoded array 
echo json_encode($namenData); 
?>