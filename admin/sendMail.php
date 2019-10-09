<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
include_once('include/HTML_HeaderFooter.php');
include_once('../../general_include/class.phpmailer.php');
include_once('../../general_include/class.html2text.php');
$requiredUserGroups = array(1);
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

if(isset($_POST['send_mail'])) {
	$lid			= $_POST['ontvanger'];
	$groep		= $_POST['groep'];
	$rooster	= $_POST['rooster'];
	
	$memberData = getMemberDetails($lid);
	$groepData	= getGroupDetails($groep);
	$roosterData	= getRoosterDetails($rooster);
		
	for($i=0 ; $i < 2 ; $i++) {
		if($i==0) {
			$ReplacedBericht = $_POST['text_mail'];
		} else {
			$ReplacedBericht = $_POST['onderwerp_mail'];
		}
		
		$ReplacedBericht = str_replace ('[[voornaam]]', $memberData['voornaam'], $ReplacedBericht);
		$ReplacedBericht = str_replace ('[[achternaam]]', $memberData['achternaam'], $ReplacedBericht);
		$ReplacedBericht = str_replace ('[[hash_kort]]', $memberData['hash_short'], $ReplacedBericht);
		$ReplacedBericht = str_replace ('[[hash_lang]]', $memberData['hash_long'], $ReplacedBericht);
		
		$ReplacedBericht = str_replace ('[[groep]]', $groep, $ReplacedBericht);
		$ReplacedBericht = str_replace ('[[groep-naam]]', $groepData['naam'], $ReplacedBericht);
		$ReplacedBericht = str_replace ('[[rooster]]', $rooster, $ReplacedBericht);
		$ReplacedBericht = str_replace ('[[rooster-naam]]', $roosterData['naam'], $ReplacedBericht);
		
		if($i==0) {								
			$FinalHTMLMail = nl2br($ReplacedBericht);
		} else {
			$FinalSubject = $ReplacedBericht;
		}					
	}
	
	$var['from']			= $_POST['mail_afzender'];
	$var['FromName']	= $_POST['naam_afzender'];
	$var['BCC']				= true;
	$var['BCC_mail']	= 'matthijs@draijer.org';
	
	if(sendMail($lid, $FinalSubject, $FinalHTMLMail, $var)) {
		toLog('debug', '', $lid, "Mail met als onderwerp '$FinalSubject' verstuurd");
	} else {
		toLog('error', '', $lid, "Problemen met versturen mail met onderwerp '$FinalSubject'");
	}
	
	//echo $FinalHTMLMail;
} else {
	$leden = getMembers();
	$groepen = getAllGroups();
	$roosters = getRoosters(0);
	
	$block[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$block[] = "<table>";
	$block[] = "<tr>";
	$block[] = "	<td valign='top'>Ontvanger</td>";
	$block[] = "	<td valign='top' colspan='2'><select name='ontvanger'>";
	foreach($leden as $lid)	$block[] = "<option value='$lid'>". makeName($lid, 8) ."</option>";	
	$block[] = "	</select></td>";
	$block[] = "</tr>";
	$block[] = "<tr>";
	$block[] = "	<td valign='top'>Afzendernaam</td>";
	$block[] = "	<td valign='top' colspan='2'><input type='text' name='naam_afzender' size=80 value='$ScriptTitle'></td>";
	$block[] = "</tr>";
	$block[] = "<tr>";
	$block[] = "	<td valign='top'>Afzenderadres</td>";
	$block[] = "	<td valign='top' colspan='2'><input type='text' name='mail_afzender' size=80 value='$ScriptMailAdress'></td>";
	$block[] = "</tr>";
	$block[] = "<tr>";
	$block[] = "	<td valign='top'>Onderwerp</td>";
	$block[] = "	<td valign='top' colspan='2'><input type='text' name='onderwerp_mail' size=80 value=''></td>";
	$block[] = "</tr>";
	
	$block[] = "<tr>";
	$block[] = "	<td valign='top'>Team</td>";
	$block[] = "	<td valign='top' colspan='2'><select name='groep'>";
	$block[] = "	<option value='0'> [ geen groep ] </option>";
	foreach($groepen as $groep) {
		$data = getGroupDetails($groep);
		$block[] = "	<option value='$groep'>".$data['naam']."</option>";	
	}
	$block[] = "	</select></td>";
	$block[] = "</tr>";
	$block[] = "<tr>";
	$block[] = "	<td valign='top'>Rooster</td>";
	$block[] = "	<td valign='top' colspan='2'><select name='rooster'>";
	$block[] = "	<option value='0'> [ geen rooster ] </option>";
	foreach($roosters as $rooster) {
		$data = getRoosterDetails($rooster);
		$block[] = "<option value='$rooster'>".$data['naam']."</option>";	
	}
	$block[] = "	</select></td>";
	$block[] = "</tr>";
	
	$block[] = "<tr>";
	$block[] = "	<td valign='top'>Mailtekst</td>";
	$block[] = "	<td valign='top'><textarea name='text_mail' rows=20 cols=60></textarea></td>";
	$block[] = "	<td valign='top'>[[voornaam]] = voornaam van de ontvanger<br>";
	$block[] = "	[[achternaam]] = achternaam van de ontvanger<br>";
	$block[] = "	[[hash_kort]] = korte hash<br>";
	$block[] = "	[[hash_lang]] = lange hash<br>";
	$block[] = "	[[groep]] = id van de gekozen groep<br>";
	$block[] = "	[[groep-naam]] = naam van de gekozen groep<br>";
	$block[] = "	[[rooster]] = id van het gekozen rooster<br>";
	$block[] = "	[[rooster-naam]] = naam van het gekozen rooster</td>";
	$block[] = "</tr>";
	$block[] = "<tr>";
	$block[] = "	<td valign='top'>&nbsp;</td><td valign='top' colspan='2'><input type='submit' name='send_mail' value='Mail versturen'></td>";
	$block[] = "</tr>";
	$block[] = "</table>";
	$block[] = "</form>";
		
	$voorbeeld[] = "Dag [[voornaam]]";
	$voorbeeld[] = "";
	$voorbeeld[] = "heb eea ingericht zodat jij zelf het rooster kunt invullen, aanpassen en up-to-date houden.";
	$voorbeeld[] = "";
	$voorbeeld[] = htmlentities("Om te beginnen heb je inloggegevens nodig. Weet niet of je die al hebt, maar anders kan je die zelf kiezen via <a href='https://www.draijer.org/extern/3GK/intranet/account.php?hash=[[hash_lang]]'>deze pagina</a>. Dit is een link die alleen voor jou is, iedereen met deze link kan jouw inloggegevens wijzigen.");
	$voorbeeld[] = "";
	$voorbeeld[] = htmlentities("Met deze inloggegevens kan je naar <a href='https://www.draijer.org/extern/3GK/intranet'>de startpagina</a> gaan. Je hebt daar links het kopje <b>Teams die ik beheer</b> met daaronder als het goed is <a href='https://www.draijer.org/extern/3GK/intranet/editGroup.php?groep=[[groep]]'>[[groep-naam]]</a>. Hier heb je een overzicht van iedereen die in [[groep-naam]] zit. Door een vinkje voor een naam weg te halen verdwijnt iemand uit de groep en door in de balk onder de namen een naam in te voeren kan je leden toevoegen.");
	$voorbeeld[] = "";
	$voorbeeld[] = htmlentities("Als dat allemaal goed is kan je op <a href='https://www.draijer.org/extern/3GK/intranet'>de startpagina</a> onder <b>Roosters die ik beheer</b> op <a href='https://www.draijer.org/extern/3GK/intranet/makeRooster.php?rooster=[[rooster]]'>[[rooster-naam]]</a> klikken om het rooster in te vullen.");
	$voorbeeld[] = "";
	$voorbeeld[] = htmlentities("Als het rooster eenmaal helemaal is ingevuld is deze zichtbaar op <a href='https://www.draijer.org/extern/3GK/intranet'>de startpagina</a> onder <b>Roosters</b>. Hier staan alle roosters zoals die momenteel bekend zijn. Door op <a href='https://www.draijer.org/extern/3GK/intranet/showRooster.php?rooster=[[rooster]]'>[[rooster-naam]]</a> te klikken wordt het rooster getoond.");
	$voorbeeld[] = "";
	$voorbeeld[] = "Mocht je vragen hebben dan hoor ik het graag.";
	$voorbeeld[] = "";
	$voorbeeld[] = "Groet,";
	$voorbeeld[] = "Matthijs";
	
	echo $HTMLHeader;
	echo showBlock(implode(NL, $block), 100);
	echo '<p>';
	echo showBlock(implode('<br>'.NL, $voorbeeld), 100);
	echo $HTMLFooter;	
}

?>