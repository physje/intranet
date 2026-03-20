<?php
include_once('../../include/functions.php');
include_once('../../include/EB_functions.php');
include_once('../../include/HTML_TopBottom.php');
include_once('../../include/config.php');
include_once('../../Classes/Declaratie.php');
include_once('../../Classes/Member.php');
include_once('../../Classes/Logging.php');

# Initialiseren
$rescue = $page = array();
$test = false;

if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP) OR $test) {
    toLog('Oude declaratie-bijlages opgeruimd');

    # Status waarvan de bijlages sowieso bewaard moeten blijven
    # @see Declaratie::status voor de omschrijving van elke status
    $keepStatus = array(0, 1, 2, 3, 4);

    # Status waarvan de bijlages na verloop van tijd verwijderd mogen worden
    $cleanStatus = array(5, 6, 7, 8);

    # Bijlages die ouder zijn dan 3 maanden (en status uit $cleanStatus hebben) mogen verwijderd wordne
    $grens = mktime(0,0,0,date('n')-3);

    # Directories
    $uploadDir      = '../uploads/';
    $uploadDirNew   = '../uploads_old/'. date('Y').'.'.substr('0'.date('n'), -2) .'/';

    # Nieuwe directory aanmaken
    if(!file_exists($uploadDirNew)) {
        mkdir($uploadDirNew);

        $index = fopen($uploadDirNew.'index.php', 'w');
        fwrite($index, '<?php'."\n");
        fwrite($index, '$url="Location: ../../";'."\n");
        fwrite($index, 'header($url);'."\n");
        fclose($index);
    }

    # Sowieso bewaren
    foreach($keepStatus as $status) {
        $hashes = Declaratie::getDeclaraties($status);

        foreach($hashes as $hash) {
            $declaratie = new Declaratie($hash);
            
            foreach($declaratie->bijlagen as $file => $name) {
                $rescue[] = $file;
            }
        }
    }

    # Bewaren als niet te oud
    foreach($cleanStatus as $status) {
        $hashes = Declaratie::getDeclaraties($status);

        foreach($hashes as $hash) {
            $declaratie = new Declaratie($hash);

            # Als de laatste actie minder dan 3 maanden geleden is
            # Bewaar de bijlage
            if($declaratie->lastAction > $grens) {            
                foreach($declaratie->bijlagen as $file => $name) {
                    $rescue[] = $file;
                }
            }
        }
    }

    # Maak een array met alle bestanden in de upload-map
    $upload = opendir($uploadDir);
    while (false !== ($entry = readdir($upload))) {
        if ($entry !=  '.' && $entry != '..' && $entry != 'index.php') {
            $files[] = $entry;
        }
    }

    # Doorloop alle bestanden in de upload-directory
    foreach($files as $file) {
        # En kijk of ze voorkomen in de lijst met te bewaren bijlages
        # Zo ja, bewaar ze
        # Zo nee, verwijder ze
        if(!in_array('uploads/'.$file, $rescue)) {        
            $page[] = $file .' verplaatst<br>';
            rename($uploadDir.$file, $uploadDirNew.$file);
            toLog('declaratie-bijlage '. $file .' verwijderd', 'debug');
        }
    }
}

if(count($page) == 0) {
    $page[] = 'Geen bijlages te verplaatsen';
}

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $page).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>