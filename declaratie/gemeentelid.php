<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
$db = connect_db();

$kmPrijs = 19; #in centen

$minUserLevel = 1;
//$requiredUserGroups = array(1, 38);
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

# Mocht er een van en naar adres bekend zijn, reken dam de reiskosten uit
if(isset($_POST['reis_van']) AND $_POST['reis_van'] != '' AND isset($_POST['reis_naar']) AND $_POST['reis_naar'] != '') {
	$kms = determineAddressDistance($_POST['reis_van'], $_POST['reis_naar']);
	$km = array_sum($kms);
	$reiskosten = $km * $kmPrijs;	
	
	$page[] = "<input type='hidden' name='reis_van' value='". trim($_POST['reis_van']) ."'>";
	$page[] = "<input type='hidden' name='reis_naar' value='". trim($_POST['reis_naar']) ."'>";
	$page[] = "<input type='hidden' name='reiskosten' value='$reiskosten'>";
	$page[] = "<input type='hidden' name='km' value='$km'>";
} else {
	$reiskosten = 0;
}

if(isset($_POST['eigen']))				$page[] = "<input type='hidden' name='eigen' value='". trim($_POST['eigen']) ."'>";
if(isset($_POST['EB_relatie']))		$page[] = "<input type='hidden' name='EB_relatie' value='". trim($_POST['EB_relatie']) ."'>";
if(isset($_POST['cluster']))			$page[] = "<input type='hidden' name='cluster' value='". trim($_POST['cluster']) ."'>";
if(isset($_POST['iban']))					$page[] = "<input type='hidden' name='iban' value='". cleanIBAN($_POST['iban']) ."'>";
if(isset($_POST['opm_cluco']))		$page[] = "<input type='hidden' name='opm_cluco' value='". trim($_POST['opm_cluco']) ."'>";

if(isset($_POST['overig']))	{
	foreach($_POST['overig'] as $key => $string) {
		if($string != '') {
			$page[] = "<input type='hidden' name='overig[$key]' value='$string'>";
			$page[] = "<input type='hidden' name='overig_price[$key]' value='". price2RightFormat($_POST['overig_price'][$key]) ."'>";
		}
	}
}

# Bestand uploaden en op de juiste plaats zetten
if(isset($_FILES['bijlage'])) {
	foreach($_FILES['bijlage']['tmp_name'] as $key => $tmpFile) {
		$oldName = trim($tmpFile);
		$newName = 'uploads/'.generateFilename();
		if(move_uploaded_file($oldName, $newName)) {
			$page[] = "<input type='hidden' name='bijlage[]' value='$newName'>";
			$page[] = "<input type='hidden' name='bijlage_naam[]' value='". trim($_FILES['bijlage']['name'][$key]) ."'>";	
		}
	}
} elseif(isset($_POST['bijlage'])) {
	foreach($_POST['bijlage'] as $key => $string) {
		$page[] = "<input type='hidden' name='bijlage[]' value='". trim($string) ."'>";
		$page[] = "<input type='hidden' name='bijlage_naam[]' value='". trim($_POST['bijlage_naam'][$key]) ."'>";
	}
} 

if(isset($_POST['correct'])) {
	$toDatabase = $_POST;
	unset($toDatabase['page']);
	unset($toDatabase['correct']);
	
	$JSONtoDatabase = json_encode($toDatabase);
	
	# Controleer of de laatste 5 minuten niet eenzelfde declaratie is ingediend.
	# Indien wel, dan is dat waarschijnlijk een misverstand	
	$sql_check = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieIndiener = ".$_SESSION['ID'] ." AND $EBDeclaratieCluster = ". $toDatabase['cluster'] ." AND $EBDeclaratieDeclaratie = '". $JSONtoDatabase ."' AND $EBDeclaratieTotaal = ". $toDatabase['totaal'] ." AND $EBDeclaratieTijd > ". (time()-300);
	$result_check = mysqli_query($db, $sql_check);
	
	# Komt niet eerder voor
	if(mysqli_num_rows($result_check) == 0) {		
		$uniqueKey = generateID();
  	
		$sql = "INSERT INTO $TableEBDeclaratie ($EBDeclaratieHash, $EBDeclaratieIndiener, $EBDeclaratieCluster, $EBDeclaratieDeclaratie, $EBDeclaratieTotaal, $EBDeclaratieTijd) VALUES ('$uniqueKey', ". $_SESSION['ID'].", ". $toDatabase['cluster'] .", '". $JSONtoDatabase ."', ". $toDatabase['totaal'] .", ". time() .")";	
		mysqli_query($db, $sql);
		$id = mysqli_insert_id($db);	
		setDeclaratieStatus(3, $id, $_SESSION['ID']);	
  	
		# -------
		# Mail naar de cluco opstellen
		$cluster = $toDatabase['cluster'];
		
		if(isset($clusterCoordinatoren[$cluster]) AND $clusterCoordinatoren[$cluster] <> $_SESSION['ID']) {
			$cluco = $clusterCoordinatoren[$cluster];
		} else {
			$cluco = 0;
		}	
		
		# Als $cluco = 0, betekent dat dat er geen cluco is
		# in dat geval naar de penningmeester mailen	
		if(isset($cluco) AND $cluco == 0) {
			$ClucoAddress	= $declaratieReplyAddress;
			$ClucoName		= $declaratieReplyName;
			$cluco				= 109401;
		} else {
			$ClucoAddress = getMailAdres($cluco);
			$ClucoName		= makeName($cluco, 5);		
		}
			
		$ClucoData		= getMemberDetails($cluco);
		$onderwerpen = array();
		
		if(isset($toDatabase['overig'])) {
			$onderwerpen = array_merge($onderwerpen, $toDatabase['overig']);
		}
		
		if(isset($toDatabase['reiskosten'])) {
			$onderwerpen = array_merge($onderwerpen, array('reiskosten'));
		}
		
		$mailCluco = array();
		$mailCluco[] = "Beste ". makeName($cluco, 1).",<br>";
		$mailCluco[] = "<br>";
		$mailCluco[] = makeName($_SESSION['ID'], 5) .' heeft een declaratie ingediend.<br>';
		$mailCluco[] = "<br>";
		$mailCluco[] = "Het betreft een declaratie van <i>". makeOpsomming($onderwerpen, '</i>, <i>', '</i> en <i>') ."</i> ter waarde van ". formatPrice($toDatabase['totaal'])."<br>";
		$mailCluco[] = "<br>";
		
		if($_POST['opm_cluco'] != '') {		
			$mailCluco[] = "Als toelichting is ingevoerd:<br>";
			$mailCluco[] = '<i>'. $_POST['opm_cluco'] .'</i><br>';
			$mailCluco[] = "<br>";
		}
		
		$mailCluco[] = "<a href='". $ScriptURL ."declaratie/cluco.php?key=$uniqueKey&hash=". $ClucoData['hash_long'] ."&accept'>Goedkeuren</a><br>";
		$mailCluco[] = "<a href='". $ScriptURL ."declaratie/cluco.php?key=$uniqueKey&reject'>Afkeuren</a> (inloggen vereist)<br>";
		$mailCluco[] = "<br>";
		$mailCluco[] = "Details zijn zichtbaar in de bijlage of <a href='". $ScriptURL ."declaratie/cluco.php?key=$uniqueKey'>online</a> (inloggen vereist)<br>";
  	
		$param_cluco['to'][]					= array($ClucoAddress, $ClucoName);
		$param_cluco['subject'] 			= "Declaratie ". makeName($_SESSION['ID'], 5) ." voor cluster ". $clusters[$cluster];	
		$param_cluco['message'] 			= implode("\n", $mailCluco);
		
		foreach($toDatabase['bijlage'] as $key => $bestand) {
			$param_cluco['attachment'][$key]['file'] = $bestand;
			$param_cluco['attachment'][$key]['name'] = $toDatabase['bijlage_naam'][$key];
		}
						
		if(!sendMail_new($param_cluco)) {
			toLog('error', $_SESSION['ID'], '', "Problemen met invoeren van declaratie [$uniqueKey] en voorleggen aan cluco (". makeName($cluco, 5).")");
			$page[] = "Er zijn problemen met het versturen van de notificatie-mail naar de clustercoordinator.";
		} else {
			toLog('info', $_SESSION['ID'], '', "Declaratie [$uniqueKey] ingevoerd en doorgestuurd naar cluco (". makeName($cluco, 5).")");
			$page[] = "De declaratie is ter goedkeuring voorgelegd aan ". makeName($cluco, 5) ." als clustercoordinator";
		}
		
		# Alles verwijderen nadat de declaratie is ingeschoten en de mail de deur uit is
		unset($_POST);		
	} else {
		toLog('info', $_SESSION['ID'], '', "Mogelijk dubbele declaratie, geblokkeerd.");
		$page[] = "U heeft recent al een dergelijke declaratie ingediend. Opnieuw indienen is helaas niet mogelijk.";
	}
	$page[] = "<br>";
	$page[] = "<br>";
	$page[] = "Wilt u nog een declaratie indienen, klik dan <a href='". $_SERVER['PHP_SELF']."'>hier</a>. Mocht dit uw laatste declaratie zijn, klik dan <a href='". $ScriptURL ."'>hier</a>";	
} elseif(isset($_POST['page']) AND $_POST['page'] > 0) {
	if($_POST['eigen'] == 'Ja') {
		$thuisAdres = $gebruikersData['straat'].' '.$gebruikersData['huisnummer'].', '.ucwords(strtolower($gebruikersData['plaats']));
		
		if($gebruikersData['eb_code'] > 0) {
			eb_getRelatieIbanByCode($gebruikersData['eb_code'], $EBIBAN);
		} else {
			$EBIBAN = '';
		}
		
		$reis_van 		= getParam('reis_van', $thuisAdres);
		$reis_naar		= getParam('reis_naar');
		$reiskosten		= getParam('reiskosten', $reiskosten);
		$iban 				= cleanIBAN(getParam('iban', $EBIBAN));
	}
	
	if($_POST['eigen'] == 'Nee') {	
		$relatie			= getParam('EB_relatie', 3);	
	}
	
	$meldingCluster = $meldingIBAN = $meldingDeclaratie = $meldingBestand = $meldingNegatief = '';
	
	$bijlage			= getParam('bijlage', array());
	$opm_cluco		= getParam('opm_cluco');
	$cluster			= getParam('cluster');
	$overige 			= getParam('overig', array());
	$overig_price	= getParam('overig_price', array());
			
	# Check op correct ingevulde velden	
	if(isset($_POST['screen_2'])) {
		$checkFields = true;
		
		# Is er wel een cluster ingevuld	
		if($cluster == '') {
			$checkFields = false;
			$meldingCluster = 'Vul cluster in';
		}
		
		# Is er wel een IBAN ingevuld	
		if($_POST['eigen'] == 'Ja' AND $iban == '') {
			$checkFields = false;
			$meldingIBAN = 'Vul IBAN in';
		}
		
		# Is de IBAN wel geldig
		if($_POST['eigen'] == 'Ja' AND $iban != '' AND !validateIBAN($iban)) {
			$checkFields = false;
			$meldingIBAN = 'IBAN niet geldig';
		}
		
		
		# Is er wel een relatie ingevuld	
		if($_POST['eigen'] == 'Nee' AND $relatie == '') {
			$checkFields = false;
			$meldingRelatie = 'Selecteer ';
		}
		
		# Is er wel iets te declareren ?
		if(((isset($reis_van) AND $reis_van == '') OR (isset($reis_naar) AND $reis_naar == '')) AND count($overige) < 2 AND $overige[0] == '') {
			$checkFields = false;
			$meldingDeclaratie = 'Vul declaratie in (reiskosten en/of overige kosten)';
		}
		
		# Bewijs
		if(count($overige) > 0 AND isset($_FILES['bijlage']['tmp_name']) AND strlen($_FILES['bijlage']['tmp_name'][0]) == 0) {
			$checkFields = false;
			$meldingBestand = 'Voeg bijlage bij';
		}
				
		
		# Positieve bedragen
		if(count($overig_price) > 0) {			
			foreach($overig_price as $key => $waarde) {				
				if($overige[$key] != '' AND $waarde <= 0) {
					$checkFields = false;
					$meldingNegatief = 'Bedragen kunnen alleen positief zijn';
				}
			}
		}
		
		# Check van bestanden
		if(isset($_FILES['bijlage'])) {
			
			# Bestandsgrootte	
			foreach($_FILES['bijlage']['size'] as $fileSize) {
				if($fileSize > (1100*1024)) {
					$checkFields = false;
					$meldingBestand = 'Bestand te groot. Maximaal 1 MB';
				}
			}
			
			# Aantal bestanden
			if(count($_FILES['bijlage']['size']) > 2) {
				$checkFields = false;
				$meldingBestand = 'Maximaal 2 bestanden';
			}			
			
			# Alleen PDF's
			foreach($_FILES['bijlage']['name'] as $bestandsnaam) {
				$path_parts = pathinfo($bestandsnaam);
				if($path_parts['extension'] != 'pdf') {
					$checkFields = false;
					$meldingBestand = 'Alleen PDF toegestaan';
				}
			}
		}		
	} else {
		$checkFields = false;
	}
	
	
	if($checkFields) {
		$input['user']						= $_SESSION['ID'];
		$input['eigen']						= $_POST['eigen'];
		$input['iban']						= $iban;
		$input['relatie']					= $relatie;
		$input['cluster']					= $cluster;
		$input['overige']					= $overige;
		$input['overig_price']		= $overig_price;
		$input['reiskosten']			= $reiskosten;
		$input['opmerking_cluco']	= $opm_cluco;
		
		$totaal = calculateTotals($overig_price) + $reiskosten;
				
		$page[] = "<input type='hidden' name='page' value='3'>";
		$page[] = "<input type='hidden' name='totaal' value='$totaal'>";
		
		$page[] = "<table border=0>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>U staat op het punt de volgende declaratie in te dienen:</td>";
		$page[] = "</tr>";
				
		$page = array_merge($page, showDeclaratieDetails($input));
		
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'><input type='submit' name='incorrect' value='Wijzigen'></td>";
		$page[] = "		<td colspan='2'>&nbsp;</td>";
		$page[] = "		<td colspan='2'><input type='submit' name='correct' value='Indienen'></td>";
		$page[] = "</tr>";	
		$page[] = "</table>";		
	} else {
		# Scherm 1
		$first = true;
		$totaal = 0;
		
		$page[] = "<input type='hidden' name='page' value='2'>";
		$page[] = "<table border=0>";
		
		if($_POST['eigen'] == 'Nee') {
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>Naar welk bedrijf of kerkelijke instellingen?</td>";	
			$page[] = "	<td valign='top'><select name='EB_relatie'>";
			$page[] = "	<option value=''>Selecteer bedrijf/instelling</option>";
			
			$relaties = eb_getRelaties();
	
			foreach($relaties as $relatieData) {
				$page[] = "	<option value='". $relatieData['code'] ."'". ($relatie == $relatieData['code'] ? ' selected' : '') .">". substr($relatieData['naam'], 0, 35) ."</option>";
			}
			
			$page[] = "	</select></td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td colspan='4'>&nbsp;</td>";
			$page[] = "</tr>";	
		}
		
		$page[] = "<tr>";
		$page[] = "	<td valign='top' colspan='2'>Voor welk cluster/onderdeel?</td>";	
		$page[] = "	<td valign='top'><select name='cluster'>";
		$page[] = "	<option value=''>Maak een keuze</option>";
		
		foreach($clusters as $id => $naam) {
			//$page[] = "	<option value='$id'". ($id == $cluster ? ' selected' : '').">Cluster $naam</option>";
			$page[] = "	<option value='$id'". ($id == $cluster ? ' selected' : '').($id != 5 ? ' disabled' : '').">Cluster $naam</option>";
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
		
		if($_POST['eigen'] == 'Ja') {
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
		}		

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
				$page[] = "		<td colspan='1'>&euro;&nbsp;<input type='text' name='overig_price[$key]' value='". (isset($_POST['overig_price'][$key]) ? price2RightFormat($_POST['overig_price'][$key]) : '') ."' size='4'></td>";
				$page[] = "	</tr>";
			}
						
			# 1 lege regel is voldoende
			if($string == '' AND $first)	$first = false;
		}
		
		if(isset($_POST['overig_price'])) {
			$totaal = $totaal + calculateTotals($_POST['overig_price']);
		}
		
		if($meldingNegatief != '') {
			$page[] = "<tr>";
			$page[] = "	<td colspan='4' class='melding'>$meldingNegatief</td>";
			$page[] = "</tr>";
		}
		
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
		
		if(
			(!isset($_POST['bijlage']) AND !isset($_FILES['bijlage']))
			OR 
			(isset($_FILES['bijlage']) AND isset($meldingBestand) AND $meldingBestand != '')
			OR
			(isset($_POST['reset_files']))
		) {
			$page[] = "<tr>";
			$page[] = "	<td colspan='3'><input type='file' name='bijlage[]' accept='application/pdf' multiple><br><small>max. 2 files en 1MB/stuk</small></td>";
			$page[] = "	<td>&nbsp;</td>";
			$page[] = "</tr>";
			
			if($meldingBestand != '') {
				$page[] = "<tr>";
				$page[] = "	<td colspan='4' class='melding'>$meldingBestand</td>";
				$page[] = "</tr>";
			}
		} else {
			if(isset($_POST['bijlage_naam'])) {
				$bestanden = $_POST['bijlage_naam'];
			} else {
				$bestanden = $_FILES['bijlage']['name'];
			}
				
			$page[] = "<tr>";
			$page[] = "	<td colspan='3'>". implode('<br>', $bestanden) ."</td>";
			$page[] = "	<td><input type='submit' name='reset_files' value='Verwijder bijlages'></td>";
			$page[] = "</tr>";
		}	
		
		$page[] = "<tr>";
		$page[] = "	<td colspan='4'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "	<td colspan='4'><b>Eventueel korte toelichting voor de clustercoordinator</b><br>Deze toelichting zal <u>niet</u> opgenomen worden in de definitieve declaratie.</td>";
		$page[] = "</tr>";		
		$page[] = "<tr>";
		$page[] = "	<td colspan='4'><textarea name='opm_cluco' cols=75 rows=5>". $_POST['opm_cluco'] ."</textarea></td>";
		$page[] = "</tr>";		
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
	$page[] = "	<td colspan='2'><i>Welkom op het Online declaratiesysteem van de Koningskerk te Deventer.<br><br>Heeft u zelf onkosten gemaakt voor de kerk dan kunt u uw declaratie eenvoudig indienen via dit systeem. Uw declaratie gaat aan de hand van de workflow naar de clusterco&ouml;rdinator, penningmeester en het financieel systeem van de Koningskerk. U wordt over de voortgang van uw declaratie via deze workflow op de hoogte gehouden.<br><br>Declaraties worden aan u zelf uitbetaald, vanwege (voorgeschoten) gemaakte kosten.<br><br>Rekeningen worden echter rechtstreeks uitbetaald richting bedrijven of instellingen. Bij rekeningen moeten betaalgegevens waaronder bankrekeningnummer in de bijlage zijn opgenomen.</i></td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td colspan='2'>&nbsp;</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td colspan='2'>Moet de rekening aan u zelf worden uitbetaald?</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td colspan='2'>&nbsp;</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td align='left'><input type='submit' name='eigen' value='Ja'></td>";
	//$page[] = "	<td>&nbsp;</td>";
	$page[] = "	<td align='right'><input type='submit' name='eigen' value='Nee'></td>";
	$page[] = "</tr>";
	$page[] = "</table>";		
}

$page[] = "</form>";

# Pagina tonen
$pageTitle = 'Declaratie';
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '	<td valign="top">'. showBlock(implode("\n", $page), 100). '</td>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;

