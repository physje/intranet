<?php
include_once('include/functions.php');
include_once('include/EB_functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$db = connect_db();
$showLogin = true;


if(isset($_REQUEST['hash'])) {
	$dader = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($dader)) {
		toLog('error', '', '', 'ongeldige hash (profiel)');
		$showLogin = true;
	} else {
		$showLogin = false;
		$_SESSION['ID'] = $dader;
		toLog('info', $dader, $_REQUEST['id'], 'profiel mbv hash');
	}
}

if($showLogin) {
	$cfgProgDir = 'auth/';
	include($cfgProgDir. "secure.php");
}

if(isset($_POST['save_data'])) {
	$sql_update = "UPDATE $TableUsers SET $UserFormeelMail = '". $_POST['form_mail'] ."', $UserEBRelatie = '". $_POST['EB_relatie'] ."' WHERE $UserID like ". $_POST['id'];
	mysqli_query($db, $sql_update);
}

# Welk profiel wordt opgevraagd
# Indien dat niet bekend is, dan het eigen profiel
$id = getParam('id', $_SESSION['ID']);
$personData = getMemberDetails($id);

# Wie mag wat zien
# Om te beginnen wordt bijna niks getoond
# Alleen mail en telefoonnumme
$aToon['adres'] = false;
$aToon['PC'] = false;
$aToon['tel'] = true;
$aToon['mail'] = true;
$aToon['form_mail'] = false;
$aToon['wijk'] = false;
$aToon['geboorte'] = false;
$aToon['kerk_staat'] = false;
$aToon['status'] = false;
$aToon['hash'] = false;
$aToon['burgelijk'] = false;
$aToon['relatie'] = false;
$aToon['username'] = false;
$aToon['EB_relatie'] = false;
$aToon['vestiging'] = false;
$aToon['change'] = false;
$aToon['visit'] = false;
$aToon['familie'] = false;

# Wijkteam mag alleen details van eigen wijk zien
$wijkteam = getWijkteamLeden($personData['wijk']);
if(in_array($_SESSION['ID'], $wijkteam)) {
	$aToon['adres'] = true;
	$aToon['PC'] = true;
	$aToon['tel'] = true;
	$aToon['mail'] = true;
	$aToon['geboorte'] = true;
	$aToon['familie'] = true;
}

# Van je eigen profiel mag je weer meer zien
if($_SESSION['ID'] == $id) {
	$aToon['adres'] = true;
	$aToon['PC'] = true;
	$aToon['tel'] = true;
	$aToon['mail'] = true;
	$aToon['geboorte'] = true;
	$aToon['username'] = true;
	$aToon['familie'] = true;
}

# Admin mag alles zien
if(in_array(1, getMyGroups($_SESSION['ID']))) {
	$aToon['adres'] = true;
	$aToon['PC'] = true;
	$aToon['tel'] = true;
	$aToon['mail'] = true;
	$aToon['form_mail'] = true;
	$aToon['wijk'] = true;
	$aToon['geboorte'] = true;
	$aToon['kerk_staat'] = true;
	$aToon['status'] = true;
	$aToon['hash'] = true;
	$aToon['burgelijk'] = true;
	$aToon['relatie'] = true;
	$aToon['username'] = true;
	$aToon['EB_relatie'] = true;
	$aToon['vestiging'] = true;
	$aToon['change'] = true;
	$aToon['visit'] = true;
	$aToon['familie'] = true;
}

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

# De admin kan hier zaken wijzigen, dus even een formulier aanmaken
if(in_array(1, getMyGroups($_SESSION['ID']))) {
	echo "	<form method='post' action='$_SERVER[PHP_SELF]'>".NL;	
	echo "	<input type='hidden' name='id' value='$id'>".NL;	
}

# Eigen gegevens
echo "	<table>".NL;

if($aToon['adres']) {
	echo "	<tr>".NL;
	echo "		<td><b>Adres</b></td>".NL;
	echo "		<td><a href='https://www.google.nl/maps/place/". urlencode($personData['straat'] .' '. $personData['huisnummer'].$personData['huisletter'] .', '. $personData['PC'] .' '. $personData['plaats']) ."' target='_blank'>". $personData['straat'] .' '. $personData['huisnummer'].$personData['huisletter'].($personData['toevoeging'] != '' ? '-'.$personData['toevoeging'] : '')."</a>";
	if(!in_array($_SESSION['ID'], $familie)) {
		$ownData = getMemberDetails($_SESSION['ID']);
		echo " <a href='https://www.google.nl/maps/dir/". urlencode($ownData['straat'] .' '. $ownData['huisnummer'].$ownData['huisletter'] .', '. $ownData['PC'] .' '. $ownData['plaats']) ."/". urlencode($personData['straat'] .' '. $personData['huisnummer'].$personData['huisletter'] .', '. $personData['PC'] .' '. $personData['plaats']) ."' title='klik hier om de route te tonen' target='_blank'><img src='images/GoogleMaps.png'></a>";
	}
	echo "	</td>".NL;
	echo "	</tr>".NL;
}

if($aToon['PC']) {
	echo "	<tr>".NL;
	echo "		<td><b>Postcode</b></td>".NL;
	echo "		<td>". $personData['PC'] .' '. $personData['plaats'] ."</td>".NL;
	echo "	</tr>".NL;
}

if($aToon['tel'] AND $personData['tel'] != '') {
	echo "	<tr>".NL;
	echo "		<td><b>Telefoon</b></td>".NL;
	echo "		<td>". $personData['tel'] ."</td>".NL;
	echo "	</tr>".NL;
}

if($aToon['mail'] AND $personData['mail'] != '') {
	echo "	<tr>".NL;	
	echo "		<td><b>Mailadres</b></td>".NL;
	echo "		<td><a href='mailto:". makeName($id, 5) ." <".$personData['mail'] .">'>". $personData['mail'] ."</td>".NL;
	echo "	</tr>".NL;
}

if($aToon['form_mail'] AND $personData['form_mail'] != '') {
	echo "	<tr>".NL;
	echo "		<td><b>Formeel mailadres</b></td>".NL;
	echo "		<td><input type='text' name='form_mail' value='". $personData['form_mail'] ."'></td>".NL;
	echo "	</tr>".NL;
}

if($aToon['wijk']) {
	echo "	<tr>".NL;
	echo "		<td><b>Wijk</b></td>".NL;
	echo "		<td><a href='ledenlijst.php?wijk=". $personData['wijk'] ."'>".$personData['wijk'] ."</a></td>".NL;
	echo "	</tr>".NL;
}

if($aToon['geboorte']) {
	echo "	<tr>".NL;
	echo "		<td><b>Geboortedatum</b></td>".NL;
	echo "		<td>". time2str("%d %B '%y", $personData['geb_unix']) ."</td>".NL;
	echo "	</tr>".NL;
}

if($aToon['kerk_staat']) {
	echo "	<tr>".NL;
	echo "		<td><b>Kerkelijke staat</b></td>".NL;
	echo "		<td>". $personData['belijdenis'] ."</td>".NL;
	echo "	</tr>".NL;
}

if($aToon['status']) {
	echo "	<tr>".NL;
	echo "		<td><b>Status</b></td>".NL;
	echo "		<td>". $personData['status'] ."</td>".NL;
	echo "	</tr>".NL;
}

if($aToon['hash']) {	
	echo "	<tr>".NL;
	echo "		<td valign='top'><b>Hash</b></td>".NL;
	echo "		<td>". $personData['hash_short'] ."<br>". $personData['hash_long'] ."</td>".NL;
	echo "	</tr>".NL;
}

if($aToon['burgelijk']) {
	echo "	<tr>".NL;
	echo "		<td><b>Burgerlijk</b></td>".NL;
	echo "		<td>". $personData['burgelijk'] ."</td>".NL;
	echo "	</tr>".NL;
}

if($aToon['relatie']) {
	echo "	<tr>".NL;
	echo "		<td><b>Relatie</b></td>".NL;
	echo "		<td>". $personData['relatie'] ."</td>".NL;
	echo "	</tr>".NL;
}

if($aToon['username']) {
	echo "	<tr>".NL;
	echo "		<td><b>Gebruikersnaam</b></td>".NL;
	echo "		<td><a href='account.php?id=$id'>". $personData['username'] ."</a></td>".NL;
	echo "	</tr>".NL;
}

if($aToon['EB_relatie']) {
	echo "	<tr>".NL;
	echo "		<td><b>e-boekhouden</b></td>".NL;
	echo "	<td><select name='EB_relatie'>";
	echo "	<option value=''>Selecteer relatie</option>";
	
	$relaties = eb_getRelaties();
	
	foreach($relaties as $relatieData) {
		echo "	<option value='". $relatieData['code'] ."'". ($personData['eb_code'] == $relatieData['code'] ? ' selected' : '') .">". substr($relatieData['naam'], 0, 35) ."</option>";
	}
			
	echo "	</select></td>";
	echo "	</tr>".NL;	
}

if($aToon['vestiging'] AND $personData['vestiging'] > 0) {
	echo "	<tr>".NL;
	echo "		<td><b>Vestingsdatum</b></td>".NL;
	echo "		<td>". time2str("%d %B '%y", $personData['vestiging']) ."</td>".NL;
	echo "	</tr>".NL;
}
	
if($aToon['change'] AND $personData['change'] > 0) {
	echo "	<tr>".NL;
	echo "		<td><b>Laatste wijziging</b></td>".NL;
	echo "		<td>". time2str("%d %B '%y", $personData['change']) ."</td>".NL;
	echo "	</tr>".NL;
}

if($aToon['visit'] AND $personData['visit'] > 0) {
	echo "	<tr>".NL;
	echo "		<td><b>Laatste login</b></td>".NL;
	echo "		<td>". time2str("%d %B '%y", $personData['visit']) ."</td>".NL;
	echo "	</tr>".NL;	
}

if(in_array(1, getMyGroups($_SESSION['ID']))) {	
	echo "	<tr>".NL;
	echo "		<td colspan='2'>&nbsp;</td>".NL;
	echo "	</tr>".NL;
	echo "	<tr>".NL;
	echo "		<td>&nbsp;</td>".NL;
	echo "		<td><input type='submit' name='save_data' value='Opslaan'></td>".NL;
	echo "	</tr>".NL;
}

echo "	</table>".NL;

# De admin kan hier zaken wijzigen, dus even een formulier aangemaakt
if(in_array(1, getMyGroups($_SESSION['ID'])))	echo "	</form>".NL;

echo "	</td>".NL;
echo "	<td width=4%>&nbsp;</td>".NL;

# Familieleden
echo "	<td width=44% valign='top'>";

# Familie alleen tonen indien dat nodig is en er familie is 
if($aToon['familie'] AND count($familie) > 1) {
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
