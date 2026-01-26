<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../Classes/Member.php');
include_once('../Classes/Team.php');
include_once('../Classes/Declaratie.php');
include_once('../Classes/KKDMailer.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Boeknummer.php');
include_once('../Classes/Mysql.php');

$showLogin = true;

if($productieOmgeving) {
	$write2EB = true;
	$sendMail = true;	
} else {
	$write2EB = false;
	$sendMail = false;	
	
	echo '[ Test-omgeving ]';
}

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('ongeldige hash (penningsmeester declaratie)', 'error');
		$showLogin = true;
	} else {
		$showLogin = false;
		session_start(['cookie_lifetime' => $cookie_lifetime]);
		$_SESSION['useID'] = $id;
		$_SESSION['realID'] = $id;
		toLog('Penningmeester-declaratie mbv hash');
	}
}

if($showLogin) {
	$cfgProgDir = '../auth/';
	include($cfgProgDir. "secure.php");
}

# Kijk of er een sessie actief is, zo niet start de sessie
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

# Kijk of er een declaratie-object in de sessie staat en laad die dan
if(!isset($_SESSION['declaratie'])) {	
	$declaratie = new Declaratie();
	$_SESSION['declaratie'] = $declaratie;
}
$declaratie = $_SESSION['declaratie'];

# 1 = Admin
# 38 = Penningmeester
# 51 = Penningmeester Jeugd en Gezin
$adminTeam = new Team(1);
$penningmeesterTeam = new Team(38);
$penningJG = new Team(51);

$toegestaan = array_merge($adminTeam->leden, $penningmeesterTeam->leden, $penningJG->leden);

# Haal gegevens van een bestaande declaratie op
# Kan gebruikt worden als een declaratie terug gaat naar het gemeentelid
if(isset($_REQUEST['key'])) {
	$declaratie = new Declaratie($_REQUEST['key']);

# Reset de declaratie als daar om gevraagd wordt
} elseif(isset($_REQUEST['reset'])) {
	$declaratie = new Declaratie();
}

if(in_array($_SESSION['useID'], $toegestaan)) {	
	if($declaratie->hash != '') {
		$onderwerpen = array();		
		$indiener	= new Member($declaratie->gebruiker);
		$penning		= new Member($_SESSION['useID']);
		
		if(count($declaratie->overigeKosten) > 0)	$onderwerpen = array_merge($onderwerpen, array_keys($declaratie->overigeKosten));		
		if($declaratie->reiskosten > 0)				$onderwerpen = array_merge($onderwerpen, array('reiskosten'));
						
		# Mochten de POST-variabele post bekend zijn (lees posten gewijzigd), ken de nieuwe posten dan toe aan JSON['post']
		#if(isset($_POST['post'])) $JSON['post'] = $data['post'] = $_POST['post'];
		if(isset($_POST['GBR']) && $_POST['GBR'] != '')								$declaratie->GBR = trim($_POST['GBR']);
		if(isset($_POST['betalingskenmerk']) && $_POST['betalingskenmerk'] != '')	$declaratie->betalingskenmerk = trim($_POST['betalingskenmerk']);
		if(isset($_POST['begunstigde']) && $_POST['begunstigde'] != '')				$declaratie->begunstigde = trim($_POST['begunstigde']);
		if(isset($_POST['toelichting']))											$declaratie->opmerking = trim($_POST['toelichting']);
				
		# Als declaratie niet al is afgehandeld (status > 5) mag je doorgaan
		if($declaratie->status < 5) {
			$veldenCorrect = true;
					
			if($declaratie->GBR == '') {
				$veldenCorrect = false;
				$meldingGBR = 'Grootboekrekening ontbreekt';
			}
				
			if(!$declaratie->eigenRekening && $declaratie->betalingskenmerk == '') {
				$veldenCorrect = false;
				$meldingKenmerk = 'Betalingskenmerk ontbreekt';
			}
		
			if(!$declaratie->eigenRekening && strlen($declaratie->betalingskenmerk) > 50) {
				$veldenCorrect = false;
				$meldingKenmerk = 'Betalingskenmerk mag maximaal 50 tekens zijn';
			}
				
			if(!$declaratie->eigenRekening && $declaratie->begunstigde == '') {
				$veldenCorrect = false;
				$meldingBegunstigde = 'Begunstigde ontbreekt';
			}
					
			if(!$declaratie->eigenRekening && $declaratie->begunstigde == 3 && ($_POST['name_new'] == '' OR $_POST['iban_new'] == '')) {
				$veldenCorrect = false;
				$meldingNewBegunstigde = 'Gegevens van begunstigde zijn onvolledig';
			}
						
			# Als declaratie OK is en alle velden juist zijn ingevoerd	
			# Voeg declaratie toe en verstuur bevestigingmails
			if(isset($_POST['accept']) AND $veldenCorrect) {								
				$boekstukNummer	= new Boeknummer(date('Y'));
								
				# EIGEN = JA
				if($declaratie->eigenRekening) {					
					# Al bekend bij eBoekhouden
					if($indiener->boekhouden > 0) {												
						$errorResult = eb_getRelatieIbanByCode($indiener->boekhouden, $EBIBAN);
													
						//$page[] = "Gebruiker is al bekend in e-boekhouden: ". $EBCode .'<br>';
						//$page[] = "Heeft daar IBAN: ". $EBIBAN .'<br>';
						
						# Klopt IBAN-nummer nog wat bij eBoekhouden bekend is
						if(cleanIBAN($EBIBAN) != cleanIBAN($declaratie->IBAN)) {							
							$errorResult = eb_updateRelatieByCode($indiener->boekhouden, $data);
							
							//$page[] = "In de declaratie is als IBAN ingevuld: ". $JSON['iban'] .'<br>';
							
							if($errorResult) {
								toLog($errorResult, 'error', $indiener->id);
							} else {
								toLog('IBAN van relatie '. $indiener->boekhouden .' aangepast van '. cleanIBAN($EBIBAN) .' naar '. cleanIBAN($declaratie->IBAN), 'debug', $indiener->id);
							}					
						}		
					
					} else {
						# Niet bekend bij eBoekhouden				
						$naam		= $indiener->getName(15);
						$geslacht	= strtolower($indiener->geslacht);
						$adres		= $indiener->getWoonadres();
						$postcode	= str_replace(' ', '', $indiener->postcode);
						$plaats		= ucfirst(strtolower($indiener->woonplaats));
						$mail		= $indiener->getMail();
						$iban		= $declaratie->IBAN;
						
						if($write2EB) {
							$errorResult = eb_maakNieuweRelatieAan ($naam , $geslacht, $adres, $postcode, $plaats, $mail, $iban, $EBCode, $EB_id);
						
							if($errorResult) {
								toLog($errorResult, 'error', $indiener->id);
							} else {
								toLog($indiener->getName(5) .' als relatie toegevoegd in eBoekhouden met als code '. $EBCode, 'debug', $indiener->id);
								$indiener->boekhouden = $EBCode;
								$indiener->save();
							}
						}				
					}

					# EBCode gebruiken we verderop om de declaratie in te schieten
					# Dit gedeelte van het if-statement is voor als het op de rekening van de indiener gestort moet worden
					# Ken de waarde van $indiener->boekhouden daarom toe aan $EBCode
					$EBCode = $indiener->boekhouden;

					$factuurnummer	= $boekstukNummer->nummer.'-declaratie-'.time2str('dd.MMMYY-HH.mm', $declaratie->tijd);					
				}		
				
				# EIGEN = NEE
				if(!$declaratie->eigenRekening) {
					if($_POST['begunstigde'] == 3) {
						$naam		= $_POST['name_new'];
						$geslacht	= 'm';
						$adres		= $_POST['adres_new'];
						$postcode	= str_replace(' ', '', $_POST['PC_new']);
						$plaats		= ucfirst(strtolower($_POST['plaats_new']));
						$mail		= '';
						$iban		= cleanIBAN($_POST['iban_new']);
						
						if($write2EB) {
							$errorResult = eb_maakNieuweRelatieAan ($naam , $geslacht, $adres, $postcode, $plaats, $mail, $iban, $EBCode, $EB_id);
						
							if($errorResult) {
								toLog($errorResult, 'error', $indiener->id);
							} else {
								toLog($naam .' als nieuwe relatie aangemaakt met als code '. $EBCode, 'debug', $indiener->id);
							}						
						} else {
							$EBCode = 9010;
						}						
					} else {
						$EBCode = $_POST['begunstigde'];
						//$page[] = "BESTAANDE BEGUNSTIGDE<br>";
						//$page[] = $_POST['begunstigde'] ."<br>";
					}

					# EBCode gebruiken we verderop om de declaratie in te schieten
					# Dit gedeelte van het if-statement is voor als het op de rekening van een ander gestort moet worden
					# Ken daarom de ID van de begunstigde toe aan $EBCode

					$factuurnummer	= str_replace(' ', '-', $_POST['betalingskenmerk']);
				}
										
				$EBData	= eb_getRelatieDataByCode($EBCode);		
								
				# Als het alleen reiskosten zijn (dus geen overige), neem dat dan als omschrijving
				# Bouw anders de omschrijving op uit de verschillende declaraties
				if(count($declaratie->overigeKosten) == 0) {
					$toelichting		= 'reiskostenvergoeding';
				
				# Loopje voor oude declaraties
				} elseif(isset($declaratie->overigeKosten[0]['omschrijving'])) {
					if(count($declaratie->posten) > 0) {
						# Als er een post bekend is, voeg dat dan toe aan de omschrijving
						foreach($declaratie->posten as $index => $post) {
							$regel[] = $declaratie->overigeKosten[$index]['omschrijving'].' [JG'. substr('0'.$post, -2) .']';            
						}
						$toelichting		= implode(', ', $regel);					
					} else {
						$toelichting		= implode(', ', array_column($declaratie->overigeKosten, 'omschrijving'));        
					}
				
				# Loopje voor declaraties in de nieuwe manier
				} else {
					if(count($declaratie->posten) > 0) {
						$key = 0;
						foreach($declaratie->overigeKosten as $titel => $bedrag) {
							$post = $declaratie->posten[$key];
							$regel[] = $titel .' [JG'. substr('0'.$post, -2) .']';
							$key++;
						}
						$toelichting		= implode(', ', $regel);        
					} else {
						$toelichting		= implode(', ', array_keys($declaratie->overigeKosten));
					}
				}
								
				# eBoekhouden heeft een limiet voor 200 tekens voor de toelichting
				# bij een te lange toelichting wordt dit gewoon vervangen door 'declaratie 15 januari 2026'
				if(strlen($toelichting) > 200) {
					$toelichting = 'declaratie '. time2str('d LLLL yyyy', $declaratie->tijd);
				}

				# Tijdelijk voor Wandertheater Dolorosa
				if($declaratie->cluster == 8) {
					$toelichting = $toelichting.'_Dolorosa';
				}
							
				if($write2EB)	{					
					$errorResult = eb_verstuurDeclaratie ($EBCode, $boekstukNummer->nummer, $factuurnummer, $declaratie->totaal, $declaratie->GBR, $toelichting.' ('.$declaratie->hash.')', $mutatieId);
					if($errorResult) {
						toLog($errorResult, 'error', $indiener->id);
						$page[] = 'Probleem met toevoegen van declaratie ter waarde van '. formatPrice($declaratie->totaal) .' aan '. $EBData['naam'] .' ('. $EBCode .')<br>';
						$addSucces = false;
					} else {
						toLog('Declaratie ['. $declaratie->hash .'] van '. formatPrice($declaratie->totaal) .' toegevoegd voor '. $EBData['naam'] .' ('. $EBCode .')', 'info', $indiener->id);
						$page[] = 'Declaratie van '. formatPrice($declaratie->totaal) .' ingediend tnv '. $EBData['naam'] .'<br>';
						$addSucces = true;
					}
				} else {
					$page[] = "DECLARATIE<br>";
					$page[] = "EBCode: ". $EBCode .'<br>';
					$page[] = "BoekstukNummer: ". $boekstukNummer->nummer .'<br>';
					$page[] = "Factuurnummer: ". $factuurnummer .'<br>';
					$page[] = "Totaal: ". $declaratie->totaal .'<br>';
					$page[] = "GBR: ". $declaratie->GBR .'<br>';
					$page[] = "Toelichting: ". $toelichting .'<br>';		
					$addSucces = true;
				}						
				
				# Als de declaratie succesvol is ingeschreven in eBoekhouden kunnen er mails verstuurd worden
				if($addSucces) {
					$cluster = $declaratie->cluster;
							
					$MailFinAdmin = array();
					$MailFinAdmin[] = $indiener->getName(5) .' heeft een declaratie ingediend.<br>';
					$MailFinAdmin[] = "<br>";
					$MailFinAdmin[] = "Het betreft een declaratie ter waarde van ". formatPrice($declaratie->totaal)." voor cluster ". $clusters[$cluster] ." tnv ". $EBData['naam'] .' ('. $EBCode .')';
					
					$FAMail = new KKDMailer();
					$FAMail->ontvangers[]	= array($EBDeclaratieAddress);
					$FAMail->Subject		= "Declaratie ". $indiener->getName(5) ." voor cluster ". $clusters[$cluster];
					$FAMail->Body			= implode("\n", $MailFinAdmin);

					foreach($declaratie->bijlagen as $path => $name) {
						$FAMail->addAttachment($path, $boekstukNummer->nummer.'_'.$name);
					}
					
					if(!$sendMail)	$FAMail->testen = true;
								
					if(!$FAMail->sendMail()) {
						toLog("Problemen met versturen van mail naar financiële administratie", 'error', $indiener->id);
						$page[] = "Er zijn problemen met het versturen van mail naar de financiële administratie";
					} else {
						toLog("Declaratie-notificatie naar financiële administratie", 'debug', $indiener->id);
					}

					$onderwerpen = array();		
					if(count($declaratie->overigeKosten) > 0)	$onderwerpen = array_merge($onderwerpen, array_keys($declaratie->overigeKosten));		
					if($declaratie->reiskosten > 0)				$onderwerpen = array_merge($onderwerpen, array('reiskosten'));
								
					$MailIndiener = array();
					$MailIndiener[] = "Beste ". $indiener->getName(1) .",<br>";
					$MailIndiener[] = "<br>";
					$MailIndiener[] = "Je declaratie van ".time2str('d LLLL', $declaratie->tijd) ." voor <i>". makeOpsomming($onderwerpen, '</i>, <i>', '</i> en <i>') ."</i> ter waarde van ". formatPrice($declaratie->totaal)." is goedgekeurd en zal worden uitbetaald.<br>";
					
					$IndMail = new KKDMailer();
					$IndMail->aan = $indiener->id;
					$IndMail->Subject	= 'Uitbetaling declaratie';
					$IndMail->Body		= implode("\n", $MailIndiener);
					
					if($cluster == 2) {
						$IndMail->From		= $penningmeesterJGAddress;
						$IndMail->FromName	= $penningmeesterJGNaam;
					} else {
						$IndMail->From		= $declaratieReplyAddress;
						$IndMail->FromName	= $declaratieReplyName;
					}
					
					if(!$sendMail)	$IndMail->testen = true;
					
					if(!$IndMail->sendMail()) {
						toLog("Problemen met versturen declaratie-goedkeuring [". $declaratie->hash ."] door penningmeester", 'error', $indiener->id);
						$page[] = "Er zijn problemen met het versturen van de goedkeuringsmail.<br>\n";
					} else {
						toLog("Declaratie-goedkeuring [". $declaratie->hash ."] door penningmeester", '', $indiener->id);
						$page[] = "Er is een mail met goedkeuring verstuurd naar ". $indiener->getName(5) ."<br>\n";
					}
				}
				
				$declaratie->status = 5;				
				
				$page[] = "<br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";
			#
			# Als declaratie niet helemaal OK is, stuur hem dan terug naar de CluCo/indiener
			} elseif(isset($_POST['reject'])) {
				if(isset($_REQUEST['send_reject'])) {
					$terugleggen_bij = $_POST['terugleggen_bij'];
					$ontvanger = new Member($terugleggen_bij);	
					
					$mail[] = "Beste ". $ontvanger->getName(1) .",<br>";
					$mail[] = "<br>";
					$mail[] = "Onderstaande declaratie is door de penningmeester teruggelegd bij jou.<br>";
					$mail[] = "Als reden daarvoor heeft de penningmeester de volgende reden opgegeven :";
					$mail[] = '<table border=0>';
					$mail[] = "<tr>";
					$mail[] = "		<td colspan='6'>&nbsp;</td>";
					$mail[] = "</tr>";
					$mail[] = "<tr>";
					$mail[] = "		<td>&nbsp;</td>";
					$mail[] = "		<td colspan='5'><i>". $declaratie->opmerking ."</i></td>";
					$mail[] = "</tr>";
					$mail[] = "<tr>";
					$mail[] = "		<td colspan='6' height=50><hr></td>";
					$mail[] = "</tr>";			
					$mail = array_merge($mail, showDeclaratieDetails($declaratie));			
					$mail[] = "</table>";
					
					$terug = new KKDMailer();
					$terug->aan		= $terugleggen_bij;
					$terug->Subject = 'Terugleggen declaratie';
					$terug->Body	= implode("\n", $mail);
					
					if($declaratie->cluster == 2) {						
						$terug->From		= $penningmeesterJGAddress;
						$terug->FromName	= $penningmeesterJGNaam;
						$declaratie->status	= 2;
					} else {
						$terug->From		= $declaratieReplyAddress;
						$terug->FromName	= $declaratieReplyName;
						$declaratie->status	= 3;
					}
					
					if(!$sendMail)	$terug->testen = true;
					
					if(!$terug->sendMail()) {
						toLog("Problemen met versturen teruglegging [". $declaratie->hash ."]", 'error', $indiener->id);
						$page[] = "Er zijn problemen met het versturen van de mail.";
					} else {
						toLog("Declaratie-afwijzing [". $declaratie->hash ."] naar gemeentelid", '', $indiener->id);
						$page[] = "Er is een mail met toelichting  verstuurd";
					}
				
					$page[] = "<br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";			
				} else {
					$cluster = $declaratie->cluster;
					if(isset($clusterCoordinatoren[$cluster])) {
						$cluco = $clusterCoordinatoren[$cluster];
					}
					
					if($cluster == 2) {
						$terugleggen_bij = $declaratie->gebruiker;
						$ontvanger = $indiener;
					} else {
						$terugleggen_bij = $cluco;
						$ontvanger = new Member($cluco);
					}
					
					$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
					#$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";
					$page[] = "<input type='hidden' name='terugleggen_bij' value='$terugleggen_bij'>";
					$page[] = "<input type='hidden' name='reject' value='1'>";
					$page[] = '<table border=0>';
					$page[] = "<tr>";
					$page[] = "		<td align='left'>Geef hieronder een korte toelichting aan ". $ontvanger->getName(1) ." waarom deze declaratie wordt teruggegeven.<br>Deze toelichting zal integraal worden opgenomen in de mail.</td>";
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
			# Als declaratie helemaal niet OK is, kan hij ook verwijderd worden (zonder feedback)
			} elseif(isset($_POST['dump'])) {
				$declaratie->status = 7;
				
				toLog("Declaratie afgekeurd [". $declaratie->hash ."]", '', $indiener->id);
				
				$page[] = "Declaratie is gemarkeerd als verwijderd. Neem contact op met de webmaster mocht dit onjuist zijn.<br>";
				$page[] = "<br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";			
			
			# 
			# Declaratie kan an-sich goed zijn, maar niet als declaratie maar als exploitatie
			# Dan doorsturen naar financiele administratie om op die manier af te handelen
			} elseif(isset($_POST['reroute'])) {
				if(isset($_REQUEST['send_reroute'])) {
					$FinMail = new KKDMailer();
					$FinMail->ontvangers[]	= array($FinAdminAddress);
					$FinMail->Subject		= 'Handmatig verwerken';
					$FinMail->Body			= $declaratie->opmerking;
					$FinMail->From			= $declaratieReplyAddress;
					$FinMail->FromName		= $declaratieReplyName;

					foreach($declaratie->bijlagen as $local => $naam) {
						$FinMail->addAttachment($local, $naam);
					}
					
					if(!$sendMail)	$FinMail->testen = true;
									
					if(!$FinMail->sendMail()) {
						toLog("Problemen met declaratie [". $declaratie->hash ."] kenmerken als niet exploitatie", 'error', $indiener->id);
						$page[] = "Er zijn problemen met het doorsturen naar de financiele administratie voor afhandeling.<br>";
					} else {
						toLog("Declaratie [". $declaratie->hash ."] betreft geen exploitatie", '', $indiener->id);
						$page[] = "Declaratie is doorgestuurd naar de financiele administratie voor verdere afhandeling als zijnde geen declaratie. Neem contact op met de webmaster mocht dit onjuist zijn.<br>";
						$declaratie->status = 8;						
					}
									
					$page[] = "<br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";				
				} else {				
					$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";					
					$page[] = "<input type='hidden' name='reroute' value='1'>";
					$page[] = '<table border=0>';
					$page[] = "<tr>";
					$page[] = "		<td align='left'>Hieronder kan een korte toelichting voor de financiele administratie gegeven worden.<br>Deze toelichting zal samen met de bijlages worden opgenomen als tekst in de mail.</td>";
					$page[] = "</tr>";	
					$page[] = "<tr>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "</tr>";	
					$page[] = "<tr>";
					$page[] = "		<td align='center'><textarea name='toelichting' cols=75 rows=10>". $declaratie->opmerking ."</textarea></td>";
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
				$page[] = "<input type='hidden' name='key' value='". $declaratie->hash ."'>";
				#$page[] = "<input type='hidden' name='user' value='". $data['user'] ."'>";
				#$page[] = "<input type='hidden' name='GBR' value='". $_REQUEST['GBR'] ."'>";		
				$page[] = "<table border=0 width='100%'>";
				
				$counter = 0;
								
				foreach($declaratie->overigeKosten as $key => $string) {
					if($string != '' OR $first) {
						$page[] = "	<tr>";
						$page[] = "		<td>$key (". formatPrice($string) .")</td>";
						$page[] = "		<td><select name='post[$key]'>";
						
						$page[] = "	<option value='0'></option>";
				
						foreach($declJGKop as $id => $kop) {
							$page[] = "	<optgroup label='$kop'>";
							
							foreach($declJGPost[$id] as $post_nr => $titel) {
								$page[] = "	<option value='$post_nr'". ($declaratie->posten[$counter] == $post_nr ? ' selected' : '') .">$titel</option>";
							}
							
							$page[] = "	</optgroup>";
						}
						$page[] = "</select></td>";
						$page[] = "	</tr>";
					}
					$counter++;
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
				if(isset($_POST['post'])) {
					foreach($_POST['post'] as $key => $waarde) {
						$page[] = "<input type='hidden' name='post[$key]' value='$waarde'>";
					}					
				}
				$page[] = "<table border=0 width='100%'>";
							
				$page = array_merge($page, showDeclaratieDetails($declaratie));
						
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
				
				if($declaratie->GBR > 0) {
					$presetGBR = $declaratie->GBR;	
				} else {			
					$presetGBR = 0;			
					switch ($declaratie->cluster) {
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
				
				if(!$declaratie->eigenRekening) {		
					$page[] = "<tr>";
					$page[] = "		<td colspan='6'>&nbsp;</td>";
					$page[] = "</tr>";
					$page[] = "<tr>";
					$page[] = "	<td valign='top' colspan='2'>Betalingskenmerk</td>";	
					$page[] = "	<td valign='top' colspan='4'><input type='text' name='betalingskenmerk' value='". $declaratie->betalingskenmerk ."' size='40'></td>";
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
						$page[] = "	<option value='". $relatieData['code'] ."'". ($declaratie->begunstigde == $relatieData['code'] ? ' selected' : '') .">". $relatieData['naam'] ."</option>";
					}
					
					$page[] = "	</select></td>";
					$page[] = "</tr>";			
					if(isset($meldingBegunstigde)) {
						$page[] = "<tr>";
						$page[] = "	<td valign='top' colspan='2'>&nbsp;</td>";
						$page[] = "	<td valign='top' colspan='4' class='melding'>$meldingBegunstigde</td>";
						$page[] = "</tr>";
					}

					if($declaratie->begunstigde == 3) {
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
				}
				
				$page[] = "<tr>";
				$page[] = "		<td colspan='6'>&nbsp;</td>";
				$page[] = "</tr>";
				$page[] = "<tr>";
				$page[] = "		<td colspan='6'>";
				
				if($declaratie->cluster == 2) {
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

		# Sla de declaratie-gegevens op in de sessie
		$_SESSION['declaratie'] = $declaratie;
	} else {
		if(in_array($_SESSION['useID'], $penningJG->leden)) {			
			$hashes = Declaratie::getDeclaraties(4, 2);
		} else {
			$hashes = Declaratie::getDeclaraties(4);
		}
					
		if(count($hashes) > 0) {
			$page[] = "<table>";
			$page[] = "<tr>";
			$page[] = "<td colspan='2'><b>Tijdstip</b></td>";
			$page[] = "<td colspan='2'><b>Cluster</b></td>";				
			$page[] = "<td colspan='2'><b>Indiener</b></td>";			
			$page[] = "<td><b>Bedrag</b></td>";
			$page[] = "</tr>";
				
			foreach($hashes as $hash) {
				$decl = new Declaratie($hash);
				$indiener = new Member($decl->gebruiker);

				$page[] = "<tr>";
				$page[] = "<td>". time2str('d LLL HH:mm', $decl->tijd) ."</td>";
				$page[] = "<td>&nbsp;</td>";			
				$page[] = "<td>". $clusters[$decl->cluster] ."</td>";
				$page[] = "<td>&nbsp;</td>";
				$page[] = "<td><a href='../profiel.php?id=". $indiener->id ."' target='profiel'>". $indiener->getName(5) ."</a></td>";
				$page[] = "<td>&nbsp;</td>";					
				$page[] = "<td><a href='?key=". $decl->hash ."'>". formatPrice($decl->totaal) ."</a></td>";
				$page[] = "</tr>";
			}
			$page[] = "</table>";
		} else {
			$page[] = "Geen openstaande declaratie's";
		}	
	}
} else {
	$page[] = "U bent geen penningsmeester";
}

if(isset($_REQUEST['send_reject']) || isset($_POST['dump']) || isset($_REQUEST['send_reroute']) || (isset($_POST['accept']) && $veldenCorrect)) {	
	# En sla het object op
	$declaratie->save();
				
	# Alles verwijderen nadat de declaratie is ingeschoten en de mail de deur uit is
	$declaratie = null;
}

# Pagina tonen
$pageTitle = 'Declaratie';
include_once('../include/HTML_TopBottom.php');

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $page).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

# Sla de declaratie-gegevens op in de sessie
$_SESSION['declaratie'] = $declaratie;
?>
