<?php
include_once('../general_include/shared_functions.php');
include_once('../general_include/general_config.php');
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$minUserLevel = 2;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

if(isset($_POST['toevoegen'])) {	
	if(!file_exists($ArchiveDir)) {
		mkdir($ArchiveDir);
	}
	
	$uniqeFilename = generateFilename().'.pdf';
	while(file_exists($ArchiveDir.'/'.$uniqeFilename)){
		$uniqeFilename = generateFilename().'.pdf';
	}		
	
	move_uploaded_file($_FILES['trinitas_bestand']['tmp_name'], $ArchiveDir.'/'.$uniqeFilename);
	$pubDate = mktime(9,30,0,$_POST['maand'],$_POST['dag'],$_POST['jaar']);
	
	$sql = "INSERT INTO $TableArchief ($ArchiefID, $ArchiefJaar, $ArchiefNr, $ArchiefName, $ArchiefPubDate) VALUES ('". generateID() ."', '". $_POST['jaargang'] ."', '". $_POST['nummer'] ."', '$uniqeFilename', $pubDate)";
	if(mysqli_query($db, $sql)) {
		$HTML[] = "Gelukt, <a href='main.php'>startpagina</a>";
		toLog('info', $_SESSION['UserID'], '', $_POST['jaargang'] .' - '. $_POST['nummer'] .' toegevoegd');
	} else {
		$HTML[] = "Daar ging iets verkeerd";
		toLog('error', $_SESSION['UserID'], '', 'Problemen met toevoegen '.$_POST['jaargang'] .' - '. $_POST['nummer'] .' toegevoegd');
	}
} else {
	$sql = "SELECT MAX($ArchiefJaar) as max FROM $TableArchief";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	$max_jaargang = $row[max];
	
	/*
	$sql = "SELECT MIN($ArchiefJaar) as min FROM $TableArchief";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	$min_jaargang = $row[min];
	*/
	
	$sql = "SELECT MAX($ArchiefNr) as max FROM $TableArchief WHERE $ArchiefJaar = $max_jaargang";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	$max_nummer = $row[max];
		
	if(isset($_REQUEST['fileID'])) {
		$data = getTrinitasData($_REQUEST['fileID']);
		
		$dag			= date("d", $data['pubDate']);
		$maand		= date("m", $data['pubDate']);
		$jaar			= date("Y", $data['pubDate']);
		$jaargang	= $data['jaar'];
		$nummer		= $data['nr'];
	} else {	
		# Reken aantal dagen uit tot komende zondag
		# reken dat om naar seconden
		# tel dat bij de huidige tijd op
		$komendeZondag = time() + (60*60*24*(7-date("w")));
		
		$dag		= date("d", $komendeZondag);
		$maand	= date("m", $komendeZondag);
		$jaar		= date("Y", $komendeZondag);
				
		
		$jaargang = $max_jaargang;
		$nummer = ($max_nummer+1);
				
		/*
		$sql = "SELECT * FROM $TableArchief WHERE $ArchiefJaar = $max_jaargang AND $ArchiefNr = $max_nummer";
		$result = mysqli_query($db, $sql);
		$row = mysqli_fetch_array($result);
		$id = $row[$ArchiefID];
		$data = getTrinitasData($id);
		
		$komendeDatum = $data['pubDate'] + (3*7*24*60*60);
		
		$dag		= date("d", $komendeDatum);
		$maand	= date("m", $komendeDatum);
		$jaar		= date("Y", $komendeDatum);		
		*/
	}
	
	$HTML[] = "<form enctype='multipart/form-data' method='post' action='$_SERVER[PHP_SELF]'>\n";
	if(isset($_REQUEST['fileID']))	$HTML[] = "<input type='hidden' name='fileID' value='$fileID'>\n";			
	$HTML[] = "<table>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td>jr. & nr.</td>";
	$HTML[] = "	<td><select name='jaargang'>";
	for($j=1;$j<=($max_jaargang+1);$j++) {
		$HTML[] = "	<option value='$j'". ($j == $jaargang ? ' selected' : '') .">$j</value>";
	}
	$HTML[] = "	</select> - ";
	$HTML[] = "	<select name='nummer'>";
	for($n=1;$n<=25;$n++) {
		$HTML[] = "	<option value='$n'". ($n == $nummer ? ' selected' : '') .">$n</value>";
	}
	$HTML[] = "	</select></td>";
	$HTML[] = "</tr>";
	/*
	$HTML[] = "<tr>";
	$HTML[] = "	<td>Nummer</td>";
	$HTML[] = "	<td><select name='nummer'>";
	for($n=1;$n<=25;$n++) {
		$HTML[] = "	<option value='$n'". ($n == $nummer ? ' selected' : '') .">$n</value>";
	}
	$HTML[] = "	</select></td>";
	$HTML[] = "</tr>";
	*/
	$HTML[] = "<tr>";
	$HTML[] = "	<td>Bestand</td>";
	$HTML[] = "	<td><input name='trinitas_bestand' type='file'>". (isset($_REQUEST['fileID']) ? " (<a href='download.php?fileID=". $_REQUEST['fileID'] ."'>huidig</a>)</td>" : '');
	$HTML[] = "</tr>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td>Publicatiedatum</td>";
	$HTML[] = "	<td><select name='dag'>\n";
	for($d=1 ; $d<=31 ; $d++) {
		$HTML[] = "	<option value='$d'". ($d == $dag ? ' selected' : '') .">$d</option>\n";
	}
	$HTML[] = "	</select>\n";	
	$HTML[] = "	<select name='maand'>\n";
	for($m=1 ; $m<=12 ; $m++) {
		$HTML[] = "	<option value='$m'". ($m == $maand ? ' selected' : '') .">". $maandNamen[$m] ."</option>\n";
	}
	$HTML[] = "	</select>\n";
	$HTML[] = "	<select name='jaar'>\n";
	for($j=2006 ; $j<=(date("Y")+1) ; $j++) {
		$HTML[] = "	<option value='$j'". ($j == $jaar ? ' selected' : '') .">$j</option>\n";
	}
	$HTML[] = "	</select></td>";
	$HTML[] = "</tr>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td colspan='2'>&nbsp;</td>";
	$HTML[] = "</tr>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td colspan='2' align='center'>". (isset($_REQUEST['fileID']) ? "<input type='submit' name='opslaan' value='Bijwerken'>" : "<input type='submit' name='toevoegen' value='Voeg toe'>") ."</td>";
	$HTML[] = "</tr>";
	$HTML[] = "</table>";
	$HTML[] = "</form>";
	
}

verdeelBlokken(implode("\n", $HTML));

?>