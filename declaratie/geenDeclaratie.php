<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();

if(isset($_REQUEST['hash'])) {
	$hash = urldecode($_REQUEST['hash']);
	$dienst = $_REQUEST['d'];
	$voorganger = $_REQUEST['v'];

	# De hash klopt
	if(password_verify($dienst.'$'.$randomCodeDeclaratie.'$'.$voorganger,$hash)) {
		$dienstData = getKerkdienstDetails($dienst);
		//$firstData = getVoorgangerData($voorganger);
		//$secondData = getDeclaratieData($voorganger, $dienstData['start']);		
		//$voorgangerData = array_merge($firstData, $secondData);
		
		$dagdeel 				= formatDagdeel($dienstData['start']);
				
		if(isset($_POST['zeker_weten'])) {
			$page[] = "Uw declaratie staat geregistreed als 'afgezien' en is daarmee afgehandeld.";
			setVoorgangerDeclaratieStatus(9, $dienst);
		} elseif(isset($_POST['toch_niet'])) {
			$declaratieLink = generateDeclaratieLink($dienst, $voorganger);
						
			$page[] = "U ziet <u>niet</u> af van declaratie<br>";
			$page[] = "Klik <a href='$declaratieLink'>hier</a> om door te gaan naar de declaratie-omgeving";
		} else {
			$page[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";			
			$page[] = "<input type='hidden' name='d' value='$dienst'>";
			$page[] = "<input type='hidden' name='v' value='$voorganger'>";
			$page[] = "<input type='hidden' name='hash' value='". trim($_REQUEST['hash']) ."'>";
			$page[] = "<table border=0>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='2'><b>Afzien declaratie</b></td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='2'>&nbsp;</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='2'>U staat op het punt af te zien van het indienen van een declaratie voor de $dagdeel.<br>Weet u dat zeker?</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='2'>&nbsp;</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td align='left'><input type='submit' name='toch_niet' value='Nee, toch wel declaratie indienen'></td>";
			$page[] = "		<td align='right'><input type='submit' name='zeker_weten' value='Ja, heel zeker'>";
			$page[] = "	</tr>";
			$page[] = "</table>";			
			$page[] = "</form>";
		}		
	} else {
		# Direct-link om te declareren is niet correct
		$page[] = "Deze link is niet correct.<br><br>";
		$page[] = "Neem contact op met <a href='mailto:$ScriptMailAdress'>de webmaster</a>.";
	}
} else {
	$page[] = "Deze pagina is op incorrecte wijze aangeroepen";
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

# Aantekeningen zijn verplaatst naar aantekeningen.txt
?>
