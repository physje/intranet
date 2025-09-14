<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_HeaderFooter.php');
$db = connect_db();

$showLogin = true;

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

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('error', '', 'ongeldige hash (declaratie)');
		$showLogin = true;
	} else {
		$showLogin = false;
		session_start(['cookie_lifetime' => $cookie_lifetime]);
		$_SESSION['useID'] = $id;
		$_SESSION['realID'] = $id;
		toLog('info', '', 'declaratie mbv hash');
	}
}

if($showLogin) {	
	$cfgProgDir = '../auth/';
	$requiredUserGroups = array(1, 38, 51);
	include($cfgProgDir. "secure.php");
}

# 1 = Admin
# 38 = Penningmeester
# 51 = Penningmeester Jeugd en Gezin
$toegestaan = array_merge(getGroupMembers(1), getGroupMembers(38), getGroupMembers(51));

if(in_array($_SESSION['useID'], $toegestaan)) {	
	if(isset($_REQUEST['key'])) {
		
		$sql = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieHash like '". $_REQUEST['key'] ."'";
		$result = mysqli_query($db, $sql);
		$row = mysqli_fetch_array($result);
				
		$JSON = json_decode($row[$EBDeclaratieDeclaratie], true);	
		$indiener = $row[$EBDeclaratieIndiener];
		$data['key']								= $_REQUEST['key'];
		$data['user']								= $indiener;
		$data['eigen']							= $JSON['eigen'];
		$data['iban']								= $JSON['iban'];
		$data['relatie']						= getParam('begunstigde', $JSON['EB_relatie']);
		$data['cluster']						= $JSON['cluster'];
		$data['post']								= $JSON['post'];
		$data['overige']						= $JSON['overig'];
		$data['overig_price']				= $JSON['overig_price'];
		$data['reiskosten']					= $JSON['reiskosten'];
		$data['bijlage']						= $JSON['bijlage'];
		$data['bijlage_naam']				= $JSON['bijlage_naam'];
		$data['opmerking_cluco']		= $JSON['opm_cluco'];
		$data['opmerking_penning']	= $JSON['opm_penning'];		
		
		# Mochten de POST-variabele post bekend zijn (lees posten gewijzigd), ken de nieuwe posten dan toe aan JSON['post']
		if(isset($_POST['post'])) $JSON['post'] = $data['post'] = $_POST['post'];
				
		# Als declaratie niet al is afgehandeld (status > 5) mag je doorgaan
		if(getDeclaratieStatus($row[$EBDeclaratieID], $data['user']) < 5) {
			$veldenCorrect = true;
					
			if(isset($_POST['GBR']) AND $_POST['GBR'] == '') {
				$veldenCorrect = false;
				$meldingGBR = 'Grootboekrekening ontbreekt';
			}
				
			if($JSON['eigen'] == 'Nee' AND isset($_POST['betalingskenmerk']) AND $_POST['betalingskenmerk'] == '') {
				$veldenCorrect = false;
				$meldingKenmerk = 'Betalingskenmerk ontbreekt';
			}
		
			if($JSON['eigen'] == 'Nee' AND strlen($_POST['betalingskenmerk']) > 50) {
				$veldenCorrect = false;
				$meldingKenmerk = 'Betalingskenmerk mag maximaal 50 tekens zijn';
			}
				
			if($JSON['eigen'] == 'Nee' AND isset($_POST['begunstigde']) AND $_POST['begunstigde'] == '') {
				$veldenCorrect = false;
				$meldingBegunstigde = 'Begunstigde ontbreekt';
			}
					
			if($JSON['eigen'] == 'Nee' AND isset($_POST['begunstigde']) AND $_POST['begunstigde'] == 3 AND ($_POST['name_new'] == '' OR $_POST['iban_new'] == '')) {
				$veldenCorrect = false;
				$meldingNewBegunstigde = 'Gegevens van begunstigde zijn onvolledig';
			}
						
			# Als declaratie OK is en alle velden juist zijn ingevoerd	
			# Voeg declaratie toe en verstuur bevestigingmails
			if(isset($_POST['accept']) AND $veldenCorrect) {				
				$UserData = getMemberDetails($indiener);
				$boekstukNummer	= generateBoekstukNr(date('Y'));
								
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
								toLog('error', $indiener, $errorResult);						
							} else {
								toLog('debug', $indiener, 'IBAN van relatie '. $EBCode .' aangepast van '. cleanIBAN($EBIBAN) .' naar '. cleanIBAN($JSON['iban']));
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
						
						if($write2EB)	{
							$errorResult = eb_maakNieuweRelatieAan ($naam , $geslacht, $adres, $postcode, $plaats, $mail, $iban, $EBCode, $EB_id);
						
							if($errorResult) {
								toLog('error', $indiener, $errorResult);						
							} else {
								toLog('debug', $indiener, makeName($indiener, 5) .' als relatie toegevoegd in eBoekhouden met als code '. $EBCode);
								mysqli_query($db, "UPDATE $TableUsers SET $UserEBRelatie = $EBCode WHERE $UserID = $indiener");
							}
						}				
					}
					$factuurnummer	= $boekstukNummer.'-declaratie-'.time2str('%d%b-%H.%M', $row[$EBDeclaratieTijd]);
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
							toLog('error', $indiener, $errorResult);						
						} else {
							toLog('debug', $indiener, $naam .' als nieuwe relatie aangemaakt met als code '. $EBCode);
						}
					} else {
						$EBCode = $_POST['begunstigde'];
						//$page[] = "BESTAANDE BEGUNSTIGDE<br>";
						//$page[] = $_POST['begunstigde'] ."<br>";
					}
					
					$factuurnummer	= str_replace(' ', '-', $_POST['betalingskenmerk']);
				}
		
				$JSON['EBCode'] = $EBCode;
				
				$EBData					= eb_getRelatieDataByCode($EBCode);		
				$totaal					= $row[$EBDeclaratieTotaal];
				
				# Als het alleen reiskosten zijn (dus geen overige), neem dat dan als omschrijving
				# Bouw anders de omschrijving op uit de verschillende declaraties
				if($JSON['overig'] == '') {
					$toelichting		= 'reiskostenvergoeding';
				} elseif(isset($JSON['post']) AND $JSON['post'] != '') {
					# Als er een post bekend is, voeg dat dan toe aan de omschrijving
					foreach($JSON['post'] as $index => $post) {
						$regel[] = $JSON['overig'][$index].' [JG'. substr('0'.$post, -2) .']';
					}
					$toelichting		= implode(', ', $regel);					
				} else {
					$toelichting		= implode(', ', $JSON['overig']);
				}
				
				# eBoekhouden heeft een limiet voor 200 tekens voor de toelichting
				# Omdat bij de toelichting altijd de key (van 8 tekens) met haakjes wordt getoond
				# moet de grens op 188 tekens komen te liggen.
				if(strlen($toelichting) > 188) {
					$toelichting = 'declaratie '. time2str('%e %B %Y', $row[$EBDeclaratieTijd]);
				}
							
				if($write2EB)	{					
					$errorResult = eb_verstuurDeclaratie ($EBCode, $boekstukNummer, $factuurnummer, $totaal, $_POST['GBR'], $toelichting.' ('.$_REQUEST['key'].')', $mutatieId);
					if($errorResult) {
						toLog('error', $indiener, $errorResult);
						$page[] = 'Probleem met toevoegen van declaratie ter waarde van '. formatPrice($totaal) .' aan '. $EBData['naam'] .' ('. $EBCode .')<br>';
						$addSucces = false;
					} else {
						toLog('info', $indiener, 'Declaratie ['. $_REQUEST['key'] .'] van '. formatPrice($totaal) .' toegevoegd voor '. $EBData['naam'] .' ('. $EBCode .')');
						$page[] = 'Declaratie van '. formatPrice($totaal) .' ingediend tnv '. $EBData['naam'] .'<br>';
						$addSucces = true;
					}
				} else {
					$page[] = "DECLARATIE<br>";
					$page[] = "EBCode: ". $EBCode .'<br>';
					$page[] = "BoekstukNummer: ". $boekstukNummer .'<br>';
					$page[] = "Factuurnummer: ". $factuurnummer .'<br>';
					$page[] = "Totaal: ". $totaal .'<br>';
					$page[] = "GBR: ". $_POST['GBR'] .'<br>';
					$page[] = "Toelichting: ". $toelichting .'<br>';		
					$addSucces = false;
				}						
				
				# Als de declaratie succesvol is ingeschreven in eBoekhouden kunnen er mails verstuurd worden
				if($addSucces) {
					$cluster = $JSON['cluster'];
							
					$MailFinAdmin = array();
					$MailFinAdmin[] = makeName($indiener, 5) .' heeft een declaratie ingediend.<br>';
					$MailFinAdmin[] = "<br>";
					$MailFinAdmin[] = "Het betreft een declaratie ter waarde van ". formatPrice($totaal)." voor cluster ". $clusters[$cluster] ." tnv ". $EBData['naam'] .' ('. $EBCode .')';
							
					$param_finAdmin['to'][]					= array($EBDeclaratieAddress);
					$param_finAdmin['subject'] 			= "Declaratie ". makeName($indiener, 5) ." voor cluster ". $clusters[$cluster];
								
					foreach($JSON['bijlage'] as $key => $bestand) {
						$param_finAdmin['attachment'][$key]['file'] = $bestand;
						$param_finAdmin['attachment'][$key]['name'] = $boekstukNummer.'_'.$JSON['bijlage_naam'][$key];
					}
					
					$param_finAdmin['message'] 			= implode("\n", $MailFinAdmin);
					
					if(!$sendMail)	$param_finAdmin['testen'] = 1;				
								
					if(!sendMail_new($param_finAdmin)) {
						toLog('error', $indiener, "Problemen met versturen van mail naar financiële administratie");
						$page[] = "Er zijn problemen met het versturen van mail naar de financiële administratie";
					} else {
						toLog('debug', $indiener, "Declaratie-notificatie naar financiële administratie");
						setDeclaratieStatus(5, $row[$EBDeclaratieID], $data['user']);
						setDeclaratieActionDate($_REQUEST['key']);
					}
								
					$MailIndiener = array();
					$MailIndiener[] = "Beste ". makeName($indiener, 1) .",<br>";
					$MailIndiener[] = "<br>";
					$MailIndiener[] = "Onderstaande declaratie van ".time2str('%e %B', $row[$EBDeclaratieTijd]) ." is goedgekeurd en zal worden uitbetaald.<br>";
					$MailIndiener[] = '<table border=0>';
					$MailIndiener[] = "<tr>";
					$MailIndiener[] = "		<td colspan='6' height=50><hr></td>";
					$MailIndiener[] = "</tr>";			
					$MailIndiener = array_merge($MailIndiener, showDeclaratieDetails($data));			
					$MailIndiener[] = "</table>";
					
					$param_indiener['to'][]			= array($data['user']);
					$param_indiener['subject']	= 'Uitbetaling declaratie';
					$param_indiener['message'] 	= implode("\n", $MailIndiener);
					
					if($data['cluster'] == 2) {
						$param_indiener['from']			= $penningmeesterJGAddress;
						$param_indiener['fromName']	= $penningmeesterJGNaam;
					} else {
						$param_indiener['from']			= $declaratieReplyAddress;
						$param_indiener['fromName']	= $declaratieReplyName;
					}
					
					if(!$sendMail)	$param_indiener['testen'] = 1;
					
					if(!sendMail_new($param_indiener)) {
						toLog('error', $indiener, "Problemen met versturen declaratie-goedkeuring [". $_REQUEST['key'] ."] door penningmeester");
						$page[] = "Er zijn problemen met het versturen van de goedkeuringsmail.<br>\n";
					} else {
						toLog('info', $indiener, "Declaratie-goedkeuring [". $_REQUEST['key'] ."] door penningmeester");
						$page[] = "Er is een mail met goedkeuring verstuurd naar ". makeName($indiener, 5) ."<br>\n";
					}
				}
				
				# JSON-string terug in database			
				$JSONtoDatabase = encode_clean_JSON($JSON);
				$sql = "UPDATE $TableEBDeclaratie SET $EBDeclaratieDeclaratie = '". $JSONtoDatabase ."' WHERE $EBDeclaratieID like ". $row[$EBDeclaratieID];
				mysqli_query($db, $sql);		
				
				$page[] = "<br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";
			#
			# Als declaratie niet helemaal OK is, stuur hem dan terug naar de CluCo/indiener
			} elseif(isset($_POST['reject'])) {
				if(isset($_REQUEST['send_reject'])) {
					$terugleggen_bij = $_POST['terugleggen_bij'];
					$ClucoAddress = getMailAdres($terugleggen_bij);
					$ClucoName		= makeName($terugleggen_bij, 5);	
					
					$mail[] = "Beste ". makeName($terugleggen_bij, 1) .",<br>";
					$mail[] = "<br>";
					$mail[] = "Onderstaande declaratie is door de penningmeester teruggelegd bij jou.<br>";
					$mail[] = "Als reden daarvoor heeft de penningmeester de volgende reden opgegeven :";
					$mail[] = '<table border=0>';
					$mail[] = "<tr>";
					$mail[] = "		<td colspan='6'>&nbsp;</td>";
					$mail[] = "</tr>";
					$mail[] = "<tr>";
					$mail[] = "		<td>&nbsp;</td>";
					$mail[] = "		<td colspan='5'><i>". $_POST['toelichting'] ."</i></td>";
					$mail[] = "</tr>";
					$mail[] = "<tr>";
					$mail[] = "		<td colspan='6' height=50><hr></td>";
					$mail[] = "</tr>";			
					$mail = array_merge($mail, showDeclaratieDetails($data));			
					$mail[] = "</table>";
					
					//$parameter['to'][]		= array($_POST['cluco']);				
					$parameter['subject']		= 'Terugleggen declaratie';
					$parameter['message'] 	= implode("\n", $mail);
					
					if($data['cluster'] == 2) {
						$parameter['to'][]			= array($terugleggen_bij);
						$parameter['from']			= $penningmeesterJGAddress;
						$parameter['fromName']	= $penningmeesterJGNaam;
					} else {
						$parameter['to'][]			= array($ClucoAddress, $ClucoName);
						$parameter['from']			= $declaratieReplyAddress;
						$parameter['fromName']	= $declaratieReplyName;
					}
					
					if(!$sendMail)	$parameter['testen'] = 1;
					
					if(!sendMail_new($parameter)) {
						toLog('error', $data['user'], "Problemen met versturen teruglegging [". $_REQUEST['key'] ."]");
						$page[] = "Er zijn problemen met het versturen van de mail.";
					} else {
						toLog('info', $data['user'], "Declaratie-afwijzing [". $_REQUEST['key'] ."] naar gemeentelid");
						$page[] = "Er is een mail met toelichting  verstuurd naar ". makeName($cluco, 5);
						setDeclaratieStatus(3, $row[$EBDeclaratieID], $data['user']);
						setDeclaratieActionDate($_REQUEST['key']);
					}
							
					# JSON-string terug in database
					$JSON['toelichting_penning'] = $_POST['toelichting'];
					$JSONtoDatabase = encode_clean_JSON($JSON);
					
					$sql = "UPDATE $TableEBDeclaratie SET $EBDeclaratieDeclaratie = '". $JSONtoDatabase ."' WHERE $EBDeclaratieID like ". $row[$EBDeclaratieID];
					mysqli_query($db, $sql);		
				
					$page[] = "<br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";			
				} else {
					$cluster = $data['cluster'];
					if(isset($clusterCoordinatoren[$cluster])) {
						$cluco = $clusterCoordinatoren[$cluster];
					}
					
					if($cluster == 2) {
						$terugleggen_bij = $indiener;
					} else {
						$terugleggen_bij = $cluco;
					}
					
					$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
					$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";
					$page[] = "<input type='hidden' name='terugleggen_bij' value='$terugleggen_bij'>";
					$page[] = "<input type='hidden' name='reject' value='1'>";
					$page[] = '<table border=0>';
					$page[] = "<tr>";
					$page[] = "		<td align='left'>Geef hieronder een korte toelichting aan ". makeName($terugleggen_bij, 1) ." waarom deze declaratie wordt teruggegeven.<br>Deze toelichting zal integraal worden opgenomen in de mail.</td>";
					$page[] = "</tr>";	
					$page[] = "<tr>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "</tr>";	
					$page[] = "<tr>";
					$page[] = "		<td align='center'><textarea name='toelichting' cols=75 rows=10></textarea></td>";
					$page[] = "</tr>";
					$page[] = "<tr>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "</tr>";
					$page[] = "<tr>";
					$page[] = "		<td align='center'><input type='submit' name='send_reject' value='Verstuur'></td>";
					$page[] = "</tr>";	
					$page[] = "</table>";
					$page[] = "</form>";			
				}
			#
			# Als declaratie niet helemaal OK is, kan hij ook verwijderd worden (zonder feedback)
			} elseif(isset($_POST['dump'])) {
				setDeclaratieStatus(7, $row[$EBDeclaratieID], $data['user']);
				setDeclaratieActionDate($_REQUEST['key']);
				
				toLog('info', $data['user'], "Declaratie afgekeurd [". $_REQUEST['key'] ."]");
				
				$page[] = "Declaratie is gemarkeerd als verwijderd. Neem contact op met de webmaster mocht dit onjuist zijn.<br>";
				$page[] = "<br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";			
			#
			# Declaratie kan an-sich goed zijn, maar niet als declaratie maar als exploitatie
			# Dan doorsturen naar financiele administratie om op die manier af te handelen
			} elseif(isset($_POST['reroute'])) {
				if(isset($_REQUEST['send_reroute'])) {
					
					$parameter['to'][]			= array($FinAdminAddress);
					$parameter['subject']		= 'Handmatig verwerken';
					$parameter['message'] 	= $_POST['toelichting'];
					$parameter['from']			= $declaratieReplyAddress;
					$parameter['fromName']	= $declaratieReplyName;
									
					foreach($JSON['bijlage'] as $key => $bestand) {
						$parameter['attachment'][$key]['file'] = $bestand;
						$parameter['attachment'][$key]['name'] = $JSON['bijlage_naam'][$key];
					}
					
					if(!$sendMail)	$parameter['testen'] = 1;
									
					if(!sendMail_new($parameter)) {
						toLog('error', $indiener, "Problemen met declaratie [". $_REQUEST['key'] ."] kenmerken als niet exploitatie");
						$page[] = "Er zijn problemen met het doorsturen naar de financiele administratie voor afhandeling.<br>";
					} else {
						toLog('info', $indiener, "Declaratie [". $_REQUEST['key'] ."] betreft geen exploitatie");
						$page[] = "Declaratie is doorgestuurd naar de financiele administratie voor verdere afhandeling als zijnde geen declaratie. Neem contact op met de webmaster mocht dit onjuist zijn.<br>";
						
						setDeclaratieStatus(8, $row[$EBDeclaratieID], $data['user']);
						setDeclaratieActionDate($_REQUEST['key']);				
					}
									
					$page[] = "<br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";				
				} else {				
					$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
					$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";				
					$page[] = "<input type='hidden' name='reroute' value='1'>";
					$page[] = '<table border=0>';
					$page[] = "<tr>";
					$page[] = "		<td align='left'>Hieronder kan een korte toelichting voor de financiele administratie gegeven worden.<br>Deze toelichting zal samen met de bijlages worden opgenomen als tekst in de mail.</td>";
					$page[] = "</tr>";	
					$page[] = "<tr>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "</tr>";	
					$page[] = "<tr>";
					$page[] = "		<td align='center'><textarea name='toelichting' cols=75 rows=10>". $data['opmerking_cluco'] ."</textarea></td>";
					$page[] = "</tr>";
					$page[] = "<tr>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "</tr>";
					$page[] = "<tr>";
					$page[] = "		<td align='center'><input type='submit' name='send_reroute' value='Verstuur'></td>";
					$page[] = "</tr>";	
					$page[] = "</table>";
					$page[] = "</form>";			
				}			
			} elseif(isset($_POST['change_post'])) {
				$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
				$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";
				$page[] = "<input type='hidden' name='user' value='". $data['user'] ."'>";
				$page[] = "<input type='hidden' name='GBR' value='". $_REQUEST['GBR'] ."'>";		
				$page[] = "<table border=0 width='100%'>";
								
				foreach($data['overige'] as $key => $string) {
					if($string != '' OR $first) {
						$page[] = "	<tr>";
						$page[] = "		<td>$string (". formatPrice(100*$data['overig_price'][$key]) .")</td>";
						$page[] = "		<td><select name='post[$key]'>";
						
						$page[] = "	<option value='0'></option>";
				
						foreach($declJGKop as $id => $kop) {
							$page[] = "	<optgroup label='$kop'>";
							
							foreach($declJGPost[$id] as $post_nr => $titel) {
								$page[] = "	<option value='$post_nr'". ($data['post'][$key] == $post_nr ? ' selected' : '') .">$titel</option>";
							}
							
							$page[] = "	</optgroup>";
						}
						$page[] = "</select></td>";
						$page[] = "	</tr>";
					}
				}
				
				$page[] = "<tr>";
				$page[] = "		<td colspan='2'>&nbsp;</td>";
				$page[] = "</tr>";
				$page[] = "<tr>";
				$page[] = "	<td><input type='submit' name='prev' value='Terug naar declaratie'></td>";
				$page[] = "	<td><input type='submit' name='accept' value='Invoeren in e-boekhouden.nl'></td>";
				#$page[] = "	<td colspan='2'><input type='submit' name='accept' value='Invoeren in e-boekhouden.nl'></td>";
				$page[] = "</tr>";		
				$page[] = "</table>";
				$page[] = "</form>";
				
			#
			# Als geen van dan alles bekend is, toen dan het overzicht van de declaratie
			} else {
				$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
				$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";
				$page[] = "<input type='hidden' name='user' value='". $data['user'] ."'>";
				if(isset($_POST['post'])) {
					foreach($_POST['post'] as $key => $waarde) {
						$page[] = "<input type='hidden' name='post[$key]' value='$waarde'>";
					}					
				}
				$page[] = "<table border=0 width='100%'>";
							
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
				$page[] = "		<td colspan='6'>";
				
				if($data['cluster'] == 2) {
					$page[] = "		<table width='100%'>";
					$page[] = "		<tr>";			
					$page[] = "			<td><input type='submit' name='change_post' value='Wijzig posten'></td>";
					#$page[] = "			<td align='center'><input type='submit' name='reroute' value='Betreft geen declaratie'></td>";
					$page[] = "			<td align='center'><input type='submit' name='dump' value='Verwijderen'></td>";
					$page[] = "			<td align='right'><input type='submit' name='accept' value='Invoeren in e-boekhouden.nl'></td>";			
					$page[] = "		</tr>";
					$page[] = "		</table>";
				} else {
					$page[] = "		<table width='100%'>";
					$page[] = "		<tr>";			
					$page[] = "			<td><input type='submit' name='reject' value='Terug naar clustercoordinator'></td>";
					$page[] = "			<td align='center'><input type='submit' name='reroute' value='Betreft geen declaratie'></td>";
					$page[] = "			<td align='center'><input type='submit' name='dump' value='Verwijderen'></td>";
					$page[] = "			<td align='right'><input type='submit' name='accept' value='Invoeren in e-boekhouden.nl'></td>";			
					$page[] = "		</tr>";
					$page[] = "		</table>";
				}
				
				$page[] = "</td>";
				$page[] = "</tr>";			
				$page[] = "</table>";
				$page[] = "</form>";		
			}
		# Als declaratie al is afgehandeld foutmelding tonen
		} else {
			$page[] = "Declaratie staat al gemarkeerd als afgehandeld. Neem contact op met de webmaster mocht dit onjuist zijn.<br>";
			$page[] = "<br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";		
		}
	} else {
		if(in_array($_SESSION['useID'], getGroupMembers(51))) {
			$sql = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieCluster = 2 AND $EBDeclaratieStatus = 4";
		} else {
			$sql = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieStatus = 4";
		}
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
} else {
	$page[] = "U bent geen penningsmeester";
}
# Pagina tonen
$pageTitle = 'Declaratie';
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $page).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>
