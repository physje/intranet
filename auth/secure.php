<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

# Create a constant that can be checked inside the files to be included.
# This gives an indication if secure.php has been loaded correctly.
define('LOADED_PROPERLY', true);

# Check if secure.php has been loaded correctly
if (isset($_GET['cfgProgDir']) || isset($_POST['cfgProgDir']) || isset($_GET['languageFile']) || isset($_POST['languageFile'])) {
    echo "Parsing of phpSecurePages has been halted!";
	exit();
}

# include configuration
require($cfgProgDir . 'config.php');

# https support
if (getenv('HTTPS') == 'on') {
	$cfgUrl = 'https://';
} else {
	$cfgUrl = 'http://';
}

# getting other variables
$phpSP_message = false;

# include functions and variables
if ( !defined("FUNCTIONS_LOADED") ) {
	# check if functions were already loaded
	include($cfgProgDir . 'objects/functions.php');
}

include($cfgProgDir . 'lng/' . $languageFile);

# choose between login or logout
if (isset($logout) && !(isset($_GET['logout']) || isset($_POST['logout']))) {
	# logout
	include($cfgProgDir . 'objects/logout.php');
} else {
    # starting login check
    if ($noDetailedMessages == true) {
        $strUserNotExist = $strUserNotAllowed = $strPwNotFound = $strPwFalse = $strNoPassword = $strNoAccess;
    }

    # make post variables global
    if (isset($_POST['entered_login']))       $entered_login = $_POST['entered_login'];
    if (isset($_POST['entered_password']))    $entered_password = $_POST['entered_password'];

    # check if login is necessary
    include($cfgProgDir . "objects/checklogin.php");

    # check login with Database
    include($cfgProgDir . 'objects/checklogin_db.php');
}

?>