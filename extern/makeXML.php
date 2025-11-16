<?php
include_once('../include/functions.php');
include_once('../include/config.php');

# Initialiseer
$header = $footer = $dienstenXML = $agendaXML = array();

# Definieer header/footer
$header[] = '<?xml version="1.0" encoding="utf-8"?>';
$footer[] = '';

# Definieer tijden
$startTijd = time();
$eindTijd = time()+(14*24*60*60);

# Definieer formatting
$fmt_dag = datefmt_create('nl_NL', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Europe/Amsterdam', IntlDateFormatter::GREGORIAN, 'EEEE');
$fmt_datum = datefmt_create('nl_NL', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Europe/Amsterdam', IntlDateFormatter::GREGORIAN, 'd MMMM');
$fmt_datum_lang = datefmt_create('nl_NL', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Europe/Amsterdam', IntlDateFormatter::GREGORIAN, 'EEEE d MMMM');
$fmt_datum_kort = datefmt_create('nl_NL', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Europe/Amsterdam', IntlDateFormatter::GREGORIAN, 'E d MMMM');
$fmt_tijd = datefmt_create('nl_NL', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Europe/Amsterdam', IntlDateFormatter::GREGORIAN, 'HH:mm');

# Vraag diensten op
$diensten = Kerkdienst::getDiensten($startTijd, $eindTijd);

foreach($diensten as $dienstID) {
	# Wat is de ID van de dienst
	# Welke gegevens horen daar bij
	# Welke diensten zijn er nog meer die dag
	$dienst = new Kerkdienst($dienstID);
	$voorganger = new Voorganger($dienst->voorganger);
	
	# Eigenlijke XML-data
	$xml = array();	
				
	if(date("H", $dienst->start) < 12) {
		$xml[] = "    <dienst>Ochtenddienst</dienst>";
	} elseif(date("H", $dienst->start) < 18) {
		$xml[] = "    <dienst>Middagdienst</dienst>";
	} else {
		$xml[] = "    <dienst>Avonddienst</dienst>";
	}			
	
	
	$xml[] = "    <dag>". datefmt_format($fmt_dag, $dienst->start) ."</dag>";
	$xml[] = "    <datum>". datefmt_format($fmt_datum, $dienst->start) ."</datum>";
	$xml[] = "    <datum_lang>". datefmt_format($fmt_datum_lang, $dienst->start) ."</datum_lang>";
	$xml[] = "    <datum_kort>". datefmt_format($fmt_datum_kort, $dienst->start) ."</datum_kort>";
	$xml[] = "    <start>". datefmt_format($fmt_tijd, $dienst->start) ."</start>";
	$xml[] = "    <eind>". datefmt_format($fmt_tijd, $dienst->eind) ."</eind>";
	$xml[] = "    <voorganger>".$voorganger->getName(0)."</voorganger>";
	$xml[] = "    <bijzonderheid>".$dienst->opmerking."</bijzonderheid>";
		
	$dienstenXML[] = "  <kerkdienst>";
	$dienstenXML = array_merge($dienstenXML, $xml);
	$dienstenXML[] = "  </kerkdienst>";	
}

//TODO: Agenda toevoegen aan XML

/*
$sql_agenda = "SELECT * FROM $TableAgenda WHERE $AgendaEind BETWEEN $startTijd AND $eindTijd";
$result_agenda = mysqli_query($db, $sql_agenda);
if($row_agenda = mysqli_fetch_array($result_agenda)) {
	do {
		# Eigenlijke XML-data
		$xml = array();	
				
		$xml[] = "    <dag>". datefmt_format($fmt_dag, $row_agenda[$AgendaStart]) ."</dag>";
		$xml[] = "    <datum>". datefmt_format($fmt_datum, $row_agenda[$AgendaStart]) ."</datum>";
		$xml[] = "    <datum_lang>". datefmt_format($fmt_datum_lang, $row_agenda[$AgendaStart]) ."</datum_lang>";
		$xml[] = "    <datum_kort>". datefmt_format($fmt_datum_kort, $row_agenda[$AgendaStart]) ."</datum_kort>";
		$xml[] = "    <start>". datefmt_format($fmt_tijd, $row_agenda[$AgendaStart]) ."</start>";
		$xml[] = "    <eind>". datefmt_format($fmt_tijd, $row_agenda[$AgendaEind]) ."</eind>";
		$xml[] = "    <titel>".urldecode($row_agenda[$AgendaTitel])."</titel>";
		$xml[] = "    <beschrijving>".urldecode($row_agenda[$AgendaDescr])."</beschrijving>";
		
		$agendaXML[] = "  <agenda>";
		$agendaXML = array_merge($agendaXML, $xml);
		$agendaXML[] = "  </agenda>";			
		
	}while($row_agenda = mysqli_fetch_array($result_agenda));
}
*/

$file_name = '../xml/agenda.xml';
	
$file = fopen($file_name, 'w+');
fwrite($file, implode("\r\n", $header));
fwrite($file, "\r\n");
fwrite($file, "<kerkdiensten>\r\n");
fwrite($file, implode("\r\n", $dienstenXML));
fwrite($file, "\r\n");
fwrite($file, "</kerkdiensten>\r\n");
fwrite($file, "<agendaitems>\r\n");
fwrite($file, implode("\r\n", $agendaXML));
fwrite($file, "\r\n");
fwrite($file, "</agendaitems>\r\n");
fwrite($file, implode("\r\n", $footer));
fclose($file);

echo $ScriptURL.$file_name .'<br>';

//echo implode("\r\n", $ics);

toLog('debug', '', '', 'Agenda export voor website aangemaakt');

?>