<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();

# Om zo te kunnen controleren of iemand is ingelogd, even de sessie starten.
session_start(['cookie_lifetime' => $cookie_lifetime]);

# Het eerste scherm waarin men de keuze kan maken welk type declaratie men wil uitvoeren
$page[] = "In welke hoedanigheid wilt u een declaratie doen?<br>";
$page[] = "<ul>";
$page[] = "<li><a href='gastpredikant.php'>Gastpredikant</a></li>";
$page[] = "<li><a href='gemeentelid.php'>Gemeentelid</a></li>";

$toegestaan = array_merge(getGroupMembers(1), getGroupMembers(38));
if(isset($_SESSION['useID']) AND in_array($_SESSION['useID'], $toegestaan)) {
	$page[] = "<li><a href='cluco.php'>Cluster-coordinator</a></li>";
	$page[] = "<li><a href='penningmeester.php'>Penningmeester</a></li>";
}

$page[] = "</ul>";

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $page).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>
