<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$querys = array();

if(isset($_POST['save'])) {
	#$startTijd = mktime(0, 0, 1, $_POST['sMaand'], $_POST['sDag'], $_POST['sJaar']);
	#$eindTijd = mktime(23, 59, 59, $_POST['eMaand'], $_POST['eDag'], $_POST['eJaar']);
	$startTijd = mktime(0, 0, 1, 1, 1, $_POST['jaar']);
	$eindTijd = mktime(23, 59, 59, 12, 31, $_POST['jaar']);
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
	
	# Mocht Goede Vrijdag, Hemelvaart of omschrijvingen moeten worden toegevoegd
	# Dan even opvragen op welke data Pasen valt
	if(isset($_POST['vrijdag']) OR isset($_POST['hemelvaart']) OR isset($_POST['omschrijving'])) {
		$DataPasen = getPasen($_POST['jaar']);
	}	
		
	# Biddag (Biddag wordt altijd op de tweede woensdag van maart gehouden)
	if(isset($_POST['biddag'])) {
		$offset = 0;
		
		# Op welke dag valt 1 maart
		$marker = date("N", mktime(0, 0, 1, 3, 1, $_POST['jaar']));
				
		# $marker = 1 (maandag)		=> 10-3
		# $marker = 2 (dinsdag)		=> 9-3
		# $marker = 3 (woensdag)	=> 8-3
		# $marker = 4 (donderdag)	=> 14-3
		# $marker = 5 (vrijdag)		=> 13-3
		# $marker = 6 (zaterdag)	=> 12-3
		# $marker = 7 (zondag)		=> 11-3
		
		# Als $marker > 4 (lees 1 maart is na woensdag), dan week erbij op
		if($marker > 3)	$offset = 7;
		
		$start_biddag = mktime(19, 30, 0, 3, (11-$marker+$offset), $_POST['jaar']);
		$eind_biddag = mktime(21, 00, 0, 3, (11-$marker+$offset), $_POST['jaar']);
				
		$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind, $DienstOpmerking) VALUES ('$start_biddag', '$eind_biddag', 'Biddag')";
	}
		
	# Goede vrijdag (Goede vrijdag is de vrijdag voor Pasen)
	if(isset($_POST['vrijdag']) AND count($DataPasen) > 1) {
		$start_vrijdag = mktime(19, 30, 0, $DataPasen['maand'], ($DataPasen['dag']-2), $_POST['jaar']);
		$eind_vrijdag = mktime(21, 00, 0, $DataPasen['maand'], ($DataPasen['dag']-2), $_POST['jaar']);
				
		$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind, $DienstOpmerking) VALUES ('$start_vrijdag', '$eind_vrijdag', 'Goede+Vrijdag')";
	}
	
	# Hemelvaart (Hemelvaart is 39 dagen na Eerste Paasdag)
	if(isset($_POST['vrijdag']) AND count($DataPasen) > 1) {
		$start_hemelvaart = mktime(10, 00, 0, $DataPasen['maand'], ($DataPasen['dag']+39), $_POST['jaar']);
		$eind_hemelvaart = mktime(11, 30, 0, $DataPasen['maand'], ($DataPasen['dag']+39), $_POST['jaar']);
				
		$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind, $DienstOpmerking) VALUES ('$start_hemelvaart', '$eind_hemelvaart', 'Hemelvaart')";
	}
	
	# Dankdag (wordt iedere eerste woensdag van november gehouden)
	if(isset($_POST['dankdag'])) {
		$offset = 0;
		
		# Op welke dag valt 1 november
		$marker = date("N", mktime(0, 0, 1, 11, 1, $_POST['jaar']));

		# $marker = 1 (maandag)		=> 3-11
		# $marker = 2 (dinsdag)		=> 2-11
		# $marker = 3 (woensdag)	=> 1-11
		# $marker = 4 (donderdag)	=> 7-11
		# $marker = 5 (vrijdag)		=> 6-11
		# $marker = 6 (zaterdag)	=> 5-11
		# $marker = 7 (zondag)		=> 4-11

		# Als $marker > 4 (lees 1 maart is na woensdag), dan week erbij op
		if($marker > 3)	$offset = 7;

		$start_dankdag = mktime(19, 30, 0, 11, (4-$marker+$offset), $_POST['jaar']);
		$eind_dankdag = mktime(21, 00, 0, 11, (4-$marker+$offset), $_POST['jaar']);

		$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind, $DienstOpmerking) VALUES ('$start_dankdag', '$eind_dankdag', 'Dankdag')";
	}
	
	# Dienst van 1ste kerstdag inplannen
	if(isset($_POST['kerst'])) {
		# Op welke dag valt 25 december
		$marker = date("N", mktime(0, 0, 1, 12, 25, $_POST['jaar']));
		
		# Als 1ste Kerstdag op zondag valt, hoeft er geen extra dienst toegevoegd te worden
		if($marker < 7) {
			$start_kerst = mktime(10, 00, 0, 12, 25, $_POST['jaar']);
			$eind_kerst = mktime(11, 30, 0, 12, 25, $_POST['jaar']);

			$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind, $DienstOpmerking) VALUES ('$start_kerst', '$eind_kerst', '1ste+Kerstdag')";
		}
	}
	
	# Oudjaars-dienst inplannen
	if(isset($_POST['oudjaar'])) {		
		# Op welke dag valt 31 december
		$marker = date("N", mktime(0, 0, 1, 12, 31, $_POST['jaar']));
		
		# Als Oud & Nieuw op zondag valt, hoeft er geen extra dienst toegevoegd te worden
		if($marker < 7) {
			$start_oudjaar = mktime(19, 30, 0, 12, 31, $_POST['jaar']);
			$eind_oudjaar = mktime(21, 00, 0, 12, 31, $_POST['jaar']);
			
			$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind, $DienstOpmerking) VALUES ('$start_oudjaar', '$eind_oudjaar', 'Oudjaar')";
		}
	}
} else {	
	$sql = "SELECT * FROM $TableDiensten ORDER BY $DienstEind DESC LIMIT 0,1";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	$offset = 24*60*60;
	
	$Jaar	= getParam('Jaar', date("Y", $row[$DienstEind]+$offset));
	
	/*
	$sDag		= getParam('sDag', date("d", $row[$DienstEind]+$offset));
	$sMaand	= getParam('sMaand', date("m", $row[$DienstEind]+$offset));
	$sJaar	= getParam('sJaar', date("Y", $row[$DienstEind]+$offset));
	$eDag		= getParam('eDag', 31);
	$eMaand	= getParam('eMaand', 12);
	$eJaar	= getParam('eJaar', date("Y", $row[$DienstEind]+$offset));
	#$eDag		= getParam('eDag', date("d"));
	#$eMaand	= getParam('eMaand', date("m"));
	#$eJaar	= getParam('eJaar', date("Y")+1);
	*/
	
		
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<table border=1>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'>Genereer diensten voor <select name='jaar'>";
	for($j=date("Y"); $j<=(date("Y")+10) ; $j++) {
		$text[] = "	<option value='$j'". ($j == $Jaar ? ' selected' : '') .">$j</option>";
	}
	$text[] = "	</select></td>";
	$text[] = "	<td rowspan='5'><input type='submit' name='save' value='Genereer'></td>";
	$text[] = "</tr>";
	
	/*
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
	*/
	
	$text[] = "<tr>";
	$text[] = "	<td><input type='checkbox' name='ochtend' value='1' checked> Ochtenddiensten</td>";
	$text[] = "	<td><input type='checkbox' name='middag' value='1' checked> Middagdiensten</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td><input type='checkbox' name='biddag' value='1'> Biddag</td>";
	$text[] = "	<td><input type='checkbox' name='dankdag' value='1'> Dankdag</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td><input type='checkbox' name='kerst' value='1'> Kerst</td>";
	$text[] = "	<td><input type='checkbox' name='oudjaar' value='1'> Oudjaar</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td><input type='checkbox' name='vrijdag' value='1'> Goede Vrijdag</td>";
	$text[] = "	<td><input type='checkbox' name='hemelvaart' value='1'> Hemelvaart</td>";
	$text[] = "</tr>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td><input type='checkbox' name='omschrijving' value='1'> Omschrijvingen</td>";
	$text[] = "	<td>&nbsp;</td>";
	$text[] = "</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
	
	# Pasen en Pinksteren rekenen is een ramp; Die moeten dus even handmatig opgezocht worden	
	# Pasen (zoek de eerste volle maan op of na 21 maart | zoek de eerstvolgende zondag na deze volle maan. Voilà, je hebt Eerste Paasdag te pakken)
	
	# Pinksteren (Eerste Pinksterdag is dus tien dagen na Hemelvaart)	
}

if(count($querys) > 0) {
	foreach($querys as $query) {
		$result = mysqli_query($db, $query);
	}
	
	$text[] = "Diensten toegevoegd<br>";
	toLog('info', $_SESSION['ID'], '', 'nieuwe diensten aangemaakt');
}

# Dit kan pas doorlopen worden als hierboven de diensten zijn ingevoerd
# Vandaar deze wat gekunstelde oplossing
if(isset($_POST['omschrijving'])) {
#if(true) {
	$details[] = array(18, 12, 24, 12, '4e Advent');
	$details[] = array(11, 12, 17, 12, '3e Advent');
	$details[] = array(4, 12, 10, 12, '2e Advent');
	$details[] = array(27, 11, 3, 12, '1e Advent');
	$details[] = array(20, 11, 26, 11, 'Zondag Voleinding');
	
	# Alleen als de datum van Pasen bekend is kan Pasen en Pinksteren worden toegevoegd
	if(count($DataPasen) > 1) {
		$details[] = array($DataPasen['dag'], $DataPasen['maand'], $DataPasen['dag'], $DataPasen['maand'], 'Pasen');
		$details[] = array(($DataPasen['dag']+49), $DataPasen['maand'], ($DataPasen['dag']+49), $DataPasen['maand'], 'Pinksteren');
	}
			
	$details[] = array(8, 1, 14, 1, 'Heilig Avondmaal');
	$details[] = array(8, 3, 14, 3, 'Heilig Avondmaal');
	$details[] = array(8, 5, 14, 5, 'Heilig Avondmaal');
	$details[] = array(8, 7, 14, 7, 'Heilig Avondmaal');
	$details[] = array(8, 9, 14, 9, 'Heilig Avondmaal');
	$details[] = array(8, 11, 14, 11, 'Heilig Avondmaal');
	
	$details[] = array(25, 1, 31, 1, 'Doopzondag');
	$details[] = array(22, 2, 28, 2, 'Doopzondag');
	$details[] = array(25, 3, 31, 3, 'Doopzondag');
	$details[] = array(24, 4, 30, 4, 'Doopzondag');
	$details[] = array(25, 5, 31, 5, 'Doopzondag');
	$details[] = array(24, 6, 30, 6, 'Doopzondag');
	$details[] = array(25, 7, 31, 7, 'Doopzondag');
	$details[] = array(25, 8, 31, 8, 'Doopzondag');
	$details[] = array(24, 9, 30, 9, 'Doopzondag');
	$details[] = array(25, 10, 31, 10, 'Doopzondag');
	$details[] = array(24, 11, 30, 11, 'Doopzondag');
	#$details[] = array(25, 12, 31, 12, 'Doopzondag');
		
	$details[] = array(25, 12, 25, 12, '1ste Kerstdag');
	$details[] = array(31, 12, 31, 12, 'Oudjaar');
				
	foreach($details as $dag) {
		$startTijd = mktime(0, 0, 1, $dag[1], $dag[0], $_POST['jaar']);
		$eindTijd = mktime(23, 59, 59, $dag[3], $dag[2], $_POST['jaar']);
		
		$diensten = getKerkdiensten($startTijd, $eindTijd);
		
		foreach($diensten as $dienst) {
			$dienstDetails = getKerkdienstDetails($dienst);
			
			# Alleen als een dienst op zondag valt de omschrijving toevoegen
			# Als Kerst en Oud & Nieuw niet op een zondag valt, is hierboven de dienst al toegevoegd 
			# en valt hij hier terecht eruit
			if(date("N", $dienstDetails['start']) == 7) {
				$opmerking = '';
				if($dienstDetails['bijzonderheden'] != '') {
					$opmerking = $dienstDetails['bijzonderheden'] .' - ';
				}
				
				$opmerking .= $dag[4];
				
				$query = "UPDATE $TableDiensten SET $DienstOpmerking = '". urlencode($opmerking) ."' WHERE $DienstID = $dienst";
				mysqli_query($db, $query);
			}
		}					
	}
}

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;


?>