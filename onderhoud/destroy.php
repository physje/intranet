<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Team.php');
include_once('../Classes/Declaratie.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Mysql.php');

# Kijk of er een sessie actief is, zo niet start de sessie
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$header = array();

if(isset($_POST['no'])) {
    $header[] = "    <meta http-equiv='refresh' content='2 url=". $ScriptURL ."' />";
    $page[] = 'Je wordt teruggebracht naar de startpagina.';
} elseif(isset($_POST['yes'])) {
    if(session_destroy()) {
        toLog('Sessie verwijderd');
        $header[] = "    <meta http-equiv='refresh' content='2 url=". $ScriptURL ."' />";
        $page[] = 'De sessie is verwijderd.<nr>';        
        $page[] = 'Je wordt teruggebracht naar de startpagina.';
    } else {
        $page[] = 'Er ging iets niet goed met verwijderen, probeer het opnieuw.';
    }
} else {
    $page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
    $page[] = 'Je staat op het punt de sessie te beëindigen.<br>';
    $page[] = '<br>';
    $page[] = 'Dit betekent :<ul>';
    $page[] = '<li>eventuele declararties die nog niet zijn ingediend worden verwijderd</li>';
    $page[] = '<li>je wordt uitgelogd</li>';
    $page[] = '</ul>';
    $page[] = '<br>';
    $page[] = 'Weet je dit zeker?<br>';
    $page[] = '<input type="submit" name="yes" value="Ja">&nbsp;<input type="submit" name="no" value="Nee">';
    $page[] = "</form>";
}

# Pagina tonen
$pageTitle = 'Destroy sessie';
include_once('../include/HTML_TopBottom.php');

echo showCSSHeader(array('default'), $header, $pageTitle);
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $page).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();
?>