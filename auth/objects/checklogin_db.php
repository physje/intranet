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
	$_SESSION['ID'] = stripslashes($userArray["$cfgDbUserIDfield"]);
	
	if(!$_SESSION['logged']) {
		toLog('info', $_SESSION['ID'], '', 'Inlogpoging vanaf '. $_SERVER['REMOTE_ADDR']);
		
		# Noteer de laatste keer dat deze persoon is ingelogd
		$sql = "UPDATE $TableUsers SET $UserLastVisit = '". time() ."' WHERE $UserID like ". $_SESSION['ID'];
		mysqli_query($db, $sql);
		
		$_SESSION['logged'] = true;
	}
	
}

if(isset($requiredUserGroups)) {
	$authorisatieArray = getMyGroups($userArray["$cfgDbUserIDfield"]);
	$overlap = array_intersect ($requiredUserGroups, $authorisatieArray);
	
	if(count($overlap) == 0) {
		# this user does not have the required user level
		$phpSP_message = $strUserNotAllowed;
		include($cfgProgDir . "interface.php");
		exit;
	}		
}

?>
