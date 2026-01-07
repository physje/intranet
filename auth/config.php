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


/****** Error Messages ******/
$noDetailedMessages = false;
  // Show detailed error messages (false) or give one single message for all errors (true).
  // If set to 'false', the error messages shown to the user describe what went wrong.
  // This is more user-friendly, but less secure, because it could allow someone to probe
  // the system for existing users.

/****** Choose Language ******/
$languageFile = 'lng_dutch.php';        // Choose from one of the 40 language files in the /lng directory

// /* this data is necessary if a database is used */
// $cfgServerHost = $dbHostname;             // MySQL hostname
// $cfgServerPort = '';                      // MySQL port - leave blank for default port
// $cfgServerUser = $dbUsername;                  // MySQL user
// $cfgServerPassword = $dbPassword;                  // MySQL password

// $cfgDbDatabase = $dbName;        // MySQL database name containing phpSecurePages table
// $cfgDbTableUsers = $TableUsers;         // MySQL table name containing phpSecurePages user fields
// $cfgDbLoginfield = $UserUsername;                // MySQL field name containing login word
// //$cfgDbPasswordfield = $UserPassword;         // MySQL field name containing password
// $cfgDbPasswordfield = $UserNewPassword;         // MySQL field name containing password
// $cfgDbUserLevelfield = '';       // MySQL field name containing user level
//   // Choose a number which represents the category of this users authorization level.
//   // Leave empty if authorization levels are not used.
//   // See readme.txt for more info.
// $cfgDbUserIDfield = $UserID;        // MySQL field name containing user identification
//   // enter a distinct ID if you want to be able to identify the current user
//   // Leave empty if no ID is necessary.
//   // See readme.txt for more info.

/**************************************************************/
/*             End of phpSecurePages Configuration            */
/**************************************************************/
?>
