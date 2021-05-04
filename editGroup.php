<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');

$db = connect_db();

if(!isset($_REQUEST['groep'])) {
	echo "geen groep gedefinieerd";
	exit;
}

$beheerder = getBeheerder($_REQUEST['groep']);
		
$requiredUserGroups = array_merge(array(1), $beheerder);
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

$groupData = getGroupDetails($_REQUEST['groep']);
	
$leden = getMembers('all');
foreach($leden as $lid) {
	$namen[$lid] = makeName($lid, 6);
}

if(isset($_POST['change_members'])) {
	removeGroupLeden($_POST['groep']);
	
	if(isset($_POST['ids'])) {
		foreach($_POST['ids'] as $lid) {
			addGroupLid($lid, $_POST['groep']);
		}
	}
	
	if($_POST['nieuw_lid'] != '') {
		$newLidID = array_search($_POST['nieuw_lid'], $namen);
		addGroupLid($newLidID, $_POST['groep']);
	}
	toLog('info', $_SESSION['ID'], '', 'Leden '. $groupData['naam'] .' gewijzigd');
}

if(isset($_POST['change_site'])) {	
	$set[] = "$GroupHTMLIn = '". urlencode($_POST['intern']) ."'";
	$set[] = "$GroupHTMLEx = '". urlencode($_POST['extern']) ."'";
	
	$sql = "UPDATE $TableGroups SET ". implode(", ", $set) ." WHERE $GroupID = ". $_REQUEST['groep'];
	mysqli_query($db, $sql);
	toLog('info', $_SESSION['ID'], '', 'Tekst voor groeppagina '. $groupData['naam'] .' gewijzigd');
}

$GroupMembers = getGroupMembers($_REQUEST['groep']);

$block_1[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";                      
$block_1[] = "<input type='hidden' name='groep' value='". $_REQUEST['groep'] ."'>"; 

foreach($GroupMembers as $lid) {
	$block_1[] = "<input type='checkbox' name='ids[]' value='$lid' checked> <a href='profiel.php?id=$lid'>". makeName($lid, 5) ."</a><br>";
}

$block_1[] = "<br>\n";
$block_1[] = "Voer naam in om persoon toe te voegen.<br>";
$block_1[] = "<input type='text' name='nieuw_lid' id=\"namen\" size='50'><br>";
$block_1[] = "<br>";
$block_1[] = "<input type='submit' name='change_members' value='Leden wijzigen'>";
$block_1[] = "</form>";

$block_2[] = "<table width=100%>";
$block_2[] = "<tr>";
$block_2[] = "<td valign='top'>";
$block_2[] = "	<form method='post' action='$_SERVER[PHP_SELF]'>";                      
$block_2[] = "	<input type='hidden' name='groep' value='". $_REQUEST['groep'] ."'>";
$block_2[] = "	Tekst op de interne groep-pagina (alleen zichtbaar voor groepsleden).<br>";
$block_2[] = "	<textarea name='intern' rows=30 cols=60>". $groupData['html-int'] ."</textarea><br>";
$block_2[] = "Als in dit blok geen tekst staat, zal er geen pagina getoond worden voor leden van deze groep.<br>";
$block_2[] = "</td>";
$block_2[] = "<td width='6%'>&nbsp;</td>".NL;
$block_2[] = "<td valign='top'>";
$block_2[] = "	Tekst op de openbare groep-pagina (zichtbaar voor alle ingelogden).<br>";
$block_2[] = "	<textarea name='extern' rows=30 cols=60>". $groupData['html-ext'] ."</textarea><br>";
$block_2[] = "Als in dit blok geen tekst staat, zal er geen externe pagina getoond worden.<br>";
$block_2[] = "</td>";
$block_2[] = "</tr>";
$block_2[] = "<tr>";
$block_2[] = "	<td colspan=3 align='center'><input type='submit' name='change_site' value='Bewaren'></td>";
$block_2[] = "</tr>";
$block_2[] = "</table>";
$block_2[] = "</form>";

echo $HTMLHead;
echo "	<link rel=\"stylesheet\" href=\"//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css\">".NL;
echo "	<link rel=\"stylesheet\" href=\"/resources/demos/style.css\">".NL;
echo "	<script src=\"https://code.jquery.com/jquery-1.12.4.js\"></script>".NL;
echo "	<script src=\"https://code.jquery.com/ui/1.12.1/jquery-ui.js\"></script>".NL;
echo "		<script>".NL;
echo "		$(function() {".NL;
echo '		var availableTags = ["'. implode('", "', $namen) ."\"];\n";
echo "		$( \"#namen\" ).autocomplete({".NL;
//echo "		minLength: 3".NL;
echo "		source: availableTags".NL;
echo "		});".NL;
echo "	});".NL;
echo "</script>".NL;
echo $HTMLBody;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td rowspan="3" width="50">&nbsp;</td>'.NL;
echo '	<td colspan="3"><h1>'. $groupData['naam'] .'</h1></td>'.NL;
echo '	<td rowspan="3" width="50">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '<tr>'.NL;
echo '	<td width="47%" valign="top">'. showBlock(implode(NL, $block_1), 100) .'</td>'.NL;
echo '	<td width="6%">&nbsp;</td>'.NL;
echo '	<td width="47%" valign="top">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" colspan="3">'. showBlock(implode(NL, $block_2), 100) .'</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;

?>