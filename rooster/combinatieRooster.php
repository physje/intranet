<?php
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Voorganger.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Rooster.php');
include_once('../Classes/Vulling.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/pdf/config.php');
include_once('../include/pdf/3gk_table.php');
include_once('../include/HTML_TopBottom.php');
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$roosters = $text = $header = $data = array();

if(isset($_REQUEST['rs'])) {
	$roosters = explode('|', $_REQUEST['rs']);
} elseif(isset($_REQUEST['r'])) {
	$roosters = $_REQUEST['r'];
}

if(count($roosters) > 0) {
	$diensten = Kerkdienst::getDiensten(0,0);
		
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
			$r = new Rooster($rooster);			
			$text[] = "<th>". $r->naam ."</th>";
			$header[] = $r->naam;
		}
	}
	
	$text[] = "</tr>";
	
	foreach($diensten as $dienst) {
		$d = new Kerkdienst($dienst);		
		$gevuldeCel = false;
		$cel = $rij = array();
				
		foreach($roosters as $rooster) {			
			# Voorganger
			if($rooster == 'v') {
				if($d->voorganger != '' AND $d->voorganger != 0) {
					$v = new Voorganger($d->voorganger);
					$cel[] = "<td>". $v->getName() ."</td>";
					$rij[] = $v->getName();
					$gevuldeCel = true;
				} else {
					$cel[] = "<td>&nbsp;</td>";
					$rij[] = '';
				}
				
			# Collecte
			} elseif($rooster == 'c') {
				if($d->collecte_1 != '') {
					$cel[] = "<td>". $d->collecte_1 .'<br>'. $d->collecte_2 ."</td>";
					$rij[] = $d->collecte_1 ."\n". $d->collecte_2;
					$gevuldeCel = true;
				} else {
					$cel[] = "<td>&nbsp;</td>";
					$rij[] = '';
				}
				
			# De rest
			} else {			
				$vulling = new Vulling($dienst, $rooster);
				
				# Als er leden gevonden is voor het rooster
				if(is_array($vulling->leden) AND count($vulling->leden) > 0) {
					$team = array();	
					foreach($vulling->leden as $lid) {
						$p = new Member($lid);
						$team[] = $p->getName();
					}
					$cel[] = "<td>". implode("<br>", $team) ."</td>";
					$rij[] = implode("\n", $team);
					$gevuldeCel = true;
					
				# Als er tekst gevonden is voor het rooster
				} elseif(isset($vulling->tekst) AND strlen($vulling->tekst) > 1) {				
					$cel[] = "<td>". $vulling->tekst ."</td>";
					$rij[] = $vulling->tekst;
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
			$text[] = "<td>".time2str("E dd LLL HH:mm", $d->start)."<br><i>". $d->opmerking ."</i></td>";
			$text[] = implode("\n", $cel);
			$text[] = "</tr>";
			$rij = array_merge(array(time2str("E dd LLL HH:mm", $d->start)."\n".$d->opmerking), $rij);
			$data[] = $rij;
		}		
	}
	
	$text[] = "</table>";
	$text[] = "<p>";
	$text[] = "<a href='?rs=". implode('|', $roosters)."&pdf' class='button'>sla op als PDF</a>";
	
	toLog('Combi-rooster '. implode('|', $roosters) .' bekeken', 'debug');
} else {
	$roosters = Rooster::getAllRoosters();
	$first[] = "<form>";
	$first[] = "<table id='combirooster_table'>";
	foreach($roosters as $rooster) {
		$r = new Rooster($rooster);		
		$first[] = "<tr><td><input type='checkbox' name='r[]' value='$rooster'></td><td>". $r->naam ."</td></tr>";
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
		$r = new Rooster($rooster);	
		$title = 'Rooster '. $r->naam;
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
  	toLog('Combi-rooster '. implode('|', $roosters) .' in PDF bekeken', 'debug');
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
