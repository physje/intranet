<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();

if(isset($_REQUEST['showLogin'])) {
	$cfgProgDir = '../auth/';
	include($cfgProgDir. "secure.php");
	$db = connect_db();
}

if(!isset($_SESSION['ID'])) {
	session_start(['cookie_lifetime' => $cookie_lifetime]);
	$ingelogd = false;
} else {
	$memberData = getMemberDetails($_SESSION['ID']);
	$ingelogd = true;
}

$text[] = "<h1>Handleiding ". $ScriptTitle ."</h1><br>";
$text[] = "Welkom op de site met de handleiding voor ". $ScriptTitle .".<br>";
$text[] = "<br>";
$text[] = "Deze handleiding legt per onderdeel uit wat er mogelijk is en hoe je bepaalde zaken doet of regelt.";
$text[] = "<br>";

if(!$ingelogd) {	
	$text[] = "De handleiding is nu nog algemeen, als je bent <a href='?showLogin=true'>ingelogd</a> worden een aantal onderdelen en links persoonlijk gemaakt.<br>";
}

/*
$text[] = "<br>";
$text[] = "<a id='achtergrond'></a><h2>Achtergrond</h2>";
$text[] = "Scipio vs site";
*/

if(!$ingelogd) {	
	$text[] = "<a id='inloggen'></a><h2>Inloggen</h2>";
	$text[] = "Om in te loggen heb je inloggegevens nodig. Deze gegevens zijn gekoppeld aan een account met hetzelfde mailadres zoals dat in Scipio/bij het kerkelijk bureau bekend is.<br>";
	$text[] = "Mocht je je inloggegevens niet weten, dan kan je die opvragen door <a href='../auth/wachtwoord.php'>deze link</a> te volgen en het bij Scipio bekende mailadres in te vullen en op 'Opvragen' te klikken. Er zal dan een mail gestuurd worden met instructies om de inloggegevens te bemachtigen<br>";
	$text[] = "Met deze inloggegevens kan je vervolgens inloggen op <a href='$ScriptURL'>$ScriptURL</a><br>";
}

#$text[] = "<a id='account'></a><h2>Account</h2>";
#$text[] = "2FA";
#$text[] = "<br>";

$text[] = "<a id='rooster'></a><h2>Roosters</h2>";
$text[] = "Als je bent ingelogd";
$text[] = "Cursief is rooster waar jij mogelijk op staat<br>";
$text[] = "Verwijzing naar digitale agenda<br>";
$text[] = "Verwijzing naar combi rooster<br>";
$text[] = "<a id='rooster_beheer'></a><h3>Roosters beheren</h3>";
$text[] = "<br>";

$text[] = "<a id='groepen'></a><h2>Groepen</h2>";
$text[] = "<a id='groepen_beheer'></a><h3>Groepen beheren</h3>";
$text[] = "<br>";

$text[] = "<a id='open_kerk'></a><h2>Open kerk</h2>";
$text[] = "<br>";

$text[] = "<a id='laposta'></a><h2>La Posta</h2>";
$text[] = "<br>";

$text[] = "<a id='declaratie'></a><h2>Declaratie</h2>";
$text[] = "<br>";


$text[] = "<a id='agenda'></a><h2>Agenda Scipio</h2>";
$text[] = "<br>";

$text[] = "<a id='laposta'></a><h2>La Posta</h2>";
$text[] = "<br>";

$text[] = "<a id='gebed'></a><h2>Gebedskalender</h2>";
$text[] = "<br>";

$text[] = "<a id='laposta'></a><h2>Overig</h2>";
$text[] = "Account<br>";
$text[] = "Profiel<br>";
$text[] = "Ledenlijst<br>";
$text[] = "<br>";

$text[] = "<a id='preek'></a><h2>Preekvoorziening</h2>";
$text[] = "<br>";

$text[] = "<a id='faq'></a><h2>Veel gestelde vragen</h2>";
$text[] = "Koppeling met Scipio";
$text[] = "<br>";
/*


$text[] = "Het is mogelijk om aan jouw digitale agenda, een losse agenda toe te voegen met daarin alle momenten dat jij voor de Koningskerk op het rooster staat. Wijzigingen op de site worden automatisch doorgevoerd in deze agenda.<br>";
$text[] = "<br>";
$text[] = "Op deze pagina staat een korte uitleg hoe je eean voor <a href='#android'>Android</a> of <a href='#ios'>iOS</a> instelt.<br>";


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
$text[] = "<li>Ga naar <a href='https://calendar.google.com/calendar/u/0/r/settings'>deze pagina</a> (mogelijk moet je inloggen op je Google-account) en zoek aan de linkerkant onder het kopje '<i>Instellingen voor andere agenda's</i>' naar je 3GK-agenda". ($agendaNaam != '' ? " [waarschijnlijk bij jouw '$agendaNaam']" : '') .".</li>";
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
*/

echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo "	<td width='15%' valign='top'>&nbsp;</td>".NL;
echo "	<td width='70%' valign='top'>". showBlock(implode(NL, $text), 100)."</td>".NL;
echo "	<td width='15%' valign='top'>&nbsp;</td>".NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;

?>