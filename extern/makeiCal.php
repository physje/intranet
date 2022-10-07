<?php
include_once('../include/functions.php');
include_once('../include/config.php');

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
	
	$sql = "SELECT * FROM $TableOpenKerkRooster GROUP BY $OKRoosterPersoon";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			if(!in_array($row[$OKRoosterPersoon], $ids) AND is_numeric($row[$OKRoosterPersoon])) {
				$ids[] = $row[$OKRoosterPersoon];
			}
		} while($row = mysqli_fetch_array($result));
	}
}

foreach($ids as $id) {
	# Initialiseren
	$ics = array();
	$memberData = getMemberDetails($id);
	
	# Standaard rooster
	$sql = "SELECT * FROM $TablePlanning WHERE $PlanningUser = $id";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {						
		do {
			$diensten = array();
			$dienst_tmp = $row[$PlanningDienst];
			$rooster = $row[$PlanningGroup];			
			$data_rooster = getRoosterDetails($rooster);
			
			if($data_rooster['gelijk'] == 1) {				
				$details = getKerkdienstDetails($dienst_tmp);				
				$diensten = getKerkdiensten(mktime(0,0,0,date("n", $details['start']),date("j", $details['start']),date("Y", $details['start'])), mktime(23,59,59,date("n", $details['start']),date("j", $details['start']),date("Y", $details['start'])));
			} else {
				$diensten[] = $dienst_tmp;
			}
			
			foreach($diensten as $dienst) {
				$data_dienst = getKerkdienstDetails($dienst);
				
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
			}
		} while($row = mysqli_fetch_array($result));
	}
	
	# Open Kerk rooster
	$sql_2 = "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterPersoon = $id";
	$result_2 = mysqli_query($db, $sql_2);
	if($row_2 = mysqli_fetch_array($result_2)) {
		do {
			$start = $row_2[$OKRoosterStart];
			$einde = $row_2[$OKRoosterEind];
			
			$ics[] = "BEGIN:VEVENT";	
			$ics[] = "UID:3GK-". $row_2[$OKRoosterStart] .'.OK.'. substr('00'.$id, -3);
			$ics[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $start);
			$ics[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $einde);	
			$ics[] = "LAST-MODIFIED:". date("Ymd\THis", time());				
			$ics[] = "SUMMARY:Gast".($memberData['geslacht'] == 'M' ? 'heer' : 'vrouw') ." Open Kerk";				
			$ics[] = "STATUS:CONFIRMED";	
			$ics[] = "TRANSP:TRANSPARENT";
			$ics[] = "END:VEVENT";			
		} while($row_2 = mysqli_fetch_array($result_2));
	}
	
	$file = fopen('../ical/'.$memberData['username'].'-'. $memberData['hash_short'] .'.ics', 'w+');
	fwrite($file, implode("\r\n", str_replace('[[NAAM]]', '3GK ('. makeName($id, 1) .')', $header)));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $ics));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $footer));
	fclose ($file);
	
	toLog('debug', '', $id, 'Persoonlijke agenda geexporteerd');
}

?>
