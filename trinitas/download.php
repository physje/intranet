<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

if(isset($_REQUEST['fileID'])) {
	$showLogin = true;

	if(isset($_REQUEST['key'])) {
		$data = getTrinitasData($_REQUEST['fileID']);
				
		if($_REQUEST['key'] != $data['hash']) {			
			toLog('error', '', '', 'ongeldige Trinitas file-hash-combinatie');
			$showLogin = true;
		} else {
			$showLogin = false;			
			toLog('info', '', '', 'Download Trinitas '. $data['jaar'] .' - '. $data['nr'] .' mbv hash');
		}
	}
	
	if($showLogin) {
		$minUserLevel = 1;
		$cfgProgDir = 'auth/';
		include($cfgProgDir. "secure.php");
	}
	
	$file_name = makeTrinitasName($_REQUEST['fileID'], 3).'.pdf';
	$file = $ArchiveDir .'/'. $data['filename'];
	
	# Als iemand geen login-window heeft gehad (en dus een hash heeft gebruikt)
	# hoeft niet gelogd te worden dat hij/zij iets download
	# dat is hierboven namelijk al gebeurd		
	if($showLogin) {
		toLog('info', $_SESSION['ID'], '', 'Download Trinitas '. $data['jaar'] .' - '. $data['nr']);
	}
	
	# In de database opnemen dat dit exemplaar een keer gedownload is
	$sql = "UPDATE $TableArchief SET $ArchiefDownload = $ArchiefDownload+1 WHERE $ArchiefID like '". $_REQUEST['fileID'] ."'";
	mysqli_query($db, $sql);
			
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="'.$file_name.'"');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length:'.filesize($file));
  readfile($file);
  exit;
}

?>