<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

# Default is vanaf 26 uur geleden tot 1 voor 12 deze dag
$startTijd	= mktime(date("H"),date("i"),date("s"),date("n"),(date("j")), date("Y")) - (36*60*60);
$eindTijd		= mktime(23,59);

$bMin		= getParam('bMin', date("i", $startTijd));
$bUur		= getParam('bUur', date("H", $startTijd));
$bDag		= getParam('bDag', date("d", $startTijd));
$bMaand	= getParam('bMaand', date("m", $startTijd));
$bJaar	= getParam('bJaar', date("Y", $startTijd));

$eMin		= getParam('eMin', date("i", $eindTijd));
$eUur		= getParam('eUur', date("H", $eindTijd));
$eDag		= getParam('eDag', date("d", $eindTijd));
$eMaand	= getParam('eMaand', date("m", $eindTijd));
$eJaar	= getParam('eJaar', date("Y", $eindTijd));

$start	= mktime ($bUur,$bMin,0,$bMaand,$bDag,$bJaar);
$end		= mktime ($eUur,$eMin,59,$eMaand,$eDag,$eJaar);

$dader				= getParam('dader', '');
$slachtoffer	= getParam('slacht', '');
$type					= getParam('type', array('info', 'error'));
$message			= getParam('message', '');
$aantal				= getParam('aantal', 100);

$cfgAantalLog = array(10, 25, 50, 100, 250, 500, 1000);

$logData = getLogData($start, $end, $type, $dader, $slachtoffer, $message, $aantal);

$zoekScherm_1[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$zoekScherm_1[] = "<table>";
$zoekScherm_1[] = "<tr>";
$zoekScherm_1[] = "	<td colspan='2'><b>Van</b></td>";
#$zoekScherm_1[] = "	<td rowspan='4'>&nbsp;</td>";
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
$zoekScherm_1[] = "	<td><select name='dader'>";
$zoekScherm_1[] = "	<option value=''>Alle</option>";
$users =  getLogMembers($start, $end);
foreach($users as $userID) {
	$zoekScherm_1[] = "	<option value='$userID'". ($dader == $userID ? ' selected' : '') .">". makeName($userID, 5) ."</option>";
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
$zoekScherm_1[] = "	<td><select name='slacht'>";
$zoekScherm_1[] = "	<option value=''>Alle</option>";
foreach($users as $userID) {
	$zoekScherm_1[] = "	<option value='$userID'". ($slachtoffer == $userID ? ' selected' : '') .">". makeName($userID, 5) ."</option>";
}
$zoekScherm_1[] = "	</select></td>";
$zoekScherm_1[] = "</tr>";
$zoekScherm_1[] = "</table>";


$zoekScherm_2[] = "<table border=0>";
$zoekScherm_2[] = "	<td><b>Zoekwoord</b></td>";
#$zoekScherm_2[] = "	<td rowspan='3'>&nbsp;</td>";
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


if(count($logData) > 0) {
	foreach($logData as $data_array) {
		if($data_array['type'] == 'error')	$pre = '<b>'; $post = '</b>';
		if($data_array['type'] == 'debug')	$pre = '<i>'; $post = '</i>';
		if($data_array['type'] == 'info')		$pre = ''; $post = '';
		
		$rij = array();
		$rij[] = "<tr>";
		$rij[] = "	<td>". date("d-m H:i:s", $data_array['tijd']) ."</td>";
		$rij[] = "	<td>&nbsp;</td>";
		$rij[] = "	<td>". ($data_array['dader'] != '' ? "<a href='../profiel.php?id=". $data_array['dader'] ."'>". makeName($data_array['dader'], 5) ."</a>" : "&nbsp;") ."</td>";
		$rij[] = "	<td>&nbsp;</td>";
		$rij[] = "	<td>". ($data_array['slachtoffer'] != '' ? "<a href='../profiel.php?id=". $data_array['slachtoffer'] ."'>". makeName($data_array['slachtoffer'], 5) ."</a>" : "&nbsp;") ."</td>";
		$rij[] = "	<td>&nbsp;</td>";
		$rij[] = "	<td>". $pre . $data_array['melding'] . $post ."</td>";
		$rij[] = "</tr>";
		
		$rijen[] = implode(NL, $rij);
	}
	
	$aantal = count($rijen);
		
	$blok_1 = array_slice($rijen, 0, round($aantal/2));
	
	if($aantal == 1) {
		$blok_2[] = '&nbsp;';
	} else {
		$blok_2 = array_slice($rijen, round($aantal/2));
	}	
}

echo showCSSHeader();
echo "<div class='content_horz_kolom'>".NL."<div class='content_block'>".NL. implode(NL, $zoekScherm_1).NL."</div>".NL."</div>".NL;
echo "<div class='content_horz_kolom'>".NL."<div class='content_block'>".NL. implode(NL, $zoekScherm_2).NL."</div>".NL."</div>".NL;
echo "</div><div class='row'>";
echo "<div class='content_horz_kolom'>".NL."<div class='content_block'><table>".NL. implode(NL, $blok_1).NL."</table></div>".NL."</div>".NL;
echo "<div class='content_horz_kolom'>".NL."<div class='content_block'><table>".NL. implode(NL, $blok_2).NL."</table></div>".NL."</div>".NL;
echo showCSSFooter();

?>