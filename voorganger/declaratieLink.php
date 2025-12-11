<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');
include_once('../Classes/Voorganger.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/KKDMailer.php');
include_once('../Classes/Logging.php');

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres

if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP)) {
	$startTijd	= mktime(0, 0, 0);
	$eindTijd	= mktime(23, 59, 59);	
	$diensten	= Kerkdienst::getDiensten($startTijd, $eindTijd);
	
	foreach($diensten as $dienstID) {
		$dienst = new Kerkdienst($dienstID);
		$voorganger = new Voorganger($dienst->voorganger);
				
		if($voorganger->declaratie && !$dienst->ruiling) {
			$dagdeel		= formatDagdeel($dienst->start);
									
			# Declaratielink genereren
			$declaratieLink = generateDeclaratieLink($dienst->dienst, $voorganger->id);
			$afzienLink 	= generateDeclaratieLink($dienst->dienst, $voorganger->id, true);
			
			# Mail opstellen
			$mailText = array();
			$mailText[] = "Beste ". $voorganger->getName(5) .",";
			$mailText[] = "";
			$mailText[] = "Vandaag ". ($voorganger->vousvoyeren ? 'gaat u' : 'ga jij')." voor in de $dagdeel van de Koningskerk te Deventer.";
			$mailText[] = "";
			$mailText[] = "De Koningskerk heeft een digitale declaratie-omgeving waar gast-predikanten hun declaratie kunnen indienen.";
			$mailText[] = "Voordeel hiervan is dat waar mogelijk gegevens al zijn ingevuld, dat de declaratie direct in de boekhouding komt (wat de doorloop-tijd verkort) en dat ". ($voorganger->vousvoyeren ? 'u' : 'jij') ." een PDF-document voor de administratie in ". ($voorganger->vousvoyeren ? 'uw' : 'jouw') ." mailbox krijgt.";
			$mailText[] = "";
			$mailText[] = "<b>Declaratie $dagdeel ". time2str('j F', $dienst->start) ."</b>";
			$mailText[] = "Om de persoonlijke digitale declaratie-omgeving voor deze dienst te bereiken ". ($voorganger->vousvoyeren ? 'kunt u' : 'kun jij') ." <a href='$declaratieLink'>hier</a> klikken.";
			$mailText[] = "";
			$mailText[] = "<b>Afzien van declaratie</b>";
			$mailText[] = "Mocht ".($voorganger->vousvoyeren ? 'u' : 'je')." willen afzien van declaratie, dan kan ".($voorganger->vousvoyeren ? 'u' : 'je')." dat middels <a href='$afzienLink'>deze link</a> aangeven, de declaratie zal dan als afgehandeld worden geregistreerd.";
			$mailText[] = "";
			$mailText[] = "<b>Declaratie eerdere dienst</b>";
			$mailText[] = "In de digitale declaratie-omgeving is het ook mogelijk vorige diensten te declareren. Ga daarvoor naar <a href='". $ScriptURL ."declaratie/gastpredikant.php'>deze site</a> en selecteer de dienst die ". ($voorganger->vousvoyeren ? 'u' : 'je') ." wilt declararen. Neem contact op met de <a href='mailto:$ScriptMailAdress'>de webmaster</a> mocht de dienst niet meer in de lijst staan.";			
			$mailText[] = "";			
			$mailText[] = "Mochten er nog vragen zijn dan horen wij het graag.";
			$mailText[] = "";
			$mailText[] = "Voor technische vragen ". ($voorganger->vousvoyeren ? 'kunt u' : 'kun je') ." contact opnemen met <a href='mailto:$ScriptMailAdress'>de webmaster</a>, voor financiele vragen met de penningmeester via onderstaand mailadres.";
			$mailText[] = "";
			$mailText[] = "Vriendelijke groeten";
			$mailText[] = $declaratieReplyName;
			$mailText[] = $declaratieReplyAddress;
			
			# Mail object aanmaken
			$mail = new KKDMailer();
			$mail->ontvangers[] = array($voorganger->mail, $voorganger->getName(4));
			$mail->Subject	= "Online declaratie-formulier $dagdeel ". date('j-n-Y', $dienstData['start']);
			$mail->From		= $declaratieReplyAddress;
			$mail->FromName	= $declaratieReplyName;
			$mail->Body		= implode("<br>\n", $mailText);
			//TODO: Testen uitzetten
			$mail->testen	= true;
			
			# Mail versturen
			if($mail->sendMail()) {
				toLog("Problemen met versturen online declaratie-formulier naar ". $voorganger->getName(4), 'error');
			} else {
				toLog("Online declaratie-formulier verstuurd naar naar ". $voorganger->getName(4));
			}

			# Declaratie-status bijwerken						
			$dienst->declaratieStatus = 2;			
			$dienst->save();
		}		
	}
}

?>
