<?php
include_once('include/functions.php');
include_once('include/config.php');

$db = connect_db();

$header[] = "BEGIN:VCALENDAR";
$header[] = "VERSION:2.0";
$header[] = "X-WR-CALNAME:[[NAAM]]";
$header[] = "X-WR-CALDESC:Kalender met daarin jouw persoonlijke 3GK-agenda.";
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
	$sql = "SELECT * FROM $TablePlanning GROUP BY $PlanningUser";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$ids[] = $row[$PlanningUser];
		} while($row = mysqli_fetch_array($result));
	}	
}

foreach($ids as $id) {
	$sql = "SELECT * FROM $TablePlanning WHERE $PlanningUser = $id";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		$ics = array();
		$memberData = getMemberDetails($id);
				
		do {
			$dienst = $row[$PlanningDienst];
			$rooster = $row[$PlanningGroup];
			
			$data_dienst = getKerkdienstDetails($dienst);			
			$data_rooster = getRoosterDetails($rooster);
			
			$start = $data_dienst['start'];
			$einde = $data_dienst['eind'];
						
			$ics[] = "BEGIN:VEVENT";	
			//$ics[] = "UID:3GK-". $dienst . $rooster .'-'. date("Ymd", $start);
			$ics[] = "UID:3GK-". substr('00'.$dienst, -3) .'.'. substr('00'.$rooster, -3) .'.'. substr('00'.$id, -3);
			$ics[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $start);
			$ics[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $einde);	
			$ics[] = "LAST-MODIFIED:". date("Ymd\THis", time());
			$ics[] = "SUMMARY:". $data_rooster[$RoostersNaam];
			//$ics[] = "LOCATION:". convertToReadable($data['adres']) .", ". $data['plaats'];
			//$ics[] = "DESCRIPTION:". implode('\n', $description);
			$ics[] = "STATUS:CONFIRMED";	
			$ics[] = "TRANSP:TRANSPARENT";
			$ics[] = "END:VEVENT";
		} while($row = mysqli_fetch_array($result));
	}
	
	$file = fopen('ical/'.$memberData['username'].'-'. $memberData['hash'] .'.ics', 'w+');
	fwrite($file, implode("\r\n", str_replace('[[NAAM]]', '3GK ('. makeName($id, 1) .')', $header)));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $ics));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $footer));
	fclose ($file);	
}

?>