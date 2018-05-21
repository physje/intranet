<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$id = getParam('id', $_SESSION['ID']);

$personData = getMemberDetails($id);
# Als je als admin bent ingelogd zie je alle leden, anders alleen de actieve 
$familie = getFamilieleden($id, in_array(1, getMyGroups($_SESSION['ID'])));

toLog('debug', $_SESSION['ID'], $id, 'profiel bekeken'); 

# Pagina tonen
echo $HTMLHeader;
echo "<h1>". makeName($id, 6) ."</h1>".NL;
echo "<table width=100% border=0>".NL;
echo "<tr>".NL;
echo "	<td width=4%>&nbsp;</td>".NL;
echo "	<td width=44% valign='top'>";

# Eigen gegevens
echo "	<table>".NL;
echo "	<tr>".NL;
echo "		<td><b>Adres</b></td>".NL;
echo "		<td><a href='https://www.google.nl/maps/place/". urlencode($personData['straat'] .' '. $personData['huisnummer'] .', '. $personData['PC'] .' '. $personData['plaats']) ."' target='_blank'>". $personData['straat'] .' '. $personData['huisnummer'] ."</a>";
if(!in_array($_SESSION['ID'], $familie)) {
	$ownData = getMemberDetails($_SESSION['ID']);
	echo " <a href='https://www.google.nl/maps/dir/". urlencode($ownData['straat'] .' '. $ownData['huisnummer'] .', '. $ownData['PC'] .' '. $ownData['plaats']) ."/". urlencode($personData['straat'] .' '. $personData['huisnummer'] .', '. $personData['PC'] .' '. $personData['plaats']) ."' title='klik hier om de route te tonen' target='_blank'><img src='images/GoogleMaps.png'></a>";
}
echo "	</td>".NL;
echo "	</tr>".NL;
echo "	<tr>".NL;
echo "		<td><b>Postcode</b></td>".NL;
echo "		<td>". $personData['PC'] .' '. $personData['plaats'] ."</td>".NL;
echo "	</tr>".NL;
echo "		<td><b>Telefoon</b></td>".NL;
echo "		<td>". $personData['tel'] ."</td>".NL;
echo "	</tr>".NL;
echo "		<td><b>Mailadres</b></td>".NL;
echo "		<td><a href='mailto:". makeName($id, 5) ." <".$personData['mail'] .">'>". $personData['mail'] ."</td>".NL;
echo "	</tr>".NL;
echo "	<tr>".NL;
echo "		<td><b>Wijk</b></td>".NL;
echo "		<td><a href='ledenlijst.php?wijk=". $personData['wijk'] ."'>".$personData['wijk'] ."</a></td>".NL;
echo "	</tr>".NL;
echo "	<tr>".NL;
echo "		<td><b>Geboortedatum</b></td>".NL;
echo "		<td>". strftime("%d %B '%y", $personData['geb_unix']) ."</td>".NL;
echo "	</tr>".NL;
echo "	<tr>".NL;
echo "		<td><b>Kerkelijke staat</b></td>".NL;
echo "		<td>". $personData['belijdenis'] ."</td>".NL;
echo "	</tr>".NL;
echo "	<tr>".NL;
echo "		<td><b>Status</b></td>".NL;
echo "		<td>". $personData['status'] ."</td>".NL;
echo "	</tr>".NL;

if(in_array(1, getMyGroups($_SESSION['ID']))) {	
	echo "	<tr>".NL;
	echo "		<td valign='top'><b>Hash</b></td>".NL;
	echo "		<td>". $personData['hash_short'] ."<br>". $personData['hash_long'] ."</td>".NL;
	echo "	</tr>".NL;
	echo "	<tr>".NL;
	echo "		<td><b>Burgerlijk</b></td>".NL;
	echo "		<td>". $personData['burgelijk'] ."</td>".NL;
	echo "	</tr>".NL;
	echo "	<tr>".NL;
	echo "		<td><b>Relatie</b></td>".NL;
	echo "		<td>". $personData['relatie'] ."</td>".NL;
	echo "	</tr>".NL;
	echo "	<tr>".NL;
	echo "		<td><b>Gebruikersnaam</b></td>".NL;
	echo "		<td><a href='account.php?id=$id'>". $personData['username'] ."</a></td>".NL;
	echo "	</tr>".NL;
}

echo "	</table>".NL;
echo "	</td>".NL;
echo "	<td width=4%>&nbsp;</td>".NL;

# Familieleden
echo "	<td width=44% valign='top'>";

if(count($familie) > 1) {
	echo "	<b>Familieleden</b><br>";
	foreach($familie as $leden) {
		if($leden != $id) {
			$famData = getMemberDetails($leden);
			
			if($famData['status'] == 'afgemeld' OR $famData['status'] == 'afgevoerd' OR $famData['status'] == 'onttrokken') {
				$class = 'ontrokken';
			} elseif($famData['status'] == 'overleden' OR $famData['status'] == 'vertrokken') {
				$class = 'inactief';
			} else {
				$class = '';
			}
			echo "<a href='?id=$leden' class='$class'>". makeName($leden, 5) ."</a> ('". substr($famData['jaar'], -2) .")<br>";
		}
	}
} else {
	echo '	&nbsp;';
}

echo "	</td>".NL;
echo "	<td width=4%>&nbsp;</td>".NL;
echo "</tr>".NL;
echo "</table>".NL;

echo $HTMLFooter;

?>
