<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Agenda.php');
include_once('../Classes/Logging.php');
include_once('../Classes/KKDMailer.php');

$showLogin = true;
$text = $footer = array();

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('ongeldige hash (agenda)', 'error');
		$showLogin = true;
	} else {
		$showLogin = false;
		session_start(['cookie_lifetime' => $cookie_lifetime]);
		$_SESSION['useID'] = $id;
		$_SESSION['realID'] = $id;
		toLog('agenda mbv hash');
	}
}

if($showLogin) {
	$cfgProgDir = '../auth/';
	include($cfgProgDir. "secure.php");
}

$gebruiker = new Member($_SESSION['useID']);
$myTeams = $gebruiker->getTeams();

if(isset($_POST['remove'])) {
	$query = "DELETE FROM $TableAgenda WHERE $AgendaID like ". $_POST['id'];
	
	if(mysqli_query($db, $query)) {
		$text[] = "De afspraak '". $_POST['titel'] ."' is verwijderd";
		toLog('info', '', "Agenda-item '". $_POST['titel'] ."' [". $_POST['id'] ."] verwijderd");
	} else {
		$text[] = "Het verwijderen van '". $_POST['titel'] ."' is niet gelukt.";
		toLog('error', '', "Kan agenda-item '". $_POST['titel'] ."' [". $_POST['id'] ."] niet verwijderen");
	}
} elseif(isset($_POST['save'])) {
	if(isset($_POST['id'])) {
		$agenda = new Agenda($_POST['id']);
		$agenda->eigenaar = $_POST['eigenaar'];
		$new = false;
	} else {
		$agenda = new Agenda();
		$agenda->eigenaar = $gebruiker->id;
		$new = true;
	}	
	
	$agenda->start	= mktime($_POST['sUur'], $_POST['sMin'], 0, $_POST['Maand'], $_POST['Dag'], $_POST['Jaar']);
	$agenda->eind	= mktime($_POST['eUur'], $_POST['eMin'], 0, $_POST['Maand'], $_POST['Dag'], $_POST['Jaar']);
	$agenda->titel	= $_POST['titel'];
	$agenda->beschrijving = $_POST['omschrijving'];	
	
	if($new) {
		if($newID = $agenda->save()) {
			$text[] = "De afspraak '". $_POST['titel'] ."' is toegevoegd";
			toLog("Agenda-item '". $_POST['titel'] ."' toegevoegd");
		} else {
			$text[] = "Het toevoegen van de afspraak is niet gelukt.";
			toLog("Kan agenda-item '". $_POST['titel'] ."' niet toevoegen", 'error');
		}
	} else {
		if($agenda->save()) {
			$text[] = "De afspraak '". $_POST['titel'] ."' is opgeslagen";
			toLog("Agenda-item '". $_POST['titel'] ."' gewijzigd");
		} else {
			$text[] = "Het opslaan van de afspraak is niet gelukt.";
			toLog("Kan agenda-item '". $_POST['titel'] ."' [". $_POST['id'] ."] niet wijzigen", 'error');
		}
	}

	$text[] = "<p>";
	$text[] = "<a href='". $_SERVER['PHP_SELF'] ."'>Terug naar het overzicht</a>.";

	if($new) {
		$body = array();
		$body[] = "Beste ". $gebruiker->getName(1);
		$body[] = "";
		$body[] = "Je hebt zojuist een agenda-item aangemaakt.";
		$body[] = "";
		$body[] = "De volgende gegevens zijn daarbij opgeslagen :";
		$body[] = "Titel: ". $agenda->titel;
		$body[] = "Beschrijving: ". $agenda->beschrijving;
		$body[] = "Datum : ". time2str("l j F Y", $agenda->start);
		$body[] = "Tijd: ". time2str("H:i", $agenda->start) ." tot ". time2str("H:i", $agenda->eind);
		$body[] = "";
		$body[] = "Om deze afspraak te beheren kan je <a href='". $ScriptURL ."agenda/agenda.php?id=". $newID ."&hash=". $gebruiker->hash_long ."'>deze link</a> gebruiken, daarmee kom je direct weer bij deze afspraak terecht.";
		$body[] = "Mocht je deze mail later niet meer terug kunnen vinden, via <a href='". $ScriptURL ."agenda/agenda.php'>Agenda voor Scipio</a> op het intranet kan je ook weer bij deze afspraak komen.";

		$mail = new KKDMailer();
		$mail->aan = $gebruiker->id;
		$mail->Body = implode("<br>\n", $body);
		$mail->Subject = "De afspraak '". $_POST['titel'] ."'";
		//TODO: verwijderen
		$mail->testen = true;

		if($mail->sendMail()) {
			$text[] = "Je hebt een bevestigingsmail ontvangen.";
			toLog("Bevestigingsmail voor '". $_POST['titel'] ."' [$newID] verstuurd", 'debug');
		} else {
			$text[] = "Het versturen van een bevestigingsmail is helaas mislukt.<br>";
			toLog("Kan geen bevestigingsmail voor '". $_POST['titel'] ."' [$newID] versturen", 'error');
		}
	}	
} elseif(isset($_REQUEST['id']) OR isset($_REQUEST['new'])) {
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
		
	if(isset($_REQUEST['id'])) {		
		$agenda = new Agenda($_REQUEST['id']);
		
		$Dag	= date("d", $agenda->start);
		$Maand	= date("m", $agenda->start);
		$Jaar	= date("Y", $agenda->start);
		$sUur	= date("H", $agenda->start);
		$sMin	= date("i", $agenda->start);
		$eUur	= date("H", $agenda->eind);
		$eMin	= date("i", $agenda->eind);
		$titel	= $agenda->titel;
		$descr	= $agenda->beschrijving;
		$text[] = "<input type='hidden' name='id' value='". $_REQUEST['id'] ."'>";		
	} else {
		$Dag	= getParam('Dag', date("d"));
		$Maand	= getParam('Maand', date("m"));
		$Jaar	= getParam('Jaar', date("Y"));
		
		$sUur	= getParam('sUur', date("H"));
		$sMin	= getParam('sMin', date("i"));
		$eUur	= getParam('eUur', date("H", time()+3600));
		$eMin	= getParam('eMin', date("i"));
		
		$titel = $descr = '';
	}
	
	
	if(!in_array(1, $myTeams) && isset($agenda->eigenaar) && $agenda->eigenaar != $_SESSION['useID'] && !isset($_REQUEST['new'])) {
		$text = array('Toestemmingsprobleem, dit id hoort niet bij een afspraak van jou');
	} else {	
		$text[] = "<table>";
		
		if(!in_array(1,$myTeams) && isset($_REQUEST['id'])) {
			$text[] = "<input type='hidden' name='eigenaar' value='". $agenda->eigenaar ."'>";
		} elseif(isset($_REQUEST['id'])) {			
			$text[] = "<tr>";
			$text[] = "	<td>Eigenaar</td>";
			$text[] = "	<td colspan=2><select name='eigenaar'>";			
			$users = Member::getMembers('volwassen');
			foreach($users as $userID) {
				$user = new Member($userID);
				$text[] = "	<option value='$userID'". ($agenda->eigenaar == $userID ? ' selected' : '') .">". $user->getName(8) ."</option>";
			}	
			$text[] = "	</select></td>";
			$text[] = "</tr>";
		}
		
		$text[] = "<tr>";
		$text[] = "	<td>Datum</td>";
		$text[] = "	<td colspan=2><select name='Dag'>";
		for($d=1 ; $d<32 ; $d++) {
			$text[] = "	<option value='$d'". ($d == $Dag ? ' selected' : '') .">$d</option>";
		}
		$text[] = "	</select> ";
		$text[] = "	<select name='Maand'>";
		for($m=1 ; $m<13 ; $m++) {
			$text[] = "	<option value='$m'". ($m == $Maand ? ' selected' : '') .">". $maandArray[$m] ."</option>";
		}
		$text[] = "	</select> ";
		$text[] = "	<select name='Jaar'>";
		for($j=date("Y"); $j<=(date("Y")+10) ; $j++) {
			$text[] = "	<option value='$j'". ($j == $Jaar ? ' selected' : '') .">$j</option>";
		}
		$text[] = "	</select></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Starttijd</td>";
		$text[] = "	<td colspan=2><select name='sUur'>";
		for($u=0; $u<24 ; $u++) {
			$text[] = "	<option value='$u'". ($u == $sUur ? ' selected' : '') .">$u</option>";
		}
		$text[] = "	</select>";
		$text[] = "	<select name='sMin'>";
		for($m=0; $m<60 ; $m=$m+5) {
			$text[] = "	<option value='$m'". ($m == $sMin ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>";
		}
		$text[] = "	</select></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Eindtijd</td>";
		$text[] = "	<td colspan=2><select name='eUur'>";
		for($u=0; $u<24 ; $u++) {
			$text[] = "	<option value='$u'". ($u == $eUur ? ' selected' : '') .">$u</option>";
		}
		$text[] = "	</select>";
		$text[] = "	<select name='eMin'>";
		for($m=0; $m<60 ; $m=$m+5) {
			$text[] = "	<option value='$m'". ($m == $eMin ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>";
		}
		$text[] = "	</select></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Titel</td>";
		$text[] = "	<td colspan=2><input type='text' name='titel' value='$titel' size='50' placeholder='Titel van de afspraak'></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>Omschrijving</td>";
		$text[] = "	<td colspan=2><textarea name='omschrijving' rows=15 cols=40>$descr</textarea></td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td>&nbsp;</td>";
		$text[] = "	<td><input type='submit' name='save' value='Opslaan'></td>";
		$text[] = "	<td align='right'><input type='submit' name='remove' value='Verwijderen'></td>";
		$text[] = "</tr>";	
		$text[] = "</table>";
		$text[] = "</form>";
	}
} else {
	# Als je niet voorkomt in de Admin-groep dan ga je naar je eigen gegevens
	if(!in_array(1, $myTeams)) {	
		$userID = $_SESSION['useID'];
	} else {
		$userID = 0;
	}
	
	# Van 1 maand geleden tot 3 jaar vooruit
	$start	= mktime(0,0,0,(date("n")-1));
	$eind	= mktime(0,0,0,12, 31, (date("Y")+3));
	$agendaIDS = Agenda::getAgendaItems($start, $eind, $userID);
	
	if(count($agendaIDS) > 0) {		
		foreach($agendaIDS as $agendaID) {
			$agenda = new Agenda($agendaID);			
			$eigenaar = new Member($agenda->eigenaar);
			$text[] = date("d-m-Y", $agenda->start). " <a href='". $ScriptURL ."agenda/agenda.php?id=$agendaID'>". $agenda->titel ."</a>". (in_array(1, $myTeams) ? ' ('. $eigenaar->getName(5) .')' : '')."<br>";
		}
		
		#$footer[] = "<p>";
		$footer[] = "<a href='?new'>Voeg 1 nieuwe afspraak toe</a> | <a href='insert.php'>Voeg meerdere afspraken in 1 keer toe</a>";
	} else {		
		$text[] = "Er zijn nog geen recente afspraken door jou ingevoerd. Klik <a href='?new'>hier</a> om (weer) je eerste afspraak toe te voegen.<br>";
		$text[] = "Wil je in een keer meerdere afspraken inladen, klik dan <a href='insert.php'>hier</a>.<br>";
		$text[] = "Afspraken die je hier invoert komen automatisch in de Scipio-agenda te staan<br>";
	}
}

echo showCSSHeader(array('default', 'table_default'));
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
if(count($footer) > 0) {
	echo "<div class='content_block'>".NL. implode(NL, $footer).NL."</div>".NL;
}
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();


?>