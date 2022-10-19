<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');

$fileNameAL		= 'adressenlijst.csv';
$fileNameLPH	= 'laPosta_hoofd.csv';
$fileNameLPP	= 'laPosta_partner.csv';
$fileNameSQL	= 'table.sql';
$fileNameZip	= 'stemming_'. date('Y_m_d') .'.zip';

# Ga op zoek naar alle personen met een mailadres
# Mailadres is daarbij alles met een @-teken erin
$sql = "SELECT * FROM $TableUsers WHERE $UserStatus = 'actief'";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);

$fp = fopen($fileNameAL, 'w+');
fwrite($fp, "Achternaam;Tussenvoegsel;Voornaam;Type;Email\n");
fclose($fp);

$fp = fopen($fileNameLPH, 'w+');
fwrite($fp, "Voornaam;Tussenvoegsel;Achternaam;Email;Token\n");
fclose($fp);

$fp = fopen($fileNameLPP, 'w+');
fwrite($fp, "Voornaam;Tussenvoegsel;Achternaam;Email;Token\n");
fclose($fp);

$fp = fopen($fileNameZip, 'w+');
fclose($fp);

$hoofdLijst = array();

do {
	# identifier is het id binnen scipio
	$scipioID = $row[$UserID];
		
	# Haal alle gegevens op
	$data = getMemberDetails($scipioID);
	$email = $data['mail'];
	
	$voornaam				= ($data['voornaam'] == '' ? $data['voorletters'] : $data['voornaam']);
	$tussenvoegsel	= $data['tussenvoegsel'];
	$achternaam			= $data['achternaam'];
	$votingtoken		= generateID(12);
	
	$addAdres = false;
			
	# LaPosta staat of valt met een correct mailadres
	# Eerst dus even een check of het adres geldig is
	if(isValidEmail($email) AND $data['belijdenis'] == 'belijdend lid' AND $email != '') {		
		if(in_array($email, $hoofdLijst)) {
			$list = 'partner';			
		} else {
			$list = 'hoofd';
		}
		$addAdres = true;
	} elseif($data['relatie'] != 'zoon' AND $data['relatie'] != 'dochter' AND $data['relatie'] != 'inw. persoon' AND $data['belijdenis'] == 'belijdend lid' AND $email == '') {
		$sql_partner = "SELECT * FROM $TableUsers WHERE $UserStatus = 'actief' AND $UserRelatie like 'gezinshoofd' AND $UserMail not like '' AND $UserAdres = ". $row[$UserAdres];
		$result_partner = mysqli_query($db, $sql_partner);
		
		if($row_partner = mysqli_fetch_array($result_partner)) {
			$email = $row_partner[$UserMail];
			
			if(in_array($email, $hoofdLijst)) {
				$list = 'partner';			
			} else {
				$list = 'hoofd';
			}
			$addAdres = true;	
		} else {
			echo "$voornaam $achternaam heeft geen gezinshoofd met mailadres<br>";
		}
#	} else {
#		echo "$voornaam $achternaam overgeslagen<br>";
	}
	
	if($addAdres) {	
		# LaPosta files			
		if($list == 'hoofd') {
			$fp = fopen($fileNameLPH, 'a+');
			fwrite($fp, "$voornaam;$tussenvoegsel;$achternaam;$email;$votingtoken\n");
			fclose($fp);
			
			$hoofdLijst[] = $email;
		} else {
			$fp = fopen($fileNameLPP, 'a+');
			fwrite($fp, "$voornaam;$tussenvoegsel;$achternaam;$email;$votingtoken\n");
			fclose($fp);
		}
		
		# SQL-file		
		$fp = fopen($fileNameSQL, 'a+');
		fwrite($fp, "$votingtoken\n");
		fclose($fp);
		
		#echo "$voornaam $achternaam | $email -> $list <br>";		
		
		$rij = "$achternaam;$tussenvoegsel;$voornaam;". $data['belijdenis'] .";$email";		
	} else {
		$rij = "$achternaam;$tussenvoegsel;$voornaam;". $data['belijdenis'] .";geen";
	}
	
	# Adressen-lijst
	$fp = fopen($fileNameAL, 'a+');
	fwrite($fp, $rij ."\n");
	fclose($fp);
		
} while($row = mysqli_fetch_array($result));

$zip = new ZipArchive;
if ($zip->open($fileNameZip) === TRUE) {
	$zip->addFile($fileNameAL, $fileNameAL);
	$zip->addFile($fileNameLPH, $fileNameLPH);
	$zip->addFile($fileNameLPP, $fileNameLPP);
	$zip->addFile($fileNameSQL, $fileNameSQL);
	$zip->close();
}

unlink($fileNameAL);
unlink($fileNameLPH);
unlink($fileNameLPP);
unlink($fileNameSQL);

echo "Download <a href='$fileNameZip'>bronbestanden</a>";