<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Voorganger.php');
include_once('../Classes/KKDMailer.php');
include_once('../Classes/Mysql.php');

$test = false;

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres

# Hij runt elke maandag, check of de dag kleiner of gelijk is aan 7 (effectief 1ste maandag van de maand)
if((in_array($_SERVER['REMOTE_ADDR'], $allowedIP) AND date('j') <= 7) OR $test) {
	toLog('Controle gegevens voorgangers komende maand');
	
	$startTijd	= mktime(0, 0, 0, date("n")+1, 1, date("Y"));
	$eindTijd	= mktime(23, 59, 59, date("n")+2, 0, date("Y"));
	$diensten	= Kerkdienst::getDiensten($startTijd, $eindTijd);
	
	$jaarGeleden = mktime(0, 0, 0, date("n")-12);
	
	foreach($diensten as $dienstID) {
		$dienst		= new Kerkdienst($dienstID);
		$voorganger	= new Voorganger($dienst->voorganger);
						
		if(isset($voorganger) && $voorganger->last_data < $jaarGeleden && $voorganger->last_voorgaan > 0 && $voorganger->declaratie) {
			$voorgangerID = $voorganger->id;
			
			# Dus een array waarbij de key het ID van de voorganger is
			# en de value de wijze van aanspreken
			$voorgangers[$voorgangerID] = $voorganger->vousvoyeren;
		}
	}
	
	foreach($voorgangers as $id => $stijl) {
		$voorganger	= new Voorganger($id);		
		$voorganger->hash = generateID(48);
		$voorganger->save();		
		
		$mailText[] = "Beste ". $voorganger->getName(5) .",<br>";
		$mailText[] = "<br>";
		$mailText[] = ($stijl == 0 ? 'u' : 'jij'). " gaat volgende maand voor in de Koningskerk te Deventer.<br>";
		$mailText[] = "Om die reden wil ik controleren of alle gegegevens die wij van ". ($stijl? 'u' : 'jouw') ." in ons systeem hebben staan (nog) correct zijn.<br>";
		$mailText[] = "<br>";
		$mailText[] = "Op dit moment ". ($stijl ? 'staat u' : 'sta je'). " o.a. met de volgende gegevens in ons systeem:<br>";
		$mailText[] = "<table>";
		$mailText[] = "<tr>\n<td width=25>&nbsp;</td>\n<td>Titel</td>\n<td>&nbsp;</td>\n<td>". $voorganger->aanhef ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Initialen</td>\n<td>&nbsp;</td>\n<td>". $voorganger->initialen ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Voornaam</td>\n<td>&nbsp;</td>\n<td>". $voorganger->voornaam ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Tussenvoegsel</td>\n<td>&nbsp;</td>\n<td>". $voorganger->tussenvoegsel ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Achternaam</td>\n<td>&nbsp;</td>\n<td>". $voorganger->achternaam ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Mailadres</td>\n<td>&nbsp;</td>\n<td>". $voorganger->mail ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Plaats</td>\n<td>&nbsp;</td>\n<td>". $voorganger->plaats ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Denominatie</td>\n<td>&nbsp;</td>\n<td>". $voorganger->denominatie ."</td></tr>";
		$mailText[] = "<tr>\n<td>&nbsp;</td>\n<td>Aanspreekstijl</td>\n<td>&nbsp;</td>\n<td>". ($voorganger->vousvoyeren ? 'Vousvoyeren' : 'Tutoyeren' ) ."</td></tr>";
		$mailText[] = "</table>";
		$mailText[] = "<br>";
		$mailText[] = "Zijn deze gegevens nog correct?<br>";
		$mailText[] = "&nbsp;<a href='". $ScriptURL ."voorganger/checkData.php?hash=". $voorganger->hash ."&correct=true'>Ja</a> (komend jaar ". ($stijl ? 'krijgt u' : 'krijg je'). " geen mail meer)<br>";
		$mailText[] = "&nbsp;<a href='". $ScriptURL ."voorganger/checkData.php?hash=". $voorganger->hash ."&correct=false'>Nee</a> (". ($stijl ? 'u' : 'je'). " komt op een pagina om de juiste gegevens in te voeren)<br>";
		

		$mail = new KKDMailer();
		#$mail->ontvangers = array($voorganger->mail, $voorganger->getName(4));
		$mail->Subject	= 'Controle gegevens';
		$mail->Body		= implode("\n", $mailText);
		$mail->From		= $noReplyAdress;
		$mail->FromName	= "Webmaster Koningskerk";
		$mail->addReplyTo($ScriptMailAdress);
		if(!$productieOmgeving) $mail->testen = true;
		
		if($mail->sendMail()) {
			toLog('Voorgangerscheck naar '. $voorganger->id .' gestuurd', 'debug');
		} else {
			toLog('Kan geen voorgangerscheck sturen naar '. $voorganger->id, 'error');
		}		
	}
	
} elseif(!in_array($_SERVER['REMOTE_ADDR'], $allowedIP)) {
	toLog('Poging handmatige run vorgangermail, IP:'.$_SERVER['REMOTE_ADDR'], 'error');
}
?>
