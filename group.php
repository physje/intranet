<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

if(!isset($_REQUEST['groep'])) {
	echo "geen groep gedefinieerd";
	exit;
}

$groep	= getParam('groep', '');
$extern	= getParam('extern', false);

$myGroups = getMyGroups($_SESSION['ID']);
$groupData = getGroupDetails($groep);

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<h1>". $groupData['naam'] ."</h1>";
echo '<div class="content_block">'.NL;
if(in_array($groep, $myGroups) AND $groupData['html-int'] != "" AND !$extern) {
	#echo '<p>'.NL;
	echo $groupData['html-int'];
	echo '<p>&nbsp;</p>'.NL;
	echo "<a href='?groep=$groep&extern=true'>Bekijk externe pagina</a>".NL;	
} elseif($groupData['html-ext'] != "" OR $extern) {
	echo '<p>'.NL;
	echo $groupData['html-ext'];
} else {
	echo "Deze pagina bestaat niet.";
}

echo '</div> <!-- end \'content_block\' -->'.NL;	
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

toLog('debug', $_SESSION['ID'], '', 'Groep-pagina '. $groupData['naam'] .' bekeken');

?>