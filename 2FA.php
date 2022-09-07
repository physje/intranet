<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');

$db = connect_db();

$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
include_once($cfgProgDir.'../include/google2fa/2FA.php');

if(get2FACode($_SESSION['ID']) != '') {
	if(isset($_POST['entered_2FA'])) {
		$google2fa = new \PragmaRX\Google2FA\Google2FA();
		$secret_key = get2FACode($_SESSION['ID']);
  	
		if(!$google2fa->verifyKey($secret_key, $_POST['entered_2FA'])) {
			toLog('debug', $_SESSION['ID'], '', 'Foutieve 2FA-code bij verwijderen 2FA');
			$phpSP_message = 'Onjuiste code';
			include($cfgProgDir . "2FA.php");
			exit;
		} else {
			$sql_update = "UPDATE $TableUsers SET $User2FA = '' WHERE $UserID = ". $_SESSION['ID'];
			
			if(mysqli_query($db, $sql_update)) {
				$text[] = "De 2FA is succesvol verwijderd.<br>";
				$text[] = "Vanaf nu log je alleen nog maar in met je gebruikersnaam & wachtwoord.";
				toLog('info', $_SESSION['ID'], '', '2FA verwijderd');
			} else {
				$text[] = "Er zijn problemen met het verwijderen van 2FA.";
				toLog('error', $_SESSION['ID'], '', 'Kon 2FA niet verwijderen');
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
		$text[] = " Mocht je op 'Volgende' klikken, dan zal je voor de laatste keer gevraagd worden een 2FA-code in te voeren.<br>";
		$text[] = "Dit om zeker te weten dat jij ". makeName($_SESSION['ID'], 5) ." bent";
		$text[] = "	</td>";
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
} else {
	if(isset($_POST['save'])) {
		$sql_update = "UPDATE $TableUsers SET $User2FA = '". $_POST['secret'] ."' WHERE $UserID = ". $_SESSION['ID'];
			
		if(mysqli_query($db, $sql_update)) {
			$text[] = "De 2FA is ingesteld.<br>";
			$text[] = "Vanaf nu moet je naast je gebruikersnaam & wachtwoord, ook inloggen met de code die je app genereert.";
			toLog('info', $_SESSION['ID'], '', '2FA ingesteld');
		} else {
			$text[] = "Er zijn problemen met het instellen van 2FA.";
			toLog('error', $_SESSION['ID'], '', 'Kon 2FA niet instellen');
		}		
	} elseif(isset($_POST['next'])) {
		$personData = getMemberDetails($_SESSION['ID']);
		
		$google2fa = new \PragmaRX\Google2FA\Google2FA();
		$secret_key = $google2fa->generateSecretKey();
		
		$QR = $google2fa->getQRCodeUrl($ScriptServer, $personData['username'], $secret_key);
		
		$image_url = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl='.$QR;
						
		$text[] = "<form method='post'>";
		$text[] = "<input type='hidden' name='secret' value='$secret_key'>";
		$text[] = "<table align='center'>";
		$text[] = "<tr>";
		$text[] = "	<td align='center'>Voeg onderstaande QR-code toe aan de app en klik op 'Volgende' als dat gelukt is</td>";
		$text[] = "</tr>";		
		$text[] = "<tr>";
		$text[] = "	<td align='center'><img src='$image_url'/></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td align='center'><input type='submit' name='save' value='Volgende'></td>";
		$text[] = "</tr>";
		$text[] = "</table>";
		$text[] = "</form>";
		
		toLog('debug', $_SESSION['ID'], '', '2FA-code gegenereerd en getoond');		
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

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>