<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_HeaderFooter.php');
$db = connect_db();

$kmPrijs = 35; #in centen

$minUserLevel = 1;
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
	
	echo '[ Test-omgeving ]';
}

$iban = $opm_cluco = $relatie = '';

$gebruikersData = getMemberDetails($_SESSION['useID']);
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

# Voeg alle bekende variabelen toe als hidden-variabele in het formulier
if(isset($_POST['eigen']))				$page[] = "<input type='hidden' name='eigen' value='". trim($_POST['eigen']) ."'>";
if(isset($_POST['EB_relatie']))		$page[] = "<input type='hidden' name='EB_relatie' value='". trim($_POST['EB_relatie']) ."'>";
if(isset($_POST['cluster']))			$page[] = "<input type='hidden' name='cluster' value='". trim($_POST['cluster']) ."'>";
if(isset($_POST['iban']))					$page[] = "<input type='hidden' name='iban' value='". cleanIBAN($_POST['iban']) ."'>";
if(isset($_POST['opm_cluco']))		$page[] = "<input type='hidden' name='opm_cluco' value='". trim($_POST['opm_cluco']) ."'>";

# Loop ook alle overige-posten door om als hidden-variabele in het formulier op te nemen
if(isset($_POST['overig']))	{
	foreach($_POST['overig'] as $key => $string) {
		if($string != '') {
			$page[] = "<input type='hidden' name='overig[$key]' value='$string'>";
			$page[] = "<input type='hidden' name='overig_price[$key]' value='". price2RightFormat($_POST['overig_price'][$key]) ."'>";
		}
	}
}

# Loop ook alle posten door (alleen bij J&G) om als hidden-variabele in het formulier op te nemen
if(isset($_POST['post'])) {
	foreach($_POST['post'] as $key => $value) {
		$page[] = "<input type='hidden' name='post[$key]' value='$value'>";
	}
}

# Bestand uploaden en op de juiste plaats zetten
if(isset($_FILES['bijlage'])) {
	foreach($_FILES['bijlage']['tmp_name'] as $key => $tmpFile) {
		$fileSize = $_FILES['bijlage']['size'][$key];
		$fileType = $_FILES['bijlage']['type'][$key];
		$oldName = trim($tmpFile);
		$newName = 'uploads/'.generateFilename();		
		
		if(move_uploaded_file($oldName, $newName)) {
			$bestands_naam = $bijlage_naam = '';			
			if($fileType == 'image/jpeg' AND $fileSize > (1024*1024)) {
				# Resize de foto en verwijder de oude
				$newFile = resize_image($newName, 1024, 1024);				
				unlink($newName);
				
				$bestands_naam = $newFile;
				$bijlage_naam = 'resized_'.trim($_FILES['bijlage']['name'][$key]);
			} else {
				$bestands_naam = $newName;
				$bijlage_naam = trim($_FILES['bijlage']['name'][$key]);
			}			
			$page[] = "<input type='hidden' name='bijlage[]' value='$bestands_naam'>";
			$page[] = "<input type='hidden' name='bijlage_naam[]' value='$bijlage_naam'>";	
		}
	}
} elseif(isset($_POST['bijlage'])) {
	foreach($_POST['bijlage'] as $key => $string) {
		$page[] = "<input type='hidden' name='bijlage[]' value='". trim($string) ."'>";
		$page[] = "<input type='hidden' name='bijlage_naam[]' value='". trim($_POST['bijlage_naam'][$key]) ."'>";
	}
}

# Hieronder staan de verschillende schermen van het formulier
if(isset($_POST['correct'])) {
	# Alle POST-variabelen, met uitzondering van de pagina en
	# de knop dat de gegevens correct zijn moeten in de database
	$toDatabase = $_POST;
	unset($toDatabase['page']);
	unset($toDatabase['correct']);
	
	# Alles omzetten in JSON-formaat en daarbij newlines
	# (met name bij opm_cluco) vervangen door een spatie	
	$JSONtoDatabase = encode_clean_JSON($toDatabase);
	
	# Controleer of de laatste 5 minuten niet eenzelfde declaratie is ingediend.
	# Indien wel, dan is dat waarschijnlijk een misverstand	
	$sql_check = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieIndiener = ".$_SESSION['useID'] ." AND $EBDeclaratieCluster = ". $toDatabase['cluster'] ." AND $EBDeclaratieDeclaratie = '". $JSONtoDatabase ."' AND $EBDeclaratieTotaal = ". $toDatabase['totaal'] ." AND $EBDeclaratieTijd > ". (time()-300);
	$result_check = mysqli_query($db, $sql_check);
	
	# Komt niet eerder voor
	if(mysqli_num_rows($result_check) == 0) {		
		$uniqueKey = generateID();
  	
		$sql = "INSERT INTO $TableEBDeclaratie ($EBDeclaratieHash, $EBDeclaratieIndiener, $EBDeclaratieCluster, $EBDeclaratieDeclaratie, $EBDeclaratieTotaal, $EBDeclaratieTijd) VALUES ('$uniqueKey', ". $_SESSION['useID'].", ". $toDatabase['cluster'] .", '". $JSONtoDatabase ."', ". $toDatabase['totaal'] .", ". time() .")";	
		mysqli_query($db, $sql);
		$declaratieID = mysqli_insert_id($db);		
		  	
		# -------
		# Mail naar de cluco opstellen
		$cluster = $toDatabase['cluster'];
		
		if(isset($clusterCoordinatoren[$cluster]) AND $clusterCoordinatoren[$cluster] <> $_SESSION['useID']) {
			$cluco = $clusterCoordinatoren[$cluster];
		} else {
			$cluco = 0;
		}	
		
		# Als $cluco = 0, betekent dat dat er geen cluco is
		# in dat geval naar de penningmeester mailen	
		if(isset($cluco) AND $cluco == 0) {
			$ClucoAddress	= $declaratieReplyAddress;
			$ClucoName		= $declaratieReplyName;
			$cluco				= 984756;			# Penningmeester
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
		$mailCluco[] = makeName($_SESSION['useID'], 5) .' heeft een declaratie ingediend.<br>';
		$mailCluco[] = "<br>";
		$mailCluco[] = "Het betreft een declaratie van <i>". makeOpsomming($onderwerpen, '</i>, <i>', '</i> en <i>') ."</i> ter waarde van ". formatPrice($toDatabase['totaal'])."<br>";
		$mailCluco[] = "<br>";
		
		if($_POST['opm_cluco'] != '') {		
			$mailCluco[] = "Als toelichting is ingevoerd:<br>";
			$mailCluco[] = '<i>'. $_POST['opm_cluco'] .'</i><br>';
			$mailCluco[] = "<br>";
		}
		
		# J&G heeft geen cluco die het goedkeurd maar direct naar de penningmeester
		# Don't ask me why
		if($cluster == 2) {
			$mailCluco[] = "Details en mogelijkheid tot goed- of afkeuren zijn zichtbaar <a href='". $ScriptURL ."declaratie/penningmeester.php?key=$uniqueKey'>online</a> (inloggen vereist)";
			$status = 4;
		} else {			
			$status = 3;			
			$mailCluco[] = "<a href='". $ScriptURL ."declaratie/cluco.php?key=$uniqueKey&hash=". $ClucoData['hash_long'] ."&accept'>Goedkeuren</a><br>";
			$mailCluco[] = "<a href='". $ScriptURL ."declaratie/cluco.php?key=$uniqueKey&reject'>Afkeuren</a> (inloggen vereist)<br>";
			$mailCluco[] = "<br>";
			$mailCluco[] = "Details zijn zichtbaar in de bijlage of <a href='". $ScriptURL ."declaratie/cluco.php?key=$uniqueKey'>online</a> (inloggen vereist)<br>";
		}
  	
		$param_cluco['to'][]					= array($ClucoAddress, $ClucoName);
		$param_cluco['subject'] 			= "Declaratie ". makeName($_SESSION['useID'], 5) ." voor ". $clusters[$cluster];	
		$param_cluco['message'] 			= implode("\n", $mailCluco);
		
		foreach($toDatabase['bijlage'] as $key => $bestand) {
			$param_cluco['attachment'][$key]['file'] = $bestand;
			$param_cluco['attachment'][$key]['name'] = $toDatabase['bijlage_naam'][$key];
		}
		
		if(!$sendMail)	$param_cluco['testen'] = 1;
					
		if(!sendMail_new($param_cluco)) {
			toLog('error', $cluco, "Problemen met invoeren van declaratie [$uniqueKey] en voorleggen aan cluco (". makeName($cluco, 5).")");
			$page[] = "Er zijn problemen met het versturen van de notificatie-mail naar de clustercoordinator.";
		} else {
			toLog('info', $cluco, "Declaratie [$uniqueKey] ingevoerd en doorgestuurd naar cluco (". makeName($cluco, 5).")");
			$page[] = "De declaratie is ter goedkeuring voorgelegd aan ". makeName($cluco, 5) ." als clustercoordinator";
		}
		
		# Stel de declaratie-status in		
		setDeclaratieStatus($status, $declaratieID, $_SESSION['useID']);
		setDeclaratieActionDate($uniqueKey);
		
		# Alles verwijderen nadat de declaratie is ingeschoten en de mail de deur uit is
		unset($_POST);		
	} else {
		toLog('info', '', "Mogelijk dubbele declaratie, geblokkeerd.");
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
	$post					= getParam('post');
			
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
		if( ( (isset($reis_van) AND $reis_van == '') OR (isset($reis_naar) AND $reis_naar == '') OR (!isset($reis_van)) OR (!isset($reis_naar)) ) AND count($overige) < 2 AND $overige[0] == '') {
			$checkFields = false;
			$meldingDeclaratie = 'Vul declaratie in'. ($_POST['eigen'] == 'Nee' ? '' : ' (reiskosten en/of overige kosten)');
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
			$aantal = count($_FILES['bijlage']['size']);
			
			# Aantal bestanden
			if($aantal > 5) {
				$checkFields = false;
				$meldingBestand = 'Maximaal 5 bestanden';
			}	
			
			for($i=0 ; $i < $aantal ; $i++) {
				$fileSize = $_FILES['bijlage']['size'][$i];
				$fileType = $_FILES['bijlage']['type'][$i];
				$bestandsnaam = $_FILES['bijlage']['tmp_name'][$i];
				
				# Plaatjes worden automatisch geschaald bij opslaan
				# Daar hoeft dus geen melding voor te zijn
				if(isset($fileType) AND $fileType != 'jpg' AND $fileType != 'image/jpeg') {					
					if($fileSize > (1100*1024)) {
						$checkFields = false;
						$meldingBestand = 'Bestand te groot. Maximaal 1 MB';
					}
				}
			}
			
		
			
			# Alleen PDF's / JPG's
			foreach($_FILES['bijlage']['name'] as $bestandsnaam) {
				$path_parts = pathinfo($bestandsnaam);
				if(isset($path_parts['extension']) AND $path_parts['extension'] != 'pdf' AND $path_parts['extension'] != 'jpg' AND $path_parts['extension'] != 'jpeg') {
					$checkFields = false;
					$meldingBestand = 'Alleen PDF of JPG toegestaan';					
				}
			}
		}		
	} else {
		$checkFields = false;
	}
		
	if($checkFields) {
		$input['user']						= $_SESSION['useID'];
		$input['eigen']						= $_POST['eigen'];
		$input['iban']						= $iban;
		$input['relatie']					= $relatie;
		$input['cluster']					= $cluster;
		$input['overige']					= $overige;
		$input['overig_price']		= $overig_price;
		$input['reiskosten']			= $reiskosten;
		$input['opmerking_cluco']	= $opm_cluco;
		$input['post']						= $post;
				
		$totaal = calculateTotals($overig_price) + $reiskosten;
		
		# Cluster Jeugd & Gezin moet een tussenscherm hebben
		if($cluster != 2 OR ($_POST['page'] == 4 AND $post > 0)) {			
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
			$page[] = "<input type='hidden' name='page' value='4'>";
			$page[] = "<input type='hidden' name='totaal' value='$totaal'>";
			
			$page[] = "<table border=0>";
			$page[] = "<tr>";
			$page[] = "		<td colspan='2'>Cluster Jeugd & Gezin heeft verschillende posten waar een declaratie op geboekt kan worden. Selecteer hieronder de post die het beste past bij jouw declaratie.</td>";
			$page[] = "</tr>";
			
			# Opties genereren zodat dat zometeen hergebruikt kan worden
			$options[] = "	<option value='0'></option>";
			
			foreach($declJGKop as $id => $kop) {
				$options[] = "	<optgroup label='$kop'>";
				
				foreach($declJGPost[$id] as $post_nr => $titel) {
					$options[] = "	<option value='$post_nr'>$titel</option>";
				}
				
				$options[] = "	</optgroup>";
			}
			
			foreach($overige as $key => $string) {
				if($string != '') {
					$page[] = "	<tr>";
					$page[] = "		<td>$string (". formatPrice(100*$overig_price[$key]) .")</td>";
					$page[] = "		<td><select name='post[$key]'>";
					
					$page = array_merge($page, $options);
					
					$page[] = "</select></td>";
					$page[] = "	</tr>";
				}
			}
			
			$page[] = "<tr>";
			$page[] = "		<td colspan='2'>Weet je niet zeker welke post je moet kiezen? Klik <a href='toelichtingPostenJG.php' target='new'>hier</a> voor een toelichting op de posten</td>";
			$page[] = "</tr>";						
			$page[] = "<tr>";
			$page[] = "		<td colspan='2'>&nbsp;</td>";
			$page[] = "</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td valign='left'><input type='submit' name='screen_0' value='Vorige'></td>";			
			$page[] = "		<td valign='right'><input type='submit' name='screen_2' value='Volgende'></td>";	
			$page[] = "	</tr>";
			$page[] = "</table>";			
		}
	} else {
		# Scherm 1
		$first = true;
		$totaal = 0;
		
		$page[] = "<input type='hidden' name='page' value='2'>";
		$page[] = "<table>";
		
		if($_POST['eigen'] == 'Nee') {
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>Naar welk bedrijf of kerkelijke instellingen?</td>";	
			$page[] = "	<td valign='top' colspan='2'><select name='EB_relatie'>";
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
			$page[] = "	<option value='$id'". ($id == $cluster ? ' selected' : '').">$naam</option>";
			//$page[] = "	<option value='$id'". ($id == $cluster ? ' selected' : '').($id != 5 ? ' disabled' : '').">Cluster $naam</option>";
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
			$page[] = "	<td valign='top'><input type='text' name='iban' value='$iban' placeholder='NL99XXXX0000000000'></td>";
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
			$page[] = "		<td><input type='text' name='reis_van' value='$reis_van' size='25' placeholder='Adres en plaats van vertrekpunt'></td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td><input type='text' name='reis_naar' value='$reis_naar' size='25' placeholder='Adres en plaats van bestemming'></td>";
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
				$page[] = "		<td colspan='3'><input type='text' name='overig[$key]' value='$string' placeholder='Omschrijving van de kosten (bv bosje bloemen, HEMA, uitje Follow-Light oid)'></td>";
				$page[] = "		<td colspan='1'>&euro;<input type='text' name='overig_price[$key]' value='". (isset($_POST['overig_price'][$key]) ? price2RightFormat($_POST['overig_price'][$key]) : '') ."' size='2' placeholder='1,23'></td>";
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
			$page[] = "	<td colspan='3'><input type='file' name='bijlage[]' accept='application/pdf, image/jpeg' multiple><br><small>Alleen PDF of JPG; max. 5 files; max 1 MB/stuk</small></td>";
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
		$page[] = "	<td colspan='4'><textarea name='opm_cluco' cols=75 rows=5>". (isset($_POST['opm_cluco']) ? $_POST['opm_cluco'] : '') ."</textarea></td>";
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

echo showCSSHeader(array('default', 'table_default'));
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $page).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>
