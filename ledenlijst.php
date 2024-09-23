<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

/*
if(isset($_REQUEST['search_member']) OR !isset($_REQUEST['letter'])) {
	$leden = getMembers('all');
	foreach($leden as $lid) {
		$namen[$lid] = makeName($lid, 6);
	}
}

if(isset($_REQUEST['search_member'])) {
	$lidID = array_search($_POST['lid'], $namen);
	$redirect = $ScriptURL."/profiel.php?id=".$lidID;
	
	toLog('debug', $_SESSION['ID'], $lidID, 'gezocht op '. $_POST['lid']);
	$url="Location: ". $redirect;
	header($url);
}

if(!isset($_REQUEST['letter'])) {		
	echo $HTMLHead;
	echo "	<link rel='stylesheet' type='text/css' href='http://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css'>".NL;
	echo "	<script src=\"http://code.jquery.com/jquery-1.9.1.js\"></script>".NL;
	echo "	<script src=\"http://code.jquery.com/ui/1.10.2/jquery-ui.js\"></script>".NL;
	echo "	<link rel=\"stylesheet\" href=\"/resources/demos/style.css\" />".NL;
	echo "		<script>".NL;
	echo "		$(function() {".NL;
	echo '		var availableTags = ["'. implode('", "', $namen) ."\"];\n";
	echo "		$( \"#namen\" ).autocomplete({".NL;
	echo "		source: availableTags".NL;
	echo "		});".NL;
	echo "	});".NL;
	echo "</script>".NL;
	echo $HTMLBody;
	$letter = '';
} else {
	echo $HTMLHeader;
	$letter = $_REQUEST['letter'];
}
*/

$letter = getParam('letter', '');
$wijk = getParam('wijk', '');

if($letter == '' AND $wijk == '') {
	$data = getMemberDetails($_SESSION['useID']);
	$achternaam = $data['achternaam'];
	$letter = $achternaam[0];
}

$text[] = 'Achternaam | ';
	
foreach($letterArray as $key => $value) {
	if($key > 0) {
		$text[] = ' | ';
	}
	
	if($value == $letter) {
		$text[] = $value;
	} else {
		$text[] = "<a href='?letter=$value'>$value</a>";
	}
}
$text[] = '<br>';
$text[] = 'Wijk | ';

foreach($wijkArray as $key => $value) {
	if($key > 0) {
		$text[] = ' | ';
	}
	
	if($value == $wijk) {
		$text[] = $value;
	} else {
		$text[] = "<a href='?wijk=$value'>$value</a>";
	}
}
$text[] = '<p>';


/*
if(!isset($_REQUEST['letter'])) {
	echo "<form method='post' action='$_SERVER[PHP_SELF]' target='_blank'>";
	echo "Voer de naam in van de persoon die u zoekt.<br>";
	echo "<input type='text' name='lid' id=\"namen\" size='50'><br>";
	echo "<br>";
	echo "<input type='submit' name='search_member' value='Lid zoeken'>";
	echo "</form>";	
} else {
*/
if($letter != '') {
	$sql = "SELECT $UserID FROM $TableUsers WHERE $UserStatus = 'actief' AND $UserAchternaam like '$letter%' ORDER BY $UserAchternaam";
	toLog('debug', $_SESSION['realID'], '', "Ledenlijst letter $letter");
} elseif($wijk != '') {
	$sql = "SELECT $UserID FROM $TableUsers WHERE $UserStatus = 'actief' AND $UserWijk like '$wijk' ORDER BY $UserAchternaam";
	toLog('debug', $_SESSION['realID'], '', "Ledenlijst wijk $wijk");
}

$result = mysqli_query($db, $sql);
if($row	= mysqli_fetch_array($result)) {
	do {
		$text[] = "<a href='profiel.php?id=". $row[$UserID] ."'>". makeName($row[$UserID], 5)."</a><br>";
	} while($row	= mysqli_fetch_array($result));
}
//}
	
echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>Ledenlijst</h1>'.NL;
echo "<div class='content_block'>". implode(NL, $text) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();
?>