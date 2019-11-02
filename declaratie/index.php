<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');
include_once('../../../general_include/class.phpmailer.php');

$db = connect_db();

if(isset($_REQUEST['draad'])) {
	if($_REQUEST['draad'] == 'predikant') {
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
				if(isset($_REQUEST['draad']))		$page[] = "<input type='hidden' name='draad' value='". $_REQUEST['draad'] ."'>";
				if(isset($dienst))							$page[] = "<input type='hidden' name='d' value='$dienst'>";
				if(isset($voorganger))					$page[] = "<input type='hidden' name='v' value='$voorganger'>";
				if(isset($_REQUEST['hash']))		$page[] = "<input type='hidden' name='hash' value='". $_REQUEST['hash'] ."'>";
				if(isset($_POST['reiskosten']))	$page[] = "<input type='hidden' name='reiskosten' value='". $_POST['reiskosten'] ."'>";
				if(isset($_POST['reis_van']))		$page[] = "<input type='hidden' name='reis_van' value='". $_POST['reis_van'] ."'>";
				if(isset($_POST['reis_naar']))	$page[] = "<input type='hidden' name='reis_naar' value='". $_POST['reis_naar'] ."'>";

				if(isset($_POST['overig']))	{
					foreach($_POST['overig'] as $key => $string) {
						$page[] = "<input type='hidden' name='overig[$key]' value='$string'>";
						$page[] = "<input type='hidden' name='overig_price[$key]' value='". $_POST['overig_price'][$key] ."'>";
					}
				}

				if(isset($_POST['indienen'])) {
					# Scherm waarbij de declaratie wordt ingevoerd
					# Alle betrokkenen een mail krijgen
					# Data in de database wordt weggeschreven

					$page[] = "Hopsakee";
					$page[] = "<ul>";
					$page[] = "	<li>PDF maken</li>";
					$page[] = "	<li>op basis van IBAN relatie opzoeken -> relatie toevoegen of IBAN aanpassen indien nodig</li>";
					$page[] = "	<li>in eBoekhouden schieten</li>";
					$page[] = "	<li>mail naar predikant</li>";
					$page[] = "	<li>mail naar Paul</li>";
					$page[] = "	<li>reis_van en relatie in dB schrijven voor volgende keer</li>";
					$page[] = "</ul>";
					
					# PDF maken
					# -> komt nog
					
					# Relatie bepalen
					# -> Kan als relatie al lokaal bekend is, dan mogelijk IBAN updaten
					# -> Als relatie nog niet bekend was, opzoeken of aanmaken en dan in lokale database toevoegen (kan samen met reis_van)
					
					# In eboekhouden inschieten
					# -> Even overleggen

					# Paar dingen definieren voor zometeen
					$aanspeekNaam	= makeVoorgangerName($voorganger, 5);
					$mailNaam			= makeVoorgangerName($voorganger, 4);
					$dagdeel			= formatDagdeel($dienstData['start']);

					# Mail naar de predikant
					$mailPredikant = array();
					$mailPredikant[] = "Beste $aanspeekNaam,";
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
					//$mail->AddAddress($voorgangerData['mail'], $mailNaam);
					$mail->AddAddress('internet@draijer.org');
					
					# Onderwerp maken
					$Subject = "Declaratie $dagdeel ". date('j-n-Y', $dienstData['start']);
					
					$mail->Subject	= trim($Subject);
					$mail->IsHTML(true);
					$mail->Body	= $MailHeader.implode("<br>\n", $mailPredikant).$MailFooter;
					
					if(!$mail->Send()) {
						toLog('error', '', '', "Problemen met declaratie-afschrift (dienst $dienst, voorganger $voorganger)");
						$page[] = "Er zijn problemen met het versturen van een afschrift van de declaratie.";
					} else {
						toLog('info', '', '', "Declaratie-afschrift naar $mailNaam voor ". date('j-n-Y', $dienstData['start']));
						$page[] = "Er is een afschrift van de declaratie naar ". ($voorgangerData['stijl'] == 0 ? 'u' : 'jou') ." verstuurd.";
					}					
					
					# Mail naar de peningsmeester
					$mailPenningsmeester = array();
					$mailPenningsmeester[] = "Beste,";
					$mailPenningsmeester[] = "";
					$mailPenningsmeester[] = makeVoorgangerName($voorganger, 3) .' heeft een declaratie ingediend.';
					$mailPenningsmeester[] = "";
					$mailPenningsmeester[] = "Het betreft de $dagdeel van ". date('d M Y', $dienstData['start']);
					$mailPenningsmeester[] = "<table>";
					$mailPenningsmeester[] = "	<tr>";
					$mailPenningsmeester[] = "		<td>Declaratie</td>";
					$mailPenningsmeester[] = "		<td>&nbsp;</td>";
					$mailPenningsmeester[] = "		<td>Preekbeurt</td>";
					$mailPenningsmeester[] = "		<td align='right'>". formatPrice($voorgangerData['honorarium']) ."</td>";
					$mailPenningsmeester[] = "	</tr>";
					$mailPenningsmeester[] = "	<tr>";
					$mailPenningsmeester[] = "		<td colspan='2'>&nbsp;</td>";
					$mailPenningsmeester[] = "		<td>Reiskosten<br><small>".$_POST['reis_van'] .' -> '. $_POST['reis_naar'] ."</small></td>";
					$mailPenningsmeester[] = "		<td align='right' valign='top'>". formatPrice($_POST['reiskosten']) ."</td>";
					$mailPenningsmeester[] = "	</tr>";

					$totaal = $voorgangerData['honorarium'] + $_POST['reiskosten'];

					foreach($_POST['overig'] as $key => $string) {
						if($string != '') {
							$mailPenningsmeester[] = "	<tr>";
							$mailPenningsmeester[] = "		<td colspan='2'>&nbsp;</td>";
							$mailPenningsmeester[] = "		<td>$string</td>";
							$mailPenningsmeester[] = "		<td align='right'>". formatPrice(100*str_replace(',', '.', $_POST['overig_price'][$key])) ."</td>";
							$mailPenningsmeester[] = "	</tr>";

							$totaal = $totaal + 100*str_replace(',', '.', $_POST['overig_price'][$key]);
						}
					}

					$mailPenningsmeester[] = "	<tr>";
					$mailPenningsmeester[] = "		<td colspan='2'>&nbsp;</td>";
					$mailPenningsmeester[] = "		<td><b>Totaal</b></td>";
					$mailPenningsmeester[] = "		<td align='right'><b>". formatPrice($totaal) ."</b></td>";
					$mailPenningsmeester[] = "	</tr>";
					$mailPenningsmeester[] = "</table>";
					
					# Nieuw mail-object aanmaken
					unset($mail);
					$mail = new PHPMailer;
					$mail->FromName	= $ScriptTitle;
					$mail->From			= $ScriptMailAdress;

					# Alle geadresseerden toevoegen
					//$mail->AddAddress($declaratieReplyAddress, $declaratieReplyName);
					$mail->AddAddress('internet@draijer.org');
					
					# Onderwerp maken
					$Subject = "Declaratie $dagdeel ". date('j-n-Y', $dienstData['start']);
					
					$mail->Subject	= trim($Subject);
					$mail->IsHTML(true);
					$mail->Body	= $MailHeader.implode("<br>\n", $mailPenningsmeester).$MailFooter;
					
					if(!$mail->Send()) {
						toLog('error', '', '', "Problemen met declaratie-notificatie (dienst $dienst, voorganger $voorganger)");
						$page[] = "Er zijn problemen met het versturen van de notificatie-mail naar penningsmeester.";
					} else {
						toLog('info', '', '', "Declaratie-notificatie naar penningsmeester voor ". date('j-n-Y', $dienstData['start']));						
					}
					
					# update reis_van voor volgende keer
					$sql = "UPDATE $TableVoorganger SET $VoorgangerVertrekpunt = '". urlencode($_POST['reis_van']) ."' WHERE $VoorgangerID like '$voorganger'";
					mysqli_query($db, $sql);
					
					# Zet de status op afgerond
					setVoorgangerDeclaratieStatus(8, $dienst);					
				} elseif(isset($_POST['check_iban'])) {
					# Formulier waar IBAN wordt gecontroleerd

					$IBAN = '';

					$page[] = "Voer hieronder het bankrekening-nummer in waarnaar het bedrag moet worden overgemaakt.<br>";
					$page[] = "<br>";
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
					$page[] = "		<td colspan='2'>". formatDagdeel($dienstData['start']) ." ". date('d M Y', $dienstData['start']) ."</td>";
					$page[] = "	</tr>";
					$page[] = "	<tr>";
					$page[] = "		<td>Declaratie</td>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "		<td>Preekbeurt</td>";
					$page[] = "		<td align='right'>". formatPrice($voorgangerData['honorarium']) ."</td>";
					$page[] = "	</tr>";
					$page[] = "	<tr>";
					$page[] = "		<td colspan='2'>&nbsp;</td>";
					$page[] = "		<td>Reiskosten<br><small>".$_POST['reis_van'] .' -> '. $_POST['reis_naar'] ."</small></td>";
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
					$page[] = "		<td colspan='3'>".formatDagdeel($dienstData['start']) ." ". date('d M Y', $dienstData['start']) ."</td>";
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
						$km = determineAdressDistance($_POST['reis_van'], $_POST['reis_naar']);

						$reiskosten = 2*$km * $voorgangerData['km_vergoeding'];

						$page[] = "	<tr>";
						$page[] = "		<td>&nbsp;</td>";
						$page[] = "		<td><small>". round(2*$km, 1) ." km x ". formatPrice($voorgangerData['km_vergoeding']) ."</small></td>";
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
				//$mail->AddAddress($voorgangerData['mail'], $mailNaam);
				$mail->AddAddress('internet@draijer.org');

				# Declaratielink genereren
				$hash = urlencode(password_hash($dienst.'$'.$randomCodeDeclaratie.'$'.$voorganger, PASSWORD_BCRYPT));
				$declaratieLink = $ScriptURL ."declaratie/index.php?hash=$hash&d=$dienst&draad=". $_REQUEST['draad'] ."&v=$voorganger";

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
				$Subject = "Declaratie $dagdeel ". date('j-n-Y', $dienstData['start']);

				$mail->Subject	= trim($Subject);
				$mail->IsHTML(true);
				$mail->Body	= $MailHeader.implode("<br>\n", $mailText).$MailFooter;

				if(!$mail->Send()) {
					toLog('error', '', '', "Problemen met declaratie-link versturen naar $mailNaam voor ". date('j-n-Y', $dienstData['start']));
					$page[] = "Er zijn problemen met het versturen van de mail.";
					$page = array_merge($page, $mailText);
				} else {
					toLog('info', '', '', "Declaratie-link verstuurd naar $mailNaam voor ". date('j-n-Y', $dienstData['start']));
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
			$page[] = "<input type='hidden' name='draad' value='". $_REQUEST['draad'] ."'>";
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
				$page[] = "<option value='$dienst'>$dagdeel ". date('d M', $dienstData['start']) ."</option>";
			}
			$page[] = "</select><br>";
			$page[] = "<br>";
			$page[] = "<input type='submit' name='send_link' value='Verstuur link'>";
			$page[] = "</form>";
		}

	} elseif($_REQUEST['draad'] == 'gemeentelid') {
		# Scherm voor gemeenteleden

		$page[] = "Momenteel is dat nog niet mogelijk.<br>";
		$page[] = "De wens is er wel, dus hopelijk op een later moment.<br>";
	}
} else {
	# Het eerste scherm waarin men de keuze kan maken welk type declaratie men wil uitvoeren

	$page[] = "In welke hoedanigheid wilt u een declaratie doen?<br>";
	$page[] = "<ul>";
	$page[] = "<li><a href='?draad=predikant'>Gastpredikant</a></li>";
	$page[] = "<li><a href='?draad=gemeentelid'>Gemeentelid</a></li>";
	$page[] = "</ul>";
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
