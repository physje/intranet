<?php
/**
 * Maak persoonlijke iCal bestanden aan voor leden die nog de oude URL gebruiken
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 2.0.0
 */
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../Classes/Member.php');
include_once('../Classes/Rooster.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Vulling.php');
include_once('../Classes/OpenKerkRooster.php');
include_once('../Classes/Logging.php');

$header[] = "BEGIN:VCALENDAR";
$header[] = "VERSION:2.0";
$header[] = "X-WR-CALNAME:[[NAAM]]";
$header[] = "X-WR-CALDESC:Kalender met daarin jouw persoonlijke KKD-agenda.";
$header[] = "BEGIN:VTIMEZONE";
$header[] = "TZID:Europe/Amsterdam";
$header[] = "X-LIC-LOCATION:Europe/Amsterdam";
$header[] = "BEGIN:DAYLIGHT";
$header[] = "TZOFFSETFROM:+0100";
$header[] = "TZOFFSETTO:+0200";
$header[] = "TZNAME:CEST";
$header[] = "DTSTART:19700329T020000";
$header[] = "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU";
$header[] = "END:DAYLIGHT";
$header[] = "BEGIN:STANDARD";
$header[] = "TZOFFSETFROM:+0200";
$header[] = "TZOFFSETTO:+0100";
$header[] = "TZNAME:CET";
$header[] = "DTSTART:19701025T030000";
$header[] = "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU";
$header[] = "END:STANDARD";
$header[] = "END:VTIMEZONE";

$footer[] = "END:VCALENDAR";

$members = array(
	110001 => 'BTJong-411313B7BF14D00A',
	107901 => 'AHeld-9681188548B19B4C',
	984907 => 'EGZwerver-03CDD515F17CBBBC',
	580902 => 'JEHuisman-E71E4F4E8A17D799',
	984716 => 'SHuijser-BE52CE2F0048438F',
	984285 => 'MJDraijer-C4D41F8B61D2DD0D'
);

$startTijd = time() - 14*24*60*60;
$eindTijd = time() + 14*24*60*60;

$diensten = Kerkdienst::getDiensten($startTijd, $eindTijd);

foreach($members as $id => $filename) {
	# Initialiseren
	$ics = $ics_temp = array();
	$member		= new Member($id);

	foreach($diensten as $dienst) {
		$kerkdienst = new Kerkdienst($dienst);

		$ics_temp[] = "BEGIN:VEVENT";
		$ics_temp[] = "UID:3GK-". substr('00'.$kerkdienst->dienst, -3) .'.'. substr('00'.$rooster->id, -3) .'.'. substr('00'.$member->id, -3);
		$ics_temp[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $kerkdienst->start);
		$ics_temp[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $kerkdienst->eind);	
		$ics_temp[] = "LAST-MODIFIED:". date("Ymd\THis", time());
		$ics_temp[] = "SUMMARY:URL is aangepast, zie beschrijving";
		$ics_temp[] = "DESCRIPTION:De bestandsnaam is aangepast, de juiste URL is : ". $ScriptURL .'ical/'. $member->hash_long .'.ics. Zie '. $ScriptURL .'ical/handleiding_ical.php voor een handleiding hoe dit aan te passen';
		$ics_temp[] = "STATUS:CONFIRMED";	
		$ics_temp[] = "TRANSP:TRANSPARENT";
		$ics_temp[] = "END:VEVENT";
	}
	
	# Deze moet tijdelijk gerund worden.
	# Na verloop van tijd kan dit, en $ics_temp hierboven, weg
	# Doel is mensen te wijzen op het feit dat de URL gewijzigd is
	$file = fopen('../ical/'.$filename .'.ics', 'w+');
	fwrite($file, implode("\r\n", str_replace('[[NAAM]]', 'KKD ('. $member->getName() .')', $header)));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $ics_temp));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $footer));
	fclose ($file);
	
	toLog('Via persoonlijke agenda geattendeerd op gewijzigde URL', 'debug', $member->id);
}

?>
