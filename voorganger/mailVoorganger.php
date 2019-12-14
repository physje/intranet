<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_HeaderFooter.php');
include_once('../../../general_include/class.phpmailer.php');
include_once('../../../general_include/class.html2text.php');
$db = connect_db();

$sendMail = false;
$sendTestMail = true;

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres
if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP) OR $test) {
	$startTijd	= mktime(0, 0, 0, date("n"), (date("j")+18), date("Y"));
	$eindTijd		= mktime(23, 59, 59, date("n"), (date("j")+18), date("Y"));	
	$diensten		= getKerkdiensten($startTijd, $eindTijd);
	
	foreach($diensten as $dienst) {
		$dienstData			= getKerkdienstDetails($dienst);
		$voorgangerData = getVoorgangerData($dienstData['voorganger_id']);
		
		$aBandleider		= getRoosterVulling(22, $dienst);
		$bandleider			= $aBandleider[0];
		$bandData				= getMemberDetails($bandleider);
		$adresBand			= getMailAdres($bandleider);
				
		$aSchriftlezer	= getRoosterVulling(12, $dienst);
		$schriftlezer		= $aSchriftlezer[0];
		$adresSchrift		= getMailAdres($schriftlezer);
		
		$aBeamer				= getRoosterVulling(11, $dienst);
		$beameraar			= $aBeamer[0];
		
		$dagdeel 				= formatDagdeel($dienstData['start']);
		
		$aanspeekNaam		= makeVoorgangerName($dienstData['voorganger_id'], 5);
		$mailNaam 			= makeVoorgangerName($dienstData['voorganger_id'], 4);
		
		# Nieuw mail-object aanmaken
		$mail = new PHPMailer;
		$mail->FromName	= 'Preekvoorziening Koningskerk Deventer';
		$mail->From			= $ScriptMailAdress;
		$mail->AddReplyTo($voorgangerReplyAddress, $voorgangerReplyName);
		
		# Als er niet getest wordt 
		if(!$sendTestMail) {
			# Alle geadresseerden toevoegen
			$mail->AddAddress($voorgangerData['mail'], $mailNaam);		
			$mail->AddCC($adresBand, makeName($bandleider, 6));
			$mail->AddCC($adresSchrift, makeName($schriftlezer, 6));
			
			# CC toevoegen
			foreach($voorgangerCC as $adres => $naam) {
				$mail->AddCC($adres, $naam);
			}
			
			# BCC toevoegen
			foreach($voorgangerBCC as $adres => $naam) {
				$mail->AddBCC($adres, $naam);
			}
		} else {
			$mail->AddAddress($ScriptMailAdress);		
		}
						
		# Mail opstellen
		$mailText = $bijlageText = array(); 
		$mailText[] = "Beste $aanspeekNaam,";
		$mailText[] = "";
		$mailText[] = "Fijn dat ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." komt preken in de $dagdeel van ". strftime ('%A %e %B', $dienstData['start'])." om ". date('H:i', $dienstData['start'])." uur, in de Koningskerk te Deventer.";
		$mailText[] = "Ik geef ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." de nodige informatie door.";
		$mailText[] = "";
		$mailText[] = "De muzikale begeleiding in deze dienst wordt gecoordineerd door ". makeName($bandleider, 5) .".";
		$mailText[] = ($schriftlezer > 0 ? "Schriftlezing wordt gedaan door ". makeName($schriftlezer, 5) : "Het is nog niet bekend wie de schriftlezing doet").".";
		$mailText[] = ($beameraar > 0 ? "Beamer wordt bediend door ". makeName($beameraar, 5) : "Het is nog niet bekend wie de beamer bediend").".";
		$mailText[] = "";
		$mailText[] = ($voorgangerData['stijl'] == 0 ? 'U kunt' : 'Je mag')." de liturgie afstemmen met ". makeName($bandleider, 1) ." voor de muziek. ". ($bandData['geslacht'] == 'M' ? 'Hij' : 'Zij') ." kan dan aangeven of liederen bekend en of geschikt zijn in onze gemeente en eventuele suggesties voor een vervangend lied.";
		$mailText[] = ($voorgangerData['stijl'] == 0 ? 'Wilt u' : 'Wil jij')." de liturgie een week van te voren doorgeven zodat de band kan oefenen.";
		$mailText[] = "Als ".($voorgangerData['stijl'] == 0 ? 'u' : 'je')." deze mail beantwoordt met \"allen\" dan is iedereen op tijd op de hoogte.";
		
		# Elke keer mailen is wat overdreven. Eens in de 6 weken lijkt mij mooi
		$aandachtPeriode = mktime(23,59,59,date("n")-(6*7));
		$lastUpdate = mktime(23,59,59,11,6,2019);
		
		if($voorgangerData['aandacht'] == 1 AND ($voorgangerData['last_aandacht'] < $aandachtPeriode OR $voorgangerData['last_aandacht'] < $lastUpdate)) {
			$bijlageText[] = "de aandachtspunten van de dienst";
			$mail->AddAttachment('../download/aandachtspunten.pdf', 'Aandachtspunten Liturgie Deventer (dd 29-11-2019).pdf');
			setLastAandachtspunten($dienstData['voorganger_id']);
		}
		
		if($voorgangerData['declaratie'] == 1) {
			$bijlageText[] = "het declaratieformulier";
			$mail->AddAttachment('../download/declaratieformulier.xlsx', date('ymd', $dienstData['start'])."_Declaratieformulier_". str_replace(' ', '', $voorgangerAchterNaam) .".xlsx");
		}
				
		if(count($bijlageText) > 0) {
			$mailText[] = "";
			$mailText[] = "In de bijlage ".($voorgangerData['stijl'] == 0 ? 'treft u' : 'tref je')." ". implode(' en ', $bijlageText) ." aan.";
		}
		
		//if($voorgangerData['declaratie'] == 1) {
		//	$mailText[] = "";
		//	$mailText[] = "Op de ochtend van de dienst ontvangt u een persoonlijke link naar de online omgeving voor het declareren van uw onkosten.";
		//	setVoorgangerDeclaratieStatus(1, $dienst);
		//}
		
		$mailText[] = "";
		$mailText[] = "Mochten er nog vragen zijn dan hoor ik het graag.";
		$mailText[] = "";
		$mailText[] = "Vriendelijke groeten";
		$mailText[] = "";
		$mailText[] = "Jenny van der Vegt-Huzen";
		$mailText[] = "Tel.: 06-10638291";
		$mailText[] = $voorgangerReplyAddress;
		
		# Onderwerp maken
		$Subject = "Preken $dagdeel ". date('j-n-Y', $dienstData['start']);
		
		# HTML- en plaintext-mail maken en deze toekennen aan het mail-object
		$HTMLMail = $MailHeader.implode("<br>\n", $mailText).$MailFooter;	
		$html =& new html2text($HTMLMail);
		$html->set_base_url($ScriptURL);
		$PlainMail = $html->get_text();
		
		$mail->Subject	= trim($Subject);
		$mail->IsHTML(true);
		$mail->Body	= $HTMLMail;
		$mail->AltBody	= $PlainMail;
		
		if($sendMail) {
			if(!$mail->Send()) {
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
	}
} else {
	toLog('error', '', '', 'Poging handmatige run vorgangermail, IP:'.$_SERVER['REMOTE_ADDR']);
}
?>
