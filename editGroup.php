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

$requiredUserGroups = array(1, $beheerder);
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

if(isset($_POST['change_members'])) {
	removeGroupLeden($_POST['groep']);
	
	if(isset($_POST['ids'])) {
		foreach($_POST['ids'] as $lid) {
			addGroupLid($lid, $_POST['groep']);
		}
	}
	
	if($_POST['nieuw_lid'] != '') {
		$delen = explode('|', $_POST['nieuw_lid']);
		$newLidID = $delen[1];
		addGroupLid($newLidID, $_POST['groep']);
	}
	toLog('info', '', 'Leden '. $groupData['naam'] .' gewijzigd');
}

if(isset($_POST['change_site'])) {	
	$set[] = "$GroupHTMLIn = '". urlencode($_POST['intern']) ."'";
	$set[] = "$GroupHTMLEx = '". urlencode($_POST['extern']) ."'";
	
	$sql = "UPDATE $TableGroups SET ". implode(", ", $set) ." WHERE $GroupID = ". $_REQUEST['groep'];
	mysqli_query($db, $sql);
		
	toLog('info', '', 'Tekst voor groeppagina '. $groupData['naam'] .' gewijzigd');
}

$GroupMembers = getGroupMembers($_REQUEST['groep']);
$groupData = getGroupDetails($_REQUEST['groep']);

$block_1[] = "<h2>Leden</h2>";
$block_1[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$block_1[] = "<input type='hidden' name='groep' value='". $_REQUEST['groep'] ."'>";

foreach($GroupMembers as $lid) {
	$block_1[] = "<input type='checkbox' name='ids[]' value='$lid' checked> <a href='profiel.php?id=$lid'>". makeName($lid, 5) ."</a><br>";
}

$block_1[] = "<br>";
$block_1[] = "Selecteer lid om toe te voegen (na selectie wordt nummer toegevoegd).<br>";
$block_1[] = "<input type='text' id='namen_input' name='nieuw_lid' placeholder='Begin met typen van naam'><br>";
$block_1[] = "<p class='after_table'><input type='submit' name='change_members' value='Leden wijzigen'></p>";
$block_1[] = "</form>";

$block_2[] = "<h2>Interne pagina</h2>";
$block_2[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$block_2[] = "<input type='hidden' name='groep' value='". $_REQUEST['groep'] ."'>";
$block_2[] = "Tekst op de interne groep-pagina (alleen zichtbaar voor groepsleden).<br>";
$block_2[] = "<textarea name='intern'>". $groupData['html-int'] ."</textarea><br>";
$block_2[] = "Als in dit blok geen tekst staat, zal er geen pagina getoond worden voor leden van deze groep.<br>";
$block_2[] = "<p class='after_table'><input type='submit' name='change_site' value='Bewaren'></p>";

$block_3[] = "<h2>Externe pagina</h2>";
$block_3[] = "Tekst op de openbare groep-pagina (zichtbaar voor alle ingelogden).<br>";
$block_3[] = "<textarea name='extern' rows=30 cols=60>". $groupData['html-ext'] ."</textarea><br>";
$block_3[] = "Als in dit blok geen tekst staat, zal er geen externe pagina getoond worden.<br>";
$block_3[] = "<p class='after_table'><input type='submit' name='change_site' value='Bewaren'></p>";
$block_3[] = "</form>";

#$header[] = "	<link rel=\"stylesheet\" href=\"//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css\">";
#$header[] = "	<link rel=\"stylesheet\" href=\"/resources/demos/style.css\">";
#$header[] = "	<script src=\"https://code.jquery.com/jquery-1.12.4.js\"></script>";
#$header[] = "	<script src=\"https://code.jquery.com/ui/1.12.1/jquery-ui.js\"></script>";
#$header[] = "		<script>";
#$header[] = "		$(function() {";
#$header[] = '		var availableTags = ["'. implode('", "', $namen) ."\"];\n";
#$header[] = "		$( \"#namen\" ).autocomplete({";
#$header[] = "		source: availableTags";
#$header[] = "		});";
#$header[] = "	});";
#$header[] = "</script>";

$header[] = "	<!-- jQuery library -->";
$header[] = "	<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>";
$header[] = "	<!-- jQuery UI library -->";
$header[] = "	<link rel='stylesheet' href='https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css'>";
$header[] = "	<script src='https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js'></script>";
$header[] = "	<script>";
$header[] = "		$(function() {";
$header[] = "		    $(\"#namen_input\").autocomplete({";
$header[] = "		    	minLength: 3,";
$header[] = "		    	source: \"autocomplete_namen.php\",";
$header[] = "		    	select: function( event, ui ) {";
$header[] = "		    		event.preventDefault();";
$header[] = "		    		$(\"#namen_input\").val(ui.item.selector);";
$header[] = "		    	}";
$header[] = "		    });";
$header[] = "		});";
$header[] = "		</script>";

echo showCSSHeader(array('default'), $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>'. $groupData['naam'] .'</h1>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $block_1).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;

echo '<div class="content_vert_kolom">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $block_2).NL."</div>".NL;
echo "<div class='content_block'>".NL. implode(NL, $block_3).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;

echo showCSSFooter();
/*
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
*/


?>