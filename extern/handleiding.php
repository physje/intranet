<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Team.php');

# Kijk of er een sessie actief is, zo niet start de sessie
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(isset($_REQUEST['showLogin'])) {
	$cfgProgDir = '../auth/';
	include($cfgProgDir. "secure.php");	
}

if(!isset($_SESSION['useID'])) {
	session_start(['cookie_lifetime' => $cookie_lifetime]);
	$gebruiker = new Member();
	$ingelogd = false;
} else {
	$gebruiker = new Member($_SESSION['useID']);
	$ingelogd = true;
}

$inleiding[] = "Welkom op de site met de handleiding voor ". $ScriptTitle .".<br>";
$inleiding[] = "<br>";
$inleiding[] = "Het doel van het intranet is om te zorgen dat iedereen altijd bij de meest up-to-date informatie kan. Inmiddels maken zoveel gemeenteleden gebruik van het intranet, en staat er zoveel informatie op, dat sommigen door de bomen het bos niet meer zien.<br>";
$inleiding[] = "Vandaar deze handleiding die per onderdeel uitlegt wat er mogelijk is en hoe je bepaalde zaken doet of regelt.";

if(!$ingelogd) {	
	$text[] = "De handleiding is nu nog algemeen, als je bent <a href='?showLogin=true'>ingelogd</a> worden alleen die delen getoond waar jij rechten voor hebt, en worden links persoonlijk gemaakt.<br>";
	$blocks[] = implode(NL, $text);
	$text = array();
}

$myGroups = $gebruiker->getTeams();

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
	$text[] = "<br>";
	$blocks[] = implode(NL, $text);
	$text = array();
}

#$text[] = "<a id='account'></a><h2>Account</h2>";
#$text[] = "2FA";
#$text[] = "<br>";

$text[] = "<a id='rooster'></a><h2>Roosters</h2>";
$text[] = "Als je bent ingelogd zie je linksboven aan het kopje \"<b>Roosters</b>\" met daaronder alle roosters zoals die momenteel bekend zijn.<br>";
$text[] = "<br>";
$text[] = "Het kan zijn dat in deze lijst met roosters een of meer roosters <i>cursief</i> zijn weergegeven. Als een rooster cursief staat, betekent dit dat jij in de groep zit van mensen die op dit rooster ingepland kunnen worden.<br>";
$text[] = "<br>";
$text[] = "Als je op een rooster klikt, zie je het betreffende rooster.<br>";
$text[] = "Door rechtsboven op \"PDF-versie\" te klikken wordt het huidige rooster als PDF getoond. Deze PDF kan je opslaan of uitprinten (je moet dat wel oppassen dat je regelmatig kijkt op de PDF nog wel up-to-date is).<br>";
$text[] = "Door in het rooster op een datum te klikken, krijg je een overzicht van wie er in die dienst een taak heeft.<br>";
$text[] = "<br>";
$text[] = "Helemaal onderaan de lijst met roosters staat \"<a href='../combinatieRooster.php'>Toon combinatie-rooster</a>\" en \"<a href='../komendeWeek.php'>Toon rooster komende week</a>\". Met \"<a href='../combinatieRooster.php'>Toon combinatie-rooster</a>\" kunnen verschillende roosters naast elkaar getoond worden. Bijvoorbeeld als verschillende gezinsleden op verschillende roosters staan, kan met deze optie 1 familie-overzicht gemaakt worden. Ook deze kan worden opgeslagen als PDF (met ook hier de waarschuwing dat deze kan verouderen).<br>";
$text[] = "Zoals de titel al zegt, kan met \"<a href='../komendeWeek.php'>Toon rooster komende week</a>\" het rooster van komende week getoond worden. Wil je snel een overzicht hebben wie komende week een rol in de dienst hebben, dan kan je dat met deze pagina zien.<br>";
$text[] = "<br>";

//TODO: reminder-mail benoemen
#
#
#$text[] = "Verwijzing naar digitale agenda<br>";

$text[] = "<a id='ruilen'></a><h3>Ruilen</h3>";
$text[] = "Het kan zijn dat je voor een rooster bent ingedeeld op een moment dat het niet schikt. Dan kan je met iemand ruilen. Dat doe je door onder het kopje \"<b>Roosters</b>\" naar het betreffende rooster te gaan en vervolgens op het ruil-symbook (<img src='../images/wisselen.png'>) te klikken van het moment dat je wilt ruilen.<br>";
$text[] = "In het volgende scherm kan je vervolgens op de naam klikken van de persoon en moment waar jij mee wilt ruilen. Op het rooster zullen jullie nu van plek wisselen.<br>";
$text[] = "Er gaat een mailtje naar beide personen, maar check voordat je gaat ruilen wel even met de betreffende persoon of hij/zij akkoord is.<br>";
$text[] = "<br>";

if($ingelogd) {
	$myRoosterPlanning = $gebruiker->getPlannerRooster();
	$myRoosterBeheer = $gebruiker->getBeheerRooster();

	if((count($myRoosterBeheer) > 0 || count($myRoosterPlanning)) > 0) {
		$text[] = "<a id='rooster_beheer'></a><h3>Roosters wijzigen</h3>";
		$text[] = "Als je rechten hebt om een rooster te wijzigen zie je het kopje <b>Roosters die ik kan wijzigen</b> met daaronder alle roosters waar jij rechten voor hebt.<br>";
		$text[] = "Je kan rechten hebben om details van roosters te wijzigen (denk aan welke diensten ingepland moeten kunnen worden, wat de tekst in de reminder-mail is, of deze remindermail ook naar ouders en/of partner moet, etc.) of om de planning te wijzigen (wie wanneer op het rooster staat).<br>";
		$text[] = "Door op <i>details</i> achter het rooster te klikken waar je de details van wilt wijzigen, kom je in het scherm waar je deze kan aanpassen. Vergeet niet de wijzigingen op te slaan.<br>";
		$text[] = "Door op <i>planning</i> achter het rooster te klikken kom je op een pagina waar je de planning kan aanpassen. Bij de meeste roosters zie je bij elke dienst een drop-down menu met daarin alle team-leden. Mochten er in deze lijst namen ontbreken dan kan de beheerder deze toevoegen aan het team (zie <a href='#groepen_beheer'>Teams beheren</a>).<br>";
		#$text[] = "<br>";		
	}
}
$blocks[] = implode(NL, $text);
$text = array();


if($ingelogd) {
	$myGroepBeheer = $gebruiker->getBeheerTeams();

	if(count($myGroepBeheer) > 0) {
		$text[] = "<a id='groepen_beheer'></a><h2>Teams beheren</h2>";
		$text[] = "Als je rechten hebt om leden toe te voegen aan een team of juist te verwijderen, zie je het kopje <b>Teams die ik beheer</b> met daaronder alle teams waar jij rechten voor hebt om te wijzigen.<br>";
		$text[] = "Door op de naam van het team te klikken, kom je op een pagina waar alle huidige leden van het team opgesomd staan. Voor elk teamlid staat een vinkje en onderaan staat een invoerveld om nieuwe leden toe te voegen.<br>";
		$text[] = "Om een lid te verwijderen, haal je het vinkje voor zijn/haar naam weg en klik je op 'Leden wijzigen' onderaan de pagina.<br>";
		$text[] = "Om een lid toe te voegen, voer je in het invoerveld onderaan (een deel) van zijn/haar naam in. Er onstaat dan een kort lijstje met namen waar je het nieuwe team-lid uit kunt kiezen. Als je een naam geselecteerd hebt klik je op 'Leden wijzigen' onderaan de pagina op dit lid toe te voegen.<br>";
		#$text[] = "<br>";
		$blocks[] = implode(NL, $text);
		$text = array();
	}
}

# 8 = Ouderlingen
# 9 = Diakenen
# 34 = Predikanten
# 49 = Pastoraat super-user
# 50 = Pastoraal bezoekers
if(in_array(1, $myGroups) || in_array(8, $myGroups) || in_array(9, $myGroups) || in_array(34, $myGroups) || in_array(49, $myGroups) || in_array(50, $myGroups)) {
	$text[] = "<a id='pastoraal'></a><h2>Bezoekregistratie</h2>";
	$text[] = "Als je ouderling, diaken of predikant bent, of deel uitmaakt van het wijkteam, kan je gebruik maken van de bezoekregistratie om bij te houden welke bezoeken je hebt afgelegd bij gemeenteleden.<br>";
	$text[] = "Onder het kopje <b>Bezoekregistratie</b> zie je de link 'Registratie bezoeken' met daarachter de wijk waar jij rechten voor hebt. Door hier op te klikken kom je in de bezoekregistratie.<br>";
	$text[] = "Vervolgens zie je een overzicht van alle kerkelijke adressen in jouw wijk met daarachter wanneer het laatste bezoek is geweest. Door op het kerkelijke adres te klikken kom je uit op het profiel van het gemeentelid, door op de datum te klikken kom je bij een overzicht met details van alle bezoeken. Door op het plus-teken te klikken kan je een nieuw bezoek toevoegen.<br>";
	$text[] = "<br>";
	$text[] = "Dit staat overigens ook beschreven in de handleiding die te vinden is door onder het kopje <b>Bezoekregistratie</b> te klikken op 'Handleiding'.<br>";
	#$text[] = "<br>";
	$blocks[] = implode(NL, $text);
	$text = array();	
}

# 1 = Admin
# 11 = Beamteam
# 20 = Preekvoorziening
# 22 = Diaconie
# 28 = Cluster Eredienst
# 52 = Scipio-beheer
if(in_array(11, $myGroups) || in_array(20, $myGroups) || in_array(22, $myGroups) || in_array(28, $myGroups) || in_array(52, $myGroups)) {
	$text[] = "<a id='diensten'></a><h2>Kerkdiensten</h2>";
	$text[] = "Het 'hart' van de site zijn de kerkdiensten (naast de leden natuurlijk). Onder het kopje <b>Kerkdiensten</b> kan je, afhankelijk van je rechten, een aantal zaken aanpassen.<br>";

	if(in_array(28, $myGroups) || in_array(52, $myGroups)) {
		$text[] = "<a id='dienst_edit'></a><h3>Kerkdiensten wijzigen</h3>";
		$text[] = "Met de optie 'Kerkdiensten wijzigen' kan je een kerkdienst aanpassen mocht deze bijvoorbeeld op een ander tijdstip dan normaal plaatsvinden. Daarvoor pas de datum en/of tijden aan en klik je onderaan op 'Diensten opslaan'.<br>";
		$text[] = "Naast het aanpassen van de tijden, is het met deze optie ook mogelijk om een dienst te laten vervallen. Daarvoor klik je achteraan op het rode prullebakje. Mocht later blijken dat de dienst ten onrechte is verwijderd, de webmaster kan de dienst weer herstellen (maak geen nieuwe dienst aan, alle rooster-makers moeten deze dienst dan weer vullen).<br>";
		$text[] = "Het toevoegen van een extra dienst (denk aan huwelijk of begrafenis) is ook mogelijk door onderaan op 'Extra dienst toevoegen' te klikken. Er wordt dan een dienst toegevoegd van 09:00 tot 09:30 de volgende dag. Deze dienst kan je vervolgens zoals hier boven beschreven staat aanpassen.<br>";
		$text[] = "<br>";
	}

	if(in_array(20, $myGroups)) {
		$text[] = "<a id='dienst_voorganger'></a><h3>Voorganger</h3>";	
		$text[] = "Onder het kopje <b>Kerkdiensten wijzigen</b> staat de optie 'Gegevens van voorgangers wijzigen', hiermee kan je ";
		$text[] = "Preekrooster invoeren";
		$text[] = "<br>";
	}

	if(in_array(11, $myGroups) || in_array(52, $myGroups)) {
		$text[] = "<a id='dienst_liturgie'></a><h3>Liturgie</h3>";
		$text[] = "<br>";
	}

	if(in_array(22, $myGroups) || in_array(52, $myGroups)) {
		$text[] = "<a id='dienst_collecte'></a><h3>Collecte</h3>";	
		$text[] = "<br>";
	}
	#$text[] = "<br>";
	$blocks[] = implode(NL, $text);
	$text = array();
}

$text[] = "<a id='laposta'></a><h2>LaPosta</h2>";
$text[] = "De kerk maakt gebruik van LaPosta voor het versturen van de mails (bv. Trinitas, gebedskalender, etc.). Onder het kopje <b>LaPosta</b> kan je via 'Mail archief' doorklikken naar het archief met alle eerder verstuurde LaPosta mails.<br>";
#$text[] = "<br>";
$blocks[] = implode(NL, $text);
$text = array();	

if(in_array(1, $myGroups) OR in_array(43, $myGroups) OR in_array(44, $myGroups)) {
	$text[] = "<a id='open_kerk'></a><h2>Open kerk</h2>";
	#$text[] = "<br>";
	$blocks[] = implode(NL, $text);
	$text = array();	
}


$text[] = "<a id='declaratie'></a><h2>Declaratie</h2>";
$text[] = "Het is ook mogelijk een declaratie in te dienen voor iets wat je hebt voorgeschoten. Klik daarvoor onder het kopje <b>Declaraties</b> op 'Dien declaratie in' en geef aan dat je de declartie wilt doen als gemeentelid.<br>";
$text[] = "Doorloop vervolgens de verschillende stappen in het formulier en dien de declaratie in. Deze zal eerst door de Cluco worden beoordeeld en daarna door de penningmeester worden verwerkt.<br>";
$text[] = "<br>";

if(in_array($gebruiker->id, $clusterCoordinatoren)) {
	$text[] = "<a id='declaratie_cluco'></a><h3>Declaraties beoordelen door Cluco</h3>";
	$text[] = "Als een gemeentelid een declaratie heeft ingediend, komt deze in eerste instantie ter goedkeuring bij de Cluco. Je krijgt daar een mail van, maar door onder het kopje <b>Declaraties</b> op 'Overzicht ingediende declaraties' te klikken krijg je een overzicht van alle declaraties die bij jouw liggen.<br>";
	$text[] = "Door op het bedrag van de declaratie te klikken ga je naar de eigenlijke declaratie, door op de naam van de indiener te klikken ga je naar zijn/haar profiel.<br>";
	$text[] = "Na het beoordelen van een declaratie kan je 3 dingen doen :<ul>";
	$text[] = "<li><u>Afkeuren</u>. Kies deze optie als deze declaratie niet gehonoreerd moet worden. In het volgende scherm kan je een toelichting voor het gemeentelid typen.</li>";
	$text[] = "<li><u>Terug naar gemeentelid</u>. Kies deze optie als de declaratie wel valide is, maar het gemeentelid nog zaken moet aanvullen of wijzigen (denk aan bijlages toevoegen, beschrijving aanpassen of cluster wijzigen). In het volgende scherm kan je een toelichting voor het gemeentelid typen. Zodra het gemeentelid deze wijzigingen heeft doorgevoerd komt deze weer bij jouw in de lijst te staan.</li>";
	$text[] = "<li><u>Goedkeuren</u>. Kies deze optie als de declaratie akkoord is en doorgestuurd kan worden naar de penningsmeester. In het volgende scherm kan je een toelichting voor de penningmeester geven, maar dat is niet verplicht.</li>";
	$text[] = "</ul>";
	$text[] = "<br>";
}

$penningmeesterTeam = new Team(38);
$penningJG = new Team(51);

if(in_array($gebruiker->id, $penningmeesterTeam->leden) || in_array($gebruiker->id, $penningJG->leden)) {
	$text[] = "<a id='declaratie_penningmeester'></a><h3>Declaraties beoordelen door penningsmeester</h3>";
	$text[] = "Als een gemeentelid een declaratie heeft ingediend, komt deze in eerste instantie ter goedkeuring bij de Cluco, die hem vervolgens goedkeurt en doorstuurt. Je krijgt daar een mail van, maar door onder het kopje <b>Declaraties</b> op 'Overzicht ingediende declaraties' te klikken krijg je een overzicht van alle declaraties die bij jouw liggen.<br>";
	$text[] = "Door op het bedrag van de declaratie te klikken ga je naar de eigenlijke declaratie, door op de naam van de indiener te klikken ga je naar zijn/haar profiel.<br>";
	$text[] = "Na het beoordelen van een declaratie kan je 3 dingen doen :<ul>";
	$text[] = "<li><u>Terug naar clustercoordinator</u>. Kies deze optie als de declaratie wel valide is, maar de cluco nog zaken moet aanvullen of wijzigen. In het volgende scherm kan je een toelichting voor de cluco typen. Zodra de Cluco deze wijzigingen heeft doorgevoerd komt deze weer bij jouw in de lijst te staan.</li>";
	$text[] = "<li><u>Betreft geen Declaratie</u>. Kies deze optie indien het geen declaratie vanuit de exploitatie is, maar vanuit een investering. De declaratie wordt dan niet weggeschreven in eBoekhouden, maar de bijlages etc. worden naar de financiele administratie gestuurd voor verdere afhabneling.</li>";
	$text[] = "<li><u>Verwijderen</u>. Kies deze optie als deze declaratie niet gehonoreerd moet worden. In het volgende scherm kan je een toelichting voor de cluso typen.</li>";
	$text[] = "<li><u>Invoeren in e-boekhouden.nl</u>. Kies deze optie als de declaratie akkoord is en doorgestuurd kan worden naar e-boekhouden.nl. </li>";
	$text[] = "</ul>";
	$text[] = "<br>";
}
$blocks[] = implode(NL, $text);
$text = array();	


$text[] = "<a id='gebed'></a><h2>Gebedskalender</h2>";
$text[] = "Door op 'Gebedskalender' onder het kopje <b>Gebedskalender</b> te klikken komt u bij de gebedskalender. Onderaan kunt u (indien beschikbaar) de volgende of vorige maand bekijken<br>";
$text[] = "<br>";

$gebedTeam = new Team(36);
if(in_array($gebruiker->id, $gebedTeam->leden)) {
	$text[] = "<a id='gebed_add'></a><h3>Gebedspunten toevoegen</h3>";
	$text[] = "Onder het kopje <b>Gebedskalender</b> staat ook 'Import', hiermee kan je in 1 keer meerdere gebedspunten toevoegen aan de kalender. Dit is handig als je bijvoorbeeld in een keer alle gebedspunten voor de volgende maand wilt toevoegen.<br>";
	$text[] = "Als he op 'Import' klikt, kom je uit op een pagina met een text-veld en daaronder de keuze-optie voor maand en jaar. Voer in het textveld de gebedspunten in in het formaat : dag|gebedspunt en begin met een nieuwe regel voor elk gebedspunt.<br>";
	$text[] = "Door onderaa op 'Opslaan' te klikken worden de gebedspunten toegevoegd aan de kalender.<br>";
	$text[] = "<br>";

	$text[] = "<a id='gebed_change'></a><h3>Gebedspunten wijzigen</h3>";
	$text[] = "Onder het kopje <b>Gebedskalender</b> staat ook 'Wijzig'. Door hier op te klikken kom je op een pagina waar je alle gebedspunten vanaf vandaag ziet.<br>";
	$text[] = "In dit overzicht kan je per gebedspunt de datum en tekst aanpassen. Als alle gebedspunten naar wens zijn aangepast, klik je onderaan op 'Opslaan' om de wijzigingen door te voeren.<br>";
	#$text[] = "<br>";
}
$blocks[] = implode(NL, $text);
$text = array();	


$text[] = "<a id='agenda'></a><h2>Agenda Scipio</h2>";
#$text[] = "<br>";
$blocks[] = implode(NL, $text);
$text = array();	


$text[] = "<a id='laposta'></a><h2>Overig</h2>";
$text[] = "Account<br>";
$text[] = "Profiel<br>";
$text[] = "Ledenlijst<br>";
#$text[] = "<br>";
$blocks[] = implode(NL, $text);
$text = array();	

$text[] = "<a id='faq'></a><h2>Veel gestelde vragen</h2>";
$text[] = "Koppeling met Scipio";
#$text[] = "<br>";
$blocks[] = implode(NL, $text);
$text = array();	

echo showCSSHeader();

echo '<div class="content_vert_kolom_full">'.NL;
echo "<h1>Handleiding Intranet</h1>";
echo "<div class='content_block'>". implode(NL, $inleiding) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo '<div class="content_vert_kolom">'.NL;
foreach($blocks as $block) {
	echo "<div class='content_block'>". $block ."</div>".NL;
}

echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>