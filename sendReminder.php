<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/config_mails.php');
include_once('include/HTML_HeaderFooter.php');

$db = connect_db();

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres
if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP)) {
	$startTijd = mktime(0, 0, 0, date("n"), (date("j")+3), date("Y"));
	$eindTijd = mktime(23, 59, 59, date("n"), (date("j")+3), date("Y"));

	$diensten = getKerkdiensten($startTijd, $eindTijd);
	$roosters = getRoosters(0);
		
	# Mochten er diensten zijn, dan even alle teams opvragen
	# Van deze teamID's een naam-array maken ($teamVulling).
	# Deze $teamVulling wegschrijven in een array met alle team-vullingen per rooster ($teams)
	foreach($diensten as $d) {
		foreach($roosters as $r) {
			$vulling = getRoosterVulling($r, $d);
			
			if(is_array($vulling)) {
				$teamVulling = array();
				foreach($vulling as $lid) {
					$teamVulling[$lid] = makeName($lid, 5);
				}
				
				$teams[$d][$r] = $teamVulling;
			} else {
				$teams[$d][$r] = array();
			}
		}
	}
	
	# Alle diensten doorlopen
	foreach($diensten as $dienst) {
		$dienstData = getKerkdienstDetails($dienst);
		foreach($roosters as $rooster) {
			$vulling = $teams[$dienst][$rooster];
		
			if(is_array($vulling) AND count($vulling) > 0) {
				$roosterData			= getRoosterDetails($rooster);			
				
				# Alleen als is aangegeven dat er remindermails verstuurd moeten worden
				# moet deze hele loop doorlopen worden
				if($roosterData['reminder'] == 1) {
					$positie					= 0;
					$HTMLMail					= $roosterData['text_mail'];
					$onderwerp				= $roosterData['onderwerp_mail'];
					$var['ReplyToName']	= $roosterData['naam_afzender'];
					$var['ReplyTo']			= $roosterData['mail_afzender'];
					# Stuur bij 'tieners' een CC naar de ouders
					$var['ouderCC']		= 1;
																	
					foreach($vulling as $lid => $naam) {
						$team = excludeID($vulling, $lid);
						$positie++;
						
						for($i=0 ; $i < 2 ; $i++) {
							if($i==0) {
								$ReplacedBericht = $HTMLMail;
							} else {
								$ReplacedBericht = $onderwerp;
							}
							
							$dagdeel					= formatDagdeel($dienstData['start'], false);
							
							$ReplacedBericht = str_replace ('[[voornaam]]', makeName($lid, 1), $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[achternaam]]', makeName($lid, 4), $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[dag]]', time2str ("%A", $dienstData['start']), $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[dagdeel]]', $dagdeel, $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[voorganger]]', $dienstData['voorganger'], $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[collecte1]]', $dienstData['collecte_1'], $ReplacedBericht);
							$ReplacedBericht = str_replace ('[[collecte2]]', $dienstData['collecte_2'], $ReplacedBericht);
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
							# Daarvoor worden alle roosters doorlopen en team erbij zoeken ($anderTeam)
							# Als [[team|$roos]] voorkomt wordt dat vervangen door dat team
							if(strpos($ReplacedBericht, '[[team|')) {
								foreach($roosters as $roos) {
									$anderTeam = $teams[$dienst][$roos];												
									
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
								$memberData = getMemberDetails($lid);
								$ReplacedBericht .= "<p>";
								$ReplacedBericht .= "Ps 1. : je kan je persoonlijke 3GK-rooster opnemen in je digitale agenda door eenmalig <a href='". $ScriptURL ."ical/".$memberData['username'].'-'. $memberData['hash_short'] .".ics'>deze link</a> toe te voegen (<a href='". $ScriptURL ."ical/handleiding_ical.php'>handleiding</a>).<br>";
								$ReplacedBericht .= "Ps 2. : mocht je onderling geruild hebben, wil je deze mail dan doorsturen naar de betreffende persoon?<br>";
								//$ReplacedBericht .= "Ps 3. : <i>recent is de site verplaatst. Mocht je een bladwijzer hebben gemaakt voor de oude site, dan dien je deze aan te passen. Dit geldt ook voor bovenstaande digitale agenda</i><br>";
								
								# Sommige rooster worden automatisch geimporteerd.
								# Ruilen moet dus niet via de site
								if(in_array($rooster, $importRoosters)) {
									$ReplacedBericht .= "Als je een volgende keer de ruiling doorgeeft aan de roostermaker, zorgt die dat het op deze site ook wordt aangepast.";	
								} else {
									$ReplacedBericht .= "In het vervolg kan je die ruiling ook doorgeven via <a href='". $ScriptURL ."showRooster.php?rooster=$rooster'>het rooster</a> zelf, dan komt de mail direct goed terecht.";	
								}
														
								$FinalHTMLMail = $ReplacedBericht;
							} else {
								$FinalSubject = $ReplacedBericht;
							}					
						}
						
						unset($param);
						$param['to'][]				= array($lid);
						$param['message']			= $FinalHTMLMail;
						$param['subject']			= $FinalSubject;
						$param['ReplyToName']	= $roosterData['naam_afzender'];
						$param['ReplyTo']			= $roosterData['mail_afzender'];						
						$param['ouderCC']			= true;
									
						if(sendMail_new($param)) {
							toLog('info', '', $lid, 'herinnering-mail '. $roosterData['naam'] .' verstuurd');
						} else {
							toLog('error', '', $lid, 'problemen met herinnering-mail '. $roosterData['naam'] .' versturen');
						}
					}
				}
			}
		}
	}
} else {
	toLog('error', '', 'Poging handmatige run herinneringmail, IP:'.$_SERVER['REMOTE_ADDR']);
}

?>
