<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 36);
include($cfgProgDir. "secure.php");

// Opslaan van het nieuw ingevoerde contact
if (isset($_REQUEST['save_new_contact'])) {
    if(!empty($_POST["fcategorie"]) AND !empty($_POST["fnaam"]) AND !empty($_POST["fmailadres"])) {
        $categorie = $_POST["fcategorie"];
        if ($categorie == 'Kies een categorie'|| $categorie =='anders...') {
            $categorie = $_POST['fcategorienew'];
        }
        if (!addGebedkalItem($categorie, $_POST["fnaam"], $_POST["fmailadres"], $_POST["fopmerking"])) {
            echo "failed to save contact"; // raise error message
        }
    } else {
        echo "Missende variabelen"; // raise error message
    }
}

// Updaten van een bestaand contact
if (isset($_REQUEST['update_contact'])) {
    $categorie = $_POST["fcategorie"];
    if ($categorie == 'Kies een categorie'|| $categorie =='anders...') {
        $categorie = $_POST['fcategorienew'];
    }
    if (!updateGebedkalItemById($_REQUEST['ID'], $categorie, $_POST["fnaam"], $_POST["fmailadres"], $_POST["fopmerking"])) {
        echo "Failed to update contact"; // raise error message
    }
}

// Verwijder contact
if (isset($_REQUEST['RemoveId'])) {
    if (!removeGebedkalItem($_REQUEST['RemoveId'])) {
        echo "verwijderen is mislukt";
    }
}

// Get gebedskalender contacten
$contacten = getGebedkalAllItems();

// Sorteer de resultaten op categorie en vervolgens op contactpersoon
$kalCategorie = array_column($contacten, $GebedKalCategorie);
$kalContactpersoon = array_column($contacten, $GebedKalContactPersoon);
array_multisort($kalCategorie, SORT_ASC, $kalContactpersoon, SORT_ASC, $contacten);

if(isset($_REQUEST['edit_page'])) {
    $block[] = "<h1>Gebedskalender mailadressen overzicht - edit</h1>";
    $block[] = "In het eerste veld kan een nieuw contactpersoon opgeslagen worden, in het tweede veld kan de lijst met contactpersonen worden aangepast. <br><br>";
    $categories = array_unique($kalCategorie);

    // Voeg nieuwe contact toe formulier
    $block[] = "<form method='post' action='$_SERVER[PHP_SELF]' >";
    $block[] = "<fieldset><legend><b>Nieuw contact toevoegen</b></legend>";
    $block[] = "<br><table>";
    $block[] = "<tr><td><label type='label'>Categorie</label></td>";
    $block[] = "<td></td><td><select name='fcategorie' onchange='CheckCategorie(this.value);'>";
    $block[] = "<option>Kies een categorie</option>";
    foreach ($categories as $categorie) {
        $block[] = "<option>".$categorie."</option>";
    }
    $block[] = "<option>anders...</option>";
    $block[] = "</select></td>";
    $block[] = "<td><input type='text' name='fcategorienew' id='categorie' style='display:none;'></td></tr>";
    $block[] = "<tr><td><label type='label'>Naam</label></td>";
    $block[] = "<td width=50></td><td colspan=2><input type='text' name='fnaam' size=45></td></tr>";
    $block[] = "<tr><td><label type='label'>Mailadres</label></td>";
    $block[] = "<td></td><td colspan=2><input type='text' name='fmailadres' size=45></td></tr>";
    $block[] = "<tr><td><label type='label'>Opmerking</label></td>";
    $block[] = "<td></td><td colspan=2><input type='text' name='fopmerking' size=45></td></tr>";
    $block[] = "<tr><td></td><td></td><td><input type='submit' value='Nieuw contact opslaan' name='save_new_contact'></td></tr>";
    $block[] = "</table>";
    $block[] = "</fieldset></form>";

    // Pas bestaande contacten aan formulier
    $block[] = "<form method='post' action='$_SERVER[PHP_SELF]' >";
    $block[] = "<fieldset><legend><b>Pas overzicht aan</b></legend>";
    $block[] = "<br><table>";


    $block[] = "<tr><th>".ucfirst($GebedKalCategorie)."</th><th>".ucfirst($GebedKalContactPersoon)."</th><th>".ucfirst($GebedKalMailadres)."</th><th>".ucfirst($GebedKalOpmerkingen)."</th></tr>";

    foreach ($contacten as $key => $contact) {
        $block[] = "<tr>";
        $block[] = "<td width=50>" . $contacten[$key][$GebedKalCategorie] . "</td>";
        $block[] = "<td>" . $contacten[$key][$GebedKalContactPersoon] . "</td>";
        $block[] = "<td>" . strtolower($contacten[$key][$GebedKalMailadres]) . "</td>";
        $block[] = "<td>" . $contacten[$key][$GebedKalOpmerkingen] . "</td>";
        $block[] = "<td width=50><a href='?EditRow=".$key."'>Edit</a></td>";
        $block[] = "<td width=50><a href='?RemoveId=".$contacten[$key][$GebedsKalId]."'>Verwijder</a></td>";
        $block[] = "</tr>";
    }

    $block[] = "</table>";
    $block[] = "</fieldset></form>";

    // Knop om naar mailadressen overzicht te gaan
    $block[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
    $block[] = "<br><input type='submit' value='Terug' name='main'>";
    $block[] = "</form>";
} else if (isset($_REQUEST['EditRow'])) {
    $categories = array_unique($kalCategorie);

    // Maak html block waar gegevens van de contactpersoon wordt aangepast
    $block[] = "<form method='post' action='$_SERVER[PHP_SELF]?ID=".$contacten[$_REQUEST['EditRow']][$GebedsKalId]."' >";
    $block[] = "<fieldset><legend><b>Pas contact aan</b></legend>";
    $block[] = "<br><table>";
    $block[] = "<tr><td><label type='label'>Categorie</label></td>";
    $block[] = "<td></td><td><select name='fcategorie' onchange='CheckCategorie(this.value);'>";
    $block[] = "<option>Kies een categorie</option>";
    foreach ($categories as $categorie) {
        if ($contacten[$_REQUEST['EditRow']][$GebedKalCategorie] == $categorie) {
            $block[] = "<option selected>".$categorie."</option>";
        } else {
            $block[] = "<option>".$categorie."</option>";
        }
    }
    $block[] = "<option>anders...</option>";
    $block[] = "</select></td>";
    $block[] = "<td><input type='text' name='fcategorienew' id='categorie' style='display:none;'></td></tr>";
    $block[] = "<tr><td><label type='label'>Naam</label></td>";
    $block[] = "<td width=50></td><td colspan=2><input type='text' name='fnaam' size=45 value='".$contacten[$_REQUEST['EditRow']][$GebedKalContactPersoon]."'></td></tr>";
    $block[] = "<tr><td><label type='label'>Mailadres</label></td>";
    $block[] = "<td></td><td colspan=2><input type='text' name='fmailadres' size=45 value='".$contacten[$_REQUEST['EditRow']][$GebedKalMailadres]."'></td></tr>";
    $block[] = "<tr><td><label type='label'>Opmerking</label></td>";
    $block[] = "<td></td><td colspan=2><input type='text' name='fopmerking' size=45 value='".$contacten[$_REQUEST['EditRow']][$GebedKalOpmerkingen]."'></td></tr>";
    $block[] = "<tr><td></td><td></td><td><input type='submit' value='Wijziging opslaan' name='update_contact'></td></tr>";
    $block[] = "</table>";
    $block[] = "</fieldset></form>";
} else {
    // Maak html block met de contactgegevens
    $block[] = '<h1>Gebedskalender mailadressen overzicht</h1>';
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
    $block[] = "<br>Druk op volgende knop om gegevens te wijzigen of nieuwe contacten toe te voegen <input type='submit' value='Wijzig contactgegevens' name='edit_page'>";
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

<script type="text/javascript">
function CheckCategorie(val){
 var element=document.getElementById('categorie');
 if(val=='Kies een categorie'||val=='anders...')
   element.style.display='block';
 else
   element.style.display='none';
}
</script>