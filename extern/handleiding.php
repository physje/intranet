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

if(!isset($_SESSION['useID'])) {
	session_start(['cookie_lifetime' => $cookie_lifetime]);
	$ingelogd = false;
} else {
	$memberData = getMemberDetails($_SESSION['useID']);
	$ingelogd = true;
}

#$text[] = "<h1>Handleiding ". $ScriptTitle ."</h1><br>";
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
$text[] = "Als je bent ingelogd zie je linksboven aan het kopje \"<b>Roosters</b>\" met daaronder alle roosters zoals die momenteel bekend zijn.<br>";
$text[] = "<br>";
$text[] = "Het kan zijn dat in deze lijst met roosters een of meer roosters <i>cursief</i> zijn weergegeven. Als een rooster cursief staat, betekent dit dat jij in de poule zit van mensen die  op dit rooster kunnen komen.<br>";
$text[] = "<br>";
$text[] = "Als je op een rooster klikt, zie je het betreffende rooster.<br>";
$text[] = "Door rechtsboven op \"PDF-versie\" te klikken wordt het huidige rooster als PDF getoond. Deze PDF kan je opslaan of uitprinten (je moet dat wel oppassen dat je regelmatig kijkt op de PDF nog wel up-to-date is).<br>";
$text[] = "Door in het rooster op een datum te klikken, krijg je een overzicht van wie er op die specifieke datum een taak rondom de dienst heeft.<br>";
$text[] = "<br>";
$text[] = "Helemaal onderaan de lijst met roosters staat \"<a href='../showCombineRooster.php'>Toon combinatie-rooster</a>\" en \"<a href='../roosterKomendeWeek.php'>Toon rooster komende week</a>\". Met \"<a href='../showCombineRooster.php'>Toon combinatie-rooster</a>\" kunnen verschillende roosters naast elkaar getoond worden. Bijvoorbeeld als verschillende gezinsleden op verschillende roosters staan, kan met deze optie 1 familie-overzicht gemaakt worden. Ook deze kan worden opgeslagen als PDF (met ook hier de waarschuwing dat deze kan verouderen).<br>";
$text[] = "Zoals de titel al zegt, kan met \"<a href='../roosterKomendeWeek.php'>Toon rooster komende week</a>\" het rooster van komende week getoond worden. Wil je snel een overzicht hebben wie komende week een rol in de dienst hebben, dan kan je dat met deze pagina zien.<br>";
#
#
#$text[] = "Verwijzing naar digitale agenda<br>";

$text[] = "<a id='ruilen'></a><h3>Ruilen</h3>";
$text[] = "Het kan zijn dat je voor een rooster bent ingedeeld op een moment dat <br>";
$text[] = "<br>";

$myRoosterBeheer = getMyRoostersBeheer($_SESSION['useID']);
if($ingelogd AND count($myRoosterBeheer) > 0) {
	$text[] = "<a id='rooster_beheer'></a><h3>Roosters beheren</h3>";
	$text[] = "<br>";
}

$text[] = "<a id='groepen'></a><h2>Groepen</h2>";

$myGroepBeheer = getMyGroupsBeheer($_SESSION['useID']);
if($ingelogd AND count($myGroepBeheer) > 0) {
	$text[] = "<a id='groepen_beheer'></a><h3>Groepen beheren</h3>";
	$text[] = "<br>";
}

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

if(in_array(1, getMyGroups($_SESSION['useID'])) OR in_array(20, getMyGroups($_SESSION['useID'])) OR in_array(22, getMyGroups($_SESSION['useID'])) OR in_array(28, getMyGroups($_SESSION['useID']))) {
	$text[] = "<a id='preek'></a><h2>Preekvoorziening</h2>";
	$text[] = "<br>";
}

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

#echo $HTMLHeader;
#echo '<table border=0 width=100%>'.NL;
#echo '<tr>'.NL;
#echo "	<td width='15%' valign='top'>&nbsp;</td>".NL;
#echo "	<td width='70%' valign='top'>". showBlock(implode(NL, $text), 100)."</td>".NL;
#echo "	<td width='15%' valign='top'>&nbsp;</td>".NL;
#echo '</tr>'.NL;
#echo '</table>'.NL;
#echo $HTMLFooter;

echo showCSSHeader(array('default', 'table_default'));
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>Handleiding '. $ScriptTitle .'</h1>'.NL;
echo "<div class='content_block'>". implode(NL, $text) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>
