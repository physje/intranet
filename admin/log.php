<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

# Default is vanaf 36 uur geleden tot 1 voor 12 deze dag
$startTijd	= mktime(date("H"),date("i"),date("s"),date("n"),(date("j")), date("Y")) - (36*60*60);
$eindTijd	= mktime(23,59);

# Op basis van de dat
$bMin	= getParam('bMin', date("i", $startTijd));
$bUur	= getParam('bUur', date("H", $startTijd));
$bDag	= getParam('bDag', date("d", $startTijd));
$bMaand	= getParam('bMaand', date("m", $startTijd));
$bJaar	= getParam('bJaar', date("Y", $startTijd));

$eMin	= getParam('eMin', date("i", $eindTijd));
$eUur	= getParam('eUur', date("H", $eindTijd));
$eDag	= getParam('eDag', date("d", $eindTijd));
$eMaand	= getParam('eMaand', date("m", $eindTijd));
$eJaar	= getParam('eJaar', date("Y", $eindTijd));

$start	= mktime ($bUur,$bMin,0,$bMaand,$bDag,$bJaar);
$end	= mktime ($eUur,$eMin,59,$eMaand,$eDag,$eJaar);

$dader			= getParam('dader', 0);
$slachtoffer	= getParam('slacht', 0);
$type			= getParam('type', array('info', 'error'));
$message		= getParam('message', '');
$aantal			= getParam('aantal', 100);

$cfgAantalLog = array(10, 25, 50, 100, 250, 500, 1000);

# intval omdat $dader en $slachtoffer als string uit het formulier komen
$logData = Logging::getLogging($start, $end, $type, intval($dader), intval($slachtoffer), $message, $aantal);

$zoekScherm_1[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$zoekScherm_1[] = "<table>";
$zoekScherm_1[] = "<tr>";
$zoekScherm_1[] = "	<td colspan='2'><b>Van</b></td>";
$zoekScherm_1[] = "	<td><b>Dader</b></td>";
$zoekScherm_1[] = "</tr>";
$zoekScherm_1[] = "<tr>";
$zoekScherm_1[] = "	<td><select name='bDag'>";
for($d=1 ; $d<=31 ; $d++)	{	$zoekScherm_1[] = "<option value='$d'". ($d == $bDag ? ' selected' : '') .">$d</option>";	}
$zoekScherm_1[] = "	</select><select name='bMaand'>";
for($m=1 ; $m<=12 ; $m++)	{	$zoekScherm_1[] = "<option value='$m'". ($m == $bMaand ? ' selected' : '') .">". $maandArray[$m] ."</option>";	}
$zoekScherm_1[] = "	</select><select name='bJaar'>";
for($j=(date('Y') - 1) ; $j<=(date('Y') + 1) ; $j++)	{	$zoekScherm_1[] = "<option value='$j'". ($j == $bJaar ? ' selected' : '') .">$j</option>";	}
$zoekScherm_1[] = "	</select></td>";
$zoekScherm_1[] = "	<td><select name='bUur'>";
for($u=0 ; $u<24 ; $u++)	{	$zoekScherm_1[] = "<option value='$u'". ($u == $bUur ? ' selected' : '') .">". substr('0'.$u, -2) ."</option>";	}
$zoekScherm_1[] = "	</select><select name='bMin'>";
for($m=0 ; $m<60 ; $m++)	{	$zoekScherm_1[] = "<option value='$m'". ($m == $bMin ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>";	}
$zoekScherm_1[] = "	</select></td>";

$daders =  array_column($logData, 'dader');
$seen = array();

$zoekScherm_1[] = "	<td><select name='dader'>";
$zoekScherm_1[] = "	<option value=''>Alle</option>";
foreach($daders as $userID) {
	if($userID <> 0 && !in_array($userID, $seen)) {
		$person = new Member($userID);
		$zoekScherm_1[] = "	<option value='$userID'". ($dader == $userID ? ' selected' : '') .">". $person->getName() ."</option>";
		$seen[] = $userID;
	}
}
$zoekScherm_1[] = "	</select></td>";
$zoekScherm_1[] = "</tr>";
$zoekScherm_1[] = "<tr>";
$zoekScherm_1[] = "	<td colspan='2'><b>Tot</b></td>";
$zoekScherm_1[] = "	<td><b>Slachtoffer</b></td>";
$zoekScherm_1[] = "</tr>";
$zoekScherm_1[] = "<tr>";
$zoekScherm_1[] = "	<td><select name='eDag'>";
for($d=1 ; $d<=31 ; $d++)	{	$zoekScherm_1[] = "<option value='$d'". ($d == $eDag ? ' selected' : '') .">$d</option>";	}
$zoekScherm_1[] = "	</select><select name='eMaand'>";
for($m=1 ; $m<=12 ; $m++)	{	$zoekScherm_1[] = "<option value='$m'". ($m == $eMaand ? ' selected' : '') .">". $maandArray[$m] ."</option>";	}
$zoekScherm_1[] = "	</select><select name='eJaar'>";
for($j=(date('Y') - 1) ; $j<=(date('Y') + 1) ; $j++)	{	$zoekScherm_1[] = "<option value='$j'". ($j == $eJaar ? ' selected' : '') .">$j</option>";	}
$zoekScherm_1[] = "	</select></td>";
$zoekScherm_1[] = "	<td><select name='eUur'>";
for($u=0 ; $u<24 ; $u++)	{	$zoekScherm_1[] = "<option value='$u'". ($u == $eUur ? ' selected' : '') .">". substr('0'.$u, -2) ."</option>";	}
$zoekScherm_1[] = "	</select><select name='eMin'>";
for($m=0 ; $m<60 ; $m++)	{	$zoekScherm_1[] = "<option value='$m'". ($m == $eMin ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>";	}
$zoekScherm_1[] = "	</select></td>";

$seen = array();
$slachtoffers =  array_column($logData, 'slachtoffer');

$zoekScherm_1[] = "	<td><select name='slacht'>";
$zoekScherm_1[] = "	<option value=''>Alle</option>";
foreach($slachtoffers as $userID) {
	if($userID <> 0 && !in_array($userID, $seen)) {
		$person = new Member($userID);
		$zoekScherm_1[] = "	<option value='$userID'". ($slachtoffer == $userID ? ' selected' : '') .">". $person->getName() ."</option>";
		$seen[] = $userID;
	}
}
$zoekScherm_1[] = "	</select></td>";
$zoekScherm_1[] = "</tr>";
$zoekScherm_1[] = "</table>";


$zoekScherm_2[] = "<table border=0>";
$zoekScherm_2[] = "	<td><b>Zoekwoord</b></td>";
$zoekScherm_2[] = "	<td><b>Aantal</b></td>";
$zoekScherm_2[] = "	<td rowspan='3'><input type='submit' name='save' value='Zoeken'></td>";
$zoekScherm_2[] = "</tr>";
$zoekScherm_2[] = "<tr>";
$zoekScherm_2[] = "	<td><input type='text' name='message' value='$message' size=40></td>";
$zoekScherm_2[] = "	<td><select name='aantal'>";
foreach($cfgAantalLog as $a) {	$zoekScherm_2[] = "<option value='$a'". ($a == $aantal ? ' selected' : '') .">$a</option>";	}
$zoekScherm_2[] = "	</select></td>";
$zoekScherm_2[] = "</tr>";
$zoekScherm_2[] = "<tr>";
$zoekScherm_2[] = "	<td colspan='2'><input type='checkbox' name='type[]' value='debug'". (in_array('debug', $type) ? ' checked' : '').">Debug <input type='checkbox' name='type[]' value='info'". (in_array('info', $type) ? ' checked' : '').">Info <input type='checkbox' name='type[]' value='error'". (in_array('error', $type) ? ' checked' : '').">Error</td>";
$zoekScherm_2[] = "</tr>";
$zoekScherm_2[] = "</table>";
#$zoekScherm_2[] = "<p class='after_table'><input type='submit' name='save' value='Zoeken'></p>";	
$zoekScherm_2[] = "</form>";

$rijen = array();

if(count($logData) > 0) {
	foreach($logData as $data_array) {		
		if($data_array['type'] == 'error')	$pre = '<b>'; $post = '</b>';
		if($data_array['type'] == 'debug')	$pre = '<i>'; $post = '</i>';
		if($data_array['type'] == 'info')	$pre = ''; $post = '';

		$dader = new Member($data_array['dader']);
		$slachtoffer = new Member($data_array['slachtoffer']);
		if($data_array['vermomd'] > 0) {
			$vermomming = new Member($data_array['vermomd']);
		}
		
		$rij = array();
		$rij[] = "<tr>";
		$rij[] = "	<td>". date("d-m H:i:s", $data_array['tijd']) ."</td>";
		$rij[] = "	<td>&nbsp;</td>";
		$rij[] = "	<td>". ($data_array['dader'] != 0 ? "<a href='../profiel.php?id=". $dader->id ."'>".  $dader->getName() ."</a>" : "&nbsp;") ."</td>";
		$rij[] = "	<td>&nbsp;</td>";
		$rij[] = "	<td>". ($data_array['slachtoffer'] != 0 ? "<a href='../profiel.php?id=". $slachtoffer->id ."'>". $slachtoffer->getName() ."</a>" : "&nbsp;") ."</td>";
		$rij[] = "	<td>&nbsp;</td>";
		$rij[] = "	<td>". $pre . $data_array['message']. ($data_array['vermomd'] > 0 ? ' (vermomd als '. $vermomming->getName() .')' : '') . $post ."</td>";
		$rij[] = "</tr>";
		
		$rijen[] = implode(NL, $rij);
	}	
} else {
	$rij[] = "<tr>";
	$rij[] = "	<td colspan='7'>Geen logfiles</td>";	
	$rij[] = "</tr>";
	$rijen[] = implode(NL, $rij);
}

echo showCSSHeader();
echo "<div class='content_horz_kolom'>".NL."<div class='content_block'>".NL. implode(NL, $zoekScherm_1).NL."</div>".NL."</div>".NL;
echo "<div class='content_horz_kolom'>".NL."<div class='content_block'>".NL. implode(NL, $zoekScherm_2).NL."</div>".NL."</div>".NL;
echo "</div><div class='row'>";
#echo "<div class='content_horz_kolom'>".NL."<div class='content_block'><table>".NL. implode(NL, $blok_1).NL."</table></div>".NL."</div>".NL;
#echo "<div class='content_horz_kolom'>".NL."<div class='content_block'><table>".NL. implode(NL, $blok_2).NL."</table></div>".NL."</div>".NL;
echo "<div class='content_horz_kolom_full'>".NL."<div class='content_block'><table>".NL. implode(NL, $rijen).NL."</table></div>".NL."</div>".NL;
echo showCSSFooter();
?>