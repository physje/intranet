<?php
/**************************************************************/
/*         phpSecurePages version 0.44 beta (04/02/15)        */
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
/*           Start of phpSecurePages Configuration            */
/**************************************************************/


/****** Installation ******/
$cfgIndexpage = '/index.php';
  // page to go to, if login is cancelled
  // Example: if your main page is http://www.mydomain.com/index.php
  // the value would be $cfgIndexpage = '/index.php'

/****** Admin Email ******/
$admEmail = '';
  // E-mail address of the site administrator
  // (This is being showed to the users on an error, so you can be notified by the users)
  // May be left blank

/****** Error Messages ******/
$noDetailedMessages = false;
  // Show detailed error messages (false) or give one single message for all errors (true).
  // If set to 'false', the error messages shown to the user describe what went wrong.
  // This is more user-friendly, but less secure, because it could allow someone to probe
  // the system for existing users.

/****** Choose Language ******/
$languageFile = 'lng_dutch.php';        // Choose from one of the 40 language files in the /lng directory

/****** IP-Restricted Access ******/
$use_IP_restricted_access=false;             // Set this to true if you need to additionally restrict access by IP address or by an IP address range
                                             // If set to 'false', IP checks will not be performed.

/****** Database ******/
$useDatabase = true;                     // Choose between using a database or data as input

/* this data is necessary if a database is used */
$cfgServerHost = $dbHostname;             // MySQL hostname
$cfgServerPort = '';                      // MySQL port - leave blank for default port
$cfgServerUser = $dbUsername;                  // MySQL user
$cfgServerPassword = $dbPassword;                  // MySQL password

$cfgDbDatabase = $dbName;        // MySQL database name containing phpSecurePages table
$cfgDbTableUsers = $TableUsers;         // MySQL table name containing phpSecurePages user fields
$cfgDbLoginfield = $UserUsername;                // MySQL field name containing login word
//$cfgDbPasswordfield = $UserPassword;         // MySQL field name containing password
$cfgDbPasswordfield = $UserNewPassword;         // MySQL field name containing password
$cfgDbUserLevelfield = '';       // MySQL field name containing user level
  // Choose a number which represents the category of this users authorization level.
  // Leave empty if authorization levels are not used.
  // See readme.txt for more info.
$cfgDbUserIDfield = $UserID;        // MySQL field name containing user identification
  // enter a distinct ID if you want to be able to identify the current user
  // Leave empty if no ID is necessary.
  // See readme.txt for more info.

/**************************************************************/
/*             End of phpSecurePages Configuration            */
/**************************************************************/
?>
