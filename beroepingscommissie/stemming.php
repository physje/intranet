<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();

if(isset($_REQUEST['token'])) {
	if(validVotingCode($_REQUEST['token'])) {
		if(uniqueVotingCode($_REQUEST['token'])) {
			$sql_token = "UPDATE `votingcodes` SET `tijd` = ". time()." AND `keuze` = ". $_REQUEST['keuze'] ." WHERE `votingtoken` LIKE '$code'";
			$result = mysqli_query($db, $sql_token);			
			
			$text[] = 'Dank voor het uitbrengen van uw stem.';			
		} else {
			$text[] = 'Deze stem is al een keer uitgebracht.';
		}		
	} else {
		$text[] = 'Er lijkt geknoeid met deze stem.';
	}
} else {
	$text[] = 'Volg de link uit de email.';
}

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

function validVotingCode($code) {
	$sql = "SELECT * FROM `votingcodes` WHERE `votingtoken` LIKE '$code'";
	$result = mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 0) {
		return false;
	} else {
		return true;
	}
}

function uniqueVotingCode($code) {
	$sql = "SELECT * FROM `votingcodes` WHERE `votingtoken` LIKE '$code' AND `tijd` > 0";
	$result = mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 0) {
		return false;
	} else {
		return true;
	}
}

?>