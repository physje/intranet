<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('Classes/Member.php');
include_once('Classes/Wijk.php');
include_once('Classes/Logging.php');
include_once('include/HTML_TopBottom.php');
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

$letter = getParam('letter', '');
$wijk = getParam('wijk', '');

if($letter == '' && $wijk == '') {
	$data = new Member($_SESSION['useID']);	
	$letter = $data->achternaam[0];
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


if($letter != '') {
	$leden = Member::getMembersByLetter($letter, false);
	toLog("Ledenlijst letter $letter", 'debug');
} elseif($wijk != '') {
	$leden = Member::getMembersByLetter($wijk, true);
	toLog("Ledenlijst wijk $wijk", 'debug');
}

if(count($leden)) {
	foreach($leden as $id) {
		$lid = new Member($id);
		$text[] = "<a href='profiel.php?id=". $lid->id ."'>". $lid->getName(5)."</a><br>";
	}	
}
	
echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>Ledenlijst</h1>'.NL;
echo "<div class='content_block'>". implode(NL, $text) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();
?>