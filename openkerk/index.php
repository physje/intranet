<?php
include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/OpenKerkRooster.php');
include_once('../Classes/Logging.php');
include_once('../include/pdf/config.php');
include_once('../include/pdf/3gk_table.php');

$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 43);
include($cfgProgDir. "secure.php");

# Opmaak voor HTML
$text[] = "<table>";

# Opmaak voor PDF
$header[] = 'Datum';
$header[] = 'Personen';
$header[] = 'Opmerking';

$starttijden = OpenKerkRooster::getAllStarts();

foreach($starttijden as $start) {
	$rooster = new OpenKerkRooster($start);

	# Opmaak voor HTML
	$text[] = "<tr>";
	$text[] = "		<td valign='top'>".time2str("E d LLL HH:mm", $rooster->start) .'-'. time2str("HH:mm", $rooster->eind) ."</td>";
	$text[] = "		<td valign='top'>";

	# Opmaak voor PDF
	$rij = array();
	$rij[] = time2str("E d LLL HH:mm", $rooster->start) .'-'. time2str("HH:mm", $rooster->eind);
	$people = array();

	foreach($rooster->personen as $key => $value) {		
		if(is_numeric($value) && $value > 0) {
			$persoon = new Member($value);
			$text[] = "<a href='../profiel.php?id=$value'>". $persoon->getName() ."</a><br>";
			$people[] = $persoon->getName();
		} elseif($value != '' && $value > 0) {
			$text[] = $extern[$value]['naam']."<br>";
			$people[] = $extern[$value]['naam'];
		}
	}
		
	$rij[] = implode("\n\r", $people);
		
	$text[] = "</td>";
	
	if($rooster->opmerking != '') {
		$text[] = "<td valign='top'><i>". $rooster->opmerking ."</i></td>";
		$rij[] = $rooster->opmerking;
	} else {
		$text[] = "<td>&nbsp;</td>";
		$rij[] = '';
	}
		
	$text[] = "</tr>";
	$data[] = $rij;
#} else {
#	$text[] = "<tr>";
#	$text[] = "	<td>Geen rooster bekend</td>";
#	$text[] = "</tr>";
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

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>". implode(NL, $text) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>
