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

if(isset($_POST['mailen'])) {	
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
	
	# Doorloop alle ontvangers om ze een persoonlijke mail te sturen met het rooster als bijlage
	foreach($_POST['ontvanger'] as $ontvanger) {
		$mail = array();
		if(is_numeric($ontvanger)) {
			$mail[] = "Beste ". makeName($ontvanger, 1) .",<br>";
			$parameter['to'][] = array($ontvanger);			
		} else {
			$mail[] = "Beste ". $extern[$ontvanger]['voornaam'] .",<br>";
			$parameter['to'][] = array($extern[$ontvanger]['mail'], $extern[$ontvanger]['naam']);
		}
		
		$mail[] = "<br>";
		$mail[] = "In de bijlage het nieuwe rooster voor de nieuwe periode.<br>";
		$mail[] = "<br>";
		$mail[] = "Met groet,<br>";
		$mail[] = "Maarten";
				
		$parameter['subject']				= 'Nieuw rooster Open Kerk';
		$parameter['message'] 			= implode("\n", $mail);
		$parameter['from']					= 'maartendejonge55@gmail.com';
		$parameter['fromName']			= 'Maarten de Jonge';
		$parameter['attachment'][]	= array('file' => $filename.'.pdf', 'name' => 'Rooster_Open_Kerk_'. time2str("%d_%b", $first) .'-tm-'. time2str("%d_%b", $last) .'.pdf');	
		
		if(sendMail_new($parameter)) {
			$text[] = 'Mail naar '. (is_numeric($ontvanger) ? makeName($ontvanger, 5) : $extern[$ontvanger]['naam']) .' gestuurd<br>';
		} else {
			$text[] = 'Geen mail naar '. (is_numeric($ontvanger) ? makeName($ontvanger, 5) : $extern[$ontvanger]['naam']) .' gestuurd<br>';
		}		
	}
	
	# Rommel weer even opruimen
	unlink($filename.'.pdf')
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
		$text[] = "		<td><input type='checkbox' name='ontvanger[]' value='". (is_numeric($value) ? $value : $key)."'". ((in_array($value, $roosterLeden) OR in_array($key, $roosterLeden)) ? ' checked' : '') ."></td>";
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
	$text[] = "		<td colspan='2'><input type='submit' name='mailen' value='Verstuur PDF-rooster'></td>";
	$text[] = "	</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
}


echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>