<?php
include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/OpenKerkRooster.php');
include_once('../Classes/Team.php');
include_once('../Classes/KKDMailer.php');
include_once('../include/pdf/config.php');
include_once('../include/pdf/3gk_table.php');

$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 44);
include($cfgProgDir. "secure.php");

if(isset($_POST['versturen'])) {	
	$filename = generateFilename();
	
	# Genereer koptekst
	$header = array('Datum', 'Personen', 'Opmerking');
	$data = array();

	$startTijden = OpenKerkRooster::getAllStarts();

	foreach($startTijden as $startTijd) {
		# Eerste datum opslaan voor bestandsnaam
		if(!isset($first)) $first = $startTijd;

		$rooster = new OpenKerkRooster($startTijd);
		$start = $rooster->start;
		$eind = $rooster->eind;
			
		# Genereer regel	
		$rij = $people = array();
		$rij[] = time2str("D j M H:i", $rooster->start) .'-'. time2str("H:i", $rooster->eind);
			
		foreach($rooster->personen as $key) {					
			if(is_numeric($key) && $key > 0) {
				$person = new Member($key);
				$people[] = $person->getName();
			} elseif($key != '' && $key > 0) {				
				$people[] = $extern[$key]['naam'];
			}
		}
			
		
		$rij[] = implode("\n\r", $people);
						
		if($rooster->opmerking != '') {
			$rij[] = $rooster->opmerking;
		} else {
			$rij[] = '';
		}
			
		$data[] = $rij;
		$last = $startTijd;
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
		$OKMail = new KKDMailer();
		
		if(is_numeric($ontvanger)) {			
			$person = new Member($ontvanger);
			$person->nameType = 1;
			$voornaam = $person->getName();
			$AgendaURL = $ScriptURL ."ical/". $person->hash_long .".ics";

			$OKMail->aan = $ontvanger;
		} else {			
			$extern[$ontvanger]['voornaam'];
			$AgendaURL = '';

			$OKMail->ontvangers = array($extern[$ontvanger]['mail'], $extern[$ontvanger]['naam']);
		}
		
		$message = $_POST['begeleidendeTekst'];
		$message = str_replace('[[voornaam]]', $voornaam, $message);
		$message = str_replace('[[url-agenda]]', $AgendaURL, $message);
		$message = nl2br($message);
						
		$OKMail->Subject	= 'Nieuw rooster Open Kerk';
		$OKMail->Body		= $message;		
		$OKMail->setFrom($ScriptMailAdress, $ScriptTitle);
		$OKMail->addReplyTo('maartendejonge55@gmail.com', 'Maarten de Jonge');
		$OKMail->addAttachment($filename.'.pdf', 'Rooster_Open_Kerk_'. time2str("d_M", $first) .'-tm-'. time2str("d_M", $last) .'.pdf');
		$OKMail->testen = true;
		//TODO: testen uitzetten
		
		if($OKMail->Sendmail()) {
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
		$last = OpenKerkRooster::getLastStart();
	
		$standaardTekst[] = "Beste [[voornaam]],";
		$standaardTekst[] = "";
		$standaardTekst[] = "Hierbij krijg je als bijlage bij het rooster \"Open Kerk\" voor de periode tot en met ". time2str("D j M", $last) .".";
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
	$roosterLeden	= OpenKerkRooster::getAllUsers();	
	$leden			= new Team(43);
	$groepLeden		= array_merge($leden->leden, $extern);
	
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<table>";
	$text[] = "	<tr>";
	$text[] = "		<td colspan='2'>Selecteer hieronder de personen die allemaal gemaild moeten worden :</td>";
	$text[] = "	</tr>";	
	
	foreach($groepLeden as $key => $value) {
		$text[] = "<tr>";
		$text[] = "		<td><input type='checkbox' name='ontvangers[]' value='". (is_numeric($value) ? $value : $key)."'". ((in_array($value, $roosterLeden) OR in_array($key, $roosterLeden)) ? ' checked' : '') ."></td>";
		if(is_numeric($value)) {
			$lid = new Member($value);
			$text[] = "		<td>". $lid->getName() ."</td>";	
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


echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>". implode(NL, $text) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();


?>