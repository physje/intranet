<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
include_once('Classes/Member.php');
include_once('Classes/Logging.php');

$showLogin = true;

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('ongeldige hash (account)', 'error', '');
		$showLogin = true;
	} else {
		$showLogin = false;
		session_start(['cookie_lifetime' => $cookie_lifetime]);		
		$_SESSION['useID'] = $id;
		$_SESSION['realID'] = $id;
		toLog('account mbv hash');
	}
}

if($showLogin) {
	$cfgProgDir = 'auth/';
	include($cfgProgDir. "secure.php");
}

$user = new Member($_SESSION['useID']);
$userGroups = $user->getTeams();

# Als je niet voorkomt in de Admin-groep dan ga je naar je eigen gegevens
if(!in_array(1, $userGroups)) {	
	$id = $_SESSION['useID'];
} else {
	$id = getParam('id', $_SESSION['useID']);
}

$person = new Member($id);
$unique = true;
$melding = '';

if(isset($_POST['username']) AND !$person->isUniqueUsername($_POST['username'])) {
	$unique = false;
	$melding = "username wordt al gebruikt";
}

if(isset($_POST['data_opslaan']) AND $unique) {
	$person->username = $_POST['username'];
	if($_POST['wachtwoord'] != '')	$person->password = password_hash($_POST['wachtwoord'], PASSWORD_DEFAULT);
		
	if(!$person->save()) {
		$account[] = "Er is een fout opgetreden.";
		$account[] = $sql;
		toLog('Fout met wijzigen accountgegevens', 'error', $_POST['id']);
	} else {
		$account[] = "Account succesvol gewijzigd.";
		toLog('Accountgegevens gewijzigd', '', $_POST['id']);
	}			
} else {
	$account[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$account[] = "<input type='hidden' name='id' value='$id'>";
	if(isset($_REQUEST['hash'])) {
		$account[] = "<input type='hidden' name='hash' value='". $_REQUEST['hash'] ."'>";
	}
		
	$account[] = "<h2>Accountgegevens</h2>";
	$account[] = "<table>";
	$account[] = "<tr>";
	$account[] = "	<td colspan='2'>Mocht u niet tevreden zijn met uw huidige gebruikersnaam en wachtwoord dan kunt u die hier wijzigen.</td>";
	$account[] = "</tr>";
	$account[] = "<tr>";
	$account[] = "	<td valign='top'>Gebruikersnaam</td>";
	$account[] = "	<td valign='top'><input type='text' name='username' value='".$person->username."'></td>";
	$account[] = "</tr>";
	
	if($melding != '') {
		$account[] = "<tr>";
		$account[] = "	<td valign='top'>&nbsp;</td>";
		$account[] = "	<td valign='top'>$melding</td>";
		$account[] = "</tr>";
	}
		
	$account[] = "<tr>";
	$account[] = "	<td valign='top'>Wachtwoord</td>";
	$account[] = "	<td valign='top'><input type='text' name='wachtwoord' value=''></td>";
	$account[] = "</tr>";
	$account[] = "</table>";
	$account[] = "<p class='after_table'><input type='submit' name='data_opslaan' value='Opslaan'>";
	
	
	$twoFactor[] = "<h2>2 factor toegang (2FA)</h2>";
	#$twoFactor[] = "<table>";
	#$twoFactor[] = "<tr>";
	#$twoFactor[] = "	<td colspan='2'>Het is mogelijk uw account beter te beveiligen door naast een gebruikersnaam & wachtwoord-combinatie, ook gebruik te maken van een code die uw telefoon genereert als u wilt inloggen. Dat heeft 2-factor authenticatie. Op die manier moet u inloggen met iets dat u weet (gebruikersnaam & wachtwoord) en iets dat u heeft (telefoon), dubbel zo veilig dus.<br>";
	#$twoFactor[] = "	Via onderstaande link krijgt u meer informatie over 2-factor authenticatie en hoe dat in te stellen.<br>";
	#$twoFactor[] = "<br>";
	#$twoFactor[] = "<a href='2FA.php'>".(get2FACode($_SESSION['useID']) != '' ? 'Zet 2FA uit' : 'Zet 2FA aan')."</a>.</td>";	
	#$twoFactor[] = "</tr>";
	#$twoFactor[] = "</table>";
	$twoFactor[] = "Wegens technische problemen is 2FA momenteel uitgeschakeld";	
}


echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;
echo "<div class='content_block'>". implode(NL, $account) ."</div>".NL;
if(isset($twoFactor))	echo "<div class='content_block'>". implode(NL, $twoFactor) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>
