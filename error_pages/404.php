<?php
# in de .htaccess is aangegeven dat deze pagina als 404 ingeladen moet worden (ipv de cPanel 404)

include_once('../include/functions.php');
include_once('../include/config.php');

if(strpos($_SERVER['REQUEST_URI'], '/ical/') !== false) {
	#$redirect = 'https://www.koningskerkdeventer.nl'.str_replace ('extern/3GK/intranet', 'intranet', $_SERVER['REQUEST_URI']);
	#$bericht = "De pagina is sinds kort verplaatst naar de site van <a href='https://www.koningskerkdeventer.nl'>de Koningskerk</a>.<br>Mocht je de oude pagina als bladwijzer hebben, pas deze bladwijzer dan aan naar <a href='$redirect'>$redirect</a>.";
	$fp = fopen('ical.csv', 'a+');
	fwrite($fp, date('d-m-Y H:i:s').';'. $_SERVER['REMOTE_ADDR'] .';'. $_SERVER['HTTP_USER_AGENT'] .';'.$_SERVER['REQUEST_URI']."\n");
	fclose($fp);
} else {
	$fp = fopen('404.csv', 'a+');
	fwrite($fp, date('d-m-Y H:i:s').';'. $_SERVER['REMOTE_ADDR'] .';'. $_SERVER['HTTP_USER_AGENT'] .';'.$_SERVER['REQUEST_URI']."\n");
	fclose($fp);
	
	$header[] = "<meta http-equiv='refresh' content='3; url=$ScriptURL' />\n";
}

$page[] = "<h2>Helaas</h2>";
$page[] = "De door jou opgevraagde pagina bestaat niet (meer).<br>Je wordt nu doorgestuurd naar de begin-pagina. Mogelijk is de URL aangepast.";
$page[] = "";
$page[] = "Pas indien mogelijk je bookmark aan zodat je niet meer op deze pagina komt";
$page[] = "";
$page[] = "Mocht je van mening zijn dat dit een fout betreft, geef dan het volgende aan de webmaster door: ". $_SERVER['REQUEST_URI'];

# Omdat hierboven bepaald is waar naartoe verwezen moet worden kan hier pas de HTML-code ingeladen worden
include_once('../include/HTML_TopBottom.php');

echo showCSSHeader(array('default'), $header, 'Oeps');
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>". implode("<br>".NL, $page) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>