<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
$db = connect_db();

$showLogin = true;

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('error', '', '', 'ongeldige hash (declaratie)');
		$showLogin = true;
	} else {
		$showLogin = false;
		$_SESSION['ID'] = $id;
		toLog('info', $id, '', 'declaratie mbv hash');
	}
}

if($showLogin) {	
	$cfgProgDir = '../auth/';
	$requiredUserGroups = array(1, 38);
	include($cfgProgDir. "secure.php");
}

if(isset($_REQUEST['key'])) {	
	$sql = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieHash like '". $_REQUEST['key'] ."'";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
			
	$JSON = json_decode($row[$EBDeclaratieDeclaratie], true);	
	$indiener = $row[$EBDeclaratieIndiener];
	$data['user']								= $indiener;
	$data['eigen']							= $JSON['eigen'];
	$data['iban']								= $JSON['iban'];
	$data['relatie']						= getParam('begunstigde', $JSON['EB_relatie']);
	$data['cluster']						= $JSON['cluster'];
	$data['overige']						= $JSON['overig'];
	$data['overig_price']				= $JSON['overig_price'];
	$data['reiskosten']					= $JSON['reiskosten'];
	$data['bijlage']						= $JSON['bijlage'];
	$data['bijlage_naam']				= $JSON['bijlage_naam'];
	$data['opmerking_cluco']		= $JSON['opm_cluco'];
	$data['opmerking_penning']	= $JSON['opm_penning'];
	
	$veldenCorrect = true;

	if(isset($_POST['GBR']) AND $_POST['GBR'] == '') {
		$veldenCorrect = false;
		$meldingGBR = 'Grootboekrekening ontbreekt';
	}
		
	if($JSON['eigen'] == 'Nee' AND isset($_POST['betalingskenmerk']) AND $_POST['betalingskenmerk'] == '') {
		$veldenCorrect = false;
		$meldingKenmerk = 'Betalingskenmerk ontbreekt';
	}
		
	if($JSON['eigen'] == 'Nee' AND isset($_POST['begunstigde']) AND $_POST['begunstigde'] == '') {
		$veldenCorrect = false;
		$meldingBegunstigde = 'Begunstigde ontbreekt';
	}
			
	if($JSON['eigen'] == 'Nee' AND isset($_POST['begunstigde']) AND $_POST['begunstigde'] == 3 AND ($_POST['name_new'] == '' OR $_POST['iban_new'] == '')) {
		$veldenCorrect = false;
		$meldingNewBegunstigde = 'Gegevens van begunstigde zijn onvolledig';
	}
		
	if(isset($_POST['accept']) AND $veldenCorrect) {
		$JSON['GBR'] = $_POST['GBR'];
		
		$UserData = getMemberDetails($indiener);
						
		# EIGEN = JA
		if($JSON['eigen'] == 'Ja') {		
			if(is_numeric($UserData['eb_code']) AND $UserData['eb_code'] > 0) {
				# Al bekend bij eBoekhouden
				$EBCode = $UserData['eb_code'];
				$errorResult = eb_getRelatieIbanByCode($EBCode, $EBIBAN);
											
				//$page[] = "Gebruiker is al bekend in e-boekhouden: ". $EBCode .'<br>';
				//$page[] = "Heeft daar IBAN: ". $EBIBAN .'<br>';
				
				# Klopt IBAN-nummer nog wat bij eBoekhouden bekend is
				if(cleanIBAN($EBIBAN) != cleanIBAN($JSON['iban'])) {
					# Update eBoekhouden
					$data['iban'] = $JSON['iban'];
					$errorResult = eb_updateRelatieByCode($EBCode, $data);
					
					//$page[] = "In de declaratie is als IBAN ingevuld: ". $JSON['iban'] .'<br>';
					
					if($errorResult) {
						toLog('error', $_SESSION['ID'], $indiener, $errorResult);						
					} else {
						toLog('debug', $_SESSION['ID'], $indiener, 'IBAN van relatie '. $EBCode .' aangepast van '. cleanIBAN($EBIBAN) .' naar '. cleanIBAN($JSON['iban']));
					}					
				}		
			
			} else {
				# Niet bekend bij eBoekhouden				
				$naam			= makeName($indiener, 15);
				$geslacht	= strtolower($UserData['geslacht']);
				$adres		= $UserData['straat'].' '.$UserData['huisnummer'].$UserData['huisletter'].($UserData['toevoeging'] != '' ? '-'.$UserData['toevoeging'] : '');
				$postcode	= str_replace(' ', '', $UserData['PC']);
				$plaats		= ucfirst(strtolower($UserData['plaats']));
				$mail			= $UserData['mail'];
				$iban			= $JSON['iban'];
				
				//$page[] = "NIEUWE RELATIE<br>";
				//$page[] = "Naam: ". $naam .'<br>';
				//$page[] = "Geslacht: ". $geslacht .'<br>';
				//$page[] = "Adres: ". $adres .'<br>';
				//$page[] = "Postcode: ". $postcode .'<br>';
				//$page[] = "Plaats: ". $plaats .'<br>';
				//$page[] = "Mail: ". $mail .'<br>';
				//$page[] = "IBAN: ". $iban .'<br>';
								
				$errorResult = eb_maakNieuweRelatieAan ($naam , $geslacht, $adres, $postcode, $plaats, $mail, $iban, $EBCode, $EB_id);
				
				if($errorResult) {
					toLog('error', $_SESSION['ID'], $indiener, $errorResult);						
				} else {
					toLog('debug', $_SESSION['ID'], $indiener, makeName($indiener, 5) .' als relatie toegevoegd in eBoekhouden met als code '. $EBCode);
					mysqli_query($db, "UPDATE $TableUsers SET $UserEBRelatie = $EBCode WHERE $UserID = $indiener");
				}				
			}
			$factuurnummer	= 'declaratie-'.time2str('%e%b_%H.%M', $row[$EBDeclaratieTijd]);
		}		
		
		# EIGEN = NEE
		if($JSON['eigen'] == 'Nee') {
			if($_POST['begunstigde'] == 3) {
				$naam			= $_POST['name_new'];
				$geslacht	= 'm';
				$adres		= $_POST['adres_new'];
				$postcode	= str_replace(' ', '', $_POST['PC_new']);
				$plaats		= ucfirst(strtolower($_POST['plaats_new']));
				$mail			= '';
				$iban			= cleanIBAN($_POST['iban_new']);
				
				//$page[] = "NIEUWE BEGUNSTIGDE<br>";
				//$page[] = "Naam: ". $naam .'<br>';
				//$page[] = "Geslacht: ". $geslacht .'<br>';
				//$page[] = "Adres: ". $adres .'<br>';
				//$page[] = "Postcode: ". $postcode .'<br>';
				//$page[] = "Plaats: ". $plaats .'<br>';
				//$page[] = "Mail: ". $mail .'<br>';
				//$page[] = "IBAN: ". $iban .'<br>';				
				
				$errorResult = eb_maakNieuweRelatieAan ($naam , $geslacht, $adres, $postcode, $plaats, $mail, $iban, $EBCode, $EB_id);
				
				if($errorResult) {
					toLog('error', $_SESSION['ID'], $indiener, $errorResult);						
				} else {
					toLog('debug', $_SESSION['ID'], $indiener, $naam .' als nieuwe relatie aangemaakt met als code '. $EBCode);
				}
			} else {
				$EBCode = $_POST['begunstigde'];
				//$page[] = "BESTAANDE BEGUNSTIGDE<br>";
				//$page[] = $_POST['begunstigde'] ."<br>";
			}
			
			$factuurnummer	= $_POST['betalingskenmerk'];
		}

		$JSON['EBCode'] = $EBCode;
		
		$EBData					= eb_getRelatieDataByCode($EBCode);
		$boekstukNummer	= generateBoekstukNr(date('Y'));
		$totaal					= $row[$EBDeclaratieTotaal];
		
		if($JSON['overig'] == '') {				
			$toelichting		= 'reiskostenvergoeding';
		} else {
			$toelichting		= implode(', ', $JSON['overig']);
		}		
				
		$errorResult = eb_verstuurDeclaratie ($EBCode, $boekstukNummer, '[remove] '.$factuurnummer, $totaal, $_POST['GBR'], $toelichting, $mutatieId);
		if($errorResult) {
			toLog('error', $_SESSION['ID'], $indiener, $errorResult);
			$page[] = 'Probleem met toevoegen van declaratie ter waarde van '. formatPrice($totaal) .' aan '. $EBData['naam'] .' ('. $EBCode .')<br>';
		} else {
			toLog('info', $_SESSION['ID'], $indiener, 'Declaratie ['. $_REQUEST['key'] .'] van '. formatPrice($totaal) .' toegevoegd voor '. $EBData['naam'] .' ('. $EBCode .')');
			$page[] = 'Declaratie van '. formatPrice($totaal) .' ingediend tnv '. $EBData['naam'] .'<br>';
		}
						
		/*	
		$page[] = "DECLARATIE<br>";
		$page[] = "EBCode: ". $EBCode .'<br>';
		$page[] = "BoekstukNummer: ". $boekstukNummer .'<br>';
		$page[] = "Factuurnummer: ". $factuurnummer .'<br>';
		$page[] = "Totaal: ". $totaal .'<br>';
		$page[] = "GBR: ". $_POST['GBR'] .'<br>';
		$page[] = "Toelichting: ". $toelichting .'<br>';		
		*/		
		
		$cluster = $JSON['cluster'];
				
		$MailFinAdmin = array();
		$MailFinAdmin[] = makeName($indiener, 5) .' heeft een declaratie ingediend.<br>';
		$MailFinAdmin[] = "<br>";
		$MailFinAdmin[] = "Het betreft een declaratie ter waarde van ". formatPrice($totaal)." voor cluster ". $clusters[$cluster] ." tnv ". $EBData['naam'] .' ('. $EBCode .')';
				
		$param_finAdmin['to'][]					= array($EBDeclaratieAddress);
		$param_finAdmin['subject'] 			= "Declaratie ". makeName($_SESSION['ID'], 5) ." voor cluster ". $clusters[$cluster];
		//$param_finAdmin['attachment'][]	= array('file' => $JSON['bijlage'], 'name' => $boekstukNummer.'_'.$JSON['bijlage_naam']);
		
		foreach($JSON['bijlage'] as $key => $bestand) {
			$param_finAdmin['attachment'][$key]['file'] = $bestand;
			$param_finAdmin['attachment'][$key]['name'] = $boekstukNummer.'_'.$JSON['bijlage_naam'][$key];
		}
		
		$param_finAdmin['message'] 			= implode("\n", $MailFinAdmin);
					
		if(!sendMail_new($param_finAdmin)) {
			toLog('error', $_SESSION['ID'], $indiener, "Problemen met versturen van mail naar financiële administratie");
			$page[] = "Er zijn problemen met het versturen van mail naar de financiële administratie";
		} else {
			toLog('debug', $_SESSION['ID'], $indiener, "Declaratie-notificatie naar financiële administratie");
			setDeclaratieStatus(5, $row[$EBDeclaratieID], $data['user']);
		}
		
		
		# JSON-string terug in database
		$JSONtoDatabase = json_encode($JSON);
		$sql = "UPDATE $TableEBDeclaratie SET $EBDeclaratieDeclaratie = '". $JSONtoDatabase ."' WHERE $EBDeclaratieID like ". $row[$EBDeclaratieID];
		mysqli_query($db, $sql);		
		
		$page[] = "<br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";
	} elseif(isset($_POST['reject'])) {
		setDeclaratieStatus(3, $row[$EBDeclaratieID], $data['user']);
		
		# JSON-string terug in database
		$JSONtoDatabase = json_encode($JSON);
		$sql = "UPDATE $TableEBDeclaratie SET $EBDeclaratieDeclaratie = '". $JSONtoDatabase ."' WHERE $EBDeclaratieID like ". $row[$EBDeclaratieID];
		mysqli_query($db, $sql);		
		
		$page[] = "<br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";		
	} else {
		$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
		$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";
		$page[] = "<input type='hidden' name='user' value='". $data['user'] ."'>";
		$page[] = '<table border=0>';
					
		$page = array_merge($page, showDeclaratieDetails($data));
				
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'><hr></td>";
		$page[] = "</tr>";	
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>Vul hieronder de ontbrekende gegevens in :</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Grootboekrekening</td>";	
		$page[] = "		<td colspan='4'><select name='GBR'>";
		$page[] = "		<option value=''>Kies Grootboekrekening</option>";
		
		if(isset($_POST['GBR'])) {
			$presetGBR = $_POST['GBR'];	
		} else {			
			$presetGBR = 0;			
			switch ($data['cluster']) {
				case 1: # Gemeenteopbouw
					$presetGBR = 43855;
					break;
				case 2: # Jeugd & Gezin
					$presetGBR = 43865;
					break;
				case 3: # Eredienst
					$presetGBR = 43845;
					break;
				case 4: # Missionaire Activiteiten
					$presetGBR = 43895;
					break;
				case 5: # Organisatie & Beheer
					$presetGBR = 43875;
					break;
			}
		}
			
		foreach($cfgGBR as $code => $naam) {
			$page[] = "		<option value='$code'". ($code == $presetGBR ? ' selected' : '') .">$naam</option>";
		}
		
		$page[] = "		</select></td>";
		$page[] = "</tr>";
		
		if(isset($meldingGBR)) {
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>&nbsp;</td>";
			$page[] = "	<td valign='top' colspan='4' class='melding'>$meldingGBR</td>";
			$page[] = "</tr>";
		}
		
		if($data['eigen'] == 'Nee') {		
			$page[] = "<tr>";
			$page[] = "		<td colspan='6'>&nbsp;</td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>Betalingskenmerk</td>";	
			$page[] = "	<td valign='top' colspan='4'><input type='text' name='betalingskenmerk' value='". $_POST['betalingskenmerk'] ."' size='40'></td>";
			$page[] = "</tr>";
			if(isset($meldingKenmerk)) {
				$page[] = "<tr>";
				$page[] = "	<td valign='top' colspan='2'>&nbsp;</td>";
				$page[] = "	<td valign='top' colspan='4' class='melding'>$meldingKenmerk</td>";
				$page[] = "</tr>";
			}
			$page[] = "<tr>";
			$page[] = "		<td colspan='6'>&nbsp;</td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>Bedrijf / kerkelijke instellingen?</td>";	
			$page[] = "	<td valign='top' colspan='4'><select name='begunstigde'>";
			$page[] = "	<option value=''>Selecteer bedrijf/instelling</option>";

			$relaties = eb_getRelaties();
	
			foreach($relaties as $relatieData) {
				$page[] = "	<option value='". $relatieData['code'] ."'". ($data['relatie'] == $relatieData['code'] ? ' selected' : '') .">". $relatieData['naam'] ."</option>";
			}
			
			$page[] = "	</select></td>";
			$page[] = "</tr>";			
			if(isset($meldingBegunstigde)) {
				$page[] = "<tr>";
				$page[] = "	<td valign='top' colspan='2'>&nbsp;</td>";
				$page[] = "	<td valign='top' colspan='4' class='melding'>$meldingBegunstigde</td>";
				$page[] = "</tr>";
			}				
			$page[] = "<tr>";
			$page[] = "		<td colspan='6'><b>Let wel</b>: om een nieuwe begunstigde toe te voegen dient bij '<i>Bedrijf / kerkelijke instellingen</i>' 'diversen' geselecteerd te worden.</td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>Naam</td>";	
			$page[] = "	<td valign='top' colspan='4'><input type='text' name='name_new' size='40'></td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>Adres</td>";	
			$page[] = "	<td valign='top' colspan='4'><input type='text' name='adres_new' size='40'></td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>Postcode</td>";	
			$page[] = "	<td valign='top' colspan='4'><input type='text' name='PC_new' size='40'></td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>Plaats</td>";	
			$page[] = "	<td valign='top' colspan='4'><input type='text' name='plaats_new' size='40'></td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>IBAN</td>";	
			$page[] = "	<td valign='top' colspan='4'><input type='text' name='iban_new' size='40'></td>";
			$page[] = "</tr>";
			if(isset($meldingNewBegunstigde)) {
				$page[] = "<tr>";
				$page[] = "	<td valign='top' colspan='2'>&nbsp;</td>";
				$page[] = "	<td valign='top' colspan='4' class='melding'>$meldingNewBegunstigde</td>";
				$page[] = "</tr>";
			}
		}
		
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";	
		$page[] = "		<td colspan='2'><input type='submit' name='reject' value='Terug naar clustercoordinator'></td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td colspan='3' align='right'><input type='submit' name='accept' value='Invoeren in e-boekhouden.nl'></td>";
		//$page[] = "		<td colspan='6' align='right'><input type='submit' name='accept' value='Invoeren in e-boekhouden.nl'></td>";
		$page[] = "</tr>";	
		$page[] = "</table>";
		$page[] = "</form>";		
	}
} else {
	$sql = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieStatus = 4";
	$result = mysqli_query($db, $sql);
		
	if($row = mysqli_fetch_array($result)) {
		$page[] = "<table>";
		$page[] = "<tr>";
		$page[] = "<td colspan='2'><b>Tijdstip</b></td>";
		$page[] = "<td colspan='2'><b>Cluster</b></td>";				
		$page[] = "<td colspan='2'><b>Indiener</b></td>";			
		$page[] = "<td><b>Bedrag</b></td>";
		$page[] = "</tr>";
			
		do {
			$page[] = "<tr>";
			$page[] = "<td>". time2str('%e %b %H:%M', $row[$EBDeclaratieTijd]) ."</td>";
			$page[] = "<td>&nbsp;</td>";			
			$page[] = "<td>". $clusters[$row[$EBDeclaratieCluster]] ."</td>";
			$page[] = "<td>&nbsp;</td>";
			$page[] = "<td><a href='../profiel.php?id=". $row[$EBDeclaratieIndiener] ."'>". makeName($row[$EBDeclaratieIndiener], 5) ."</a></td>";
			$page[] = "<td>&nbsp;</td>";
				
			$page[] = "<td><a href='?key=". $row[$EBDeclaratieHash] ."'>". formatPrice($row[$EBDeclaratieTotaal]) ."</a></td>";
			$page[] = "</tr>";
		} while($row = mysqli_fetch_array($result));
		$page[] = "</table>";
	} else {
		$page[] = "Geen openstaande declaratie's";
	}	
}

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
