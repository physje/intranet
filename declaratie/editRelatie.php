<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/EB_functions.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['relNr'])) {
	if(isset($_REQUEST['save'])) {
		$errorResult = eb_updateRelatieByCode ($_POST['relNr'], $_POST);
		if($errorResult) {
			$page[] = 'Foutje :'. $errorResult;
		} else {
			$page[] = 'Opgeslagen';			
		}
	} else {		
		$data = eb_getRelatieDataByCode($_REQUEST['relNr']);
		
		if($data['code'] != $_REQUEST['relNr']) {
			$page[] = "Hier klopt iets niet"; 
		} else {
			$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
			$page[] = "<input type='hidden' name='relNr' value='". $_REQUEST['relNr'] ."'>";
			$page[] = "<table>";
			$page[] = "<tr>";
			$page[] = "	<td>Naam</td>";
			$page[] = "	<td><input type='text' name='naam' value='". $data['naam'] ."'><td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td>Geslacht</td>";
			$page[] = "	<td><select name='geslacht'>";
			$page[] = "		<option value=''". ($data['geslacht'] == '' ? ' selected' : '') .">Onbepaald</option>";
			$page[] = "		<option value='M'". (($data['geslacht'] == 'm' OR $data['geslacht'] == 'M') ? ' selected' : '') .">Man</option>";
			$page[] = "		<option value='V'". (($data['geslacht'] == 'v' OR $data['geslacht'] == 'V') ? ' selected' : '') .">Vrouw</option>";
			$page[] = "	</select><td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td>Adres</td>";
			$page[] = "	<td><input type='text' name='adres' value='". $data['adres'] ."'><td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td>Postcode</td>";
			$page[] = "	<td><input type='text' name='postcode' value='". $data['postcode'] ."'><td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td>Plaats</td>";
			$page[] = "	<td><input type='text' name='plaats' value='". $data['plaats'] ."'><td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td>Mail</td>";
			$page[] = "	<td><input type='text' name='mail' value='". $data['mail'] ."'><td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td>IBAN</td>";
			$page[] = "	<td><input type='text' name='iban' value='". $data['iban'] ."'><td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td>Notitie</td>";
			$page[] = "	<td><textarea name='notitie' cols='25' rows='8'>". $data['notitie'] ."</textarea><td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td>Telefoon</td>";
			$page[] = "	<td><input type='text' name='telefoon' value='". $data['telefoon'] ."'><td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td>GSM</td>";
			$page[] = "	<td><input type='text' name='gsm' value='". $data['gsm'] ."'><td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td colspan='2'>&nbsp;</td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td colspan='2'><input type='submit' name='save' value='Opslaan'></td>";
			$page[] = "</tr>"; 	
			$page[] = "</table>";
			$page[] = "</form>";
		}
	}
} else {
	$relaties = eb_getRelaties();
	
	foreach($relaties as $relatie) {
		$page[] = "<a href='?relNr=". $relatie['code'] ."'>". $relatie['naam'] ."</a><br>";
	}	
}

# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '	<td valign="top">'. showBlock(implode("\n", $page), 100). '</td>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;