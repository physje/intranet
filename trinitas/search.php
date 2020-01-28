<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$zoekScherm[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$zoekScherm[] = "<table border=0 align='center'>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td>Zoekterm</td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td><input type='text' name='searchString' value='". $_POST['searchString'] ."' size='75'></td>";
$zoekScherm[] = "	<td><input type='submit' value='Zoeken' name='submit'></td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "</table>";

if($_POST['searchString'] != '') {
	toLog('debug', $_SESSION['ID'], '', 'zoeken op '. $_POST['searchString']);
	
	$zoekString = $_POST['searchString'];
	$zoekString = str_replace(' ', '+', $zoekString);
	$zoekString = str_replace('(', '( ', $zoekString);
	$zoekString = str_replace(')', ' )', $zoekString);
	$zoekString = str_replace('+AND+', ' AND ', $zoekString);
	$zoekString = str_replace('+OR+', ' OR ', $zoekString);
	
	$delen = explode(" ", $zoekString);
	
	foreach($delen as $deel) {
		if($deel != '(' AND $deel != ')' AND $deel != 'OR' AND $deel != 'AND') {
			$search[] = "$TablePlainText.$PlainTextText like '%". str_replace('+', ' ', trim($deel)) ."%'";
		} else {
			$search[] = trim($deel);
		}
	}
			
	$sql = "SELECT $TableArchief.$ArchiefID FROM $TableArchief, $TablePlainText WHERE (". implode(' ', $search) .") AND $TablePlainText.$PlainTextID = $TableArchief.$ArchiefID ORDER BY $TableArchief.$ArchiefJaar, $TableArchief.$ArchiefNr";
	$result = mysqli_query($db, $sql);
			
	if($row = mysqli_fetch_array($result) AND $_POST['searchString'] != "") {
		do {
			$nummer = $row[$ArchiefID];
			
			$rij = "<tr>\n";		
			$rij .= "	<td><a href='download.php?fileID=$nummer'>". makeTrinitasName($nummer, 4) ."</a></td>\n";
			$rij .= "</tr>\n";
			
			$rijen[] = $rij;
		} while($row = mysqli_fetch_array($result));
		
		$aantal = count($rijen);
			
		$blok_1 = array_slice($rijen, 0, round($aantal/2));
		
		if($aantal == 1) {
			$blok_2[] = '&nbsp;';
		} else {
			$blok_2 = array_slice($rijen, round($aantal/2));
		}
	} else {
		$rij = "<tr>\n";
		$rij .= "	<td>Geen exemplaren gevonden</td>\n";
		$rij .= "</tr>\n";
		
		$blok_1[] = $rij;
		$blok_2[] = '&nbsp;';
	}	
} else {
	$rij = "<tr>\n";
	$rij .= "	<td>Hier kunt u zoeken op woorden in het archief van Trinitas.<p>Goed om te weten :<ul>";
	$rij .= "<li>Om op 2 woorden tegelijkertijd te zoeken kan AND gebruikt worden</li>\n";
	$rij .= "<li>Om op 2 woorden afzonderlijk te zoeken kan OR gebruikt worden</li>\n";
	$rij .= "<li>Het gebruik van ( en ) wordt ook ondersteund</li>\n";
	$rij .= "<li>Om op 2 woorden direct achter elkaar te zoeken moeten deze gescheiden worden door een spatie</li>\n";
	//$rij .= "<li>Het omzetten van PDF naar doorzoekbare tekst gaat niet alti</td>\n";
	$rij .= "</ul>\n";
	$rij .= "</tr>\n";	
}


echo $HTMLHeader;
echo "<tr>\n";
echo "	<td valign='top' align='center' colspan=2>". showBlock(implode("\n", $zoekScherm), 100) ."</td>\n";
echo "</tr>\n";

if($_POST['searchString'] != '') {
	echo "<tr>\n";
	echo "	<td valign='top' align='center' width='50%'>". showBlock("<table>". implode("\n", $blok_1) ."</table>", 100) ."</td>\n";
	echo "	<td valign='top' align='center' width='50%'>". showBlock("<table>". implode("\n", $blok_2) ."</table>", 100) ."</td>\n";
	echo "</tr>\n";
} else {
	echo "<tr>\n";
	echo "	<td valign='top' align='center' width='100%'>". showBlock("<table>$rij</table>", 100) ."</td>\n";
	echo "</tr>\n";
}

echo '</table>'.NL;
echo $HTMLFooter;

?>