<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');
include_once('../../../general_include/class.phpmailer.php');

$db = connect_db();

# Het eerste scherm waarin men de keuze kan maken welk type declaratie men wil uitvoeren

$page[] = "In welke hoedanigheid wilt u een declaratie doen?<br>";
$page[] = "<ul>";
$page[] = "<li><a href='gastpredikant.php'>Gastpredikant</a></li>";
$page[] = "<li><a href='gemeentelid.php'>Gemeentelid</a></li>";
$page[] = "</ul>";

# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '	<td valign="top">'. showBlock(implode("\n", $page), 100). '</td>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;
?>
