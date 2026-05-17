<?php
/**
 * Eenvoudig script wat door GitHub Webhook wordt aangeroepen elke keer dat er een commit is.
 * Commits staat gelijk aan uploads, dus als dit script een teller ophoogt
 * Kan ik in de page-title laten zien welke versie we nu op zitten
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */

$payload = file_get_contents('php://input');

if(strlen($payload) > 1) {
    $file = '../include/version.php';
    include($file);
    $VersionCount++;

    $githubData = json_decode($payload, true);
    $bericht = $githubData["commits"][0]['message'];
    $tijdstip = $githubData["commits"][0]['timestamp'];

    $oudeFile = file($file, FILE_IGNORE_NEW_LINES);
    $closeTag       = array_pop($oudeFile);
    $laatsteRegel   = array_pop($oudeFile);
    
    $v = fopen($file, 'w+');

    foreach($oudeFile as $regel) {
        fwrite($v, $regel."\n");
    }

    fwrite($v, "# $laatsteRegel \n");
    fwrite($v, '$VersionCount = '. $VersionCount ."; # $bericht ($tijdstip)"."\n");
    fwrite($v, $closeTag);    
    fclose($v);
}

?>