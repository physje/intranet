<?php
/**
 * Script om herinnering-mails te versturen aan beheerders van een rooster als het rooster (bijna) is afgelopen.
 * 
 * Dit script wordt elke maandag uitgevoerd via een cronjob en kijkt naar de vulling van de roosters.
 * Als er nog maar een paar weken (meestal 2 tot 4) vulling voor het rooster is wordt er een mail gestuurd naar de beheerders dat het rooster bijna is afgelopen.
 * Als het rooster daadwerkelijk leeg is wordt er nog 1 keer een mail gestuurd
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Rooster.php');
include_once('../Classes/Mysql.php');
include_once('../Classes/Team.php');
include_once('../Classes/Member.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/KKDMailer.php');
include_once('../Classes/Logging.php');

$db = new Mysql();
$roosters = Rooster::getAllRoosters();
$week = 7*24*60*60;

# Doorloop alle roosters
foreach($roosters as $r) {	
	$rooster = new Rooster($r);

	# Is ingesteld dat er gealarmeerd moet worden
	if($rooster->alert > 0) {		
		# Is het een text-only rooster
		if($rooster->tekst) {			
			$sql = "SELECT * FROM `kerkdiensten`, `planning_tekst` WHERE `planning_tekst`.`dienst` = `kerkdiensten`.`id` AND `planning_tekst`.`rooster` = ". $rooster->id ." AND `kerkdiensten`.`actief` = '1' AND `kerkdiensten`.`start` > ". (time()+(($rooster->alert-1)*$week));
		# Of een "normaal" rooster
		} elseif(!$rooster->tekst) {
			$sql = "SELECT * FROM `kerkdiensten`, `planning` WHERE `planning`.`dienst` = `kerkdiensten`.`id` AND `planning`.`commissie` = ". $rooster->id ." AND `kerkdiensten`.`actief` = '1' AND `kerkdiensten`.`start` > ". (time()+(($rooster->alert-1)*$week));
		}
		$data = $db->select($sql);

		echo $rooster->naam .' -> '. count($data) .'<br>';

		# Er zijn geen diensten
		if(count($data) == 0) {
			# Initialiseren
			$lastWarning = $verlopen = false;
			$last_data = array();
			
			# Wat is de laatste dienst die nog wel gevonden is
			# Eerst in geval van een "normaal" rooster, daarna bij een text-only rooster
			if($rooster->tekst) {
				$sql_last = "SELECT MAX(`kerkdiensten`.`start`) as lastDienst FROM `kerkdiensten`, `planning_tekst` WHERE `planning_tekst`.`dienst` = `kerkdiensten`.`id` AND `kerkdiensten`.`actief` = '1' AND `planning_tekst`.`rooster` = ". $rooster->id;
			} else {
				$sql_last = "SELECT MAX(`kerkdiensten`.`start`) as lastDienst FROM `kerkdiensten`, `planning` WHERE `planning`.`dienst` = `kerkdiensten`.`id` AND `kerkdiensten`.`actief` = '1' AND `planning`.`commissie` = ". $rooster->id;
			}
			
			$last_data = $db->select($sql_last);
			
			# Als er een eerst volgende gevonden is verder gaan
			if(is_array($last_data) && isset($last_data['lastDienst']) && $last_data['lastDienst'] > 0) {
				# De eerst volgende dienst 10 uur daarna, is de eerst "gemiste" dienst
				# die 10 uur is om te voorkomen dat bij een morgendienst
				# de middagdienst als eerst volgende missend gezien wordt
				$startTijd		= $last_data['lastDienst']+(10*60*60);
				$missed			= Kerkdienst::getDiensten($startTijd, $startTijd+$week);
				$firstMissed	= new Kerkdienst(current($missed));
      	
				# Ingewikkelde rekensom om deadline uit te rekenen.
				# De deadline van 4 weken is meestal voor de mail naar de voorganger (=18 dagen)
				# De deadline van 2 weken is meestal voor de reminder-mail (=3 dagen)
				# De deadline van 1 week is meestal voor een text-only rooster (=1 dag)
				if($rooster->alert == 4) {
					$offset = 18;
				} elseif($rooster->alert == 2) {
					$offset = 3;
				} elseif($rooster->alert == 1) {
					$offset = 1;
				}
				
				# Reken op basis van de eerst gemiste dienst icm de offset uit
				# wat de deadline is voor het invullen van het rooster
				$deadline = mktime (0,1,2, date("n", $firstMissed->start), (date("j", $firstMissed->start)-$offset), date("Y", $firstMissed->start));
				
				//echo "lastDienst :". date('d-m-Y', $row_last['lastDienst']) ."|firstMissed :". date('d-m-Y', $firstMissed->start) ."|deadline :". date('d-m-Y', $deadline) .'<br>';
      	
				# Als de deadline verlopen is komen we "in de gevarenzone"
				# Als de eerst gemiste dienst al geweest is, is het rooster verlopen
				# Als de deadline net (= 1 week) verlopen is nog een laatste waarschuwing sturen
				if($deadline < time()) {
					if($firstMissed->start < time()) {
						$verlopen = true;
					} else {
						$verlopen = false;
					}
      	
					if((time()-$deadline) < $week) {
						$lastWarning = true;
					} else {
						$lastWarning = false;
					}
				} else {
					$verlopen = false;
				}
      	
				# Stel
				#		$row_last['lastDienst'] : 24-11
				# Dan
				#		deadline : 28-11
				#		$firstMissed : 01-12
				#
				# 18-11 mail -> $verlopen = false
				# 25-11 mail -> $verlopen = true
				# 2-12 mail -> $verlopen = true; lastWarning = true
				# 9-12 geen mail -> $verlopen = true; lastWarning = false
      	
				if((!$verlopen OR $lastWarning) && $deadline > 0) {
					# geadresseerden
					$beheerders = $namenBeheerders = array();
					$beheerders = new Team($rooster->beheerder);
					
					foreach($beheerders->leden as $lid) {						
						$bhrdr = new Member($lid);
						$namenBeheerders[$lid] = $bhrdr->getName(1);
					}
					      	
					foreach($beheerders->leden as $b) {
						$andereOntvangers = excludeID($namenBeheerders, $b);
						$beheerder = new Member($b);						

						# Mail opstellen
						$alert = array();
						$alert[] = "Goedemorgen ". $beheerder->getName(1) .",";
						$alert[] = "";
						$alert[] = "dit is een automatisch gegenereerde mail om aan te geven dat het huidige online rooster '". $rooster->naam ."' ". ($verlopen ? '' : 'bijna ') ."afgelopen is.";
						$alert[] = "";
				
						if(!$lastWarning && $rooster->tekst) {
							$alert[] = "Om te zorgen dat iedereen op tijd remindermails krijgt/er geen lege mails verstuurd worden, moet je v&oacute;&oacute;r ". time2str('EEEE d LLLL', $deadline) ." het nieuwe rooster invoeren.";
						} elseif($lastWarning && $rooster->tekst) {
							if($firstMissed->start < time()) {
								$alert[] = "Voor afgelopen ". time2str('EEEE', $firstMissed->start) ." zijn geen reminder-mails verstuurd. Zodra het rooster weer is ingevoerd zal iedereen weer remindermails krijgen en zal deze o.a. weer in de scipio-app verschijnen.";
							} else {
								$alert[] = "Het rooster van ". time2str('EEEE d LLLL', $firstMissed->start) ." heeft een lege plek en is niet meegenomen in de mails die daarover verstuurd zijn. Zodra het rooster weer is ingevoerd zal iedereen weer de juiste mails krijgen en zal deze o.a. weer in de scipio-app verschijnen.";
							}
						} elseif(!$lastWarning && !$rooster->tekst) {
							$alert[] = "Om te zorgen dat er geen plekken in het rooster ontstaan, moet je v&oacute;&oacute;r ". time2str('EEEE d LLLL', $deadline) ." het nieuwe rooster invoeren.";
						} elseif($lastWarning AND !$rooster->tekst) {
							if($firstMissed->start < time()) {
								$alert[] = "Het rooster voor afgelopen ". time2str('EEEE', $firstMissed->start) ." was leeg. Zodra het rooster weer is ingevoerd zal deze o.a. weer in de scipio-app verschijnen.";
							} else {
								$alert[] = "Het rooster van ". time2str('EEEE d LLLL', $firstMissed->start) ." was leeg. Zodra het rooster weer is ingevoerd zal deze o.a. weer in de scipio-app verschijnen.";
							}
						}
      	
						$alert[] = "";
						$alert[] = "Klik <a href='". $ScriptURL ."rooster/index.php?id=". $rooster->id ."'>hier</a> om direct naar het rooster te gaan om deze aan te vullen.";
						$alert[] = "Mocht het rooster niet meer gebruikt worden, of mocht je andere vragen hebben, neem dan even contact op.";
						$alert[] = "";
						$alert[] = "Groet,";
						$alert[] = "Matthijs";
						$alert[] = "";
						$alert[] = (count($andereOntvangers) > 0 ? "Deze mail is ook naar ". makeOpsomming($andereOntvangers) .' gestuurd.' : '');
						//$alert[] = "";
						$alert[] = "<!-- lastDienst: ". date('d-m-Y', $last_data['lastDienst']). "; firstMissed: ". date('d-m-Y', $firstMissed->start). "; deadline: ". date('d-m-Y', $deadline) ." -->";
						$alert[] = "<!-- ". ($verlopen ? 'verlopen' : 'niet verlopen') . "; ". ($lastWarning ? 'laatste waarschuwing' : 'nog geen laatste waarschuwing') ." -->";
						
						#echo '['. $beheerder->id .']';

						$mail = new KKDMailer();
						$mail->aan	= $beheerder->id;
						$mail->Body	= implode("<br>\n", $alert);
						$mail->Subject = "Rooster-alert '". $rooster->naam ."'";
						if(!$productieOmgeving)	$mail->testen = true;
												
						if($mail->sendMail()) {
							toLog("Rooster-alert ". $rooster->naam ." verstuurd", '', $beheerder->id);
							echo 'Mail verstuurd<br>';
						} else {
							toLog("Kon geen rooster-alert ". $rooster->naam ." versturen", 'error', $beheerder->id);
							echo "Kon geen rooster-alert ". $rooster->naam ." versturen<br>";
						}
											
					}
				}
			}
		}
	}
}
?>