<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

if(isset($_POST['save'])) {
	$startTijd = mktime(0, 0, 1, $_POST['sMaand'], $_POST['sDag'], $_POST['sJaar']);
	$eindTijd = mktime(23, 59, 59, $_POST['eMaand'], $_POST['eDag'], $_POST['eJaar']);
	$i = 0;
	$doorgaan = true;
		
	while($doorgaan) {
		$offset = (7-date("N", $startTijd)) + (7*$i);
		
		$start_1	= mktime(10,0,0,date("n", $startTijd),(date("j", $startTijd)+$offset), date("Y", $startTijd));
		$eind_1		= mktime(11,30,0,date("n", $startTijd),(date("j", $startTijd)+$offset), date("Y", $startTijd));
		
		$start_2	= mktime(16,30,0,date("n", $startTijd),(date("j", $startTijd)+$offset), date("Y", $startTijd));
		$eind_2		= mktime(18,00,0,date("n", $startTijd),(date("j", $startTijd)+$offset), date("Y", $startTijd));
		
		if($eind_2 < $eindTijd) {
			if(isset($_POST['ochtend']))	$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind) VALUES ('$start_1', '$eind_1')";
			if(isset($_POST['middag']))		$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind) VALUES ('$start_2', '$eind_2')";
			$i++;
		} else {
			$doorgaan = false;
		}
	}
	
	# Biddag (Biddag wordt altijd op de tweede woensdag van maart gehouden)
	if(isset($_POST['biddag'])) {
		$offset = 0;
		
		# Op welke dag valt 1 maart
		$marker = date("N", mktime(0, 0, 1, 3, 1, $_POST['sJaar']));
				
		# $marker = 1 (maandag)		=> 10-3
		# $marker = 2 (dinsdag)		=> 9-3
		# $marker = 3 (woensdag)	=> 8-3
		# $marker = 4 (donderdag)	=> 14-3
		# $marker = 5 (vrijdag)		=> 13-3
		# $marker = 6 (zaterdag)	=> 12-3
		# $marker = 7 (zondag)		=> 11-3
		
		# Als $marker > 4 (lees 1 maart is na woensdag), dan week erbij op
		if($marker > 3)	$offset = 7;
		
		$start_biddag = mktime(19, 30, 0, 3, (11-$marker+$offset), $_POST['sJaar']);
		$eind_biddag = mktime(21, 00, 0, 3, (11-$marker+$offset), $_POST['sJaar']);
				
		$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind, $DienstOpmerking) VALUES ('$start_biddag', '$eind_biddag', 'Biddag')";
	}
	
	# Dankdag (wordt iedere eerste woensdag van november gehouden)
	if(isset($_POST['dankdag'])) {
		$offset = 0;
		
		# Op welke dag valt 1 november
		$marker = date("N", mktime(0, 0, 1, 11, 1, $_POST['sJaar']));

		# $marker = 1 (maandag)		=> 3-11
		# $marker = 2 (dinsdag)		=> 2-11
		# $marker = 3 (woensdag)	=> 1-11
		# $marker = 4 (donderdag)	=> 7-11
		# $marker = 5 (vrijdag)		=> 6-11
		# $marker = 6 (zaterdag)	=> 5-11
		# $marker = 7 (zondag)		=> 4-11

		# Als $marker > 4 (lees 1 maart is na woensdag), dan week erbij op
		if($marker > 3)	$offset = 7;

		$start_dankdag = mktime(19, 30, 0, 11, (4-$marker+$offset), $_POST['sJaar']);
		$eind_dankdag = mktime(21, 00, 0, 11, (4-$marker+$offset), $_POST['sJaar']);

		$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind, $DienstOpmerking) VALUES ('$start_dankdag', '$eind_dankdag', 'Dankdag')";
	}
	
	# Dienst van 1ste kerstdag inplannen
	if(isset($_POST['kerst'])) {
		# Op welke dag valt 25 december
		$marker = date("N", mktime(0, 0, 1, 12, 25, $_POST['sJaar']));
		
		# Als 1ste Kerstdag op zondag valt zal er geen dienst zijn
		if($marker < 7) {
			$start_kerst = mktime(10, 00, 0, 12, 25, $_POST['sJaar']);
			$eind_kerst = mktime(11, 30, 0, 12, 25, $_POST['sJaar']);

			$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind, $DienstOpmerking) VALUES ('$start_kerst', '$eind_kerst', '1ste+Kerstdag')";
		}
	}
	
	# Oudjaars-dienst inplannen
	if(isset($_POST['oudjaar'])) {		
		# Op welke dag valt 31 december
		$marker = date("N", mktime(0, 0, 1, 12, 31, $_POST['sJaar']));
		
		# Als Oud & Nieuw op zondag valt zal er geen dienst zijn
		if($marker < 7) {
			$start_oudjaar = mktime(19, 30, 0, 12, 31, $_POST['sJaar']);
			$eind_oudjaar = mktime(21, 00, 0, 12, 31, $_POST['sJaar']);
			
			$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind, $DienstOpmerking) VALUES ('$start_oudjaar', '$eind_oudjaar', 'Oudjaar')";
		}
	}
	
} else {
	$querys = array();
	$sql = "SELECT * FROM $TableDiensten ORDER BY $DienstEind DESC LIMIT 0,1";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	$offset = 24*60*60;
	
	$sDag		= getParam('sDag', date("d", $row[$DienstEind]+$offset));
	$sMaand	= getParam('sMaand', date("m", $row[$DienstEind]+$offset));
	$sJaar	= getParam('sJaar', date("Y", $row[$DienstEind]+$offset));
	$eDag		= getParam('eDag', date("d"));
	$eMaand	= getParam('eMaand', date("m"));
	$eJaar	= getParam('eJaar', date("Y")+1);

	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<table border=0>";
	$text[] = "<tr>";
	$text[] = "	<td>Startdatum</td>";
	$text[] = "	<td><select name='sDag'>";
	for($d=1 ; $d<32 ; $d++) {
		$text[] = "	<option value='$d'". ($d == $sDag ? ' selected' : '') .">$d</option>";
	}
	$text[] = "	</select> - ";
	$text[] = "	<select name='sMaand'>";
	for($m=1 ; $m<13 ; $m++) {
		$text[] = "	<option value='$m'". ($m == $sMaand ? ' selected' : '') .">". $maandArray[$m] ."</option>";
	}
	$text[] = "	</select> - ";
	$text[] = "	<select name='sJaar'>";
	for($j=date("Y"); $j<=(date("Y")+10) ; $j++) {
		$text[] = "	<option value='$j'". ($j == $sJaar ? ' selected' : '') .">$j</option>";
	}
	$text[] = "	</select></td>";
	$text[] = "	<td rowspan='3'>&nbsp;</td>";
	$text[] = "	<td rowspan='3'><input type='submit' name='save' value='Genereer'></td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>Einddatum</td>";
	$text[] = "	<td><select name='eDag'>";
	for($d=1 ; $d<32 ; $d++) {
		$text[] = "	<option value='$d'". ($d == $eDag ? ' selected' : '') .">$d</option>";
	}
	$text[] = "	</select> - ";
	$text[] = "	<select name='eMaand'>";
	for($m=1 ; $m<13 ; $m++) {
		$text[] = "	<option value='$m'". ($m == $eMaand ? ' selected' : '') .">". $maandArray[$m] ."</option>";
	}
	$text[] = "	</select> - ";
	$text[] = "	<select name='eJaar'>";
	for($j=date("Y"); $j<=(date("Y")+10) ; $j++) {
		$text[] = "	<option value='$j'". ($j == $eJaar ? ' selected' : '') .">$j</option>";
	}
	$text[] = "	</select></td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'><input type='checkbox' name='ochtend' value='1' checked> Ochtenddiensten | <input type='checkbox' name='middag' value='1' checked> Middagdiensten</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'><input type='checkbox' name='biddag' value='1'> Biddag | <input type='checkbox' name='dankdag' value='1'> Dankdag<br><input type='checkbox' name='kerst' value='1'> Kerst | <input type='checkbox' name='oudjaar' value='1'> Oudjaar<br></td>";
	$text[] = "</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
	
	# Pasen en Pinksteren rekenen is een ramp
	# Die moeten dus even handmatig opgezocht worden	
	# Pasen (zoek de eerste volle maan op of na 21 maart | zoek de eerstvolgende zondag na deze volle maan. Voilà, je hebt Eerste Paasdag te pakken)
	# Hemelvaart (Hemelvaart is 39 dagen na Eerste Paasdag)
	# Pinksteren (Eerste Pinksterdag is dus tien dagen na Hemelvaart)	
}

if(count($querys) > 0) {
	foreach($querys as $query) {
		$result = mysqli_query($db, $query);		
	}
	
	$text[] = "Diensten toegevoegd<br>";
	toLog('info', $_SESSION['ID'], '', 'nieuwe diensten aangemaakt');
}

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;


?>