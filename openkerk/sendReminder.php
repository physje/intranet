<?php
include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');
include_once('../Classes/OpenKerkRooster.php');
include_once('../Classes/KKDMailer.php');

$startDag = mktime(0,0,0,date('n'),(date('j')+1));
$eindDag = $startDag + (24*60*60);

$personen = OpenKerkRooster::getCrew($startDag, $eindDag);

# Niet alle dagen is er een mail te versturen
foreach($personen as $persoon) {
	if($persoon != 'externTG' && $persoon != 'externSM' && $persoon != '' && $persoon > 0) {
		$OKReminder = new KKDMailer();
		
		$geslacht = '';
		$mail = array();
				
		if(is_numeric($persoon)) {
			$person = new Member($persoon);
			$person->nameType = 1;

			$mail[] = "Beste ". $person->getName() .",<br>";
			$OKReminder->aan = $person->id;			
		} else {
			$geslacht	= $extern[$persoon]['geslacht'];

			$mail[] = "Beste ". $extern[$persoon]['voornaam'] .",<br>";			
			$OKReminder->ontvangers = array($extern[$persoon]['mail'], $extern[$persoon]['naam']);
		}

		$mail[] = "<br>";
		$mail[] = "dit is een herinnering dat je voor morgen op het rooster staat als gast".($person->geslacht == 'M' || $geslacht == 'M' ? 'heer' : 'vrouw') ." voor Open Kerk<br>";
		$mail[] = "Je bent op de volgende tijden ingedeeld :";
		$mail[] = "<ul>";

		$shifts = OpenKerkRooster::getShifts($startDag, $eindDag, $persoon);

		foreach($shifts as $shift) {
			$rooster = new OpenKerkRooster($shift);
			$mail[] = "<li>". time2str("E d LLL HH:mm", $rooster->start) .'-'. time2str("HH:mm", $rooster->eind) .($rooster->opmerking != '' ? " (<i>$rooster->opmerking</i>)" : '') .'</li>';				
		}
		
		$mail[] = "</ul>";
		$mail[] = "<br>";
			
		if(is_numeric($persoon)) {			
			$mail[] = "Ps 1. : je kan je persoonlijke 3GK-rooster opnemen in je digitale agenda door eenmalig <a href='". $ScriptURL ."ical/". $person->hash_long .".ics'>deze link</a> toe te voegen (<a href='". $ScriptURL ."ical/handleiding_ical.php'>handleiding</a>).<br>";
			$mail[] = "Ps 2. : mocht je onderling geruild hebben, wil je deze mail dan doorsturen naar de betreffende persoon?";
		} else {
			$mail[] = "Ps : mocht je onderling geruild hebben, wil je deze mail dan doorsturen naar de betreffende persoon?";
		}

		$OKReminder->Subject	= "Herinnering morgen gast".($person->geslacht == 'M' || $geslacht == 'M' ? 'heer' : 'vrouw') ." Open Kerk";
		$OKReminder->Body		= implode("\n", $mail);
		$OKReminder->From		= $ScriptMailAdress;
		$OKReminder->FromName	= 'Open Kerk herinnering';
		$OKReminder->addReplyTo('maartendejonge55@gmail.com', 'Maarten de Jonge');
		if(!$productieOmgeving)	$OKReminder->testen		= true;
		$OKReminder->Sendmail();
	}
}

?>
