<?php
include_once('../include/class.phpmailer.php');
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');
include_once('genereerDeclaratiePdf.php');

$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

if($productieOmgeving) {
	$write2EB = true;
	$sendMail = true;
	$sendTestMail = false;
} else {
	$write2EB = false;
	$sendMail = false;
	$sendTestMail = false;
	
	//echo 'Test-omgeving';
}

$page[] = "<form enctype='multipart/form-data' method='post' action='$_SERVER[PHP_SELF]'>";
if(isset($_POST['cluster']))			$page[] = "<input type='hidden' name='cluster' value='". $_POST['cluster'] ."'>";
if(isset($_POST['iban']))					$page[] = "<input type='hidden' name='iban' value='". $_POST['iban'] ."'>";
if(isset($_POST['toelichting'])) 	$page[] = "<input type='hidden' name='toelichting' value='". ($_POST['page'] == 4 ? urlencode($_POST['toelichting']) : $_POST['toelichting'])  ."'>";
if(isset($_POST['regel'])) {
	foreach($_POST['price'] as $key => $prijs) {
		if($_POST['regel'][$key] != '') {
			$page[] = "<input type='hidden' name='regel[$key]' value='". ($_POST['page'] == 2 ? urlencode($_POST['regel'][$key]) : $_POST['regel'][$key]) ."'>";
			$page[] = "<input type='hidden' name='price[$key]' value='". str_replace(',', '.', $prijs) ."'>";
			if($_POST['bewijs'][$key] != '') {
				$page[] = "<input type='hidden' name='bewijs[$key]' value='". ($_POST['page'] == 3 ? urlencode($_POST['bewijs'][$key]) : $_POST['bewijs'][$key]) ."'>";
				$page[] = "<input type='hidden' name='originalName[$key]' value='". ($_POST['page'] == 3 ? urlencode($_POST['originalName'][$key]) : $_POST['originalName'][$key]) ."'>";
			}
		}
	}
}

# Om elke keer weg te kunnen schrijven
$toDatabase = $_POST;
unset($toDatabase['insturen']);
unset($toDatabase['page']);
unset($toDatabase['cluster']);
$JSONtoDatabase = json_encode($toDatabase);

if(isset($_POST['page']) AND !isset($_POST['id'])) {
	$sql = "INSERT INTO $TableEBDeclaratie ($EBDeclaratieIndiener, $EBDeclaratieCluster, $EBDeclaratieDeclaratie, $EBDeclaratieTijd) VALUES (". $_SESSION['ID'].", ". $_POST['cluster'] .", '". $JSONtoDatabase ."', ". time() .")";	
	mysqli_query($db, $sql);
	$id = mysqli_insert_id($db);
	$page[] = "<input type='hidden' name='id' value='$id'>";	
	setDeclaratieStatus(1, $id, $_SESSION['ID']);
} elseif(isset($_POST['page']) AND isset($_POST['id'])) {
	$sql = "UPDATE $TableEBDeclaratie SET $EBDeclaratieIndiener = ". $_SESSION['ID'].", $EBDeclaratieCluster = ". $_POST['cluster'] .", $EBDeclaratieDeclaratie = '". $JSONtoDatabase ."', $EBDeclaratieTijd = ". time() ." WHERE $EBDeclaratieID = ". $_POST['id'];
	mysqli_query($db, $sql);	
	$page[] = "<input type='hidden' name='id' value='". $_POST['id'] ."'>";
	setDeclaratieStatus(4, $_POST['id'], $_SESSION['ID']);
}


# Scherm voor gemeenteleden

if(isset($_POST['insturen'])) {	
	# Scherm 7
	$cluster = $_POST['cluster'];
	$cluco = $clusterCoordinatoren[$cluster];
	
	$mail[] = 'Beste '. makeName($cluco, 3) .'<br>';
	$mail[] = '<br>';
	$mail[] = makeName($_SESSION['ID'], 5) .' heeft een declaratie ingediend.<br>';
	$mail[] = '<br>';
	$mail[] = '<table>';
	
	if(trim($_POST['toelichting']) != '') {
		$mail[] = "<tr>";
		$mail[] = "	<td colspan='2'><b>Toelichting :</b><br><i>".urldecode($_POST['toelichting']) ."</i></td>";
		$mail[] = "</tr>";
		$mail[] = "<tr>";
		$mail[] = "	<td colspan='2'>&nbsp;</td>";
		$mail[] = "</tr>";
	}
	
	$mail[] = "<tr>";
	$mail[] = "	<td colspan='2'><b>Declaratieposten</b></td>";
	$mail[] = "</tr>";
	
	foreach($_POST['regel'] as $key => $regel) {
		$mail[] = "<tr>";
		$mail[] = "	<td>". urldecode($regel) ."</td>";
		$mail[] = "	<td>". formatPrice(100*$_POST['price'][$key]) ."</td>";
		$mail[] = "</tr>";
		
		if($_POST['originalName'][$key] != '') {
			$mail[] = "<tr>";
			$mail[] = "	<td colspan='2'><li><i>". $_POST['originalName'][$key] ."</i></li></td>";
			$mail[] = "</tr>";
		}		
	}
	
	$mail[] = "<tr>";
	$mail[] = "	<td colspan='2'>&nbsp;</td>";
	$mail[] = "</tr>";
	$mail[] = "<tr>";
	$mail[] = "	<td>Goedkeuren</td>";
	$mail[] = "	<td>Afkeuren</td>";
	$mail[] = "</tr>";
	$mail[] = "</table>";
	
	$parameter['to'][]				= array($cluco);
	$parameter['subject']			= 'Declaratie van '. makeName($_SESSION['ID'], 5);
	$parameter['ReplyTo']			= getMailAdres($_SESSION['ID']);
	$parameter['ReplyToName']	= makeName($_SESSION['ID'], 5);
	$parameter['message']			= implode("\n", $mail);
		
	foreach($_POST['bewijs'] as $key => $bewijs) {
		$parameter['file'][] = $bewijs;
		$parameter['fileName'][] = $_POST['originalName'][$key];
	}	
	
	sendMail_new($parameter);	
	
	$page[] = "Je declaratie is naar ". makeName($cluco, 5) ." als CluCo van ". $clusters[$cluster] .' gestuurd';
} elseif(isset($_POST['last_check'])) {	
	# Scherm 6
	$page[] = "<input type='hidden' name='page' value='6'>";
	$page[] = "<table border=0>";
	$page[] = "<tr>";
	$page[] = "	<td colspan='2'>De volgende zaken worden naar de clustercoordinator gestuurd ter goedkeuring :</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td colspan='2'>&nbsp;</td>";
	$page[] = "</tr>";
	if(trim($_POST['toelichting']) != '') {
		$page[] = "<tr>";
		$page[] = "	<td colspan='2'><b>Toelichting :</b><br><i>".urldecode($_POST['toelichting']) ."</i></td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "	<td colspan='2'>&nbsp;</td>";
		$page[] = "</tr>";
	}
	
	$page[] = "<tr>";
	$page[] = "	<td colspan='2'><b>Declaratieposten</b></td>";
	$page[] = "</tr>";
	
	foreach($_POST['regel'] as $key => $regel) {
		$page[] = "<tr>";
		$page[] = "	<td>". urldecode($regel) ."</td>";
		$page[] = "	<td>". formatPrice(100*$_POST['price'][$key]) ."</td>";
		$page[] = "</tr>";
	}
	
	$page[] = "<tr>";
	$page[] = "	<td colspan='2'>&nbsp;</td>";
	$page[] = "</tr>";
	
	if(count($_POST['originalName']) > 0) {
		$page[] = "<tr>";
		$page[] = "	<td colspan='2'><b>Bijlages</b></td>";
		$page[] = "</tr>";
	
		foreach($_POST['originalName'] as $name) {
			$page[] = "<tr>";
			$page[] = "	<td>". $name ."</td>";
			$page[] = "	<td>&nbsp;</td>";
			$page[] = "</tr>";
		}
		
		$page[] = "<tr>";
		$page[] = "	<td colspan='2'>&nbsp;</td>";
		$page[] = "</tr>";		
	}
	
	$page[] = "<tr>";
	$page[] = "	<td align='left'><input type='submit' name='give_iban' value='Vorige'></td>";
	$page[] = "	<td align='right'><input type='submit' name='insturen' value='Volgende'></td>";
	$page[] = "</tr>";
	
	$page[] = "</table>";
} elseif(isset($_POST['give_iban'])) {	
	# Scherm 5
	if(!isset($_POST['iban'])) {
		eb_getRelatieIbanById ( $id, $IBAN);
	} else {
		$IBAN = $_POST['iban'];
	}
	
	$page[] = "<input type='hidden' name='page' value='5'>";
	$page[] = "<table border=0>";
	$page[] = "<tr>";
	$page[] = "	<td>Op welk IBAN-nummer moet de declaratie worden uitbetaald?</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td><input type='text' name='iban' value='$IBAN'></td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td>&nbsp;</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td align='left'><input type='submit' name='add_toelichting' value='Vorige'></td>";
	$page[] = "	<td align='right'><input type='submit' name='last_check' value='Volgende'></td>";
	$page[] = "</tr>";
	$page[] = "</table>";	
} elseif(isset($_POST['add_toelichting'])) {
	# Scherm 4
	if(!isset($_POST['toelichting'])) {
		$toelichting = '';
	} else {
		$toelichting = urldecode($_POST['toelichting']);
	}
	
	foreach($_FILES['bewijs']['tmp_name'] as $key => $bewijs) {
		if($_FILES['bewijs']['name'][$key] != '') {
			$path_parts = pathinfo($_FILES['bewijs']['name'][$key]);
			$uniqeFilename = generateFilename().'.'.$path_parts['extension'];
			move_uploaded_file($bewijs, 'uploads/'.$uniqeFilename);
			$page[] = "<input type='hidden' name='bewijs[$key]' value='$uniqeFilename'>";
			$page[] = "<input type='hidden' name='originalName[$key]' value='". $path_parts['basename'] ."'>";
		}
	}
	
	$page[] = "<input type='hidden' name='page' value='4'>";
	$page[] = "<table border=0>";
	$page[] = "<tr>";
	$page[] = "	<td>Mocht je een toelichting voor de clustercoordinator willen toevoegen, dan kan je dat hieronder doen.</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td><textarea name='toelichting' cols='75' rows='10'>$toelichting</textarea></td>";
	$page[] = "</tr>";	
	$page[] = "<tr>";
	$page[] = "	<td>&nbsp;</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "<tr>";
	$page[] = "	<td align='left'><input type='submit' name='add_evidence' value='Vorige'></td>";
	$page[] = "	<td align='right'><input type='submit' name='give_iban' value='Volgende'></td>";
	$page[] = "</tr>";
	$page[] = "</table>";
} elseif(isset($_POST['add_evidence'])) {
	# Scherm 3
	if(!isset($_POST['regel'])) {
		$regels = array();
	} else {
		$regels = $_POST['regel'];
	}
	
	if(!isset($_POST['price'])) {
		$prijs = array();
	} else {
		$prijs = $_POST['price'];
	}
	
	$page[] = "<input type='hidden' name='page' value='3'>";
	$page[] = "<table border=0>";
	$page[] = "<tr>";
	$page[] = "	<td colspan='2'>Hieronder kan je voor de ".(count($regels) == 1 ? ' post' : count($regels).' posten') ." die je hebt ingevuld bewijsstukken uploaden. Mocht dat niet van belang zijn dan kan je gewoon doorgaan naar het volgende scherm.</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td colspan='2'>&nbsp;</td>";
	$page[] = "</tr>";
			
	foreach($regels as $key => $regel) {
		if($regel != '') {
			$page[] = "</tr>";
			$page[] = "	<td>". urldecode($regel) ."</td>";
			$page[] = "	<td><input type='file' name='bewijs[$key]'></td>";
			$page[] = "</tr>";
		}
	}
	
	$page[] = "<tr>";
	$page[] = "	<td colspan='2'>&nbsp;</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td align='left'><input type='submit' name='add_posten' value='Vorige'></td>";
	$page[] = "	<td align='right'><input type='submit' name='add_toelichting' value='Volgende'></td>";
	$page[] = "</tr>";
	$page[] = "</table>";
} elseif(isset($_POST['add_posten']) OR isset($_POST['new_item'])) {
	# Scherm 2
	$page[] = "<input type='hidden' name='page' value='2'>";
	$page[] = "<table border=0>";
	$page[] = "<tr>";
	$page[] = "	<td colspan='4'>Hieronder kan je specificeren wat je wilt declareren.<br>Door onderaan op '<i>Regel toevoegen</i>' te klikken verschijnt er een extra regel.<br><br>Als je klaar bent kan je met '<i>Controleren</i>' naar het volgende scherm gaan waar je 'bewijsstukken' kan uploaden.</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td colspan='4'>&nbsp;</td>";
	$page[] = "</tr>";
	
	$first = true;
	
	if(isset($_POST['regel'])) {
		$regel = $_POST['regel'];
	} else {
		$regel = array();
	}

	$regel[] = '';
	$totaal = 0;
	
	# Laat invoervelden zien
	foreach($regel as $key => $string) {
		if($string != '' OR $first) {
			$page[] = "	<tr>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td><input type='text' name='regel[$key]' value='". urldecode($string) ."' size='50'></td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td>&euro;&nbsp;<input type='text' name='price[$key]' value='". str_replace(',', '.', $_POST['price'][$key]) ."' size='5'></td>";
			$page[] = "	</tr>";
			
			$totaal = $totaal + 100*str_replace(',', '.', $_POST['price'][$key]);
		}

		# 1 lege regel is voldoende
		if($string == '' AND $first)	$first = false;
	}
	
	$page[] = "	<tr>";
	$page[] = "		<td colspan='3'>&nbsp;</td>";
	$page[] = "		<td align='right'><b>". formatPrice($totaal) ."</b></td>";
	$page[] = "	</tr>";	
	$page[] = "	<tr>";
	$page[] = "		<td colspan='4'>&nbsp;</td>";
	$page[] = "	</tr>";
	$page[] = "	<tr>";
	$page[] = "		<td>&nbsp;</td>";
	$page[] = "		<td colspan='3'>";
	$page[] = "		<table border=0 width=100%>";
	$page[] = "			<tr>";
	$page[] = "			<td width='33%' align='left'><input type='submit' name='choose_cluster' value='Vorige'>";
	$page[] = "			<td width='33%' align='center'><input type='submit' name='new_item' value='Declaratieregel toevoegen'>";
	$page[] = "			<td width='33%' align='right'><input type='submit' name='add_evidence' value='Volgende'>";
	$page[] = "		</tr>";
	$page[] = "		</table>";
	$page[] = "		</td>";
	$page[] = "		</tr>";
	$page[] = "</table>";
} else {
	# Scherm 1
	if(isset($_POST['cluster'])) {
		$cluster = $_POST['cluster'];
	} else {
		$cluster = '';
	}
	
	$page[] = "<input type='hidden' name='page' value='1'>";
	$page[] = "<table>";
	$page[] = "<tr>";
	$page[] = "	<td>Selecteer het cluster in het kader waarvan u deze declaratie doet.</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td>&nbsp;</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td><select name='cluster'>";
	$page[] = "<option value=''>Selecteer het cluster</option>";
	
	foreach($clusters as $id => $naam) {
		$page[] = "<option value='$id'". ($id == $cluster ? ' selected' : '').">Cluster $naam</option>";
	}
	
	$page[] = "</select></td>";
	$page[] = "</tr>";	
	$page[] = "<tr>";
	$page[] = "	<td>&nbsp;</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "	<td align='right'><input type='submit' name='add_posten' value='Volgende'></td>";
	$page[] = "</tr>";
	$page[] = "</table>";	
}

$page[] = "</form>";

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

# Aantekeningen zijn verplaatst naar aantekeningen.txt


?>
