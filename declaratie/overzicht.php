<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
#include_once('../include/config_mails.php');
include_once('../Classes/Declaratie.php');
include_once('../Classes/Member.php');

$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 38);
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['key'])) {	
    $declaratie = new Declaratie($_REQUEST['key']);
		
	$page = array_merge(array('<table border=0>'), showDeclaratieDetails($declaratie), array('</table>'));	
} else {
    $statusNaam[2] = 'Gemeentelid';
	$statusNaam[3] = 'CluCo';
	$statusNaam[6] = 'Afgekeurd';
	$statusNaam[4] = 'Penningmeester';
	$statusNaam[5] = 'Afgerond';
	$statusNaam[7] = 'Verwijderd';
	$statusNaam[8] = 'Doorgestuurd';

    if(isset($_REQUEST['status'])) {
        $hashes = Declaratie::getDeclaraties($_REQUEST['status']);
    } else {
        $hashes = Declaratie::getDeclaraties();
    }    
	
    if(count($hashes) > 0) {        
        $page[] = "<table>";
        $page[] = "<tr>";
        $page[] = "<td colspan='2'><b>Tijdstip</b></td>";
        $page[] = "<td colspan='2'><b>Cluster</b></td>";				
        $page[] = "<td colspan='2'><b>Indiener</b></td>";			
        $page[] = "<td colspan='2'><b>Bedrag</b></td>";
        $page[] = "<td><b>Status</b></td>";
        $page[] = "</tr>";
        
        $statusActive = array(3,4);
        $statusInactive = array(5,8);
        $statusDelete = array(6,7);
	
        foreach($hashes as $hash) {
            $declaratie = new Declaratie($hash);
            $indiener = new Member($declaratie->gebruiker);
            
            if(in_array($declaratie->status, $statusInactive)) {
                $class = 'inactief';
            } elseif(in_array($declaratie->status, $statusDelete)) {
                $class = 'ontrokken';
            } else {
                $class = '';			
            }
                    
            $page[] = "<tr>";
            $page[] = "<td>". time2str('d LLLL HH:mm', $declaratie->tijd) ."</td>";
            $page[] = "<td>&nbsp;</td>";			
            $page[] = "<td>". $clusters[$declaratie->cluster] ."</td>";
            $page[] = "<td>&nbsp;</td>";
            $page[] = "<td><a". ($class != '' ? " class='$class'" : '')." href='../profiel.php?id=". $indiener->id ."'>". $indiener->getName(5) ."</a></td>";
            $page[] = "<td>&nbsp;</td>";			
            $page[] = "<td><a". ($class != '' ? " class='$class'" : '')." href='?key=". $declaratie->hash ."'>". formatPrice($declaratie->totaal) ."</a></td>";
            $page[] = "<td>&nbsp;</td>";
            $page[] = "<td><a". ($class != '' ? " class='$class'" : '')." href='?status=". $declaratie->status ."'>". $statusNaam[$declaratie->status] ."</a></td>";	
            $page[] = "</tr>";
        }
        $page[] = "</table>";
    }
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