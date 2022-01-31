<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

#$memberData = getMemberDetails($_SESSION['ID']);	

$text[] = "<h1>Handleiding Intranet</h1>";
$text[] = "Het doel van het intranet is om te zorgen dat iedereen altijd bij de meest up-to-date informatie kan. Inmiddels maken zoveel gemeenteleden gebruik van het intranet, en staat er zoveel informatie op, dat sommigen door de bomen het bos niet meer zien.<br>";
$text[] = "Vandaar deze handleiding.<br>";
$text[] = "<br>";
$text[] = "<h3>Roosters</h3>";
$text[] = "Onder dit kopje staan alle roosters die bekend zijn binnen de Koningskerk. Door op de naam van het rooster te klikken zie je het rooster voor de komende tijd.<br>";
$text[] = "In een rooster kan je op 2 manieren doorklikken. Om te beginnen kan je klikken op de naam van diegene die op het rooster staat, op deze manier kom je uit het op het profiel van deze persoon.<br>";
$text[] = "Daarnaast kan je in een rooster op de datum klikken, op die manier zie je wie er op die datum allemaal voor de verschillende onderdelen op het rooster staan.<br>";
$text[] = "<br>";
$text[] = "Op de overzichtspagina van een rooster zie je aan de rechterkant ook staan 'PDF-versie', door daar op te klikken wordt een PDF gemaakt van het rooster, handig voor als je deze in de keuken op de koelkast ofzo wil hangen.<br>";
$text[] = "<br>";
$text[] = "Het is ook mogelijk om meerdere roosters in 1x te bekijken, klik daarvoor onder het kopje 'Roosters' op de link '<a href='../showCombineRooster.php'>Toon combinatie-rooster</a>'. In het volgende scherm kan dan geselecteerd worden welke roosters allemaal getoond moeten worden, bijvoorbeeld de taken die de verschillende familie-leden hebben. Door vervolgens op de knop 'Toon gezamenlijk' te klikken verschijnen alle roosters naast elkaar.<br>";
$text[] = "Ook hier is het mogelijk om onderaan op 'sla op als PDF' te klikken om &eacute;&eacute;n PDF te krijgen met alle roosters voor op de koelkast.<br>";
$text[] = "<br>";
$text[] = "<h3>Pagina's van teams</h3>";
$text[] = "Hier komt wat te staan<br>";

$myGroepBeheer = getMyGroupsBeheer($_SESSION['ID']);
if(count($myGroepBeheer) > 0) {
	$text[] = "<h3>Teams die ik beheer</h3>";
	$text[] = "Als je beheerder van een rooster bent, heb je het kopje 'Teams die ik beheer' op de pagina staan.<br>";
}

$myRoosterBeheer = getMyRoostersBeheer($_SESSION['ID']);
if(count($myRoosterBeheer) > 0) {
	$text[] = "<h3>Roosters die ik kan wijzigen</h3>";
	$text[] = "Hier komt wat te staan<br>";
}

if(in_array(1, getMyGroups($_SESSION['ID'])) OR in_array(43, getMyGroups($_SESSION['ID'])) OR in_array(44, getMyGroups($_SESSION['ID']))) {
	$text[] = "<h3>Open kerk</h3>";
	$text[] = "Hier komt wat te staan<br>";
}

if(in_array(1, getMyGroups($_SESSION['ID'])) OR in_array(20, getMyGroups($_SESSION['ID'])) OR in_array(22, getMyGroups($_SESSION['ID'])) OR in_array(28, getMyGroups($_SESSION['ID']))) {
	$text[] = "<h3>Diensten wijzigen</h3>";
	$text[] = "Hier komt wat te staan<br>";
}

$text[] = "<h3>Declaraties</h3>";
$text[] = "Hier komt wat te staan<br>";

$text[] = "<h3>Gebedskalender</h3>";
$text[] = "Hier komt wat te staan<br>";

$text[] = "<h3>Trinitas</h3>";
$text[] = "Hier komt wat te staan<br>";

$text[] = "<h3>Links</h3>";
$text[] = "Hier komt wat te staan<br>";

$text[] = "<h3>Ingelogd als ". makeName($_SESSION['ID'], 5) ."</h3>";
$text[] = "Hier komt wat te staan<br>";

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