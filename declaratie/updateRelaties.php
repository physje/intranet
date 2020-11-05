<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');
$db = connect_db();

$updateLeden				= true;
$updateVoorgangers	= false;

if($updateLeden) {
	$sql = "SELECT * FROM $TableUsers WHERE $UserEBRelatie > 0 LIMIT 0,1";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	do {
		set_time_limit(10);
		unset($data);
		$scipioID = $row[$UserID];
		$UserData = getMemberDetails($scipioID);
		
		echo makeName($scipioID, 5) .' -> ';
		
		if(is_numeric($UserData['eb_code']) AND $UserData['eb_code'] > 0) {
			$code = $UserData['eb_code'];
			$data['naam'] = makeName($scipioID, 15);
			$data['geslacht'] = $UserData['geslacht'];
			$data['adres'] = $UserData['straat'].' '.$UserData['huisnummer'].$UserData['huisletter'].($UserData['toevoeging'] != '' ? '-'.$UserData['toevoeging'] : '');
			$data['postcode'] = str_replace(' ', '', $UserData['PC']);
			$data['plaats'] = ucfirst(strtolower($UserData['plaats']));
			$data['mail'] = $UserData['mail'];
			$data['notitie'] = '';
			
			if(substr($UserData['tel'], 0, 3) == '06-') {
				$data['mobiel'] = $UserData['tel'];				
			} else {
				$data['telefoon'] = $UserData['tel'];
			}
				
			$errorResult = eb_updateRelatieByCode($code, $data);
			if($errorResult) {
				echo $errorResult;
			} else {
				echo 'Succes<br>';
			}
		}
		sleep(2);
	} while($row = mysqli_fetch_array($result));
}



if($updateVoorgangers) {
	$sql = "SELECT * FROM $TableVoorganger WHERE $VoorgangerEBRelatie > 0";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	do {
		set_time_limit(10);
		$voorganger = $row[$VoorgangerID];
				
		echo makeVoorgangerName($voorganger, 3) .' -> ';
		
		$code = $row[$VoorgangerEBRelatie];
		$data['naam'] = makeVoorgangerName($voorganger, 6);
		//$data['adres'] = $UserData['straat'].' '.$UserData['huisnummer'].$UserData['huisletter'].($UserData['toevoeging'] != '' ? '-'.$UserData['toevoeging'] : '');
		//$data['postcode'] = str_replace(' ', '', $UserData['PC']);
		//$data['plaats'] = ucfirst(strtolower($row[$VoorgangerPlaats]));
		$data['mail'] = $row[$VoorgangerMail];
				
		$errorResult = eb_updateRelatieByCode($code, $data);
		if($errorResult) {
			echo $errorResult;
		} else {
			echo 'Succes<br>';
		}
		
		sleep(3);
	} while($row = mysqli_fetch_array($result));
}


?>