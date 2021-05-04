<?php
include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/pdf/config.php');
include_once('../include/pdf/3gk_table.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 44);
include($cfgProgDir. "secure.php");

if(isset($_POST['versturen'])) {	
	$filename = generateFilename();
	
	# Genereer koptekst
	$header = array('Datum', 'Personen', 'Opmerking');
	$data = array();

	$sql		= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterTijd > ". time() ." GROUP BY $OKRoosterTijd ORDER BY $OKRoosterTijd ASC";
	$result	= mysqli_query($db, $sql);

	if($row		= mysqli_fetch_array($result)) {
		do {
			# Eerste datum opslaan voor bestandsnaam
			if(!isset($first)) $first = $row[$OKRoosterTijd];
			
			$datum = $row[$OKRoosterTijd];
			$eindTijd = $datum + (60*60);
			
			# Genereer regel	
			$rij = $people = array();
			$rij[] = time2str("%a %d %b %H:%M", $datum) .'-'. time2str("%H:%M", $eindTijd);
					
			$sql_datum		= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterTijd = ". $datum;
			$result_datum	= mysqli_query($db, $sql_datum);
			$row_datum = mysqli_fetch_array($result_datum);
		
			do {
				$key = $row_datum[$OKRoosterPersoon];
					
				if(is_numeric($key)) {
					$people[] = makeName($key, 5);
				} else {
					$people[] = $extern[$key]['naam'];
				}
			} while($row_datum = mysqli_fetch_array($result_datum));
		
			$rij[] = implode("\n\r", $people);
			
			$sql_opmerking = "SELECT * FROM $TableOpenKerkOpmerking WHERE $OKOpmerkingTijd = ". $datum;
			$result_opmerking	= mysqli_query($db, $sql_opmerking);
			if($row_opmerking = mysqli_fetch_array($result_opmerking)) {
				$rij[] = urldecode($row_opmerking[$OKOpmerkingOpmerking]);
			} else {
				$rij[] = '';
			}
			
			$data[] = $rij;
		} while($row = mysqli_fetch_array($result));
		$last = $datum;
	}
	
	$title = 'Rooster Open Kerk';
	
	$pdf = new PDF_3GK_Table();
	$breedte = $pdf->GetPageWidth();

	$pdf->AliasNbPages();
	$pdf->AddPage();
	$pdf->SetFont($cfgLttrType,'',8);
	
	$widths = array_fill(1, (count($header)-1), ($breedte-50-(2*$cfgMarge))/(count($header)-1));
	$widths[0] = 50;
	$pdf->SetWidths($widths);	
	$pdf->makeTable($header, $data);
	$pdf->Output('F', $filename.'.pdf');
	
	$ontvangers = explode('|', $_POST['ontvangers']);
	
	# Doorloop alle ontvangers om ze een persoonlijke mail te sturen met het rooster als bijlage
	foreach($ontvangers as $ontvanger) {		
		$parameter = array();
		
		if(is_numeric($ontvanger)) {
			$voornaam = makeName($ontvanger, 1);
			$parameter['to'][] = array($ontvanger);
			$memberData = getMemberDetails($ontvanger);			
			$AgendaURL = $ScriptURL ."ical/".$memberData['username'].'-'. $memberData['hash_short'] .".ics";
		} else {
			$voornaam = $extern[$ontvanger]['voornaam'];
			$parameter['to'][] = array($extern[$ontvanger]['mail'], $extern[$ontvanger]['naam']);
			$AgendaURL = '';
		}
		
		$message = $_POST['begeleidendeTekst'];
		$message = str_replace('[[voornaam]]', $voornaam, $message);
		$message = str_replace('[[url-agenda]]', $AgendaURL, $message);
		$message = nl2br($message);
						
		$parameter['subject']				= 'Nieuw rooster Open Kerk';
		$parameter['message'] 			= $message;
		$parameter['from']					= $ScriptMailAdress;
		$parameter['fromName']			= $ScriptTitle;
		$parameter['ReplyTo']				= 'maartendejonge55@gmail.com';
		$parameter['ReplyToName']		= 'Maarten de Jonge';
		$parameter['attachment'][]	= array('file' => $filename.'.pdf', 'name' => 'Rooster_Open_Kerk_'. time2str("%d_%b", $first) .'-tm-'. time2str("%d_%b", $last) .'.pdf');	
		
		if(sendMail_new($parameter)) {
			$text[] = 'Mail naar '. $voornaam .' gestuurd<br>';
		} else {
			$text[] = 'Geen mail naar '. $voornaam .' gestuurd<br>';
		}		
	}
	
	# Rommel weer even opruimen
	unlink($filename.'.pdf');
} elseif(isset($_POST['mailen'])) {
	if(isset($_POST['begeleidendeTekst'])) {
		$begeleidendeTekst = $_POST['begeleidendeTekst'];
	} else {
		$sql_last 		= "SELECT * FROM $TableOpenKerkRooster ORDER BY $OKRoosterTijd DESC LIMIT 0,1";
		$result_last	= mysqli_query($db, $sql_last);
		$row_last			= mysqli_fetch_array($result_last);
	
		$standaardTekst[] = "Beste [[voornaam]],";
		$standaardTekst[] = "";
		$standaardTekst[] = "Hierbij krijg je als bijlage bij het rooster \"Open Kerk\" voor de periode tot en met ". time2str("%A %e %B", $row_last[$OKRoosterTijd]) .".";
		$standaardTekst[] = "";
		$standaardTekst[] = "Je kan je persoonlijke open-kerk-rooster opnemen in je digitale agenda door eenmalig <a href='[[url-agenda]]'>deze link</a> toe te voegen.";
		$standaardTekst[] = "";
		$standaardTekst[] = "Een dag voor je dienst krijg je sowieso een herinnering via de mail.";
		$standaardTekst[] = "";
		$standaardTekst[] = "Ik verzoek je om incidentele ruilingen zelf in 'Rooster wijzigen' door te voeren middels <a href='". $ScriptURL ."openkerk/editRooster.php'>deze link</a> of op de website <a href='". $ScriptURL ."'>". $ScriptURL ."</a> onder het kopje 'Open Kerk'.";
		$standaardTekst[] = "Je hebt hiervoor inloggegevens nodig (alleen vrijwilligers die lid van de gemeente zijn hebben toegang tot deze app).";
		$standaardTekst[] = "Als je roosterwijzigingen doorvoert is het voor iedereen duidelijk met wie hij/zij samenwerkt en krijgt de juiste persoon een herinneringsmail.";
		$standaardTekst[] = "Als je in een volgend rooster structurele wijzigingen wilt dan verzoek ik je je wensen en mogelijkheden door te geven aan de roostermaker.";
		$standaardTekst[] = "";
		$standaardTekst[] = "Het zou overigens plezierig zijn als je structurele ruilingen met andere vrijwilligers zelf kunt regelen.";
		$standaardTekst[] = "Kant-en-klaar doorgeven van wijzigingen scheelt de roostermaker veel werk.";
		$standaardTekst[] = "";
		$standaardTekst[] = "Als je minder uren wilt gaan doen of overweegt te gaan stoppen bespreek dat alsjeblieft tijdig met de roostermaker.";
		$standaardTekst[] = "";
		$standaardTekst[] = "Hartelijke groet,";
		$standaardTekst[] = "Maarten de Jonge";
		
		$begeleidendeTekst = implode("\n", $standaardTekst);
	}	
	
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<input type='hidden' name='ontvangers' value='". implode('|', $_POST['ontvangers'])."'>";
	$text[] = "<table>";
	$text[] = "	<tr>";
	$text[] = "		<td colspan='3'>Voer de begeleidende tekst in die verstuurd moet worden gelijk met het rooster.</td>";
	$text[] = "	</tr>";
	$text[] = "	<tr>";
	$text[] = "		<td colspan='3'>&nbsp;</td>";
	$text[] = "	</tr>";	
	$text[] = "<tr>";
	$text[] = "		<td valign='top'><textarea name='begeleidendeTekst' rows=15 cols=75>$begeleidendeTekst</textarea></td>";
	$text[] = "		<td>&nbsp;</td>";
	$text[] = "		<td valign='top'>[[voornaam]] wordt vervangen door de werkelijke voornaam<br>[[url-agenda]] wordt vervangen door de link naar de persoonlijke agenda</td>";
	$text[] = "	</tr>";
	$text[] = "	<tr>";
	$text[] = "		<td colspan='3'>&nbsp;</td>";
	$text[] = "	</tr>";	
	$text[] = "	<tr>";
	$text[] = "		<td colspan='3'><input type='submit' name='versturen' value='Verstuur PDF-rooster'></td>";
	$text[] = "	</tr>";
	$text[] = "</table>";
	$text[] = "</form>";	
} else {
	$sql		= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterTijd > ". time() ." GROUP BY $OKRoosterPersoon";
	$result	= mysqli_query($db, $sql);

	if($row		= mysqli_fetch_array($result)) {
		do {
			$roosterLeden[] = $row[$OKRoosterPersoon];
		} while($row = mysqli_fetch_array($result));
	}
	
	$leden = getGroupMembers(43);
	$groepLeden = array_merge($leden, $extern);
	
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<table>";
	$text[] = "	<tr>";
	$text[] = "		<td colspan='2'>Selecteer hieronder de personen die allemaal gemaild moeten worden :</td>";
	$text[] = "	</tr>";	
	
	foreach($groepLeden as $key => $value) {
		$text[] = "<tr>";
		$text[] = "		<td><input type='checkbox' name='ontvangers[]' value='". (is_numeric($value) ? $value : $key)."'". ((in_array($value, $roosterLeden) OR in_array($key, $roosterLeden)) ? ' checked' : '') ."></td>";
		if(is_numeric($value)) {
			$text[] = "		<td>". makeName($value, 5) ."</td>";	
		} else {
			$text[] = "		<td>". $value['naam'] ."</td>";	
		}
		$text[] = "	</tr>";
	}

	$text[] = "	<tr>";
	$text[] = "		<td colspan='2'>&nbsp;</td>";
	$text[] = "	</tr>";	
	$text[] = "	<tr>";
	$text[] = "		<td colspan='2'><input type='submit' name='mailen' value='Voer begeleidende tekst in'></td>";
	$text[] = "	</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
}


echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>