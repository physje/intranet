<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$maand = getParam('maand', date('n'));
$punten = getGebedspunten(date("Y-m-d", mktime(0,0,1,$maand,1,date("Y"))), date("Y-m-d", mktime(0,0,1,($maand+1),0,date("Y"))));

toLog('debug', '', 'Gebedskalender '. time2str("%h %y", mktime(0,0,1,$maand,1,date("Y"))) .' bekeken');
	
#$blockLinks = "<table>". NL;
#$blockLinks .= "<tr>". NL;
#$blockLinks .= "	<td colspan='2'><h1>". time2str("%B %Y", mktime(0,0,1,$maand,1,date("Y"))) ."</h1></td>". NL;
#$blockLinks .= "</tr>".NL;
	
foreach($punten as $punt) {
	$data = getGebedspunt($punt);	
		
	if(date("j m Y") == date("j m Y", $data['unix'])) {
		$prefix = '<b>';
		$postfix = '</b>';		
	} else {
		$postfix = $prefix = '';
	}
	
	#$blockLinks .= "<a href=>". NL;
	#$blockLinks .= "<tr>". NL;
	#$blockLinks .= "	<td valign='top'>".$prefix."<a id='".date('d', $data['unix'])."'></a>".time2str('%e', $data['unix']).$postfix."</td>". NL;
	#$blockLinks .= "	<td valign='top'>".$prefix.$data['gebedspunt'].$postfix."</td>". NL;
	#$blockLinks .= "</tr>".NL;
	
	if($data['gebedspunt'] != '') {
		$HTML = array("<a id='".date('d', $data['unix'])."'></a>".$prefix.time2str('%e', $data['unix']).'. '.$data['gebedspunt'].$postfix);
		$blocks[] = implode(NL, $HTML);
	}
}	

$prevPunten = getGebedspunten(date("Y-m-d", mktime(0,0,1,($maand-1),0,date("Y"))), date("Y-m-d", mktime(0,0,1,$maand,0,date("Y"))));
$nextPunten = getGebedspunten(date("Y-m-d", mktime(0,0,1,($maand+1),0,date("Y"))), date("Y-m-d", mktime(0,0,1,($maand+2),0,date("Y"))));

if(count($prevPunten) > 1 OR count($nextPunten) > 1) {
	$nav = "<div id='textbox'>";	
	if(count($prevPunten) > 1)	$nav .= "<p class='alignleft'><a href='?maand=". ($maand-1) ."'>". time2str("%B %Y", mktime(0,0,1,($maand-1),1,date("Y"))) ."</a></p>";
	if(count($nextPunten) > 1)	$nav .= "<p class='alignright'><a href='?maand=". ($maand+1) ."'>". time2str("%B %Y", mktime(0,0,1,($maand+1),1,date("Y"))) ."</a></p>";		
	$nav .= "</div>";
}

echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;
echo '<h1>'. time2str("%B %Y", mktime(0,0,1,$maand,1,date("Y"))) .'</h1>';

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