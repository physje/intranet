<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');

# Deze gebruiken voor reguliere sync
# De eerste keer kan je beter firstRun.php gebruiken

$db = connect_db();

$listIDs['leden']					= $LPLedenListID;
$listIDs['trinitas']			= $LPTrinitasListID;
$listIDs['koningsmail']		= $LPKoningsmailListID;
$listIDs['gebed (dag)'] 	= $LPGebedDagListID;
$listIDs['gebed (week)'] 	= $LPGebedWeekListID;
$listIDs['gebed (maand)']	= $LPGebedMaandListID;
$listIDs = $listIDs+$LPWijkListID;	

# Ga op zoek naar alle personen met een mailadres
# Mailadres is daarbij alles met een @-teken erin
$sql = "SELECT * FROM $TableUsers WHERE $UserMail like '%@%' AND $UserStatus = 'actief'";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);
do {
	# 3 seconden per persoon moet voldoende zijn
	set_time_limit(5);
	
	# identifier is het id binnen scipio
	$scipioID = $row[$UserID];
		
	# Haal alle gegevens op
	$data = getMemberDetails($scipioID); 
	$email = $data['mail'];
	
	# LaPosta staat of valt met een correct mailadres
	# Eerst dus even een check of het adres geldig is
	if(isValidEmail($email)) {		
		$custom_fields['voornaam'] = $data['voornaam'];
		$custom_fields['tussenvoegsel'] = $data['tussenvoegsel'];
		$custom_fields['achternaam'] = $data['achternaam'];
		$custom_fields['geslacht'] = ($data['geslacht'] == 'M'?'Man':'Vrouw');
		$custom_fields_short = $custom_fields;
		
		$custom_fields['wijk'] = $wijk = $data['wijk'];
		$custom_fields['geboortedatum'] = $data['geboorte'];
		$custom_fields['relatie'] = $data['relatie'];
		$custom_fields['status'] = $data['belijdenis'];
		$custom_fields['scipioid'] = $scipioID;
			
  	                     			
		# Van elke persoon vraag ik op of die al voorkomt in mijn lokale mailchimp-database.
		# 	dat is iets sneller dan aan mailchimp vragen of die al voorkomt én
		#		ik kan dan werken met het scipio id als identiefier ipv het mailadres (wat LP doet)		
		$sql_lp = "SELECT * FROM $TableLP WHERE $LPID = $scipioID";
		$result_lp = mysqli_query($db, $sql_lp);
		
		# Komt hij niet voor dan moet hij aan LP worden toegevoegd
		#  en alle variabelen ingesteld
		if(mysqli_num_rows($result_lp) == 0) {
			# Komt ook niet voor in LP
			if(!lp_onList($LPLedenListID, $email)) {			
				# Toevoegen aan de leden-lijst
				$addMember = lp_addMember($LPLedenListID, $email, $custom_fields);
				if($addMember === true) {
					toLog('info', '', $scipioID, 'Toegevoegd aan LaPosta ledenlijst');
					echo makeName($scipioID, 6) ." toegevoegd aan de LaPosta ledenlijst<br>\n";
				} else {
					toLog('error', '', $scipioID, 'ledenlijst: '. $addMember['error']);
				}
				
				# Toevoegen aan de Trinitas-lijst
				$addMember = lp_addMember($LPTrinitasListID, $email, $custom_fields_short);
				if($addMember === true) {
					toLog('debug', '', $scipioID, 'Toegevoegd aan LaPosta Trinitaslijst');
					echo makeName($scipioID, 6) ." toegevoegd aan de LaPosta Trinitaslijst<br>\n";
				} else {
					toLog('error', '', $scipioID, 'trinitas: '. $addMember['error']);
				}
				
				# Toevoegen aan de Koningsmail-lijst
				$addMember = lp_addMember($LPKoningsmailListID, $email, $custom_fields_short);
				if($addMember === true) {
					toLog('debug', '', $scipioID, 'Toegevoegd aan LaPosta Koningsmaillijst');
					echo makeName($scipioID, 6) ." toegevoegd aan LaPosta Koningsmaillijst<br>\n";
				} else {
					toLog('error', '', $scipioID, 'koningsmail: '. $addMember['error']);
				}
				
				# Toevoegen aan de juiste wijkmail-lijst			
				$addMember = lp_addMember($LPWijkListID[$wijk], $email, $custom_fields_short);
				if($addMember === true) {			
					toLog('debug', '', $scipioID, 'Toegevoegd aan LaPosta wijklijst wijk '. $wijk);
					echo makeName($scipioID, 6) ." toegevoegd aan LaPosta wijklijst wijk $wijk<br>\n";
				} else {
					toLog('error', '', $scipioID, 'wijk '. $wijk .': '. $addMember['error']);
				}
				
			} else {			
				# Updaten in leden-lijst
				$updateMember = lp_updateMember($LPLedenListID, $email, $custom_fields);
				if($updateMember === true) {
					toLog('info', '', $scipioID, 'Bestond nog niet lokaal maar wel in LP');
					echo makeName($scipioID, 6) ." toegevoegd en geupdate<br>\n";
				} else {
					toLog('error', '', $scipioID, 'nieuw update: '. $updateMember['error']);
				}			
			}		
					
			# De wijzigingen aan de LP kant moeten ook verwerkt worden in mijn lokale mailchimp-database
			$sql_lp_insert = "INSERT INTO $TableLP ($LPID, $LPgeslacht, $LPmail, $LPVoornaam, $LPTussenvoegsel, $LPAchternaam, $LPwijk, $LPstatus, $LPrelatie, $LPdoop, $LPlastChecked, $LPlastSeen) VALUES ($scipioID, '". $data['geslacht'] ."', '". $data['mail'] ."', '". $data['voornaam'] ."', '". urlencode($data['tussenvoegsel']) ."', '". $data['achternaam'] ."', '". $data['wijk'] ."', 'actief', '". $data['relatie'] ."', '". $data['belijdenis'] ."', ". time() .", ". time() .")";
			if(mysqli_query($db, $sql_lp_insert)) {
				toLog('debug', '', $scipioID, 'LaPosta-data na sync toegevoegd in lokale LP-tabel');
			} else {
				echo $sql_lp_insert;
				toLog('error', '', $scipioID, 'Kon na sync niets toevoegen in lokale LP-tabel');
			}				
			
		# Komt hij wel voor dan check ik even of een aantal velden gewijzigd zijn :
		#		mailadres / naam / wijk / kerkelijke status / relatie
		} else {
			$changed_short = $changedMail = false;
			$oldMail = $newMail = '';
			$affectedLists = array();	
			unset($changed_field);
							
			$row_lp = mysqli_fetch_array($result_lp);
			$formerMail = $row_lp[$LPmail];
			
			$sql_update = array();
			$sql_update[] = "$LPlastSeen = ". time();
			
			# Als mensen zichzelf hebben uitgeschreven, is hun adres
			# bij LaPosta geblokeerd en mag ik er niks aan wijzigen
			if($row_lp[$LPstatus] != 'opgezegd') {
				# Stond in de tabel als niet ingeschreven
				if($row_lp[$LPstatus] == 'uitgeschreven') {
					$resubscribe = lp_resubscribeMember($LPLedenListID, $email);
					if($resubscribe === true) {
						toLog('info', '', $scipioID, 'Opnieuw ingeschreven in de LaPosta ledenlijst');
						$sql_update[] = "$LPstatus = 'actief'";
					} else {
						toLog('error', '', $scipioID, 'resubscribe: '. $resubscribe['error']);
					}				
				}
				
							
				# Gewijzigde naam
				if(urldecode($row_lp[$LPVoornaam]) != $data['voornaam'] OR urldecode($row_lp[$LPTussenvoegsel]) != $data['tussenvoegsel'] OR urldecode($row_lp[$LPAchternaam]) != $data['achternaam']) {
					$changed_field['voornaam']			= $data['voornaam'];
					$changed_field['tussenvoegsel'] = $data['tussenvoegsel'];
					$changed_field['achternaam']		= $data['achternaam'];
					$changed_short = true;
					toLog('info', '', $scipioID, "Naam gewijzigd in LaPosta '". $naam ."'");
					
					$sql_update[] = "$LPVoornaam = '". urlencode($data['voornaam']) ."'";
					$sql_update[] = "$LPTussenvoegsel = '". urlencode($data['tussenvoegsel']) ."'";
					$sql_update[] = "$LPAchternaam = '". urlencode($data['achternaam']) ."'";
				}
				
										
				# Gewijzigd geslacht
				if($row_lp[$LPgeslacht] != $data['geslacht']) {				
					$changed_field['geslacht'] = ($data['geslacht'] == 'M'?'Man':'Vrouw');					
					$changed_short = true;
					toLog('info', '', $scipioID, "Geslacht gewijzigd dus ook gewijzigd in LaPosta");
					$sql_update[] = "$LPgeslacht = '". $data['geslacht'] ."'";
				}
							
				# Gewijzigd mailadres
				if($row_lp[$LPmail] != $data['mail']) {
					$oldMail = $row_lp[$LPmail];
					$newMail = $data['mail'];
					$changedMail = true;
					$sql_update[] = "$LPmail = '". $data['mail'] ."'";				
				}
						
				# Welke mailinglijsten zijn betrokken
				if($changedMail OR $changed_short) {
					# Controleer welke lijsten allemaal getroffen zijn		
					foreach($listIDs as $naam => $listID) {					
						if(lp_onList($listID, $row_lp[$LPmail])) {
							 $affectedLists[$naam] = $listID;
						}
					}
				}
				
				
				# Gewijzigde mail verwerken
				if($changedMail) {
					foreach($affectedLists as $naam => $list) {
						$changeMailAddress = lp_changeMailAddress($list, $oldMail, $newMail);
						if($changeMailAddress === true) {
							toLog('info', '', $scipioID, "Mailadres gewijzigd in LaPosta lijst '". $naam ."'");
						} else {
							//toLog('error', '', $scipioID, "Mailadres lokaal gewijzigd, maar niet gewijzigd in LaPosta '". $naam ."'");
							toLog('error', '', $scipioID, "wijzig mail voor '". $naam ."': ". $changeMailAddress['error']);
						}
					}
				}			
				
				
				# Gewijzigde naam of geslacht verwerken
				if($changed_short) {								
					foreach($affectedLists as $naam => $list) {
						$updateMember = lp_updateMember($list, $email, $custom_fields_short);
						if($updateMember === true) {
							toLog('info', '', $scipioID, "Relatie gewijzigd in LaPosta lijst '". $naam ."'");
						} else {
							//toLog('error', '', $scipioID, "Relatie gewijzigd maar niet gewijzigd in LaPosta '". $naam ."'");
							toLog('error', '', $scipioID, "wijzig relatie voor '". $naam ."': ". $updateMember['error']);
						}
					}
				}
							
									
				# Gewijzigde wijk
				if($row_lp[$LPwijk] != $wijk) {
					$oudeWijk = $row_lp[$LPwijk];
					$changed_field['wijk'] = array($wijk);
					
					# Stel iemand heeft zich uitgeschreven voor zijn/haar oude wijk
					# dan moet hij/zij niet worden ingeschreven bij de nieuwe wijk
					# dus even een check of iemand 'lid' is van de oude wijk				
					if(lp_onList($LPWijkListID[$oudeWijk], $email)) {					
						$addmember = lp_addMember($LPWijkListID[$wijk], $email, $custom_fields_short);
						$unsubscribeMember = lp_unsubscribeMember($LPWijkListID[$oudeWijk], $email);
						
						if($addmember === true AND $unsubscribeMember === true) {
							toLog('info', '', $scipioID, "Wijk gewijzigd ($oudeWijk -> $wijk), verplaatst naar nieuwe LaPosta lijst");
							$sql_update[] = "$LPwijk = '$wijk'";
						} else {
							//toLog('error', '', $scipioID, "Wijk gewijzigd ($oudeWijk -> $wijk) maar niet verplaatst in LaPosta");
							toLog('error', '', $scipioID, "-> $wijk: ". $addmember['error']);
							toLog('error', '', $scipioID, "$oudeWijk ->: ". $unsubscribeMember['error']);
						}
					} else {
						toLog('info', '', $scipioID, "Wijk gewijzigd ($oudeWijk -> $wijk) maar was onbekend in $oudeWijk");
					}
				}
				
			
				# Gewijzigde kerkelijke status
				if($row_lp[$LPdoop] != $data['belijdenis']) {
					$oudeStatus = $row_lp[$LPdoop];				
					$changed_field['status'] = $data['belijdenis'];
					$sql_update[] = "$LPdoop = '". $data['belijdenis'] ."'";
					toLog('info', '', $scipioID, "Kerkelijke status gewijzigd ($oudeStatus -> $status) dus gewijzigd in LaPosta");
				}
			
			
				# Gewijzigde kerkelijke relatie
				if($row_lp[$LPrelatie] != $data['relatie']) {
					$oudeRelatie = $row_lp[$LPrelatie];				
					$changed_field['relatie'] = $data['relatie'];				
					$sql_update[] = "$LPrelatie = '". $data['relatie'] ."'";
					toLog('info', '', $scipioID, "Kerkelijke relatie gewijzigd (". $row_lp[$LPrelatie] ." -> ". $data['relatie'] .") dus gewijzigd in LaPosta");
				}			
				
				
				# Gewijzigde status of relatie verwerken
				if(isset($changed_field)) {				
					if(!$LPFullUpdate) {
						$custom_fields = $changed_field;
					}
					
					$updateMember = lp_updateMember($LPLedenListID, $email, $custom_fields);				
					if($updateMember === true) {
						toLog('info', '', $scipioID, "Gegevens aangepast in LaPosta ledenlijst");					
					} else {					
						toLog('error', '', $scipioID, "update ledenlijst: ". $updateMember['error']);
						toLog('debug', '', $scipioID, $LPLedenListID.'|'.$email.'|'.json_encode($custom_fields));
					}				
				}			
			}
			
			# De wijzigingen aan de LP kant moeten ook verwerkt worden in mijn lokale mailchimp-database
			$sql_lp_update = "UPDATE $TableLP SET ". implode(', ', $sql_update)." WHERE $LPID like $scipioID";
			mysqli_query($db, $sql_lp_update);
		}
	} else {
		toLog('error', '', $scipioID, 'Ongeldig mailadres');
	}	
} while($row = mysqli_fetch_array($result));

toLog('info', '', '', 'Synchronisatie naar LaPosta uitgevoerd');

# Verwijder adressen die al even niet meer gezien zijn
//$deadline = mktime (0, 0, 0, date("n"), (date("j")-1));
$deadline = mktime ((date('H')-13));
$sql_lp_unsub = "SELECT * FROM $TableLP WHERE $LPstatus like 'actief' AND $LPlastSeen < ". $deadline;
$result_unsub = mysqli_query($db, $sql_lp_unsub);
if($row_unsub = mysqli_fetch_array($result_unsub)) {
	do {
		set_time_limit(5);
		$email = $row_unsub[$LPmail];
		
		$sql_unique = "SELECT * FROM $TableLP WHERE $LPmail like '$email' AND $LPlastSeen > ". $deadline;
		$result_unique = mysqli_query($db, $sql_unique);
		if(mysqli_num_rows($result_unique) == 0) {
			foreach($listIDs as $naam => $listID) {
				if(lp_onList($listID, $email)) {					
					$unsubscribeMember = lp_unsubscribeMember($listID, $email);
					if($unsubscribeMember === true) {
						toLog('debug', '', $row_unsub[$LPID], 'Uitgeschreven voor '. $naam);
					} else {
						toLog('error', '', $row_unsub[$LPID], "Uitschrijven voor '". $naam ."': ". $unsubscribeMember['error']);
					}
				}
			}	
		
			toLog('info', '', $row_unsub[$LPID], 'Uitschrijving gesynced naar LaPosta');
			mysqli_query($db, "UPDATE $TableLP SET $LPstatus = 'uitgeschreven' WHERE $LPID = ". $row_unsub[$LPID]);
		} else {
			toLog('info', '', $row_unsub[$LPID], $email.' lijkt vaker voor te komen, niet uitgeschreven bij LaPosta');
		}
	} while($row_unsub = mysqli_fetch_array($result_unsub));
}
?>