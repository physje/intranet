<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/config_mails.php');
include_once('Classes/Kerkdienst.php');
include_once('Classes/Rooster.php');
include_once('Classes/Vulling.php');
include_once('Classes/Member.php');
include_once('Classes/Voorganger.php');
include_once('Classes/KKDMailer.php');
include_once('Classes/Logging.php');

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres
if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP)) {
	$startTijd = mktime(0, 0, 0, date("n"), (date("j")+3), date("Y"));
	$eindTijd = mktime(23, 59, 59, date("n"), (date("j")+3), date("Y"));

	$diensten = Kerkdienst::getDiensten($startTijd, $eindTijd);
	$roosters = Rooster::getAllRoosters();
		
	# Mochten er diensten zijn, dan even alle teams opvragen
	# Van deze teamID's een naam-array maken ($teamVulling).
	# Deze $teamVulling wegschrijven in een array met alle team-vullingen per rooster ($teams)
	foreach($diensten as $d) {
		foreach($roosters as $r) {
			$rooster = new Rooster($r);
			$vulling = new Vulling($d, $r);
			
			if(!$rooster->tekst && count($vulling->leden)>0) {
				$teamVulling = array();
				foreach($vulling->leden as $lid) {
					$gebruiker = new Member($lid);
					$teamVulling[$lid] = $gebruiker->getName();
				}
				
				$teams[$d][$r] = $teamVulling;
			} else {
				$teams[$d][$r] = array();
			}
		}
	}
	
	# Alle diensten doorlopen
	foreach($diensten as $d) {
		$dienst = new Kerkdienst($d);		
		$voorganger = new Voorganger($dienst->voorganger);

		foreach($roosters as $r) {
			$vulling = $teams[$d][$r];
		
			if(is_array($vulling) AND count($vulling) > 0) {
				$rooster = new Rooster($r);
				
				# Alleen als is aangegeven dat er remindermails verstuurd moeten worden
				# moet deze hele loop doorlopen worden
				if($rooster->reminder) {
					$positie	= 0;
					$HTMLMail	= $rooster->mail;
					$onderwerp	= $rooster->onderwerp;
																	
					foreach($vulling as $lid => $naam) {
						$gebruiker = new Member($lid);
						$team = excludeID($vulling, $lid);
						$positie++;
						
						for($i=0 ; $i < 2 ; $i++) {
							if($i==0) {
								$ReplacedBericht = $HTMLMail;
							} else {
								$ReplacedBericht = $onderwerp;
							}
							
							$dagdeel					= formatDagdeel($dienst->start, false);
							
							$gebruiker->nameType = 1;	$ReplacedBericht = str_replace ('[[voornaam]]', $gebruiker->getName(), $ReplacedBericht);
							$gebruiker->nameType = 4;	$ReplacedBericht = str_replace ('[[achternaam]]', $gebruiker->getName(), $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[dag]]', time2str ("l", $dienst->start), $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[dagdeel]]', $dagdeel, $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[voorganger]]', $voorganger->getName(), $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[collecte1]]', $dienst->collecte_1, $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[collecte2]]', $dienst->collecte_2, $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[n]]', $positie, $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[n+1]]', ($positie+1), $ReplacedBericht);
												
							# Als er meer dan 1 teamlid is dan een opsommingslijst, anders gewoon een vermelding
							if(count($team) == 1) {
								$ReplacedBericht = str_replace ('[[team]]', current($team), $ReplacedBericht);
							} elseif(count($team) > 1) {
								$ReplacedBericht = str_replace ('[[team]]', makeOpsomming($team), $ReplacedBericht);
							} else {
								$ReplacedBericht = str_replace ('[[team]]', 'onbekend', $ReplacedBericht);
							}
							
							# Als [[team|X]] voorkomt moeten deze vervangen worden
							# Daarvoor worden alle roosters doorlopen en gezocht naar andere teams ($anderTeam)
							# Als [[team|$roos]] voorkomt wordt dat vervangen door dat team
							if(strpos($ReplacedBericht, '[[team|')) {
								foreach($roosters as $roos) {
									$anderTeam = $teams[$d][$roos];												
									
									if(count($anderTeam) == 1) {
										$ReplacedBericht = str_replace ("[[team|$roos]]", current($anderTeam), $ReplacedBericht);
									} elseif(count($anderTeam) > 1) {
										$ReplacedBericht = str_replace ("[[team|$roos]]", makeOpsomming($anderTeam), $ReplacedBericht);
									} else {
										$ReplacedBericht = str_replace ("[[team|$roos]]", 'onbekend', $ReplacedBericht);
									}
								}
							}
												
							if($i==0) {								
								$ReplacedBericht .= "<p>";
								$ReplacedBericht .= "Ps 1. : je kan je persoonlijke 3GK-rooster opnemen in je digitale agenda door eenmalig <a href='". $ScriptURL ."ical/". $gebruiker->username .'-'. $gebruiker->hash_short .".ics'>deze link</a> toe te voegen (<a href='". $ScriptURL ."ical/handleiding_ical.php'>handleiding</a>).<br>";
								$ReplacedBericht .= "Ps 2. : mocht je onderling geruild hebben, wil je deze mail dan doorsturen naar de betreffende persoon?<br>";
								//$ReplacedBericht .= "Ps 3. : <i>recent is de site verplaatst. Mocht je een bladwijzer hebben gemaakt voor de oude site, dan dien je deze aan te passen. Dit geldt ook voor bovenstaande digitale agenda</i><br>";
								
								# Sommige rooster worden automatisch geimporteerd.
								# Ruilen moet dus niet via de site
								if(in_array($r, $importRoosters)) {
									$ReplacedBericht .= "Als je een volgende keer de ruiling doorgeeft aan de roostermaker, zorgt die dat het op deze site ook wordt aangepast.";	
								} else {
									$ReplacedBericht .= "In het vervolg kan je die ruiling ook doorgeven via <a href='". $ScriptURL ."showRooster.php?id=$r'>het rooster</a> zelf, dan komt de mail direct goed terecht.";	
								}
														
								$FinalHTMLMail = $ReplacedBericht;
							} else {
								$FinalSubject = $ReplacedBericht;
							}					
						}
						
						$mail = new KKDMailer();
						$mail->aan			= $lid;
						$mail->Body			= $FinalHTMLMail;
						$mail->Subject		= $FinalSubject;
						$mail->partnerTo	= $rooster->partner;
						$mail->ouderCC		= $rooster->ouder;

						if($rooster->van != '') {
							if($rooster->vanNaam != '') {
								$mail->AddReplyTo($rooster->van, $rooster->vanNaam);
							} else {
								$mail->AddReplyTo($rooster->van);
							}
						}

						$mail->testen = true;
									
						if($mail->sendMail()) {
							toLog('Herinnering-mail '. $rooster->naam .' verstuurd', 'info', $lid);
						} else {
							toLog('Problemen met herinnering-mail '. $rooster->naam .' versturen', 'error', $lid);
						}
					}
				}
			}
		}
	}
} else {
	toLog('Poging handmatige run herinneringmail, IP:'.$_SERVER['REMOTE_ADDR'], 'error');
}

?>
