<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Gebedspunt.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');

$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 36);
include($cfgProgDir. "secure.php");

if(isset($_POST['save'])) {	
	$blockLinks = '';
	
	foreach($_POST['gebedspunt'] as $id => $tekst) {
        $punt = new Gebedspunt($id);
        $store = false;

        if($punt->dag != $_POST['dag'][$id]) {
            toLog('Dag van gebedspunt '. $id .' is verplaatst van '. $punt->dag .' naar '. $_POST['dag'][$id], 'debug');
            $punt->dag = $_POST['dag'][$id];
            $store = true;
        }

        if($punt->maand != $_POST['maand'][$id]) {
            toLog('Maand van gebedspunt '. $id .' is verplaatst van '. $punt->maand .' naar '.$_POST['maand'][$id], 'debug');
            $punt->dag = $_POST['dag'][$id];
            $store = true;
        }

        if($punt->maand != $_POST['jaar'][$id]) {
            toLog('Jaar van gebedspunt '. $id .' is verplaatst van '. $punt->jaar .' naar '.$_POST['jaar'][$id], 'debug');
            $punt->dag = $_POST['dag'][$id];
            $store = true;
        }

        if($punt->gebedspunt != $tekst) {
            toLog('Gebedspunt van '. $punt->dag .'-'. $punt->maand .'-'. $punt->jaar .' ('. $id .') gewijzigd');
            $punt->gebedspunt = $tekst;
            $store = true;
        }

        if($store && !$punt->save()) {
            $blockLinks .= "Er ging iets mis met het opslaan van het gebedspunt voor $id<br>";
		    toLog('Gebedspunt '. $id .' kon niet worden gewijzigd', 'error');
        }        
	}
	
	if($blockLinks == '') {
		$blockLinks = "Punten zijn opgeslagen";
	}	
} else {
	$punten = Gebedspunt::getPunten(date("Y-m-d"), date("Y-m-d", mktime(0,0,1,date("n"),date("j"), (date("Y")+1))));
	
	$blockLinks = "<form method=post>". NL;	
	$blockLinks .= "<table>". NL;
	
	foreach($punten as $id) {
        $punt = new Gebedspunt($id);
		$blockLinks .= "<tr>". NL;
		
        # Dag
        $blockLinks .= "	<td valign='top'><select name='dag[$id]'>".NL;
        for($d=1 ; $d<32 ; $d++) {		
            $blockLinks .= "<option value='$d'". ($d == $punt->dag ? ' selected' : '') .">$d</option>".NL;
        }
        $blockLinks .= "</select></td>". NL;

        # Maand
        $blockLinks .= "	<td valign='top'><select name='maand[$id]'>".NL;
        foreach($maandArrayLang as $nr => $naam) {		
            $blockLinks .= "<option value='$nr'". ($nr == $punt->maand ? ' selected' : '') .">$naam</option>".NL;
        }
        $blockLinks .= "</select></td>". NL;

        # Jaar
        $blockLinks .= "	<td valign='top'><select name='jaar[$id]'>".NL;
        for($j = (date("Y")-1) ; $j <= (date("Y")+1) ; $j++) {
            $blockLinks .= "<option value='$j'". ($j == $punt->jaar ? ' selected' : '') .">$j</option>".NL;		
        }	
        $blockLinks .= "</select></td>". NL;
        
		$blockLinks .= "	<td valign='top'><textarea name='gebedspunt[$id]' rows=4 cols=100>". $punt->gebedspunt ."</textarea></td>". NL;
		$blockLinks .= "</tr>".NL;
	}	
	$blockLinks .= "</table>". NL;
	$blockLinks .= "<p class='after_table'><input type='submit' name='save' value='Opslaan'></p>". NL;
	$blockLinks .= "</form>". NL;
}


# Pagina tonen
echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>". $blockLinks ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>