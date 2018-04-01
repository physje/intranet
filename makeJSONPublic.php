<?php
include_once('include/functions.php');
include_once('include/config.php');

$db = connect_db();

$sql_dienst = "SELECT $DienstID FROM $TableDiensten WHERE $DienstEind > ". (time()-(31*24*60*60)) ." AND $DienstEind < ". (time()+(90*24*60*60));
$result_dienst = mysqli_query($db, $sql_dienst);
if($row_dienst = mysqli_fetch_array($result_dienst)) {		
	do {
		# Wat is de ID van de dienst
		# Welke gegevens horen daar bij
		# Welke diensten zijn er nog meer die dag
		$dienst = $row_dienst[$DienstID];		
		$data_dienst = getKerkdienstDetails($dienst);
		
		$data_dienst['id'] = $dienst;		
		$diensten[] = $data_dienst;
	} while($row_dienst = mysqli_fetch_array($result_dienst));	
}

echo json_encode ($diensten);

?>