<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
//$requiredUserGroups = array(1);
//$cfgProgDir = '../auth/';
//include_once($cfgProgDir. "secure.php");
$db = connect_db();
set_time_limit(300);

$GroupMembers = getGroupMembers(8);

$row[] = "UserPrincipalName,FirstName,LastName,DisplayName,UsageLocation,City,PhoneNumber,PostalCode,StreetAddress,AccountSkuId";

foreach($GroupMembers as $id) {
	$data = getMemberDetails($id);
	
	$UserPrincipalName = str_replace(' ', '', strtolower($data['voornaam'].'.'. ($data['tussenvoegsel'] != '' ? $data['tussenvoegsel'].'.' : '').$data['achternaam'] .'@koningskerkdeventer.nl'));
	//$output[] = shell_exec("powershell.exe Get-MsolUser -UserPrincipalName $id");
	
	$cel = array();
	//$cel[] = str_replace(' ', '', strtolower($data['voornaam'].'.'. ($data['tussenvoegsel'] != '' ? $data['tussenvoegsel'].'.' : '').$data['achternaam'] .'@koningskerkdeventer.nl'));
	$cel[] = $UserPrincipalName;
	$cel[] = $data['voornaam'];
	$cel[] = $data['achternaam'];
	$cel[] = makeName($id, 5);
	$cel[] = 'NL';
	$cel[] = $data['plaats'];
	$cel[] = $data['tel'];
	$cel[] = $data['PC'];
	$cel[] = $data['straat'];
	$cel[] = 'koningskerkdeventer:O365_BUSINESS_ESSENTIALS';
	
	$row[] = implode(',', $cel);
}

//echo implode('<br>', $output);

$fp = fopen('newMembers.csv', 'w+');
fwrite($fp, implode("\n", $row));
fclose($fp);

# Autologin
#echo shell_exec("powershell.exe ./autologin.ps1");

# Import nieuwe leden
echo shell_exec("powershell.exe ./addNieuweLeden.ps1");

?>