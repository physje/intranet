<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');

if(isset($_REQUEST['showLogin'])) {
	$cfgProgDir = '../auth/';
	include($cfgProgDir. "secure.php");	
}

# Kijk of er een sessie actief is, zo niet start de sessie
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_lifetime' => $cookie_lifetime]);
}

if(isset($_SESSION['useID'])) {
	$member = new Member($_SESSION['useID']);
	
	$agendaURL = $ScriptURL .'ical/'. $member->hash_long .'.ics';
	$agendaNaam = "KKD (". $member->getName(1) .")";
} else {
	$agendaURL = '';
	$agendaNaam = '';
}

$text[] = "<h1>Handleiding toevoegen digitale agenda</h1><br>";
$text[] = "Het is mogelijk om aan jouw digitale agenda, een losse agenda toe te voegen met daarin alle momenten dat jij voor de Koningskerk op het rooster staat. Wijzigingen op de site worden automatisch doorgevoerd in deze agenda.<br>";
$text[] = "<br>";
$text[] = "Op deze pagina staat een korte uitleg hoe je eean voor <a href='#android'>Android</a> of <a href='#ios'>iOS</a> instelt.<br>";

if($agendaURL == '') {
	$text[] = "De handleiding is nu nog algemeen, als je bent <a href='?showLogin=true'>ingelogd</a> worden een aantal punten specifiek persoonlijk gemaakt.<br>";
}

$text[] = "<br>";
$text[] = "<a id='android'></a><h2>Android</h2>";
$text[] = "<a id='androidAdd'></a><h3>Toevoegen</h3>";
$text[] = "Om de agenda toe te voegen, doorloop je de volgende stappen :";
$text[] = "<ol>";
$text[] = "<li>Ga naar <a href='https://calendar.google.com/calendar/u/0/r/settings/addbyurl'>deze pagina</a> (mogelijk moet je inloggen op je Google-account).</li>";

if($agendaURL != '') {
	$text[] = "<li>Je ziet nu het veld '<i>Agenda-URL</i>', daar voer je in<br>$agendaURL</li>";
} else {
	$text[] = "<li>Je ziet nu het veld '<i>Agenda-URL</i>', daar voer je de link naar je persoonlijke agenda in</li>";
}
$text[] = "<li>Klik vervolgens op '<i>Agenda toevoegen</i>' om de agenda toe te voegen</li>";
$text[] = "<li>Als de agenda succesvol is toegevoegd, zie je onderin beeld kort de melding verschijnen '<i>Agenda toegevoegd</i>'.";
$text[] = "</ol>";
$text[] = "De agenda is nu toegevoegd, mocht je de naam willen wijzigen, kijk dan hieronder bij het kopje <a href='#AndroidChange'>Wijzigen</a>";
$text[] = "<a id='androidChange'></a><h3>Wijzigen</h3>";
$text[] = "Om de agenda die je in het verleden hebt toegevoegd, te wijzigen, doorloop je de volgende stappen :";
$text[] = "<ol>";
$text[] = "<li>Ga naar <a href='https://calendar.google.com/calendar/u/0/r/settings'>deze pagina</a> (mogelijk moet je inloggen op je Google-account) en zoek aan de linkerkant onder het kopje '<i>Instellingen voor andere agenda's</i>' naar je KKD-agenda". ($agendaNaam != '' ? " [waarschijnlijk bij jouw '$agendaNaam']" : '') .".</li>";
$text[] = "<li>Door op de agendanaam te klikken kan de naam gewijzigd worden.</li>";
$text[] = "<li>Wijzigingen worden vanzelf opgeslagen.</li>";
$text[] = "</ol>";
$text[] = "<a id='androidDel'></a><h3>Verwijderen</h3>";
$text[] = "<ol>";
$text[] = "<li>Ga naar <a href='https://calendar.google.com/calendar/u/0/r/settings'>deze pagina</a> (mogelijk moet je inloggen op je Google-account) en zoek aan de linkerkant onder het kopje '<i>Instellingen voor andere agenda's</i>' naar je 3GK-agenda". ($agendaNaam != '' ? " [waarschijnlijk bij jouw '$agendaNaam']" : '') .".</li>";
$text[] = "<li>Klik op de agendanaam en scroll helemaal naar beneden, naar de knop '<i>Afmelden</i>'.</li>";
$text[] = "<li>Er komt nu een pop-up-venster om te bevestigen dat je de agenda wilt verwijderen. Bevestig dit door op '<i>Agenda Verwijderen</i>' te klikken.</li>";
$text[] = "</ol>";
$text[] = "<br>";
$text[] = "<a id='ios'></a><h2>iOS</h2>";
$text[] = "<a id='iosAdd'></a><h3>Toevoegen</h3>";
$text[] = "<ol>";
$text[] = "<li>Ga op je iPhone naar 'Instellingen' -> 'Agenda' -> 'Accounts' -> 'Nieuwe account' -> 'Andere' -> 'Voeg agenda-abonnement toe'</li>";
if($agendaURL != '') {
	$text[] = "<li>Je ziet nu het veld '<i>Server</i>', daar voer je in<br>$agendaURL<br>en klikt op 'Volgende'.</li>";
} else {
	$text[] = "<li>Je ziet nu het veld '<i>Server</i>', daar voer je de link naar je persoonlijke agenda in en klikt op 'Volgende' en daarna 'Bewaar'.</li>";
}
$text[] = "</ol>";
$text[] = "De agenda is nu toegevoegd, mocht je de naam willen wijzigen, kijk dan hieronder bij het kopje <a href='#iosChange'>Wijzigen</a>";
$text[] = "<a id='iosChange'></a><h3>Wijzigen</h3>";
$text[] = "<ol>";
$text[] = "<li>Ga op je iPhone naar 'Instellingen' -> 'Agenda' -> 'Accounts' -> 'Agendas met abonnement' en zoek in deze lijst naar je 3GK-agenda". ($agendaNaam != '' ? " [waarschijnlijk bij jouw '$agendaNaam']" : '') ." en klik hier op.</li>";
$text[] = "<li>Hier kan de naam (Beschrijving) of internetadres van de agenda (Server) worden aangeast.</li>";
$text[] = "<li>Klik bovenaan op 'Gereed' om de wijzigingen op te slaan.</li>";
$text[] = "</ol>";
$text[] = "<a id='iosDel'></a><h3>Verwijderen</h3>";
$text[] = "<ol>";
$text[] = "<li>Ga op je iPhone naar 'Instellingen' -> 'Agenda' -> 'Accounts' -> 'Agendas met abonnement' en zoek in deze lijst naar je 3GK-agenda". ($agendaNaam != '' ? " [waarschijnlijk bij jouw '$agendaNaam']" : '') .".</li>";
$text[] = "<li>Klik op de agendanaam en scroll helemaal naar beneden, naar de knop '<i>Verwijder account</i>'.</li>";
$text[] = "<li>Er komt nu een pop-up-venster om te bevestigen dat je de agenda wilt verwijderen. Bevestig dit door op '<i>verwijderen account</i>' te klikken.</li>";
$text[] = "</ol>";


echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>". implode(NL, $text) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>
