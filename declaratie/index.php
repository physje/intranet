<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();


# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '	<td valign="top">'. showBlock('Hier kan men op termijn zijn of haar declaraties doen', 100). '</td>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;




















//echo $_SESSION['ID'];

/*
# LINKS
$links['account.php']					= 'Account';
$links['archief.php']						= 'Archief';
$links['search.php']						= 'Zoeken op woorden';
$links['auth/objects/logout.php']										= 'Uitloggen';	

foreach($links as $url => $titel) {
	$blockLinks .= "<a href='$url' target='_blank'>$titel</a><br>\n";
}

$blockArray[] = $blockLinks;

# BEHEERDER & ADMIN
if($_SESSION['level'] >= 2) {
	$beheer['exemplaar.php']	= 'Voeg exemplaar Trinitas toe';
	$beheer['sendMail.php']	= 'Verstuur klaarstaande mail';
	$beheer['stats.php']	= 'Bekijk download-statistieken';
	$beheer['stats_user.php']	= 'Bekijk statistieken per gebruiker';
			
	$admin['new_account.php?adminAdd']	= 'Voeg account toe';
		
	if($_SESSION['level'] >= 3) {
		$beheer['sendMail.php?testRun=true']	= 'Test klaarstaande mail';
		
		$admin['account.php?all']	= 'Toon alle accounts';
		//$admin['renewHash.php']	= 'Vernieuw gebruikers-hash';
		$admin['generateURL.php']	= 'Genereer URL';
		$admin['log.php']	= 'Bekijk logfiles';
	}
		
	foreach($beheer as $url => $titel) {
		$blockBeheer .= "<a href='$url' target='_blank'>$titel</a><br>\n";
	}

	foreach($admin as $url => $titel) {
		$blockAdmin .= "<a href='$url' target='_blank'>$titel</a><br>\n";
	}
		
	$blockArray[] = $blockBeheer;
	$blockArray[] = $blockAdmin;
}

//echo $HTMLHeader;
//echo "<tr>\n";
//
//# Als er maar 1 blok is, is het mooier die gecentreerd te hebben
//if($_SESSION['level'] == 1) {
//	echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
//	echo "<td width='50%' valign='center' align='center'>\n";
//# Als er meer blokken zijn, dan gewoon in 2 kolommen bovenaan
//} else {
//	echo "<td width='50%' valign='top' align='center'>\n";
//}
//echo showBlock($blockLinks);
//if($_SESSION['level'] == 1) {
//	echo "</td>\n";
//	echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
//} else {
//	echo "</td><td width='50%' valign='top' align='center'>\n";
//	if(isset($blockBeheer)) {
//		echo showBlock($blockBeheer);
//	}	
//	if(isset($blockAdmin)) {
//		echo "<p>\n";
//		echo showBlock($blockAdmin);
//	}	
//	echo "</td>\n";	
//}
//echo "</tr>\n";
//echo $HTMLFooter;

verdeelBlokken($blockArray);
*/
?>
