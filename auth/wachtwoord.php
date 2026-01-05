<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/KKDMailer.php');
include_once('../Classes/Logging.php');

if(isset($_POST['opvragen']) AND isset($_POST['invoer']) AND trim($_POST['invoer']) != '') {
	$invoer	= trim($_POST['invoer']);
	#$sql		= "SELECT $UserID FROM $TableUsers WHERE $UserUsername like '$invoer' OR $UserMail like '$invoer' OR $UserFormeelMail like '$invoer'";	
	#$result = mysqli_query($db, $sql);
	$UserID = getUserByInput($invoer);
			
	if(!$UserID) {
		$text[] = "Er is helaas niks gevonden met '$invoer'";
	} elseif(count($UserID) > 1) {
		$text[] = "Er zijn meer leden die voldoen aan '$invoer'. Probeer het op een andere manier.";
		toLog('Inloggegevens gezocht met '. $invoer .', meer dan 1 resultaat', 'error');
	} else {
		$user = new Member($UserID[0]);
				
		$Mail[] = "Beste ". $user->voornaam .",<br>";
		$Mail[] = "<br>";
		$Mail[] = "je hebt een nieuw wachtwoord aangevraagd voor $ScriptTitle.<br>";
		$Mail[] = "Heb geen nieuw wachtwoord voor je aangemaakt, maar een link gemaakt waarmee je zelf een wachtwoord kunt instellen<br>";
		$Mail[] = "Door <a href='". $ScriptURL ."account.php?hash=". $user->hash_long ."'>deze link</a> te volgen kom je op jouw persoonlijke account-pagina waarop je een wachtwoord kunt instellen.<br>";
		$Mail[] = "<br>";
		$Mail[] = "<i>Let wel op, iemand met deze link kan zonder in te loggen bij je account komen, wees er dus zuinig op!";
		$Mail[] = "Mocht je het idee hebben dan iemand anders jouw link gebruikt/misbruikt, laat het weten, dan krijg jij een nieuwe link en maken we de oude link onklaar</i>.";
		
		$mail = new KKDMailer();
		$mail->aan		= $user->id;
		$mail->Body 	= implode("\n", $Mail);
		$mail->Subject	= "Nieuw wachtwoord voor $ScriptTitle";
		if(!$productieOmgeving)	$mail->testen	= true;
						
		if(!$mail->Sendmail()) {
			toLog('Problemen met wachtwoord-mail versturen', 'error', $user->id);
			$text[] = "Inloggegevens konden helaas niet verstuurd worden";			
		} else {
			toLog("Inloggegevens verstuurd", '', $user->id);
			$text[] = "Inloggegevens zijn verstuurd";
		}		
	}
} else {
	$invoer = getParam('invoer', '');
	
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>\n";
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td>Voer uw loginnaam of email-adres in. Het systeem zal dan een link sturen waarmee u een nieuw wachtwoord kunt instellen.</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td><input type='text' name='invoer' value='$invoer' size='75'></td>";
	$text[] = "</tr>";
	
	if(isset($invoer) AND trim($invoer) == '')	{
		$text[] = "<tr>";
		$text[] = "	<td><i>Veld lijkt leeg te zijn, vul gebruikersnaam of mailadres in</i></td>";
		$text[] = "</tr>";
	}
	
	$text[] = "<tr>";
	$text[] = "	<td>&nbsp;</td>";
	$text[] = "</tr>";	
	$text[] = "<tr>";
	$text[] = "	<td align='center'><input type='submit' name='opvragen' value='Opvragen'></td>";
	$text[] = "</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
}

echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;
echo "<div class='content_block'>". implode(NL, $text) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>