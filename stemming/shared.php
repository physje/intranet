<?php

# Moet de optie met de meeste stemmen op 100% gezet worden ?
$relatief = false;

# Wat zijn de keuze-opties
$opties[1] = 'Ja, ik sta achter de voorgenomen beroeping van Ds. Reinier Kramer';
$opties[0] = 'Nee, ik sta niet achter de voorgenomen beroeping van Ds. Reinier Kramer';
$opties[2] = 'Ik stem blanco';

# Wanneer moet de stemming dicht
$sluiting = mktime(18, 0, 0, 10, 12, 2022);







function validVotingCode($code) {
	global $db;
	
	$sql = "SELECT * FROM `votingcodes` WHERE `votingtoken` LIKE '$code'";
	$result = mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 0) {
		return false;
	} else {
		return true;
	}
}

function uniqueVotingCode($code) {
	global $db;
	
	$sql = "SELECT * FROM `votingcodes` WHERE `votingtoken` LIKE '$code' AND `time` > 0";
	$result = mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 0) {
		return true;
	} else {
		return false;
	}
}

?>