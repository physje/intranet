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


$file = '../include/version.php';

include($file);

$VersionCount++;

$v = fopen($file, 'w+');
fwrite($v, "<?php\n");
fwrite($v, '$VersionCount = '. $VersionCount .";\n");
fwrite($v, "?>\n");
fclose($v);

?>