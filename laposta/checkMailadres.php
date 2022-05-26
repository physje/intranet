<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');

# Doorloop een rij mailadressen en kijk of dit 3GK-adressen zijn

$start = getParam('start', 0);
$stap = 1;

$wijk = 'D';

$filename = 'wijk_'. $wijk .'.csv';

$adres[] = '';
$adres[] = '';
$adres[] = '';
$adres[] = '';
$adres[] = '';


# Als de stapgrootte netzo groot is als het aantal resultaten
# de pagina opnieuw opvragen
if(count($adres) > $start) {
	echo '<html>';
	echo '<head>';
	echo '	<meta http-equiv="refresh" content="3; url=?start='. ($start+$stap) .'" />';
	echo '</head>';
	echo '<body>';
} else {
	echo '<b>Laatste keer</b>';
}

$veld[] = 'E-mailadres';	
$veld[] = 'voornaam';
$veld[] = 'tussenvoegsel';
$veld[] = 'achternaam';	
$veld[] = 'geslacht';
$veld[] = '3GK-adres';

if($start == 0) {
	$fp = fopen($filename, 'w+');
	fwrite($fp, implode(',', $veld)."\n");
} else {
	$fp = fopen($filename, 'a+');
}

$adressen = array_slice($adres, $start, $stap);

foreach($adressen as $mailadres) {
	$veld = array();
	
	if(lp_onList($LPLedenListID, $mailadres)) {
		echo $mailadres .' is wel bekend';
		$data = lp_getMemberData($LPLedenListID, $mailadres);
		if($data['state'] != 'active') {
			echo ', maar '. $data['state'];
		} elseif($data['custom_fields']['wijk'] != $wijk) {
			echo ', maar '. $data['custom_fields']['wijk'];
		}
				
		echo '<br>';
		
		$veld[] = $mailadres;	
		$veld[] = $data['custom_fields']['voornaam'];
		$veld[] = $data['custom_fields']['tussenvoegsel'];
		$veld[] = $data['custom_fields']['achternaam'];	
		$veld[] = $data['custom_fields']['geslacht'];	;
		$veld[] = 'Ja';		
	} else {
		echo $mailadres .' is <b>niet</b> bekend<br>';
		
		$veld[] = $mailadres;	
		$veld[] = '';
		$veld[] = '';
		$veld[] = '';	
		$veld[] = '';
		$veld[] = 'Nee';
	}
		
	fwrite($fp, implode(',', $veld)."\n");
}

fclose($fp);

?>