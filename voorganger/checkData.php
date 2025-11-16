<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Voorganger.php');
include_once('../Classes/KKDMailer.php');
include_once('../Classes/Logging.php');

if(isset($_REQUEST['hash'])) {
	$hash = $_REQUEST['hash'];
	$voorgangerID = isValidVoorgangerHash($hash);
	
	if(is_numeric($voorgangerID)) {
		$voorganger = new Voorganger($voorgangerID);
		
		if(isset($_REQUEST['correct']) AND $_REQUEST['correct'] == 'true') {
			$voorganger->last_data = time();			
			
			if($voorganger->save()) {
				$correct[] = 'Dank voor de bevestiging.';
				$correct[] = '<p>';
				$correct[] = 'Niet eerder dan over een jaar zullen wij weer controleren of de gegevens juist zijn. Mocht er in de tussentijd iets wijzigen, dan kan '. ($voorganger->vousvoyeren ? 'u' : 'je'). " dat via <a href='checkData.php?hash=". $voorganger->hash ."&correct=false'>deze link</a> alsnog wijzigen.";
				
				toLog($voorganger->getName(4) .' ['. $voorganger->id .'] heeft de voorgangersdata bevestigt');
			} else {
				$correct[] = 'Helaas kon de bevestiging niet worden opgeslagen.';
				$correct[] = 'Er is een bericht gestuurd naar de webmaster. Deze zal uitzoeken wat er aan de hand is.';
				
				toLog('Bevestiging data door '. $voorganger->getName(4) .' ['. $voorganger->id .'] mislukt', 'error');
			}
			 
			$blocks[] = $correct;
		} elseif(isset($_REQUEST['correct']) AND $_REQUEST['correct'] == 'false') {
			$opslaan = false;
						
			if(isset($_REQUEST['save_data'])) {				
				$opslaan = true;
				
				if($_POST['init'] == '' AND $_POST['voor'] == '') {
					$opslaan = false;
					$melding[] = 'Voornaam of initialen moet zijn ingevuld';
				}
				
				if($_POST['achter'] == '') {
					$opslaan = false;
					$melding[] = 'Achternaam moet zijn ingevuld';
				}
				
				if(!isValidEmail($_POST['mail'])) {
					$opslaan = false;
					$melding[] = 'Mailadres is niet geldig';
				}
				
				if($_POST['plaats'] == '') {
					$opslaan = false;
					$melding[] = 'Plaats moet zijn ingevuld';
				}

				if($_POST['denom'] == '') {
					$opslaan = false;
					$melding[] = 'Denominatie moet zijn ingevuld';
				}								
			}
			
			if($opslaan) {
				$voorganger->aanhef			= $_POST['titel'];
				$voorganger->voornaam		= $_POST['voor'];
				$voorganger->initialen		= $_POST['init'];
				$voorganger->tussenvoegsel	= $_POST['tussen'];
				$voorganger->achternaam		= $_POST['achter'];
				$voorganger->telefoon		= $_POST['tel'];
				$voorganger->mobiel			= $_POST['tel2'];
				$voorganger->preekvoorziener = $_POST['pvnaam'];
				$voorganger->preekvoorziener_telefoon = $_POST['pvtel'];
				$voorganger->mail			= $_POST['mail'];
				$voorganger->plaats			= $_POST['plaats'];
				$voorganger->denominatie	= $_POST['denom'];
				$voorganger->opmerkingen	= $_POST['opm'];
				$voorganger->vousvoyeren	= ($_POST['stijl'] == 1 ? '1' : '0');
				$voorganger->last_data		= time();
											
				if($voorganger->save()) {
					$inCorrect[] = 'De gewijzigde gegevens zijn opgeslagen.';
					$inCorrect[] = '<p>';
					$inCorrect[] = 'Niet eerder dan over een jaar zullen wij weer controleren of de gegevens juist zijn. Mocht er in de tussentijd iets wijzigen, dan kan '. ($voorganger->vousvoyeren ? 'u' : 'je'). " dat via <a href='checkData.php?hash=". $voorganger->hash ."&correct=false'>deze link</a> alsnog wijzigen.";
				
					toLog('Gegevens voorganger ('. $voorganger->id .') bijgewerkt door '. $voorganger->getName(4));
				} else {
					$correct[] = 'Helaas kon de gegevens niet worden opgeslagen.';
					$correct[] = 'Er is een bericht gestuurd naar de webmaster. Deze zal uitzoeken wat er aan de hand is.';				
					toLog('Gegevens voorganger ('.  $voorganger->id .') konden niet worden opgeslagen door '. $voorganger->getName(4), 'error');
				}
			} else {
				# Als er meldingen zijn (lees, check van gegevens is niet succesvol)
				# deze meldingen laten zien
				if(isset($melding)) {
					foreach($melding as $melding_text) {
						$inCorrect[] = '<b>'. $melding_text .'</b><br>';
					}
					$inCorrect[] = '<p>';
				} 
				
				# Formulier laten zien.
				$inCorrect[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
				#$left[] = "<input type='hidden' name='voorgangerID' value='". $voorgangerID ."'>";
				$inCorrect[] = "<input type='hidden' name='hash' value='". $voorganger->hash ."'>";
				$inCorrect[] = "<input type='hidden' name='correct' value='false'>";			
				$inCorrect[] = "<table>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Titel</td>";
				$inCorrect[] = "	<td><input type='text' name='titel' value='". getParam('titel', $voorganger->aanhef) ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Initialen</td>";
				$inCorrect[] = "	<td><input type='text' name='init' value='". getParam('init', $voorganger->initialen) ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Voornaam</td>";
				$inCorrect[] = "	<td><input type='text' name='voor' value='". getParam('voor', $voorganger->voornaam) ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Tussenvoegsel</td>";
				$inCorrect[] = "	<td><input type='text' name='tussen' value='". getParam('tussen', $voorganger->tussenvoegsel) ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Achternaam</td>";
				$inCorrect[] = "	<td><input type='text' name='achter' value='". getParam('achter', $voorganger->achternaam) ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Telefoonnummer</td>";
				$inCorrect[] = "	<td><input type='text' name='tel' value='". getParam('tel', $voorganger->telefoon) ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Mailadres</td>";
				$inCorrect[] = "	<td><input type='text' name='mail' value='". getParam('mail', $voorganger->mail) ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Plaats</td>";
				$inCorrect[] = "	<td><input type='text' name='plaats' value='". getParam('plaats', $voorganger->plaats) ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Denominatie</td>";
				$inCorrect[] = "	<td><input type='text' name='denom' value='". getParam('denom', $voorganger->denominatie) ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Mobiel</td>";
				$inCorrect[] = "	<td><input type='text' name='tel2' value='". getParam('tel2', $voorganger->mobiel) ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Naam preekvoorziener</td>";
				$inCorrect[] = "	<td><input type='text' name='pvnaam' value='". getParam('pvnaam', $voorganger->preekvoorziener) ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Telefoon preekvoorziener</td>";
				$inCorrect[] = "	<td><input type='text' name='pvtel' value='". getParam('pvtel', $voorganger->preekvoorziener_telefoon) ."'></td>";
				$inCorrect[] = "</tr>";		
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td valign='top'>Opmerking</td>";
				$inCorrect[] = "	<td><textarea name='opm'>". getParam('opm', $voorganger->opmerkingen) ."</textarea></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Aanspreekstijl</td>";
				$inCorrect[] = "	<td><select name='stijl'>";
				$inCorrect[] = "	<option value='0'". ($voorganger->vousvoyeren ? ' selected' : '') .">Vousvoyeren</option>";
				$inCorrect[] = "	<option value='1'". (!$voorganger->vousvoyeren ? ' selected' : '') .">Tutoyeren</option>";		
				$inCorrect[] = "	</select></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "</table>";
				$inCorrect[] = "<p class='after_table'><input type='submit' name='save_data' value='Gegevens opslaan'></p>";
				$inCorrect[] = "</form>";
			}
			
			$blocks[] = $inCorrect;
		}		
	} else {
		toLog('Poging check vorgangersdata met ongeldige hash', 'error');
	}	
} else {
	toLog('Poging check vorgangersdata zonder hash', 'error');
}

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;

foreach($blocks as $block) {
	echo "<div class='content_block'>". implode(NL, $block) ."</div>".NL;
}

echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();
