<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

if(isset($_REQUEST['fileID'])) {
	$data = getTrinitasData($_REQUEST['fileID']);
	
	$file_name = makeTrinitasName($_REQUEST['fileID'], 3).'.pdf';
	$file = $ArchiveDir .'/'. $data['filename'];
	
	# Als iemand geen login-window heeft gehad (en dus een hash heeft gebruikt)
	# hoeft niet gelogd te worden dat hij/zij iets download
	# dat is hierboven namelijk al gebeurd		
	toLog('info', $_SESSION['ID'], $_REQUEST['fileID'], 'download');
		
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