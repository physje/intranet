<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();

$sendMail = true;
$sendTestMail = false;
$test = false;

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres

if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP) OR $test) {
	$startTijd	= mktime(0, 0, 0, date("n"), (date("j")+18), date("Y"));
	$eindTijd		= mktime(23, 59, 59, date("n"), (date("j")+18), date("Y"));	
	$diensten		= getKerkdiensten($startTijd, $eindTijd);
	
	foreach($diensten as $dienst) {
		$param					= array();		
		$dienstData			= getKerkdienstDetails($dienst);
		$voorgangerData = getVoorgangerData($dienstData['voorganger_id']);
				
		$aBandleider		= getRoosterVulling(22, $dienst);
		$bandleider			= $aBandleider[0];
		$bandData				= getMemberDetails($bandleider);
		$adresBand			= getMailAdres($bandleider);
				
		$aSchriftlezer	= getRoosterVulling(12, $dienst);
		$schriftlezer		= $aSchriftlezer[0];
		$adresSchrift		= getMailAdres($schriftlezer);
		
		$aOuderling			= getRoosterVulling(7, $dienst);
		$ouderling			= $aOuderling[0];
		$adresOuderling	= getMailAdres($ouderling, true);		
		
		$aJeugd					= getRoosterVulling(25, $dienst);
		$jeugdmoment		= $aJeugd[0];
		$adresJeugd			= getMailAdres($jeugdmoment, true);		
		
		$aBeamer				= getRoosterVulling(11, $dienst);
		$beameraar			= $aBeamer[0];
		
		$aKoster				= getRoosterVulling(13, $dienst);
		$koster					= $aKoster[0];
		
		$dagdeel 				= formatDagdeel($dienstData['start']);
		
		$aanspeekNaam		= makeVoorgangerName($dienstData['voorganger_id'], 5);
		$mailNaam 			= makeVoorgangerName($dienstData['voorganger_id'], 4);
		$voorgangerAchterNaam = makeVoorgangerName($dienstData['voorganger_id'], 7);
		
		# Als er geen mailadres van de voorganger bekend is		
		if(isValidEmail($voorgangerData['mail'])) {
			# Nieuw mail-object aanmaken
			unset($param);
		
			$param['fromName'] = 'Preekvoorziening Koningskerk Deventer';
			$param['ReplyTo'] = $voorgangerReplyAddress;
			$param['ReplyToName'] = $voorgangerReplyName;
			
			# Als er niet getest wordt
			if(!$sendTestMail) {
				# Alle geadresseerden toevoegen
				$param['to'][] = array($voorgangerData['mail'], $mailNaam);
				$param['cc'][] = array($adresBand, makeName($bandleider, 6));
				$param['cc'][] = array($adresSchrift, makeName($schriftlezer, 6));
				$param['cc'][] = array($adresOuderling, makeName($ouderling, 6));
				
				# Als Reinier geen voorganger is, gemeentelid voor jeugdmoment in de CC
				if($dienstData['voorganger_id'] != 91) {
					$param['cc'][] = array($adresJeugd, makeName($jeugdmoment, 6));
				}
								
				# CC toevoegen
				foreach($voorgangerCC as $adres => $naam) {
					$param['cc'][] = array($adres, $naam);
				}
				
				# BCC toevoegen
				foreach($voorgangerBCC as $adres => $naam) {
					$param['bcc'][] = $adres;
				}
			} else {
				$param['to'][] = array($ScriptMailAdress);	
			}
							
			# Mail opstellen
			$mailText = $bijlageText = array();
			
			$lastTekstUpdate = mktime(23,59,59,1,1,2025);
			
			if($voorgangerData['last_voorgaan'] < $lastTekstUpdate) {
				$mailText[] = "<b>LET OP: onderstaande tekst is gewijzigd tov vorige keer.</b>";
				$mailText[] = "";
			}
				
			$mailText[] = "Beste $aanspeekNaam en anderen met een taak in de eredienst,";
			$mailText[] = "";
			$mailText[] = "Jullie staan op het rooster voor de $dagdeel van ". time2str ('%A %e %B', $dienstData['start'])." om ". date('H:i', $dienstData['start'])." uur in de Koningskerk te Deventer.";
						
			if($ouderling > 0) {
				$mailText[] = "";
				$mailText[] = "<i>Ouderling van Dienst</i>";
				$mailText[] = "In deze dienst is ". makeName($ouderling, 5) ." ouderling van dienst. Als het een speciale Eredienst betreft, waarin bijvoorbeeld gedoopt wordt, zal ". makeName($ouderling, 1) ." als ouderling van dienst afstemming zoeken om nadere details te bespreken.";
			}
			
			if($bandleider > 0) {
				$mailText[] = "";
				$mailText[] = "<i>Bandleider</i>";				
				$mailText[] = "De muzikale begeleiding wordt geco&ouml;rdineerd door ". makeName($bandleider, 5) ." ($adresBand). Wij waarderen het als predikant en bandleider de interactie zoeken over de liturgie. ".($voorgangerData['stijl'] == 0 ? 'Wilt u' : 'Wil jij').", ". $aanspeekNaam ." als voorganger in de week voorafgaand ".($voorgangerData['stijl'] == 0 ? 'uw' : 'jouw')." voorstel voor liturgie met liederen, preekthema en bijbelteksten met ". makeName($bandleider, 1) ." delen? ". ($bandData['geslacht'] == 'M' ? 'Hij' : 'Zij') ." kan eventueel suggesties aandragen en helpen inschatten of liederen goed uit te voeren zijn (dit ivm niveau muzikanten, bekendheid van het lied in de gemeente, of dat een lied zeer recent al vaker is gezongen). Als er Engelse liederen worden gebruikt willen we graag dat de vertaling in het Nederlands erbij staat. Uiterlijk op ". time2str('%A', ($dienstData['start'] - (4*24*60*60)))."avond moet de liturgie bekend en gedeeld zijn.";
				
				# Reinier heeft zelf ID 91
				if($dienstData['voorganger_id'] != 91) {
					$mailText[] = "De liturgie graag ook delen met onze eigen predikant ds. Reinier Kramer. Hij leest graag mee in het kader van integraliteit en overlap van opvolgende Erediensten.";	
				}
				$mailText[] = "";				
			} else {
				$mailText[] = "";
			}
								
			$opsomming = array();
			$opsomming[] = "<i>Andere taken</i>";
			$opsomming[] = "<ul>";
			$opsomming[] = "<li>".($ouderling > 0 ? makeName($ouderling, 5) ." zal als ouderling van dienst" : "De ouderling van dienst zal"). " de mededelingen voorafgaand aan de dienst verzorgen.</li>";
			$opsomming[] = "<li>".($schriftlezer > 0 ? "De schriftlezing wordt gedaan door ". makeName($schriftlezer, 5) : "Het is nog niet bekend wie de schriftlezing doet").". Wij gebruiken de vertaling NBV21.</li>";
			$opsomming[] = "<li>".($beameraar > 0 ? "De beamer wordt bediend door ". makeName($beameraar, 5) : "Het is nog niet bekend wie de beamer bedient").".</li>";
			$opsomming[] = "<li>".($koster > 0 ? makeName($koster, 5) ." is koster" : "Het is nog niet bekend wie de koster is").".</li>";
			$opsomming[] = "<li>Aankondiging/toelichting op de collecte en het collectegebed, wordt gedaan door de diaken van dienst.</li>";
			$opsomming[] = "<li>De ouderling van dienst verzorgt het dankgebed en de voorbeden (meestal aansluitend aan het collectegebed)</li>";
			$opsomming[] = "<li>De dienst wordt live vertaald door het vertaalteam voor gemeenteleden die de Nederlandse taal nog niet machtig zijn. Het helpt het vertaalteam als bij het verzenden van de liturgie ook de preek of een preekschets wordt toegevoegd zodat men zich kan voorbereiden.</li>";
			$opsomming[] = "</ul>";
			
			# Reinier heeft ID 91
			if($dienstData['voorganger_id'] != 91 AND $jeugdmoment > 0) {
				$opsomming[] = "Voorafgaand aan de schriftlezing gaat een gedeelte van de basisschoolkinderen naar de bijbelklas of basiscatechese. Wij zijn gewend dat er in de Eredienst vaak een moment speciaal aandacht is voor kinderen of jeugd. Dit kan een kindermoment zijn voor de schriftlezing en voordat een deel van de kinderen naar bijbelklas of basiscatechese gaan. Dit kan ook in de vorm van een jeugdmoment zijn. Bij een gastpredikant is hier standaard een gemeentelid voor ingeroosterd die in de week voorafgaand contact met ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." zal opnemen over vorm en onderwerp. Voor deze dienst is ". makeName($jeugdmoment, 5) ." daarvoor als gemeentelid ingeroosterd.";
			}
			
			$mailText[] = implode("\n", $opsomming);			
			$mailText[] = "";
			$mailText[] = "<i>Communicatie</i>";			
			$mailText[] = "Als je deze mail beantwoordt aan \"allen\", zijn alle  betrokkenen op tijd op de hoogte.";
 			#$mailText[] = "";
			#$mailText[] = "(Het gaat in ieder geval om de volgende mailadressen die aangeschreven moeten worden:";
			#$mailText[] = "reinier.kramer@koningskerkdeventer.nl";
			#$mailText[] = "preekvoorziening@koningskerkdeventer.nl";
			#$mailText[] = "mededelingen@koningskerkdeventer.nl";
			#$mailText[] = "beamteam@koningskerkdeventer.nl)";
			$mailText[] = "";
			$mailText[] = "Daarnaast is het goed om te vermelden dat de kerkdienst online te volgen en terug te kijken is. Hiervoor maken wij gebruiken van de diensten van Kerkdienstgemist en YouTube.";
												
			if($voorgangerData['declaratie'] == 1 AND $dienstData['ruiling'] == 0) {
				$mailText[] = "";
				$mailText[] = "<i>Declaratie</i>";
				$mailText[] = "Op ". time2str ('%A %e %B', $dienstData['start']).' '.($voorgangerData['stijl'] == 0 ? 'ontvangt u' : 'ontvang je') ." in de ochtend een link naar ". ($voorgangerData['stijl'] == 0 ? 'uw' : 'jouw') ." persoonlijke digitale declaratie-omgeving voor het declareren van ".($voorgangerData['stijl'] == 0 ? 'uw' : 'jouw')." onkosten.";
				setVoorgangerDeclaratieStatus(1, $dienst);
			}
		
			# Elke keer de aandachtspunten mailen is wat overdreven. Eens in de 6 weken lijkt mij mooi
			$aandachtPeriode = mktime(23,59,59,date("n")-(6*7));
			$lastUpdate = mktime(23,59,59,11,05,2021);
			
			if($voorgangerData['aandacht'] == 1 AND ($voorgangerData['last_aandacht'] < $aandachtPeriode OR $voorgangerData['last_aandacht'] < $lastUpdate)) {
				$bijlageText[] = "de aandachtspunten van de dienst";				
				$param['attachment'][]	= array('file' => '../download/aandachtspunten.pdf', 'name' => 'Aandachtspunten Liturgie Deventer (dd 05-11-2021).pdf');
				setLastAandachtspunten($dienstData['voorganger_id']);
			}
					
			if(count($bijlageText) > 0) {
				$mailText[] = "";
				$mailText[] = "In de bijlage ".($voorgangerData['stijl'] == 0 ? 'treft u' : 'tref je')." ". implode(' en ', $bijlageText) ." aan.";
			}
			
			$mailText[] = "";
			$mailText[] = "Als er onduidelijkheid is of er zijn vragen dan kan ". ($voorgangerData['stijl'] == 0 ? 'u' : 'je') ." contact opnemen met ". ($ouderling > 0 ? "de ouderling van dienst via <a href='mailto:". makeName($ouderling, 5) ." <". $adresOuderling .">'>mail</a>, met " : ""). "Sander Lagendijk als Clustercoo&ouml;rdinator Eredienst (<a href='tel:+31612586835'>06-12586835</a>) of met mij.";
			$mailText[] = "";
			$mailText[] = "Vriendelijke groeten";
			$mailText[] = "";
			$mailText[] = "Jenny van der Vegt-Huzen";
			$mailText[] = "Tel.: 06-10638291";
			$mailText[] = $voorgangerReplyAddress;		
			
			# Onderwerp maken
			#$Subject = "Voorgaan $dagdeel ". date('j-n-Y', $dienstData['start']);
			$Subject = "Introductie en toelichting voor komende Eredienst in de Koningskerk Deventer";
			
			if($sendMail) {		
				$param['subject'] = trim($Subject);
				$param['message'] = implode("<br>\n", $mailText);
				
				if(!sendMail_new($param)) {
					toLog('error', '', "Problemen met voorgangersmail versturen naar $mailNaam voor ". date('j-n-Y', $dienstData['start']));
					echo "Problemen met mail versturen<br>\n";
				} else {
					toLog('info', '', "Voorgangersmail verstuurd naar $mailNaam voor ". date('j-n-Y', $dienstData['start']));
					echo "Mail verstuurd naar $mailNaam<br>\n";
				}								
			} else {
				echo 'Afzender : Preekvoorziening Koningskerk Deventer |'.$ScriptMailAdress .'<br>';
				echo 'Ontvanger :'. $mailNaam .'|'.$voorgangerData['mail'] .'<br>';
				echo 'Onderwerp :'. trim($Subject) .'<br>';
				echo implode("<br>\n", $mailText);
			}
		
			setVoorgangerLastSeen($dienstData['voorganger_id'], $dienstData['start']);
		} elseif(trim($voorgangerData['mail']) == '') {
			toLog('info', '', "Geen voorgangersmail verstuurd voor ". $dagdeel .' van '. date('j-n', $dienstData['start']) ." omdat geen voorganger bekend is");
		} else {
			toLog('error', '', "Kon geen voorgangersmail versturen voor ". $dagdeel .' van '. date('j-n', $dienstData['start']) .", ongeldig mailadres");
		}
	}
} else {
	toLog('error', '', 'Poging handmatige run vorgangermail, IP:'.$_SERVER['REMOTE_ADDR']);
}
?>
