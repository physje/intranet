<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/config_mails.php');
include_once('include/HTML_TopBottom.php');
include_once('include/HTML_HeaderFooter.php');

$db = connect_db();
$cfgProgDir = 'auth/';
$requiredUserGroups = array(1, 22);
include($cfgProgDir. "secure.php");

# Als er op een knop gedrukt is, het rooster wegschrijven
if(isset($_POST['save']) OR isset($_POST['maanden'])) {	
	foreach($_POST['collecte'] as $dienst => $collectes) {
		$oldData = getKerkdienstDetails($dienst);
		
		$set = array();
		$set[] = $DienstCollecte_1 .' = \''. urlencode($collectes[1]) .'\'';
		$set[] = $DienstCollecte_2 .' = \''. urlencode($collectes[2]) .'\'';
		
		$sql = "UPDATE $TableDiensten SET ". implode(', ', $set)." WHERE $DienstID = ". $dienst;		
			
		if(mysqli_query($db, $sql)) {
			if($oldData['collecte_1'] != $collectes[1]) {
				$dagdeel = formatDagdeel($oldData['start']);				
				if($oldData['collecte_1'] != '' AND $collectes[1] != '' ) {
					$bericht[] = '1ste collecte van de '. $dagdeel.' van '. date('d-m-y', $oldData['start']).' is gewijzigd van <i>'. $oldData['collecte_1'] .'</i> naar <i>'. $collectes[1] .'</i>';
				} elseif($collectes[1] == '') {
					$bericht[] = '<i>'. $oldData['collecte_1'] .'</i> is als 1ste collecte van de '. $dagdeel.' van '. date('d-m-y', $oldData['start']).' verwijderd.';
				} else {
					$bericht[] = '<i>'. $collectes[1] .'</i> is als 1ste collecte van de '. $dagdeel.' van '. date('d-m-y', $oldData['start']).' toegevoegd.';
				}
			}
			
			if($oldData['collecte_2'] != $collectes[2]) {
				$dagdeel = formatDagdeel($oldData['start']);
				if($oldData['collecte_2'] != '' AND $collectes[2] != '') {
					$bericht[] = '2de collecte van de '. $dagdeel.' van '. date('d-m-y', $oldData['start']).' is gewijzigd van <i>'. $oldData['collecte_2'] .'</i> naar <i>'. $collectes[2] .'</i>';
				} elseif($collectes[2] == '') {
					$bericht[] = '<i>'. $oldData['collecte_2'] .'</i> is als 2de collecte van de '. $dagdeel.' van '. date('d-m-y', $oldData['start']).' verwijderd.';
				} else {
					$bericht[] = '<i>'. $collectes[2] .'</i> is als 2de collecte van de '. $dagdeel.' van '. date('d-m-y', $oldData['start']).' toegevoegd.';
				}
			}
		} else {
			toLog('error', $_SESSION['ID'], '', 'Collectes van '. date('d-m-y', $oldData['start']) .' konden niet worden opgeslagen');
		}
	}
	
	if(isset($bericht)){
		$param['to'][] = array('scipiobeheer@koningskerkdeventer.nl', 'Scipio beheer');
		$param['subject'] = count($bericht).' '.(count($bericht) > 1 ? 'gewijzigde collectedoelen' : 'gewijzigd collectedoel');
		$param['message'] = implode('<br>', $bericht);
				
		if(!sendMail_new($param)) {
			toLog('error', $_SESSION['ID'], '', 'Kon geen mail sturen naar Scipio-beheer voor gewijzigde collectes');
		} else {
			toLog('debug', $_SESSION['ID'], '', 'Mail gestuurd naar Scipio-beheer voor gewijzigde collectes');
		}		
	}	
	toLog('info', $_SESSION['ID'], '', 'Collectes bijgewerkt');
}

# Als er op de knop van 3 maanden extra geklikt is, 3 maanden bij de eindtijd toevoegen
# Eerst initeren, event. later ophogen
if(isset($_POST['blokken'])) {
	$blokken = $_POST['blokken'];
} else {
	$blokken = 1;
}

if(isset($_POST['maanden'])) {
	$blokken++;
}

# Haal alle kerkdiensten binnen een tijdsvak op
$diensten = getKerkdiensten(mktime(0,0,0), mktime(date("H"),date("i"),date("s"),(date("n")+(3*$blokken))));

//$text[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$text[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";

$text[] = "<input type='hidden' name='blokken' value='$blokken'>";
$text[] = "<table>";
$text[] = "<thead>";
$text[] = "<tr>";
$text[] = "	<th>Datum</th>";
$text[] = "	<th>Start</th>";
$text[] = "	<th>Collecte 1</th>";
$text[] = "	<th>Collecte 2</th>";
$text[] = "	<th>Bijzonderheid</th>";
$text[] = "</tr>";
$text[] = "</thead>";

foreach($diensten as $dienst) {
	$data = getKerkdienstDetails($dienst);
	
	$text[] = "<tr>";
	$text[] = "	<td align='right'>". time2str("%a %e %b", $data['start']) ."</td>";
	$text[] = "	<td>". date('H:i', $data['start']) ."</td>";	
	$text[] = "	<td><input type='text' name='collecte[$dienst][1]' value='". addslashes($data['collecte_1']) ."'></td>";
	$text[] = "	<td><input type='text' name='collecte[$dienst][2]' value='". addslashes($data['collecte_2']) ."'></td>";		
	$text[] = "	<td>". ($data['bijzonderheden'] != '' ? $data['bijzonderheden'] : '&nbsp;') ."</td>";
	$text[] = "</tr>";
}

$text[] = "</table>";
$text[] = "<p class='after_table'><input type='submit' name='save' value='Diensten opslaan'>&nbsp;<input type='submit' name='maanden' value='Volgende 3 maanden'></p>";
$text[] = "</form>";

$header[] = '<style>';
$header[] = '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
$header[] = '	td:nth-of-type(1):before { content: "Datum"; }';
$header[] = '	td:nth-of-type(2):before { content: "Start"; }';
$header[] = '	td:nth-of-type(3):before { content: "Collecte 1"; }';
$header[] = '	td:nth-of-type(4):before { content: "Collecte 2"; }';
$header[] = '	td:nth-of-type(5):before { content: "Bijzonderheid"; }';
$header[] = "}";
$header[] = "</style>";

echo showCSSHeader(array('default', 'table_rot'), $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>Collecterooster</h1>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>
