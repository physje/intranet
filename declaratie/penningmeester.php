<?php
include_once('../include/functions.php');
//include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');
//include_once('genereerDeclaratiePdf.php');
$db = connect_db();

$showLogin = true;

if(isset($_REQUEST['hash'])) {
	$id = isValidHash($_REQUEST['hash']);
	
	if(!is_numeric($id)) {
		toLog('error', '', '', 'ongeldige hash (declaratie)');
		$showLogin = true;
	} else {
		$showLogin = false;
		$_SESSION['ID'] = $id;
		toLog('info', $id, '', 'declaratie mbv hash');
	}
}

if($showLogin) {	
	$cfgProgDir = '../auth/';
	$requiredUserGroups = array(1, 38);
	include($cfgProgDir. "secure.php");

}

if(isset($_REQUEST['key'])) {	
	$sql = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieHash like '". $_REQUEST['key'] ."'";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
			
	$JSON = json_decode($row[$EBDeclaratieDeclaratie], true);
	
	$data['user']					= $row[$EBDeclaratieIndiener];
	$data['eigen']				= $JSON['eigen'];
	$data['iban']					= $JSON['iban'];
	$data['relatie']			= $JSON['relatie'];
	$data['cluster']			= $JSON['cluster'];
	$data['overige']			= $JSON['overig'];
	$data['overig_price']	= $JSON['overig_price'];
	$data['reiskosten']		= $JSON['reiskosten'];
	
	if(isset($_POST['accept'])) {
		$data['GBR'] = $JSON['GBR'] = $_POST['GBR'];
		
		//$page[] = $data['eigen'] .'|'.$data['relatie'] .'<br>';		
		//$page[] = $data['eigen'] .'|'.$data['iban'];
		
		foreach($JSON as $key => $value) {
			$page[] = "<b>$key</b>: $value<br>\n";
		}
		
		# Check
		
		//eb_maakNieuweRelatieAan (makeVoorgangerName($voorganger, 6), 'm', '', '', $voorgangerData['plaats'], $voorgangerData['mail'], $_POST['IBAN'], $EB_code, $EB_id);
		
	} else {			
		$page[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>";
		$page[] = "<input type='hidden' name='key' value='". $_REQUEST['key'] ."'>";
		$page[] = "<input type='hidden' name='user' value='". $data['user'] ."'>";
		$page[] = '<table border=0>';
					
		$page = array_merge($page, showDeclaratieDetails($data));
				
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'><hr></td>";
		$page[] = "</tr>";	
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>Vul hieronder de ontbrekende gegevens in :</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Grootboekrekening</td>";	
		$page[] = "		<td colspan='4'><select name='GBR'>";
		$page[] = "		<option value=''>Kies Grootboekrekening</option>";
		
		$presetGBR = 0;	
		
		switch ($data['cluster']) {
			case 1: # Gemeenteopbouw
				$presetGBR = 0;
				break;
			case 2: # Jeugd & Gezin
				$presetGBR = 43865;
				break;
			case 3: # Eredienst
				$presetGBR = 43845;
				break;
			case 4: # Missionaire Activiteiten
				$presetGBR = 43895;
				break;
			case 5: # Organisatie & Beheer
				$presetGBR = 43875;
				break;
		}
			
		foreach($cfgGBR as $code => $naam) {
			$page[] = "		<option value='$code'". ($code == $presetGBR ? ' selected' : '') .">$naam</option>";
		}
		
		$page[] = "		</select></td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";	
		$page[] = "		<td colspan='6' align='right'><input type='submit' name='accept' value='Invoeren in e-boekhouden.nl'></td>";
		$page[] = "</tr>";	
		$page[] = "</table>";
		$page[] = "</form>";		
	}
} else {
	$sql = "SELECT * FROM $TableEBDeclaratie WHERE $EBDeclaratieStatus = 4";
	$result = mysqli_query($db, $sql);
		
	if($row = mysqli_fetch_array($result)) {
		$page[] = "<table>";
		$page[] = "<tr>";
		$page[] = "<td colspan='2'><b>Tijdstip</b></td>";
		$page[] = "<td colspan='2'><b>Cluster</b></td>";				
		$page[] = "<td colspan='2'><b>Indiener</b></td>";			
		$page[] = "<td><b>Bedrag</b></td>";
		$page[] = "</tr>";
			
		do {
			$page[] = "<tr>";
			$page[] = "<td>". time2str('%e %b %H:%M', $row[$EBDeclaratieTijd]) ."</td>";
			$page[] = "<td>&nbsp;</td>";			
			$page[] = "<td>". $clusters[$row[$EBDeclaratieCluster]] ."</td>";
			$page[] = "<td>&nbsp;</td>";
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

# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '	<td valign="top">'. showBlock(implode("\n", $page), 100). '</td>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;