<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$cfgProgDir = 'auth/';
include_once($cfgProgDir. "secure.php");
$db = connect_db();

if(!isset($_REQUEST['groep'])) {
	echo "geen groep gedefinieerd";
	exit;
}

$groep	= getParam('groep', '');
$extern	= getParam('extern', false);

$myGroups = getMyGroups($_SESSION['ID']);
$groupData = getGroupDetails($groep);
echo $HTMLHead;
echo $HTMLBody;

if(in_array($groep, $myGroups) AND $groupData['html-int'] != "" AND !$extern) {
	echo "<h1>". $groupData['naam'] ."</h1>";
	echo '<p>'.NL;
	echo $groupData['html-int'];
	echo '<p>'.NL;
	echo "<a href='?groep=$groep&extern=true'>Bekijk externe pagina</a>".NL;
} elseif($groupData['html-ext'] != "" OR $extern) {
	echo "<h1>". $groupData['naam'] ."</h1>";
	echo '<p>'.NL;
	echo $groupData['html-ext'];
} else {
	echo "Deze pagina bestaat niet.";
}
	
echo $HTMLFooter;

toLog('debug', $_SESSION['ID'], '', 'Groep-pagina '. $groupData['naam'] .' bekeken');

?>