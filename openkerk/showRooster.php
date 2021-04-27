<?php
include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/pdf/config.php');
include_once('../include/pdf/3gk_table.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 43);
include($cfgProgDir. "secure.php");

# Opmaak voor HTML
$text[] = "<table>";

# Opmaak voor PDF
$header[] = 'Datum';
$header[] = 'Personen';
$header[] = 'Opmerking';

$sql		= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterTijd > ". time() ." GROUP BY $OKRoosterTijd ORDER BY $OKRoosterTijd ASC";
$result	= mysqli_query($db, $sql);

if($row		= mysqli_fetch_array($result)) {
	do {
		$datum = $row[$OKRoosterTijd];
		$eindTijd = $datum + (60*60);
	
		# Opmaak voor HTML
		$text[] = "<tr>";
		$text[] = "		<td valign='top'>".time2str("%a %d %b %H:%M", $datum) .'-'. time2str("%H:%M", $eindTijd) ."</td>";
		$text[] = "		<td valign='top'>";

		# Opmaak voor PDF
		$rij = array();
		$rij[] = time2str("%a %d %b %H:%M", $datum) .'-'. time2str("%H:%M", $eindTijd);
		$people = array();
		
		$sql_datum		= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterTijd = ". $datum;
		$result_datum	= mysqli_query($db, $sql_datum);
		$row_datum = mysqli_fetch_array($result_datum);
		
		do {
			$key = $row_datum[$OKRoosterPersoon];
					
			if(is_numeric($key)) {
				$text[] = "<a href='../profiel.php?id=$key'>". makeName($key, 5) ."</a><br>";
				$people[] = makeName($key, 5);
			} else {
				$text[] = $extern[$key]['naam']."<br>";
				$people[] = $extern[$key]['naam'];
			}
		} while($row_datum = mysqli_fetch_array($result_datum));
		
		$rij[] = implode("\n\r", $people);
		
		$text[] = "</td>";
	
		$sql_opmerking = "SELECT * FROM $TableOpenKerkOpmerking WHERE $OKOpmerkingTijd = ". $datum;
		$result_opmerking	= mysqli_query($db, $sql_opmerking);
		if($row_opmerking = mysqli_fetch_array($result_opmerking)) {
			$text[] = "<td valign='top'><i>". urldecode($row_opmerking[$OKOpmerkingOpmerking]) ."</i></td>";
			$rij[] = urldecode($row_opmerking[$OKOpmerkingOpmerking]);
		} else {
			$text[] = "<td>&nbsp;</td>";
			$rij[] = '';
		}
		
		$text[] = "</tr>";
		$data[] = $rij;
		
	} while($row = mysqli_fetch_array($result));
} else {
	$text[] = "<tr>";
	$text[] = "	<td>Geen rooster bekend</td>";
	$text[] = "</tr>";
}

$text[] = "</table>";
$text[] = "<p>";
$text[] = "<a href='?pdf=ja'>Sla op als PDF</a>";


if(isset($_REQUEST['pdf'])) {
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
	$pdf->Output('I', $title.'_'.date('Y_m_d').'.pdf');
}

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>