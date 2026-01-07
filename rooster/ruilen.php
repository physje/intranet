<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Rooster.php');
include_once('../Classes/Vulling.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/KKDMailer.php');
include_once('../Classes/Logging.php');

$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$gebruiker = new Member($_SESSION['useID']);

$id		= getParam('id', 0);
$d_d	= getParam('d_d', 0);
$d_s	= getParam('d_s', 0);
$d		= getParam('d', 0);
$s		= getParam('s', 0);

if($id > 0)		$rooster = new Rooster($id);
if($d > 0)		$dader = new Member($d);
if($s > 0)		$slachtoffer = new Member($s);
if($d_d > 0)	$dienst_d = new Kerkdienst($d_d);
if($d_s > 0)	$dienst_s = new Kerkdienst($d_s);
if($d_d > 0)	$vulling_d = new Vulling($dienst_d->dienst, $rooster->id);
if($d_s > 0)	$vulling_s = new Vulling($dienst_s->dienst, $rooster->id);

if(isset($dader) AND isset($slachtoffer)) {	
	# Vervang in de array waar "de dader" in voorkomt
	# deze waarde door die van het slachtoffer
	foreach($vulling_d->leden as $key => $value) {
		if($value == $dader->id)	$vulling_d->leden[$key] = $slachtoffer->id;			
	}
	
	# En vice versa
	foreach($vulling_s->leden as $key => $value) {
		if($value == $slachtoffer->id)	$vulling_s->leden[$key] = $dader->id;			
	}
	
	$vulling_d->save();
	$vulling_s->save();
		
	$mail_d = array();	
	$slachtoffer->nameType = 1;
	$mail_d[] = "Dag ". $slachtoffer->getName() .",";
	$mail_d[] = "";
	$mail_d[] = $dader->getName() ." heeft zojuist met jou geruild op het rooster '". $rooster->naam ."'.";
	$dader->nameType = 1;
	$mail_d[] = "Jij staat nu ingepland op ". time2str("j F", $dienst_d->start) ." en ". $dader->getName() ." op ". time2str("j F", $dienst_s->start);
	$mail_d[] = "";
	$mail_d[] = "Klik <a href='".$ScriptURL."rooster/index.php?id=". $rooster->id ."'>hier</a> voor het meest recente rooster";	
	
	$mail_dader = new KKDMailer();
	$mail_dader->aan 		= $slachtoffer->id;
	$mail_dader->Body		= implode("<br>\n", $mail_d);
	$mail_dader->Subject	= "Er is met jou geruild voor '". $rooster->naam ."'";
	if(!$productieOmgeving)	$mail_dader->testen		= true;
		
	if($mail_dader->sendMail()) {
		toLog($dader->getName() .' verplaatst van dienst '. $dienst_d->dienst.' naar '. $dienst_s->dienst, 'debug', $slachtoffer->id);
	}

	
	$mail_d = array();
	$mail_d[] = "Dag ". $dader->getName() .",";
	$mail_d[] = "";
	$slachtoffer->nameType = 2;
	$mail_d[] = "Jij hebt zojuist met ". $slachtoffer->getName() ." geruild op het rooster '". $rooster->naam ."'.";
	$slachtoffer->nameType = 1;
	$mail_d[] = "Jij staat nu ingepland op ". time2str("j F",  $dienst_s->start) ." en ". $slachtoffer->getName() ." op ". time2str("j F",  $dienst_d->start);
	$mail_d[] = "";
	$mail_d[] = "Klik <a href='".$ScriptURL."rooster/index.php?id=". $rooster->id ."'>hier</a> voor het meest recente rooster";	
	
	$mail_slachtoffer = new KKDMailer();
	$mail_slachtoffer->aan = $dader->id;
	$mail_slachtoffer->Body		= implode("<br>\n", $mail_d);
	$mail_slachtoffer->Subject	= "Je hebt geruild voor '". $rooster->naam ."'";
	if(!$productieOmgeving)	$mail_slachtoffer->testen		= true;

	if($mail_slachtoffer->sendMail()) {
		toLog($slachtoffer->getName() .' verplaatst van dienst '. $dienst_s->dienst.' naar '. $dienst_d->dienst, 'debug');
	}
		
	$text[] = 'Er is een bevestigingsmail naar jullie allebei gestuurd.';
	toLog("Geruild voor '". $rooster->naam ."'", '', $slachtoffer->id);
} elseif(isset($slachtoffer) OR isset($dader)) {
	$diensten = Kerkdienst::getDiensten(0, 0);	
	$familie = $gebruiker->getFamilieLeden();
	
	if(isset($dader)) {
		$text[] = "Met wie wil ". ($dader->id == $gebruiker->id ? 'je' : $dader->getName()) ." ruilen ?<br>";
	} else {
		$text[] = "Welke dienst neemt ". $slachtoffer->getName() ." over?<br>";	
	}
	
	$text[] = '<table>';
	
	foreach($diensten as $dienst) {
		$details = new Kerkdienst($dienst);
		$vulling = new Vulling($details->dienst, $rooster->id);
		
		$namen = array();
						
		foreach($vulling->leden as $lid) {
			$person = new Member($lid);
			if(isset($dader) AND $lid != $dader->id AND $dienst != $d_d) {
				$namen[] = "<a href='ruilen.php?id=". $rooster->id ."&d_d=$d_d&d_s=$dienst&s=$lid&d=". $dader->id ."'>". $person->getName() ."</a>";
				$tonen = true;
			} elseif(isset($slachtoffer) AND in_array($lid, $familie) AND $dienst != $d_s) {
				$namen[] = "<a href='ruilen.php?id=". $rooster->id ."&d_d=$dienst&d_s=$d_s&s=". $slachtoffer->id ."&d=$lid'>". $person->getName() ."</a>";
				$tonen = true;			
			} else {
				$namen[] = $person->getName();
			}
		}
		
		if(count($namen) > 0) {
			$text[] = '<tr><td valign=\'top\'>'.date("d-m", $details->start).'</td><td valign=\'top\'>'. implode('<br>', $namen).'</td></tr>'.NL;
		}
	}
	
	$text[] = '</table>';
} else {
	$text[] = "Er mist wat";
}

echo showCSSHeader(array('default', 'table_default'), '', $rooster->naam);
echo '<div class="content_vert_kolom">'.NL;
echo "<h1>". $rooster->naam ."</h1>".NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div>'.NL;
echo showCSSFooter();
?>
