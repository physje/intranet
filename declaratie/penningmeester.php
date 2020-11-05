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
	$indiener = $row[$EBDeclaratieIndiener];
	
	if(isset($_POST['accept'])) {
		$JSON['GBR'] = $_POST['GBR'];
		
		$UserData = getMemberDetails($indiener);
				
		//foreach($JSON as $key => $value) {
		//	$page[] = "<b>$key</b>: $value<br>\n";
		//}
		
		# EIGEN = JA
		if($JSON['eigen'] == 'ja') {			
			if(is_numeric($UserData['eb_code']) AND $UserData['eb_code'] > 0) {
				# Al bekend bij eBoekhouden
				$EBCode = $UserData['eb_code'];
				
				#eb_getRelatieIbanByCode($EBCode, $EBIBAN);
				
				# Klopt IBAN-nummer nog wat bij eBoekhouden bekend is
				if(cleanIBAN($EBIBAN) != cleanIBAN($JSON['iban'])) {
					# Update eBoekhouden
					$data['iban'] = $JSON['iban'];
					#$errorResult = eb_updateRelatieByCode($EBCode, $data);
					
					if($errorResult) {
						toLog('error', '', $indiener, $errorResult);						
					} else {
						toLog('debug', '', $indiener, 'IBAN van relatie '. $EBCode .' aangepast van '. cleanIBAN($EBIBAN) .' naar '. cleanIBAN($JSON['iban']));
					}					
				}			
			
			} else {
				# Niet bekend bij eBoekhouden				
				$naam			= makeName($indiener, 15);
				$geslacht	= strtolower($UserData['geslacht']);
				$adres		= $UserData['straat'].' '.$UserData['huisnummer'].$UserData['huisletter'].($UserData['toevoeging'] != '' ? '-'.$UserData['toevoeging'] : '');
				$postcode	= str_replace(' ', '', $UserData['PC']);
				$plaats		= ucfirst(strtolower($UserData['plaats']));
				$mail			= $UserData['mail'];
				$iban			= $JSON['iban'];
								
				#eb_maakNieuweRelatieAan ($naam , $geslacht, $adres, $postcode, $plaats, $mail, $iban, $EBCode, $EB_id);
				#mysqli_query($db, "UPDATE $TableUsers SET $UserEBRelatie = $EBCode WHERE $UserID = $indiener");
			}
		}
		
		
		# EIGEN = NEE
		if($JSON['eigen'] == 'nee') {
			if($_POST['begunstigde'] == 3) {
				$naam			= $_POST['name_new'];
				$geslacht	= 'm';
				$adres		= $_POST['adres_new'];
				$postcode	= str_replace(' ', '', $_POST['PC_new']);
				$plaats		= ucfirst(strtolower($_POST['plaats_new']));
				$mail			= '';
				$iban			= cleanIBAN($_POST['iban_new']);
				
				#eb_maakNieuweRelatieAan ($naam , $geslacht, $adres, $postcode, $plaats, $mail, $iban, $EBCode, $EB_id);
			} else {
				$EBCode = $_POST['begunstigde'];
			}
		}
		
		$boekstukNummer	= generateBoekstukNr(date('Y'));				
		$factuurnummer	= 'declaratie-'.date('d-m-Y').'-'.$_REQUEST['key'];
		$toelichting		= implode(', ', $omschrijving);
		$totaal					= $row[$EBDeclaratieTotaal];
								
		#$errorResult = eb_verstuurDeclaratie ($EBCode, $boekstukNummer, $factuurnummer, $totaal, $_POST['GBR'], $toelichting, $mutatieId);
		
		
		# EIGEN = JA
		# BEPAAL EBID
		# 	Check of $data['user'] al bekend is in eboekhouden
		# 	Indien bekend -> check IBAN nummer -> pas aan indien nodig -> definieer EBID
		# 	Indien niet bekend -> voeg toe -> definieer EBID -> koppel ook in leden-db
		
		# EIGEN = NEE
		# RELATIE <> 3
		#		 definieer EBID
		# RELATIE == 3
				
		
	} else {
		$data['user']					= $indiener;
		$data['eigen']				= $JSON['eigen'];
		$data['iban']					= $JSON['iban'];
		$data['relatie']			= $JSON['EB_relatie'];
		$data['cluster']			= $JSON['cluster'];
		$data['overige']			= $JSON['overig'];
		$data['overig_price']	= $JSON['overig_price'];
		$data['reiskosten']		= $JSON['reiskosten'];
		$data['bijlage']			= $JSON['bijlage'];
		$data['bijlage_naam']	= $JSON['bijlage_naam'];
			
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
				$presetGBR = 43855;
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
		
		//if($data['eigen'] == 'Nee') {
		if(true) {
			$page[] = "<tr>";
			$page[] = "		<td colspan='6'>&nbsp;</td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>Betalingskenmerk</td>";	
			$page[] = "	<td valign='top' colspan='4'><input type='text' name='betalingskenmerk' size='40'></td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "		<td colspan='6'>&nbsp;</td>";
			$page[] = "</tr>";
			$page[] = "<tr>";
			$page[] = "	<td valign='top' colspan='2'>Bedrijf / kerkelijke instellingen?</td>";	
			$page[] = "	<td valign='top' colspan='4'><select name='begunstigde'>";
			$page[] = "	<option value=''>Selecteer bedrijf/instelling</option>";
			
			$sql = "SELECT * FROM $TableEBoekhouden ORDER BY $EBoekhoudenNaam";
			$result = mysqli_query($db, $sql);
			$row = mysqli_fetch_array($result);
		
			do {
				$page[] = "	<option value='". $row[$EBoekhoudenCode] ."'". ($row[$EBoekhoudenCode] == $data['relatie'] ? ' selected' : '').">". $row[$EBoekhoudenNaam] ."</option>";
			} while($row = mysqli_fetch_array($result));
			
			$page[] = "	</select></td>";
			$page[] = "</tr>";			
		}
		
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'><b>Let wel</b>: om een nieuwe begunstigde toe te voegen dient bij '<i>Bedrijf / kerkelijke instellingen</i>' 'diversen' geselecteerd te worden.</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "	<td valign='top' colspan='2'>Naam</td>";	
		$page[] = "	<td valign='top' colspan='4'><input type='text' name='name_new' size='40'></td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "	<td valign='top' colspan='2'>Adres</td>";	
		$page[] = "	<td valign='top' colspan='4'><input type='text' name='adres_new' size='40'></td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "	<td valign='top' colspan='2'>Postcode</td>";	
		$page[] = "	<td valign='top' colspan='4'><input type='text' name='PC_new' size='40'></td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "	<td valign='top' colspan='2'>Plaats</td>";	
		$page[] = "	<td valign='top' colspan='4'><input type='text' name='plaats_new' size='40'></td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "	<td valign='top' colspan='2'>IBAN</td>";	
		$page[] = "	<td valign='top' colspan='4'><input type='text' name='iban_new' size='40'></td>";
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