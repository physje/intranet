<?php
/**************************************************************/
/*              phpSecurePages version 0.43 beta               */
/*              Copyright 2015 Circlex.com, Inc.              */
/*                                                            */
/*          ALWAYS CHECK FOR THE LATEST RELEASE AT            */
/*              http://www.phpSecurePages.com                 */
/*                                                            */
/*              Free for non-commercial use only.             */
/*               If you are using commercially,               */
/*         or using to secure your clients' web sites,        */
/*   please purchase a license at http://phpsecurepages.com   */
/*                                                            */
/**************************************************************/
/*      There are no user-configurable items on this page     */
/**************************************************************/

# check login with Database

# Check if secure.php has been loaded correctly
if (!defined("LOADED_PROPERLY") || isset($_GET['cfgProgDir']) || isset($_POST['cfgProgDir'])) {
	echo "Parsing of phpSecurePages has been halted!";
	exit();
}

# contact database
if (empty($cfgServerPort)) {
	$db = mysqli_connect($cfgServerHost, $cfgServerUser, $cfgServerPassword) or die($strNoConnection);
} else {
	$db = mysqli_connect($cfgServerHost, $cfgServerUser, $cfgServerPassword, $cfgServerPort) or die($strNoConnection);
}

mysqli_select_db($db, $cfgDbDatabase) or die(mysqli_error());

$login = mysqli_real_escape_string($db, $login);
$userQuery = mysqli_query($db, "SELECT * FROM $cfgDbTableUsers WHERE $cfgDbLoginfield = '$login'") or die($strNoDatabase);

# check user and password
if(mysqli_num_rows($userQuery) != 0) {
	# user exist --> continue
	$userArray = mysqli_fetch_array($userQuery);
	
	if ($login != $userArray[$cfgDbLoginfield]) {
		toLog('debug', '', '', 'Inlogpoging vanaf '. $_SERVER['REMOTE_ADDR'] .', verkeerd getypte username |'. $login .'|');
		# Case sensative user not present in database
		$phpSP_message = $strUserNotExist;
    		include($cfgProgDir . "interface.php");
    		exit;
	}
} else {
	toLog('debug', '', '', 'Inlogpoging vanaf '. $_SERVER['REMOTE_ADDR'] .', onbekende username |'. $login .'|');
	# user not present in database
	$phpSP_message = $strUserNotExist;
  include($cfgProgDir . "interface.php");
  exit;
}

if(!$userArray[$cfgDbPasswordfield]) {
	# password not present in database for this user
	$phpSP_message = $strPwNotFound;
	include($cfgProgDir . "interface.php");
	exit;
}

if(!password_verify($password, $userArray["$cfgDbPasswordfield"])) {
	toLog('debug', '', '', 'Inlogpoging vanaf '. $_SERVER['REMOTE_ADDR'] .', verkeerd wachtwoord |'. $password .'|, username |'. $login .'|');
	# password is wrong
	$phpSP_message = $strPwFalse;
	include($cfgProgDir . "interface.php");
	exit;
}

if(isset($userArray["$cfgDbUserIDfield"]) && !empty($cfgDbUserIDfield)) {
	$_SESSION['realID'] = stripslashes($userArray["$cfgDbUserIDfield"]);	
}

# Om te kunnen vermommen als een ander lid
if(isset($_SESSION['fakeID'])) {
	$_SESSION['useID'] = $_SESSION['fakeID'];
} else {
	$_SESSION['useID'] = $_SESSION['realID'];
}

if(isset($requiredUserGroups)) {
	$authorisatieArray = getMyGroups($_SESSION['useID']);
	$overlap = array_intersect ($requiredUserGroups, $authorisatieArray);
	
	if(count($overlap) == 0) {
		# this user does not have the required user level
		toLog('info', $_SESSION['useID'], '', 'Ingelogd maar onvoldoende rechten voor '. $_SERVER['PHP_SELF']);		
		$phpSP_message = $strUserNotAllowed;
		include($cfgProgDir . "interface.php");
		exit;
	}		
}



# Als je voor de eerste keer hier komt (lees, nog nooit het einde van dit script gehaald)
# moet gecheckt worden of je een 2FA moet invoeren
# en moet de administratie worden bijgewerkt
if(!$_SESSION['logged']) {
	$secret_key = get2FACode($_SESSION['realID']);
	
	# Alleen als er een secret-key bekend is, en 2FA dus aan staat
	# de loop van 2FA doorlopen
	if($secret_key != '') {
		if(isset($_POST['entered_2FA'])) {
			include_once($cfgProgDir.'../include/google2fa/2FA.php');
  		$google2fa = new \PragmaRX\Google2FA\Google2FA();
  		
			if(!$google2fa->verifyKey($secret_key, $_POST['entered_2FA'])) {			
				toLog('debug', '', 'Foutieve 2FA-code');
				$phpSP_message = 'Onjuiste code';
				include($cfgProgDir . "2FA.php");
				exit;
			}			
		} else {
			include($cfgProgDir . "2FA.php");
			exit;
		}
	}
		
	# Long-term store
	storeLogin($_SESSION['realID'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
	
	# Schrijf inlog in logfiles weg
	toLog('info', '', 'Inlogpoging vanaf '. $_SERVER['REMOTE_ADDR']);
}


# Stel een sessie-variabele in om aan te geven dat iemand ingelogd is.
$_SESSION['logged'] = true;

?>
