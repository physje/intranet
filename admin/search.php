<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');

$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

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
	//toLog('debug', '', "Gezocht op S:$searchString G:$geslacht W:$wijk B:$sDag-$sMaand-$sJaar E:$eDag-$eMaand-$eJaar");
	
	# Geslacht
	foreach($geslacht as $g) {
		$sql_geslacht[] = "`geslacht` like '$g'";
	}
	$where[] = "(". implode(' OR ', $sql_geslacht).")";
	
	# Geboren
	$where[] = "`geboortedatum` BETWEEN '$sJaar-". substr('0'.$sMaand, -2) ."-". substr('0'.$sDag, -2) ."' AND '$eJaar-". substr('0'.$eMaand, -2) ."-". substr('0'.$eDag, -2) ."'";
	
	# Wijk
	foreach($wijk as $w) {
		$sql_wijk[] = "`wijk` like '$w'";
	}
	$where[] = "(". implode(' OR ', $sql_wijk).")";
	
	# Status
	foreach($status as $s) {
		$sql_status[] = "`status` like '$s'";
	}
	$where[] = "(". implode(' OR ', $sql_status).")";
	
	# Burgelijke staat
	foreach($burgerlijk as $b) {
		$sql_burgerlijk[] = "`burgstaat` like '$b'";
	}
	$where[] = "(". implode(' OR ', $sql_burgerlijk).")";
	
	# Kerkelijke staat
	foreach($kerkelijk as $k) {
		$sql_kerkelijk[] = "`belijdenis` like '$k'";
	}
	$where[] = "(". implode(' OR ', $sql_kerkelijk).")";
	
	# Gezinsrelatie
	foreach($gezin as $g) {
		$sql_gezin[] = "`relatie` like '$g'";
	}
	$where[] = "(". implode(' OR ', $sql_gezin).")";	
	
	# Naam		
	if($searchString != '') {
		$searchString = strtolower($searchString);
		$having1 = ", LOWER(CONCAT_WS(' ', `voornaam`, `achternaam`)) as naamKort, LOWER(CONCAT_WS(' ', `voornaam`, `tussenvoegsel`, `achternaam`)) as naamLang, LOWER(CONCAT_WS(' ', `voornaam`, `meisjesnaam`)) as naamMeisjes";
		$having2 = " HAVING naamKort like '%$searchString%' OR naamKort like '$searchString%' OR naamKort like '%$searchString' OR naamLang like '%$searchString%' OR naamLang like '$searchString%' OR naamLang like '%$searchString' OR naamMeisjes like '%$searchString%' OR naamMeisjes like '$searchString%' OR naamMeisjes like '%$searchString'";
	} else {
		$having1 = '';
		$having2 = '';
	}
	
	$sql = "SELECT `scipio_id`". ($having1 != '' ? ", $having1" : '') ." FROM `leden` WHERE ". implode(' AND ', $where) ."$having2 ORDER BY `achternaam`";
    $db = new Mysql();
    $data = $db->select($sql, true);

    if(count($data) > 0) {
		$rechts[] = '<ol>';
        foreach($data as $person) {
            $lid = new Member($person['scipio_id']);
						
			if(in_array($lid->status, array('afgemeld', 'afgevoerd', 'onttrokken'))) {
				$class = 'ontrokken';
            } elseif(in_array($lid->status, array('overleden', 'vertrokken'))) {
				$class = 'inactief';
			} else {
				$class = '';
			}
			
			$rechts[] = "<li><a href='../profiel.php?id=". $lid->id ."' class='$class' target='_profiel'>". $lid->getName(5) ."</a></li>";
			$ids[] = $lid->id;
						
			/*
            $ouders = $lid->getParents();
			foreach($ouders as $ouder) {
				$parentIDs[$ouder] = $ouder;
			}
            */			
		}
		$rechts[] = '</ol>';
		
		#$rechts[] = "<a href='admin/exportGroupMembers.php?ids=".implode('|', $ids)."'>Exporteer deze gegevens</a>";
		#$rechts[] = "<a href='admin/exportGroupMembers.php?ids=".implode('|', $parentIDs)."'>Exporteer de ouders van deze gegevens</a>";
	}
} else {
	$rechts[] = 'Nog geen resultaten';
}


echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;
echo "<div class='content_block'>". implode(NL, $links) ."</div>".NL;
echo "<div class='content_block'>". implode(NL, $rechts) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();
?>