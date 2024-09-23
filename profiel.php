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
		$_SESSION['useID'] = $dader;
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
$id = getParam('id', $_SESSION['useID']);
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
if(in_array($_SESSION['useID'], $wijkteam)) {
	$aToon['adres'] = true;
	$aToon['PC'] = true;
	$aToon['tel'] = true;
	$aToon['mail'] = true;
	$aToon['geboorte'] = true;
	$aToon['familie'] = true;
}

# Van je eigen profiel mag je weer meer zien
if($_SESSION['useID'] == $id) {
	$aToon['adres'] = true;
	$aToon['PC'] = true;
	$aToon['tel'] = true;
	$aToon['mail'] = true;
	$aToon['geboorte'] = true;
	$aToon['username'] = true;
	$aToon['familie'] = true;
}

# Admin mag alles zien
if(in_array(1, getMyGroups($_SESSION['useID']))) {
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
$familie = getFamilieleden($id, in_array(1, getMyGroups($_SESSION['useID'])));

toLog('debug', $_SESSION['realID'], $id, 'profiel bekeken');


# De admin kan hier zaken wijzigen, dus even een formulier aanmaken
if(in_array(1, getMyGroups($_SESSION['useID']))) {
	$blok[] = "	<form method='post' action='$_SERVER[PHP_SELF]'>";	
	$blok[] = "	<input type='hidden' name='id' value='$id'>";	
}

# Eigen gegevens
$blok[] = "	<table>";

if($aToon['adres']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Adres</b></td>";
	$blok[] = "		<td><a href='https://www.google.nl/maps/place/". urlencode($personData['straat'] .' '. $personData['huisnummer'].$personData['huisletter'] .', '. $personData['PC'] .' '. $personData['plaats']) ."' target='_blank'>". $personData['straat'] .' '. $personData['huisnummer'].$personData['huisletter'].($personData['toevoeging'] != '' ? '-'.$personData['toevoeging'] : '')."</a>";
	if(!in_array($_SESSION['useID'], $familie)) {
		$ownData = getMemberDetails($_SESSION['useID']);
		$blok[] = " <a href='https://www.google.nl/maps/dir/". urlencode($ownData['straat'] .' '. $ownData['huisnummer'].$ownData['huisletter'] .', '. $ownData['PC'] .' '. $ownData['plaats']) ."/". urlencode($personData['straat'] .' '. $personData['huisnummer'].$personData['huisletter'] .', '. $personData['PC'] .' '. $personData['plaats']) ."' title='klik hier om de route te tonen' target='_blank'><img src='images/GoogleMaps.png'></a>";
	}
	$blok[] = "	</td>";
	$blok[] = "	</tr>";
}

if($aToon['PC']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Postcode</b></td>";
	$blok[] = "		<td>". $personData['PC'] .' '. $personData['plaats'] ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['tel'] AND $personData['tel'] != '') {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Telefoon</b></td>";
	$blok[] = "		<td><a href='tel:". str_replace('-', '', $personData['tel']) ."'>". $personData['tel'] ."</a></td>";
	$blok[] = "	</tr>";
}

if($aToon['mail'] AND $personData['mail'] != '') {
	$blok[] = "	<tr>";	
	$blok[] = "		<td><b>Mailadres</b></td>";
	$blok[] = "		<td><a href='mailto:". makeName($id, 5) ." <".$personData['mail'] .">'>". $personData['mail'] ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['form_mail']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Formeel mailadres</b></td>";
	$blok[] = "		<td><input type='text' name='form_mail' value='". $personData['form_mail'] ."'></td>";
	$blok[] = "	</tr>";
}

if($aToon['wijk']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Wijk</b></td>";
	$blok[] = "		<td><a href='ledenlijst.php?wijk=". $personData['wijk'] ."'>".$personData['wijk'] ."</a></td>";
	$blok[] = "	</tr>";
}

if($aToon['geboorte']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Geboortemaand</b></td>";
	//echo "		<td>". time2str("%d %B '%y", $personData['geb_unix']) ."</td>";
	$blok[] = "		<td>". time2str("%B '%y", $personData['geb_unix']) ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['kerk_staat']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Kerkelijke staat</b></td>";
	$blok[] = "		<td>". $personData['belijdenis'] ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['status']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Status</b></td>";
	$blok[] = "		<td>". $personData['status'] ."</td>";
	$blok[] = "	</tr>";
}

#if($aToon['hash']) {	
#	$blok[] = "	<tr>";
#	$blok[] = "		<td valign='top'><b>Hash</b></td>";
#	$blok[] = "		<td>". $personData['hash_short'] ."<br>". $personData['hash_long'] ."</td>";
#	$blok[] = "	</tr>";
#}

if($aToon['burgelijk']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Burgerlijk</b></td>";
	$blok[] = "		<td>". $personData['burgelijk'] ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['relatie']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Relatie</b></td>";
	$blok[] = "		<td>". $personData['relatie'] ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['username']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Gebruikersnaam</b></td>";
	$blok[] = "		<td><a href='account.php?id=$id'>". $personData['username'] ."</a></td>";
	$blok[] = "	</tr>";
}

if($aToon['EB_relatie']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>e-boekhouden</b></td>";
	$blok[] = "	<td><select name='EB_relatie'>";
	$blok[] = "	<option value=''>Selecteer relatie</option>";
	
	$relaties = eb_getRelaties();
	
	foreach($relaties as $relatieData) {
		$blok[] = "	<option value='". $relatieData['code'] ."'". ($personData['eb_code'] == $relatieData['code'] ? ' selected' : '') .">". substr($relatieData['naam'], 0, 35) ."</option>";
	}
			
	$blok[] = "	</select></td>";
	$blok[] = "	</tr>";	
}

if($aToon['vestiging'] AND $personData['vestiging'] > 0) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Vestingsdatum</b></td>";
	$blok[] = "		<td>". time2str("%d %B '%y", $personData['vestiging']) ."</td>";
	$blok[] = "	</tr>";
}
	
if($aToon['change'] AND $personData['change'] > 0) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Laatste wijziging</b></td>";
	$blok[] = "		<td>". time2str("%d %B '%y", $personData['change']) ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['visit'] AND $personData['visit'] > 0) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Laatste login</b></td>";
	$blok[] = "		<td>". time2str("%d %B '%y", $personData['visit']) ."</td>";
	$blok[] = "	</tr>";	
}

$blok[] = "	</table>".NL;

if(in_array(1, getMyGroups($_SESSION['useID']))) {	
	$blok[] = "<p class='after_table'><input type='submit' name='save_data' value='Opslaan'></p>";
}

# De admin kan hier zaken wijzigen, dus even een formulier aangemaakt
if(in_array(1, getMyGroups($_SESSION['useID'])))	$blok[] = "</form>";

# Familie alleen tonen indien dat nodig is en er familie is 
if($aToon['familie'] AND count($familie) > 1) {
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
			$blok_2[] = "<a href='?id=$leden' class='$class'>". makeName($leden, 5) ."</a> ('". substr($famData['jaar'], -2) .")<br>";
		}
	}
} else {
	$blok_2[] = '	&nbsp;';
}

echo showCSSHeader(array('default', 'table_default'));
echo '<div class="content_vert_kolom">'.NL;
echo '<h1>'. makeName($id, 6) .'</h1>'.NL;
#echo '<h2>Profiel</h2>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $blok).NL."</div>".NL;
#echo '<h2>Familieleden</h2>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $blok_2).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();


?>
