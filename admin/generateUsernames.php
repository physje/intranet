<?php
include_once('../include/functions.php');
include_once('../include/config.php');
# include_once('include/HTML_TopBottom.php');
$db = connect_db();

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres
if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP)) {
	# Eerst de inactieve verwijderen
	$sql_oud = "UPDATE $TableUsers SET $UserUsername = '', $UserHashShort = '', $UserHashLong = '' WHERE $UserStatus NOT like 'actief'";
	$result = mysqli_query($db, $sql_oud);
	
	# Zoeken welke actieve leden nog geen username of hash hebben
	$sql = "SELECT * FROM $TableUsers WHERE $UserStatus like 'actief' AND ($UserUsername like '' OR $UserHashShort = '' OR $UserHashLong = '') ORDER BY $UserVoornaam";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$id = $row[$UserID];
			$data = getMemberDetails($id);
			
			if($data['username'] == '') {
				$username = generateUsername($id);
				$password = generatePassword(8);
			
				#$sql_update = "UPDATE $TableUsers SET $UserUsername = '$username', $UserPassword = '". md5($password) ."', $UserNewPassword = '". password_hash($password, PASSWORD_DEFAULT) ."'  WHERE $UserID = $id";
				$sql_update = "UPDATE $TableUsers SET $UserUsername = '$username', $UserNewPassword = '". password_hash($password, PASSWORD_DEFAULT) ."'  WHERE $UserID = $id";
				mysqli_query($db, $sql_update);
				echo 'Username aangemaakt voor '.  makeName($id, 5) ."($username)<br>\n";
				toLog('info', '', $id, 'account aangemaakt');
			}
			
			if($data['hash_short'] == '' OR $data['hash_long'] == '') {
				if($data['hash_short'] == '') {
					$hashS = generateID($lengthShortHash);
				} else {
					$hashS = $data['hash_short'];
				}
				
				if($data['hash_long'] == '') {
					$hashL = generateID($lengthLongHash);
				} else {
					$hashL = $data['hash_long'];
				}
				
				$sql_update = "UPDATE $TableUsers SET $UserHashShort = '$hashS', $UserHashLong = '$hashL' WHERE $UserID = $id";
				mysqli_query($db, $sql_update);
				echo 'Hash aangemaakt voor '.  makeName($id, 5) ."<br>\n";
				toLog('debug', '', $id, 'hash aangemaakt');
			}	
			
		} while($row = mysqli_fetch_array($result));
	}
} else {
	toLog('error', '', 'Poging handmatige run gebruikersnamen, IP:'.$_SERVER['REMOTE_ADDR']);
}

?>