<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_HeaderFooter.php');
include_once('../../../general_include/class.phpmailer.php');
$db = connect_db();

$roosters = getRoosters();
$week = 7*24*60*60;

# Doorloop alle roosters
foreach($roosters as $rooster) {	
	
	# Vraag data over het rooster op
	$roosterData			= getRoosterDetails($rooster);

	# Is ingesteld dat er gealarmeerd moet worden
	if($roosterData['alert'] > 0) {
		
		# Is het een "normaal" rooster
		if($roosterData['text_only'] == 0) {
			$sql = "SELECT * FROM $TableDiensten, $TablePlanning WHERE $TablePlanning.$PlanningDienst = $TableDiensten.$DienstID AND $TablePlanning.$PlanningGroup = $rooster AND $TableDiensten.$DienstStart > ". (time()+(($roosterData['alert']-1)*$week));
		# Of text-only rooster
		} elseif($roosterData['text_only'] == 1) {
			$sql = "SELECT * FROM $TableDiensten, $TablePlanningTxt WHERE $TablePlanningTxt.$PlanningTxTDienst = $TableDiensten.$DienstID AND $TablePlanningTxt.$PlanningTxTGroup = $rooster AND $TableDiensten.$DienstStart > ". (time()+(($roosterData['alert']-1)*$week));
		}
		$result = mysqli_query($db, $sql);

		echo $roosterData['naam'] .' -> '. mysqli_num_rows($result) .'<br>';

		# Er zijn geen diensten
		if(mysqli_num_rows($result) == 0) {
			# Initialiseren
			$lastWarning = $verlopen = false;
			
			# Wat is de laatste dienst die nog wel gevonden is
			# Eerst in geval van een "normaal" rooster, daarna bij een text-only rooster
			if($roosterData['text_only'] == 0) {
				$sql_last = "SELECT MAX($TableDiensten.$DienstStart) as lastDienst FROM $TableDiensten, $TablePlanning WHERE $TablePlanning.$PlanningDienst = $TableDiensten.$DienstID AND $TablePlanning.$PlanningGroup = $rooster";
			} else {
				$sql_last = "SELECT MAX($TableDiensten.$DienstStart) as lastDienst FROM $TableDiensten, $TablePlanningTxt WHERE $TablePlanningTxt.$PlanningTxTDienst = $TableDiensten.$DienstID AND $TablePlanningTxt.$PlanningTxTGroup = $rooster"; 
			}
			
			$result_last = mysqli_query($db, $sql_last);
			$row_last = mysqli_fetch_array($result_last);
			
			# Als er een eerst volgende gevonden is verder gaan
			if($row_last['lastDienst'] > 0) {
				# De eerst volgende dienst 10 uur daarna, is de eerst "gemiste" dienst
				# die 10 uur is om te voorkomen dat bij een morgendienst
				# de middagdienst als eerst volgende missend gezien wordt
				$startTijd		= $row_last['lastDienst']+(10*60*60);
				$missed				= getKerkdiensten($startTijd, $startTijd+$week);
				$firstMissed	= getKerkdienstDetails(current($missed));
      	
				# Ingewikkelde rekensom om deadline uit te rekenen.
				# De deadline van 4 weken is meestal voor de mail naar de voorganger (=18 dagen)
				# De deadline van 2 weken is meestal voor de reminder-mail (=3 dagen)
				# De deadline van 1 week is meestal voor een text-only rooster (=1 dag)
				if($roosterData['alert'] == 4) {
					$offset = 18;
				} elseif($roosterData['alert'] == 2) {
					$offset = 3;
				} elseif($roosterData['alert'] == 1) {
					$offset = 1;
				}
				
				# Reken op basis van de eerst gemiste dienst icm de offset uit
				# wat de deadline is voor het invullen van het rooster
				$deadline = mktime (0,1,2, date("n", $firstMissed['start']), (date("j", $firstMissed['start'])-$offset), date("Y", $firstMissed['start']));
				
				//echo "lastDienst :". date('d-m-Y', $row_last['lastDienst']) ."|firstMissed :". date('d-m-Y', $firstMissed['start']) ."|deadline :". date('d-m-Y', $deadline) .'<br>';
      	
				# Als de deadline verlopen is komen we "in de gevarenzone"
				# Als de eerst gemiste dienst al geweest is, is het rooster verlopen
				# Als de deadline net (= 1 week) verlopen is nog een laatste waarschuwing sturen
				if($deadline < time()) {
					if($firstMissed['start'] < time()) {
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
      	
				if((!$verlopen OR $lastWarning) AND $deadline> 0) {
					# geadresseerden
					$beheerders = array();
					$beheerders = getGroupMembers($roosterData['beheerder']);
					$beheerders[] = 984285;
      	
					foreach($beheerders as $beheerder) {
						# $parameters['to'] = ;
						# $parameters['cc'] = ;
						# $parameters['bcc'] = ;
						# $parameters['message'] = ;
						# $parameters['subject'] = ;
      	
						# Mail opstellen
						$alert = array();
						$alert[] = "Goedemorgen ". makeName($beheerder, 1) .",";
						$alert[] = "";
						$alert[] = "dit is een automatisch gegenereerde mail om aan te geven dat het huidige online rooster '". $roosterData['naam'] ."' ". ($verlopen ? '' : 'bijna ') ."afgelopen is.";
						$alert[] = "";
				  
						if(!$lastWarning AND $roosterData['text_only'] == 0) {
							$alert[] = "Om te zorgen dat iedereen op tijd remindermails krijgt/er geen lege mails verstuurd worden, moet je v&oacute;&oacute;r ". time2str('%A %e %B', $deadline) ." het nieuwe rooster invoeren.";
						} elseif($lastWarning AND $roosterData['text_only'] == 0) {
							if($firstMissed['start'] < time()) {
								$alert[] = "Voor afgelopen ". time2str('%A', $firstMissed['start']) ." zijn geen reminder-mails verstuurd. Zodra het rooster weer is ingevoerd zal iedereen weer remindermails krijgen en zal deze o.a. weer in de scipio-app verschijnen.";
							} else {
								$alert[] = "Het rooster van ". time2str('%A %e %B', $firstMissed['start']) ." heeft een lege plek en is niet meegenomen in de mails die daarover verstuurd zijn. Zodra het rooster weer is ingevoerd zal iedereen weer de juiste mails krijgen en zal deze o.a. weer in de scipio-app verschijnen.";
							}
						} elseif(!$lastWarning AND $roosterData['text_only'] == 1) {
							$alert[] = "Om te zorgen dat er geen plekken in het rooster ontstaan, moet je v&oacute;&oacute;r ". time2str('%A %e %B', $deadline) ." het nieuwe rooster invoeren.";
						} elseif($lastWarning AND $roosterData['text_only'] == 1) {
							if($firstMissed['start'] < time()) {
								$alert[] = "Het rooster voor afgelopen ". time2str('%A', $firstMissed['start']) ." was leeg. Zodra het rooster weer is ingevoerd zal deze o.a. weer in de scipio-app verschijnen.";
							} else {
								$alert[] = "Het rooster van ". time2str('%A %e %B', $firstMissed['start']) ." was leeg. Zodra het rooster weer is ingevoerd zal deze o.a. weer in de scipio-app verschijnen.";
							}
						}
      	
						$alert[] = "";
						$alert[] = "Klik <a href='". $ScriptURL ."makeRooster.php?rooster=$rooster'>hier</a> om direct naar het rooster te gaan om deze aan te vullen.";
						$alert[] = "Mocht het rooster niet meer gebruikt worden, of mocht je andere vragen hebben, neem dan even contact op.";
						$alert[] = "";
						$alert[] = "Groet,";
						$alert[] = "Matthijs";
						//$alert[] = "";
						$alert[] = "<!-- lastDienst: ". date('d-m-Y', $row_last['lastDienst']). "; firstMissed: ". date('d-m-Y', $firstMissed['start']). "; deadline: ". date('d-m-Y', $deadline) ." -->";
						$alert[] = "<!-- ". ($verlopen ? 'verlopen' : 'niet verlopen') . "; ". ($lastWarning ? 'laatste waarschuwing' : 'nog geen laatste waarschuwing') ." -->";
						
						//echo implode("<br>\n", $alert);
						//echo '<hr>';
												
						if(sendMail($beheerder, "Rooster-alert '". $roosterData['naam'] ."'", implode("<br>\n", $alert), array())) {
							toLog('info', '', $beheerder, "Rooster-alert ". $roosterData['naam'] ." verstuurd");
							echo 'Mail verstuurd<br>';
						} else {
							toLog('error', '', $beheerder, "Kon geen rooster-alert ". $roosterData['naam'] ." versturen");
							echo "Kon geen rooster-alert ". $roosterData['naam'] ." versturen<br>";
						}
						
						$param['to']			= $beheerder;
						$param['message']	= implode("<br>\n", $alert);
						$param['subject']	= "Rooster-alert '". $roosterData['naam'] ."'";
						sendMail_new($param);										
					}
				}
			}
		}
	}
}
?>
