<?php
include_once('../Classes/Mysql.php');
include_once('../Classes/Member.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');
include_once('../include/HTML_TopBottom.php');

$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$person = new Member($_SESSION['useID']);
$email = $person->getMail();

$lijst = getParam('lijst', '');

if(isset($lijst) AND $lijst != '') {
	if(lp_onList($lijst, $email)) {
		$campaigns = lp_getArchief($lijst);
		$campaigns = array_reverse($campaigns);
		
		foreach($campaigns as $campaign) {
			$data = lp_getMailData($campaign);			
			if($data['delivery_started'] != NULL)	$text[] = substr($data['delivery_started'], 8, 2).'-'.substr($data['delivery_started'], 5, 2).'-'.substr($data['delivery_started'], 2, 2)." : <a href='". $data['web'] ."'>". $data['subject'] .'</a><br>';			
		}
		
		if(!isset($text))	$text[] = 'Het archief is nog leeg<br>';		
	} else {
		$text[] = "Je bent niet geabonneerd op deze lijst";
	}
} else {
	$listIDs['Ledenlijst']					= $LPLedenListID;
	$listIDs['Trinitas']						= $LPTrinitasListID;
	$listIDs['Wekelijkse Trinitas']	= $LPWeekTrinitasListID;
	$listIDs['Koningsmail']					= $LPKoningsmailListID;
	$listIDs['Dagelijkse gebedskalender'] 	= $LPGebedDagListID;
	$listIDs['Wekelijkse gebedskalender'] 	= $LPGebedWeekListID;
	$listIDs['Maandelijkse gebedskalender']	= $LPGebedMaandListID;
	$listIDs['Wijk A']							= $LPWijkListID['A'];
	$listIDs['Wijk B']							= $LPWijkListID['B'];
	$listIDs['Wijk C']							= $LPWijkListID['C'];
	$listIDs['Wijk D']							= $LPWijkListID['D'];
	$listIDs['Wijk E'] 							= $LPWijkListID['E'];
	$listIDs['Wijk F'] 							= $LPWijkListID['F'];
	$listIDs['Wijk G'] 							= $LPWijkListID['G'];
	$listIDs['Wijk H'] 							= $LPWijkListID['H'];
	$listIDs['Wijk I'] 							= $LPWijkListID['I'];
	$listIDs['Wijk J'] 							= $LPWijkListID['J'];
	$listIDs['ICF']									= $LPWijkListID['ICF'];

	#$listIDs = $listIDs+$LPWijkListID;	
		
	$text[] = 'Maak een keuze voor de lijst waar je het archief van wilt zien<br>';
	$text[] = '<br>';
	
	foreach($listIDs as $naam => $listID) {		
		if(lp_onList($listID, $email)) {
			$text[] = "<a href='?lijst=$listID'>$naam</a><br>";
		}
	}
}

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
#echo '<h1>LaPosta</h1>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>

