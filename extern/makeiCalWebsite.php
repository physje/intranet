<?php
/**
 * Script om data te exporteren zodat het in een digitale agenda komt.
 * 
 * Dit script maakt een iCal-bestand aan met alle kerkdiensten en de voorganger.
 * Dit is dus een duidelijk kortere versie dan @see makeiCalScipio.php
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../Classes/Voorganger.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Logging.php');

$header[] = "BEGIN:VCALENDAR";
$header[] = "VERSION:2.0";
$header[] = "X-WR-CALNAME:Diensten Koningskerk Deventer";
$header[] = "X-WR-CALDESC:Kalender met daarin de diensten van de Koningskerk.";
$header[] = "PRODID:-//hacksw/handcal//NONSGML v1.0//EN";
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

#################
# Kerkdiensten	#
#################

# Maand terug tot jaar vooruit
$startTijd = time()-(31*24*60*60);
$eindTijd = time()+(365*24*60*60);

$kerkdiensten = Kerkdienst::getDiensten($startTijd, $eindTijd);

foreach($kerkdiensten as $dienstID) {
	$dienst = new Kerkdienst($dienstID);
	$voorganger = new Voorganger($dienst->voorganger);
		
	# Eigenlijke ICS-data
	$ics = array();
	$ics[] = "BEGIN:VEVENT";
	$ics[] = "UID:3GK-dienst-". substr('00000'. $dienst->dienst, -5);
	$ics[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $dienst->start);
	$ics[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $dienst->eind);
	$ics[] = "LAST-MODIFIED:". date("Ymd\THis", time());

	if($dienst->opmerking != "") { $postfix = ' - '.$dienst->opmerking; } else { $postfix = ''; }

	if(date("H", $dienst->start) < 12) {
		$ics[] = "SUMMARY:Ochtenddienst ". $voorganger->getName(3) . $postfix;
	} elseif(date("H", $dienst->start) < 18) {
		$ics[] = "SUMMARY:Middagdienst ". $voorganger->getName(3) . $postfix;
	} else {
		$ics[] = "SUMMARY:Avonddienst ". $voorganger->getName(3) . $postfix;
	}

	# Initialiseer
	$DESCRIPTION  = '';

	$ics[] = 'DESCRIPTION:'.$DESCRIPTION;
	$ics[] = "STATUS:CONFIRMED";	
	$ics[] = "TRANSP:TRANSPARENT";
	$ics[] = "END:VEVENT";
		
	$vEvent[] = implode("\r\n", $ics);
}

$file_name = '../ical/website.ics';
	
$file = fopen($file_name, 'w+');
fwrite($file, implode("\r\n", $header));
fwrite($file, "\r\n");
fwrite($file, implode("\r\n", $vEvent));
fwrite($file, "\r\n");
fwrite($file, implode("\r\n", $footer));
fclose($file);

echo $ScriptURL.$file_name .'<br>';

toLog('Agenda export voor website aangemaakt', 'debug');
?>