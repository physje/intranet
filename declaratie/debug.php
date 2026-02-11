<?php
/**
 * Eenvoudig script om declaratie's te debuggen
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Declaratie.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Mysql.php');

$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(in_array($_SESSION['useID'], array(1))) {	
    if(isset($_REQUEST['key'])) {
        $declaratie = new Declaratie($_REQUEST['key']);

        $page[] = "<table border=1>";

        foreach($declaratie as $key => $value) {
            $page[] = "<tr>";
            $page[] = " <td valign='top'>$key</td>";
            if(is_array($value)) {
                $first = true;
                foreach($value as $sub_key => $sub_value) {
                    if(!$first) {
                        $page[] = "<tr>";
                        $page[] = " <td>&nbsp;</td>";
                    }
                    $page[] = " <td valign='top'>$sub_key</td>";
                    if(is_array($sub_value)) {
                        $page[] = " <td valign='top'>". implode('|', $sub_value) ."</td>";
                    } else {
                        $page[] = " <td valign='top'>$sub_value</td>";
                    }
                    if(!$first) {
                        $page[] = "</tr>";                    
                    }
                    $first = false;                
                }
            } else {
                $page[] = " <td colspan='2' valign='top'>$value</td>";
            }        
            $page[] = '</tr>';
        }
        $page[] = "</table>";
    } else {
        $page[] = 'Geen declaratie gedefinieerd';
    }
} else {
    $page[] = 'Onvoldoende rechten';
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