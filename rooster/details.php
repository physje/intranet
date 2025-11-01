<?php
include_once('../Classes/Mysql.php');
#include_once('../Classes/Team.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Rooster.php');
include_once('../include/functions.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$showLogin = true;

if(!isset($_REQUEST['id'])) {
	echo "geen rooster gedefinieerd";
	exit;
}

# Data ophalen om te bepalen of persoon wel toegang heeft
$rooster = new Rooster($_REQUEST['id']);
$beheerder = $rooster->beheerder;

# Ken kijk- en schrijf-rechten toe aan admin, beheerder en planner
$requiredUserGroups = array(1, $beheerder);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

# Als op de knop van de details geklikt is die data wegschrijven
if(isset($_POST['save_details'])) {
	$rooster->gelijk = $_POST['gelijkeDiensten'];
	$rooster->opmerking = (isset($_POST['interneOpmerking']) ? true : false);
	$rooster->ouder = (isset($_POST['ouderMail']) ? true : false);
	$rooster->partner = (isset($_POST['partnerMail']) ? true : false);
	$rooster->voorganger = (isset($_POST['voorganger']) ? true : false);
	$rooster->lastChange = date("Y-m-d H:i:s");
	$rooster->save();

	toLog('Details voor '. $rooster->naam .' aangepast');
}

# Als op de knop van de mail geklikt is die data wegschrijven
if(isset($_POST['save_mail'])) {
	$rooster->mail = urlencode($_POST['text_mail']);
	$rooster->onderwerp = urlencode($_POST['onderwerp_mail']);
	$rooster->van = urlencode($_POST['mail_afzender']);
	$rooster->vanNaam = urlencode($_POST['naam_afzender']);
	$rooster->save();

	toLog('Mail voor '. $rooster->naam .' aangepast');
}

# Data kan hierboven gewijzigd zijn, voor de zekerheid opnieuw ophalen
$rooster = new Rooster($_REQUEST['id']);

$rooster_details[] = "<h2>Details</h2>";
$rooster_details[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$rooster_details[] = "<input type='hidden' name='id' value='". $_REQUEST['id'] ."'>";
$rooster_details[] = "<table>";
$rooster_details[] = "<tr>";
$rooster_details[] = "	<td valign='top'>Diensten<br><small>(pas effect na opslaan)</small></td>";
$rooster_details[] = "	<td>";
$rooster_details[] = "	<input type='radio' name='gelijkeDiensten' value='0'". ($rooster->gelijk == 0 ? ' checked' : '') ."> Toon alle diensten afzonderlijk<br>";
$rooster_details[] = "	<input type='radio' name='gelijkeDiensten' value='1'". ($rooster->gelijk == 1 ? ' checked' : '') ."> Toon per dag (ochtend, middag en avond zijn gelijk)<br>";
$rooster_details[] = "	<input type='radio' name='gelijkeDiensten' value='2'". ($rooster->gelijk == 2 ? ' checked' : '') ."> Toon ochtend- en avonddiensten<br>";
$rooster_details[] = "	<input type='radio' name='gelijkeDiensten' value='3'". ($rooster->gelijk == 3 ? ' checked' : '') ."> Toon ochtenddiensten<br>";
$rooster_details[] = "	<input type='radio' name='gelijkeDiensten' value='4'". ($rooster->gelijk == 4 ? ' checked' : '') ."> Toon middag- en avonddiensten<br>";
$rooster_details[] = "	<input type='radio' name='gelijkeDiensten' value='5'". ($rooster->gelijk == 5 ? ' checked' : '') ."> Toon middagdiensten<br>";
$rooster_details[] = "	<input type='radio' name='gelijkeDiensten' value='6'". ($rooster->gelijk == 6 ? ' checked' : '') ."> Toon avonddiensten";
$rooster_details[] = "	</td>";
$rooster_details[] = "</tr>";
$rooster_details[] = "<tr>";
$rooster_details[] = "	<td><input type='checkbox' name='interneOpmerking' value='1'". ($rooster->opmerking ? ' checked' : '') ."></td>";
$rooster_details[] = "	<td>Mogelijkheid om interne opmerkingen bij het rooster te plaatsen<br><small>(huidige opmerkingen worden verwijderd bij uitvinken)</small></td>";
$rooster_details[] = "</tr>";
$rooster_details[] = "<tr>";
$rooster_details[] = "	<td><input type='checkbox' name='voorganger' value='1'". ($rooster->voorganger ? ' checked' : '') ."></td>";
$rooster_details[] = "	<td>Mogelijkheid om achter elke dienst de voorganger te tonen</td>";
$rooster_details[] = "</tr>";
$rooster_details[] = "<tr>";
$rooster_details[] = "	<td><input type='checkbox' name='ouderMail' value='1'". ($rooster->ouder ? ' checked' : '') ."></td>";
$rooster_details[] = "	<td>Mocht er een tiener op het rooster staan, stuur zijn/haar ouders dan een CC van de remindermail</td>";
$rooster_details[] = "</tr>";
$rooster_details[] = "<tr>";
$rooster_details[] = "	<td><input type='checkbox' name='partnerMail' value='1'". ($rooster->partner ? ' checked' : '') ."></td>";
$rooster_details[] = "	<td>Stuur niet alleen de persoon op het rooster een remindermail, maar ook zijn/haar partner</td>";
$rooster_details[] = "</tr>";
$rooster_details[] = "</table>";
$rooster_details[] = "<p class='after_table'><input type='submit' name='save_details' value='Details opslaan'></p>";
$rooster_details[] = "</form>";

$mail_details[] = "<h2>Remindermail</h2>";
$mail_details[] = "3 dagen voordat iemand op het rooster staat krijgt hij/zij een mail als reminder.<br>";
$mail_details[] = "Hieronder kan die mail worden vormgegeven.<br>";
$mail_details[] = "<br>";
$mail_details[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$mail_details[] = "<input type='hidden' name='id' value='". $_REQUEST['id'] ."'>";
$mail_details[] = "<table width=100%>";
$mail_details[] = "<tr>";
$mail_details[] = "	<td valign='top'>Afzendernaam</td>";
$mail_details[] = "	<td valign='top'><input type='text' name='naam_afzender' value='".$rooster->vanNaam ."'></td>";
$mail_details[] = "</tr>";
$mail_details[] = "<tr>";
$mail_details[] = "	<td valign='top'>Mailadres</td>";
$mail_details[] = "	<td valign='top'><input type='text' name='mail_afzender' value='".$rooster->van ."'></td>";
$mail_details[] = "</tr>";
$mail_details[] = "<tr>";
$mail_details[] = "	<td valign='top'>Onderwerp</td>";
$mail_details[] = "	<td><input type='text' name='onderwerp_mail' value='".$rooster->onderwerp ."'></td>";
$mail_details[] = "</tr>";
$mail_details[] = "<tr>";
$mail_details[] = "	<td valign='top'>Mailtekst</td>";
$mail_details[] = "	<td valign='top'><textarea name='text_mail'>". $rooster->mail ."</textarea></td>";
$mail_details[] = "</tr>";
$mail_details[] = "</table>";
$mail_details[] = "<p class='after_table'><input type='submit' name='save_mail' value='Mail-gegevens opslaan'></p>";
$mail_details[] = "</form>";

$mail_FAQ[] = "<table>";
$mail_FAQ[] = "<tr>";
$mail_FAQ[] = "	<td valign='top'>[[voornaam]]</td>";
$mail_FAQ[] = "	<td valign='top'>voornaam van de ontvanger.</td>";
$mail_FAQ[] = "</tr>";
$mail_FAQ[] = "<tr>";
$mail_FAQ[] = "	<td valign='top'>[[achternaam]]</td>";
$mail_FAQ[] = "	<td valign='top'>achternaam van de ontvanger.</td>";
$mail_FAQ[] = "</tr>";
$mail_FAQ[] = "<tr>";
$mail_FAQ[] = "	<td valign='top'>[[team]]</td>";
$mail_FAQ[] = "	<td valign='top'>alle namen (uitgezonderd de ontvanger) van wie op het rooster staan.</td>";
$mail_FAQ[] = "</tr>";
$mail_FAQ[] = "<tr>";
$mail_FAQ[] = "	<td valign='top'>[[voorganger]]</td>";
$mail_FAQ[] = "	<td valign='top'>naam van de voorganger.</td>";
$mail_FAQ[] = "</tr>";
$mail_FAQ[] = "<tr>";
$mail_FAQ[] = "	<td valign='top'>[[dag]]</td>";
$mail_FAQ[] = "	<td valign='top'>naam van de dag. Meestal zondag, bij feestdagen meestal andere dag.</td>";
$mail_FAQ[] = "</tr>";
$mail_FAQ[] = "<tr>";
$mail_FAQ[] = "	<td valign='top'>[[dagdeel]]</td>";
$mail_FAQ[] = "	<td valign='top'>naam van het dagdeel (ochtend, middag, avond).</td>";
$mail_FAQ[] = "</tr>";
$mail_FAQ[] = "	<tr>";
$mail_FAQ[] = "	<td valign='top'>[[team|xx]]</td>";
$mail_FAQ[] = "	<td valign='top'>Om namen die voor deze dienst op een ander roosters in te voeren, vervang je XX door het id van dat rooster.</td>";
$mail_FAQ[] = "</tr>";
$mail_FAQ[] = "</table>";


echo showCSSHeader(array('default', 'table'));
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>'. $rooster->naam .'</h1>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $rooster_details).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
if($rooster->tekst == 0) {
	echo '<div class="content_vert_kolom">'.NL;
	echo "<div class='content_block'>".NL. implode(NL, $mail_details).NL."</div>".NL;
	echo "<div class='content_block'>".NL. implode(NL, $mail_FAQ).NL."</div>".NL;
	echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
}
#echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();
?>