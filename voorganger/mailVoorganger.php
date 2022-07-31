<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();

$sendMail = true;
$sendTestMail = false;

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
		
		$aJeudmoment	= getRoosterVulling(25, $dienst);
		$jeugdmoment		= $aJeudmoment[0];
		$adresJeugd		= getMailAdres($jeugdmoment, true);			
		
		$aRegisseur			= getRoosterVulling(26, $dienst);
		$regisseur			= $aRegisseur[0];
		$adresRegisseur	= getMailAdres($regisseur, true);		
		
		$aBeamer				= getRoosterVulling(11, $dienst);
		$beameraar			= $aBeamer[0];
		
		$dagdeel 				= formatDagdeel($dienstData['start']);
		
		$aanspeekNaam		= makeVoorgangerName($dienstData['voorganger_id'], 5);
		$mailNaam 			= makeVoorgangerName($dienstData['voorganger_id'], 4);
		$voorgangerAchterNaam = makeVoorgangerName($dienstData['voorganger_id'], 7);
		
		# Als er geen mailadres van de voorganger bekend is
		//if(isValidEmail($voorgangerData['mail']) AND (date("H", $dienstData['start']) > 5 AND date("H", $dienstData['eind']) < 17)) {
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
				$param['cc'][] = array($adresJeugd, makeName($jeugdmoment, 6));
				$param['cc'][] = array($adresRegisseur, makeName($regisseur, 6));
								
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
			$mailText[] = "Beste $aanspeekNaam en anderen met een taak in de eredienst,";
			$mailText[] = "";
			$mailText[] = "Jullie staan op het rooster voor de $dagdeel van ". time2str ('%A %e %B', $dienstData['start'])." om ". date('H:i', $dienstData['start'])." uur in de Koningskerk te Deventer. Ik geef jullie de nodige informatie door.";
			
			if($regisseur > 0) {
				$mailText[] = "";
				$mailText[] = "<b>Regisseur</b>";
				$mailText[] = "In deze dienst is ". makeName($regisseur, 5) ." de regisseur. ". ($voorgangerData['stijl'] == 0 ? 'Heeft u' : 'Heb je')."  vragen of ".($voorgangerData['stijl'] == 0 ? 'wilt u' : 'wil je')." overleggen over de inhoud van de dienst? Neem dan contact op met ". makeName($regisseur, 1) .".";				
			}
			
			if($bandleider > 0) {
				$mailText[] = "";
				$mailText[] = "<b>Bandleider</b>";				
				$mailText[] = "De muzikale begeleiding wordt geco&ouml;rdineerd door ". makeName($bandleider, 5) .". Wanneer ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." het preekthema en de te lezen bijbelteksten een week van tevoren aanlevert, zal de bandleider geschikte liederen uitzoeken die passen binnen de gemeentezang. Mocht ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." zelf nog liederen in gedachten hebben, dan ".($voorgangerData['stijl'] == 0 ? 'kunt u' : 'kun je')." deze altijd ter suggestie aandragen bij ". makeName($bandleider, 1) .".";
				$mailText[] = ($voorgangerData['stijl'] == 0 ? 'Wilt u' : 'Wil je')." de liturgie een week van te voren doorgeven zodat de band kan oefenen?";
			}
			
			if($jeugdmoment > 0) {
				$mailText[] = "";
				$mailText[] = "<b>Jeugdmoment</b>";
				$mailText[] = makeName($jeugdmoment, 5) ." zal contact met ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." opnemen om het thema van het jeugdmoment af te stemmen op ".($voorgangerData['stijl'] == 0 ? 'uw' : 'jouw')." keuze voor het thema van de preek en dienst. Aan ". makeName($jeugdmoment, 1) ." de vraag om hier ongeveer 5 minuten voor te nemen.";
			}
			
			$mailText[] = "";
			$mailText[] = "<b>Andere taken</b>";
			$mailText[] = ($schriftlezer > 0 ? "De schriftlezing wordt gedaan door ". makeName($schriftlezer, 5) : "Het is nog niet bekend wie de schriftlezing doet").".";
			$mailText[] = ($beameraar > 0 ? "De beamer wordt bediend door ". makeName($beameraar, 5) : "Het is nog niet bekend wie de beamer bediend").".";
			$mailText[] = "Het gebed bij de collecte wordt gedaan door de diaken van dienst.";
									
			if($voorgangerData['declaratie'] == 1 AND $dienstData['ruiling'] == 0) {
				$mailText[] = "";
				$mailText[] = "<b>Declaratie</b>";
				$mailText[] = "Op ". time2str ('%A %e %B', $dienstData['start']).' '.($voorgangerData['stijl'] == 0 ? 'ontvangt u' : 'ontvang je') ." in de ochtend een link naar ". ($voorgangerData['stijl'] == 0 ? 'uw' : 'jouw') ." persoonlijke digitale declaratie-omgeving voor het declareren van ".($voorgangerData['stijl'] == 0 ? 'uw' : 'jouw')." onkosten.";
				setVoorgangerDeclaratieStatus(1, $dienst);
			}
			
			$mailText[] = "";
			$mailText[] = "<b>Communicatie</b>";
			if($regisseur > 0) {
				$mailText[] = "De regisseur is het eerste aanspreekpunt bij vragen of opmerkingen. Neem dus gerust contact op met ". makeName($regisseur, 1) .".";
			}
			$mailText[] = "Als ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." deze mail beantwoordt aan \"allen\" dan zijn ook alle andere betrokkenen op tijd op de hoogte.";
			
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
			$mailText[] = "Vriendelijke groeten";
			$mailText[] = "";
			$mailText[] = "Jenny van der Vegt-Huzen";
			$mailText[] = "Tel.: 06-10638291";
			$mailText[] = $voorgangerReplyAddress;		
			
			# Onderwerp maken
			$Subject = "Voorgaan $dagdeel ". date('j-n-Y', $dienstData['start']);
			
			if($sendMail) {		
				$param['subject'] = trim($Subject);
				$param['message'] = implode("<br>\n", $mailText);
				
				if(!sendMail_new($param)) {
					toLog('error', '', '', "Problemen met voorgangersmail versturen naar $mailNaam voor ". date('j-n-Y', $dienstData['start']));
					echo "Problemen met mail versturen<br>\n";
				} else {
					toLog('info', '', '', "Voorgangersmail verstuurd naar $mailNaam voor ". date('j-n-Y', $dienstData['start']));
					echo "Mail verstuurd naar $mailNaam<br>\n";
				}								
			} else {
				echo 'Afzender : Preekvoorziening Koningskerk Deventer |'.$ScriptMailAdress .'<br>';
				echo 'Ontvanger :'. $mailNaam .'|'.$voorgangerData['mail'] .'<br>';
				echo 'Onderwerp :'. trim($Subject) .'<br>';
				echo $HTMLMail;
			}
		
			setVoorgangerLastSeen($dienstData['voorganger_id'], $dienstData['start']);
		} elseif(trim($voorgangerData['mail']) == '') {
			toLog('info', '', '', "Geen voorgangersmail verstuurd voor ". $dagdeel .' van '. date('j-n', $dienstData['start']) ." omdat geen voorganger bekend is");
		} else {
			toLog('error', '', '', "Kon geen voorgangersmail versturen voor ". $dagdeel .' van '. date('j-n', $dienstData['start']) .", ongeldig mailadres");
		}
	}
} else {
	toLog('error', '', '', 'Poging handmatige run vorgangermail, IP:'.$_SERVER['REMOTE_ADDR']);
}
?>
