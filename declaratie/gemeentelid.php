<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../Classes/Declaratie.php');
include_once('../Classes/Member.php');
include_once('../Classes/KKDMailer.php');
include_once('../Classes/Logging.php');

$kmPrijs = 35; #in centen

$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if($productieOmgeving) {
	$write2EB = true;
	$sendMail = true;
} else {
	$write2EB = false;
	$sendMail = false;
	
	echo '[ Test-omgeving ]';
}

# Kijk of er een sessie actief is, zo niet start de sessie
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

# Kijk of er een declaratie-object in de sessie staat en laad die dan
if(!isset($_SESSION['declaratie'])) {	
	$declaratie = new Declaratie();
	$declaratie->type = 'gemeentelid';
	$_SESSION['declaratie'] = $declaratie;
}
$declaratie = $_SESSION['declaratie'];

# Mocht het een voorganger-declaratie zijn, maak dan een nieuwe aan
if($declaratie->type != 'gemeentelid') {
	# Verwijder de oude declaratie en start een nieuwe
	unset($_SESSION['declaratie']);
	$declaratie = new Declaratie();
	$declaratie->type = 'gemeentelid';
	$_SESSION['declaratie'] = $declaratie;
}

# Reset de declaratie als daar om gevraagd wordt
if(isset($_REQUEST['reset'])) {
	$declaratie = new Declaratie();
	$declaratie->type = 'gemeentelid';
}

# Stel de gebruiker in als die nog niet bekend is
if($declaratie->gebruiker == 0) {
	$declaratie->gebruiker = $_SESSION['useID'];
}

# Initialiseer variabelen
$iban = $opm_cluco = $relatie = '';

# Maak het Member-object van de ingelogde gebruiker
$gebruiker = new Member($_SESSION['useID']);

# Voeg alle bekende variabelen toe als Declaratie-object properties
if(isset($_POST['reis_van']))		$declaratie->van = urldecode($_POST['reis_van']);
if(isset($_POST['reis_naar']))		$declaratie->naar = urldecode($_POST['reis_naar']);
if(isset($_POST['eigen']))			$declaratie->eigenRekening = ($_POST['eigen'] == 'Ja' ? true : false);	
if(isset($_POST['iban']))			$declaratie->IBAN = cleanIBAN($_POST['iban']);
if(isset($_POST['EB_relatie']))		$declaratie->EB_relatie = intval($_POST['EB_relatie']);
if(isset($_POST['cluster']))		$declaratie->cluster = intval($_POST['cluster']);
if(isset($_POST['opm_cluco']))		$declaratie->opmerkingCluco = trim($_POST['opm_cluco']);

# Overige kosten als array van omschrijving => bedrag
if(isset($_POST['overig'])) {
	$declaratie->overigeKosten = array();
	foreach($_POST['overig'] as $key => $string) {		
		$declaratie->overigeKosten[$string] = 100*floatval(str_replace(',', '.', $_POST['overig_price'][$key]));
	}
}
# Loop ook alle posten door (alleen bij J&G)
if(isset($_POST['post'])) {
	foreach($_POST['post'] as $key => $value) {
		$declaratie->posten[$key] = intval($value);		
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
			
			# Kijk of het een JPG is die groter is dan 1 MB
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

			$declaratie->bijlagen[$bestands_naam] = $bijlage_naam;
		}
	}
}

# Verwijder alle bijlages als op de knop 'Verwijder alle bestanden' is geklikt
if(isset($_POST['reset_files'])) {
	# Verwijder alle eerder geuploade bestanden
	foreach($declaratie->bijlagen as $local => $naam) {
		if(file_exists($local)) {
			unlink($local);
		}
	}
	$declaratie->bijlagen = array();
}

# Mocht er een van en naar adres bekend zijn, reken dam de reiskosten uit
if($declaratie->van != '' && $declaratie->naar != '' ) {
	$kms = determineAddressDistance($declaratie->van, $declaratie->naar);
	$km = array_sum($kms);
	$declaratie->afstand = $km;
	$declaratie->reiskosten = $km * $kmPrijs;
} else {
	$declaratie->afstand = 0;
	$declaratie->reiskosten = 0;
}	

# Pagina opbouwen
$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."' enctype='multipart/form-data'>";
# Hieronder staan de verschillende schermen van het formulier
if(isset($_POST['correct'])) {	
	# Als de gebruiker op het laatste scherm op 'refresh' klikt zou in theorie de declaratie 2x ingediend worden.
	# Na inschieten van de declaratie wordt het object verwijderd, daarom een check of het totaal bedrag groter is dan 0	
	if($declaratie->totaal > 0) {
		$declaratie->hash = generateID();  	

		$cluster = $declaratie->cluster;
		
		if(isset($clusterCoordinatoren[$cluster]) AND $clusterCoordinatoren[$cluster] <> $_SESSION['useID']) {
			$clucoID = $clusterCoordinatoren[$cluster];
		} else {
			$clucoID = 0;
		}	
		
		# Als $cluco = 0, betekent dat dat er geen cluco is
		# in dat geval naar de penningmeester mailen	
		if(isset($clucoID) AND $clucoID == 0) {
			$ClucoAddress	= $declaratieReplyAddress;
			$ClucoName		= $declaratieReplyName;
			$clucoID		= 984756;			# Penningmeester
		}
								
		$onderwerpen = array();		
		if(count($declaratie->overigeKosten) > 0)	$onderwerpen = array_merge($onderwerpen, array_keys($declaratie->overigeKosten));		
		if($declaratie->reiskosten > 0)				$onderwerpen = array_merge($onderwerpen, array('reiskosten'));

		# -------
		# Mail naar de cluco opstellen
		$cluco = new Member($clucoID);
		$mailCluco = array();
		$mailCluco[] = "Beste ". $cluco->getName(1).",<br>";
		$mailCluco[] = "<br>";
		$mailCluco[] = $gebruiker->getName(5) .' heeft een declaratie ingediend.<br>';
		$mailCluco[] = "<br>";
		$mailCluco[] = "Het betreft een declaratie van <i>". makeOpsomming($onderwerpen, '</i>, <i>', '</i> en <i>') ."</i> ter waarde van ". formatPrice($declaratie->totaal)."<br>";
		$mailCluco[] = "<br>";
		
		if($declaratie->opmerkingCluco != '') {		
			$mailCluco[] = "Als toelichting is ingevoerd:<br>";
			$mailCluco[] = '<i>'. $declaratie->opmerkingCluco .'</i><br>';
			$mailCluco[] = "<br>";
		}
		
		# J&G heeft geen cluco die het goedkeurd maar direct naar de penningmeester
		# Don't ask me why
		if($cluster == 2) {
			$mailCluco[] = "Details en mogelijkheid tot goed- of afkeuren zijn zichtbaar <a href='". $ScriptURL ."declaratie/penningmeester.php?key=". $declaratie->hash ."'>online</a> (inloggen vereist)";
			$status = 4;
		} else {			
			$status = 3;			
			$mailCluco[] = "<a href='". $ScriptURL ."declaratie/cluco.php?key=". $declaratie->hash ."&hash=". $cluco->hash_long ."&accept'>Goedkeuren</a><br>";
			$mailCluco[] = "<a href='". $ScriptURL ."declaratie/cluco.php?key=". $declaratie->hash ."&reject'>Afkeuren</a> (inloggen vereist)<br>";
			$mailCluco[] = "<br>";
			$mailCluco[] = "Details zijn zichtbaar in de bijlage of <a href='". $ScriptURL ."declaratie/cluco.php?key=". $declaratie->hash ."'>online</a> (inloggen vereist)<br>";
		}

		$cMail = new KKDMailer();
		$cMail->aan		= $clucoID;
		$cMail->Subject	= "Declaratie ". $gebruiker->getName(5) ." voor ". $clusters[$cluster];
		$cMail->Body	= implode("\n", $mailCluco);  	
		
		foreach($declaratie->bijlagen as $local => $naam) {
			$cMail->addAttachment($local, $naam);
		}
		
		if(!$sendMail)	$cMail->testen = true;
					
		if(!$cMail->sendMail()) {
			toLog("Problemen met invoeren van declaratie [". $declaratie->hash ."] en voorleggen aan cluco (". $cluco->getName(5) .")", 'error', $clucoID);
			$page[] = "Er zijn problemen met het versturen van de notificatie-mail naar de clustercoordinator.";
		} else {
			toLog("Declaratie [". $declaratie->hash ."] ingevoerd en doorgestuurd naar cluco (". $cluco->getName(5) .")", 'info', $clucoID);
			$page[] = "De declaratie is ter goedkeuring voorgelegd aan ". $cluco->getName(5) ." als clustercoordinator";
		}
		
		# Stel de declaratie-status en tijd in en sla object op in de database
		$declaratie->status = $status;
		$declaratie->lastAction = time();
		$declaratie->save();
				
		# Alles verwijderen nadat de declaratie is ingeschoten en de mail de deur uit is
		$declaratie = null;
	} else {
		toLog("Mogelijk dubbele declaratie, geblokkeerd.", '', $gebruiker->id);
		$page[] = "U heeft recent al een dergelijke declaratie ingediend. Opnieuw indienen is helaas niet mogelijk.";
	}
	$page[] = "<br>";
	$page[] = "<br>";
	$page[] = "Wilt u nog een declaratie indienen, klik dan <a href='". $_SERVER['PHP_SELF']."'>hier</a>. Mocht dit uw laatste declaratie zijn, klik dan <a href='". $ScriptURL ."'>hier</a>";	
} elseif(isset($_POST['page']) AND $_POST['page'] > 0) {
	if($declaratie->eigenRekening) {		
		if($declaratie->van == '')									$declaratie->van = $gebruiker->getWoonadres();				
		if($gebruiker->boekhouden > 0 && $declaratie->IBAN == '')	eb_getRelatieIbanByCode($gebruiker->boekhouden, $declaratie->IBAN);
	}
	
	$meldingCluster = $meldingIBAN = $meldingDeclaratie = $meldingBestand = $meldingNegatief = '';
			
	# Check op correct ingevulde velden	
	if(isset($_POST['screen_2'])) {
		$checkFields = true;
		
		# Is er wel een cluster ingevuld	
		if($declaratie->cluster == 0) {
			$checkFields = false;
			$meldingCluster = 'Vul cluster in';
		}
		
		# Is er wel een IBAN ingevuld	
		if($declaratie->eigenRekening && $declaratie->IBAN == '') {
			$checkFields = false;
			$meldingIBAN = 'Vul IBAN in';
		}
		
		# Is de IBAN wel geldig
		if($declaratie->eigenRekening && $declaratie->IBAN != '' && !validateIBAN($declaratie->IBAN)) {
			$checkFields = false;
			$meldingIBAN = 'IBAN niet geldig';
		}
		
		
		# Is er wel een relatie ingevuld	
		if(!$declaratie->eigenRekening && $declaratie->EB_relatie == '') {
			$checkFields = false;
			$meldingRelatie = 'Selecteer ';
		}
		
		# Is er wel iets te declareren ?
		if($declaratie->reiskosten == 0 && count($declaratie->overigeKosten) == 0) {
			$checkFields = false;
			$meldingDeclaratie = 'Vul declaratie in'. ($declaratie->eigenRekening ? ' (reiskosten en/of overige kosten)' : '');
		}
				
		# Bewijs
		if(count($declaratie->overigeKosten) > 0 && count($declaratie->bijlagen) == 0) {
			$checkFields = false;
			$meldingBestand = 'Voeg bijlage bij';
		}				
		
		# Positieve bedragen
		if(count($declaratie->overigeKosten) > 0) {			
			foreach($declaratie->overigeKosten as $item => $waarde) {				
				if($item != '' AND $waarde <= 0) {
					$checkFields = false;
					$meldingNegatief = 'Bedragen kunnen alleen positief zijn';
				}
			}
		}
		
		# Check van bestanden
		if(count($declaratie->bijlagen) > 0) {
			$aantal = count($declaratie->bijlagen);
			
			# Aantal bestanden
			if($aantal > 5) {
				$checkFields = false;
				$meldingBestand = 'Maximaal 5 bestanden';
			}	
			
			# Grootte van de bestanden
			foreach($declaratie->bijlagen as $local => $naam) {
				$fileSize = filesize($local);
				$fileType = mime_content_type($local);
				
				# Plaatjes worden automatisch geschaald bij opslaan
				# Daar hoeft dus geen melding voor te zijn
				if(isset($fileType) && $fileType != 'image/jpeg' && $fileType != 'application/pdf') {					
					if($fileSize > (1100*1024)) {
						$checkFields = false;
						$meldingBestand = 'Bestand te groot. Maximaal 1 MB';
					}
				}
			}		
			
			# Alleen PDF's / JPG's
			foreach($declaratie->bijlagen as $naam) {
				$path_parts = pathinfo($naam);
				if(isset($path_parts['extension']) && $path_parts['extension'] != 'pdf' && $path_parts['extension'] != 'jpg' && $path_parts['extension'] != 'jpeg') {
					$checkFields = false;
					$meldingBestand = 'Alleen PDF of JPG toegestaan';					
				}
			}
		}		
	} else {
		$checkFields = false;
	}
		
	if($checkFields) {				
		$declaratie->totaal = calculateTotals($declaratie->overigeKosten) + $declaratie->reiskosten;
		
		# Cluster Jeugd & Gezin moet een tussenscherm hebben
		if($declaratie->cluster != 2 OR ($_POST['page'] == 4 AND $declaratie->posten > 0)) {			
			$page[] = "<input type='hidden' name='page' value='3'>";
			$page[] = "<table border=1>";
			$page[] = "<tr>";
			$page[] = "		<td colspan='6'>U staat op het punt de volgende declaratie in te dienen:</td>";
			$page[] = "</tr>";
					
			$page = array_merge($page, showDeclaratieDetails($declaratie));
			
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

			$key=0;

			foreach($declaratie->overigeKosten as $item => $prijs) {
				if($item != '') {
					$page[] = "	<tr>";
					$page[] = "		<td>$item (". formatPrice($prijs) .")</td>";
					$page[] = "		<td><select name='post[$key]'>";
					
					$page = array_merge($page, $options);
					
					$page[] = "</select></td>";
					$page[] = "	</tr>";
					$key++;
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
		
		if($declaratie->eigenRekening == false) {
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>Naar welk bedrijf of kerkelijke instellingen?</td>";	
			$page[] = "	<td valign='top' colspan='2'><select name='EB_relatie'>";
			$page[] = "	<option value=''>Selecteer bedrijf/instelling</option>";
			
			$relaties = eb_getRelaties();
	
			foreach($relaties as $relatieData) {
				$page[] = "	<option value='". $relatieData['code'] ."'". ($declaratie->EB_relatie == $relatieData['code'] ? ' selected' : '') .">". substr($relatieData['naam'], 0, 35) ."</option>";
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
			$page[] = "	<option value='$id'". ($declaratie->cluster == $id ? ' selected' : '').">$naam</option>";
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
		
		if($declaratie->eigenRekening) {
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>Wat is uw rekeningnummer</td>";
			$page[] = "	<td valign='top'><input type='text' name='iban' value='". $declaratie->IBAN ."' placeholder='NL99XXXX0000000000'></td>";
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
			$page[] = "		<td><input type='text' name='reis_van' value='". $declaratie->van ."' size='25' placeholder='Adres en plaats van vertrekpunt'></td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td><input type='text' name='reis_naar' value='". $declaratie->naar ."' size='25' placeholder='Adres en plaats van bestemming'></td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "	</tr>";
			
			# Als reis_van en reis_naar bekend zijn, kan het aantal kilometers worden uitgerekend
			# en kan het volgende deel van het formulier getoond worden
			if($declaratie->reiskosten > 0 && $declaratie->afstand > 0) {				
				$page[] = "	<tr>";
				$page[] = "		<td colspan='3'><small>". round($declaratie->afstand, 1) ." km x ". formatPrice($kmPrijs) ."</small></td>";
				$page[] = "		<td align='right'>". formatPrice($declaratie->reiskosten) ."</td>";				
				$page[] = "	</tr>";
				
				$totaal = $totaal + $declaratie->reiskosten;
			}
			
			$page[] = "<tr>";
			$page[] = "	<td colspan='4'>&nbsp;</td>";
			$page[] = "</tr>";		
		}		

		$page[] = "	<tr>";
		$page[] = "		<td colspan='4'><b>Overige kosten</b></td>";
		$page[] = "	</tr>";
  	
		# Een extra leeg veld toevoegen
		$key = 0;
		$declaratie->overigeKosten[''] = '';
		
		# Laat invoervelden voor overige zaken zien
		foreach($declaratie->overigeKosten as $item => $prijs) {
			if($item != '' OR $first) {
				$page[] = "	<tr>";				
				$page[] = "		<td colspan='3'><input type='text' name='overig[$key]' value='$item' size='50' placeholder='Omschrijving van de kosten (bv bosje bloemen, HEMA, uitje Follow-Light oid)'></td>";
				$page[] = "		<td>&euro;&nbsp;<input type='text' name='overig_price[$key]' value='". str_replace('.', ',', intval($prijs)/100) ."' size='2' placeholder='1,23'></td>";
				$page[] = "	</tr>";
				$key++;
			}
		}
		
		if(count($declaratie->overigeKosten) > 0) {
			$totaal = $totaal + calculateTotals($declaratie->overigeKosten);
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
			(count($declaratie->bijlagen) == 0)
			||
			(count($declaratie->bijlagen) > 0 && isset($meldingBestand) && $meldingBestand != '')
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
			$page[] = "<tr>";
			$page[] = "	<td colspan='3'>". implode('<br>', $declaratie->bijlagen) ."</td>";
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
		$page[] = "	<td colspan='4'><textarea name='opm_cluco' cols=75 rows=5>". ($declaratie->opmerkingCluco != '' ? $declaratie->opmerkingCluco : '') ."</textarea></td>";
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
	$page[] = "	<td colspan='2'>Moet de rekening aan u zelf worden uitbetaald?<br><small>Kies 'Ja' als u het bedrag hebt voorgeschoten, kies 'Nee' als de factuur rechtstreeks betaald moet worden</small></td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td colspan='2'>&nbsp;</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td align='left'><input type='submit' name='eigen' value='Ja'></td>";
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

# Sla de declaratie-gegevens op in de sessie
$_SESSION['declaratie'] = $declaratie;
?>

