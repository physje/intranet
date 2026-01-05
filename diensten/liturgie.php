<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Logging.php');

$requiredUserGroups = array(1, 11, 28, 52);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$aantalMaanden = 1;

if(isset($_REQUEST['dienstID'])) {
    if(isset($_REQUEST['save'])) {
        $dienst = new Kerkdienst($_REQUEST['dienstID']);
        $dienst->liturgie = $_REQUEST['liturgieTekst'];
		
		if($dienst->save()) {
			$text[] = "Liturgie succesvol opgeslagen!";
			toLog('Litugie ('. time2str("D j M", $dienst->start) .') bijgewerkt');
		} else {
			$text[] = "Helaas, er ging iets niet goed met het opslaan van de liturgie";
			toLog('Liturgie ('. time2str("D j M", $dienst->start) .') konden niet worden opgeslagen', 'error');
		}
    } else {        
        $dienst = new Kerkdienst($_REQUEST['dienstID']);
        $liturgie = $dienst->liturgie;

        $text[] = "<form method='post' action='$_SERVER[PHP_SELF]?dienstID=".$_REQUEST['dienstID']."'>";

        if(!$liturgie) {
            # Geen liturgie aanwezig voor geselecteerde dienst, nieuwe invoeren
            $text[] = "Voer hieronder de nieuwe liturgie in voor de dienst van ". time2str("j F Y", $dienst->start). " om ". date("H:i", $dienst->start). ":<br><br>";
            $text[] = "<textarea rows='30' name='liturgieTekst' cols='50' font: normal 1em Verdana, sans-serif></textarea>";
        } else {
            # Liturgie gevonden voor geselecteerde dienst, bijwerken
            $text[] = "Pas hieronder de liturgie aan voor de dienst van ". time2str("j F Y",  $dienst->start). " om ". date("H:i", $dienst->start). ":<br><br>";
            $text[] = "<textarea rows='30' name='liturgieTekst' cols='50'>". $liturgie. "</textarea>";
        }

        # Sla de nieuwe liturgie op door op de save knop te drukken
        $text[] = "<p class='after_table'><input type='submit' name='save' value='Opslaan'></p>";
        $text[] = "</form>";
    }
} else {
    # Haal alle kerkdiensten binnen een tijdsvak op
    $diensten = Kerkdienst::getDiensten(mktime(0,0,0), mktime(date("H"),date("i"),date("s"),(date("n")+$aantalMaanden)));

    # Bouw formulier op
    $text[] = "Klik op de 'edit' link achter de kerdienst waarvan de liturgie moet worden ingevoerd of aangepast.<br><br>";
    $text[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
    $text[] = "<input type='hidden' name='blokken' value='$aantalMaanden'>";
    $text[] = "<table>";
	$text[] = "<thead>";
    $text[] = "<tr>";
    $text[] = "	<th>Datum</th>";
    $text[] = "	<th>Start</th>";
    $text[] = "	<th>Bijzonderheid</th>";
    $text[] = "	<th>Bijwerken</th>";
    $text[] = "</tr>";
    $text[] = "</thead>";

    foreach($diensten as $dienstID) {
        $dienst = new Kerkdienst($dienstID);

        $text[] = "<tr>";
        $text[] = "	<td align='right'>". time2str("D j M", $dienst->start) ."</td>";
        //$text[] = "	<td align='right'>". date("d-m-Y", $data['start']) ."</td>";
        $text[] = "	<td>". date('H:i', $dienst->start) ."</td>";
        $text[] = "	<td>". $dienst->opmerking ."</td>";
        $text[] = " <td><a href='?dienstID=$dienstID'>edit</a></td>";
        $text[] = "</tr>";
    }
    $text[] = "</table>";
    $text[] = "</form>";
}

echo showCSSHeader(array('default', 'table_default'));
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>Liturgie</h1>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();
?>
