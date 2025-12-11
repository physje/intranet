<?php
include_once('../Classes/Mysql.php');
include_once('../Classes/Team.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

if(!isset($_REQUEST['id'])) {
	echo "geen groep gedefinieerd";
	exit;
}

$team = new Team($_REQUEST['id']);
$beheerder = $team->beheerder;

$requiredUserGroups = array(1, $beheerder);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_POST['change_members'])) {
	$team->emptyLeden();
	$team->leden = $_POST['ids'];
	
	if($_POST['nieuw_lid'] != '') {
		$delen = explode('|', $_POST['nieuw_lid']);
		$newLidID = $delen[1];
		$team->addLid($newLidID);		
		toLog('Als lid toegevoegd aan '. $team->name, 'debug', $newLidID);
	}
	$team->save();
	toLog('Leden '. $team->name .' gewijzigd');
}

/*
if(isset($_POST['change_site'])) {	
	$set[] = "$GroupHTMLIn = '". urlencode($_POST['intern']) ."'";
	$set[] = "$GroupHTMLEx = '". urlencode($_POST['extern']) ."'";
	
	$sql = "UPDATE $TableGroups SET ". implode(", ", $set) ." WHERE $GroupID = ". $_REQUEST['groep'];
	mysqli_query($db, $sql);
		
	toLog('info', '', 'Tekst voor groeppagina '. $groupData['naam'] .' gewijzigd');
}
	*/

$team = new Team($_REQUEST['id']);
$GroupMembers = $team->leden;

$block_1[] = "<h2>Leden</h2>";
$block_1[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$block_1[] = "<input type='hidden' name='id' value='". $_REQUEST['id'] ."'>";

foreach($GroupMembers as $lid) {
	$person = new Member($lid);
	$block_1[] = "<input type='checkbox' name='ids[]' value='$lid' checked> <a href='../profiel.php?id=$lid'>". $person->getName() ."</a><br>";
}

$block_1[] = "<br>";
$block_1[] = "Selecteer lid om toe te voegen (na selectie wordt nummer toegevoegd).<br>";
$block_1[] = "<input type='text' id='namen_input' name='nieuw_lid' placeholder='Begin met typen van naam'><br>";
$block_1[] = "<p class='after_table'><input type='submit' name='change_members' value='Leden wijzigen'></p>";
$block_1[] = "</form>";

/*
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
*/

$header[] = "	<!-- jQuery library -->";
$header[] = "	<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>";
$header[] = "	<!-- jQuery UI library -->";
$header[] = "	<link rel='stylesheet' href='https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css'>";
$header[] = "	<script src='https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js'></script>";
$header[] = "	<script>";
$header[] = "		$(function() {";
$header[] = "		    $(\"#namen_input\").autocomplete({";
$header[] = "		    	minLength: 3,";
$header[] = "		    	source: \"../autocomplete_namen.php\",";
$header[] = "		    	select: function( event, ui ) {";
$header[] = "		    		event.preventDefault();";
$header[] = "		    		$(\"#namen_input\").val(ui.item.selector);";
$header[] = "		    	}";
$header[] = "		    });";
$header[] = "		});";
$header[] = "		</script>";

echo showCSSHeader(array('default'), $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>'. $team->name .'</h1>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $block_1).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;

#echo '<div class="content_vert_kolom">'.NL;
#echo "<div class='content_block'>".NL. implode(NL, $block_2).NL."</div>".NL;
#echo "<div class='content_block'>".NL. implode(NL, $block_3).NL."</div>".NL;
#echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;

echo showCSSFooter();

?>