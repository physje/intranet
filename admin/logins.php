<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$users			= array();

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

$lid		= getParam('lid', '');
$ip			= getParam('ip', '');

$where[] = "$LoginTijd BETWEEN '$bJaar-$bMaand-$bDag $bUur:$bMin:00' AND '$eJaar-$eMaand-$eDag $eUur:$eMin:59'";
if($lid != '') $where[] = "$LoginLid = $lid";
if($ip != '') $where[] = "($LoginIP like '%$ip%' OR $LoginIP like '$ip%' OR $LoginIP like '%$ip')";

$sql		= "SELECT * FROM $TableLogins WHERE ". implode(' AND ', $where);
$result	= mysqli_query($db, $sql);

if($row		= mysqli_fetch_array($result)) {
	do {
		$rij = array();
		$rij[] = "<tr>";
		$rij[] = "	<td>". $row[$LoginTijd] ."</td>";
		$rij[] = "	<td>&nbsp;</td>";
		$rij[] = "	<td>". ($row[$LoginLid] != '' ? "<a href='?lid=". $row[$LoginLid] ."'>". makeName($row[$LoginLid], 5) ."</a>" : "&nbsp;") ."</td>";
		$rij[] = "	<td>&nbsp;</td>";
		$rij[] = "	<td>". ($row[$LoginIP] != '' ? "<a href='https://who.is/whois-ip/ip-address/". $row[$LoginIP] ."'>". $row[$LoginIP] ."</a>" : "&nbsp;") ."</td>";
		$rij[] = "	<td>&nbsp;</td>";
		$rij[] = "	<td>". $row[$LoginAgent] ."</td>";
		$rij[] = "</tr>";
		
		$rijen[] = implode(NL, $rij);
		
		if(!in_array($row[$LoginLid], $users)) {
			$users[] = $row[$LoginLid];
		}		
	} while($row		= mysqli_fetch_array($result));
}


$zoekScherm_1[] = "<table>";
$zoekScherm_1[] = "<tr>";
$zoekScherm_1[] = "	<td colspan='2'><b>Van</b></td>";
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
$zoekScherm_1[] = "</tr>";
$zoekScherm_1[] = "<tr>";
$zoekScherm_1[] = "	<td colspan='2'><b>Tot</b></td>";
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
$zoekScherm_1[] = "</tr>";
$zoekScherm_1[] = "</table>";

$zoekScherm_2[] = "<table border=0>";
$zoekScherm_2[] = "<tr>";
$zoekScherm_2[] = "	<td><b>Persoon</b></td>";
$zoekScherm_2[] = "	<td rowspan='4'><input type='submit' name='save' value='Zoeken'></td>";
$zoekScherm_2[] = "</tr>";

$zoekScherm_2[] = "<tr>";
$zoekScherm_2[] = "	<td><select name='lid'>";
$zoekScherm_2[] = "	<option value=''>Alle</option>";
foreach($users as $userID) {
	$zoekScherm_2[] = "	<option value='$userID'". ($lid == $userID ? ' selected' : '') .">". makeName($userID, 5) ."</option>";
}
$zoekScherm_2[] = "	</select></td>";
$zoekScherm_2[] = "</tr>";

$zoekScherm_2[] = "<tr>";
$zoekScherm_2[] = "	<td><b>IP</b></td>";
$zoekScherm_2[] = "</tr>";

$zoekScherm_2[] = "<tr>";
$zoekScherm_2[] = "	<td><input type='text' name='ip' value='$ip' size=40></td>";
$zoekScherm_2[] = "</tr>";

$zoekScherm_2[] = "</table>";


echo showCSSHeader();
echo "<form method='post' action='$_SERVER[PHP_SELF]'>";
echo "<div class='content_horz_kolom'>".NL."<div class='content_block'>".NL. implode(NL, $zoekScherm_1).NL."</div>".NL."</div>".NL;
echo "<div class='content_horz_kolom'>".NL."<div class='content_block'>".NL. implode(NL, $zoekScherm_2).NL."</div>".NL."</div>".NL;
echo "</form>";
echo "</div><div class='row'>";
echo "<div class='content_horz_kolom_full'>".NL."<div class='content_block'><table>".NL. implode(NL, $rijen).NL."</table></div>".NL."</div>".NL;
echo showCSSFooter();

?>