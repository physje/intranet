<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');
include_once('../Classes/Gebedspunt.php');

$afzenderAdress = 'gebedskalender@koningskerkdeventer.nl';
$rust = 2;

$Kop[] = "Beste {{voornaam}},<br>";
$Kop[] = "<br>";
		
$Staart[] = "<br>";
$Staart[] = "<i>We nodigen u uit gebedspunten aan te dragen! Mail uw punt naar <a href='mailto:gebedskalender@koningskerkdeventer.nl'>gebedskalender@koningskerkdeventer.nl</a></i><br>";
$Staart[] = "<br>";
$Staart[] = "Met groet,<br>";
$Staart[] = "het gebedskalenderteam<br>";

# Dagelijkse mailtjes
$info = $dag = array();
$bericht = $verzendtijd = '';

$punten = Gebedspunt::getPunten(date("Y-m-d"), date("Y-m-d"));
$punt = new Gebedspunt($punten[0]);
$dag[] = "Het gebedspunt voor vandaag is :<br>".NL;
$dag[] = '<blockquote>'. $punt->gebedspunt .'</blockquote>'.NL;

$verzendtijd = mktime(5, 57);

$info['name']		= 'Gebedskalender - '. date('y.m.d');
$info['subject']	= 'Gebedspunt voor '. time2str('l j F');
$info['from']		= array('name' => 'Gebedskalender','email' => $afzenderAdress);
$info['reply_to']	= $afzenderAdress;
$info['list_ids']	= array($LPGebedDagListID);

$preheader = $disclaimer = $afmelding = '';
include('../include/LP_HeaderFooter.php');
$bericht  = implode("\n", $LaPostaHeader);
$bericht .= implode("\n", $Kop);
$bericht .= implode("\n", $dag);
$bericht .= implode("\n", $Staart);
$bericht .= implode("\n", $LaPostaFooter);

$campaignDag = lp_createMail($info);
sleep($rust);
if(lp_populateMail($campaignDag, $bericht)) {
	sleep($rust);
	lp_scheduleMail($campaignDag, $verzendtijd);
	toLog('debug', '', 'Dagelijkse gebedskalender verstuurd');
}



# Weekelijkse mailtjes
if(date('w') == 0) {
	$info = $week = array();
	$bericht = $verzendtijd = '';
	
	$wPunten = Gebedspunt::getPunten(date("Y-m-d"), date("Y-m-d", (time()+(6*24*60*60))));
	$week[] = "De gebedspunten voor komende week zijn :<br>".NL;
	$week[] = "<table>".NL;
	
	foreach($wPunten as $punt) {
		$wData = new Gebedspunt($punt);		
		$week[] = "<tr><td valign='top' width='100'>".time2str("l", $wData->unix) .'</td><td>'. $wData->gebedspunt .'</td></tr>'.NL;
	}
	$week[] = "</table>".NL;
	
	$verzendtijd = mktime(5, 58);

	$info['name']		= 'Gebedskalender - week '. date('W', (time() + (24*60*60)));
	$info['subject']	= 'Gebedspunten week '. time2str('%U');
	$info['from']		= array('name' => 'Gebedskalender','email' => $afzenderAdress);
	$info['reply_to']	= $afzenderAdress;
	$info['list_ids']	= array($LPGebedWeekListID);

	$preheader = $disclaimer = $afmelding = '';
	include('../include/LP_HeaderFooter.php');
	$bericht  = implode("\n", $LaPostaHeader);
	$bericht .= implode("\n", $Kop);
	$bericht .= implode("\n", $week);
	$bericht .= implode("\n", $Staart);
	$bericht .= implode("\n", $LaPostaFooter);
	
	$campaignWeek = lp_createMail($info);
	sleep($rust);
	if(lp_populateMail($campaignWeek, $bericht)) {
		sleep($rust);
		lp_scheduleMail($campaignWeek, $verzendtijd);
		toLog('debug', '', 'Wekelijkse gebedskalender verstuurd');
	}
}





#  maandelijkse mailtjes
if(date('j') == 1) {
	$info = array();
	$bericht = $verzendtijd = '';
	
	$mPunten = Gebedspunt::getPunten(date("Y-m-d"), date("Y-m-d", mktime(0,0,1,(date("n")+1),date("j"), date("Y"))));
	$maand[] = "De gebedspunten voor deze maand zijn :<br>".NL;
	$maand[] = "<table>".NL;

	foreach($mPunten as $punt) {
		$mData =  new Gebedspunt($punt);		
		$maand[] = "<tr><td valign='top' width='25'>".time2str("j", $mData->unix) .'</td><td>'. $mData->gebedspunt .'</td></tr>'. NL;
	}
	$maand[] = "</table>".NL;

	$verzendtijd = mktime(5, 59);
	#$verzendtijd = time()+(365*24*60*60);

	$info['name']		= 'Gebedskalender - '. time2str('F');
	$info['subject']	= 'Gebedspunten '. time2str('F');
	$info['from']		= array('name' => 'Gebedskalender','email' => $afzenderAdress);
	$info['reply_to']	= $afzenderAdress;
	$info['list_ids']	= array($LPGebedWeekListID);

	$preheader = $disclaimer = $afmelding = '';
	include('../include/LP_HeaderFooter.php');
	$bericht  = implode("\n", $LaPostaHeader);
	$bericht .= implode("\n", $Kop);
	$bericht .= implode("\n", $maand);
	$bericht .= implode("\n", $Staart);
	$bericht .= implode("\n", $LaPostaFooter);
	
	$campaignMaand = lp_createMail($info);
	sleep($rust);
	if(lp_populateMail($campaignMaand, $bericht)) {
		sleep($rust);
		lp_scheduleMail($campaignMaand, $verzendtijd);
		toLog('debug', '', 'Maandelijkse gebedskalender verstuurd');
	}
}

?>