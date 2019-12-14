<?php
include_once('../../../general_include/class.phpmailer.php');
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');
include_once('genereerDeclaratiePdf.php');

$db = connect_db();

$write2EB = false;
$sendMail = false;
$sendTestMail = true;

if(isset($_REQUEST['hash'])) {
	$hash = urldecode($_REQUEST['hash']);
	$dienst = $_REQUEST['d'];
	$voorganger = $_REQUEST['v'];

	# De hash klopt
	if(password_verify($dienst.'$'.$randomCodeDeclaratie.'$'.$voorganger,$hash)) {
		$dienstData = getKerkdienstDetails($dienst);
		$voorgangerData = getVoorgangerData($voorganger);

		# Schrijf de variabelen die in het hele proces verzameld worden als hidden parameters weg in het formulier
		$page[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";		
		if(isset($dienst))							$page[] = "<input type='hidden' name='d' value='$dienst'>";
		if(isset($voorganger))					$page[] = "<input type='hidden' name='v' value='$voorganger'>";
		if(isset($_REQUEST['hash']))		$page[] = "<input type='hidden' name='hash' value='". trim($_REQUEST['hash']) ."'>";
		if(isset($_POST['reiskosten']))	$page[] = "<input type='hidden' name='reiskosten' value='". trim($_POST['reiskosten']) ."'>";
		if(isset($_POST['reis_van']))		$page[] = "<input type='hidden' name='reis_van' value='". trim($_POST['reis_van']) ."'>";
		if(isset($_POST['reis_naar']))	$page[] = "<input type='hidden' name='reis_naar' value='". trim($_POST['reis_naar']) ."'>";

		if(isset($_POST['overig']))	{
			foreach($_POST['overig'] as $key => $string) {
				$page[] = "<input type='hidden' name='overig[$key]' value='$string'>";
				$page[] = "<input type='hidden' name='overig_price[$key]' value='". $_POST['overig_price'][$key] ."'>";
			}
		}

		if(isset($_POST['indienen'])) {
			# Scherm waarbij
			# - bepaald wordt of al een eBoekhouden-relatie bekend is, zo ja updaten / zo nee toevoegen
			# - de mail naar de penningmeester wordt opgesteld, daarin wordt het totaal berekend
			# - toevoegen aan eBoekhouden, daar is totaal voor nodig en komt mutatieId terug
			# - PDF wordt aangemaakt, daar is mutatieId voor nodig
			
			

			# -------
			# Paar dingen definieren voor zometeen			
			$mailNaam				= makeVoorgangerName($voorganger, 4);
			$dagdeel				= formatDagdeel($dienstData['start']);
			$IBANChangeSucces = $IBANSearchSucces = $addRelatieSucces = $sendDeclaratieSucces = true;

			
			# -------
			# Relatie bepalen, vergelijken, en zo nodig updaten of invoeren
			if($voorgangerData['EB-relatie'] != '' AND $voorgangerData['EB-relatie'] > 0) {				
				if(trim(strtoupper($_POST['oorspronkelijke_IBAN'])) != trim(strtoupper($_POST['IBAN'])) AND $write2EB) {
					$errorResult = eb_updateRelatieIbanByCode($voorgangerData['EB-relatie'], trim(strtoupper($_POST['IBAN'])));
					if($errorResult) {
						toLog('error', '', '', $errorResult);
						$IBANChangeSucces = false;						
					}
				}			
			} else {
				# op basis van IBAN zoeken of iemand al bekend is				 
				$errorResult = eb_getRelatieCodeByIban ($_POST['IBAN'], $EB_code);
				if($errorResult) {
					toLog('error', '', '', $errorResult);
					$IBANSearchSucces = false;
				}
								
				if(!is_numeric($EB_code)) {
					if($write2EB) {
						$errorResult = eb_maakNieuweRelatieAan (makeVoorgangerName($voorganger, 6), 'm', '', '', $voorgangerData['plaats'], $voorgangerData['mail'], $_POST['IBAN'], $EB_code, $EB_id);
						if($errorResult) {
							toLog('error', '', '', $errorResult);
							$addRelatieSucces = false;
						}
						
						if($addRelatieSucces) {
							$sql = "UPDATE $TableVoorganger SET $VoorgangerEBRelatie = '$EB_code' WHERE $VoorgangerID = $voorganger";
							mysqli_query($db, $sql);							
							$voorgangerData['EB-relatie'] = $EB_code;
						}
					} else {
						echo 'Nieuwe relatie aanmaken voor '. $_POST['IBAN'];
					}
				}				
			}
			
			
			# -------
			# Mail naar de penningsmeester opstellen
			$mailPenningsmeester = array();
			$mailPenningsmeester[] = "Beste,<br>";
			$mailPenningsmeester[] = "<br>";
			$mailPenningsmeester[] = makeVoorgangerName($voorganger, 3) .' heeft een declaratie ingediend.<br>';
			$mailPenningsmeester[] = "<br>";
			$mailPenningsmeester[] = "Het betreft de $dagdeel van ". date('d M Y', $dienstData['start']) ."<br>";
			$mailPenningsmeester[] = "<table>";
			$mailPenningsmeester[] = "	<tr>";
			$mailPenningsmeester[] = "		<td>Declaratie</td>";
			$mailPenningsmeester[] = "		<td>&nbsp;</td>";
			$mailPenningsmeester[] = "		<td>Preekbeurt</td>";
			$mailPenningsmeester[] = "		<td align='right'>". formatPrice($voorgangerData['honorarium']) ."</td>";
			$mailPenningsmeester[] = "	</tr>";
			$mailPenningsmeester[] = "	<tr>";
			$mailPenningsmeester[] = "		<td colspan='2'>&nbsp;</td>";
			$mailPenningsmeester[] = "		<td>Reiskosten<br><small>".$_POST['reis_van'] .' -> '. $_POST['reis_naar'] ." v.v.</small></td>";
			$mailPenningsmeester[] = "		<td align='right' valign='top'>". formatPrice($_POST['reiskosten']) ."</td>";
			$mailPenningsmeester[] = "	</tr>";

			$totaal = $voorgangerData['honorarium'] + $_POST['reiskosten'];
			$declaratieDataExtra = array();

			foreach($_POST['overig'] as $key => $string) {
				if($string != '') {
					$price = 100*str_replace(',', '.', $_POST['overig_price'][$key]);
					$totaal = $totaal + $price;
					
					$mailPenningsmeester[] = "	<tr>";
					$mailPenningsmeester[] = "		<td colspan='2'>&nbsp;</td>";
					$mailPenningsmeester[] = "		<td>$string</td>";
					$mailPenningsmeester[] = "		<td align='right'>". formatPrice($price) ."</td>";
					$mailPenningsmeester[] = "	</tr>";
					
					$declaratieDataExtra[] = array($string, $price);
				}
			}

			$mailPenningsmeester[] = "	<tr>";
			$mailPenningsmeester[] = "		<td colspan='2'>&nbsp;</td>";
			$mailPenningsmeester[] = "		<td><b>Totaal</b></td>";
			$mailPenningsmeester[] = "		<td align='right'><b>". formatPrice($totaal) ."</b></td>";
			$mailPenningsmeester[] = "	</tr>";
			$mailPenningsmeester[] = "</table>";
			
			
			
			# -------
			# In eboekhouden inschieten
			if($write2EB AND isset($voorgangerData['EB-relatie']) AND $voorgangerData['EB-relatie'] > 0) {			
				$errorResult = eb_verstuurDeclaratie ($voorgangerData['EB-relatie'], $totaal, '[verwijder deze declaratie] '. date('Y-m-d', $dienstData['start']).', '. $dagdeel .', '.makeVoorgangerName($voorganger, 2), $mutatieId);			
				if($errorResult) {
					toLog('error', '', '', $errorResult);
					$sendDeclaratieSucces = false;
				}
			} else {
				$mutatieId = '10101';
			}
			
			# Als de declaratie succesvol is ingeschoten			
			if($sendDeclaratieSucces) {			
				# -------
				# PDF maken
				$mutatieNr			= $mutatieId;
				$mutatieDatum 	= date("d-m-Y");
				$naam						= makeVoorgangerName($voorganger, 3);
				$adres					= $voorgangerData['plaats'];
				$mailadres			= $voorgangerData['mail'];
				$iban						= trim(strtoupper($_POST['IBAN']));
				$declaratieData	= array(
					array("Voorgaan $dagdeel ". date('d-m', $dienstData['start']), $voorgangerData['honorarium']),
					array("Reiskosten (". $_POST['reis_van'] .")", $_POST['reiskosten'])
				);
			
				$declaratieData = array_merge($declaratieData, $declaratieDataExtra);
						
				genereer_declaratie_pdf($mutatieNr, $mutatieDatum, $naam, $adres, $mailadres, $iban, $declaratieData);				
					
			
				# -------		
				# Mail naar penningmeester versturen
				unset($mail);
				$mail = new PHPMailer;
				$mail->FromName	= $ScriptTitle;
				$mail->From			= $ScriptMailAdress;
    	
				# Alle geadresseerden toevoegen
				#
				# Volgens mij kunnen wij ook  een document in eBoekhouden mailen
				# dan hoeft de boekhouder alleen te koppelen
				# even uitzoeken
				# PDF sturen naar 184264@e-boekhouden.nl
				
				if(!$sendTestMail) {
					$mail->AddAddress($declaratieReplyAddress, $declaratieReplyName);
					$mail->AddCC($EBDeclaratieAddress);
				} else {
					$mail->AddAddress($ScriptMailAdress);
				}
							
				# Onderwerp maken
				$Subject = "Declaratie $dagdeel ". date('j-n-Y', $dienstData['start']);
				
				$mail->Subject	= trim($Subject);
				$mail->IsHTML(true);
				$mail->Body	= $MailHeader.implode("\n", $mailPenningsmeester).$MailFooter;
				$mail->AddAttachment('PDF\\'. $mutatieNr .'.pdf', date('ymd') .' '. makeVoorgangerName($voorganger, 1) . ' '. date('d-m', $dienstData['start']) .' '. $dagdeel .'.pdf');
				
				if(!$sendMail) {
					$page[] = 'Afzender :'. $ScriptTitle .'|'.$ScriptMailAdress;
					$page[] = 'Ontvanger :'. $declaratieReplyName .'|'.$declaratieReplyAddress;
					$page[] = 'Onderwerp :'. trim($Subject);
					$page[] = implode("\n", $mailPenningsmeester);
				} else {
					if(!$mail->Send()) {
						toLog('error', '', '', "Problemen met declaratie-notificatie (dienst $dienst, voorganger $voorganger)");
						$page[] = "Er zijn problemen met het versturen van de notificatie-mail naar penningsmeester.";
					} else {
						toLog('info', '', '', "Declaratie-notificatie naar penningsmeester voor ". date('j-n-Y', $dienstData['start']));						
					}
				}
				
				
				
				# -------
				# Mail naar de predikant opstellen en versturen
				$mailPredikant = array();
				$mailPredikant[] = "Beste ". makeVoorgangerName($voorganger, 5) .",";
				$mailPredikant[] = "";
				$mailPredikant[] = ($voorgangerData['stijl'] == 0 ? 'u heeft' : 'jij hebt')." online een declaratie ingediend voor het voorgaan in de $dagdeel van ". strftime ('%e %B', $dienstData['start'])." in de Koningskerk te Deventer.";
				$mailPredikant[] = "Een samenvatting van deze declaratie voor in ". ($voorgangerData['stijl'] == 0 ? 'uw administratie treft u' : 'in je administratie tref je')." aan de bijlage";
				$mailPredikant[] = "";
				$mailPredikant[] = "Declaratie worden over het algemeen rond de 20ste van de maand uitbetaald.";
				$mailPredikant[] = "";
				$mailPredikant[] = "Mochten er nog vragen zijn dan hoor ik het graag.";
				$mailPredikant[] = "";
				$mailPredikant[] = "Vriendelijke groeten";
				$mailPredikant[] = "";
				$mailPredikant[] = $declaratieReplyName;
				$mailPredikant[] = $declaratieReplyAddress;
				
				# Nieuw mail-object aanmaken
				unset($mail);
				$mail = new PHPMailer;
				$mail->FromName	= $declaratieReplyName;
				$mail->From			= $declaratieReplyAddress;
    	
				# Alle geadresseerden toevoegen
				if(!$sendTestMail) {
					$mail->AddAddress($voorgangerData['mail'], $mailNaam);
				} else {
					$mail->AddAddress($ScriptMailAdress);
				}
							
				# Onderwerp maken
				$Subject = "Declaratie $dagdeel ". date('j-n-Y', $dienstData['start']);
				
				$mail->Subject	= trim($Subject);
				$mail->IsHTML(true);
				$mail->Body	= $MailHeader.implode("<br>\n", $mailPredikant).$MailFooter;
				$mail->AddAttachment('PDF\\'. $mutatieNr .'.pdf',"Declaratie $dagdeel ". date('j-n-Y', $dienstData['start']) ." Koningskerk Deventer.pdf");
				
				if(!$sendMail) {
					$page[] = 'Afzender :'. $declaratieReplyName .'|'.$declaratieReplyAddress;
					$page[] = 'Ontvanger :'. $mailNaam .'|'.$voorgangerData['mail'];
					$page[] = 'Onderwerp :'.trim($Subject);
					$page[] = implode("<br>\n", $mailPredikant);
				} else {
					if(!$mail->Send()) {
						toLog('error', '', '', "Problemen met declaratie-afschrift (dienst $dienst, voorganger $voorganger)");
						$page[] = "Er zijn problemen met het versturen van een afschrift van de declaratie.";
					} else {
						toLog('info', '', '', "Declaratie-afschrift naar $mailNaam voor ". date('j-n-Y', $dienstData['start']));
						$page[] = "Er is een afschrift van de declaratie naar ". ($voorgangerData['stijl'] == 0 ? 'u' : 'jou') ." verstuurd.";
					}
				}					
				
				# -------						
				# update reis_van voor volgende keer
				$sql = "UPDATE $TableVoorganger SET $VoorgangerVertrekpunt = '". urlencode($_POST['reis_van']) ."' WHERE $VoorgangerID like '$voorganger'";
				mysqli_query($db, $sql);
				
							
				# -------
				# Zet de status op afgerond
				setVoorgangerDeclaratieStatus(8, $dienst);
			}
		} elseif(isset($_POST['check_iban'])) {
			# Formulier waar IBAN wordt gecontroleerd
			
			eb_getRelatieIbanByCode ($voorgangerData['EB-relatie'], $IBAN);

			$page[] = "Voer hieronder het bankrekening-nummer in waarnaar het bedrag moet worden overgemaakt.<br>";
			$page[] = "<br>";
			$page[] = "<input type='hidden' name='oorspronkelijke_IBAN' value='$IBAN' size='30'>";
			$page[] = "<input type='text' name='IBAN' value='$IBAN' size='30'>";
			$page[] = "<input type='submit' name='indienen' value='Dien declaratie in'>";
			$page[] = "</form>";
		} elseif(isset($_POST['check_form'])) {
			# Formulier waar preekbeurt en reiskostenvergoeding ter controle worden getoond

			$page[] = "U staat op het punt de volgende declaratie in te dienen :<br>";
			$page[] = "<br>";
			$page[] = "<table>";
			$page[] = "	<tr>";
			$page[] = "		<td>Naam</td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td colspan='2'>". makeVoorgangerName($voorganger, 3) ."</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td>Dienst</td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td colspan='2'>". formatDagdeel($dienstData['start']) ." ". strftime('%e %B %Y', $dienstData['start']) ."</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td>Declaratie</td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td>Preekbeurt</td>";
			$page[] = "		<td align='right'>". formatPrice($voorgangerData['honorarium']) ."</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='2'>&nbsp;</td>";
			$page[] = "		<td>Reiskosten<br><small>".$_POST['reis_van'] .' -> '. $_POST['reis_naar'] ." v.v.</small></td>";
			$page[] = "		<td align='right' valign='top'>". formatPrice($_POST['reiskosten']) ."</td>";
			$page[] = "	</tr>";

			$totaal = $voorgangerData['honorarium'] + $_POST['reiskosten'];

			foreach($_POST['overig'] as $key => $string) {
				if($string != '') {
					$page[] = "	<tr>";
					$page[] = "		<td colspan='2'>&nbsp;</td>";
					$page[] = "		<td>$string</td>";
					$page[] = "		<td align='right'>". formatPrice(100*str_replace(',', '.', $_POST['overig_price'][$key])) ."</td>";
					$page[] = "	</tr>";

					$totaal = $totaal + 100*str_replace(',', '.', $_POST['overig_price'][$key]);
				}
			}

			$page[] = "	<tr>";
			$page[] = "		<td colspan='2'>&nbsp;</td>";
			$page[] = "		<td><b>Totaal</b></td>";
			$page[] = "		<td align='right'><b>". formatPrice($totaal) ."</b></td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='4'>&nbsp;</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='4'>Zijn deze gegevens correct ?</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='2'><input type='submit' name='check_iban' value='Ja'></td>";
			$page[] = "		<td colspan='2'><input type='submit' name='redo_form' value='Nee'></td>";
			$page[] = "	</tr>";
			$page[] = "</table>";
			$page[] = "</form>";
		} else {
			# De direct-link uit de declaratie-mail komt hier terecht
			setVoorgangerDeclaratieStatus(3, $dienst);

			# Formulier waar preekbeurt en reiskostenvergoeding kunnen worden ingevuld
			$next = false;

			$page[] = "<table border=0>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='6'><b>Preekbeurt</b></td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td width='20'>&nbsp;</td>";
			$page[] = "		<td colspan='3'>".formatDagdeel($dienstData['start']) ." ". strftime('%e %B %Y', $dienstData['start']) ."</td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td align='right'>". formatPrice($voorgangerData['honorarium']) ."</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='6'>&nbsp;</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='6'><b>Reiskostenvergoeding</b></td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td>Van</td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td>Naar</td>";
			$page[] = "		<td colspan='2'>&nbsp;</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td><input type='text' name='reis_van' value='". (!isset($_POST['reis_van']) ? $voorgangerData['reis_van'] : $_POST['reis_van']) ."' size='30'></td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td><input type='text' name='reis_naar' value='Mari&euml;nburghstraat 4, Deventer' size='30'></td>";
			$page[] = "		<td colspan='2'>&nbsp;</td>";
			$page[] = "	</tr>";

			# Als reis_van en reis_naar bekend zijn, kan het aantal kilometers worden uitgerekend
			# en kan het volgende deel van het formulier getoond worden
			if(isset($_POST['reis_van']) AND isset($_POST['reis_naar'])) {
				$next = true;
				$first = true;
				$kms = determineAddressDistance($_POST['reis_van'], $_POST['reis_naar']);
				$km = array_sum($kms);

				$reiskosten = $km * $voorgangerData['km_vergoeding'];

				$page[] = "	<tr>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td><small>". round($km, 1) ." km x ". formatPrice($voorgangerData['km_vergoeding']) ."</small></td>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td align='right'>". formatPrice($reiskosten) ."</td>";
				$page[] = "<input type='hidden' name='reiskosten' value='$reiskosten'>";
				$page[] = "	</tr>";
				$page[] = "	<tr>";
				$page[] = "		<td colspan='6'>&nbsp;</td>";
				$page[] = "	</tr>";
				$page[] = "	<tr>";
				$page[] = "		<td colspan='6'><b>Overige</b></td>";
				$page[] = "	</tr>";

				if(isset($_POST['overig'])) {
					$overige = $_POST['overig'];
				} else {
					$overige = array();
				}

				$overige[] = '';

				# Laat invoervelden voor overige zaken zien
				foreach($overige as $key => $string) {
					if($string != '' OR $first) {
						$page[] = "	<tr>";
						$page[] = "		<td>&nbsp;</td>";
						$page[] = "		<td colspan='3'><input type='text' name='overig[$key]' value='$string' size='50'></td>";
						$page[] = "		<td>&nbsp;</td>";
						$page[] = "		<td>&euro;&nbsp;<input type='text' name='overig_price[$key]' value='". str_replace(',', '.', $_POST['overig_price'][$key]) ."' size='5'></td>";
						$page[] = "	</tr>";
					}

					# 1 lege regel is voldoende
					if($string == '' AND $first)	$first = false;
				}
			}

			$page[] = "	<tr>";
			$page[] = "		<td colspan='6'>&nbsp;</td>";
			$page[] = "	</tr>";

			if(!$next) {
				$page[] = "	<tr>";
				$page[] = "		<td colspan='6' align='right'><input type='submit' name='next_form' value='Volgende'></td>";
				$page[] = "	</tr>";
			} else {
				$page[] = "	<tr>";
				$page[] = "		<td colspan='3' align='left'><input type='submit' name='new_item' value=\"Regel 'overige' toevoegen\">";
				$page[] = "		<td colspan='3' align='right'><input type='submit' name='check_form' value='Controleren'>";
				$page[] = "	</tr>";
			}
			$page[] = "</table>";
			$page[] = "</form>";
		}
	} else {
		# Direct-link om te declareren is niet correct
		$page[] = "Deze link is niet correct.<br><br>";
		$page[] = "U kunt via <a href='". $_SERVER['PHP_SELF'] ."?draad=predikant'>deze pagina</a> een nieuwe link aanvragen.<br><br>";
		$page[] = "Mocht dat het probleem niet oplossen dan kunt u contact opnemen met <a href='mailto:$ScriptMailAdress'>de webmaster</a>.";
	}
} elseif(isset($_POST['send_link'])) {
	# 2de scherm voor predikanten
	# Als er een dienst geselecteerd is wordt deze doorgemaild

	$dienst = $_POST['dienst'];
	$dienstData = getKerkdienstDetails($dienst);
	$voorganger = $dienstData['voorganger_id'];
	$voorgangerData = getVoorgangerData($voorganger);

	if($voorgangerData['declaratie'] == 0) {
		$page[] = 'Voor deze dienst is het niet mogelijk een declaratie in te dienen.<br>';
		$page[] = 'Mogelijke oorzaken zijn :';
		$page[] = "<ul>";
		$page[] = '<li>Er is in deze dienst geen gastpredikant voorgegaan.</li>';
		$page[] = '<li>Er is voor deze dienst al een declaratie ingediend.</li>';
		$page[] = "</ul>";
	} else {
		$dagdeel = formatDagdeel($dienstData['start']);
		$aanspeekNaam		= makeVoorgangerName($voorganger, 5);
		$mailNaam 			= makeVoorgangerName($voorganger, 4);

		# Nieuw mail-object aanmaken
		$mail = new PHPMailer;
		$mail->FromName	= $declaratieReplyName;
		$mail->From			= $declaratieReplyAddress;

		# Alle geadresseerden toevoegen
		if(!$sendTestMail) {		
			$mail->AddAddress($voorgangerData['mail'], $mailNaam);
		} else {
			$mail->AddAddress($ScriptMailAdress);
		}
		
		# Declaratielink genereren
		$hash = urlencode(password_hash($dienst.'$'.$randomCodeDeclaratie.'$'.$voorganger, PASSWORD_BCRYPT));
		$declaratieLink = $ScriptURL ."declaratie/gastpredikant.php?hash=$hash&d=$dienst&v=$voorganger";

		# Mail opstellen
		$mailText = array();
		$mailText[] = "Beste $aanspeekNaam,";
		$mailText[] = "";
		$mailText[] = ($voorgangerData['stijl'] == 0 ? 'u heeft' : 'jij hebt')." online aangegeven een declaratie te willen indienen voor het voorgaan in de $dagdeel van ". strftime ('%e %B', $dienstData['start'])." in de Koningskerk te Deventer.";
		$mailText[] = "";
		$mailText[] = "Om zeker te weten dat alleen de juiste persoon de declaratie kan indienen wordt ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." verzocht onderstaande link te volgen, ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." wordt dat doorgeleid naar de juiste pagina.";
		$mailText[] = "<a href='$declaratieLink'>invoeren online declaratie</a>";
		$mailText[] = "";
		$mailText[] = "Mochten er nog vragen zijn dan hoor ik het graag.";
		$mailText[] = "";
		$mailText[] = "Vriendelijke groeten";
		$mailText[] = "";
		$mailText[] = $declaratieReplyName;
		$mailText[] = $declaratieReplyAddress;

		# Onderwerp maken
		$Subject = "Declaratie $dagdeel ". strftime('%e %b %y', $dienstData['start']);

		$mail->Subject	= trim($Subject);
		$mail->IsHTML(true);
		$mail->Body	= $MailHeader.implode("<br>\n", $mailText).$MailFooter;
		
		if(!$sendMail) {
			$page[] = 'Afzender :'. $declaratieReplyName .'|'.$declaratieReplyAddress;
			$page[] = 'Ontvanger :'. $mailNaam .'|'.$voorgangerData['mail'];
			$page[] = trim($Subject);
			$page[] = implode("<br>\n", $mailText);
		} else {
			if(!$mail->Send()) {
				toLog('error', '', '', "Problemen met declaratie-link versturen naar $mailNaam voor ". date('j-n-Y', $dienstData['start']));
				$page[] = "Er zijn problemen met het versturen van de mail.";			
			} else {
				toLog('info', '', '', "Declaratie-link verstuurd naar $mailNaam voor ". date('j-n-y', $dienstData['start']));
				$page[] = "Er is een mail gestuurd.";
			}
		}
	}
} else {
	# Startscherm voor predikanten
	# Hier kunnen zij een dienst selecteren

	$page[] = "Om u te identificeren zal zometeen naar het bij ons bekende email-adres van de voorganger van die dienst een link worden gestuurd.<br>";
	$page[] = "<br>";
	$page[] = "Door het volgen van die link komt u uit op de juiste plek in de declaratie-omgeving.<br>";
	$page[] = "<br>";
	$page[] = "Voor welke dienst wilt u een declaratie indienen?<br>";
	$page[] = "<br>";
	$page[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$page[] = "<select name='dienst'>";
	$page[] = "<option value=''>Selecteer de dienst</option>";

	# 3 maanden terug
	$startTijd = mktime(0, 0, 0, (date("n")-3));

	# 23:59:59 vandaag
	$eindTijd = mktime(23, 59, 50);
	$diensten = getKerkdiensten($startTijd, $eindTijd);

	foreach(array_reverse($diensten) as $dienst) {
		$dienstData = getKerkdienstDetails($dienst);
		$dagdeel = formatDagdeel($dienstData['start']);
		$page[] = "<option value='$dienst'>$dagdeel ". strftime('%e %b', $dienstData['start']) ."</option>";		
	}
	$page[] = "</select><br>";
	$page[] = "<br>";
	$page[] = "<input type='submit' name='send_link' value='Verstuur link'>";
	$page[] = "</form>";
}

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

# Aantekeningen zijn verplaatst naar aantekeningen.txt
?>
