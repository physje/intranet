<?php
include_once('include/functions.php');
include_once('include/EB_functions.php');
include_once('include/config.php');
include_once('Classes/Member.php');
include_once('Classes/Wijk.php');
include_once('Classes/Logging.php');
include_once('include/HTML_TopBottom.php');
$showLogin = true;

if(isset($_REQUEST['hash'])) {
	$dader = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($dader)) {
		toLog('ongeldige hash (profiel)', 'error');
		$showLogin = true;
	} else {
		$showLogin = false;
		session_start(['cookie_lifetime' => $cookie_lifetime]);
		$_SESSION['useID'] = $dader;
		$_SESSION['realID'] = $dader;
		toLog('profiel mbv hash');
	}
}

if($showLogin) {
	$cfgProgDir = 'auth/';
	include($cfgProgDir. "secure.php");
}

if(isset($_POST['save_data'])) {
	$user = new Member($_POST['id']);
	$user->email_formeel = $_POST['form_mail'];
	$user->boekhouden = $_POST['EB_relatie'];
	if($user->save()) {
		toLog('Problemen met aanpassen profielgegevens', 'error');
	} else {
		toLog('Profielgegevens aangepast');
	}
}

# Welk profiel wordt opgevraagd
# Indien dat niet bekend is, dan het eigen profiel
$id = getParam('id', $_SESSION['useID']);
$person = new Member($id);

$gebruiker = new Member($_SESSION['useID']);
$myGroups = $gebruiker->getTeams();

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

$wijk = new Wijk();
$wijk->wijk = $person->wijk;
$wijkteam = $wijk->getWijkteam();

# Admin mag alles zien
if(in_array(1, $myGroups)) {
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

# Van je eigen profiel mag je weer meer zien
elseif($_SESSION['useID'] == $id) {
	$aToon['adres'] = true;
	$aToon['PC'] = true;
	$aToon['tel'] = true;
	$aToon['mail'] = true;
	$aToon['geboorte'] = true;
	$aToon['username'] = true;
	$aToon['familie'] = true;
}

# Wijkteam mag alleen details van eigen wijk zien
elseif(array_key_exists ($_SESSION['useID'], $wijkteam)) {
	$aToon['adres'] = true;
	$aToon['PC'] = true;
	$aToon['tel'] = true;
	$aToon['mail'] = true;
	$aToon['geboorte'] = true;
	$aToon['familie'] = true;
}



# Als je als admin bent ingelogd zie je alle leden, anders alleen de actieve
#$familie = getFamilieleden($id, in_array(1, getMyGroups($_SESSION['useID'])));
$familie = $person->getFamilieLeden();

toLog('profiel bekeken', 'debug', $id);

# De admin kan hier zaken wijzigen, dus even een formulier aanmaken
if(in_array(1, $myGroups)) {
	$blok[] = "	<form method='post' action='$_SERVER[PHP_SELF]'>";	
	$blok[] = "	<input type='hidden' name='id' value='$id'>";	
}

# Eigen gegevens
$blok[] = "	<table>";

if($aToon['adres']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Adres</b></td>";
	$blok[] = "		<td><a href='https://www.google.nl/maps/place/". urlencode($person->straat .' '. $person->huisnummer . $person->huisnummer_letter .', '. $person->postcode .' '. $person->woonplaats) ."' target='_blank'>". $person->straat .' '. $person->huisnummer . $person->huisnummer_letter . ($person->huisnummer_toevoeging != '' ? '-'.$person->huisnummer_toevoeging : '')."</a>";
	if(!in_array($_SESSION['useID'], $familie)) {		
		$blok[] = " <a href='https://www.google.nl/maps/dir/". urlencode($gebruiker->straat .' '. $gebruiker->huisnummer . $gebruiker->huisnummer_letter .', '. $gebruiker->postcode .' '. $gebruiker->woonplaats) ."/". urlencode($person->straat .' '. $person->huisnummer . $person->huisnummer_letter .', '. $person->postcode .' '. $person->woonplaats) ."' title='klik hier om de route te tonen' target='_blank'><img src='images/GoogleMaps.png'></a>";
	}
	$blok[] = "	</td>";
	$blok[] = "	</tr>";
}

if($aToon['PC']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Postcode</b></td>";
	$blok[] = "		<td>". $person->postcode .' '. $person->woonplaats ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['tel'] AND $person->telefoon != '') {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Telefoon</b></td>";
	$blok[] = "		<td><a href='tel:". str_replace('-', '', $person->telefoon) ."'>". $person->telefoon ."</a></td>";
	$blok[] = "	</tr>";
}

if($aToon['mail'] AND $person->email != '') {
	$blok[] = "	<tr>";	
	$blok[] = "		<td><b>Mailadres</b></td>";
	$blok[] = "		<td><a href='mailto:". $person->getName() ." <". $person->email .">'>". $person->email ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['form_mail']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Formeel mailadres</b></td>";
	$blok[] = "		<td><input type='text' name='form_mail' value='". $person->email_formeel ."'></td>";
	$blok[] = "	</tr>";
}

if($aToon['wijk']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Wijk</b></td>";
	$blok[] = "		<td><a href='ledenlijst.php?wijk=". $person->wijk ."'>".$person->wijk ."</a></td>";
	$blok[] = "	</tr>";
}

if($aToon['geboorte']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Geboortemaand</b></td>";
	//echo "		<td>". time2str("%d %B '%y", $personData['geb_unix']) ."</td>";
	$blok[] = "		<td>". time2str("F Y", mktime(0,0,0,$person->geboorte_maand, $person->geboorte_dag, $person->geboorte_jaar)) ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['kerk_staat']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Kerkelijke staat</b></td>";
	$blok[] = "		<td>". $person->doop_belijdenis ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['status']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Status</b></td>";
	$blok[] = "		<td>". $person->status ."</td>";
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
	$blok[] = "		<td>". $person->burgelijk ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['relatie']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Relatie</b></td>";
	$blok[] = "		<td>". $person->relatie ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['username']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Gebruikersnaam</b></td>";
	$blok[] = "		<td><a href='account.php?id=$id'>". $person->username ."</a></td>";
	$blok[] = "	</tr>";
}

if($aToon['EB_relatie']) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>e-boekhouden</b></td>";
	$blok[] = "	<td><select name='EB_relatie'>";
	$blok[] = "	<option value=''>Selecteer relatie</option>";
	
	$relaties = eb_getRelaties();	
	foreach($relaties as $relatieData) {
		$blok[] = "	<option value='". $relatieData['code'] ."'". ($person->boekhouden == $relatieData['code'] ? ' selected' : '') .">". substr($relatieData['naam'], 0, 35) ."</option>";
	}
			
	$blok[] = "	</select></td>";
	$blok[] = "	</tr>";	
}

if($aToon['vestiging'] AND $person->tijd_vestiging > 0) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Vestingsdatum</b></td>";
	$blok[] = "		<td>". time2str("d F 'y", $person->tijd_vestiging) ."</td>";
	$blok[] = "	</tr>";
}
	
if($aToon['change'] AND $person->tijd_wijziging > 0) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Laatste wijziging</b></td>";
	$blok[] = "		<td>". time2str("d F 'y", $person->tijd_wijziging) ."</td>";
	$blok[] = "	</tr>";
}

if($aToon['visit'] AND $person->tijd_bezoek > 0) {
	$blok[] = "	<tr>";
	$blok[] = "		<td><b>Laatste login</b></td>";
	$blok[] = "		<td>". time2str("d F 'y", $person->tijd_bezoek) ."</td>";
	$blok[] = "	</tr>";	
}

$blok[] = "	</table>".NL;

if(in_array(1, $myGroups)) {	
	$blok[] = "<p class='after_table'><input type='submit' name='save_data' value='Opslaan'></p>";
}

# De admin kan hier zaken wijzigen, dus even een formulier aangemaakt
if(in_array(1, $myGroups))	$blok[] = "</form>";

# Familie alleen tonen indien dat nodig is en er familie is
if($aToon['familie'] AND count($familie) > 1) {
	foreach($familie as $lid) {
		if($lid != $id) {
			$familieLid = new Member($lid);
			
			if(in_array($familieLid->status, array('afgemeld', 'afgevoerd', 'onttrokken'))) {
				$class = 'ontrokken';
			} elseif(in_array($familieLid->status, array('overleden', 'vertrokken'))) {
				$class = 'inactief';
			} else {
				$class = '';
			}
			$blok_2[] = "<a href='?id=$lid' class='$class'>". $familieLid->getName() ."</a> ('". substr($familieLid->geboorte_jaar, -2) .")<br>";
		}
	}
} else {
	$blok_2[] = '	&nbsp;';
}

echo showCSSHeader(array('default', 'table_default'));
echo '<div class="content_vert_kolom">'.NL;
echo '<h1>'. $person->getName() .'</h1>'.NL;
#echo '<h2>Profiel</h2>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $blok).NL."</div>".NL;
echo '<h2>Familieleden</h2>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $blok_2).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();


?>
