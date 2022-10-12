<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('shared.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 48);
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['token'])) {
	if(validVotingCode($_REQUEST['token'])) {
		if(uniqueVotingCode($_REQUEST['token'])) {
			$text[] = 'Deze stem is <b>niet</b> digitaal uitgebracht';
		} else {
			$text[] = 'Deze stem is digitaal uitgebracht';
		}				
	} else {
		$text[] = 'Deze persoon had helemaal niet mogen stemmen';
	}
}

echo $HTMLHeader;
echo implode(NL, $text);
echo $HTMLFooter;




?>