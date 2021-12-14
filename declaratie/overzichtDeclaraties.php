<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
$db = connect_db();

$cfgProgDir = '../auth/';
$requiredUserGroups = array(1);
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['key'])) {	
	$sql = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieHash like '". $_REQUEST['key'] ."'";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	$JSON = json_decode($row[$EBDeclaratieDeclaratie], true);	
	$indiener = $row[$EBDeclaratieIndiener];
	$data['key']								= $_REQUEST['key'];
	$data['user']								= $indiener;
	$data['eigen']							= $JSON['eigen'];
	$data['iban']								= $JSON['iban'];
	$data['relatie']						= getParam('begunstigde', $JSON['EB_relatie']);
	$data['cluster']						= $JSON['cluster'];
	$data['overige']						= $JSON['overig'];
	$data['overig_price']				= $JSON['overig_price'];
	$data['reiskosten']					= $JSON['reiskosten'];
	$data['bijlage']						= $JSON['bijlage'];
	$data['bijlage_naam']				= $JSON['bijlage_naam'];
	$data['opmerking_cluco']		= $JSON['opm_cluco'];
	$data['opmerking_penning']	= $JSON['opm_penning'];
		
	$page = array_merge(array('<table border=0>'), showDeclaratieDetails($data), array('</table>'));	
} else {
	$sql = "SELECT * FROM $TableEBDeclaratie";
	if(isset($_REQUEST['status'])) {	
		$sql .= " WHERE $EBDeclaratieStatus like '". $_REQUEST['status'] ."'";
	}
	$sql .= " ORDER BY $EBDeclaratieTijd DESC";
	
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	$page[] = "<table>";
	$page[] = "<tr>";
	$page[] = "<td colspan='2'><b>Tijdstip</b></td>";
	$page[] = "<td colspan='2'><b>Cluster</b></td>";				
	$page[] = "<td colspan='2'><b>Indiener</b></td>";			
	$page[] = "<td colspan='2'><b>Bedrag</b></td>";
	$page[] = "<td><b>Status</b></td>";
	$page[] = "</tr>";
	
	$statusNaam[3] = 'CluCo';
	$statusNaam[6] = 'Afgekeurd';
	$statusNaam[4] = 'Penningmeester';
	$statusNaam[5] = 'Afgerond';
	$statusNaam[7] = 'Verwijderd';
		
	$statusActive = array(3,4);
	$statusInactive = array(5);
	$statusDelete = array(6,7);
	
	do {
		$status = $row[$EBDeclaratieStatus];
		
		if(in_array($status, $statusInactive)) {
			$class = 'inactief';
		} elseif(in_array($status, $statusDelete)) {
			$class = 'ontrokken';
		} else {
			$class = '';			
		}
				
		$page[] = "<tr>";
		$page[] = "<td>". time2str('%e %b %H:%M', $row[$EBDeclaratieTijd]) ."</td>";
		$page[] = "<td>&nbsp;</td>";			
		$page[] = "<td>". $clusters[$row[$EBDeclaratieCluster]] ."</td>";
		$page[] = "<td>&nbsp;</td>";
		$page[] = "<td><a". ($class != '' ? " class='$class'" : '')." href='../profiel.php?id=". $row[$EBDeclaratieIndiener] ."'>". makeName($row[$EBDeclaratieIndiener], 5) ."</a></td>";
		$page[] = "<td>&nbsp;</td>";			
		$page[] = "<td><a". ($class != '' ? " class='$class'" : '')." href='?key=". $row[$EBDeclaratieHash] ."'>". formatPrice($row[$EBDeclaratieTotaal]) ."</a></td>";
		$page[] = "<td>&nbsp;</td>";
		$page[] = "<td><a". ($class != '' ? " class='$class'" : '')." href='?status=$status'>". $statusNaam[$status] ."</a></td>";	
		$page[] = "</tr>";
	} while($row = mysqli_fetch_array($result));
	$page[] = "</table>";
}

# Pagina tonen
$pageTitle = 'Declaratie';
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '	<td valign="top">'. showBlock(implode("\n", $page), 100). '</td>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;

?>