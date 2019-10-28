<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../../../general_include/class.phpmailer.php');
$db = connect_db();

if(isset($_REQUEST['draad'])) {
	if($_REQUEST['draad'] == 'predikant') {
		if(isset($_REQUEST['hash'])) {
			# 3de scherm voor predikanten
			# De direct-link uit de declaratie-mail komt hier terecht
			
			$hash = urldecode($_REQUEST['hash']);
			$dienst = $_REQUEST['d'];
			$voorganger = $_REQUEST['v'];
			
			if(password_verify($dienst.'$'.$voorganger,$hash)) {
				$dienstData = getKerkdienstDetails($dienst);
				$voorgangerData = getVoorgangerData($voorganger);
				
				//$page[] = 'En we zijn binnen';
				
				$page[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
				$page[] = "<input type='hidden' name='draad' value='". $_REQUEST['draad'] ."'>";
				$page[] = "<input type='hidden' name='d' value='$dienst'>";
				$page[] = "<input type='hidden' name='v' value='$voorganger'>";
				$page[] = "<input type='hidden' name='hash' value='". $_REQUEST['hash'] ."'>";
				$page[] = "<table border=1>";
				$page[] = "	<tr>";
				$page[] = "		<td colspan='6'><b>Preekbeurt</b></td>";
				$page[] = "	</tr>";
				$page[] = "	<tr>";
				$page[] = "		<td width='20'>&nbsp;</td>";
				$page[] = "		<td colspan='3'>".formatDagdeel($dienstData['start']) ." ". date('d M Y', $dienstData['start']) ."</td>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td>". formatPrice($preekbeurt) ."</td>";
				$page[] = "	</tr>";
				$page[] = "	<tr>";
				$page[] = "		<td colspan='6'>&nbsp;</td>";
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
				$page[] = "		<td><input type='text' name='reis_van' value='' size='30'></td>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td><input type='text' name='reis_naar' value='Mariënburghstraat 4, Deventer' size='30'></td>";				
				$page[] = "		<td colspan='2'>&nbsp;</td>";
				$page[] = "	</tr>";
				
				if(isset($_POST['reis_van']) AND isset($_POST['reis_naar'])) {
					$km = 125;
					
					$page[] = "	<tr>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "		<td>$km km x € 0,35</td>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "		<td>". formatPrice($reiskosten) ."</td>";
					$page[] = "	</tr>";
					$page[] = "	<tr>";
					$page[] = "		<td colspan='6'>&nbsp;</td>";
					$page[] = "	</tr>";
					$page[] = "	<tr>";
					$page[] = "		<td colspan='6'><b>Overige</b></td>";
					$page[] = "	</tr>";
					$page[] = "	<tr>";
					$page[] = "		<td colspan='6'>&nbsp;</td>";
					$page[] = "	</tr>";
				}
				

				
				$page[] = "</table>";	
				
				$page[] = "<br>";
				$page[] = "<input type='submit' name='check_form' value='Volgende'>";
				$page[] = "</form>";
				
			} else {
				# Direct-link om te declareren is niet correct
				
				$page[] = 'Deze link is niet correct';
			}			
		} elseif(isset($_POST['send_link'])) {
			# 2de scherm voor predikanten
			# Als er een dienst geselecteerd is wordt deze doorgemails
			
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
				
				/*
				if(date("H", $dienstData['start']) < 12) {
					$dagdeel = 'morgendienst';
				} elseif(date("H", $dienstData['start']) < 18) {
					$dagdeel = 'middagdienst';
				} else {
					$dagdeel = 'avonddienst';
				}
				*/
								
				# Achternaam
				$voorgangerAchterNaam = '';
				if($voorgangerData['tussen'] != '')	$voorgangerAchterNaam = lcfirst($voorgangerData['tussen']).' ';	
				$voorgangerAchterNaam .= $voorgangerData['achter'];
				
				# Naam voor voorganger in de mail
				if($voorgangerData['voor'] != "") {
					$aanspeekNaam = $voorgangerData['voor'];
					$mailNaam = $voorgangerData['voor'].' '.$voorgangerAchterNaam;
				} else {
					$aanspeekNaam = lcfirst($voorgangerData['titel']).' '.$voorgangerAchterNaam;
					$mailNaam = $voorgangerData['init'].' '.$voorgangerAchterNaam;
				}
				
				# Nieuw mail-object aanmaken
				$mail = new PHPMailer;
				$mail->FromName	= 'Penningmeester Koningskerk Deventer';
				$mail->From			= $ScriptMailAdress;
				//$mail->AddReplyTo($voorgangerReplyAddress, $voorgangerReplyName);
				
				# Alle geadresseerden toevoegen
				$mail->AddAddress($voorgangerData['mail'], $mailNaam);
				
				# Declaratielink genereren
				$hash = urlencode(password_hash($dienst.'$'.$voorganger, PASSWORD_BCRYPT));
				$declaratieLink = $ScriptURL ."declaratie/index.php?hash=$hash&d=$dienst&draad=". $_REQUEST['draad'] ."&v=$voorganger";
				
				# Mail opstellen
				$mailText = $bijlageText = array(); 
				$mailText[] = "Beste $aanspeekNaam,";
				$mailText[] = "";
				$mailText[] = "U heeft online aangegeven een declaratie te willen indienen voor het voorgaan in de $dagdeel van ". strftime ('%e %B', $dienstData['start'])." in de Koningskerk te Deventer.";
				$mailText[] = "";
				$mailText[] = "Om zeker te weten dat alleen de juiste persoon de declaratie kan indienen wordt u verzocht onderstaande link te volgen, u wordt dat doorgeleid naar de juiste pagina.";
				$mailText[] = "<a href='$declaratieLink'>invoeren online declaratie</a>";
				$mailText[] = "";
				$mailText[] = "Mochten er nog vragen zijn dan hoor ik het graag.";
				$mailText[] = "";
				$mailText[] = "Vriendelijke groeten";
				$mailText[] = "";
				$mailText[] = "Paul Huizing";
				$mailText[] = "paul.huizing@koningskerkdeventer.nl";
				
				$page = $mailText;
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
