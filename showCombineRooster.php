<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/pdf/config.php');
include_once('include/pdf/3gk_table.php');
include_once('include/HTML_TopBottom.php');
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$roosters = array();

if(isset($_REQUEST['rs'])) {
	$roosters = explode('|', $_REQUEST['rs']);
} elseif(isset($_REQUEST['r'])) {
	$roosters = $_REQUEST['r'];
}

if(count($roosters) > 0) {
	$diensten = getAllKerkdiensten(true);
		
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "<th>&nbsp;</th>";
	
	$header[] = 'Datum';
	
	foreach($roosters as $rooster) {		
		# Voorganger
		if($rooster == 'v') {
			$text[] = "<th>Voorganger</th>";
			$header[] = 'Voorganger';
		# Collecte
		} elseif($rooster == 'c') {
			$text[] = "<th>Collecte</th>";
			$header[] = 'Collecte';
		# de rest
		} else {
			$RoosterData = getRoosterDetails($rooster);
			$text[] = "<th>". $RoosterData['naam'] ."</th>";
			$header[] = $RoosterData['naam'];
		}
	}
	
	$text[] = "</tr>";
	
	foreach($diensten as $dienst) {
		$dienstData = getKerkdienstDetails($dienst);		
		$gevuldeCel = false;
		$cel = $rij = array();
				
		foreach($roosters as $rooster) {			
			# Voorganger
			if($rooster == 'v') {
				if($dienstData['voorganger'] != '') {
					$cel[] = "<td>". $dienstData['voorganger'] ."</td>";
					$rij[] = $dienstData['voorganger'];
					$gevuldeCel = true;
				} else {
					$cel[] = "<td>&nbsp;</td>";
					$rij[] = '';
				}
				
			# Collecte
			} elseif($rooster == 'c') {
				if($dienstData['collecte_1'] != '') {
					$cel[] = "<td>". $dienstData['collecte_1'] .'<br>'. $dienstData['collecte_2'] ."</td>";
					$rij[] = $dienstData['collecte_1'] ."\n". $dienstData['collecte_2'];
					$gevuldeCel = true;
				} else {
					$cel[] = "<td>&nbsp;</td>";
					$rij[] = '';
				}
				
			# De rest
			} else {			
				$vulling = getRoosterVulling($rooster, $dienst);
				
				# Als er leden gevonden is voor het rooster
				if(is_array($vulling) AND count($vulling) > 0) {
					$team = array();	
					foreach($vulling as $lid) {
						$team[] = makeName($lid, 5);
					}
					$cel[] = "<td>". implode("<br>", $team) ."</td>";
					$rij[] = implode("\n", $team);
					$gevuldeCel = true;
					
				# Als er tekst gevonden is voor het rooster
				} elseif(is_string($vulling) AND strlen($vulling) > 1) {				
					$cel[] = "<td>". $vulling ."</td>";
					$rij[] = $vulling;
					$gevuldeCel = true;
					
				# Als er niks gevonden is voor het rooster
				} else {
					$cel[] = "<td>&nbsp;</td>";
					$rij[] = '';
				}
			}		
		}
		
		if($gevuldeCel) {
			$text[] = "<tr>";
			$text[] = "<td>".time2str("%a %d %b %H:%M", $dienstData['start'])."<br><i>".$dienstData['bijzonderheden'] ."</i></td>";
			$text[] = implode("\n", $cel);
			$text[] = "</tr>";
			$rij = array_merge(array(time2str("%a %d %b %H:%M", $dienstData['start'])."\n".$dienstData['bijzonderheden']), $rij);
			$data[] = $rij;
		}		
	}
	
	$text[] = "</table>";
	$text[] = "<p>";
	$text[] = "<a href='?rs=". implode('|', $roosters)."&pdf' class='button'>sla op als PDF</a>";
	
	toLog('debug', $_SESSION['realID'], '', 'Combi-rooster '. implode('|', $roosters) .' bekeken');
} else {
	$roosters = getRoosters(0);
	$first[] = "<form>";
	$first[] = "<table id='combirooster_table'>";
	foreach($roosters as $rooster) {
		$data = getRoosterDetails($rooster);
		$first[] = "<tr><td><input type='checkbox' name='r[]' value='$rooster'></td><td>". $data['naam']."</td></tr>";
	}
	
	$first[] = "<tr>";
	$first[] = "	<td><input type='checkbox' name='r[]' value='v'></td>";
	$first[] = "	<td>Voorganger</td>";
	$first[] = "</tr>";
	$first[] = "<tr>";
	$first[] = "	<td><input type='checkbox' name='r[]' value='c'></td>";
	$first[] = "	<td>Collecte</td>";
	$first[] = "</tr>";
	$first[] = "</table>";
	#$first[] = "<tr>";
	#$first[] = "	<td colspan='2'>&nbsp;</td>";
	#$first[] = "</tr>";
	#$first[] = "<tr>";
	#$first[] = "	<td colspan='2'><input type='submit' name='show' value='Toon gezamenlijk'></td>";
	#$first[] = "</tr>";
	$first[] = "<p>&nbsp;</p>";
	$first[] = "<input type='submit' name='show' value='Toon gezamenlijk'>";	
	$first[] = "</form>";	
}

if(isset($_REQUEST['pdf'])) {
	if(count($header) == 2) {
		$RoosterData = getRoosterDetails(end($roosters));
		$title = 'Rooster '. $RoosterData['naam'];
	} else {
		$title = 'Gecombineerd rooster';
	}
	
	if(count($header) < 6) {
		$pdf = new PDF_3GK_Table();
	} else {
		$pdf = new PDF_3GK_Table('L');
	}
	$breedte = $pdf->GetPageWidth();
	
	$pdf->AliasNbPages();
	$pdf->AddPage();
	$pdf->SetFont($cfgLttrType,'',8);
	
	$widths = array_fill(1, (count($header)-1), ($breedte-25-(2*$cfgMarge))/(count($header)-1));
	$widths[0] = 25;
	$pdf->SetWidths($widths);
	
	$pdf->makeTable($header, $data);
  $pdf->Output('I', $title.'_'.date('Y_m_d').'.pdf');
  toLog('debug', $_SESSION['realID'], '', 'Combi-rooster '. implode('|', $roosters) .' in PDF bekeken');
} else {
	echo showCSSHeader(array('default', 'table_default'));	
	if(isset($first)) {
		echo '<div class="content_vert_kolom">'.NL;
		echo "	<div class='content_block'>".NL. implode(NL, $first).NL."</div>".NL;	
		echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
	} else {
		echo '<div class="content_vert_kolom_full">'.NL;
		echo "	<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;		
		echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
	}
	
	echo showCSSFooter();
}

?>
