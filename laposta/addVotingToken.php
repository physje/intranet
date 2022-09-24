<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');

$start = getParam('start', 0);
$stap = 5;

$hoofdLijst = '3t0kfm4zfi';
$partnerLijst = 'aw8rbmpmtq';

# Ga op zoek naar alle personen met een mailadres
# Mailadres is daarbij alles met een @-teken erin
$sql = "SELECT * FROM $TableUsers WHERE $UserStatus = 'actief' LIMIT $start, $stap";
#$sql = "SELECT * FROM $TableUsers WHERE $UserMail like '%@%' AND $UserStatus = 'actief' GROUP BY $UserMail";
#$sql = "SELECT * FROM $TableUsers WHERE $UserMail like 'matthijs@draijer.org'";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);

if(mysqli_num_rows($result) == $stap) {
	echo '<html>';
	echo '<head>';
	echo '	<meta http-equiv="refresh" content="3; url=?start='. ($start+$stap) .'" />';
	echo '</head>';
	echo '<body>';
} else {
	echo '<b>Laatste keer</b>';
	echo '<br>';
	echo "<a href='import.php'>Importeer leden</a>";
}

do {
	# 5 seconden per persoon moet voldoende zijn
	set_time_limit(5);
	
	# identifier is het id binnen scipio
	$scipioID = $row[$UserID];
		
	# Haal alle gegevens op
	$data = getMemberDetails($scipioID);
	$email = $data['mail'];
	
	$addAdres = false;
	$list = '';
	
	$custom_fields['voornaam']			= ($data['voornaam'] == '' ? $data['voorletters'] : $data['voornaam']);
	$custom_fields['tussenvoegsel'] = $data['tussenvoegsel'];
	$custom_fields['achternaam']		= $data['achternaam'];
	$custom_fields['votingtoken']		= generateID(12);
		
	# LaPosta staat of valt met een correct mailadres
	# Eerst dus even een check of het adres geldig is
	if(isValidEmail($email)) {		
		if(lp_onList($hoofdLijst, $email)) {
			$list = $partnerLijst;			
		} else {
			$list = $hoofdLijst;
		}		
		$addAdres = true;	
	} elseif($data['relatie'] != 'zoon' AND $data['relatie'] != 'dochter' AND $email == '') {
		$sql_partner = "SELECT * FROM $TableUsers WHERE $UserStatus = 'actief' AND $UserRelatie like 'gezinshoofd' AND $UserAdres = ". $row[$UserAdres];
		$result_partner = mysqli_query($db, $sql_partner);
		
		if($row_partner = mysqli_fetch_array($result_partner)) {
			$email = $row_partner[$UserMail];
			$addAdres = true;
			
			if(lp_onList($hoofdLijst, $email)) {
				$list = $partnerLijst;			
			} else {
				$list = $hoofdLijst;
			}		
		}
	} else {
		echo $custom_fields['voornaam'] .' '. $custom_fields['achternaam'] .' overgeslagen<br>';
	}
	
	if($addAdres) {
		if(lp_addMember($list, $email, $custom_fields)) {
			echo $custom_fields['voornaam'] .' '. $custom_fields['achternaam'] .'|'. $email .' -> '. $list .'<br>';
			
			$sql_token = "INSERT INTO `votingcodes` (`votingtoken`) VALUES ('". $custom_fields['votingtoken'] ."')";
			mysqli_query($db, $sql_token);
			
		} else {
			echo $custom_fields['voornaam'] .' '. $custom_fields['achternaam'] .' mislukt<br>';
		}
				
		# ff rusten voor we verder gaan
		sleep(2);
	}
	
} while($row = mysqli_fetch_array($result));

echo '<a href="?start='. ($start+$stap) .'">Volgende</a>';