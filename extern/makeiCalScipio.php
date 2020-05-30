<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$header[] = "BEGIN:VCALENDAR";
$header[] = "VERSION:2.0";
//$header[] = "X-WR-CALDESC:3GK-gebedspunten.";
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

$sql_dienst = "SELECT $DienstID FROM $TableDiensten WHERE $DienstEind > ". (time()-(31*24*60*60));
$result_dienst = mysqli_query($db, $sql_dienst);
if($row_dienst = mysqli_fetch_array($result_dienst)) {		
	do {
		# Wat is de ID van de dienst
		# Welke gegevens horen daar bij
		# Welke diensten zijn er nog meer die dag
		$dienst = $row_dienst[$DienstID];		
		$data_dienst = getKerkdienstDetails($dienst);		
		$diensten = getKerkdiensten(mktime(0,0,0,date("n", $data_dienst['start']),date("j", $data_dienst['start']),date("Y", $data_dienst['start'])), mktime(23,59,59,date("n", $data_dienst['start']),date("j", $data_dienst['start']),date("Y", $data_dienst['start'])));
		
		# Eigenlijke ICS-data
		$ics = array();
		$ics[] = "BEGIN:VEVENT";	
		$ics[] = "UID:3GK-dienst-". substr('00'.$dienst, -3);
		$ics[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $data_dienst['start']);
		$ics[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $data_dienst['eind']);	
		$ics[] = "LAST-MODIFIED:". date("Ymd\THis", time());
		
		if($data_dienst['bijzonderheden'] != "") { $postfix = ' - '.$data_dienst['bijzonderheden']; } else { $postfix = ''; }
				
		$ics[] = "SUMMARY:Kerkdienst";
				
		# Initialiseer
		$DESCRIPTION = $CollecteString = '';
		
		# Collectes
		if($data_dienst['collecte_1'] != '')	{ $CollecteString .= '1. '. $data_dienst['collecte_1']; }
		if($data_dienst['collecte_2'] != '')	{ $CollecteString .= '\n2. '. $data_dienst['collecte_2']; }
		
		
		$DESCRIPTION = 'Voorganger: '. makeVoorgangerName($data_dienst['voorganger_id'], 4) .'\n';
		$DESCRIPTION .= 'Muziek: Erik Janssen\n';
		$DESCRIPTION .= 'Collecte: '. $data_dienst['collecte_1'] .'\n';
		$DESCRIPTION .= 'Voorbede: Gerard Zijlstra\n';
		$DESCRIPTION .= 'Techniek: Ronald Oomen\n';
		$DESCRIPTION .= 'Montage: Barend Schimmel\n';
		$DESCRIPTION .= 'Cluster/contact: Aleida Heres';
		
		if($data_dienst['liturgie'] != '') {
			$DESCRIPTION .= 'LITURGIE\n'. str_replace("\r\n", '\n', $data_dienst['liturgie']);
		}
				
		$ics[] = 'DESCRIPTION:'.$DESCRIPTION;
		$ics[] = "STATUS:CONFIRMED";	
		$ics[] = "TRANSP:TRANSPARENT";
		$ics[] = "END:VEVENT";
		
		$vEvent[] = implode("\r\n", $ics);		
	} while($row_dienst = mysqli_fetch_array($result_dienst));	
}


$sql_agenda = "SELECT * FROM $TableAgenda WHERE $AgendaEind > ". (time()-(31*24*60*60));
$result_agenda = mysqli_query($db, $sql_agenda);
if($row_agenda = mysqli_fetch_array($result_agenda)) {
	do {
		# Eigenlijke ICS-data
		$ics = array();
		$ics[] = "BEGIN:VEVENT";	
		$ics[] = "UID:3GK-agenda-". substr('00'.$row_agenda[$AgendaID], -3);
		$ics[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $row_agenda[$AgendaStart]);
		$ics[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $row_agenda[$AgendaEind]);	
		$ics[] = "LAST-MODIFIED:". date("Ymd\THis", time());
		$ics[] = "SUMMARY:". urldecode($row_agenda[$AgendaTitel]);
		$ics[] = 'DESCRIPTION:'.urldecode($row_agenda[$AgendaDescr]);
		$ics[] = "STATUS:CONFIRMED";	
		$ics[] = "TRANSP:TRANSPARENT";
		$ics[] = "END:VEVENT";
		$vEvent[] = implode("\r\n", $ics);
	}while($row_agenda = mysqli_fetch_array($result_agenda));
}

$file_name = '../ical/scipio.ics';
	
$file = fopen($file_name, 'w+');
fwrite($file, implode("\r\n", $header));
fwrite($file, "\r\n");
fwrite($file, implode("\r\n", $vEvent));
fwrite($file, "\r\n");
fwrite($file, implode("\r\n", $footer));
fclose($file);

echo $ScriptURL.$file_name .'<br>';

//echo implode("\r\n", $ics);

?>