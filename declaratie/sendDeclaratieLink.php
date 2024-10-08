<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres

if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP)) {
	$startTijd	= mktime(0, 0, 0);
	$eindTijd		= mktime(23, 59, 59);	
	$diensten		= getKerkdiensten($startTijd, $eindTijd);
	
	foreach($diensten as $dienst) {
		$param = array();
		$dienstData	= getKerkdienstDetails($dienst);
		$voorganger = $dienstData['voorganger_id'];
		$voorgangerData = getVoorgangerData($voorganger);
				
		if($voorgangerData['declaratie'] == 1 AND $dienstData['ruiling'] == 0) {
			$dagdeel		= formatDagdeel($dienstData['start']);
			$aanspeekNaam		= makeVoorgangerName($voorganger, 5);
			$mailNaam 			= makeVoorgangerName($voorganger, 4);
			
						
			# Declaratielink genereren
			$declaratieLink = generateDeclaratieLink($dienst, $voorganger);
			$afzienLink = generateDeclaratieLink($dienst, $voorganger, true);
			
			# Mail opstellen
			$mailText = array();
			$mailText[] = "Beste $aanspeekNaam,";
			$mailText[] = "";
			$mailText[] = "Vandaag ". ($voorgangerData['stijl'] == 0 ? 'gaat u' : 'ga jij')." voor in de $dagdeel van de Koningskerk te Deventer.";
			$mailText[] = "";
			$mailText[] = "De Koningskerk heeft een digitale declaratie-omgeving waar gast-predikanten hun declaratie kunnen indienen.";
			$mailText[] = "Voordeel hiervan is dat waar mogelijk gegevens al zijn ingevuld, dat de declaratie direct in de boekhouding komt (wat de doorloop-tijd verkort) en dat ". ($voorgangerData['stijl'] == 0 ? 'u' : 'jij') ." een PDF-document voor de administratie in ". ($voorgangerData['stijl'] == 0 ? 'uw' : 'jouw') ." mailbox krijgt.";
			$mailText[] = "";
			$mailText[] = "<b>Declaratie $dagdeel ". time2str('%e %B', $dienstData['start']) ."</b>";
			$mailText[] = "Om de persoonlijke digitale declaratie-omgeving voor deze dienst te bereiken ". ($voorgangerData['stijl'] == 0 ? 'kunt u' : 'kun jij') ." <a href='$declaratieLink'>hier</a> klikken.";
			$mailText[] = "";
			$mailText[] = "<b>Afzien van declaratie</b>";
			$mailText[] = "Mocht ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." willen afzien van declaratie, dan kan ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." dat middels <a href='$afzienLink'>deze link</a> aangeven, de declaratie zal dan als afgehandeld worden geregistreerd.";
			$mailText[] = "";
			$mailText[] = "<b>Declaratie eerdere dienst</b>";
			$mailText[] = "In de digitale declaratie-omgeving is het ook mogelijk vorige diensten te declareren. Ga daarvoor naar <a href='". $ScriptURL ."declaratie/gastpredikant.php'>deze site</a> en selecteer de dienst die ". ($voorgangerData['stijl'] == 0 ? 'u' : 'je') ." wilt declararen. Neem contact op met de <a href='mailto:$ScriptMailAdress'>de webmaster</a> mocht de dienst niet meer in de lijst staan.";			
			$mailText[] = "";			
			$mailText[] = "Mochten er nog vragen zijn dan horen wij het graag.";
			$mailText[] = "";
			$mailText[] = "Voor technische vragen ". ($voorgangerData['stijl'] == 0 ? 'kunt u' : 'kun je') ." contact opnemen met <a href='mailto:$ScriptMailAdress'>de webmaster</a>, voor financiele vragen met de penningmeester via onderstaand mailadres.";
			$mailText[] = "";
			$mailText[] = "Vriendelijke groeten";
			$mailText[] = $declaratieReplyName;
			$mailText[] = $declaratieReplyAddress;
			
			# Onderwerp maken
			$Subject = "Online declaratie-formulier $dagdeel ". date('j-n-Y', $dienstData['start']);
						
			$param['to'][] = array($voorgangerData['mail'], $mailNaam);
			$param['from'] = $declaratieReplyAddress;
			$param['fromName'] = $declaratieReplyName;
			$param['subject'] = trim($Subject);
			$param['message'] = implode("<br>\n", $mailText);
			
			if(!sendMail_new($param)) {
				toLog('error', '', "Problemen met versturen online declaratie-formulier naar $mailNaam");
			} else {
				toLog('info', '', "Online declaratie-formulier verstuurd naar $mailNaam");
			}
						
			setVoorgangerDeclaratieStatus(2, $dienst);			
		}		
	}
}
