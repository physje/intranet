<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Logging.php');
include_once('../Classes/KKDMailer.php');

$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 22, 52);
include($cfgProgDir. "secure.php");
$bericht = array();

# Als er op een knop gedrukt is, het rooster wegschrijven
if(isset($_POST['save']) || isset($_POST['maanden'])) {	
	foreach($_POST['collecte'] as $dienstID => $collectes) {
		$dienst = new Kerkdienst($dienstID);
		$oldCollecte_1 = $dienst->collecte_1;
		$oldCollecte_2 = $dienst->collecte_2;
		$dienst->collecte_1 = $collectes[1];
		$dienst->collecte_2 = $collectes[2];
				
		if($dienst->save()) {
			if($oldCollecte_1 != $dienst->collecte_1) {
				$dagdeel = formatDagdeel($dienst->start);				
				if($oldCollecte_1 != '' && $dienst->collecte_1 != '' ) {
					$bericht[] = '1ste collecte van de '. $dagdeel.' van '. date('d-m-y', $dienst->start).' is gewijzigd van <i>'. $oldCollecte_1 .'</i> naar <i>'. $dienst->collecte_1 .'</i>';
				} elseif($dienst->collecte_1 == '') {
					$bericht[] = '<i>'. $oldCollecte_1 .'</i> is als 1ste collecte van de '. $dagdeel.' van '. date('d-m-y', $dienst->start).' verwijderd.';
				} else {
					$bericht[] = '<i>'. $dienst->collecte_1 .'</i> is als 1ste collecte van de '. $dagdeel.' van '. date('d-m-y', $dienst->start).' toegevoegd.';
				}
			}

			if($oldCollecte_2 != $dienst->collecte_2) {
				$dagdeel = formatDagdeel($dienst->start);				
				if($oldCollecte_2 != '' && $dienst->collecte_2 != '' ) {
					$bericht[] = '2de collecte van de '. $dagdeel.' van '. date('d-m-y', $dienst->start).' is gewijzigd van <i>'. $oldCollecte_2 .'</i> naar <i>'. $dienst->collecte_2 .'</i>';
				} elseif($dienst->collecte_2 == '') {
					$bericht[] = '<i>'. $oldCollecte_2 .'</i> is als 2de collecte van de '. $dagdeel.' van '. date('d-m-y', $dienst->start).' verwijderd.';
				} else {
					$bericht[] = '<i>'. $dienst->collecte_2 .'</i> is als 2de collecte van de '. $dagdeel.' van '. date('d-m-y', $dienst->start).' toegevoegd.';
				}
			}
		} else {
			toLog('Collectes van '. date('d-m-y', $dienst->start) .' konden niet worden opgeslagen', 'error');
		}
	}
	
	if(count($bericht) > 0){
		$KKD = new KKDMailer();
		$KKD->addAddress('scipiobeheer@koningskerkdeventer.nl', 'Scipio beheer');
		$KKD->Subject	= count($bericht).' '.(count($bericht) > 1 ? 'gewijzigde collectedoelen' : 'gewijzigd collectedoel');
		$KKD->Body		= implode('<br>', $bericht);
		$KKD->testen = true;
		//TODO: testen uitzetten
				
		if($KKD->sendMail()) {
			toLog('Mail gestuurd naar Scipio-beheer voor gewijzigde collectes', 'debug');			
		} else {
			toLog('Kon geen mail sturen naar Scipio-beheer voor gewijzigde collectes', 'error');
		}		
	}	
	toLog('Collectes bijgewerkt');
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
$diensten = Kerkdienst::getDiensten(mktime(0,0,0), mktime(date("H"),date("i"),date("s"),(date("n")+(3*$blokken))));

$text = array();
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

foreach($diensten as $dienstID) {
	$dienst = new Kerkdienst($dienstID);	
	
	$text[] = "<tr>";
	$text[] = "	<td align='right'>". time2str("D j M", $dienst->start) ."</td>";
	$text[] = "	<td>". date('H:i', $dienst->start) ."</td>";	
	$text[] = "	<td><input type='text' name='collecte[$dienstID][1]' value='". addslashes($dienst->collecte_1) ."'></td>";
	$text[] = "	<td><input type='text' name='collecte[$dienstID][2]' value='". addslashes($dienst->collecte_2) ."'></td>";		
	$text[] = "	<td>". ($dienst->opmerking != '' ? $dienst->opmerking : '&nbsp;') ."</td>";
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
