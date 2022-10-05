<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');

$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$aantalMaanden = 1;

if(isset($_REQUEST['dienstID'])) {

    if(isset($_REQUEST['save'])) {
        $sql = "UPDATE $TableDiensten SET ";
		$sql .= "$DienstLiturgie = '". urlencode($_REQUEST['liturgieTekst']) ."' ";
		$sql .= "WHERE $DienstID = '". $_REQUEST['dienstID'] ."'";
		
		if(mysqli_query($db, $sql)) {
			$text[] = "Liturgie succesvol opgeslagen!";
			toLog('info', $_SESSION['ID'], '', 'Litugie ('. $_REQUEST['dienstID'] .') bijgewerkt');
		} else {
			$text[] = "Helaas, er ging iets niet goed met het opslaan van de liturgie";
			toLog('error', $_SESSION['ID'], '', 'Liturgie ('. $_REQUEST['dienstID'] .') konden niet worden opgeslagen');
		}
    } else {
        $liturgie = getLiturgie($_REQUEST['dienstID']);
        $dienstInfo = getKerkdienstDetails($_REQUEST['dienstID']);

        $text[] = "<form method='post' action='$_SERVER[PHP_SELF]?dienstID=".$_REQUEST['dienstID']."'>";

        if(!$liturgie) {
            # Geen liturgie aanwezig voor geselecteerde dienst, nieuwe invoeren
            $text[] = "Voer hieronder de nieuwe liturgie voor de dienst in van ". date("j F Y", $dienstInfo['start']). ", ". date("H:i", $dienstInfo['start']). ":<br><br>";
            $text[] = "<textarea rows='30' name='liturgieTekst' cols='50' font: normal 1em Verdana, sans-serif></textarea>";
        } else {
            # Liturgie gevonden voor geselecteerde dienst, bijwerken
            $text[] = "Pas hieronder de liturgie aan voor de dienst van ". date("j F Y", $dienstInfo['start']). ", ". date("H:i", $dienstInfo['start']). ":<br><br>";
            $text[] = "<textarea rows='30' name='liturgieTekst' cols='50'>". $liturgie. "</textarea>";
        }

        # Sla de nieuwe liturgie op door op de save knop te drukken
        $text[] = "<br><br><input type='submit' name='save' value='Opslaan'></form>";
    }

} else {
    # Haal alle kerkdiensten binnen een tijdsvak op
    $diensten = getKerkdiensten(mktime(0,0,0), mktime(date("H"),date("i"),date("s"),(date("n")+$aantalMaanden)));

    # Bouw formulier op
    $text[] = "Klik op de 'edit' link achter de kerdienst waarvan de liturgie moet worden ingevoerd of aangepast.<br><br>";
    $text[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
    $text[] = "<input type='hidden' name='blokken' value='$aantalMaanden'>";
    $text[] = "<table cellspacing='10'>";
    $text[] = "<tr>";
    $text[] = "	<td><b>Datum</b></td>";
    $text[] = "	<td><b>Start</b></td>";
    $text[] = "	<td><b>Bijzonderheid</b></td>";
    $text[] = "	<td><b>Bijwerken</b></td>";
    $text[] = "</tr>";

    foreach($diensten as $dienst) {
        $data = getKerkdienstDetails($dienst);

        $text[] = "<tr>";
        $text[] = "	<td align='right'>". time2str("%a %e %b", $data['start']) ."</td>";
        //$text[] = "	<td align='right'>". date("d-m-Y", $data['start']) ."</td>";
        $text[] = "	<td>". date('H:i', $data['start']) ."</td>";
        $text[] = "	<td>". $data['bijzonderheden'] ."</td>";
        $text[] = " <td><a href='?dienstID=$dienst'>edit</a></td>";
        $text[] = "</tr>";
    }
    $text[] = "</table>";
    $text[] = "</form>";
}

echo $HTMLHeader;
echo implode(NL, $text);
echo $HTMLFooter;
?>