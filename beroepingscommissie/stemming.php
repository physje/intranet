<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();

if(isset($_REQUEST['token'])) {
	if(validVotingCode($_REQUEST['token'])) {
		if(uniqueVotingCode($_REQUEST['token'])) {
			if(isset($_POST['save'])) {				
				$sql_token = "UPDATE `votingcodes` SET `time` = ". time().", `keuze` = '". $_POST['keuze'] ."' WHERE `votingtoken` LIKE '". $_POST['token'] ."'";
				
				if(mysqli_query($db, $sql_token)) {
					$text[] = 'Dank voor het uitbrengen van uw stem.';
				} else {
					$text[] = 'Helaas kon uw stem niet worden weggeschreven.';
				}
			} else {
				$text[] = "<form action='stemming.php' method='post'>";
				$text[] = "<input type='hidden' name='token' value='". $_REQUEST['token'] ."'>";
				$text[] = "Vindt u dat wij ds. Reinier Kramer moeten beroepen?<br>";
				$text[] = "<br>";
				$text[] = "<input type='radio' name='keuze' value='1'".($_REQUEST['keuze'] == 1 ? ' checked' : '') ."> Ja<br>";
				$text[] = "<input type='radio' name='keuze' value='0'".($_REQUEST['keuze'] == 0 ? ' checked' : '') ."> Nee<br>";
				$text[] = "<br>";
				$text[] = "<input type='submit' name='save' value='Stem uitbrengen'><br>";
				$text[] = "</form>";
			}
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
	
	$sql = "SELECT * FROM `votingcodes` WHERE `votingtoken` LIKE '$code' AND `tijd` > 0";
	$result = mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 0) {
		return true;
	} else {
		return false;
	}
}

?>