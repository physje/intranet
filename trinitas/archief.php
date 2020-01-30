<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$jaargangen = getJaargangen();

foreach($jaargangen as $jaargang) {
	$nummers = getNrInJaargang($jaargang);
	$Code[] = "<h1>Jaargang $jaargang</h1>";
	foreach($nummers as $nummer) {
		$data = getTrinitasData($nummer);
		$Code[] = "<a href='download.php?fileID=$nummer'>". makeTrinitasName($nummer, 3) ."</a>". (in_array(1, getMyGroups($_SESSION['ID'])) ? " (<a href='exemplaar.php?fileID=$nummer'>edit</a>)" : '');		
	}
	
	$HTML[] = implode("<br>\n", $Code);
	unset($Code);	
}

# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '	<td valign="top">'.NL;

$scheiding = ceil(count($HTML)/3);
$counter = 0;

foreach($HTML as $key => $block) {
	if($scheiding == $counter) {
		echo '	</td>'.NL;
		echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
		echo '	<td valign="top">'.NL;
		$counter = 0;
	}
	echo showBlock($block, 100);
	echo '<p>'.NL;
	$counter++;
}
echo '	</td>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;

?>