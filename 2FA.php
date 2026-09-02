<?php
/**
 * Pagina voor het beheren van de MFA.
 * 
 * Met deze pagina kan de MFA worden ingesteld en uitgeschakeld.
 * Op dit moment wordt 2FA en MFA nog door elkaar gebruikt.
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
include_once('include/TOTP/Authenticator.php');
include_once('Classes/Member.php');
include_once('Classes/Logging.php');

$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

$gebruiker = new Member($_SESSION['useID']);

# Er bestaat een MFA-code. MFA staat dus ingesteld
if($gebruiker->MFA_code != '') {
	if(isset($_POST['entered_2FA'])) {
		if(!Authenticator::verifyCode($gebruiker->MFA_code, $_POST['entered_2FA'])) {
			toLog('Foutieve 2FA-code bij verwijderen 2FA', 'debug');
			$phpSP_message = 'Onjuiste code';
			include($cfgProgDir . "2FA.php");
			exit;
		} else {
			$gebruiker->MFA_code = '';
			
			if($gebruiker->save()) {
				$text[] = "De 2FA is succesvol verwijderd.<br>";
				$text[] = "Vanaf nu log je alleen nog maar in met je gebruikersnaam & wachtwoord.";
				$text[] = "<br>";
				$text[] = "Je kan nu dus ook deze site uit je autorisator-app verwijderen.<br>";
				toLog('2FA verwijderd');
			} else {
				$text[] = "Er zijn problemen met het verwijderen van 2FA.";
				toLog('Kon 2FA niet verwijderen', 'error');
			}			
		}
	} elseif(isset($_POST['next'])) {
		include($cfgProgDir . "2FA.php");
		exit;		
	} else {
		$text[] = "<form method='post'>";
		$text[] = "<table>";
		$text[] = "<tr>";
		$text[] = "	<td>Je staat op het punt 2FA uit te zetten. Vanuit het oogpunt van veiligheid is dat niet aan te raden.<br>";
		$text[] = "	<br>";
		$text[] = " Mocht je op 'Volgende' klikken, dan zal om zeker te weten dat jij ". $gebruiker->getName(5) ." bent, voor de laatste keer gevraagd worden een 2FA-code in te voeren.</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td align='center'>&nbsp;</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td align='center'><input type='submit' name='next' value='Volgende'></td>";
		$text[] = "</tr>";
		$text[] = "</table>";
		$text[] = "</form>";
	}	

# Er bestaat nog geen MFA-code. MFA moet dus worden ingesteld		
} else {
	$secret_key = getParam('secret_key', '');
	$code = getParam('code', '');

	if(isset($_POST['save'])) {
		if(Authenticator::verifyCode($secret_key, $code)) {
			$gebruiker->MFA_code = $secret_key;
			
			if($gebruiker->save()) {
				$text[] = "De 2FA is ingesteld.<br>";
				$text[] = "<br>";
				$text[] = "Vanaf nu moet je naast je gebruikersnaam & wachtwoord, ook inloggen met de code die je app genereert.<br>";
				$text[] = "Dit geldt overigens alleen als je inlogt van een onbekende computer. Mocht je eerder van dezelfde computer ingelogd hebben, dan zal de 2FA-code niet gevraagd worden.";
				toLog('2FA ingesteld');
			} else {
				$text[] = "De code is juist, er zijn echter problemen met het instellen van 2FA.";
				toLog('Kon 2FA niet instellen', 'error');
			}			
		} else {
			$text[] = "Deze code is niet juist.";
			toLog('Fout bij controle code bij instellen 2FA', 'error');			
		}
	} elseif(isset($_POST['check'])) {
		$text[] = "<form method='post'>";
		$text[] = "<input type='hidden' name='secret_key' value='$secret_key'>";
		$text[] = "<table align='center'>";
		$text[] = "<tr>";
		$text[] = "	<td align='center'>Zoek in de app waarmee je zojuist de QR-code gescand hebt, de code voor deze site op en voer hem hieronder in.</td>";
		$text[] = "</tr>";		
		$text[] = "<tr>";
		$text[] = "	<td align='center'><input type='text' name='code' value='' class='login_text'></td>";        
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>&nbsp;</td>";        
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td align='center'><input type='submit' name='save' value='Controleer'></td>";
		$text[] = "</tr>";
		$text[] = "</table>";
		$text[] = "</form>";
	} elseif(isset($_POST['next'])) {
		$secret_key = Authenticator::createSecret();		
		
		$params['width'] = $params['height'] = 300;
        $QR = Authenticator::generateQrCodeUrl($gebruiker->username, $secret_key, $ScriptTitle, $params);
		
		#$image_url = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl='.$QR;
        $imageData = base64_encode(file_get_contents($QR));
        #echo '<img src="data:image/jpeg;base64,'.$imageData.'">';
						
		$text[] = "<form method='post'>";
		$text[] = "<input type='hidden' name='secret_key' value='$secret_key'>";
		$text[] = "<table align='center'>";
		$text[] = "<tr>";
		$text[] = "	<td align='center'>Voeg onderstaande QR-code toe aan de app en klik op 'Volgende' als dat gelukt is</td>";
		$text[] = "</tr>";		
		$text[] = "<tr>";
		#$text[] = "	<td align='center'><img src='$image_url'/></td>";
        $text[] = "	<td align='center'><img src='data:image/jpeg;base64,".$imageData."'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td align='center'><input type='submit' name='check' value='Volgende'></td>";
		$text[] = "</tr>";
		$text[] = "</table>";
		$text[] = "</form>";
		
		toLog('2FA-code gegenereerd en getoond', 'debug');		
	} else {
		$text[] = "Om 2 factor authenticatie (2FA) aan te zetten, heb je een app op je telefoon, tablet of computer nodig die een code kan genereren.<br>";
		$text[] = "&nbsp;<br>";
		$text[] = "Er zijn verschillende apps die dat kunnen, de bekendste is Google Authenticator. Die is te downloaden voor";
		$text[] = "<ul>";
		$text[] = "	<li><a href='https://apps.apple.com/us/app/google-authenticator/id388497605'>iOS</a></li>";
		$text[] = "	<li><a href='https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2'>Android</a></li>";
		$text[] = "	<li><a href='https://www.microsoft.com/en-us/store/p/google-authenticator/9wzdncrdnkrf'>Windows</a></li>";
		$text[] = "</ul>";
		$text[] = "Alternatieven zijn :";
		$text[] = "<ul>";
		$text[] = "	<li><a href='https://www.authy.com/download'>Authy</a></li>";
		#$text[] = "	<li><a href=''>FreeOTP</a></li>";
		$text[] = "	<li><a href='https://www.microsoft.com/nl-nl/store/apps/authenticator/9wzdncrfj3rj'>Microsoft Authenticator</a></li>";
		$text[] = "	<li><a href='https://lastpass.com/misc_download2.php'>LastPass Authenticator</a></li>";
		$text[] = "	<li><a href='https://1password.com/downloads/'>1Password</a></li>";
		$text[] = "</ul>";
		$text[] = "&nbsp;<br>";
		$text[] = "Zorg dat een van bovenstaande apps ge&iuml;nstalleerd is, en klik op 'Volgende'<br>";
		$text[] = "<form method='post'>";
		$text[] = "<table width='100%'>";
		$text[] = "<tr>";
		$text[] = "	<td align='right'><input type='submit' name='next' value='Volgende'></td>";
		$text[] = "</tr>";
		$text[] = "</table>";
		$text[] = "</form>";	
	}
}

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>". implode(NL, $text) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>