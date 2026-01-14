<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Logging.php');

# 1 jaar geleden
$grens = mktime(0, 0, 0, (date("n")-12));

$diensten = Kerkdienst::getDiensten(0, $grens);

foreach($diensten as $d) {
    $dienst = new Kerkdienst($d);
    $dienst->delete();

    toLog('Diensten van voor '. date('d-m-y', $grens).' verwijderd', 'debug');
}

$db = new Mysql();

# Selecteer alles in de planningsdatabase waarvan de dienst niet meer bestaat
$sql_planning = "SELECT `planning`.`dienst` FROM `planning` LEFT JOIN `kerkdiensten` ON `planning`.`dienst` = `kerkdiensten`.`id` WHERE `kerkdiensten`.`id` IS NULL GROUP BY `dienst`";
$data = $db ->select($sql_planning);

if(count($data) > 0) {
    $diensten = array_column($data, 'dienst');

    $sql_diensten = "DELETE FROM `planning` WHERE `dienst` = '". implode("' OR `dienst` = '", $diensten) ."'";

    echo $sql_diensten;

    if($db->query($sql_diensten)) {
        toLog('Planning opgeschoond na verwijderen diensten', 'debug');
    } else {
        toLog('Kon planning niet opschonen na verwijderen diensten', 'error');
    }
}

# Selecteer alles in de planningsdatabase waarvan de dienst niet meer bestaat
$sql_planning = "SELECT `planning`.`commissie` FROM `planning` LEFT JOIN `roosters` ON `planning`.`commissie` = `roosters`.`id` WHERE `roosters`.`id` IS NULL GROUP BY `commissie`";
$data = $db ->select($sql_planning);

if(count($data) > 0) {
    $roosters = array_column($data, 'commissie');

    $sql_roosters = "DELETE FROM `planning` WHERE `commissie` = '". implode("' OR `commissie` = '", $roosters) ."'";

    if($db->query($sql_roosters)) {
        toLog('Planning opgeschoond na verwijderen roosters', 'debug');
    } else {
        toLog('Kon planning niet opschonen na verwijderen roosters', 'error');
    }
}

toLog('Database opgeschoond voor diensten, roosters en planning tot '. date('d-m-y', $grens));

?>