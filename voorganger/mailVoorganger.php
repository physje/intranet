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
		$adresJeugd		= getMailAdres($jeugdmoment);				
		
		$aRegisseur			= getRoosterVulling(26, $dienst);
		$regisseur			= $aRegisseur[0];
		$adresRegisseur	= getMailAdres($regisseur);		
		
		$aBeamer				= getRoosterVulling(11, $dienst);
		$beameraar			= $aBeamer[0];
		
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
				$param['cc'][] = array($adresJeugd, makeName($jeugdmoment, 6));
								
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
			$mailText[] = "Beste $aanspeekNaam,";
			$mailText[] = "";
			$mailText[] = "Fijn dat ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." ". time2str ('%A %e %B', $dienstData['start'])." om ". date('H:i', $dienstData['start'])." uur komt preken in de $dagdeel van de Koningskerk te Deventer.";
			$mailText[] = "Ik geef ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." de nodige informatie door.";
			
			if($regisseur > 0) {
				$mailText[] = "";
				$mailText[] = "Regisseur";
				$mailText[] = "In Deventer worden regisseurs ingezet om de coördinatie tussen alle partijen die een rol hebben in de dienst (vooraf en tijdens de eredienst) te verzorgen. De regisseur heeft hierin een afstemmende rol richting (gast)predikant en andere betrokkenen en is het eerste aanspreekpunt. ";
				$mailText[] = "In deze dienst is ". makeName($regisseur, 5) ." de regisseur.";
				$mailText[] = ($voorgangerData['stijl'] == 0 ? 'Heeft u' : 'Heb je')." vragen of ".($voorgangerData['stijl'] == 0 ? 'wilt u' : 'wil je')." overleggen over de inhoud van de dienst? Neem dan contact op met ". makeName($regisseur, 1) .".";
			}
			
			if($bandleider > 0) {
				$mailText[] = "";
				$mailText[] = "Bandleider";
				$mailText[] = "De muzikale begeleiding wordt gecoördineerd door ". makeName($bandleider, 5) .". De liturgie ".($voorgangerData['stijl'] == 0 ? 'kunt u' : 'kun je')." afstemmen met ". makeName($bandleider, 1) ." voor de muziek. De bandleider kan aangeven of liederen bekend en of geschikt zijn in onze gemeente en eventuele suggesties doen voor een vervangend lied.";
				$mailText[] = ($voorgangerData['stijl'] == 0 ? 'Wilt u' : 'Wil je')." de liturgie een week van te voren doorgeven zodat de band kan oefenen?";
			}
			
			if($jeugdmoment > 0) {
				$mailText[] = "";
				$mailText[] = "Jeugdmoment";
				$mailText[] = makeName($jeugdmoment, 5) ." zal contact met ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." opnemen om het thema van het jeugdmoment af te stemmen op ".($voorgangerData['stijl'] == 0 ? 'uw' : 'jouw')." keuze voor het thema van de preek en dienst.";
			}
			
			if($schriftlezer > 0 OR $beameraar > 0) {
				$mailText[] = "";
				$mailText[] = "Andere taken";
				$mailText[] = ($schriftlezer > 0 ? "De schriftlezing wordt gedaan door ". makeName($schriftlezer, 5) : "Het is nog niet bekend wie de schriftlezing doet").".";
				$mailText[] = ($beameraar > 0 ? "De beamer wordt bediend door ". makeName($beameraar, 5) : "Het is nog niet bekend wie de beamer bediend").".";
			}
						
			if($voorgangerData['declaratie'] == 1 AND $dienstData['ruiling'] == 0) {
				$mailText[] = "";
				$mailText[] = "Declaratie";
				$mailText[] = "Op ". time2str ('%A %e %B', $dienstData['start']).' '.($voorgangerData['stijl'] == 0 ? 'ontvangt u' : 'ontvang je') ." in de ochtend een link naar uw persoonlijke digitale declaratie-omgeving voor het declareren van ".($voorgangerData['stijl'] == 0 ? 'uw' : 'jouw')." onkosten.";
				setVoorgangerDeclaratieStatus(1, $dienst);
			}
			
			$mailText[] = "";
			$mailText[] = "Communicatie";
			$mailText[] = "De regisseur is het eerste aanspreekpunt bij vragen of opmerkingen. Neem dus gerust contact op met de regisseur. Als u deze mail beantwoordt aan \"allen\" dan zijn ook alle andere betrokkenen op tijd op de hoogte.";
			
			# Elke keer mailen is wat overdreven. Eens in de 6 weken lijkt mij mooi
			$aandachtPeriode = mktime(23,59,59,date("n")-(6*7));
			$lastUpdate = mktime(23,59,59,11,6,2019);
			
			if($voorgangerData['aandacht'] == 1 AND ($voorgangerData['last_aandacht'] < $aandachtPeriode OR $voorgangerData['last_aandacht'] < $lastUpdate)) {
				$bijlageText[] = "de aandachtspunten van de dienst";				
				$param['attachment'][]	= array('file' => '../download/aandachtspunten.pdf', 'name' => 'Aandachtspunten Liturgie Deventer (dd 29-11-2019).pdf');
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
			$Subject = "Preken $dagdeel ". date('j-n-Y', $dienstData['start']);
			
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
		} else {
			toLog('error', '', '', "Kon geen voorgangersmail versturen voor ". $dagdeel .' van '. date('j-n', $dienstData['start']) .", ongeldig mailadres");
		}
	}
} else {
	toLog('error', '', '', 'Poging handmatige run vorgangermail, IP:'.$_SERVER['REMOTE_ADDR']);
}
?>
