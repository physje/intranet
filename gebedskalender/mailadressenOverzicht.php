<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 36);
include($cfgProgDir. "secure.php");

// Get gebedskalender contacten
$contacten = getGebedkalAllItems();

// Sorteer de resultaten op categorie en vervolgens op contactpersoon
$kalCategorie = array_column($contacten, $GebedKalCategorie);
$kalContactpersoon = array_column($contacten, $GebedKalContactPersoon);
array_multisort($kalCategorie, SORT_ASC, $kalContactpersoon, SORT_ASC, $contacten);

if(isset($_REQUEST['edit'])) {
    $block[] = "TODO: webpagina met mogelijkheid tot toevoegen van nieuwe contactpersonen en wijzigen van bestaande contactpersonen";
} else {
    // Maak html block met de contactgegevens
    $block[] = '<h3>Gebedskalender mailadressen overzicht</h3>';
    $block[] = 'Overzicht met contactpersonen en bijbehorende emailadressen die gebruikt kunnen worden voor het vragen naar input voor de gebedskalender<br><br><br>';
    $block[] = "<table border=1>";
    $block[] = "<tr><th>".ucfirst($GebedKalCategorie)."</th><th>".ucfirst($GebedKalContactPersoon)."</th><th>".ucfirst($GebedKalMailadres)."</th><th>".ucfirst($GebedKalOpmerkingen)."</th></tr>";

    foreach ($contacten as $key => $contact) {
        $block[] = "<tr>";
        
        // Als categorie meerdere keren voorkomt, een keer laten zien en cellen mergen
        if ($key == 0 OR $contacten[$key][$GebedKalCategorie] != $contacten[$key - 1][$GebedKalCategorie] ) {
            if ($key == (count($contacten) -1)) {
                $block[] = "<td><b>" . $contacten[$key][$GebedKalCategorie] . "</b></td>"; 
            }
            else {
                for ($i = 1; ($key + $i) <= count($contacten); $i++) {
                    if ($contacten[$key][$GebedKalCategorie] != $contacten[$key + $i][$GebedKalCategorie]) {
                        $block[] = "<td rowspan='". strval($i) ."'><b>" . $contacten[$key][$GebedKalCategorie] . "</b></td>"; 
                        break;
                    }
                }
            }
        }

        $block[] = "<td>" . $contacten[$key][$GebedKalContactPersoon] . "</td>";
        $block[] = "<td>" . strtolower($contacten[$key][$GebedKalMailadres]) . "</td>";
        $block[] = "<td>" . $contacten[$key][$GebedKalOpmerkingen] . "</td>";
        $block[] = "</tr>";
        
    }
    $block[] = "</table>";

    // Knop om nieuw contact toe te voegen
    $block[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
    $block[] = "<br>Druk op volgende knop om gegevens te wijzigen of nieuwe contacten toe te voegen <input type='submit' value='Wijzig contactgegevens' name='edit'>";
    $block[] = "</form>";
}

// Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '	<td valign="top">'.NL;
echo showBlock(implode($block, NL), 100);
echo '	</td>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;

?>