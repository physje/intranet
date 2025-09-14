<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$showLogin = true;

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('error', '', 'ongeldige hash (agenda)');
		$showLogin = true;
	} else {
		$showLogin = false;
		session_start(['cookie_lifetime' => $cookie_lifetime]);
		$_SESSION['useID'] = $id;
		$_SESSION['realID'] = $id;
		toLog('info', '', 'agenda mbv hash');
	}
}

if($showLogin) {
	$cfgProgDir = '../auth/';
	include($cfgProgDir. "secure.php");
}

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
	$startTijd = mktime($_POST['sUur'], $_POST['sMin'], 0, $_POST['Maand'], $_POST['Dag'], $_POST['Jaar']);
	$eindTijd = mktime($_POST['eUur'], $_POST['eMin'], 0, $_POST['Maand'], $_POST['Dag'], $_POST['Jaar']);
	
	if(isset($_POST['id'])) {
		$query = "UPDATE $TableAgenda SET $AgendaStart = '$startTijd', $AgendaEind = '$eindTijd', $AgendaTitel = '". urlencode($_POST['titel']) ."', $AgendaDescr = '". urlencode($_POST['omschrijving']) ."', $AgendaOwner = '". $_POST['eigenaar'] ."' WHERE $AgendaID like ". $_POST['id'];
				
		if(mysqli_query($db, $query)) {
			$text[] = "De afspraak '". $_POST['titel'] ."' [". $_POST['id'] ."] is opgeslagen";
			toLog('info', '', "Agenda-item '". $_POST['titel'] ."' gewijzigd");
		} else {
			$text[] = "Het opslaan van de afspraak is niet gelukt.";
			toLog('error', '', "Kan agenda-item '". $_POST['titel'] ."' [". $_POST['id'] ."] niet wijzigen");
		}
		$text[] = "<p>";
		$text[] = "<a href='". $_SERVER['PHP_SELF'] ."'>Terug naar het overzicht</a>.";
	} else {
		$query = "INSERT INTO $TableAgenda ($AgendaStart, $AgendaEind, $AgendaTitel, $AgendaDescr, $AgendaOwner) VALUES ('$startTijd', '$eindTijd', '". urlencode($_POST['titel']) ."', '". urlencode($_POST['omschrijving']) ."', ". $_SESSION['useID'] .")";
		
		if(mysqli_query($db, $query)) {			
			$newID = mysqli_insert_id($db);
			$UserData = getMemberDetails($_SESSION['useID']);
						
			$mail[] = "Beste ". $UserData['voornaam'];
			$mail[] = "";
			$mail[] = "Je hebt zojuist een agenda-item aangemaakt.";
			$mail[] = "";
			$mail[] = "De volgende gegevens zijn daarbij opgeslagen :";
			$mail[] = "Titel: ". $_POST['titel'];
			$mail[] = "Beschrijving: ". $_POST['omschrijving'];
			$mail[] = "Datum : ". time2str("%A %d %B", $startTijd);
			$mail[] = "Tijd: ". time2str("%H:%M", $startTijd) ." tot ". time2str("%H:%M", $eindTijd);
			$mail[] = "";
			$mail[] = "Om deze afspraak te beheren kan je <a href='". $ScriptURL ."agenda/agenda.php?id=". $newID ."&hash=". $UserData['hash_long'] ."'>deze link</a> gebruiken, daarmee kom je direct weer bij deze afspraak terecht";
			
			$text[] = "De afspraak '". $_POST['titel'] ."' is toegevoegd.<br>";
			toLog('info', '', "Agenda-item '". $_POST['titel'] ."' toegevoegd");
						
			$param['to'][]			= array($_SESSION['useID']);
			$param['message']		= implode("<br>\n", $mail);
			$param['subject']		= "De afspraak '". $_POST['titel'] ."'";			
						
			if(sendMail_new($param)) {
				$text[] = "Je hebt een bevestigingsmail ontvangen.";
				toLog('debug', '', "Bevestigingsmail voor '". $_POST['titel'] ."' [$newID] verstuurd");
			} else {
				$text[] = "Het versturen van een bevestigingsmail is helaas mislukt.<br>";
				toLog('error', '', "Kan geen bevestigingsmail voor '". $_POST['titel'] ."' [$newID] versturen");
			}			
		} else {
			$text[] = "Het opslaan van de afspraak is niet gelukt.";
			toLog('error', '', "Kan agenda-item '". $_POST['titel'] ."' niet opslaan");
		}
	}	
} elseif(isset($_REQUEST['id']) OR isset($_REQUEST['new'])) {
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
		
	if(isset($_REQUEST['id'])) {		
		$details = getAgendaDetails($_REQUEST['id']);
		
		$Dag		= date("d", $details['start']);
		$Maand	= date("m", $details['start']);
		$Jaar		= date("Y", $details['start']);
		$sUur		= date("H", $details['start']);
		$sMin		= date("i", $details['start']);
		$eUur		= date("H", $details['eind']);
		$eMin		= date("i", $details['eind']);
		$titel	= $details['titel'];
		$descr	= $details['descr'];		
		$text[] = "<input type='hidden' name='id' value='". $_REQUEST['id'] ."'>";		
	} else {
		$Dag		= getParam('Dag', date("d"));
		$Maand	= getParam('Maand', date("m"));
		$Jaar		= getParam('Jaar', date("Y"));
		
		$sUur		= getParam('sUur', date("H"));
		$sMin		= getParam('sMin', date("i"));
		$eUur		= getParam('eUur', date("H", time()+3600));
		$eMin		= getParam('eMin', date("i"));
		
		$titel = $descr = '';
	}
	
	if(!in_array(1, getMyGroups($_SESSION['useID'])) AND isset($details['eigenaar']) AND $details['eigenaar'] != $_SESSION['useID'] AND !isset($_REQUEST['new'])) {
		$text = array('Toestemmingsprobleem, dit id hoort niet bij een afspraak van jou');
	} else {	
		$text[] = "<table>";
		
		if(!in_array(1, getMyGroups($_SESSION['useID'])) AND isset($_REQUEST['id'])) {
			$text[] = "<input type='hidden' name='eigenaar' value='". $details['eigenaar'] ."'>";
		} elseif(isset($_REQUEST['id'])) {
			//$leden =
			$text[] = "<tr>";
			$text[] = "	<td>Eigenaar</td>";
			$text[] = "	<td colspan=2><select name='eigenaar'>";			
			$users =  getMembers('volwassen');
			foreach($users as $userID)	$text[] = "	<option value='$userID'". ($details['eigenaar'] == $userID ? ' selected' : '') .">". makeName($userID, 8) ."</option>";
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
	if(!in_array(1, getMyGroups($_SESSION['useID']))) {	
		$userID = $_SESSION['useID'];
	} else {
		$userID = 'all';
	}
	
	$maandGeleden = mktime(0,0,0,(date("n")-1));
	$agendaIDS = getAgendaItems($userID, $maandGeleden);
	
	if(count($agendaIDS) > 0) {		
		foreach($agendaIDS as $agendaID) {
			$details = getAgendaDetails($agendaID);
			$text[] = date("d-m-Y", $details['start']). " <a href='". $ScriptURL ."agenda/agenda.php?id=$agendaID'>". $details['titel'] ."</a>". (in_array(1, getMyGroups($_SESSION['useID'])) ? ' ('. makeName($details['eigenaar'], 5) .')' : '')."<br>";
		}
		
		$text[] = "<p>";
		$text[] = "<a href='?new'>Voeg 1 nieuwe afspraak toe</a> | <a href='insert.php'>Voeg meerdere afspraken in 1 keer toe</a>";
	} else {		
		$text[] = "Er zijn nog geen recente afspraken door jou ingevoerd. Klik <a href='?new'>hier</a> om (weer) je eerste afspraak toe te voegen.<br>";
		$text[] = "Wil je in een keer meerdere afspraken inladen, klik dan <a href='insert.php'>hier</a>.<br>";
		$text[] = "Afspraken die je hier invoert komen automatisch in de Scipio-agenda te staan<br>";
	}
}

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;


?>