<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

# 100 dagen, 24 uur, 60 minuten, 60 seconden.
# $grens = time() - (100*24*60*60);

# 1 jaar geleden
$grens = mktime(0, 0, 0, (date("n")-12));

$sql_diensten = "SELECT $DienstID FROM $TableDiensten WHERE $DienstStart < $grens";
$result_diensten = mysqli_query($db, $sql_diensten);
if($row_diensten = mysqli_fetch_array($result_diensten)) {	
	do {
		$sql_rooster = "DELETE FROM $TablePlanning WHERE $PlanningDienst = ". $row_diensten[$DienstID];
		mysqli_query($db, $sql_rooster);
		
		$sql_dienst = "DELETE FROM $TableDiensten WHERE $DienstID = ". $row_diensten[$DienstID];
		mysqli_query($db, $sql_dienst);		
	} while($row_diensten = mysqli_fetch_array($result_diensten));
}

toLog('info', '', 'Diensten van voor '. date('d-m-y', $grens).' verwijderd');

?>