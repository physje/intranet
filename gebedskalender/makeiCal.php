<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Gebedspunt.php');
include_once('../Classes/Logging.php');

$header[] = "BEGIN:VCALENDAR";
$header[] = "VERSION:2.0";
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

$ics = array();

$footer[] = "END:VCALENDAR";

$begin = date("Y-m-d", (time()-(7*24*60*60)));
$einde = date("Y-m-d", (time()+(60*24*60*60)));
$punten = Gebedspunt::getPunten($begin, $einde);

if(count($punten) > 0) {
	foreach($punten as $punt) {
		$gebedspunt = new Gebedspunt($punt);
		$punt = str_replace(array("\r", "\n"), '', $gebedspunt->gebedspunt);
				
		$ics[] = "BEGIN:VEVENT";	
		$ics[] = "UID:gebedskalender-". substr('0000000'.$gebedspunt->id, -5);
		$ics[] = "DTSTAMP:". date("Ymd\THis", time());
		$ics[] = "DTSTART;TZID=Europe/Amsterdam:". $gebedspunt->jaar.substr('0'.$gebedspunt->maand, -2).substr('0'.$gebedspunt->dag, -2).'\T000001';
		$ics[] = "DTEND;TZID=Europe/Amsterdam:". $gebedspunt->jaar.substr('0'.$gebedspunt->maand, -2).substr('0'.$gebedspunt->dag, -2).'\T235959';
		
		if(strlen($punt) > 65) {
			$ics[] = "SUMMARY:". substr($punt, 0, 60) .'...';
		} else {
			$ics[] = "SUMMARY:". $punt;
		}
		
		$ics[] = "DESCRIPTION:". $punt . '\n\nWe nodigen u uit gebedspunten aan te dragen! Mail uw punt naar gebedskalender@koningskerkdeventer.nl';
		$ics[] = "STATUS:CONFIRMED";	
		$ics[] = "TRANSP:TRANSPARENT";
		$ics[] = "END:VEVENT";
	}
}

$file = fopen('../ical/gebedskalender.ics', 'w+');
fwrite($file, implode("\r\n", $header));
fwrite($file, "\r\n");
fwrite($file, implode("\r\n", $ics));
fwrite($file, "\r\n");
fwrite($file, implode("\r\n", $footer));
fclose ($file);

toLog('Gebedskalender voor Scipio aangemaakt', 'debug');

?>
