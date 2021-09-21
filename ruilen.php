<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/config_mails.php');
include_once('include/HTML_TopBottom.php');

$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$rooster			= getParam('rooster', '');
$dienst_d			= getParam('dienst_d', '');
$dienst_s			= getParam('dienst_s', '');
$dader				= getParam('dader', '');
$slachtoffer	= getParam('slachtoffer', '');

if(isset($_REQUEST['dader']) AND isset($_REQUEST['slachtoffer'])) {
	$roosterData = getRoosterDetails($rooster);
	$vulling_d = getRoosterVulling($rooster, $dienst_d);
	$vulling_s = getRoosterVulling($rooster, $dienst_s);
	
	# Vervang in de array waar "de dader" in voorkomt
	# deze waarde door die van het slachtoffer
	foreach($vulling_d as $key => $value) {
		if($value == $dader)	$vulling_d[$key] = $slachtoffer;			
	}
	
	# En vice versa
	foreach($vulling_s as $key => $value) {
		if($value == $slachtoffer)	$vulling_s[$key] = $dader;			
	}
	
	#array_splice ($vulling_d, array_search($dader, $vulling_d), 1, array($slachtoffer));
	#array_splice ($vulling_s, array_search($slachtoffer, $vulling_s), 1, array($dader));
	
	# Alle gegevens voor de dienst verwijderen
	removeFromRooster($rooster, $dienst_d);
	removeFromRooster($rooster, $dienst_s);
	
	foreach($vulling_d as $pos => $persoon) {
		add2Rooster($rooster, $dienst_d, $persoon, $pos);
	}
	
	foreach($vulling_s as $pos => $persoon) {
		add2Rooster($rooster, $dienst_s, $persoon, $pos);
	}
			
	$details_d = getKerkdienstDetails($dienst_d);
	$details_s = getKerkdienstDetails($dienst_s);
	
	$mail = array();	
	$mail[] = "Dag ". makeName($slachtoffer, 1) .",";
	$mail[] = "";
	$mail[] = makeName($dader, 5) ." heeft zojuist met jou geruild op het rooster '". $roosterData['naam'] ."'.";
	$mail[] = "Jij staat nu ingepland op ". time2str("%e %B", $details_d['start']) ." en ". makeName($dader, 1) ." op ". time2str("%e %B", $details_s['start']);
	$mail[] = "";
	$mail[] = "Klik <a href='".$ScriptURL."showRooster.php?rooster=$rooster'>hier</a> voor het meest recente rooster";	
	
	
	$param_dader['to'][]			= array($slachtoffer);
	$param_dader['message']	= implode("<br>\n", $mail);
	$param_dader['subject']	= "Er is met jou geruild voor '". $roosterData['naam'] ."'";
		
	if(sendMail_new($param_dader)) {
		toLog('debug', $dader, '', 'verplaatst van dienst '. $dienst_d .' naar '. $dienst_s);
	}
				
	$mail = array();
	$mail[] = "Dag ". makeName($dader, 1) .",";
	$mail[] = "";
	$mail[] = "Jij hebt zojuist met ". makeName($slachtoffer, 5) ." geruild op het rooster '". $roosterData['naam'] ."'.";
	$mail[] = "Jij staat nu ingepland op ". time2str("%e %B", $details_s['start']) ." en ". makeName($slachtoffer, 1) ." op ". time2str("%e %B", $details_d['start']);
	$mail[] = "";
	$mail[] = "Klik <a href='". $ScriptURL ."showRooster.php?rooster=$rooster'>hier</a> voor het meest recente rooster";	
		
	$param_slachtoffer['to'][]			= array($dader);
	$param_slachtoffer['message']	= implode("<br>\n", $mail);
	$param_slachtoffer['subject']	= "Je hebt geruild voor '". $roosterData['naam'] ."'";
		
	if(sendMail_new($param_slachtoffer)) {
		toLog('debug', $slachtoffer, '', 'verplaatst van dienst '. $dienst_s .' naar '. $dienst_d);
	}
		
	$text[] = 'Er is een bevestigingsmail naar jullie allebei gestuurd.';
	toLog('info', $dader, $slachtoffer, "geruild voor '". $roosterData['naam'] ."'");
} elseif($slachtoffer != '' OR $dader != '') {
	$diensten = getAllKerkdiensten(true);
	
	if(isset($_REQUEST['dader'])) {
		$text[] = "Met wie wil je ruilen ?<br>";
	} else {
		$text[] = "Welke dienst neemt ". makeName($slachtoffer, 5) ." van jou over?<br>";	
	}
	
	$text[] = '<table>';
	
	foreach($diensten as $dienst) {
		$details = getKerkdienstDetails($dienst);
		$vulling = getRoosterVulling($rooster, $dienst);
		
		$namen = array();
				
		foreach($vulling as $lid) {
			if(isset($_REQUEST['slachtoffer']) AND $lid == $_SESSION['ID'] AND $dienst != $dienst_s) {
				$namen[] = "<a href='ruilen.php?rooster=$rooster&dienst_d=$dienst&dienst_s=$dienst_s&slachtoffer=$slachtoffer&dader=$lid'>". makeName($lid, 5) ."</a>";
				$tonen = true;
			} elseif(isset($_REQUEST['dader']) AND $lid != $dader AND $dienst != $dienst_d) {
				$namen[] = "<a href='ruilen.php?rooster=$rooster&dienst_d=$dienst_d&dienst_s=$dienst&slachtoffer=$lid&dader=$dader'>". makeName($lid, 5) ."</a>";
				$tonen = true;
			//} else {
			//	$namen[] = makeName($lid, 5);
			}
		}
		
		if(count($namen) > 0) {
			$text[] = '<tr><td valign=\'top\'>'.date("d-m", $details['start']).'</td><td valign=\'top\'>'. implode('<br>', $namen).'</td></tr>'.NL;
		}
	}
	
	$text[] = '</table>';
} else {
	$text[] = "Er mist wat";
}

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;

?>
