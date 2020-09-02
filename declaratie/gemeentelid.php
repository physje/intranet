<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');
include_once('genereerDeclaratiePdf.php');
$db = connect_db();

$kmPrijs = 19; #in centen

$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if($productieOmgeving) {
	$write2EB = true;
	$sendMail = true;
	$sendTestMail = false;
} else {
	$write2EB = false;
	$sendMail = false;
	$sendTestMail = false;
	
	//echo 'Test-omgeving';
}

$gebruikersData = getMemberDetails($_SESSION['ID']);
$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."' enctype='multipart/form-data'>";

if(isset($_POST['eigen_nee']))	$page[] = "<input type='hidden' name='eigen_nee' value='". trim($_POST['eigen_nee']) ."'>";
if(isset($_POST['eigen_ja']))		$page[] = "<input type='hidden' name='eigen_ja' value='". trim($_POST['eigen_ja']) ."'>";
if(isset($_POST['cluster']))		$page[] = "<input type='hidden' name='cluster' value='". trim($_POST['cluster']) ."'>";
if(isset($_POST['iban']))				$page[] = "<input type='hidden' name='iban' value='". trim($_POST['iban']) ."'>";
if(isset($_POST['reis_van']))		$page[] = "<input type='hidden' name='reis_van' value='". trim($_POST['reis_van']) ."'>";
if(isset($_POST['reis_naar']))	$page[] = "<input type='hidden' name='reis_naar' value='". trim($_POST['reis_naar']) ."'>";
if(isset($_POST['reiskosten']))	$page[] = "<input type='hidden' name='reiskosten' value='". trim($_POST['reiskosten']) ."'>";
if(isset($_POST['km']))					$page[] = "<input type='hidden' name='km' value='". trim($_POST['km']) ."'>";
if(isset($_FILES['bijlage']))		$page[] = "<input type='hidden' name='bijlage' value='". trim($_FILES['bijlage']['tmp_name']) ."'>\n<input type='hidden' name='bijlage_naam' value='". trim($_FILES['bijlage']['name']) ."'>";
if(isset($_POST['overig']))	{
	foreach($_POST['overig'] as $key => $string) {
		if($string != '') {
			$page[] = "<input type='hidden' name='overig[$key]' value='$string'>";
			$page[] = "<input type='hidden' name='overig_price[$key]' value='". $_POST['overig_price'][$key] ."'>";
		}
	}
}
	

if(isset($_POST['eigen_nee'])) {
	$page[] = "<input type='hidden' name='page' value='2'>";
	$page[] = "<table border=1>";
	$page[] = "<tr>";
	$page[] = "	<td align='left'><input type='submit' name='eigen_ja' value='Ja'></td>";
	$page[] = "	<td align='right'><input type='submit' name='eigen_nee' value='Nee'></td>";
	$page[] = "</tr>";
	$page[] = "</table>";		
} elseif(isset($_POST['eigen_ja'])) {	
	$thuisAdres = $gebruikersData['straat'].' '.$gebruikersData['huisnummer'].', '.ucwords(strtolower($gebruikersData['plaats']));
	$meldingCluster = $meldingIBAN = $meldingDeclaratie = $meldingBestand = '';
	
	eb_getRelatieIbanByCode($gebruikersData['eb_code'], $EBIBAN);
					
	$cluster			= getParam('cluster');
	$reis_van 		= getParam('reis_van', $thuisAdres);
	$reis_naar		= getParam('reis_naar');
	$reiskosten		= getParam('reiskosten');
	$overige 			= getParam('overig', array());
	$overig_price	= getParam('overig_price', array());
	$iban 				= getParam('iban', $EBIBAN);
				
	# Check op correct ingevulde velden	
	if(isset($_POST['screen_2'])) {
		$checkFields = true;
		
		# Is er wel een cluster ingevuld	
		if($cluster == '') {
			$checkFields = false;
			$meldingCluster = 'Vul cluster in';
		}
		
		# Is er wel een cluster ingevuld	
		if($iban == '') {
			$checkFields = false;
			$meldingIBAN = 'Vul IBAN in';
		}
		
		# Is er wel iets te declareren ?
		if(($reis_van == '' OR $reis_naar == '') AND count($overige) < 2 AND $overige[0] == '') {
			$checkFields = false;
			$meldingDeclaratie = 'Vul declaratie in (reiskosten en/of overige kosten)';
		}
		
		# Bewijs
		if(count($overige) > 0 AND $_FILES['bijlage']['tmp_name'] == '') {
			$checkFields = false;
			$meldingBestand = 'Voeg bijlage bij';
		}
		
		# Bestandsgrootte	
		if($_FILES['bijlage']['size'] > (500*1024)) {
			$checkFields = false;
			$meldingBestand = 'Bestand te groot. Maximaal 500 kB';
		}		
	} else {
		$checkFields = false;
	}
	
	
	if($checkFields) {
		$page[] = "<input type='hidden' name='page' value='3'>";
		$page[] = "<table border=1>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='3'>U staat op het punt de volgende declaratie in te dienen:</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Naam:</td>";
		$page[] = "		<td>". makeName($_SESSION['ID'], 5) ."</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Emailadres:</td>";
		$page[] = "		<td>". getMailAdres($_SESSION['ID']) ."</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Rekeningnummer:</td>";
		$page[] = "		<td>$iban</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Cluster onderdeel:</td>";
		$page[] = "		<td>". $clusters[$cluster] ."</td>";
		$page[] = "</tr>";
		if(count($overige) > 0) {
			$page[] = "<tr>";
			$page[] = "		<td colspan='3'>&nbsp;</td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "		<td colspan='3'><b>Declaraties</b></td>";
			$page[] = "</tr>";
			
			$totaal = calculateTotals($overig_price);
		
			foreach($overige as $key => $value) {
				if($value != "") {
					$page[] = "<tr>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "		<td>$value</td>";
					$page[] = "		<td>". formatPrice($overig_price[$key]*100) ."</td>";
					$page[] = "</tr>";				
				}
			}			
		}
		
		if(isset($reiskosten)) {
			$page[] = "<tr>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td>Reiskosten</td>";
			$page[] = "		<td>". formatPrice($reiskosten) ."</td>";
			$page[] = "</tr>";	
			
			$totaal = $totaal + $reiskosten;
		}
		
		
		$page[] = "<tr>";
		$page[] = "		<td colspan='3'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'><b>Totaal</b></td>";
		$page[] = "		<td><b>". formatPrice($totaal) ."</b></td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='3'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td><input type='submit' name='incorrect' value='Wijzigen'></td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td><input type='submit' name='correct' value='Indienen'></td>";
		$page[] = "</tr>";	
		$page[] = "</table>";		
	} else {
		# Scherm 1
		$first = true;
		$totaal = 0;
		
		$page[] = "<input type='hidden' name='page' value='2'>";
		$page[] = "<table border=1>";
		$page[] = "<tr>";
		$page[] = "	<td valign='top' colspan='2'>Voor welk cluster/onderdeel?</td>";	
		$page[] = "	<td valign='top'><select name='cluster'>";
		$page[] = "	<option value=''>Maak een keuze</option>";
		
		foreach($clusters as $id => $naam) {
			$page[] = "	<option value='$id'". ($id == $cluster ? ' selected' : '').">Cluster $naam</option>";
		}
		
		$page[] = "	</select></td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "</tr>";
		if($meldingCluster != '') {
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>&nbsp;</td>";
			$page[] = "	<td valign='top' colspan='2' class='melding'>$meldingCluster</td>";
			$page[] = "</tr>";
		}
		
		$page[] = "<tr>";
		$page[] = "	<td colspan='4'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "	<td valign='top' colspan='2'>Wat is uw rekeningnummer</td>";
		$page[] = "	<td valign='top'><input type='text' name='iban' value='$iban'></td>";
		$page[] = "	<td>&nbsp;</td>";
		$page[] = "</tr>";
		
		if($meldingIBAN != '') {
			$page[] = "<tr>";
			$page[] = "	<td colspan='2'>&nbsp;</td>";
			$page[] = "	<td  colspan='2' class='melding'>$meldingIBAN</td>";
			$page[] = "</tr>";
		}
		
		$page[] = "<tr>";
		$page[] = "	<td colspan='4'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "	<tr>";
		$page[] = "		<td colspan='4'><b>Reiskostenvergoeding</b></td>";
		$page[] = "	</tr>";
		$page[] = "	<tr>";
		$page[] = "		<td>Van</td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td>Naar</td>";	
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "	</tr>";
		$page[] = "	<tr>";
		$page[] = "		<td><input type='text' name='reis_van' value='$reis_van' size='25'></td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td><input type='text' name='reis_naar' value='$reis_naar' size='25'></td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "	</tr>";
		
		# Als reis_van en reis_naar bekend zijn, kan het aantal kilometers worden uitgerekend
		# en kan het volgende deel van het formulier getoond worden
		if(isset($_POST['reis_van']) AND $_POST['reis_van'] != '' AND isset($_POST['reis_naar']) AND $_POST['reis_naar'] != '') {
			$next = true;
			$kms = determineAddressDistance($_POST['reis_van'], $_POST['reis_naar']);
			$km = array_sum($kms);
			$reiskosten = $km * $kmPrijs;
  	
			$page[] = "	<tr>";
			$page[] = "		<td colspan='3'><small>". round($km, 1) ." km x ". formatPrice($kmPrijs) ."</small></td>";
			$page[] = "		<td align='right'>". formatPrice($reiskosten) ."</td>";
			$page[] = "		<input type='hidden' name='reiskosten' value='$reiskosten'>";
			$page[] = "		<input type='hidden' name='km' value='$km'>";
			$page[] = "	</tr>";
			
			$totaal = $totaal + $reiskosten;
		}
		
		
		$page[] = "<tr>";
		$page[] = "	<td colspan='4'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "	<tr>";
		$page[] = "		<td colspan='4'><b>Overige kosten</b></td>";
		$page[] = "	</tr>";
  	
		# Een extra leeg veld toevoegen
		$overige[] = $overig_price[] = '';
		
		# Laat invoervelden voor overige zaken zien
		foreach($overige as $key => $string) {
			if($string != '' OR $first) {
				$page[] = "	<tr>";
				$page[] = "		<td colspan='3'><input type='text' name='overig[$key]' value='$string' size='57'></td>";			
				$page[] = "		<td colspan='1'>&euro;&nbsp;<input type='text' name='overig_price[$key]' value='". (isset($_POST['overig_price'][$key]) ? str_replace(',', '.', $_POST['overig_price'][$key]) : '') ."' size='4'></td>";
				$page[] = "	</tr>";
			}
						
			# 1 lege regel is voldoende
			if($string == '' AND $first)	$first = false;
		}
		
		$totaal = $totaal + calculateTotals($_POST['overig_price']);
		
		if($totaal > 0) {
			$page[] = "	<tr>";
			$page[] = "		<td colspan='3'>&nbsp;</td>";
			$page[] = "		<td align='right'><b>". formatPrice($totaal) ."</b></td>";
			$page[] = "	</tr>";
		}
		
		if($meldingDeclaratie != '') {
			$page[] = "<tr>";
			$page[] = "	<td colspan='4' class='melding'>$meldingDeclaratie</td>";
			$page[] = "</tr>";
		}		
			
		$page[] = "<tr>";
		$page[] = "	<td colspan='4'>&nbsp;</td>";
		$page[] = "</tr>";		
		$page[] = "<tr>";
		$page[] = "	<td colspan='4'><b>Bijlages / facturen</b></td>";
		$page[] = "</tr>";	
		$page[] = "<tr>";
		$page[] = "	<td colspan='3'><input type='file' name='bijlage' accept='application/pdf'><br><small>max. 500 kB</small></td>";
		$page[] = "	<td>&nbsp;</td>";
		$page[] = "</tr>";
		
		if($meldingBestand != '') {
			$page[] = "<tr>";
			$page[] = "	<td colspan='4' class='melding'>$meldingBestand</td>";
			$page[] = "</tr>";
		}	
		
		$page[] = "<tr>";
		$page[] = "	<td colspan='4'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "	<td colspan='4'>";
		$page[] = "	<table width='100%'>";
		$page[] = "	<tr>";
		$page[] = "		<td width='33%' align='left'><input type='submit' name='screen_0' value='Vorige'></td>";
		$page[] = "		<td width='33%'><input type='submit' name='screen_1' value=\"Voeg 'Overige kosten' toe\"></td>";
		$page[] = "		<td width='33%' align='right'><input type='submit' name='screen_2' value='Volgende'></td>";	
		$page[] = "	</tr>";
		$page[] = "	</table>";
		$page[] = "</td>";
		$page[] = "</tr>";
		$page[] = "</table>";
	}
} else {
	$page[] = "<input type='hidden' name='page' value='1'>";
	$page[] = "<table border=0>";
	$page[] = "<tr>";
	$page[] = "	<td colspan='2'>Moet de rekening aan u zelf worden uitbetaald?</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td colspan='3'>&nbsp;</td>";
	$page[] = "</tr>";		
	$page[] = "<tr>";
	$page[] = "	<td align='left'><input type='submit' name='eigen_ja' value='Ja'></td>";
	//$page[] = "	<td>&nbsp;</td>";
	$page[] = "	<td align='right'><input type='submit' name='eigen_nee' value='Nee'></td>";
	$page[] = "</tr>";
	$page[] = "</table>";		
}

$page[] = "</form>";

# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '	<td valign="top">'. showBlock(implode("\n", $page), 100). '</td>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;

function calculateTotals($array) {
	$totaal = 0;
	
	foreach($array as $waarde) {
		if($waarde > 0) {
			$price = 100*str_replace(',', '.', $waarde);
			$totaal = $totaal + $price;
		}
	}
	
	return $totaal;
}