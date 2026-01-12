<?php
/**
 * Maak persoonlijke iCal bestanden aan voor alle leden die nu of in de toekomst ergens op in het rooster staan.
 * 
 * Deze iCal-bestanden kan men toevoegen als externe agenda in hun Apple of Google Agenda ofzo.
 * Op die manier staat direct in hun agenda als ze ergens op het rooster staan.
 * In de vorige versie van dit script bevatte de naam van het bestand ook de inlognaam.
 * Maar dat betekende dat als je je inlognaam wijzigde, je ook je agenda moest wijzigen.
 * Daarom is vanaf versie 2 die naam verdwenen uit de bestandsnaam en gebruiken we een lang alfanumerieke reeks.
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

if(isset($_REQUEST['id'])) {
	$ids[] = $_REQUEST['id'];
} else {
	$roosterIDs = Vulling::getAllPlannedMembers();
	$OKIDs = OpenKerkRooster::getAllUsers();

	$ids = array_unique(array_merge($roosterIDs, $OKIDs));	
}

foreach($ids as $id) {
	# Initialiseren
	$ics = array();
	$member		= new Member($id);
	$diensten	= Vulling::getPlannedTimes4Member($member->id);

	# Standaard rooster
	foreach($diensten as $d => $r) {
		$diensten = array();		
		$rooster = new Rooster($r);

		if($rooster->gelijk == 1) {				
			$details = new Kerkdienst($d);
			$diensten = Kerkdienst::getDiensten(mktime(0,0,0,date("n", $details->start),date("j", $details->start),date("Y", $details->start)), mktime(23,59,59,date("n", $details->start),date("j", $details->start),date("Y", $details->start)));
		} else {
			$diensten[] = $d;
		}

		foreach($diensten as $dienst) {
			$kerkdienst = new Kerkdienst($dienst);

			$ics[] = "BEGIN:VEVENT";	
			$ics[] = "UID:KKD-". substr('00000'. $kerkdienst->dienst, -5) .'.'. substr('00'. $rooster->id, -3) .'.'. substr('0000000'. $member->id, -7);
			$ics[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $kerkdienst->start);
			$ics[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $kerkdienst->eind);	
			$ics[] = "LAST-MODIFIED:". date("Ymd\THis", time());
			$ics[] = "SUMMARY:". $rooster->naam;
			$ics[] = "STATUS:CONFIRMED";	
			$ics[] = "TRANSP:TRANSPARENT";
			$ics[] = "END:VEVENT";

			$ics_temp[] = "BEGIN:VEVENT";
			$ics_temp[] = "UID:3GK-". substr('00'.$kerkdienst->dienst, -3) .'.'. substr('00'.$rooster->id, -3) .'.'. substr('00'.$member->$id, -3);
			$ics_temp[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $kerkdienst->start);
			$ics_temp[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $kerkdienst->eind);	
			$ics_temp[] = "LAST-MODIFIED:". date("Ymd\THis", time());
			$ics_temp[] = "SUMMARY:URL is aangepast, zie beschrijving";
			$ics_temp[] = "DESCRIPTION:De bestandsnaam is aangepast, de juiste URL is : ". $ScriptURL .'ical/'. $member->hash_long .'.ics. Zie '. $ScriptURL .'ical/handleiding_ical.php voor een handleiding hoe dit aan te passen';
			$ics_temp[] = "STATUS:CONFIRMED";	
			$ics_temp[] = "TRANSP:TRANSPARENT";
			$ics_temp[] = "END:VEVENT";
		}
	}
	
	# Open Kerk rooster
	$shifts	= OpenKerkRooster::getShifts(time(), time()+(365*24*60*60), $member->id);
	
	foreach($shifts as $shift) {
		$openkerk = new OpenKerkRooster($shift);

		$ics[] = "BEGIN:VEVENT";	
		$ics[] = "UID:KKD-". $openkerk->start .'.OK.'. substr('000000'.$member->id, -3);
		$ics[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $openkerk->start);
		$ics[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $openkerk->eind);	
		$ics[] = "LAST-MODIFIED:". date("Ymd\THis", time());
		$ics[] = "SUMMARY:Gast".($member->geslacht == 'M' ? 'heer' : 'vrouw') ." Open Kerk";
		$ics[] = "STATUS:CONFIRMED";	
		$ics[] = "TRANSP:TRANSPARENT";
		$ics[] = "END:VEVENT";

		$ics_temp[] = "BEGIN:VEVENT";
		$ics_temp[] = "UID:3GK-". $openkerk->start .'.OK.'. substr('00'.$member->id, -3);
		$ics_temp[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $openkerk->start);
		$ics_temp[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $openkerk->eind);	
		$ics_temp[] = "LAST-MODIFIED:". date("Ymd\THis", time());
		$ics_temp[] = "SUMMARY:URL is aangepast, zie beschrijving";
		$ics_temp[] = "DESCRIPTION:De bestandsnaam is aangepast, de juiste URL is : ". $ScriptURL .'ical/'. $member->hash_long .'.ics. Zie '. $ScriptURL .'ical/handleiding_ical.php voor een handleiding hoe dit aan te passen';
		$ics_temp[] = "STATUS:CONFIRMED";	
		$ics_temp[] = "TRANSP:TRANSPARENT";
		$ics_temp[] = "END:VEVENT";
	}
		
	$file = fopen('../ical/'. $member->hash_long .'.ics', 'w+');	
	fwrite($file, implode("\r\n", str_replace('[[NAAM]]', 'KKD ('. $member->getName() .')', $header)));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $ics));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $footer));
	fclose ($file);

	# Deze moet tijdelijk gerund worden.
	# Na verloop van tijd kan dit, en $ics_temp hierboven, weg
	# Doel is mensen te wijzen op het feit dat de URL gewijzigd is
	$file = fopen('../ical/'.$member->username.'-'. $member->hash_short .'.ics', 'w+');
	fwrite($file, implode("\r\n", str_replace('[[NAAM]]', 'KKD ('. $member->getName() .')', $header)));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $ics_temp));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $footer));
	fclose ($file);
	
	toLog('Persoonlijke agenda geexporteerd', 'debug', $member->id);
}

?>
