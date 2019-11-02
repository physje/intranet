<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');
include_once('../../../general_include/class.phpmailer.php');

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres
if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP)) {
	$startTijd	= mktime(0, 0, 0);
	$eindTijd		= mktime(23, 59, 59);	
	$diensten		= getKerkdiensten($startTijd, $eindTijd);
	
	foreach($diensten as $dienst) {
		$dienstData	= getKerkdienstDetails($dienst);
		$voorganger = $dienstData['voorganger_id'];
		$voorgangerData = getVoorgangerData($voorganger);
		
		if($voorgangerData['declaratie'] == 1) {
			$dagdeel		= formatDagdeel($dienstData['start']);
			$aanspeekNaam		= makeVoorgangerName($voorganger, 5);
			$mailNaam 			= makeVoorgangerName($voorganger, 4);
			
			# Nieuw mail-object aanmaken
			$mail = new PHPMailer;
			$mail->FromName	= $declaratieReplyName;
			$mail->From			= $declaratieReplyAddress;
			
			# Geadresseerden toevoegen
			//$mail->AddAddress($voorgangerData['mail'], $mailNaam);
			$mail->AddAddress('internet@draijer.org');
			
			# Declaratielink genereren
			$hash = urlencode(password_hash($dienst.'$'.$randomCodeDeclaratie.'$'.$voorganger, PASSWORD_BCRYPT));
			$declaratieLink = $ScriptURL ."declaratie/index.php?hash=$hash&d=$dienst&draad=". $_REQUEST['draad'] ."&v=$voorganger";
			
			# Mail opstellen
			$mailText = array();
			$mailText[] = "Beste $aanspeekNaam,";
			$mailText[] = "";
			$mailText[] = "Vandaag ". ($voorgangerData['stijl'] == 0 ? 'gaat u' : 'ga jij')." voor in de $dagdeel in de Koningskerk te Deventer.";
			$mailText[] = "";
			$mailText[] = ($voorgangerData['stijl'] == 0 ? 'U' : 'Jij')." kan daarvoor online een declaratie indienen.";
			$mailText[] = "Volg <a href='$declaratieLink'>deze link</a> om bij de persoonlijke declaratie-omgeving uit te komen.";
			$mailText[] = "";
			$mailText[] = "Mochten er nog vragen zijn dan hoor ik het graag.";
			$mailText[] = "";
			$mailText[] = "Vriendelijke groeten";
			$mailText[] = "";
			$mailText[] = $declaratieReplyName;
			$mailText[] = $declaratieReplyAddress;
			
			# Onderwerp maken
			$Subject = "Online declaratie-formulier $dagdeel ". date('j-n-Y', $dienstData['start']);
			
			$mail->Subject	= trim($Subject);
			$mail->IsHTML(true);
			$mail->Body	= $MailHeader.implode("<br>\n", $mailText).$MailFooter;
			
			if(!$mail->Send()) {
				toLog('error', '', '', "Problemen met versturen online declaratie-formulier naar $mailNaam");
			} else {
				toLog('info', '', '', "Online declaratie-formulier verstuurd naar $mailNaam");
			}
			
			setVoorgangerDeclaratieStatus(2, $dienst);			
		}		
	}
}