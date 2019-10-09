<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$header[] = "BEGIN:VCALENDAR";
$header[] = "VERSION:2.0";
$header[] = "X-WR-CALNAME:[[NAAM]]";
$header[] = "X-WR-CALDESC:Kalender met daarin de verjaardagen van de 3GK";
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

for($maand=1 ; $maand<13 ; $maand++) {
	$maxDagen = date('t', mktime(1,1,1,$maand,1));
	
	for($dag=1 ; $dag<=$maxDagen ; $dag++) {
		$jarigen = getJarigen($dag, $maand);
		
		$start = mktime(0,0,0,$maand,$dag);
		$einde = $start + (24*60*60) - 5;		
		
		if(count($jarigen) > 0) {
			$namen = array();
			foreach($jarigen as $jarige) {
				//$data = getMemberDetails($jarige);
				$namen[] = makeName($jarige, 5);
			}
			
			if(count($jarigen) == 1) {
				$titel = makeOpsomming($namen) .' is jarig';
			} else {
				$titel = makeOpsomming($namen) .' zijn jarig';
			}			
			
			$ics[] = "BEGIN:VEVENT";
			$ics[] = "UID:3GK-verjaardagen-". substr('0'.$dag, -2) .'.'. substr('0'.$maand, -2) .'.'. substr('00'.date('Y'), -4);
			$ics[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $start);
			$ics[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $einde);	
			$ics[] = "LAST-MODIFIED:". date("Ymd\THis", time());
			$ics[] = "SUMMARY:". $titel;
			$ics[] = "STATUS:CONFIRMED";	
			$ics[] = "TRANSP:TRANSPARENT";
			$ics[] = "END:VEVENT";
		}		
	}
}

$file = fopen('../ical/verjaardagskalender.ics', 'w+');
fwrite($file, implode("\r\n", $header));
fwrite($file, "\r\n");
fwrite($file, implode("\r\n", $ics));
fwrite($file, "\r\n");
fwrite($file, implode("\r\n", $footer));
fclose($file);	

/*
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
	
	$file = fopen('ical/'.$memberData['username'].'-'. $memberData['hash'] .'.ics', 'w+');
	fwrite($file, implode("\r\n", str_replace('[[NAAM]]', '3GK ('. makeName($id, 1) .')', $header)));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $ics));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $footer));
	fclose ($file);	
}
*/

?>