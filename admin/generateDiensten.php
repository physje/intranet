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
			$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind) VALUES ('$start_1', '$eind_1')";
			$querys[] = "INSERT INTO $TableDiensten ($DienstStart, $DienstEind) VALUES ('$start_2', '$eind_2')";
			$i++;
		} else {
			$doorgaan = false;
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
	$text[] = "<table>";
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
	$text[] = "	<td rowspan='2'>&nbsp;</td>";
	$text[] = "	<td rowspan='2'><input type='submit' name='save' value='Genereer'></td>";
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
	$text[] = "</table>";
	$text[] = "</form>";
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