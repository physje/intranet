<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$start = mktime(0, 0, 0, 1, 1, 2020);
$eind = mktime(23, 59, 59, 12, 31, 2020);
$dag = 24*60*60;

$file_name = 'collectes_'. date('Ymd', $start) .'_tm_'. date('Ymd', $eind) .'.csv';

$kop[] = 'Collecte naam';
$kop[] = 'Start datum';
$kop[] = 'Eind datum';
$output  = implode(";", $kop)."\n";

$diensten = getKerkdiensten($start, $eind);

foreach($diensten as $dienst) {	
 	$data = getKerkdienstDetails($dienst);
 	
 	$veld = array();
	$veld[] = '1e collecte '. strftime('%e %B', $data['start']) .': '. $data['collecte_1'];
	$veld[] = strftime('%d-%m-%Y', $data['start']-$dag);
	$veld[] = strftime('%d-%m-%Y', $data['eind']+$dag);	
	$output .= implode(";", $veld)."\n";
	
	$veld = array();
	$veld[] = '2e collecte '. strftime('%e %B', $data['start']) .': '. $data['collecte_2'];
	$veld[] = strftime('%d-%m-%Y', $data['start']-$dag);
	$veld[] = strftime('%d-%m-%Y', $data['eind']+$dag);	
	$output .= implode(";", $veld)."\n";	 	
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