<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_HeaderFooter.php');
//include_once('genereerDeclaratiePdf.php');
$db = connect_db();

$showLogin = true;

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('error', '', 'ongeldige hash (declaratie)');
		$showLogin = true;
	} else {
		$showLogin = false;
		$_SESSION['useID'] = $id;
		toLog('info', $id, '', 'declaratie mbv hash');
	}
}

if($showLogin) {
	$cfgProgDir = '../auth/';
	include($cfgProgDir. "secure.php");
	$db = connect_db();
}

$toegestaan = array_merge($clusterCoordinatoren, getGroupMembers(1), getGroupMembers(38));

if(in_array($_SESSION['useID'], $toegestaan)) {	
	if(isset($_REQUEST['key'])) {
		$sql = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieHash like '". $_REQUEST['key'] ."'";
		$result = mysqli_query($db, $sql);
		$row = mysqli_fetch_array($result);
				
		$JSON = json_decode($row[$EBDeclaratieDeclaratie], true);
				
		$data['user']							= $row[$EBDeclaratieIndiener];
		$data['eigen']						= $JSON['eigen'];
		$data['iban']							= $JSON['iban'];
		$data['relatie']					= $JSON['EB_relatie'];
		$data['cluster']					= $JSON['cluster'];
		$data['overige']					= $JSON['overig'];
		$data['overig_price']			= $JSON['overig_price'];
		$data['reiskosten']				= $JSON['reiskosten'];
		$data['opmerking_cluco']	= $JSON['opm_cluco'];
				
		if(isset($_REQUEST['accept'])) {
			if(isset($_REQUEST['send_accept'])) {
				# Mail naar gemeentelid
				$mail[] = "Beste ". makeName($data['user'], 1) .",<br>";
				$mail[] = "<br>";
				$mail[] = "Onderstaande declaratie van ".time2str('%e %B', $row[$EBDeclaratieTijd]) ." is door ". makeName($_SESSION['useID'], 5) ." als cluster-coordinator goedgekeurd en doorgestuurd naar de penningmeester voor verdere afhandeling.<br>";
				$mail[] = "Mocht deze laatste nog vragen hebben dan neemt hij contact met je op.<br>";
				$mail[] = '<table border=0>';
				$mail[] = "<tr>";
				$mail[] = "		<td colspan='6' height=50><hr></td>";
				$mail[] = "</tr>";			
				$mail = array_merge($mail, showDeclaratieDetails($data));			
				$mail[] = "</table>";
				
				$parameter['to'][]			= array($data['user']);
				$parameter['subject']		= 'Declaratie doorgestuurd voor afhandeling';
				$parameter['message'] 	= implode("\n", $mail);
				
				# @live.nl heeft zijn mail-records niet op orde (geen SPF ed).
				# Aantal mailservers weigeren daarom deze mails als met een @live.nl verstuurd.
				# Daarom voor de zekerheid het formele van de cluco
				$parameter['from']			= getMailAdres($_SESSION['useID'], true);
				$parameter['fromName']	= makeName($_SESSION['useID'], 5);
				
				if(!sendMail_new($parameter)) {
					toLog('error', $data['user'], "Problemen met versturen declaratie-goedkeuring [". $_REQUEST['key'] ."] door cluco");
					$page[] = "Er zijn problemen met het versturen van de goedkeuringsmail.<br>\n";
				} else {
					toLog('info', $data['user'], "Declaratie-goedkeuring [". $_REQUEST['key'] ."] door cluco");
					$page[] = "Er is een mail met status-update verstuurd naar ". makeName($data['user'], 5) ."<br>\n";
					setDeclaratieStatus(4, $row[$EBDeclaratieID], $data['user']);
					setDeclaratieActionDate($_REQUEST['key']);
				}
				
				# Mail naar penningmeester
				unset($mail, $parameter);
				
				$mail[] = "Beste Penningmeester,<br>";
				$mail[] = "<br>";
				$mail[] = "Onderstaande declaratie van ". makeName($data['user'], 5) ." is door ". makeName($_SESSION['useID'], 5) ." als cluster-coordinator goedgekeurd.<br>";
				$mail[] = '<table border=0>';
				$mail[] = "<tr>";
				$mail[] = "		<td colspan='6' height=50><hr></td>";
				$mail[] = "</tr>";			
				$mail = array_merge($mail, showDeclaratieDetails($data));			
				$mail[] = "</table>";
				$mail[] = "<br>";
				$mail[] = "Details en mogelijkheid tot goed- of afkeuren zijn zichtbaar <a href='". $ScriptURL ."declaratie/penningmeester.php?key=". $_REQUEST['key'] ."'>online</a> (inloggen vereist)";
				
				#$parameter['to'][]			= array($penningmeesterJGAddress, $penningmeesterJGNaam);
				$parameter['to'][]			= array($declaratieReplyAddress, $declaratieReplyName);
				$parameter['subject']		= 'Door cluco goedgekeurde declaratie';
				$parameter['message'] 	= implode("\n", $mail);
				$parameter['from']			= getMailAdres($_SESSION['useID'], true);
				$parameter['fromName']	= makeName($_SESSION['useID'], 5);
				
				if(!sendMail_new($parameter)) {
					toLog('error', $data['user'], "Problemen met versturen declaratie-goedkeuring naar penningmeester [". $_REQUEST['key'] ."]");
					$page[] = "Er zijn problemen met het versturen van de goedgekeurde declaratie naar de penningsmeester.";
				} else {
					toLog('info', $data['user'], "Declaratie-goedkeuring [". $_REQUEST['key'] ."] naar penningmeester");
					$page[] = "De goedgekeurde declaratie is doorgestuurd naar de penningsmeester.";
				}
				
				# JSON-string terug in database
				$JSON['opm_penning'] = $_POST['opm_penning'];				
				$JSONtoDatabase = encode_clean_JSON($JSON);
				$sql = "UPDATE $TableEBDeclaratie SET $EBDeclaratieDeclaratie = '". $JSONtoDatabase ."' WHERE $EBDeclaratieID like ". $row[$EBDeclaratieID];
				mysqli_query($db, $sql);			
			} else {
				$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
				$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";
				$page[] = "<input type='hidden' name='accept' value='1'>";
				$page[] = '<table border=0>';
				$page[] = "<tr>";
				$page[] = "		<td align='left'>Geef hieronder optioneel een korte toelichting aan de penningsmeester.<br>Deze toelichting zal <u>niet</u> opgenomen worden in de definitieve declaratie en is dus enkel om eventueel een korte toelichting te geven.</td>";
				$page[] = "</tr>";	
				$page[] = "<tr>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "</tr>";	
				$page[] = "<tr>";
				$page[] = "		<td align='center'><textarea name='opm_penning' cols=75 rows=10>". $_POST['opm_penning'] ."</textarea></td>";
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
				$mail[] = "Beste ". makeName($data['user'], 1) .",<br>";
				$mail[] = "<br>";
				$mail[] = "Op ".time2str('%e %B', $row[$EBDeclaratieTijd]) ." heb jij onderstaande declaratie gedaan. Helaas is deze afgewezen door de cluster-coordinator.<br>";
				$mail[] = "Als reden daarvoor heeft de cluster-coordinator de volgende reden opgegeven :";
				$mail[] = '<table border=0>';
				$mail[] = "<tr>";
				$mail[] = "		<td colspan='6'>&nbsp;</td>";
				$mail[] = "</tr>";
				$mail[] = "<tr>";
				$mail[] = "		<td>&nbsp;</td>";
				$mail[] = "		<td colspan='5'><i>". $_POST['afwijzing'] ."</i></td>";
				$mail[] = "</tr>";
				$mail[] = "<tr>";
				$mail[] = "		<td colspan='6' height=50><hr></td>";
				$mail[] = "</tr>";			
				$mail = array_merge($mail, showDeclaratieDetails($data));			
				$mail[] = "</table>";
				
				$parameter['to'][]			= array($data['user']);
				$parameter['subject']		= 'Afwijzing declaratie';
				$parameter['message'] 	= implode("\n", $mail);
				$parameter['from']			= getMailAdres($_SESSION['useID']);
				$parameter['fromName']	= makeName($_SESSION['useID'], 5);
				
				if(!sendMail_new($parameter)) {
					toLog('error', $data['user'], "Problemen met versturen declaratie-afwijzing [". $_REQUEST['key'] ."]");
					$page[] = "Er zijn problemen met het versturen van de afwijzingsmail.";
				} else {
					toLog('info', $data['user'], "Declaratie-afwijzing [". $_REQUEST['key'] ."] naar gemeentelid");
					$page[] = "Er is een mail met onderbouwing voor de afwijzing verstuurd naar ". makeName($data['user'], 5);
					setDeclaratieStatus(6, $row[$EBDeclaratieID], $data['user']);
					setDeclaratieActionDate($_REQUEST['key']);
				}						
			} else {
				$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
				$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";
				$page[] = "<input type='hidden' name='reject' value='1'>";
				$page[] = '<table border=0>';
				$page[] = "<tr>";
				$page[] = "		<td align='left'>Geef hieronder een korte toelichting aan ". makeName($data['user'], 1) ." waarom deze declaratie is afgewezen.<br>Deze toelichting zal integraal worden opgenomen in de mail.</td>";
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
		} else {
			if(count($JSON) > 1) {		
				$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
				$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";
				$page[] = "<input type='hidden' name='user' value='". $data['user'] ."'>";
				$page[] = '<table border=0>';
				
				# Pas hier toevoegen, omdat anders in de mail ook de bijlage in de tekst wordt opgenomen				
				$data['bijlage']			= $JSON['bijlage'];
				$data['bijlage_naam']	= $JSON['bijlage_naam'];
				$data['toelichting_penning']	= $JSON['toelichting'];
				$data['key']					= $_REQUEST['key'];
				
				$page = array_merge($page, showDeclaratieDetails($data));
			
				$page[] = "<tr>";
				$page[] = "		<td colspan='6'>&nbsp;</td>";
				$page[] = "</tr>";
				$page[] = "<tr>";
				$page[] = "		<td colspan='2'><input type='submit' name='reject' value='Afkeuren'></td>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td colspan='3' align='right'><input type='submit' name='accept' value='Goedkeuren'></td>";
				$page[] = "</tr>";	
				$page[] = "</table>";
				$page[] = "</form>";
			} else {
				$page[] = "Deze declaratie bestaat niet";
			}		
		}
	} else {		
		$sql = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieStatus = 3";
		
		if(in_array($_SESSION['useID'], $clusterCoordinatoren)) {
			$cluster = array_search($_SESSION['useID'], $clusterCoordinatoren);
			$sql .= " AND $EBDeclaratieCluster = $cluster AND $EBDeclaratieIndiener NOT like '". $_SESSION['useID'] ."'";
		}
		
		$result = mysqli_query($db, $sql);
		
		if($row = mysqli_fetch_array($result)) {
			$page[] = "<table>";
			$page[] = "<tr>";
			$page[] = "<td colspan='2'><b>Tijdstip</b></td>";
			
			if(!isset($cluster)) {
				$page[] = "<td colspan='2'><b>Cluster</b></td>";				
			}
			
			$page[] = "<td colspan='2'><b>Indiener</b></td>";			
			$page[] = "<td><b>Bedrag</b></td>";
			$page[] = "</tr>";
			
			do {
				$page[] = "<tr>";
				$page[] = "<td>". time2str('%e %b %H:%M', $row[$EBDeclaratieTijd]) ."</td>";
				$page[] = "<td>&nbsp;</td>";
				
				if(!isset($cluster)) {
					$page[] = "<td>". $clusters[$row[$EBDeclaratieCluster]] ."</td>";
					$page[] = "<td>&nbsp;</td>";
				}
				
				$page[] = "<td><a href='../profiel.php?id=". $row[$EBDeclaratieIndiener] ."'>". makeName($row[$EBDeclaratieIndiener], 5) ."</a></td>";
				$page[] = "<td>&nbsp;</td>";
				
				$page[] = "<td><a href='?key=". $row[$EBDeclaratieHash] ."'>". formatPrice($row[$EBDeclaratieTotaal]) ."</a></td>";
				$page[] = "</tr>";
			} while($row = mysqli_fetch_array($result));
			$page[] = "</table>";
		} else {
			$page[] = "Geen openstaande declaratie's";
		}
	}
} else {
	$page[] = "U bent geen cluster-coordinator";
}

if(isset($_REQUEST['send_accept']) OR isset($_REQUEST['send_reject'])) {
	$page[] = "<br><br>Ga terug naar <a href='". $_SERVER['PHP_SELF']."'>het overzicht</a>.";
}

# Pagina tonen
$pageTitle = 'Declaratie';
include_once('../include/HTML_TopBottom.php');

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $page).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>