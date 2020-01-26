<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();

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

$footer[] = "END:VCALENDAR";

$sql = "SELECT * FROM $TablePunten WHERE $PuntenDatum >= '". date("Y-m-d", (time()-(7*24*60*60))) ."'";
//$sql = "SELECT * FROM $TablePunten";
$result = mysqli_query($db, $sql);
if($row = mysqli_fetch_array($result)) {
	do {
		$start = strtotime($row[$PuntenDatum]);
		$einde = $start + (24*60*60) - 5;
		$gebedspunt = urldecode($row[$PuntenPunt]);
				
		$ics[] = "BEGIN:VEVENT";	
		$ics[] = "UID:gebedskalender-". $row[$PuntenDatum];
		$ics[] = "DTSTAMP:". date("Ymd\THis", time());
		$ics[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $start);
		$ics[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $einde);	
		
		if(strlen($gebedspunt) > 65) {
			$ics[] = "SUMMARY:". substr($gebedspunt, 0, 60) .'...';
			$ics[] = "DESCRIPTION:". $gebedspunt;
		} else {
			$ics[] = "SUMMARY:". $gebedspunt;
		}
		$ics[] = "STATUS:CONFIRMED";	
		$ics[] = "TRANSP:TRANSPARENT";
		$ics[] = "END:VEVENT";
	} while($row = mysqli_fetch_array($result));
}

$file = fopen('../ical/gebedskalender.ics', 'w+');
fwrite($file, implode("\r\n", $header));
fwrite($file, "\r\n");
fwrite($file, implode("\r\n", $ics));
fwrite($file, "\r\n");
fwrite($file, implode("\r\n", $footer));
fclose ($file);	

?>