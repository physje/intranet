<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
//$requiredUserGroups = array(1);
//$cfgProgDir = '../auth/';
//include_once($cfgProgDir. "secure.php");
$db = connect_db();
set_time_limit(300);

$GroupMembers = getGroupMembers(7);

foreach($GroupMembers as $id) {
	$data = getMemberDetails($id);
	
	if($data['mail'] != '') {
		$regel[] = '$data = New-AzureADMSInvitation -InvitedUserDisplayName "[test] '. makeName($id, 5) .'" -InvitedUserEmailAddress '. $data['mail'] .' -SendInvitationMessage $False -InviteRedirectUrl "http://www.koningskerkdeventer.nl/"';
		$regel[] = 'Add-AzureADGroupMember -ObjectId be087e36-104d-435b-8c42-1f1017f32692 -RefObjectId $data.InvitedUser.Id';
	}
}

# temp-file genereren
$tmpfname = generatePassword(8).'.ps1';

# Bestand verwijderen
# Daarna bestand kopieren met automatisch inloggen
# Hier gaan we later de accounts aan toevoegen
#unlink('gastToevoegen.ps1');
copy('autologinAzure.ps1', $tmpfname);

$fp = fopen($tmpfname, 'a+');
fwrite($fp, implode("\n", $regel));
fclose($fp);

# Import nieuwe leden
echo shell_exec("powershell.exe ./".$tmpfname);

//unlink($tmpfname);
?>