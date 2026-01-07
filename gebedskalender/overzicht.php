<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Gebedspunt.php');
include_once('../Classes/Logging.php');

$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$maand = getParam('maand', date('n'));
$punten = Gebedspunt::getPunten(date("Y-m-d", mktime(0,0,1,$maand,1,date("Y"))), date("Y-m-d", mktime(0,0,1,($maand+1),0,date("Y"))));

toLog('Gebedskalender '. time2str("F y", mktime(0,0,1,$maand,1,date("Y"))) .' bekeken', 'debug');

	
foreach($punten as $punt) {
	$gebedspunt = new Gebedspunt($punt);
		
	if($gebedspunt->dag == date('d') && $gebedspunt->maand == date('m')) {
		$prefix = '<b>';
		$postfix = '</b>';		
	} else {
		$postfix = $prefix = '';
	}
	
	if($gebedspunt->gebedspunt != '') {
		$HTML = array("<a id='".substr('0'.$gebedspunt->dag, -2)."'></a>".$prefix.$gebedspunt->dag.'. '.$gebedspunt->gebedspunt.$postfix);
		$blocks[] = implode(NL, $HTML);
	}
}	

$prevPunten = Gebedspunt::getPunten(date("Y-m-d", mktime(0,0,1,($maand-1),0,date("Y"))), date("Y-m-d", mktime(0,0,1,$maand,0,date("Y"))));
$nextPunten = Gebedspunt::getPunten(date("Y-m-d", mktime(0,0,1,($maand+1),0,date("Y"))), date("Y-m-d", mktime(0,0,1,($maand+2),0,date("Y"))));

if(count($prevPunten) > 1 OR count($nextPunten) > 1) {
	$nav = "<div id='textbox'>";	
	if(count($prevPunten) > 1)	$nav .= "<p class='alignleft'><a href='?maand=". ($maand-1) ."'>". time2str("F Y", mktime(0,0,1,($maand-1),1,date("Y"))) ."</a></p>";
	if(count($nextPunten) > 1)	$nav .= "<p class='alignright'><a href='?maand=". ($maand+1) ."'>". time2str("F Y", mktime(0,0,1,($maand+1),1,date("Y"))) ."</a></p>";		
	$nav .= "</div>";
}

echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;
echo '<h1>'. time2str("F Y", mktime(0,0,1,$maand,1,date("Y"))) .'</h1>';

foreach($blocks as $block) {
	echo "<div class='content_block'>". $block ."</div>".NL;
}

echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo "</div><div class='row'>";
echo '<div class="content_vert_kolom_full">'.NL;
echo $nav.NL;
echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>