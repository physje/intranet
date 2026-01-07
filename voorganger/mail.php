<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
#include_once('../include/HTML_HeaderFooter.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Voorganger.php');
include_once('../Classes/Vulling.php');
include_once('../Classes/Rooster.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');
include_once('../Classes/KKDMailer.php');

$sendMail = true;
$sendTestMail = false;
$test = false;

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres

if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP) OR $test) {
	$startTijd	= mktime(0, 0, 0, date("n"), (date("j")+18), date("Y"));
	$eindTijd	= mktime(23, 59, 59, date("n"), (date("j")+18), date("Y"));	
	$diensten	= Kerkdienst::getDiensten($startTijd, $eindTijd);
	
	foreach($diensten as $dienstID) {
		$dienst		= new Kerkdienst($dienstID);
		$voorganger = new Voorganger($dienst->voorganger);
				
		$aOuderling		= new Vulling($dienstID, 7);		
		$aBeamer		= new Vulling($dienstID, 11);		
		$aSchriftlezer	= new Vulling($dienstID, 12);
		$aKoster		= new Vulling($dienstID, 13);		
		$aBandleider	= new Vulling($dienstID, 22);
		#$aJeugd			= new Vulling($dienstID, 25);

		$bandleider		= new Member($aBandleider->leden[0]);
		$ouderling		= new Member($aOuderling->leden[0]);		
		$beameraar		= new Member($aBeamer->leden[0]);
		$koster			= new Member($aKoster->leden[0]);
		
		if(count($aSchriftlezer->leden) > 0) {
			$schriftlezer	= new Member($aSchriftlezer->leden[0]);
		}	
		
		#if(count($aJeugd->leden) > 0) {
		#	$jeugdmoment	= new Member($aJeugd->leden[0]);
		#}
		
		$dagdeel 		= formatDagdeel($dienst->start);
		
		$aanspeekNaam			= $voorganger->getName(5);
		$mailNaam 				= $voorganger->getName(4);
		$voorgangerAchterNaam	= $voorganger->getName(7);
		
		# Als er geen mailadres van de voorganger bekend is		
		if(isValidEmail($voorganger->mail)) {
			# Nieuw mail-object aanmaken
			$KKD = new KKDMailer();
			$KKD->FromName = 'Preekvoorziening Koningskerk Deventer';
			$KKD->addReplyTo($voorgangerReplyAddress, $voorgangerReplyName);
			if(!$productieOmgeving) $KKD->testen = true;
						
			# Als er niet getest wordt
			if(!$sendTestMail) {
				# Alle geadresseerden toevoegen
				$KKD->addAddress($voorganger->mail, $mailNaam);
				$KKD->addCC($bandleider->getMail(), $bandleider->getName());				
				$KKD->addCC($ouderling->getMail(), $ouderling->getName());
				if(isset($schriftlezer)) {
					$KKD->addCC($schriftlezer->getMail(), $schriftlezer->getName());
				}				
								
				# Als Reinier geen voorganger is, gemeentelid voor jeugdmoment in de CC
				#if($dienst->voorganger != 91) {					
				#	$KKD->addCC($jeugdmoment->getMail(), $jeugdmoment->getName());
				#}
								
				# CC toevoegen
				foreach($voorgangerCC as $adres => $naam) {
					$KKD->addCC($adres, $naam);
				}
				
				# BCC toevoegen
				foreach($voorgangerBCC as $adres => $naam) {
					$KKD->addBCC($adres, $naam);
				}
			} else {
				$KKD->addAddress($ScriptMailAdress);
			}
							
			# Mail opstellen
			$mailText = $bijlageText = array();
			
			$lastTekstUpdate = mktime(23,59,59,1,1,2025);
			
			if($voorganger->last_voorgaan < $lastTekstUpdate) {
				$mailText[] = "<b>LET OP: onderstaande tekst is gewijzigd tov vorige keer.</b>";
				$mailText[] = "";
			}
				
			$mailText[] = "Beste $aanspeekNaam en anderen met een taak in de eredienst,";
			$mailText[] = "";
			$mailText[] = "Jullie staan op het rooster voor de $dagdeel van ". time2str('l j F', $dienst->start)." om ". date('H:i', $dienst->start)." uur in de Koningskerk te Deventer.";
						
			if(isset($ouderling)) {
				$mailText[] = "";
				$mailText[] = "<i>Ouderling van Dienst</i>";
				$mailText[] = "In deze dienst is ". $ouderling->getName() ." ouderling van dienst. Als het een speciale Eredienst betreft, waarin bijvoorbeeld gedoopt wordt, zal ". $ouderling->getName(1) ." als ouderling van dienst afstemming zoeken om nadere details te bespreken.";
			}
			
			if(isset($bandleider)) {
				$mailText[] = "";
				$mailText[] = "<i>Bandleider</i>";				
				$mailText[] = "De muzikale begeleiding wordt geco&ouml;rdineerd door ". $bandleider->getName() ." (". $bandleider->getMail() ."). Wij waarderen het als predikant en bandleider de interactie zoeken over de liturgie. ".($voorganger->vousvoyeren ? 'Wilt u' : 'Wil jij').", ". $aanspeekNaam ." als voorganger in de week voorafgaand ".($voorganger->vousvoyeren ? 'uw' : 'jouw')." voorstel voor liturgie met liederen, preekthema en bijbelteksten met ". $bandleider->getName(1) ." delen? ". ($bandleider->geslacht == 'M' ? 'Hij' : 'Zij') ." kan eventueel suggesties aandragen en helpen inschatten of liederen goed uit te voeren zijn (dit ivm niveau muzikanten, bekendheid van het lied in de gemeente, of dat een lied zeer recent al vaker is gezongen). Als er Engelse liederen worden gebruikt willen we graag dat de vertaling in het Nederlands erbij staat. Uiterlijk op ". time2str('l', ($dienst->start - (4*24*60*60)))."avond moet de liturgie bekend en gedeeld zijn.";
				
				# Reinier heeft zelf ID 91
				if($dienst->voorganger != 91) {
					$mailText[] = "De liturgie graag ook delen met onze eigen predikant ds. Reinier Kramer. Hij leest graag mee in het kader van integraliteit en overlap van opvolgende Erediensten.";	
				}
				$mailText[] = "";				
			} else {
				$mailText[] = "";
			}
								
			$opsomming = array();
			$opsomming[] = "<i>Andere taken</i>";
			$opsomming[] = "<ul>";
			$opsomming[] = "<li>".(isset($ouderling->id) ? $ouderling->getName() ." zal als ouderling van dienst" : "De ouderling van dienst zal"). " de mededelingen voorafgaand aan de dienst verzorgen.</li>";
			$opsomming[] = "<li>".(isset($schriftlezer->id) ? "De schriftlezing wordt gedaan door ". $schriftlezer->getName() : "Het is nog niet bekend wie de schriftlezing doet").". Wij gebruiken de vertaling NBV21.</li>";
			$opsomming[] = "<li>".(isset($beameraar->id) ? "De beamer wordt bediend door ". $beameraar->getName() : "Het is nog niet bekend wie de beamer bedient").".</li>";
			$opsomming[] = "<li>".(isset($koster->id)? $koster->getName() ." is koster" : "Het is nog niet bekend wie de koster is").".</li>";
			$opsomming[] = "<li>Aankondiging/toelichting op de collecte en het collectegebed, wordt gedaan door de diaken van dienst.</li>";
			$opsomming[] = "<li>". (isset($ouderling->id) ? $ouderling->getName() : "De ouderling van dienst"). " verzorgt het dankgebed en de voorbeden (meestal aansluitend aan het collectegebed)</li>";
			$opsomming[] = "<li>De dienst wordt live vertaald door het vertaalteam voor gemeenteleden die de Nederlandse taal nog niet machtig zijn. Het helpt het vertaalteam als bij het verzenden van de liturgie ook de preek of een preekschets wordt toegevoegd zodat men zich kan voorbereiden.</li>";
						
			# Reinier heeft ID 91
			if($dienst->voorganger != 91) {
				$opsomming[] = "<li>Buiten de vakanties om gaan kinderen van groep 3 tot en met groep 8 voor de schriftlezing naar de bijbelklas of basiscatechese. Voordat de kinderen gaan is er een kindermoment. Dit mag ".($voorganger->vousvoyeren ? 'u' : 'je')." zelf verzorgen maar hiervoor ".($voorganger->vousvoyeren ? 'kan u' : 'kun je')." ook Henrike Nijman (<a href='mailto:henrike.nijman@koningskerkdeventer.nl'>henrike.nijman@koningskerkdeventer.nl</a>) of Pieter Wierenga (<a href='mailto:pietwierenga@kpnplanet.nl'>pietwierenga@kpnplanet.nl</a>) vragen.</li>";
			}
			$opsomming[] = "</ul>";
			
			$mailText[] = implode("\n", $opsomming);			
			$mailText[] = "";
			$mailText[] = "<i>Communicatie</i>";			
			$mailText[] = "Als je deze mail beantwoordt aan \"allen\", zijn alle  betrokkenen op tijd op de hoogte."; 			
			$mailText[] = "";
			$mailText[] = "Daarnaast is het goed om te vermelden dat de kerkdienst online te volgen en terug te kijken is. Hiervoor maken wij gebruiken van de diensten van Kerkdienstgemist en YouTube.";
												
			if($voorganger->declaratie && $dienst->ruiling == 0) {
				$mailText[] = "";
				$mailText[] = "<i>Declaratie</i>";
				$mailText[] = "Op ". time2str ('l j F', $dienst->start).' '.($voorganger->vousvoyeren ? 'ontvangt u' : 'ontvang je') ." in de ochtend een link naar ". ($voorganger->vousvoyeren ? 'uw' : 'jouw') ." persoonlijke digitale declaratie-omgeving voor het declareren van ".($voorganger->vousvoyeren ? 'uw' : 'jouw')." onkosten.";
				$dienst->declaratieStatus = 1;
			}
		
			# Elke keer de aandachtspunten mailen is wat overdreven. Eens in de 6 weken lijkt mij mooi
			$aandachtPeriode = mktime(23,59,59,date("n")-(6*7));
			$lastUpdate = mktime(23,59,59,11,05,2021);
			
			if($voorganger->aandachtspunt && ($voorganger->last_aandacht < $aandachtPeriode || $voorganger->last_aandacht < $lastUpdate)) {
				$KKD->addAttachment('../download/aandachtspunten.pdf', 'Aandachtspunten Liturgie Deventer (dd 05-11-2021).pdf');
				$bijlageText[] = "de aandachtspunten van de dienst";				
				$voorganger->last_aandacht = time();				
			}
					
			if(count($bijlageText) > 0) {
				$mailText[] = "";
				$mailText[] = "In de bijlage ".($voorganger->vousvoyeren ? 'treft u' : 'tref je')." ". implode(' en ', $bijlageText) ." aan.";
			}
			
			$mailText[] = "";
			$mailText[] = "Als er onduidelijkheid is of er zijn vragen dan kan ". ($voorganger->vousvoyeren ? 'u' : 'je') ." contact opnemen met ". ($ouderling->id > 0 ? "de ouderling van dienst via <a href='mailto:". $ouderling->getName() ." <". $ouderling->getMail() .">'>mail</a>, met " : ""). "Sander Lagendijk als Clustercoo&ouml;rdinator Eredienst (<a href='tel:+31612586835'>06-12586835</a>) of met mij.";
			$mailText[] = "";
			$mailText[] = "Vriendelijke groeten";
			$mailText[] = "";
			$mailText[] = "Paula Lieffijn-Joosse";
			$mailText[] = "Tel.: 06-29052411";
			$mailText[] = $voorgangerReplyAddress;		
			
			# Onderwerp maken			
			$Subject = "Introductie en toelichting voor komende Eredienst in de Koningskerk Deventer";
			
			if($sendMail) {		
				$KKD->Subject = trim($Subject);
				$KKD->Body = implode("<br>\n", $mailText);
				
				if(!$KKD->Sendmail()) {
					toLog("Problemen met voorgangersmail versturen naar $mailNaam voor ". date('j-n-Y', $dienst->start), 'error');
					echo "Problemen met mail versturen<br>\n";
				} else {
					toLog("Voorgangersmail verstuurd naar $mailNaam voor ". date('j-n-Y', $dienst->start));
					echo "Mail verstuurd naar $mailNaam<br>\n";
				}								
			}
		
			$voorganger->last_voorgaan = $dienst->start;
			
			$dienst->save();
			$voorganger->save();
		} elseif(trim($voorganger->mail) == '') {
			toLog("Geen voorgangersmail verstuurd voor ". $dagdeel .' van '. date('j-n', $dienst->start) ." omdat geen voorganger bekend is");
		} else {
			toLog("Kon geen voorgangersmail versturen voor ". $dagdeel .' van '. date('j-n', $dienst->start) .", ongeldig mailadres", 'error');
		}
	}
} else {
	toLog('Poging handmatige run vorgangermail, IP:'.$_SERVER['REMOTE_ADDR'], 'error');
}
?>
