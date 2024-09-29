<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();

if(isset($_REQUEST['hash'])) {
	$hash = $_REQUEST['hash'];
	$sql = "SELECT $VoorgangerID FROM $TableVoorganger WHERE $VoorgangerHash like '$hash'";
	$result = mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 1) {
		$row = mysqli_fetch_array($result);
		$voorgangerID = $row[$VoorgangerID];
		$voorgangerData = getVoorgangerData($voorgangerID);
		
		if(isset($_REQUEST['correct']) AND $_REQUEST['correct'] == 'true') {
			$sql = "UPDATE $TableVoorganger SET $VoorgangerLastDataCheck = '". time() ."' WHERE $VoorgangerID = $voorgangerID";
			
			if(mysqli_query($db, $sql)) {
				$correct[] = 'Dank voor de bevestiging.';
				$correct[] = '<p>';
				$correct[] = 'Niet eerder dan over een jaar zullen wij weer controleren of de gegevens juist zijn. Mocht er in de tussentijd iets wijzigen, dan kan '. ($voorgangerData['stijl'] == 0 ? 'u' : 'je'). " dat via <a href='checkVoorgangerData.php?hash=$hash&correct=false'>deze link</a> alsnog wijzigen.";
				
				toLog('info', '', '', makeVoorgangerName($voorgangerID, 4) .' ['. $voorgangerID .'] heeft de voorgangersdata bevestigt');
			} else {
				$correct[] = 'Helaas kon de bevestiging niet worden opgeslagen.';
				$correct[] = 'Er is een bericht gestuurd naar de webmaster. Deze zal uitzoeken wat er aan de hand is.';
				
				toLog('error', '', 'Bevestiging data door '. makeVoorgangerName($voorgangerID, 4) .' ['. $voorgangerID .'] mislukt');
			}
			 
			$blocks[] = $correct;
		} elseif(isset($_REQUEST['correct']) AND $_REQUEST['correct'] == 'false') {
			$opslaan = false;
			$firstData = getVoorgangerData($voorgangerID);
			
			if(isset($_REQUEST['save_data'])) {
				$voorgangerData['titel']		= getParam('titel', $firstData['titel']);
				$voorgangerData['voor']			= getParam('voor', $firstData['voor']);
				$voorgangerData['init']			= getParam('init', $firstData['init']);
				$voorgangerData['tussen']		= getParam('tussen', $firstData['tussen']);
				$voorgangerData['achter']		= getParam('achter', $firstData['achter']);
				$voorgangerData['tel']			= getParam('tel', $firstData['tel']);
				$voorgangerData['tel2']			= getParam('tel2', $firstData['tel2']);
				$voorgangerData['pv_naam']	= getParam('pvnaam', $firstData['pv_naam']);
				$voorgangerData['pv_tel']		= getParam('pvtel', $firstData['pv_tel']);
				$voorgangerData['mail']			= getParam('mail', $firstData['mail']);
				$voorgangerData['plaats']		= getParam('plaats', $firstData['plaats']);
				$voorgangerData['denom']		= getParam('denom', $firstData['denom']);
				$voorgangerData['opm']			= getParam('opm', $firstData['opm']);
				$voorgangerData['stijl']		= getParam('stijl', $firstData['stijl']);
					
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
								
			} else {
				$voorgangerData = $firstData;
			}
			
			if($opslaan) {
				$sql = "UPDATE $TableVoorganger SET ";
				$sql .= "$VoorgangerTitel = '". $_POST['titel'] ."', ";
				$sql .= "$VoorgangerVoor = '". $_POST['voor'] ."', ";
				$sql .= "$VoorgangerInit = '". $_POST['init'] ."', ";
				$sql .= "$VoorgangerTussen = '". $_POST['tussen'] ."', ";
				$sql .= "$VoorgangerAchter = '". $_POST['achter'] ."', ";
				$sql .= "$VoorgangerTel = '". $_POST['tel'] ."', ";
				$sql .= "$VoorgangerTel2 = '". $_POST['tel2'] ."', ";
				$sql .= "$VoorgangerPVNaam = '". $_POST['pvnaam'] ."', ";
				$sql .= "$VoorgangerPVTel = '". $_POST['pvtel'] ."', ";
				$sql .= "$VoorgangerMail = '". $_POST['mail'] ."', ";
				$sql .= "$VoorgangerPlaats = '". $_POST['plaats'] ."', ";
				$sql .= "$VoorgangerDenom = '". $_POST['denom'] ."', ";
				$sql .= "$VoorgangerOpmerking = '". $_POST['opm'] ."', ";
				$sql .= "$VoorgangerStijl = '". $_POST['stijl'] ."', ";		
				$sql .= "$VoorgangerLastDataCheck = ". time()." ";
				$sql .= "WHERE $VoorgangerID = ". $voorgangerID;
							
				if(mysqli_query($db, $sql)) {
					$inCorrect[] = 'De gewijzigde gegevens zijn opgeslagen.';
					$inCorrect[] = '<p>';
					$inCorrect[] = 'Niet eerder dan over een jaar zullen wij weer controleren of de gegevens juist zijn. Mocht er in de tussentijd iets wijzigen, dan kan '. ($voorgangerData['stijl'] == 0 ? 'u' : 'je'). " dat via <a href='checkVoorgangerData.php?hash=$hash&correct=false'>deze link</a> alsnog wijzigen.";
				
					toLog('info', '', 'Gegevens voorganger ('. $voorgangerID .') bijgewerkt door '. makeVoorgangerName($voorgangerID, 4));
				} else {
					$correct[] = 'Helaas kon de gegevens niet worden opgeslagen.';
					$correct[] = 'Er is een bericht gestuurd naar de webmaster. Deze zal uitzoeken wat er aan de hand is.';				
					toLog('error', '', 'Gegevens voorganger ('. $voorgangerID .') konden niet worden opgeslagen door '. makeVoorgangerName($voorgangerID, 4));
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
				$inCorrect[] = "<input type='hidden' name='hash' value='". $hash ."'>";
				$inCorrect[] = "<input type='hidden' name='correct' value='false'>";			
				$inCorrect[] = "<table>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Titel</td>";
				$inCorrect[] = "	<td><input type='text' name='titel' value='". $voorgangerData['titel'] ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Initialen</td>";
				$inCorrect[] = "	<td><input type='text' name='init' value='". $voorgangerData['init'] ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Voornaam</td>";
				$inCorrect[] = "	<td><input type='text' name='voor' value='". $voorgangerData['voor'] ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Tussenvoegsel</td>";
				$inCorrect[] = "	<td><input type='text' name='tussen' value='". $voorgangerData['tussen'] ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Achternaam</td>";
				$inCorrect[] = "	<td><input type='text' name='achter' value='". $voorgangerData['achter'] ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Telefoonnummer</td>";
				$inCorrect[] = "	<td><input type='text' name='tel' value='". $voorgangerData['tel'] ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Mailadres</td>";
				$inCorrect[] = "	<td><input type='text' name='mail' value='". $voorgangerData['mail'] ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Plaats</td>";
				$inCorrect[] = "	<td><input type='text' name='plaats' value='". $voorgangerData['plaats'] ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Denominatie</td>";
				$inCorrect[] = "	<td><input type='text' name='denom' value='". $voorgangerData['denom'] ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Mobiel</td>";
				$inCorrect[] = "	<td><input type='text' name='tel2' value='". $voorgangerData['tel2'] ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Naam preekvoorziener</td>";
				$inCorrect[] = "	<td><input type='text' name='pvnaam' value='". $voorgangerData['pv_naam'] ."'></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Telefoon preekvoorziener</td>";
				$inCorrect[] = "	<td><input type='text' name='pvtel' value='". $voorgangerData['pv_tel'] ."'></td>";
				$inCorrect[] = "</tr>";		
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td valign='top'>Opmerking</td>";
				$inCorrect[] = "	<td><textarea name='opm'>". $voorgangerData['opm'] ."</textarea></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "<tr>";
				$inCorrect[] = "	<td>Aanspreekstijl</td>";
				$inCorrect[] = "	<td><select name='stijl'>";
				$inCorrect[] = "	<option value='0'". ($voorgangerData['stijl'] == 0 ? ' selected' : '') .">Vousvoyeren</option>";
				$inCorrect[] = "	<option value='1'". ($voorgangerData['stijl'] == 1 ? ' selected' : '') .">Tutoyeren</option>";		
				$inCorrect[] = "	</select></td>";
				$inCorrect[] = "</tr>";
				$inCorrect[] = "</table>";
				$inCorrect[] = "<p class='after_table'><input type='submit' name='save_data' value='Gegevens opslaan'></p>";
				$inCorrect[] = "</form>";
			}
			
			$blocks[] = $inCorrect;
		}
		
		
	} else {
		toLog('error', '', 'Poging check vorgangersdata met ongeldige hash');
	}	
} else {
	toLog('error', '', 'Poging check vorgangersdata zonder hash');
}

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;

foreach($blocks as $block) {
	echo "<div class='content_block'>". implode(NL, $block) ."</div>".NL;
}

echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();
