<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');

$Kop[] = "Beste {{voornaam}},<br>";
$Kop[] = "<br>";
		
$Staart[] = "<br>";
$Staart[] = "We nodigen u uit gebedspunten aan te aandragen!<br>";
$Staart[] = "Graag voor de 15e van de maand  mailen naar <a href='mailto:gebedskalender@koningskerkdeventer.nl'>gebedskalender@koningskerkdeventer.nl</a><br>";
$Staart[] = "<br>";
$Staart[] = "Met groet,<br>";
$Staart[] = "het gebedskalenderteam<br>";
$Staart[] = "<br>";
$Staart[] = "Ps. De gebedspunten worden ook dagelijks in de 3GK Scipio-app getoond. Nog geen 3GK Scipio app? Bekijk <a href='http://www.draijer.org/download/3GK/InstallatieHandleidingScipioApp3GK.pdf'>hier</a> de handleiding.";
	

# dagelijkse mailtjes
$dPunt = getGebedspunten(date("Y-m-d"), date("Y-m-d"));
$dData = getGebedspunt($dPunt[0]);		
$dag[] = "Het gebedspunt voor vandaag is :<br>".NL;
$dag[] = '<blockquote>'.$dData['gebedspunt'] .'</blockquote><br>'.NL;

$verzendtijd = mktime(5, 57);

$info['name']			= 'Gebedskalender - '. date('ymd');
$info['subject']	= 'Gebedspunt voor '. time2str('%A %e %B');
$info['from']			= array('name' => 'Gebedskalender','email' => 'matthijs.draijer@koningskerkdeventer.nl');
$info['list_ids']	= array($LPGebedDagListID);

$preheader = $disclaimer = $afmelding = '';
include('../include/LP_HeaderFooter.php');
$bericht  = implode("\n", $LaPostaHeader);
$bericht .= implode("\n", $Kop);
$bericht .= implode("\n", $dag);
$bericht .= implode("\n", $Staart);
$bericht .= implode("\n", $LaPostaFooter);

$campaignDag = lp_createMail($info);
if(lp_populateMail($campaignDag, $bericht)) {
	lp_scheduleMail($campaignDag, $verzendtijd);
}





# weekelijkse mailtjes
if(date('w') == 0) {
	$wPunten = getGebedspunten(date("Y-m-d"), date("Y-m-d", (time()+(6*24*60*60))));
	$week[] = "De gebedspunten voor komende week zijn :<br>".NL;
	$week[] = "<table>".NL;
	
	foreach($wPunten as $punt) {
		$wData = getGebedspunt($punt);		
		$week[] = "<tr><td valign='top'>".strftime("%A", $wData['unix']) .'</td><td>'. $wData['gebedspunt'] .'</td></tr>'.NL;
	}
	$week[] = "</table>".NL;
	
	$verzendtijd = mktime(5, 58);

	$info['name']			= 'Gebedskalender - week '. date('W');
	$info['subject']	= 'Gebedspunten week '. time2str('%U');
	$info['from']			= array('name' => 'Gebedskalender','email' => 'matthijs.draijer@koningskerkdeventer.nl');
	$info['list_ids']	= array($LPGebedWeekListID);

	$preheader = $disclaimer = $afmelding = '';
	include('../include/LP_HeaderFooter.php');
	$bericht  = implode("\n", $LaPostaHeader);
	$bericht .= implode("\n", $Kop);
	$bericht .= implode("\n", $week);
	$bericht .= implode("\n", $Staart);
	$bericht .= implode("\n", $LaPostaFooter);
	
	$campaignWeek = lp_createMail($info);
	if(lp_populateMail($campaignWeek, $bericht)) {
		lp_scheduleMail($campaignWeek, $verzendtijd);
	}
}





#  maandelijkse mailtjes
if(date('j') == 1) {
	$mPunten = getGebedspunten(date("Y-m-d"), date("Y-m-d", mktime(0,0,1,(date("n")+1),date("j"), date("Y"))));
	$maand[] = "De gebedspunten voor deze maand zijn :<br>".NL;
	$maand[] = "<table>".NL;

	foreach($mPunten as $punt) {
		$mData = getGebedspunt($punt);		
		$maand[] = "<tr><td valign='top'>".strftime("%e", $mData['unix']) .'</td><td>'. $mData['gebedspunt'] .'</td></tr>'. NL;
	}
	$maand[] = "</table>".NL;

	$verzendtijd = mktime(5, 59);

	$info['name']			= 'Gebedskalender - '. time2str('%B');
	$info['subject']	= 'Gebedspunten '. time2str('%B');
	$info['from']			= array('name' => 'Gebedskalender','email' => 'matthijs.draijer@koningskerkdeventer.nl');
	$info['list_ids']	= array($LPGebedWeekListID);

	$preheader = $disclaimer = $afmelding = '';
	include('../include/LP_HeaderFooter.php');
	$bericht  = implode("\n", $LaPostaHeader);
	$bericht .= implode("\n", $Kop);
	$bericht .= implode("\n", $maand);
	$bericht .= implode("\n", $Staart);
	$bericht .= implode("\n", $LaPostaFooter);
	
	$campaignMaand = lp_createMail($info);
	if(lp_populateMail($campaignMaand, $bericht)) {
		lp_scheduleMail($campaignMaand, $verzendtijd);
	}
}

?>