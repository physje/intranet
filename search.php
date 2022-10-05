<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$geslacht			= getParam('geslacht', array('M', 'V'));
$sDag					= getParam('sDag', 1);
$sMaand				= getParam('sMaand', 1);
$sJaar				= getParam('sJaar', 1900);
$eDag					= getParam('eDag', date("d"));
$eMaand				= getParam('eMaand', date("m"));
$eJaar				= getParam('eJaar', date("Y"));
$wijk					= getParam('wijk', $wijkArray);
$status				= getParam('status', array('actief'));
$burgerlijk		= getParam('burgerlijk', $burgelijkArray);
$gezin				= getParam('gezin', $gezinArray);
$kerkelijk		= getParam('kerkelijk', $kerkelijkArray);
$searchString	= getParam('searchString', '');

$links[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
$links[] = "<table border=0>";

$links[] = "<tr>";
$links[] = "	<td align='right' valign='top'>Geslacht</td>";
$links[] = "	<td>&nbsp;</td>";
$links[] = "	<td align='left' valign='top'>";
$links[] = "	<input type='checkbox' name='geslacht[]' value='M'". (in_array('M', $geslacht) ? ' checked' : '') .">Man<br>";
$links[] = "	<input type='checkbox' name='geslacht[]' value='V'". (in_array('V', $geslacht) ? ' checked' : '') .">Vrouw</option>";
$links[] = "	</select></td>";
$links[] = "</tr>";

$links[] = "<tr>";
$links[] = "	<td align='right' valign='top'>Geboren na</td>";
$links[] = "	<td>&nbsp;</td>";
$links[] = "	<td align='left' valign='top'><select name='sDag'>";
for($d=1 ; $d<32 ; $d++) {
	$links[] = "	<option value='$d'". ($d == $sDag ? ' selected' : '') .">$d</option>";
}
$links[] = "	</select> - ";
$links[] = "	<select name='sMaand'>";
for($m=1 ; $m<13 ; $m++) {
	$links[] = "	<option value='$m'". ($m == $sMaand ? ' selected' : '') .">". $maandArray[$m] ."</option>";
}
$links[] = "	</select> - ";
$links[] = "	<select name='sJaar'>";
for($j=1900 ; $j<=date("Y") ; $j++) {
	$links[] = "	<option value='$j'". ($j == $sJaar ? ' selected' : '') .">$j</option>";
}
$links[] = "	</select>";
$links[] = "	</td>";
$links[] = "</tr>";

$links[] = "<tr>";
$links[] = "	<td align='right' valign='top'>Geboren voor</td>";
$links[] = "	<td>&nbsp;</td>";
$links[] = "	<td align='left' valign='top'><select name='eDag'>";
for($d=1 ; $d<32 ; $d++) {
	$links[] = "	<option value='$d'". ($d == $eDag ? ' selected' : '') .">$d</option>";
}
$links[] = "	</select> - ";
$links[] = "	<select name='eMaand'>";
for($m=1 ; $m<13 ; $m++) {
	$links[] = "	<option value='$m'". ($m == $eMaand ? ' selected' : '') .">". $maandArray[$m] ."</option>";
}
$links[] = "	</select> - ";
$links[] = "	<select name='eJaar'>";
for($j=1900 ; $j<=date("Y") ; $j++) {
	$links[] = "	<option value='$j'". ($j == $eJaar ? ' selected' : '') .">$j</option>";
}
$links[] = "	</select></td>";
$links[] = "</tr>";

$links[] = "<tr>";
$links[] = "	<td align='right' valign='top''>Wijk</td>";
$links[] = "	<td>&nbsp;</td>";
$links[] = "	<td align='left' valign='top'>";
foreach($wijkArray as $w) {
	$links[] = "	<input type='checkbox' name='wijk[]' value='$w'". (in_array($w, $wijk) ? ' checked' : '') .">Wijk $w<br>";
}
$links[] = "	</td>";
$links[] = "</tr>";

$links[] = "<tr>";
$links[] = "	<td align='right' valign='top'>Status</td>";
$links[] = "	<td>&nbsp;</td>";
$links[] = "	<td align='left' valign='top'>";
foreach($statusArray as $s) {
	$links[] = "	<input type='checkbox' name='status[]' value='$s'". (in_array($s, $status) ? ' checked' : '') .">". ucfirst($s) ."<br>";
}
$links[] = "	</td>";
$links[] = "</tr>";

$links[] = "<tr>";
$links[] = "	<td align='right' valign='top'>Burgerlijke staat</td>";
$links[] = "	<td>&nbsp;</td>";
$links[] = "	<td align='left' valign='top'>";
foreach($burgelijkArray as $b) {
	$links[] = "	<input type='checkbox' name='burgerlijk[]' value='$b'". (in_array($b, $burgerlijk) ? ' checked' : '') .">". ucfirst($b) ."<br>";
}
$links[] = "	</td>";
$links[] = "</tr>";

$links[] = "<tr>";
$links[] = "	<td align='right' valign='top'>Kerkelijke staat</td>";
$links[] = "	<td>&nbsp;</td>";
$links[] = "	<td align='left' valign='top'>";
foreach($kerkelijkArray as $k) {
	$links[] = "	<input type='checkbox' name='kerkelijk[]' value='$k'". (in_array($k, $kerkelijk) ? ' checked' : '') .">". ucfirst($k) ."<br>";
}
$links[] = "	</td>";
$links[] = "</tr>";

$links[] = "<tr>";
$links[] = "	<td align='right' valign='top'>Gezinsrelatie</td>";
$links[] = "	<td>&nbsp;</td>";
$links[] = "	<td align='left' valign='top'>";
foreach($gezinArray as $g) {
	$links[] = "	<input type='checkbox' name='gezin[]' value='$g'". (in_array($g, $gezin) ? ' checked' : '') .">". ucfirst($g) ."<br>";
}
$links[] = "	</td>";
$links[] = "</tr>";

$links[] = "<tr>";
$links[] = "	<td align='right' valign='top'>(deel van) naam</td>";
$links[] = "	<td>&nbsp;</td>";
$links[] = "	<td align='left' valign='top'><input type='text' name='searchString' value='$searchString'></td>";
$links[] = "</tr>";

$links[] = "<tr>";
$links[] = "	<td colspan='3'>&nbsp;</td>";
$links[] = "</tr>";

$links[] = "<tr>";
$links[] = "	<td colspan='3' align='center'><input type='submit' name='search' value='Zoeken'></td>";
$links[] = "</tr>";
$links[] = "</table>";

if(isset($_POST['search'])) {
	//toLog('debug', $_SESSION['ID'], '', "Gezocht op S:$searchString G:$geslacht W:$wijk B:$sDag-$sMaand-$sJaar E:$eDag-$eMaand-$eJaar");
	
	# Geslacht
	foreach($geslacht as $g) {
		$sql_geslacht[] = "$UserGeslacht like '$g'";
	}
	$where[] = "(". implode(' OR ', $sql_geslacht).")";
	
	# Geboren
	$where[] = "$UserGeboorte BETWEEN '$sJaar-$sMaand-$sDag' AND '$eJaar-$eMaand-$eDag'";
	
	# Wijk
	foreach($wijk as $w) {
		$sql_wijk[] = "$UserWijk like '$w'";
	}
	$where[] = "(". implode(' OR ', $sql_wijk).")";
	
	# Status
	foreach($status as $s) {
		$sql_status[] = "$UserStatus like '$s'";
	}
	$where[] = "(". implode(' OR ', $sql_status).")";
	
	# Burgelijke staat
	foreach($burgerlijk as $b) {
		$sql_burgerlijk[] = "$UserBurgelijk like '$b'";
	}
	$where[] = "(". implode(' OR ', $sql_burgerlijk).")";
	
	# Kerkelijke staat
	foreach($kerkelijk as $k) {
		$sql_kerkelijk[] = "$UserBelijdenis like '$k'";
	}
	$where[] = "(". implode(' OR ', $sql_kerkelijk).")";
	
	# Gezinsrelatie
	foreach($gezin as $g) {
		$sql_gezin[] = "$UserRelatie like '$g'";
	}
	$where[] = "(". implode(' OR ', $sql_gezin).")";	
	
	# Naam		
	if($searchString != '') {
		$searchString = strtolower($searchString);
		$having1 = ", LOWER(CONCAT_WS(' ', $UserVoornaam, $UserAchternaam)) as naamKort, LOWER(CONCAT_WS(' ', $UserVoornaam, $UserTussenvoegsel, $UserAchternaam)) as naamLang, LOWER(CONCAT_WS(' ', $UserVoornaam, $UserMeisjesnaam)) as naamMeisjes";
		$having2 = " HAVING naamKort like '%$searchString%' OR naamKort like '$searchString%' OR naamKort like '%$searchString' OR naamLang like '%$searchString%' OR naamLang like '$searchString%' OR naamLang like '%$searchString' OR naamMeisjes like '%$searchString%' OR naamMeisjes like '$searchString%' OR naamMeisjes like '%$searchString'";
	} else {
		$having1 = '';
		$having2 = '';
	}
	
	$sql = "SELECT $UserID, $UserStatus$having1 FROM $TableUsers WHERE ". implode(' AND ', $where) ."$having2 ORDER BY $UserAchternaam";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		$rechts[] = '<ol>';
		do {
			$lid = $row[$UserID];
			
			if($row[$UserStatus] == 'afgemeld' OR $row[$UserStatus] == 'afgevoerd' OR $row[$UserStatus] == 'onttrokken') {
				$class = 'ontrokken';
			} elseif($row[$UserStatus] == 'overleden' OR $row[$UserStatus] == 'vertrokken') {
				$class = 'inactief';
			} else {
				$class = '';
			}
			
			$rechts[] = "<li><a href='profiel.php?id=$lid' class='$class'>". makeName($lid, 5) ."</a></li>";
			$ids[] = $lid;			
						
			$ouders = getParents($lid);			
			foreach($ouders as $ouder) {
				$parentIDs[$ouder] = $ouder;
			}			
		} while($row = mysqli_fetch_array($result));		
		$rechts[] = '</ol>';
		
		$rechts[] = "<a href='admin/exportGroupMembers.php?ids=".implode('|', $ids)."'>Exporteer deze gegevens</a>";
		$rechts[] = "<a href='admin/exportGroupMembers.php?ids=".implode('|', $parentIDs)."'>Exporteer de ouders van deze gegevens</a>";
	}
} else {
	$rechts[] = 'Nog geen resultaten';
}


echo $HTMLHeader;
echo "<table width='100%'>";
echo "<tr>";
echo "	<td width='50%' valign='top'>". implode(NL, $links) .'</td>';
echo "	<td width='50%' valign='top'>". implode(NL, $rechts) .'</td>';
echo "<tr>";
echo "<table>";
echo $HTMLFooter;
?>