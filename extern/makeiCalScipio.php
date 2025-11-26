<?php
/**
 * Script om data in de Scipio-agenda te krijgen.
 * 
 * Dit script maakt een iCal-bestand aan met alle kerkdiensten, agenda-items en open-kerk-roosters.
 * Aan de Scipio-kant staat vervolgens ingesteld dat dit iCal-bestand (scipio.ics) regulier moet worden ingelezen.
 * Omdat het een 2-traps proces is, is er geen directe koppeling tussen Scipio en het intranet, en zit er dus enige 
 * vertraging tussen wijzigingen op het intranet en dat het zichtbaar is in Scipio.
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../Classes/Voorganger.php');
include_once('../Classes/Vulling.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Rooster.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');
include_once('../Classes/OpenKerkRooster.php');

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

	if(date("H", $dienst->dienst) < 12) {
		$ics[] = "SUMMARY:Ochtenddienst ". $voorganger->getName(3) . $postfix;
	} elseif(date("H", $dienst->dienst) < 18) {
		$ics[] = "SUMMARY:Middagdienst ". $voorganger->getName(3) . $postfix;
	} else {
		$ics[] = "SUMMARY:Avonddienst ". $voorganger->getName(3) . $postfix;
	}

	# Initialiseer
	$DESCRIPTION = $CollecteString = $RoosterString = '';

	# Collectes
	if($dienst->collecte_1 != '')	$CollecteString .= '1. '. $dienst->collecte_1;
	if($dienst->collecte_2 != '')	$CollecteString .= '\n2. '. $dienst->collecte_2;

	# Vraag andere diensten die dag op
	$diensten = Kerkdienst::getDiensten(mktime(0,0,0,date("n", $dienst->start),date("j", $dienst->start),date("Y", $dienst->start)), mktime(23,59,59,date("n", $dienst->start),date("j", $dienst->start),date("Y", $dienst->start)));

	# Vraag alle mogelijke roosters op
	$roosters = Rooster::getAllRoosters();

	foreach($roosters as $roosterID) {
		$rooster = new Rooster($roosterID);
		$personen = array();

		# Gelijk = 1 betekent  rooster voor de hele dag gelijk.
		# Daarom alle diensten doorlopen
		if($rooster->gelijk == 1) {
			foreach($diensten as $tmp) {
				$tempVulling = new Vulling($tmp, $roosterID);

				# Bij een tekst-rooster is de vulling een string, anders een array
				# Daarom op beide checken
				if(($tempVulling->tekst_only && $tempVulling->tekst != '') || (!$tempVulling->tekst_only && count($tempVulling->leden) > 0)) {
					$vulling = $tempVulling;
				}
			}
		} else {
			$vulling = new Vulling($dienst->dienst, $roosterID);
		}

		# Tekst-rooster
		if(isset($vulling) && $vulling->tekst_only && $vulling->tekst != '') {
			$RoosterString .= $rooster->naam .'\n'. $vulling->tekst .'\n\n';
		} elseif(isset($vulling) && !$vulling->tekst_only && count($vulling->leden) > 0) {
			foreach($vulling->leden as $lid) {
				$person = new Member($lid);
				$personen[] = $person->getName();
			}

			$RoosterString .= $rooster->naam .'\n- '. implode('\n- ', $personen) .'\n\n';
		}
	}

	if($CollecteString != '') {
		$DESCRIPTION = 'COLLECTE\n'. $CollecteString.'\n\n';
	}

	if($dienst->liturgie != '') {
		$DESCRIPTION .= 'LITURGIE\n'. str_replace("\r\n", '\n', $dienst->liturgie).'\n\n';
	}

	if($RoosterString != '') {
		$DESCRIPTION .= 'ROOSTERS\n'. $RoosterString;
	}

	$ics[] = 'DESCRIPTION:'.$DESCRIPTION;
	$ics[] = "STATUS:CONFIRMED";	
	$ics[] = "TRANSP:TRANSPARENT";
	$ics[] = "END:VEVENT";
		
	$vEvent[] = implode("\r\n", $ics);
}



#################
# Agenda		  	#
#################
$agendaItems = Agenda::getAgendaItems($startTijd, $eindTijd);

foreach($agendaItems as $agendaID) {
	$agenda = new Agenda($agendaID);

	# Eigenlijke ICS-data
	$ics = array();
	$ics[] = "BEGIN:VEVENT";	
	$ics[] = "UID:3GK-agenda-". substr('000'.$agenda->id, -4);
	$ics[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $agenda->start);
	$ics[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $agenda->eind);	
	$ics[] = "LAST-MODIFIED:". date("Ymd\THis", time());
	$ics[] = "SUMMARY:". $agenda->titel;
	$ics[] = 'DESCRIPTION:'.str_replace("\r\n", '\n', $agenda->beschrijving);
	$ics[] = "STATUS:CONFIRMED";	
	$ics[] = "TRANSP:TRANSPARENT";
	$ics[] = "END:VEVENT";
	$vEvent[] = implode("\r\n", $ics);
}



#################
# Open Kerk			#
#################
$starts = OpenKerkRooster::getStarts($startTijd, $eindTijd);
$grens = 0;
foreach($starts as $OKID) {
	$openkerk = new OpenKerkRooster($OKID);

	$maand	= date("m", $openkerk->start);
	$dag	= date("d", $openkerk->start);
	$jaar	= date("Y", $openkerk->start);

	$s = mktime(0, 0, 0, $maand, $dag, $jaar);
	$e = mktime(23, 59, 59, $maand, $dag, $jaar);

	if($s > $grens) {
		$grens = $e;
		$vandaag = OpenKerkRooster::getStarts($s, $e);
		$eerste = new OpenKerkRooster($vandaag[0]);
		$laatste = new OpenKerkRooster(end($vandaag));

		# Eigenlijke ICS-data
		$ics = array();
		$ics[] = "BEGIN:VEVENT";	
		$ics[] = "UID:OPENKERK-$dag-$maand-$jaar";
		$ics[] = "DTSTART;TZID=Europe/Amsterdam:". date("Ymd\THis", $eerste->start);
		$ics[] = "DTEND;TZID=Europe/Amsterdam:". date("Ymd\THis", $laatste->eind);	
		$ics[] = "LAST-MODIFIED:". date("Ymd\THis", time());
		$ics[] = "SUMMARY:Open Kerk";
		$ics[] = "STATUS:CONFIRMED";	
		$ics[] = "TRANSP:TRANSPARENT";
		$ics[] = "END:VEVENT";
		$vEvent[] = implode("\r\n", $ics);
	}
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

toLog('Agenda export voor Scipio aangemaakt', 'debug');
?>