<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');
setlocale(LC_TIME, 'nl_NL');
$db = connect_db();

toLog('debug', '', 'checken voor mail versturen');

$sql = "SELECT * FROM $TableArchief WHERE $ArchiefSend like '0'";
$result = mysqli_query($db, $sql);

if($row = mysqli_fetch_array($result )) {
	$nummer = $row[$ArchiefID];
	$TrinitasData = getTrinitasData($nummer);
	
	$HTML[] = "Beste {{voornaam}},<br>";
	$HTML[] = "<br>";
	$HTML[] = "De Trinitas van komende zondag is uit.<br>";
	$HTML[] = "<br>";
	$HTML[] = "Download ". makeTrinitasName($nummer, 9) ." via <a href='". $ScriptURL."trinitas/download.php?fileID=$nummer&key=". $TrinitasData['hash'] ."'>deze link</a>.<br>";
	$HTML[] = "<br>";		
	$HTML[] = "<br>";
	$HTML[] = "Met groet,<br>";
	$HTML[] = "Matthijs<br>";
	$HTML[] = "<br>";
	$HTML[] = "Ps. De Trinitas is ook te vinden in de 3GK Scipio-app getoond. Nog geen 3GK Scipio app? Bekijk <a href='http://www.draijer.org/download/3GK/InstallatieHandleidingScipioApp3GK.pdf'>hier</a> de handleiding.";
	
	$info['name']			= 'Trinitas - '. date('Y.m.d', $TrinitasData['pubDate']);
	$info['subject']	= 'Trinitas '. time2str('%e %B', $TrinitasData['pubDate']);
	$info['from']			= array('name' => 'Trinitas','email' => 'matthijs.draijer@koningskerkdeventer.nl');
	$info['list_ids']	= array($LPTrinitasListID);
	
	# 5 minuten later
	$verzendtijd = time()+300;
		
	$preheader = $disclaimer = $afmelding = '';
	include('../include/LP_HeaderFooter.php');
	$bericht  = implode("\n", $LaPostaHeader);
	$bericht .= implode("\n", $HTML);
	$bericht .= implode("\n", $LaPostaFooter);
	
	$campaignTrinitas = lp_createMail($info);
	if(lp_populateMail($campaignTrinitas, $bericht)) {
		lp_scheduleMail($campaignTrinitas, $verzendtijd);
	}
	
	# In de database het aantal verstuurde exemplaten opnemen
	$sql = "UPDATE $TableArchief SET $ArchiefSend = '1' WHERE $ArchiefID like '$nummer'";
	mysqli_query($db, $sql);
}

?>