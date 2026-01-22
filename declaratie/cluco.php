<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Team.php');
include_once('../Classes/Declaratie.php');
include_once('../Classes/Member.php');
include_once('../Classes/KKDMailer.php');
include_once('../Classes/Logging.php');

//include_once('genereerDeclaratiePdf.php');

$showLogin = true;

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('ongeldige hash (cluco declaratie)', 'error');
		$showLogin = true;
	} else {
		$showLogin = false;
		session_start(['cookie_lifetime' => $cookie_lifetime]);
		$_SESSION['useID'] = $id;
		$_SESSION['realID'] = $id;
		toLog('Cluco-declaratie mbv hash');
	}
}

if($showLogin) {
	$cfgProgDir = '../auth/';
	include($cfgProgDir. "secure.php");
}

# Kijk of er een sessie actief is, zo niet start de sessie
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

# Kijk of er een declaratie-object in de sessie staat en laad die dan
if(!isset($_SESSION['declaratie'])) {	
	$declaratie = new Declaratie();
	$_SESSION['declaratie'] = $declaratie;
}
$declaratie = $_SESSION['declaratie'];

# Haal gegevens van een bestaande declaratie op
# Kan gebruikt worden als een declaratie terug gaat naar het gemeentelid
if(isset($_REQUEST['key'])) {
	$declaratie = new Declaratie($_REQUEST['key']);

# Reset de declaratie als daar om gevraagd wordt
} elseif(isset($_REQUEST['reset'])) {
	$declaratie = new Declaratie();
}

$adminTeam = new Team(1);
$penningmeesterTeam = new Team(38);

$toegestaan = array_merge($clusterCoordinatoren, $adminTeam->leden, $penningmeesterTeam->leden);

if(in_array($_SESSION['useID'], $toegestaan)) {	
	if($declaratie->hash != '') {
		$onderwerpen = array();		
		$indiener	= new Member($declaratie->gebruiker);
		$cluco		= new Member($_SESSION['useID']);
		
		if(count($declaratie->overigeKosten) > 0)	$onderwerpen = array_merge($onderwerpen, array_keys($declaratie->overigeKosten));		
		if($declaratie->reiskosten > 0)				$onderwerpen = array_merge($onderwerpen, array('reiskosten'));
		if(isset($_POST['opm_penning']))			$declaratie->opmerking = trim($_POST['opm_penning']);
		if(isset($_POST['afwijzing']))				$declaratie->opmerking = trim($_POST['afwijzing']);
		if(isset($_POST['information']))			$declaratie->opmerking = trim($_POST['information']);
						
		if(isset($_REQUEST['accept'])) {			
			if(isset($_REQUEST['send_accept'])) {
				# Mail naar gemeentelid				
				$mail = array();
				$mail[] = "Beste ". $indiener->getName(1) .",<br>";
				$mail[] = "<br>";
				$mail[] = "Je declaratie van ".time2str('d LLLL', $declaratie->tijd) ." voor <i>". makeOpsomming($onderwerpen, '</i>, <i>', '</i> en <i>') ."</i> ter waarde van ". formatPrice($declaratie->totaal) ." is door ". $cluco->getName(5) ." als cluster-coordinator goedgekeurd en doorgestuurd naar de penningmeester voor verdere afhandeling.<br>";
				$mail[] = "Mocht deze laatste nog vragen hebben dan neemt hij contact met je op.<br>";

				$gem = new KKDMailer();
				$gem->aan = $indiener->id;
				$gem->Subject	= "Declaratie van ". time2str('d LLLL', $declaratie->tijd) ." doorgestuurd voor afhandeling";
				$gem->Body		= implode("\n", $mail);
								
				# @live.nl heeft zijn mail-records niet op orde (geen SPF ed).
				# Aantal mailservers weigeren daarom deze mails als met een @live.nl verstuurd.
				# Daarom voor de zekerheid het formele van de cluco
				$gem->From	= $cluco->getMail(2);
				$gem->FromName = $cluco->getName(5);
				if(!$productieOmgeving)	$gem->testen = true;

				if(!$gem->sendMail()) {
					toLog("Problemen met versturen declaratie-goedkeuring [". $declaratie->hash ."] door cluco", 'error', $indiener->id);
					$page[] = "Er zijn problemen met het versturen van de goedkeuringsmail.<br>\n";
				} else {
					toLog("Declaratie-goedkeuring [". $declaratie->hash ."] door cluco", '', $indiener->id);
					$page[] = "Er is een mail met status-update verstuurd naar ". $indiener->getName(5) ."<br>\n";
				}
				
				# Mail naar penningmeester				
				$mail = array();
				$mail[] = "Beste Penningmeester,<br>";
				$mail[] = "<br>";
				$mail[] = "De declaratie van ". $indiener->getName(2)." voor <i>". makeOpsomming($onderwerpen, '</i>, <i>', '</i> en <i>') ."</i> ter waarde van ". formatPrice($declaratie->totaal) ." is door ". $cluco->getName(5) ." als cluster-coordinator goedgekeurd.";
				$mail[] = "<br>";
				$mail[] = "Details en mogelijkheid tot goed- of afkeuren zijn zichtbaar <a href='". $ScriptURL ."declaratie/penningmeester.php?key=". $declaratie->hash ."&reset'>online</a> (inloggen vereist)";
				
				$pen = new KKDMailer();
				$pen->ontvangers[]	= array($declaratieReplyAddress, $declaratieReplyName);
				$pen->Subject		= 'Door cluco goedgekeurde declaratie';
				$pen->Body			= implode("\n", $mail);
				$pen->From			= $indiener->getMail(1);
				$pen->FromName		= $indiener->getName(5);
				if(!$productieOmgeving)	$pen->testen = true;
								
				if(!$pen->sendMail()) {
					toLog("Problemen met versturen declaratie-goedkeuring naar penningmeester [". $declaratie->hash ."]", 'error', $indiener->id);
					$page[] = "Er zijn problemen met het versturen van de goedgekeurde declaratie naar de penningsmeester.";
				} else {
					toLog("Declaratie-goedkeuring [". $declaratie->hash ."] naar penningmeester", 'info', $indiener->id);
					$page[] = "De goedgekeurde declaratie is doorgestuurd naar de penningsmeester.";
				}
				
				# Stel de declaratie-status in
				$declaratie->status = 4;
			} else {
				$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
				$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";
				$page[] = "<input type='hidden' name='accept' value='1'>";
				$page[] = '<table border=0>';
				$page[] = "<tr>";
				$page[] = "		<td align='left'>Geef hieronder optioneel een korte toelichting aan de penningsmeester.<br>Deze toelichting zal enkel worden opgenomen in de correspondentie, maar zal <u>niet</u> opgenomen worden in de definitieve declaratie.</td>";
				$page[] = "</tr>";	
				$page[] = "<tr>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "</tr>";	
				$page[] = "<tr>";
				$page[] = "		<td align='center'><textarea name='opm_penning' cols=75 rows=10></textarea></td>";
				$page[] = "</tr>";
				$page[] = "<tr>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "</tr>";
				$page[] = "<tr>";
				$page[] = "		<td align='center'><input type='submit' name='send_accept' value='Goedkeuring versturen'></td>";
				$page[] = "</tr>";	
				$page[] = "</table>";
				$page[] = "</form>";
			}			
		} elseif(isset($_REQUEST['reject'])) {			
			if(isset($_REQUEST['send_reject'])) {				
				$mail[] = "Beste ". $indiener->getName(1) .",<br>";
				$mail[] = "<br>";
				$mail[] = "Je declaratie van ".time2str('d LLLL', $declaratie->tijd) ." voor <i>". makeOpsomming($onderwerpen, '</i>, <i>', '</i> en <i>') ."</i> ter waarde van ". formatPrice($declaratie->totaal) ." is door de cluster-coordinator afgewezen.<br>";				
				$mail[] = "Als reden daarvoor heeft de cluster-coordinator de volgende reden opgegeven :";
				$mail[] = "<i>". $declaratie->opmerking ."</i>";
				
				$gem = new KKDMailer();
				$gem->aan = $indiener->id;
				$gem->Subject	= "Afwijzing declaratie van ". time2str('d LLLL', $declaratie->tijd);
				$gem->Body		= implode("\n", $mail);
								
				# @live.nl heeft zijn mail-records niet op orde (geen SPF ed).
				# Aantal mailservers weigeren daarom deze mails als met een @live.nl verstuurd.
				# Daarom voor de zekerheid het formele van de cluco
				$gem->From	= $cluco->getMail(2);
				$gem->FromName = $cluco->getName(5);
				if(!$productieOmgeving)	$gem->testen = true;

				if(!$gem->sendMail()) {
					toLog("Problemen met versturen declaratie-afwijzing [". $declaratie->hash ."]", 'error', $indiener->id);
					$page[] = "Er zijn problemen met het versturen van de afwijzingsmail.";
				} else {
					toLog("Declaratie-afwijzing [". $declaratie->hash ."] naar gemeentelid", 'info', $indiener->id);
					$page[] = "Er is een mail met onderbouwing voor de afwijzing verstuurd naar ". $indiener->getName(5);
				}

				# Stel de declaratie-status in
				$declaratie->status = 6;
			} else {
				$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
				$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";
				$page[] = "<input type='hidden' name='reject' value='1'>";
				$page[] = '<table border=0>';
				$page[] = "<tr>";
				$page[] = "		<td align='left'>Geef hieronder een korte toelichting aan ". $indiener->getName(1) ." waarom deze declaratie is afgewezen.<br>Deze toelichting zal integraal worden opgenomen in de mail.</td>";
				$page[] = "</tr>";	
				$page[] = "<tr>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "</tr>";	
				$page[] = "<tr>";
				$page[] = "		<td align='center'><textarea name='afwijzing' cols=75 rows=10></textarea></td>";
				$page[] = "</tr>";
				$page[] = "<tr>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "</tr>";
				$page[] = "<tr>";
				$page[] = "		<td align='center'><input type='submit' name='send_reject' value='Afwijzing versturen'></td>";
				$page[] = "</tr>";	
				$page[] = "</table>";
				$page[] = "</form>";
			}
		} elseif(isset($_REQUEST['ask'])) {			
			if(isset($_REQUEST['send_question'])) {
				$mail[] = "Beste ". $indiener->getName(1) .",<br>";
				$mail[] = "<br>";
				$mail[] = "Je declaratie van ".time2str('d LLLL', $declaratie->tijd) ." voor <i>". makeOpsomming($onderwerpen, '</i>, <i>', '</i> en <i>') ."</i> ter waarde van ". formatPrice($declaratie->totaal) ." is door de cluster-coordinator bekeken.<br>";
				$mail[] = "Naar aanleiding daarvan heeft ". $cluco->getName(5) ." nog een vraag die hieronder is opgenomen.<br>";
				$mail[] = "<br>";
				$mail[] = "<i>". $declaratie->opmerking ."</i><br>";
				$mail[] = "<br>";
				$mail[] = "Je kan je declaratie aanvullen door middel van <a href='". $ScriptURL ."declaratie/gemeentelid.php?key=". $declaratie->hash ."&reset'>deze link</a> (inloggen vereist).";
				
				$gem = new KKDMailer();
				$gem->aan = $indiener->id;
				$gem->Subject	= "Aanvullende vraag over je declaratie van ". time2str('d LLLL', $declaratie->tijd);
				$gem->Body		= implode("\n", $mail);
								
				# @live.nl heeft zijn mail-records niet op orde (geen SPF ed).
				# Aantal mailservers weigeren daarom deze mails als met een @live.nl verstuurd.
				# Daarom voor de zekerheid het formele mailadres van de cluco
				$gem->From	= $cluco->getMail(2);
				$gem->FromName = $cluco->getName(5);
				if(!$productieOmgeving)	$gem->testen = true;

				if(!$gem->sendMail()) {
					toLog("Problemen met versturen aanvullende vraag declaratie [". $declaratie->hash ."]", 'error', $indiener->id);
					$page[] = "Er zijn problemen met het versturen van de mail.";
				} else {
					toLog("Declaratie-vraag [". $declaratie->hash ."] naar gemeentelid", 'info', $indiener->id);
					$page[] = "Er is een mail met de vraag verstuurd naar ". $indiener->getName(5);
				}

				# Stel de declaratie-status in
				$declaratie->status = 2;
			} else {
				$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
				$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";
				$page[] = "<input type='hidden' name='ask' value='1'>";
				$page[] = '<table border=0>';
				$page[] = "<tr>";
				$page[] = "		<td align='left'>Geef hieronder aan welke vraag je aan ". $indiener->getName(1) ." hebt of welke informatie ".($indiener->geslacht == 'M' ? 'hij' : 'zij')." moet toevoegen.<br>". $indiener->getName(1) ." krijgt dan een mail met link naar de declaratie inclusief deze opmerking.</td>";
				$page[] = "</tr>";	
				$page[] = "<tr>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "</tr>";	
				$page[] = "<tr>";
				$page[] = "		<td align='center'><textarea name='information' cols=75 rows=10></textarea></td>";
				$page[] = "</tr>";
				$page[] = "<tr>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "</tr>";
				$page[] = "<tr>";
				$page[] = "		<td align='center'><input type='submit' name='send_question' value='Vraag versturen'></td>";
				$page[] = "</tr>";	
				$page[] = "</table>";
				$page[] = "</form>";
			}
		} else {
			if($declaratie->id > 0) {		
				$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
				$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";				
				$page[] = '<table border=0>';
								
				$page = array_merge($page, showDeclaratieDetails($declaratie));
			
				$page[] = "<tr>";
				$page[] = "		<td colspan='6'>&nbsp;</td>";
				$page[] = "</tr>";
				$page[] = "<tr>";
				$page[] = "		<td colspan='2'><input type='submit' name='reject' value='Afkeuren' title='Keur deze declaratie af'></td>";
				$page[] = "		<td colspan='2' align='center'><input type='submit' name='ask' value='Terug naar gemeentelid' title='Vraag de indiener de declaratie aan te vullen'></td>";				
				$page[] = "		<td colspan='2' align='right'><input type='submit' name='accept' value='Goedkeuren' title='Stuur deze declaratie door naar de penningmeester'></td>";
				$page[] = "</tr>";	
				$page[] = "</table>";
				$page[] = "</form>";
			} else {
				$page[] = "Deze declaratie bestaat niet";
				toLog('Niet bestaande declaratie geopend', 'error');
			}
		}
	} else {		
		$cluster = 0;			
		if(in_array($_SESSION['useID'], $clusterCoordinatoren)) {
			$cluster = array_search($_SESSION['useID'], $clusterCoordinatoren);			
		}

		$declaraties = Declaratie::getDeclaraties(3, $cluster);
		
		if(count($declaraties) > 0)	{					
			$page[] = "<table>";
			$page[] = "<tr>";
			$page[] = "<td colspan='2'><b>Tijdstip</b></td>";
			
			if($cluster == 0) {
				$page[] = "<td colspan='2'><b>Cluster</b></td>";				
			}
			
			$page[] = "<td colspan='2'><b>Indiener</b></td>";			
			$page[] = "<td><b>Bedrag</b></td>";
			$page[] = "</tr>";
			
			foreach($declaraties as $declaratieHash) {				
				$declaratie = new Declaratie($declaratieHash);
				$user = new Member($declaratie->gebruiker);

				$page[] = "<tr>";
				$page[] = "<td>". time2str('d LLL HH:mm', $declaratie->tijd) ."</td>";
				$page[] = "<td>&nbsp;</td>";
				
				if($cluster == 0) {
					$page[] = "<td>". $clusters[$declaratie->cluster] ."</td>";
					$page[] = "<td>&nbsp;</td>";
				}				
				
				$page[] = "<td><a href='../profiel.php?id=". $user->id ."' target='profiel'>". $user->getName(5) ."</a></td>";
				$page[] = "<td>&nbsp;</td>";
				
				$page[] = "<td><a href='?key=".$declaratie->hash ."'>". formatPrice($declaratie->totaal) ."</a></td>";
				$page[] = "</tr>";
			}
			$page[] = "</table>";
		} else {
			$page[] = "Geen openstaande declaratie's";
		}
	}
} else {
	$page[] = "U bent geen cluster-coordinator";
	toLog('Probeert als niet CluCo de cluco-pagina te opnenen', 'error');
}

if(isset($_REQUEST['send_accept']) || isset($_REQUEST['send_reject']) || isset($_REQUEST['send_question'])) {	
	$page[] = "<br><br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";

	# En sla het object op
	$declaratie->save();
				
	# Alles verwijderen nadat de declaratie is ingeschoten en de mail de deur uit is
	$declaratie = null;	
}

# Pagina tonen
$pageTitle = 'Declaratie';
include_once('../include/HTML_TopBottom.php');

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $page).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

# Sla de declaratie-gegevens op in de sessie
$_SESSION['declaratie'] = $declaratie;
?>