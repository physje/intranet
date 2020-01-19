<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');

/*
$start = getParam('start', 0);
$stap = 5;

echo '<html>';
echo '<head>';
echo '	<meta http-equiv="refresh" content="0; url=?start='. ($start+$stap) .'" />';
echo '</head>';
echo '<body>';
*/

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
//$sql = "SELECT * FROM $TableUsers WHERE $UserMail like '%@%' AND $UserStatus = 'actief' LIMIT $start, $stap";
//$sql = "SELECT * FROM $TableUsers WHERE $UserMail like '%@%' LIMIT $start, $stap";
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
	
	$custom_fields['voornaam'] = $data['voornaam'];
	$custom_fields['tussenvoegsel'] = $data['tussenvoegsel'];
	$custom_fields['achternaam'] = $data['achternaam'];
	$custom_fields['geslacht'] = ($data['geslacht'] == 'M'?'Man':'Vrouw');
	$custom_fields_short = $custom_fields;
	
	$wijk = $data['wijk'];
	$custom_fields['wijk'] = array($wijk);
	$custom_fields['geboortedatum'] = $data['geboorte'];
	$custom_fields['relatie'] = $data['relatie'];
	$custom_fields['status'] = $data['belijdenis'];
	$custom_fields['scipioid'] = $scipioID;
	//$custom_fields['hash'] = $data['hash_long'];
	//$custom_fields['mailinglijsten'] = array('Koningsmail', 'Wijkmail');
		
                       			
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
			if(lp_addMember($LPLedenListID, $email, $custom_fields)) {
				toLog('info', '', $scipioID, 'Toegevoegd aan LaPosta ledenlijst');
				echo makeName($scipioID, 6) ." toegevoegd aan de LaPosta ledenlijst<br>\n";
			} else {
				toLog('error', '', $scipioID, 'Kon niet toevoegen aan LaPosta');
			}
			
			# Toevoegen aan de Trinitas-lijst
			if(lp_addMember($LPTrinitasListID, $email, $custom_fields_short)) {
				toLog('debug', '', $scipioID, 'Toegevoegd aan LaPosta Trinitaslijst');
				echo makeName($scipioID, 6) ." toegevoegd aan de LaPosta Trinitaslijst<br>\n";
			} else {
				toLog('error', '', $scipioID, 'Kon niet toevoegen aan LaPosta Trinitas');
			}
			
			# Toevoegen aan de Koningsmail-lijst
			if(lp_addMember($LPKoningsmailListID, $email, $custom_fields_short)) {
				toLog('debug', '', $scipioID, 'Toegevoegd aan LaPosta Koningsmaillijst');
				echo makeName($scipioID, 6) ." toegevoegd aan LaPosta Koningsmaillijst<br>\n";
			} else {
				toLog('error', '', $scipioID, 'Kon niet toevoegen aan LaPosta Koningsmaill');
			}
			
			# Toevoegen aan de juiste wijkmail-lijst			
			if(lp_addMember($LPWijkListID[$wijk], $email, $custom_fields_short)) {
				toLog('debug', '', $scipioID, 'Toegevoegd aan LaPosta wijklijst wijk '. $wijk);
				echo makeName($scipioID, 6) ." toegevoegd aan LaPosta wijklijst wijk $wijk<br>\n";
			} else {
				toLog('error', '', $scipioID, 'Kon niet toevoegen aan LaPosta wijkmail '. $wijk);
			}
			
		} else {			
			# Updaten in leden-lijst
			if(lp_updateMember($LPLedenListID, $email, $custom_fields)) {
				toLog('info', '', $scipioID, 'Bestond nog niet lokaal maar wel in LP');
				echo makeName($scipioID, 6) ." toegevoegd en geupdate<br>\n";
			} else {
				toLog('error', '', $scipioID, 'Kon niet syncen naar LaPosta');
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
				if(lp_resubscribeMember($LPLedenListID, $email)) {
					toLog('info', '', $scipioID, 'Opnieuw ingeschreven in de LaPosta ledenlijst');
					$sql_update[] = "$LPstatus = 'actief'";
				} else {
					toLog('error', '', $scipioID, 'Kon niet opnieuw inschrijven in de LaPosta ledenlijst');
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
				$sql_update[] = "$LPgeslacht = '$geslacht'";
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
					if(lp_changeMailAddress($list, $oldMail, $newMail)) {
						toLog('info', '', $scipioID, "Mailadres gewijzigd in LaPosta lijst '". $naam ."'");
					} else {
						toLog('error', '', $scipioID, "Mailadres lokaal gewijzigd, maar niet gewijzigd in LaPosta '". $naam ."'");
					}
				}
			}			
			
			
			# Gewijzigde naam of geslacht verwerken
			if($changed_short) {								
				foreach($affectedLists as $naam => $list) {
					if(lp_updateMember($list, $email, $custom_fields_short)) {
						toLog('info', '', $scipioID, "Relatie gewijzigd in LaPosta lijst '". $naam ."'");
					} else {
						toLog('error', '', $scipioID, "Relatie gewijzigd maar niet gewijzigd in LaPosta '". $naam ."'");
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
					if(lp_addMember($LPWijkListID[$wijk], $email, $custom_fields_short) AND lp_unsubscribeMember($LPWijkListID[$oudeWijk], $email)) {
						toLog('info', '', $scipioID, "Wijk gewijzigd ($oudeWijk -> $wijk), verplaatst naar nieuwe LaPosta lijst");
						$sql_update[] = "$LPwijk = '$wijk'";
					} else {
						toLog('error', '', $scipioID, "Wijk gewijzigd ($oudeWijk -> $wijk) maar niet verplaatst in LaPosta");
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
				$sql_update[] = "$LPrelatie = '$relatie'";
				toLog('info', '', $scipioID, "Kerkelijke relatie gewijzigd (". $row_lp[$LPrelatie] ." -> ". $data['relatie'] .") dus gewijzigd in LaPosta");
			}			
			
			
			# Gewijzigde status of relatie verwerken
			if(isset($changed_field)) {				
				if(!$LPFullUpdate) {
					$custom_fields = $changed_field;
				}
				
				if(lp_updateMember($LPLedenListID, $email, $custom_fields)) {
					toLog('info', '', $scipioID, "Gegevens aangepast in LaPosta ledenlijst");
					toLog('debug', '', $scipioID, $LPLedenListID.'|'.$email.'|'.json_encode($custom_fields));
				} else {
					toLog('error', '', $scipioID, "Kon gegevens niet updaten in LaPosta ledenlijst");
				}				
			}			
		}
		
		# De wijzigingen aan de LP kant moeten ook verwerkt worden in mijn lokale mailchimp-database
		$sql_lp_update = "UPDATE $TableLP SET ". implode(', ', $sql_update)." WHERE $LPID like $scipioID";
		mysqli_query($db, $sql_lp_update);
		echo $sql_lp_update .'<br>';
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
		
		foreach($listIDs as $naam => $listID) {
			if(lp_onList($listID, $email)) {
				if(lp_unsubscribeMember($listID, $email)) {
					toLog('debug', '', $row_unsub[$LPID], 'Uitgeschreven voor '. $naam);
				} else {
					toLog('error', '', $row_unsub[$LPID], 'Kon niet uitschrijven voor '. $naam);
				}
			}
		}
		
		toLog('info', '', $row_unsub[$LPID], 'Uitschrijving gesynced naar LaPosta');
		mysqli_query($db, "UPDATE $TableLP SET $LPstatus = 'uitgeschreven' WHERE $LPID = ". $row_unsub[$LPID]);
	} while($row_unsub = mysqli_fetch_array($result_unsub));
}
?>
