<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');

$showLogin = true;

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('error', '', '', 'ongeldige hash (account)');
		$showLogin = true;
	} else {
		$showLogin = false;
		$_SESSION['ID'] = $id;
		toLog('info', $id, '', 'account mbv hash');
	}
}

if($showLogin) {
	$cfgProgDir = 'auth/';
	include($cfgProgDir. "secure.php");
	$db = connect_db();
}

$id = getParam('id', $_SESSION['ID']);

# Als je niet voorkomt in de Admin-groep dan ga je naar je eigen gegevens
if(!in_array(1, getMyGroups($_SESSION['ID']))) {	
	$id = $_SESSION['ID'];
}

$personData = getMemberDetails($id);	
$unique = true;
$melding = '';

if(isset($_POST['username']) AND ($_POST['username'] != $personData['username']) AND !isUniqueUsername($_POST['username'])) {
	$unique = false;
	$melding = "username wordt al gebruikt";
}

if(isset($_POST['data_opslaan']) AND $unique) {
	#$sql = "UPDATE $TableUsers SET `$UserUsername` = '". addslashes($_POST['username']) ."'". ($_POST['wachtwoord'] != '' ? ", `$UserPassword` = '". md5($_POST['wachtwoord']) ."', $UserNewPassword = '". password_hash($_POST['wachtwoord'], PASSWORD_DEFAULT) ."'" : '') ." WHERE `$UserID` = ". $_POST['id'];
	$sql = "UPDATE $TableUsers SET `$UserUsername` = '". addslashes($_POST['username']) ."'". ($_POST['wachtwoord'] != '' ? ", $UserNewPassword = '". password_hash($_POST['wachtwoord'], PASSWORD_DEFAULT) ."'" : '') ." WHERE `$UserID` = ". $_POST['id'];
		
	if(!mysqli_query($db, $sql) ) {
		$text[] = "Er is een fout opgetreden.";
		$text[] = $sql;
		toLog('error', $_SESSION['ID'], $_POST['id'], 'Fout met wijzigen accountgegevens');
	} else {
		$text[] = "Account succesvol gewijzigd.";
		toLog('info', $_SESSION['ID'], $_POST['id'], 'Accountgegevens gewijzigd');
	}			
} else {
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<input type='hidden' name='id' value='$id'>";
	if(isset($_REQUEST['hash'])) {
		$text[] = "<input type='hidden' name='hash' value='". $_REQUEST['hash'] ."'>";
	}
	$text[] = "<table border=0 width=100%>";
	$text[] = "<tr>";
	$text[] = "	<td width=4% rowspan='2'>&nbsp;</td>";
	$text[] = "	<td width=44%><h1>Accountgegevens</h1></td>";
	$text[] = "	<td width=4% rowspan='2'>&nbsp;</td>";
	$text[] = "	<td width=44%><h1>2 factor toegang (2FA)</h1></td>";
	$text[] = "	<td width=4% rowspan='2'>&nbsp;</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	//$text[] = "	<td>&nbsp;</td>";
	$text[] = "	<td valign='top'>";
	$text[] = "	<table width='100%' border=0>";
	$text[] = "	<tr>";
	$text[] = "		<td colspan='2'>Mocht u niet tevreden zijn met uw huidige gebruikersnaam en wachtwoord dan kunt u die hier wijzigen.</td>";
	$text[] = "	</tr>";
	$text[] = "	<tr>";
	$text[] = "		<td valign='top'>Gebruikersnaam</td>";
	$text[] = "		<td valign='top'><input type='text' name='username' value='".$personData['username']."'></td>";
	$text[] = "	</tr>";
	
	if($melding != '') {
		$text[] = "	<tr>";
		$text[] = "		<td valign='top'>&nbsp;</td>";
		$text[] = "		<td valign='top'>$melding</td>";
		$text[] = "	</tr>";
	}
		
	$text[] = "	<tr>";
	$text[] = "		<td valign='top'>Wachtwoord</td>";
	$text[] = "		<td valign='top'><input type='text' name='wachtwoord' value=''></td>";
	$text[] = "	</tr>";
	$text[] = "	<tr>";
	$text[] = "		<td colspan='5' align='center'><input type='submit' name='data_opslaan' value='Opslaan'></td>";
	$text[] = "	</tr>";
	$text[] = "	</table>";
	$text[] = "	</td>";
	$text[] = "	<td valign='top'>";
	$text[] = " Het is mogelijk uw account beter te beveiligen door naast een gebruikersnaam & wachtwoord-combinatie, ook gebruik te maken van een code die uw telefoon genereert als u wilt inloggen. Dat heeft 2-factor authenticatie. Op die manier moet u inloggen met iets dat u weet (gebruikersnaam & wachtwoord) en iets dat u heeft (telefoon), dubbel zo veilig dus.<br>";
	$text[] = "Via onderstaande link krijgt u meer informatie over 2-factor authenticatie en hoe dat in te stellen.<br>";
	$text[] = "<br>";
	$text[] = "<a href='2FA.php'>".(get2FACode($_SESSION['ID']) != '' ? 'Zet 2FA uit' : 'Zet 2FA aan')."</a>.";
	$text[] = "	</td>";
	$text[] = "</tr>";
	$text[] = "</table>";
}


echo $HTMLHeader;
echo implode(NL, $text);
echo $HTMLFooter;

?>
