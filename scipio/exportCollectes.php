<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

if(date('n') == 12) {
	$jaar = date('Y')+1;
} else {
	$jaar = date('Y');
}

$start = mktime(0, 0, 0, 1, 1, $jaar);
$eind = mktime(23, 59, 59, 12, 31, $jaar);
$dag = 24*60*60;

if($start < time()) {
	$start = time();
}

$file_name = 'collectes_'. date('Ymd', $start) .'_tm_'. date('Ymd', $eind) .'.csv';

$kop[] = 'Naam';
$kop[] = 'Van';
$kop[] = 'Tot';
$kop[] = 'Omschrijving';
$kop[] = 'Video URL';
$kop[] = 'Website URL';
$kop[] = 'Opbrengst weergave';
$kop[] = 'Doelbedrag';

$output  = implode(";", $kop)."\n";

$diensten = getKerkdiensten($start, $eind);

foreach($diensten as $dienst) {	
 	$data = getKerkdienstDetails($dienst);
 	
 	if($data['collecte_1'] != '') {
	 	$veld = array();
		//$veld[] = '1e collecte '. time2str('%e %B', $data['start']) .': '. $data['collecte_1'];
		if($data['collecte_2'] != '') {
			$veld[] = '1e collecte voor '. $data['collecte_1'];
		} else {
			$veld[] = 'Collecte voor '. $data['collecte_1'];
		}
		$veld[] = time2str('%d-%m-%Y', $data['start']-$dag);
		$veld[] = time2str('%d-%m-%Y', $data['eind']+$dag);
		$veld[] = '';
		$veld[] = '';
		$veld[] = '';
		$veld[] = 'ALL_TIME';
		$veld[] = '';
		$output .= implode(";", $veld)."\n";
	}
	
	if($data['collecte_2'] != '') {
		$veld = array();
		//$veld[] = '2e collecte '. time2str('%e %B', $data['start']) .': '. $data['collecte_2'];
		$veld[] = '2e collecte voor '. $data['collecte_2'];
		$veld[] = time2str('%d-%m-%Y', $data['start']-$dag);
		$veld[] = time2str('%d-%m-%Y', $data['eind']+$dag);	
		$veld[] = '';
		$veld[] = '';
		$veld[] = '';
		$veld[] = 'ALL_TIME';
		$veld[] = '';
		$output .= implode(";", $veld)."\n";
	}
}

if(isset($_REQUEST['onscreen'])) {
	echo $output;
} else {
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.$file_name.'"');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length:'.strlen($output));
	echo $output;
	exit;
}

?>