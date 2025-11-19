<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Voorganger.php');
include_once('genereerDeclaratiePdf.php');

if($productieOmgeving) {
	$write2EB = true;
	$sendMail = true;
	$sendTestMail = false;
} else {
	$write2EB = false;
	$sendMail = false;
	$sendTestMail = false;
	
	echo 'Test-omgeving';
}

if(isset($_REQUEST['hash'])) {
	$hash		= urldecode($_REQUEST['hash']);	
		
	$dienst		= new Kerkdienst($_REQUEST['d']);
	$voorganger	= new Voorganger($_REQUEST['v']);
	$declaratieStatus = $dienst->declaratieStatus;

	# De hash klopt & de predikant staat ook op het rooster & er is nog niet eerder een declaratie ingediend
	if(password_verify($dienst->dienst.'$'.$randomCodeDeclaratie.'$'.$voorganger->id,$hash) && $dienst->voorganger == $voorganger->id && $declaratieStatus < 8) {		
		#$firstData = getVoorgangerData($voorganger);
		#$secondData = getDeclaratieData($voorganger, $dienst);		
		#$voorgangerData = array_merge($firstData, $secondData);

		# Schrijf de variabelen die in het hele proces verzameld worden als hidden parameters weg in het formulier
		$page[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";		
		if(isset($dienst))				$page[] = "<input type='hidden' name='d' value='". $dienst->dienst ."'>";
		if(isset($voorganger))			$page[] = "<input type='hidden' name='v' value='". $voorganger->id ."'>";
		if(isset($_REQUEST['hash']))	$page[] = "<input type='hidden' name='hash' value='". trim($_REQUEST['hash']) ."'>";
		if(isset($_POST['reiskosten']))	$page[] = "<input type='hidden' name='reiskosten' value='". trim($_POST['reiskosten']) ."'>";
		if(isset($_POST['reis_van']))	$page[] = "<input type='hidden' name='reis_van' value='". trim($_POST['reis_van']) ."'>";
		if(isset($_POST['reis_naar']))	$page[] = "<input type='hidden' name='reis_naar' value='". trim($_POST['reis_naar']) ."'>";
		if(isset($_POST['km']))			$page[] = "<input type='hidden' name='km' value='". trim($_POST['km']) ."'>";

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
			$mailNaam	= $voorganger->getName(4);
			$dagdeel	= formatDagdeel($dienst->start);
			$IBANChangeSucces = $IBANSearchSucces = $addRelatieSucces = $sendDeclaratieSucces = true;
			
			# -------
			# Relatie bepalen, vergelijken, en zo nodig updaten of invoeren
			if($voorganger->boekhoud_id != '' && $voorganger->boekhoud_id > 0) {				
				if(cleanIBAN($_POST['oorspronkelijke_IBAN']) != cleanIBAN($_POST['IBAN']) AND $write2EB) {
					$errorResult = eb_updateRelatieIbanByCode($voorganger->boekhoud_id, cleanIBAN($_POST['IBAN']));
					if($errorResult) {
						toLog($errorResult, 'error');
						$IBANChangeSucces = false;						
					} else {
						toLog('IBAN van relatie '. $voorganger->boekhoud_id .' aangepast van '. $_POST['oorspronkelijke_IBAN'] .' naar '. $_POST['IBAN'], 'debug');
					}
				}			
			} else {
				$EB_code = 0;
				# op basis van IBAN zoeken of iemand al bekend is				
				$errorResult = eb_getRelatieCodeByIban ($_POST['IBAN'], $EB_code);
				if($errorResult) {
					toLog($errorResult, 'error');
					$IBANSearchSucces = false;
				} else {
					toLog('IBAN '. $_POST['IBAN'] .' hoort bij relatie '. $EB_code, 'debug');
					$voorganger->boekhoud_id = $EB_code;
					$voorganger->save();
				}

				// Als er geen nummer terugkomt, is dit IBAN niet bekend en moet deze voorganger worden toegevoegd
				if(!$IBANSearchSucces) {
					if($write2EB) {
						$errorResult = eb_maakNieuweRelatieAan ($voorganger->getName(6), 'm', '', '', $voorganger->plaats, $voorganger->mail, $_POST['IBAN'], $EB_code, $EB_id);
						if($errorResult) {
							toLog($errorResult, 'error');
							$addRelatieSucces = false;
						} else {
							toLog('Nieuwe relatie aangemaakt in e-boekhouden voor '. $voorganger->getName(6) .' -> '. $EB_code, 'debug');
						}
						
						if($addRelatieSucces) {
							$voorganger->boekhoud_id = $EB_code;
														
							if($voorganger->save()) {								
								toLog('In lokale database EBcode '. $EB_code .' aan voorganger '. $voorganger->id .' gekoppeld', 'debug');
							} else {
								toLog('Koppelen van EBcode '. $EB_code .' aan voorganger '. $voorganger->id .' is mislukt', 'error');
							}
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
			$mailPenningsmeester[] = $voorganger->getName(3) .' heeft een declaratie ingediend.<br>';
			$mailPenningsmeester[] = "<br>";
			$mailPenningsmeester[] = "Het betreft de $dagdeel van ". date('d M Y', $dienst->start) ."<br>";
			$mailPenningsmeester[] = "<table>";
			$mailPenningsmeester[] = "	<tr>";
			$mailPenningsmeester[] = "		<td>Declaratie</td>";
			$mailPenningsmeester[] = "		<td>&nbsp;</td>";
			$mailPenningsmeester[] = "		<td>Preekbeurt</td>";
			$mailPenningsmeester[] = "		<td align='right'>". formatPrice($voorganger->honorarium) ."</td>";
			$mailPenningsmeester[] = "	</tr>";
			
			$totaal = $voorganger->honorarium;
			$omschrijving[] = 'preekvergoeding: '. round(formatPrice($voorganger->honorarium, false));
			
			if($voorganger->reiskosten) {
				$mailPenningsmeester[] = "	<tr>";
				$mailPenningsmeester[] = "		<td colspan='2'>&nbsp;</td>";
				$mailPenningsmeester[] = "		<td>Reiskosten<br><small>".$_POST['reis_van'] .' -> '. $_POST['reis_naar'] ." v.v.</small></td>";
				$mailPenningsmeester[] = "		<td align='right' valign='top'>". formatPrice($_POST['reiskosten']) ."</td>";
				$mailPenningsmeester[] = "	</tr>";
				
				$totaal = $totaal + $_POST['reiskosten'];
				$omschrijving[] = 'kilometers: '. round($_POST['km']);
			}

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
					$omschrijving[] = strtolower($string) .': '. formatPrice($price, false);
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
			if($write2EB && isset($voorganger->boekhoud_id) && $voorganger->boekhoud_id > 0) {				
				$boekstukNummer = generateBoekstukNr(date('Y'));				
				$factuurnummer = 'voorgaan-'.date('d-m-Y', $diens->start).'-'.$dagdeel;
				$toelichting = implode(', ', $omschrijving);
								
				$errorResult = eb_verstuurDeclaratie ($voorganger->boekhoud_id, $boekstukNummer, $factuurnummer, $totaal, $cfgGBRPreek, $toelichting, $mutatieId);
				//$page[] = "relatie:". $relatie ."<br>\n";
				//$page[] = "boekstukNummer:". $boekstukNummer ."<br>\n";
				//$page[] = "factuurnummer:". $factuurnummer ."<br>\n";
				//$page[] = "totaal:". $totaal ."<br>\n";
				//$page[] = "toelichting:". $toelichting ."<br>\n";
				
				if($errorResult) {
					toLog($errorResult, 'error');
					$page[] = 'Er is iets niet goed gegaan met aanmaken van de declaratie<br>';
					$page[] = 'Neem contact op met de webmaster zodat deze de logfiles kan uitlezen';
					$sendDeclaratieSucces = false;
				} else {
					toLog("Declaratie aangemaakt; relatie:". $voorganger->boekhoud_id .", boekstukNummer:". $boekstukNummer .", mutatieId:". $mutatieId .", factuurnummer:". $factuurnummer);
				}
			} else {
				$mutatieId = '10101';
				echo 'factuurnummer : '.'voorgaan-'.date('d-m-Y', $diens->start).'-'.$dagdeel ."<br>\n";
				echo 'toelichting :'. implode(', ', $omschrijving) ."<br>\n";
				echo 'boekstukNummer :'. $boekstukNummer ."<br>\n";
			}
			
			# Als de declaratie succesvol is ingeschoten			
			if($sendDeclaratieSucces) {			
				# -------
				# PDF maken
				
				# We gaan er vanuit dat hierboven alles goed gegaan is,
				# maar voor de zekerheid vragen we nogmaals het IBAN-nummer op
				# horend bij dit ID.
				# Dat gaat ook IBAN nummer zijn wat gebruikt gaat worden.
				eb_getRelatieIbanByCode ($voorganger->boekhoud_id, $iban);
				
				$mutatieNr		= $mutatieId;
				$mutatieDatum 	= date("d-m-Y");
				$naam			= $voorganger->getName(3);
				$adres			= $voorganger->plaats;
				$mailadres		= $voorganger->mail;
				$iban			= cleanIBAN($iban);
				$declaratieData	= array(
					array("Voorgaan $dagdeel ". date('d-m', $dienst->start), $voorganger->honorarium),
					array("Reiskosten (". $_POST['reis_van'] .")", $_POST['reiskosten'])
				);
			
				$declaratieData = array_merge($declaratieData, $declaratieDataExtra);
						
				genereer_declaratie_pdf($mutatieNr, $mutatieDatum, $naam, $adres, $mailadres, $iban, $declaratieData);				
					
			
				# -------		
				# Mail naar penningmeester versturen				
				$mail_p = new KKDMailer();
				$mail_p->ontvangers = array($declaratieReplyAddress, $declaratieReplyName);
				$mail_p->addCC($EBDeclaratieAddress);
				$mail_p->addCC($FinAdminAddress);
    			$mail_p->Subject	= "Declaratie $dagdeel ". date('j-n-Y', $dienst->start);
				$mail_p->bijlage	= array('file' => 'PDF/'. $mutatieNr .'.pdf', 'name' => $boekstukNummer .' '. $voorganger->getName(1) . ' '. date('d-m', $dienst->start) .' '. $dagdeel .'.pdf');	
				$mail_p->Body		= implode("<br>\n", $mailPenningsmeester);

				if(!$sendMail)	$mail_p->testen = true;

				if(!$mail_p->sendMail()) {
					toLog("Problemen met declaratie-notificatie (dienst ". $dienst->dienst .", voorganger ". $voorganger->id .")", 'error');
					$page[] = "Er zijn problemen met het versturen van de notificatie-mail naar penningsmeester.";
				} else {
					toLog("Declaratie-notificatie naar penningsmeester voor ". date('j-n-Y', $dienst->start), 'debug');
				}				
				
				
				# -------
				# Mail naar de predikant opstellen en versturen
				$mailPredikant = array();
				$mailPredikant[] = "Beste ". $voorganger->getName(5) .",";
				$mailPredikant[] = "";
				$mailPredikant[] = ($voorganger->vousvoyeren ? 'u heeft' : 'jij hebt')." online een declaratie ingediend voor het voorgaan in de $dagdeel van ". time2str('j F', $dienst->start)." in de Koningskerk te Deventer.";
				$mailPredikant[] = "Een samenvatting van deze declaratie voor in ". ($voorganger->vousvoyeren ? 'uw administratie treft u' : 'in je administratie tref je')." aan in de bijlage";
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
				$mail_v = new KKDMailer();
				$mail_v->ontvangers = array($voorganger->mail, $voorganger->getName(4));
				$mail_v->Subject	= "Declaratie $dagdeel ". date('j-n-Y', $dienst->start);
				$mail_v->From		= $declaratieReplyAddress;
				$mail_v->FromName	= $declaratieReplyName;
				$mail_v->Body		= implode("<br>\n", $mailPredikant);
				$mail_v->bijlage	= array('file' => 'PDF/'. $mutatieNr .'.pdf', 'name' => "Declaratie $dagdeel ". date('j-n-Y', $dienst->start) ." Koningskerk Deventer.pdf");
    			
				# Alle geadresseerden toevoegen
				if(!$sendMail)	$mail_v->testen = true;
				
				if(!$mail_v->sendMail()) {
					toLog("Problemen met declaratie-afschrift (dienst ". $dienst->dienst .", voorganger ". $voorganger->id .")", 'error');
					$page[] = "Er zijn problemen met het versturen van een afschrift van de declaratie.";
				} else {
					toLog("Declaratie-afschrift naar ". $voorganger->getName(4) ." voor ". date('j-n-Y', $dienst->start), 'debug');
					$page[] = "Er is een afschrift van de declaratie naar ". ($voorganger->vousvoyeren ? 'u' : 'jou') ." verstuurd.";
				}				
				
				# -------						
				# update vertekpunt voor volgende keer
				$voorganger->vertrekpunt = $_POST['reis_van'];
				$voorganger->save();
								
				# -------
				# Zet de status op afgerond
				$dienst->declaratieStatus = 8;
				$dienst->save();
				
				toLog("Declaratie ingediend voor ". $dagdeel .' van '. date('j-n-Y', $dienst->start) .' door '. $voorganger->getName(3));
			}
		} elseif(isset($_POST['check_iban'])) {
			# Formulier waar IBAN wordt gecontroleerd
			
			eb_getRelatieIbanByCode ($voorganger->boekhoud_id, $IBAN);

			$page[] = "Voer hieronder het bankrekening-nummer in waarnaar het bedrag moet worden overgemaakt.<br>";
			$page[] = "<br>";
			$page[] = "<input type='hidden' name='oorspronkelijke_IBAN' value='$IBAN' size='30'>";
			$page[] = "<input type='text' name='IBAN' value='$IBAN' size='25' placeholder='NL99XXXX0000000000'>";
			$page[] = "<br>";
			$page[] = "<input type='submit' name='indienen' value='Dien declaratie in'>";
			$page[] = "</form>";
		} elseif(isset($_POST['check_form'])) {
			# Formulier waar preekbeurt en reiskostenvergoeding ter controle worden getoond
			
			$totaal = $voorganger->honorarium;

			$page[] = "U staat op het punt de volgende declaratie in te dienen :<br>";
			$page[] = "<br>";
			$page[] = "<table>";
			$page[] = "	<tr>";
			$page[] = "		<td>Naam</td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td colspan='2'>". $voorganger->getName(3) ."</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td>Dienst</td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td colspan='2'>". formatDagdeel($dienst->start) ." ". time2str('j F Y', $dienst->start) ."</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td>Declaratie</td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td>Preekbeurt</td>";
			$page[] = "		<td align='right'>". formatPrice($voorganger->honorarium) ."</td>";
			$page[] = "	</tr>";
			
			if($voorganger->reiskosten) {
				$page[] = "	<tr>";
				$page[] = "		<td colspan='2'>&nbsp;</td>";
				$page[] = "		<td>Reiskosten<br><small>".$_POST['reis_van'] .' -> '. $_POST['reis_naar'] ." v.v.</small></td>";
				$page[] = "		<td align='right' valign='top'>". formatPrice($_POST['reiskosten']) ."</td>";
				$page[] = "	</tr>";
				
				$totaal = $totaal + $_POST['reiskosten'];
			}

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
			$dienst->declaratieStatus = 3;
			$dienst->save();
	
			# Formulier waar preekbeurt en reiskostenvergoeding kunnen worden ingevuld
			$next = false;

			$page[] = "<table border=0>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='6'><b>Preekbeurt</b></td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td width='20'>&nbsp;</td>";
			$page[] = "		<td colspan='3'>".formatDagdeel($dienst->start) ." ". time2str('j F Y', $dienst->start) ."</td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td align='right'>". formatPrice($voorganger->honorarium) ."</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='6'>&nbsp;</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='6'><b>Reiskostenvergoeding</b></td>";
			$page[] = "	</tr>";
			
			# Laat alleen zien als er reiskosten gedeclareerd kunnen worden
			if($voorganger->reiskosten) {				
				$page[] = "	<tr>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td>Van</td>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td>Naar</td>";
				$page[] = "		<td colspan='2'>&nbsp;</td>";
				$page[] = "	</tr>";
				$page[] = "	<tr>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td><input type='text' name='reis_van' value='". (!isset($_POST['reis_van']) ? $voorganger->vertrekpunt : $_POST['reis_van']) ."' size='30' placeholder='Adres en plaats van het vertrekpunt'></td>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td><input type='text' name='reis_naar' value='Mari&euml;nburghstraat 4, Deventer' size='30'></td>";
				$page[] = "		<td colspan='2'>&nbsp;</td>";
				$page[] = "	</tr>";
			} else {
				$page[] = "	<tr>";
				$page[] = "		<td colspan='6'>Reiskostenvergoeding is reeds opgenomen in het honorarium.</td>";
				$page[] = "	</tr>";
			}

			# Als reis_van en reis_naar bekend zijn, kan het aantal kilometers worden uitgerekend
			# en kan het volgende deel van het formulier getoond worden
			# �f als er geen reiskosten gedeclareerd kunnen worden
			if((isset($_POST['reis_van']) && isset($_POST['reis_naar']) && $voorganger->reiskosten) || !$voorganger->reiskosten) {
				$next = true;
				$first = true;
				
				if($voorganger->reiskosten) {
					$kms = determineAddressDistance($_POST['reis_van'], $_POST['reis_naar']);
					$km = array_sum($kms);
					
					$reiskosten = $km * $voorganger->km_vergoeding;
					
					$page[] = "	<tr>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "		<td><small>". round($km, 1) ." km x ". formatPrice($voorganger->km_vergoeding) ."</small></td>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "		<td align='right'>". formatPrice($reiskosten) ."</td>";
					$page[] = "		<input type='hidden' name='reiskosten' value='$reiskosten'>";
					$page[] = "		<input type='hidden' name='km' value='$km'>";
					$page[] = "	</tr>";
					$page[] = "	<tr>";
					$page[] = "		<td colspan='6'>&nbsp;</td>";
					$page[] = "	</tr>";
				}
				
				$page[] = "	<tr>";
				$page[] = "		<td colspan='6'><b>Overige</b></td>";
				$page[] = "	</tr>";
      	
				if(isset($_POST['overig'])) {
					$overige = $_POST['overig'];
					$prijs = $_POST['overig_price'];
				} else {
					$overige = array();
					$prijs = array(0 => '');
				}
      	
				$overige[] = '';
      	
				# Laat invoervelden voor overige zaken zien
				foreach($overige as $key => $string) {
					if($string != '' OR $first) {
						$page[] = "	<tr>";
						$page[] = "		<td>&nbsp;</td>";
						$page[] = "		<td colspan='3'><input type='text' name='overig[$key]' value='$string' size='50' placeholder='Omschrijving van eventuele extra kosten'></td>";
						$page[] = "		<td>&nbsp;</td>";
						$page[] = "		<td>&euro;&nbsp;<input type='text' name='overig_price[$key]' value='". str_replace(',', '.', $prijs[$key]) ."' size='5' placeholder='1,23'></td>";
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
		$page[] = "Deze link is niet (meer) geldig.<br><br>";
		$page[] = "U kunt via <a href='". $_SERVER['PHP_SELF'] ."?draad=predikant'>deze pagina</a> een nieuwe link aanvragen.<br><br>";
		$page[] = "Mocht dat het probleem niet oplossen dan kunt u contact opnemen met <a href='mailto:$ScriptMailAdress'>de webmaster</a>.";
	}
} elseif(isset($_POST['send_link'])) {
	# 2de scherm voor predikanten
	# Als er een dienst geselecteerd is wordt deze doorgemaild

	$dienst = new Kerkdienst($_POST['dienst']);
	$voorganger = new Voorganger($dienst->voorganger);
	
	if(!$voorganger->declaratie) {
		$page[] = 'Voor deze dienst is het niet mogelijk een declaratie in te dienen.<br>';
		$page[] = 'Mogelijke oorzaken zijn :';
		$page[] = "<ul>";
		$page[] = '<li>Er is in deze dienst geen gastpredikant voorgegaan.</li>';
		$page[] = '<li>Er is voor deze dienst al een declaratie ingediend.</li>';
		$page[] = "</ul>";
	} else {
		$dagdeel		= formatDagdeel($dienst->start);
		$aanspeekNaam	= $voorganger->getName(5);
		$mailNaam 		= $voorganger->getName(4);
		
		# Declaratielink genereren
		$declaratieLink = generateDeclaratieLink($dienst->dienst, $voorganger->id);

		# Mail opstellen
		$mailText = array();
		$mailText[] = "Beste $aanspeekNaam,";
		$mailText[] = "";
		$mailText[] = ($voorganger->vousvoyeren ? 'u heeft' : 'jij hebt')." online aangegeven een declaratie te willen indienen voor het voorgaan in de $dagdeel van ". time2str ('j F', $dienst->start)." in de Koningskerk te Deventer.";
		$mailText[] = "";
		$mailText[] = "Om zeker te weten dat alleen de juiste persoon de declaratie kan indienen wordt ".($voorganger->vousvoyeren ? 'u' : 'je')." verzocht onderstaande link te volgen, ".($voorganger->vousvoyeren ? 'u' : 'je')." wordt dat doorgeleid naar de juiste pagina.";
		$mailText[] = "<a href='$declaratieLink'>invoeren online declaratie</a>";
		$mailText[] = "";
		$mailText[] = "Mochten er nog vragen zijn dan hoor ik het graag.";
		$mailText[] = "";
		$mailText[] = "Vriendelijke groeten";
		$mailText[] = "";
		$mailText[] = $declaratieReplyName;
		$mailText[] = $declaratieReplyAddress;

		# Onderwerp maken
		$Subject = "Link naar declaratie-omgeving voor $dagdeel ". time2str('j M Y', $dienst->start);
		
		$mail = new KKDMailer();
		$mail->Body			= implode("<br>\n", $mailText);
		$mail->Subject		= trim($Subject);
		$mail->From			= $declaratieReplyAddress;
		$mail->FromName		= $declaratieReplyName;
		$mail->ontvangers	= array($voorgangerData['mail'], $mailNaam);
		$mail->testen		= true;

		if(!$mail->sendMail()) {
			toLog("Problemen met declaratie-link versturen naar $mailNaam voor ". date('j-n-Y', $dienstData['start']), 'error');
			$page[] = "Er zijn problemen met het versturen van de mail.";
		} else {
			toLog("Declaratie-link verstuurd naar $mailNaam voor ". date('j-n-y', $dienstData['start']), 'debug');
			$page[] = "Er is een mail gestuurd.";
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
	$diensten = Kerkdienst::getDiensten($startTijd, $eindTijd);

	foreach(array_reverse($diensten) as $dienstID) {
		$dienst = new Kerkdienst($dienstID);
		$dagdeel = formatDagdeel($dienst->start);
		$page[] = "<option value='$dienstID'>$dagdeel ". time2str('j M', $dienst->start) ."</option>";		
	}
	$page[] = "</select>";
	$page[] = "<p class='after_table'><input type='submit' name='send_link' value='Verstuur link'></p>";	
	$page[] = "</form>";
}


echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $page).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

# Aantekeningen zijn verplaatst naar aantekeningen.txt
?>
