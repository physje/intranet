<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/KKDMailer.php');
include_once('../Classes/Member.php');
include_once('../Classes/Rooster.php');
include_once('../Classes/Team.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Mysql.php');

$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_POST['send_mail'])) {
	$lid		= $_POST['ontvanger'];
	$groep		= $_POST['groep'];
	$roosterID	= $_POST['rooster'];
	
	$ontvanger  = new Member($lid);
	$team       = new Team($groep);
	$rooster	= new Rooster($roosterID);
		
	for($i=0 ; $i < 2 ; $i++) {
		if($i==0) {
			$ReplacedBericht = $_POST['text_mail'];
		} else {
			$ReplacedBericht = $_POST['onderwerp_mail'];
		}
		
		$ReplacedBericht = str_replace ('[[voornaam]]', $ontvanger->voornaam, $ReplacedBericht);
		$ReplacedBericht = str_replace ('[[achternaam]]', $ontvanger->achternaam, $ReplacedBericht);
		$ReplacedBericht = str_replace ('[[hash_kort]]', $ontvanger->hash_short, $ReplacedBericht);
		$ReplacedBericht = str_replace ('[[hash_lang]]', $ontvanger->hash_long, $ReplacedBericht);		
		$ReplacedBericht = str_replace ('[[groep]]', $team->id, $ReplacedBericht);
		$ReplacedBericht = str_replace ('[[groep-naam]]', $team->name, $ReplacedBericht);
		$ReplacedBericht = str_replace ('[[rooster]]', $rooster->id, $ReplacedBericht);
		$ReplacedBericht = str_replace ('[[rooster-naam]]', $rooster->naam, $ReplacedBericht);
		
		if($i==0) {								
			$FinalHTMLMail = nl2br($ReplacedBericht);
		} else {
			$FinalSubject = $ReplacedBericht;
		}					
	}
	
    $mail = new KKDMailer();
    $mail->aan      = $gebruiker->id;
    $mail->Body     = $FinalHTMLMail;
    $mail->Subject  = $FinalSubject;
    $mail->From     = $_POST['mail_afzender'];
    $mail->FromName = $_POST['naam_afzender'];
    if(!$productieOmgeving)	$mail->testen = true;
			
	if($mail->sendMail()) {
		toLog("Mail met als onderwerp '$FinalSubject' verstuurd", 'debug', $ontvanger->id);
	} else {
		toLog("Problemen met versturen mail met onderwerp '$FinalSubject'", 'error', $ontvanger->id);
	}	
} else {
	$leden      = Member::getMembers();
	$groepen    = Team::getAllTeams();
	$roosters   = Rooster::getAllRoosters();
	
	$block[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$block[] = "<table>";
	$block[] = "<tr>";
	$block[] = "	<td>Ontvanger</td>";
	$block[] = "	<td><select name='ontvanger'>";
	foreach($leden as $id) {
        $lid = new Member($id);
        $block[] = "<option value='". $lid->id ."'>". $lid->getName(8) ."</option>";	
    }	
	$block[] = "	</select></td>";
	$block[] = "</tr>";
	$block[] = "<tr>";
	$block[] = "	<td>Afzendernaam</td>";
	$block[] = "	<td><input type='text' name='naam_afzender' size=80 value='$ScriptTitle'></td>";
	$block[] = "</tr>";
	$block[] = "<tr>";
	$block[] = "	<td>Afzenderadres</td>";
	$block[] = "	<td><input type='text' name='mail_afzender' size=80 value='$ScriptMailAdress'></td>";
	$block[] = "</tr>";
	$block[] = "<tr>";
	$block[] = "	<td>Onderwerp</td>";
	$block[] = "	<td><input type='text' name='onderwerp_mail' size=80 value=''></td>";
	$block[] = "</tr>";
	
	$block[] = "<tr>";
	$block[] = "	<td>Team</td>";
	$block[] = "	<td><select name='groep'>";
	$block[] = "	<option value='0'> [ geen groep ] </option>";
	foreach($groepen as $id) {
		$team = new Team($id);
		$block[] = "	<option value='". $team->id ."'>". $team->name ."</option>";	
	}
	$block[] = "	</select></td>";
	$block[] = "</tr>";
	$block[] = "<tr>";
	$block[] = "	<td>Rooster</td>";
	$block[] = "	<td><select name='rooster'>";
	$block[] = "	<option value='0'> [ geen rooster ] </option>";
	foreach($roosters as $id) {
		$rooster = new Rooster($id);
		$block[] = "<option value='". $rooster->id ."'>". $rooster->naam ."</option>";	
	}
	$block[] = "	</select></td>";
	$block[] = "</tr>";	
	$block[] = "<tr>";
	$block[] = "	<td>Mailtekst</td>";
	$block[] = "	<td><textarea name='text_mail' rows=20 cols=60></textarea></td>";	
	$block[] = "</tr>";
	$block[] = "<tr>";
	$block[] = "	<td colspan='2'><input type='submit' name='send_mail' value='Mail versturen'></td>";
	$block[] = "</tr>";
	$block[] = "</table>";
	$block[] = "</form>";
	
	$uitleg[] = "<table>";
	$uitleg[] = "<tr>";
	$uitleg[] = "	<td>[[voornaam]]</td>";
	$uitleg[] = "	<td>voornaam van de ontvanger</td>";
	$uitleg[] = "</tr>";
	$uitleg[] = "<tr>";	
	$uitleg[] = "	<td>[[achternaam]]</td>";
	$uitleg[] = "	<td>achternaam van de ontvanger</td>";
	$uitleg[] = "</tr>";
	$uitleg[] = "<tr>";
	$uitleg[] = "	<td>[[hash_kort]]</td>";
	$uitleg[] = "	<td>korte hash</td>";
	$uitleg[] = "</tr>";
	$uitleg[] = "<tr>";
	$uitleg[] = "	<td>[[hash_lang]]</td>";
	$uitleg[] = "	<td>lange hash</td>";
	$uitleg[] = "</tr>";
	$uitleg[] = "<tr>";
	$uitleg[] = "	<td>[[groep]]</td>";
	$uitleg[] = "	<td>id van de gekozen groep</td>";
	$uitleg[] = "</tr>";
	$uitleg[] = "<tr>";
	$uitleg[] = "	<td>[[groep-naam]]</td>";
	$uitleg[] = "	<td>naam van de gekozen groep</td>";
	$uitleg[] = "</tr>";
	$uitleg[] = "<tr>";
	$uitleg[] = "	<td>[[rooster]]</td>";
	$uitleg[] = "	<td>id van het gekozen rooster</td>";
	$uitleg[] = "</tr>";
	$uitleg[] = "<tr>";
	$uitleg[] = "	<td>[[rooster-naam]]</td>";
	$uitleg[] = "	<td>naam van het gekozen rooster</td>";
	$uitleg[] = "</tr>";
	$uitleg[] = "</table>";
		
	$voorbeeld[] = "Dag [[voornaam]]";
	$voorbeeld[] = "";
	$voorbeeld[] = "heb eea ingericht zodat jij zelf het rooster kunt invullen, aanpassen en up-to-date houden.";
	$voorbeeld[] = "";
	$voorbeeld[] = htmlentities("Om te beginnen heb je inloggegevens nodig. Weet niet of je die al hebt, maar anders kan je die zelf kiezen via <a href='".$ScriptURL."account.php?hash=[[hash_lang]]'>deze pagina</a>. Dit is een link die alleen voor jou is, iedereen met deze link kan jouw inloggegevens wijzigen.");
	$voorbeeld[] = "";
	$voorbeeld[] = htmlentities("Met deze inloggegevens kan je naar <a href='".$ScriptURL."'>de startpagina</a> gaan. Je hebt daar links het kopje <b>Teams die ik beheer</b> met daaronder als het goed is <a href='".$ScriptURL."editGroup.php?groep=[[groep]]'>[[groep-naam]]</a>. Hier heb je een overzicht van iedereen die in [[groep-naam]] zit. Door een vinkje voor een naam weg te halen verdwijnt iemand uit de groep en door in de balk onder de namen een naam in te voeren kan je leden toevoegen.");
	$voorbeeld[] = "";
	$voorbeeld[] = htmlentities("Als dat allemaal goed is kan je op <a href='".$ScriptURL."'>de startpagina</a> onder <b>Roosters die ik beheer</b> op <a href='".$ScriptURL."makeRooster.php?rooster=[[rooster]]'>[[rooster-naam]]</a> klikken om het rooster in te vullen.");
	$voorbeeld[] = "";
	$voorbeeld[] = htmlentities("Als het rooster eenmaal helemaal is ingevuld is deze zichtbaar op <a href='".$ScriptURL."'>de startpagina</a> onder <b>Roosters</b>. Hier staan alle roosters zoals die momenteel bekend zijn. Door op <a href='".$ScriptURL."showRooster.php?rooster=[[rooster]]'>[[rooster-naam]]</a> te klikken wordt het rooster getoond.");
	$voorbeeld[] = "";
	$voorbeeld[] = "Mocht je vragen hebben dan hoor ik het graag.";
	$voorbeeld[] = "";
	$voorbeeld[] = "Groet,";
	$voorbeeld[] = "Matthijs";
	
	echo showCSSHeader();
	echo "<div class='content_horz_kolom'>".NL."<div class='content_block'>".NL. implode(NL, $block).NL."</div>".NL."</div>".NL;
	echo "<div class='content_horz_kolom'>".NL."<div class='content_block'>".NL. implode(NL, $uitleg).NL."</div>".NL."</div>".NL;
	echo "</div><div class='row'>";
	echo "<div class='content_horz_kolom_full'>".NL."<div class='content_block'>".NL. implode('<br>'.NL, $voorbeeld).NL."</div>".NL."</div>".NL;
	echo showCSSFooter();
}

?>
