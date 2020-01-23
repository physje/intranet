<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$maand = getParam('maand', date('n'));
$punten = getGebedspunten(date("Y-m-d", mktime(0,0,1,$maand,1,date("Y"))), date("Y-m-d", mktime(0,0,1,($maand+1),0,date("Y"))));

toLog('debug', $_SESSION['ID'], '', 'Gebedskalender '. time2str("%h %y", mktime(0,0,1,$maand,1,date("Y"))) .' bekeken');
	
$blockLinks = "<table>". NL;
$blockLinks .= "<tr>". NL;
$blockLinks .= "	<td colspan='2'><h1>". time2str("%B %Y", mktime(0,0,1,$maand,1,date("Y"))) ."</h1></td>". NL;
$blockLinks .= "</tr>".NL;
	
foreach($punten as $punt) {
	$data = getGebedspunt($punt);	
		
	if(date("j m Y") == date("j m Y", $data['unix'])) {
		$prefix = '<b>';
		$postfix = '</b>';		
	} else {
		$postfix = $prefix = '';
	}
	
	$blockLinks .= "<a href=>". NL;
	$blockLinks .= "<tr>". NL;
	$blockLinks .= "	<td valign='top'>".$prefix."<a id='".date('d', $data['unix'])."'></a>".time2str('%e', $data['unix']).$postfix."</td>". NL;
	$blockLinks .= "	<td valign='top'>".$prefix.$data['gebedspunt'].$postfix."</td>". NL;
	$blockLinks .= "</tr>".NL;
}	

$prevPunten = getGebedspunten(date("Y-m-d", mktime(0,0,1,($maand-1),0,date("Y"))), date("Y-m-d", mktime(0,0,1,$maand,0,date("Y"))));
$nextPunten = getGebedspunten(date("Y-m-d", mktime(0,0,1,($maand+1),0,date("Y"))), date("Y-m-d", mktime(0,0,1,($maand+2),0,date("Y"))));

if(count($prevPunten) > 1 OR count($nextPunten) > 1) {
	$blockLinks .= "<tr>". NL;
	$blockLinks .= "	<td colspan='2'>&nbsp;</td>". NL;
	$blockLinks .= "</tr>". NL;
	$blockLinks .= "<tr>". NL;
	$blockLinks .= "	<table>". NL;
	$blockLinks .= "	<tr>". NL;
	$blockLinks .= "		<td width='50%' align='left'>". (count($prevPunten) > 1 ? "<a href='?maand=". ($maand-1) ."'>". time2str("%B %Y", mktime(0,0,1,($maand-1),1,date("Y"))) ."</a></td>" : '&nbsp;' ).'<td>'. NL;
	$blockLinks .= "		<td width='50%' align='right'>". (count($nextPunten) > 1 ? "<a href='?maand=". ($maand+1) ."'>". time2str("%B %Y", mktime(0,0,1,($maand+1),1,date("Y"))) ."</a></td>" : '&nbsp;' ).'<td>'. NL;
	$blockLinks .= "	</tr>". NL;
	$blockLinks .= "	</table>". NL;
	$blockLinks .= "</tr>". NL;	
}

$blockLinks .= "</table>". NL;

# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '	<td valign="top">'.NL;
echo showBlock($blockLinks, 100);
echo '	</td>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;

?>