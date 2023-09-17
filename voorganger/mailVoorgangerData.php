<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();

$sendMail = false;
$sendTestMail = false;
$test = true;

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres

if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP) OR $test) {
	$startTijd	= mktime(0, 0, 0, date("n")+1, 1, date("Y"));
	$eindTijd		= mktime(23, 59, 59, date("n")+2, 0, date("Y"));
	$diensten		= getKerkdiensten($startTijd, $eindTijd);
	
	$jaarGeleden = mktime(0, 0, 0, date("n")-12);
	
	foreach($diensten as $dienst) {
		$param					= array();		
		$dienstData			= getKerkdienstDetails($dienst);
		$voorgangerData = getVoorgangerData($dienstData['voorganger_id']);
						
		if(
				$dienstData['voorganger_id'] > 0 AND
				$voorgangerData['last_check'] < $jaarGeleden AND
				$voorgangerData['last_voorgaan'] > 0 AND
				$voorgangerData['declaratie'] == 1
			) {
			$voorgangerID = $dienstData['voorganger_id'];
			
			# Dus een array waarbij de key het ID van de voorganger is
			# en de value de wijze van aanspreken
			$voorgangers[$voorgangerID] = $voorgangerData['stijl'];
		}
	}
	
	foreach($voorgangers as $id => $stijl) {
		unset($mailText, $data);
		
		$data = getVoorgangerData($id);
		
		$hash = generateID(48);
		
		$mailText[] = "Beste ". makeVoorgangerName($id, 5) .",<br>";
		$mailText[] = "<br>";
		$mailText[] = ($stijl == 0 ? 'u' : 'jij'). " gaat volgende maand voor in de Koningskerk te Deventer.<br>";
		$mailText[] = "Om die reden wil ik controleren of alle gegegevens die wij van ". ($stijl == 0 ? 'u' : 'jouw') ." in ons systeem hebben staan nog correct zijn.<br>";
		$mailText[] = "<br>";
		$mailText[] = "Op dit moment ". ($stijl == 0 ? 'staat u' : 'sta je'). " o.a. met de volgende gegevens in ons systeem:<br>";
		$mailText[] = "<table>";
		$mailText[] = "<tr>\n<td width=25>&nbsp;</td>\n<td>Titel</td>\n<td>&nbsp;</td>\n<td>". $data['titel'] ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Initialen</td>\n<td>&nbsp;</td>\n<td>". $data['init'] ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Voornaam</td>\n<td>&nbsp;</td>\n<td>". $data['voor'] ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Tussenvoegsel</td>\n<td>&nbsp;</td>\n<td>". $data['tussen'] ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Achternaam</td>\n<td>&nbsp;</td>\n<td>". $data['achter'] ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Mailadres</td>\n<td>&nbsp;</td>\n<td>". $data['mail'] ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Plaats</td>\n<td>&nbsp;</td>\n<td>". $data['plaats'] ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Denominatie</td>\n<td>&nbsp;</td>\n<td>". $data['denom'] ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Aanspreekstijl</td>\n<td>&nbsp;</td>\n<td>". ($data['stijl'] == 0 ? 'Vousvoyeren' : 'Tutoyeren' ) ."</td></tr>";
		$mailText[] = "</table>";
		$mailText[] = "<br>";
		$mailText[] = "Zijn deze gegevens nog correct?<br>";
		$mailText[] = "&nbsp;<a href='checkVoorgangerData.php?hash=$hash&correct=true'>Ja</a> (komend jaar ". ($stijl == 0 ? 'krijgt u' : 'krijg je'). " geen mail meer)<br>";
		$mailText[] = "&nbsp;<a href='checkVoorgangerData.php?hash=$hash&correct=false'>Nee</a> (". ($stijl == 0 ? 'u' : 'je'). " komt op een pagina om de juiste gegevens in te voeren)<br>";
		
		$sql = "UPDATE $TableVoorganger SET $VoorgangerHash = '$hash' WHERE $VoorgangerID = $id";
		$result = mysqli_query($db, $sql);
		
		$param['to'][] = array($data['mail'], makeVoorgangerName($id, 4));
		$param['subject']				= 'Controle gegevens';
		$param['message'] 			= implode("\n", $mailText);;
		$param['from']					= $noReplyAdress;
		$param['fromName']			= "Webmaster Koningskerk";
		$param['ReplyTo']				= $ScriptMailAdress;
		$param['testen']				= true;
		
		sendMail_new($param);
	}
	
} else {
	toLog('error', '', '', 'Poging handmatige run vorgangermail, IP:'.$_SERVER['REMOTE_ADDR']);
}
?>
