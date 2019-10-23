<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

if(isset($_REQUEST['draad'])) {
	if($_REQUEST['draad'] == 'predikant') {
		if(isset($_POST['send_link'])) {
			$dienst = $_POST['dienst'];
			$dienstData = getKerkdienstDetails($dienst);
			$voorganger = $dienstData['voorganger_id'];
			$voorgangerData = getVoorgangerData($voorganger);
			
			if($voorgangerData['declaratie'] == 0) {
				$page[] = $dienstData['voorganger'] . ' kan niet declareren (er komt een algemene melding dat er niet gedeclareerd kan worden';
			} else {
				$page[] = $dienstData['voorganger'] .' -> '. $voorgangerData['mail'];
			}
		} else {
			$page[] = "Om u te identificeren zal zometeen naar het bij ons bekende email-adres van de voorganger van die dienst een link worden gestuurd.<br>";
			$page[] = "<br>";
			$page[] = "Door het volgen van die link komt u uit op de juiste plek in de declaratie-omgeving.<br>";
			$page[] = "<br>";
			$page[] = "Voor welke dienst wilt u een declaratie indienen?<br>";
			$page[] = "<br>";		
			$page[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
			$page[] = "<input type='hidden' name='draad' value='". $_REQUEST['draad'] ."'>";
			$page[] = "<select name='dienst'>";
			$page[] = "<option value=''>Selecteer de dienst</option>";
			
			# 3 maanden terug
			$startTijd = mktime(0, 0, 0, (date("n")-3));
			
			# 23:59:59 vandaag
			$eindTijd = mktime(23, 59, 50);
			$diensten = getKerkdiensten($startTijd, $eindTijd);
			
			
			foreach(array_reverse($diensten) as $dienst) {
				$dienstData = getKerkdienstDetails($dienst);
				
				if(date("H", $dienstData['start']) < 12) {
					$dagdeel = 'ochtenddienst';
				} elseif(date("H", $dienstData['start']) < 18) {
					$dagdeel = 'middagdienst';
				} else {
					$dagdeel = 'avonddienst';
				}			
				
				$page[] = "<option value='$dienst'>$dagdeel ". date('d M', $dienstData['start']) ."</option>";
			}
			$page[] = "</select><br>";
			$page[] = "<br>";
			$page[] = "<input type='submit' name='send_link' value='Verstuur link'>";
			$page[] = "</form>";
		}
		
	} elseif($_REQUEST['draad'] == 'gemeentelid') {
		$page[] = "Momenteel is dat nog niet mogelijk.<br>";
		$page[] = "De wens is er wel, dus hopelijk op een later moment.<br>";
	}
} else {
	$page[] = "In welke hoedanigheid wilt u een declaratie doen?<br>";
	$page[] = "<ul>";
	$page[] = "<li><a href='?draad=predikant'>Gastpredikant</a></li>";
	$page[] = "<li><a href='?draad=gemeentelid'>Gemeentelid</a></li>";
	$page[] = "</ul>";
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
