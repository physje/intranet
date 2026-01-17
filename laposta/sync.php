<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');
include_once('../Classes/Member.php');
include_once('../Classes/LaPostaData.php');
include_once('../Classes/Logging.php');

# Deze gebruiken voor reguliere sync
# De eerste keer kan je beter firstRun.php gebruiken
$listIDs['leden']			= $LPLedenListID;
$listIDs['trinitas']		= $LPTrinitasListID;
$listIDs['trinitas (week)']	= $LPWeekTrinitasListID;
$listIDs['koningsmail']		= $LPKoningsmailListID;
$listIDs['gebed (dag)'] 	= $LPGebedDagListID;
$listIDs['gebed (week)'] 	= $LPGebedWeekListID;
$listIDs['gebed (maand)']	= $LPGebedMaandListID;
$listIDs['70 plus']			= $LP70plusListID;
$listIDs = $listIDs+$LPWijkListID;

$zeventigPlus = mktime(0,0,0, date('m'), date('d'), (date('Y')-70));

# Ga op zoek naar alle personen met een mailadres
$adressen = Member::getMailadressen();

foreach($adressen as $id => $email_encoded) {
	# 5 seconden per persoon moet voldoende zijn
	set_time_limit(5);

	# Mail staat encoded in database
	$email = urldecode($email_encoded);
				
	# Maak lid-object aan
	$lid = new Member($id);
	
	# LaPosta staat of valt met een correct mailadres
	# Eerst dus even een check of het adres geldig is
	if(isValidEmail($email)) {		
		$custom_fields['voornaam'] 		= $lid->voornaam;
		$custom_fields['tussenvoegsel']	= $lid->tussenvoegsel;
		$custom_fields['achternaam'] 	= $lid->achternaam;
		$custom_fields['geslacht'] 		= ($lid->geslacht == 'M'?'Man':'Vrouw');
		$custom_fields['3gkadres'] 		= 'Ja';
		$custom_fields_short = $custom_fields;
		
		$custom_fields['wijk'] 			= $lid->wijk;
		$custom_fields['geboortedatum']	= $lid->geboortedatum;
		$custom_fields['relatie'] 		= $lid->relatie;
		$custom_fields['status'] 		= $lid->doop_belijdenis;
		$custom_fields['scipioid'] 		= $lid->id;			
  	                     			
		# Van elke persoon vraag ik op of die al voorkomt in mijn lokale LaPosta-database.
		# 	dat is iets sneller dan aan LaPosta vragen of die al voorkomt én
		#		ik kan dan werken met het scipio id als identiefier ipv het mailadres (wat LP doet)		
		$LP = new LaPostaData($id);

		$geb_unix = mktime(0,0,0,substr($lid->geboorte_maand, 5, 2),substr($lid->geboorte_dag, 8, 2),substr($lid->geboorte_jaar, 0, 4));
		
		# Komt hij niet voor dan moet hij aan LP worden toegevoegd
		#  en alle variabelen ingesteld
		if($LP->new) {
			# Komt ook niet voor in de ledenlijst van LP
			if(!lp_onList($LPLedenListID, $lid->email)) {
				# Toevoegen aan de leden-lijst
				$addMember = lp_addMember($LPLedenListID, $lid->email, $custom_fields);
				if($addMember === true) {
					toLog('Toegevoegd aan LaPosta ledenlijst', 'info', $lid->id);
					echo $lid->getName() ." toegevoegd aan de LaPosta ledenlijst<br>\n";
				} else {
					toLog('ledenlijst: '. $addMember['error'], 'error', $lid->id);
				}
				
				# Toevoegen aan de Trinitas-lijst
				$addMember = lp_addMember($LPTrinitasListID, $lid->email, $custom_fields_short);
				if($addMember === true) {					
					toLog('Toegevoegd aan LaPosta Trinitaslijst', 'debug', $lid->id);
					echo $lid->getName() ." toegevoegd aan de LaPosta Trinitaslijst<br>\n";
				} else {
					toLog('trinitas: '. $addMember['error'], 'error', $lid->id);
				}
				
				# Toevoegen aan de wekelijkse Trinitas-lijst
				$addMember = lp_addMember($LPWeekTrinitasListID, $lid->email, $custom_fields_short);
				if($addMember === true) {
					toLog('Toegevoegd aan LaPosta wekelijkse Trinitaslijst', 'debug', $lid->id);
					echo $lid->getName() ." toegevoegd aan LaPosta wekelijkse Trinitaslijst<br>\n";
				} else {
					toLog('wekelijkse trinitas: '. $addMember['error'], 'error', $lid->id);
				}
				
				# Toevoegen aan de Koningsmail-lijst
				$addMember = lp_addMember($LPKoningsmailListID, $lid->email, $custom_fields_short);
				if($addMember === true) {
					toLog('Toegevoegd aan LaPosta Koningsmaillijst', 'debug', $lid->id);
					echo $lid->getName() ." toegevoegd aan LaPosta Koningsmaillijst<br>\n";
				} else {
					toLog('koningsmail: '. $addMember['error'], 'error', $lid->id);
				}
				
				# Toevoegen aan de juiste wijkmail-lijst			
				$addMember = lp_addMember($LPWijkListID[$lid->wijk], $lid->email, $custom_fields_short);
				if($addMember === true) {			
					toLog('Toegevoegd aan LaPosta wijklijst wijk '. $lid->wijk, 'debug', $lid->id);
					echo $lid->getName() ." toegevoegd aan LaPosta wijklijst wijk ". $lid->wijk ."<br>\n";
				} else {
					toLog('Wijk '. $lid->wijk .': '. $addMember['error'], 'error', $lid->id);
				}
				
				# Toevoegen aan de 70+-lijst (indien nodig)
				if($geb_unix < $zeventigPlus) {
					$addMember = lp_addMember($LP70plusListID, $lid->email, $custom_fields_short);
					if($addMember === true) {
						toLog('Toegevoegd aan LaPosta 70+-lijst', 'debug', $lid->id);
						echo $lid->getName() ." toegevoegd aan LaPosta 70+<br>\n";
					} else {
						toLog('70+: '. $addMember['error'], 'error', $lid->id);
					}
				}				
			} else {			
				# Updaten in leden-lijst
				$updateMember = lp_updateMember($LPLedenListID, $lid->email, $custom_fields);
				if($updateMember === true) {
					toLog('Bestond nog niet lokaal maar wel in LP', '', $lid->id);
					echo $lid->getName() ." toegevoegd en geupdate<br>\n";
				} else {
					toLog('nieuw update: '. $updateMember['error'], 'error', $lid->id);
				}			
			}
						
			$LP->status = 'actief';
			$LP->geslacht = $lid->geslacht;
			$LP->voornaam		= $lid->voornaam;
			$LP->tussenvoegsel	= $lid->tussenvoegsel;
			$LP->achternaam		= $lid->achternaam;	
			$LP->mail = $lid->email;
			$LP->wijk = $lid->wijk;
			$LP->relatie = $lid->relatie;
			$LP->doop = $lid->doop_belijdenis;
			$LP->lastSeen = time();
			$LP->lastChecked = time();
								
			# De wijzigingen aan de LP kant moeten ook verwerkt worden in mijn lokale laposta-database
			if($LP->save()) {
				toLog('LaPosta-data na sync toegevoegd in lokale LP-tabel', 'debug', $lid->id);
			} else {
				toLog('Kon na sync niets toevoegen in lokale LP-tabel', 'error', $lid->id);
			}				
			
		# Komt hij wel voor dan check ik even of een aantal velden gewijzigd zijn :
		#		mailadres / naam / wijk / kerkelijke status / relatie
		} else {
			$changed_short = $changedMail = false;
			$oldMail = $newMail = '';
			$affectedLists = $changed_part = array();	
			unset($changed_field);							
			
			$formerMail = $LP->mail;
			$LP->lastSeen = time();
			#$sql_update = array();
			#$sql_update[] = "$LPlastSeen = ". time();
			
			# Als mensen zichzelf hebben uitgeschreven, is hun adres
			# bij LaPosta geblokeerd en mag ik er niks aan wijzigen
			if($LP->status != 'opgezegd') {
				# Stond in de tabel als niet ingeschreven
				if($LP->status == 'uitgeschreven') {
					$resubscribe = lp_resubscribeMember($LPLedenListID, $lid->email);
					if($resubscribe === true) {
						toLog('Opnieuw ingeschreven in de LaPosta ledenlijst', '', $lid->id);
						$LP->status = 'actief';
					} else {
						toLog('resubscribe: '. $resubscribe['error'], 'error', $lid->id);
					}				
				}
				
							
				# Gewijzigde naam
				if($LP->voornaam != $lid->voornaam OR $LP->tussenvoegsel != $lid->tussenvoegsel OR $LP->achternaam != $lid->achternaam) {
					$changed_field['voornaam']		= $lid->voornaam;
					$changed_field['tussenvoegsel'] = $lid->tussenvoegsel;
					$changed_field['achternaam']	= $lid->achternaam;
										
					$LP->voornaam		= $lid->voornaam;
					$LP->tussenvoegsel	= $lid->tussenvoegsel;
					$LP->achternaam		= $lid->achternaam;					

					$changed_short = true;
					$changed_part[] = 'naam';
					toLog("Naam gewijzigd in LaPosta", '', $lid->id);
				}				
										
				# Gewijzigd geslacht
				if($LP->geslacht != $lid->geslacht) {				
					$changed_field['geslacht'] = ($lid->geslacht == 'M'?'Man':'Vrouw');

					$LP->geslacht = $lid->geslacht;

					$changed_short = true;
					$changed_part[] = 'geslacht';
					toLog("Geslacht gewijzigd in LaPosta", '', $lid->id);					
				}
							
				# Gewijzigd mailadres
				if($LP->mail != $lid->email) {					
					$oldMail = $LP->mail;
					#$newMail = $lid->email;
					$changedMail = true;
					$LP->mail = $lid->email;
				}
				
				# Toevoegen aan de 70+-lijst (indien nodig)
				if(!$LP->zeventigPlus && ($geb_unix < $zeventigPlus)) {
					$addMember = lp_addMember($LP70plusListID, $lid->email, $custom_fields_short);
					if($addMember === true) {
						toLog('Toegevoegd aan LaPosta 70+-lijst', '', $lid->id);
						$LP->zeventigPlus = true;
					} else {
						toLog('70+: '. $addMember['error'], 'error', $lid->id);
					}					
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
						$changeMailAddress = lp_changeMailAddress($list, $oldMail, $lid->email);
						if($changeMailAddress === true) {
							toLog("Mailadres gewijzigd in LaPosta lijst '". $naam ."'", '', $lid->id);
						} else {							
							toLog("wijzig mail voor '". $naam ."': ". $changeMailAddress['error'], 'error', $lid->id);
						}
					}
				}			
				
				
				# Gewijzigde naam of geslacht verwerken
				if($changed_short) {								
					foreach($affectedLists as $naam => $list) {
						$updateMember = lp_updateMember($list, $email, $custom_fields_short);
						if($updateMember === true) {							
							toLog(implode(' en ', $changed_part) ." gewijzigd in LaPosta lijst '". $naam ."'", '', $lid->id);
						} else {
							toLog('Wijzigen '. implode(' en ', $changed_part) ." in LaPosta lijst '". $naam ."' mislukt: ". $updateMember['error'], 'error', $lid->id);
						}
					}
				}
							
									
				# Gewijzigde wijk
				if($LP->wijk != $lid->wijk) {					
					$changed_field['wijk'] = array($lid->wijk);
					
					# Stel iemand heeft zich uitgeschreven voor zijn/haar oude wijk
					# dan moet hij/zij niet worden ingeschreven bij de nieuwe wijk
					# dus even een check of iemand 'lid' is van de oude wijk				
					if(lp_onList($LPWijkListID[$LP->wijk], $lid->email)) {					
						$addmember = lp_addMember($LPWijkListID[$lid->wijk], $lid->email, $custom_fields_short);
						$unsubscribeMember = lp_unsubscribeMember($LPWijkListID[$LP->wijk], $lid->email);
						
						if($addmember === true && $unsubscribeMember === true) {
							toLog("Wijk gewijzigd (". $LP->wijk ." -> ". $lid->wijk ."), verplaatst naar nieuwe LaPosta lijst", '', $lid->id);							
						} else {
							toLog("-> ". $lid->wijk .": ". $addmember['error'], 'error', $lid->id);
							toLog($LP->wijk ." ->: ". $unsubscribeMember['error'], 'error', $lid->id);
						}
					} else {
						toLog("Wijk gewijzigd (". $LP->wijk ." -> ". $lid->wijk .") maar was onbekend in ". $LP->wijk, '', $lid->id);						
					}
					$LP->wijk = $lid->wijk;
				}
				
			
				# Gewijzigde kerkelijke status
				if($LP->doop != $lid->doop_belijdenis) {					
					$changed_field['status'] = $lid->doop_belijdenis;					
					toLog("Kerkelijke status gewijzigd (". $LP->doop ." -> ". $lid->doop_belijdenis .") dus gewijzigd in LaPosta");
					$LP->doop = $lid->doop_belijdenis;
				}
			
			
				# Gewijzigde kerkelijke relatie
				if($LP->relatie != $lid->relatie) {					
					$changed_field['relatie'] = $data['relatie'];									
					toLog("Kerkelijke relatie gewijzigd (". $LP->relatie ." -> ". $lid->relatie .") dus gewijzigd in LaPosta", '', $lid->id);
					$LP->relatie = $lid->relatie;
				}			
				
				
				# Gewijzigde status of relatie verwerken
				if(isset($changed_field)) {				
					if(!$LPFullUpdate) {
						$custom_fields = $changed_field;
					}
					
					$updateMember = lp_updateMember($LPLedenListID, $lid->email, $custom_fields);				
					if($updateMember === true) {
						toLog("Gegevens aangepast in LaPosta ledenlijst", '', $lid->id);
					} else {					
						toLog("update ledenlijst: ". $updateMember['error'], 'error', $lid->id);						
					}				
				}			
			}
			
			# De wijzigingen aan de LP kant moeten ook verwerkt worden in mijn lokale laposta-database
			$LP->save();
		}
	} else {
		toLog('Ongeldig mailadres', 'error', $lid->id);
	}	
}

toLog('Synchronisatie naar LaPosta uitgevoerd');


#
# Verwijder adressen die 2 ronde's (uitgaande van elke 12 uur een check) niet meer gezien zijn
# Er is een limiet van 4 per keer om de LaPosta-API niet te overvragen
/*
$users = LaPostaData::getOldAdresses();
foreach($users as $userID) {
	$user = new LaPostaData($userID);

	if($user->isUnique()) {
		foreach($listIDs as $naam => $listID) {
			if(lp_onList($listID, $user->mail)) {
				$unsubscribeMember = lp_unsubscribeMember($listID, $user->mail);
				if($unsubscribeMember === true) {
					toLog('Uitgeschreven voor '. $naam, 'debug', $user->id);
				} else {
					toLog("Uitschrijven voor '". $naam ."': ". $unsubscribeMember['error'], 'error', $user->id);
				}
			}
		}
		
		toLog('Uitschrijving gesynced naar LaPosta', '', $user->id);

		$user->status = 'uitgeschreven';
		$user->save();
	} else {
		toLog($email.' lijkt vaker voor te komen, niet uitgeschreven bij LaPosta', '', $user->id);
	}
}
*/
?>
