<?php
include_once('../include/functions.php');
include_once('../include/MC_functions.php');
include_once('../include/config.php');

# https://github.com/drewm/mailchimp-api

$db = connect_db();

# Ga op zoek naar alle personen met een mailadres
# Mailadres is daarbij alles met een @-teken erin
$sql = "SELECT * FROM $TableUsers WHERE $UserMail like '%@%' AND $UserStatus = 'actief' GROUP BY $UserMail";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);
do {
	# 3 seconden per persoon moet voldoende zijn
	set_time_limit(3);
	
	# identifier is het id binnen scipio
	$scipioID = $row[$UserID];
		
	# Haal alle gegevens op
	$data = getMemberDetails($scipioID);
	$wijk = $data['wijk'];
	$relatie = $data['relatie'];
	$status = $data['belijdenis'];
	$geslacht = $data['geslacht'];
			
	# Van elke persoon vraag ik op of die al voorkomt in mijn lokale mailchimp-database.
	# 	dat is iets sneller dan aan mailchimp vragen of die al voorkomt én
	#		ik kan dan werken met het scipio id als identiefier ipv het mailadres (wat MC doet)		
	$sql_mc = "SELECT * FROM $TableMC WHERE $MCID = $scipioID";
	$result_mc = mysqli_query($db, $sql_mc);
	
	# Komt hij niet voor dan moet hij aan MC worden toegevoegd en aan de juiste wijk worden toegekend	
	if(mysqli_num_rows($result_mc) == 0) {
		# Toevoegen aan de database
		if(mc_subscribe($data['mail'], $data['voornaam'], $data['tussenvoegsel'], $data['achternaam'])) {
			toLog('info', '', $scipioID, 'Gesyned naar MailChimp');
			echo makeName($scipioID, 6) ." toegevoegd<br>\n";
		} else {
			toLog('error', '', $scipioID, 'Kon niet syncen naar MailChimp');
		}
		
		# + geslacht toevoegen
		if(mc_addtag($data['mail'], $tagGeslacht[$geslacht])) {
			toLog('debug', '', $scipioID, 'Geslacht gesynced naar MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon geslacht niet syncen naar MailChimp');
		}
		
		# + tag van de juiste wijk eraan
		if(mc_addtag($data['mail'], $tagWijk[$wijk])) {
			toLog('debug', '', $scipioID, 'Wijk gesynced in MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon geen wijk syncen in MailChimp');
		}
		
		# + tag dat deze vanuit Scipio komt
		if(mc_addtag($data['mail'], $tagScipio)) {
			toLog('debug', '', $scipioID, 'Scipio-tag gesynced naar MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon geen Scipio-tag syncen naar MailChimp');
		}
		
		# + Scipio-ID toevoegen
		if(mc_addScipioID($data['mail'], $scipioID)) {
			toLog('debug', '', $scipioID, 'ScipioID gesynced naar MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon geen ScipioID syncen naar MailChimp');
		}
		
		# + toevoegen aan GoogleGroups
		if(mc_addinterest($data['mail'], $ID_google)) {
			toLog('debug', '', $scipioID, 'Inschrijving GoogleGroups gesynced naar MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon inschrijving GoogleGroups niet syncen naar MailChimp');
		}
		
		# + toevoegen aan Trinitas
		if(mc_addinterest($data['mail'], $ID_trinitas)) {
			toLog('debug', '', $scipioID, 'Inschrijving Trinitas gesynced naar MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon inschrijving Trinitas niet syncen naar MailChimp');
		}
		
		# + toevoegen aan Wekelijkse Trinitas
		if(mc_addinterest($data['mail'], $ID_week_trinitas)) {
			toLog('debug', '', $scipioID, 'Inschrijving wekelijkse Trinitas gesynced naar MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon inschrijving wekelijkse Trinitas niet syncen naar MailChimp');
		}
		
		
		# + toevoegen aan wijkmails
		if(mc_addinterest($data['mail'], $ID_wijkmails)) {
			toLog('debug', '', $scipioID, 'Inschrijving wijkmails gesynced naar MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon inschrijving wijkmails niet syncen naar MailChimp');
		}
		
		# + hash toevoegen
		if(mc_addHash($data['mail'], $data['hash_long'])) {
			toLog('debug', '', $scipioID, 'Hash gesynced naar MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon hash niet syncen naar MailChimp');
		}

		# + kerkelijke relatie toevoegen
		if(mc_addtag($data['mail'], $tagRelatie[$relatie])) {
			toLog('debug', '', $scipioID, 'Kerkelijke relatie gesynced naar MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon kerkelijke relatie niet syncen naar MailChimp');
		}
		
		# + kerkelijke status (doop / belijdenis) toevoegen
		if(mc_addtag($data['mail'], $tagStatus[$status])) {
			toLog('debug', '', $scipioID, 'Kerkelijke status gesynced naar MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon kerkelijke status niet syncen naar MailChimp');
		}
		
		# De wijzigingen aan de MC kant moeten ook verwerkt worden in mijn lokale mailchimp-database
		$sql_mc_insert = "INSERT INTO $TableMC ($MCID, $MCgeslacht, $MCmail, $MCfname, $MCtname, $MClname, $MCwijk, $MCstatus, $MCrelatie, $MCdoop, $MClastChecked, $MClastSeen) VALUES ($scipioID, '". $data['geslacht'] ."', '". $data['mail'] ."', '". $data['voornaam'] ."', '". urlencode($data['tussenvoegsel']) ."', '". $data['achternaam'] ."', '$wijk', 'subscribed', '$relatie', '". $data['belijdenis'] ."', ". time() .", ". time() .")";
		if(mysqli_query($db, $sql_mc_insert)) {
			toLog('debug', '', $scipioID, 'Mailchimp-data na sync toegevoegd in lokale MC-tabel');
		} else {
			toLog('error', '', $scipioID, 'Kon na sync niets toevoegen in lokale MC-tabel');
		}
		
				
		
	# Komt hij wel voor dan check ik even of een aantal velden gewijzigd zijn :
	#		mailadres / naam / wijk / kerkelijke status / relatie
	} else {
		$row_mc = mysqli_fetch_array($result_mc);
		$email = $row_mc[$MCmail];
		
		$sql_update = array();
		$sql_update[] = "$MClastSeen = ". time();
		
		# Als mensen zichzelf hebben uitgeschreven, is hun adres
		# bij Mailchimp geblokeerd en mag ik er niks aan wijzigen
		if($row_mc[$MCstatus] != 'block') {
			
			# Stond in de tabel als niet ingeschreven
			if($row_mc[$MCstatus] == 'unsubscribed') {
				if(mc_resubscribe($email)) {
					toLog('info', '', $scipioID, 'Opnieuw ingeschreven in MailChimp [S]');
					$sql_update[] = "$MClastSeen = 'subscribed'";
				} else {
					toLog('error', '', $scipioID, 'Kon niet opnieuw inschrijven in MailChimp [S]');
				}
			}
													
			# Gewijzigd mailadres
			if($email != $data['mail']) {
				if(mc_changemail($email, $data['mail'])) {
					toLog('info', '', $scipioID, 'Mailadres gewijzigd dus gesynced naar MailChimp');
					$sql_update[] = "$MCmail = '". $data['mail'] ."'";
					
					# Het adres binnen MC is vanaf nu gewijzigd.
					# Vanaf nu dus met gewijzigde mailadres in het script werken
					$email = $data['mail'];
				} else {
					toLog('error', '', $scipioID, 'Mailadres gewijzigd naar niet gesynced naar MailChimp');
				}						
			}
		
			# Gewijzigde naam
			if($row_mc[$MCfname] != $data['voornaam'] OR urldecode($row_mc[$MCtname]) != $data['tussenvoegsel'] OR $row_mc[$MClname] != $data['achternaam']) {
				if(mc_changename($email, $data['voornaam'], $data['tussenvoegsel'], $data['achternaam'])) {
					toLog('info', '', $scipioID, 'Naam gewijzigd dus gesynced naar MailChimp');
					$sql_update[] = "$MCfname = '". $data['voornaam'] ."'";
					$sql_update[] = "$MCtname = '". urlencode($data['tussenvoegsel']) ."'";
					$sql_update[] = "$MClname = '". $data['achternaam'] ."'";
				} else {
					toLog('error', '', $scipioID, 'Naam gewijzigd maar niet gesynced naar MailChimp');
				}
			}
		
			# Gewijzigde wijk
			if($row_mc[$MCwijk] != $wijk) {
				$oudeWijk = $row_mc[$MCwijk];			
				if(mc_addtag($email, $tagWijk[$wijk]) AND mc_rmtag($email, $tagWijk[$oudeWijk])){
					toLog('info', '', $scipioID, "Wijk gewijzigd ($oudeWijk -> $wijk) dus gesynced naar MailChimp");
					$sql_update[] = "$MCwijk = '$wijk'";
				} else {
					toLog('error', '', $scipioID, "Wijk gewijzigd ($oudeWijk -> $wijk) maar niet gesynced naar MailChimp");
				}
			}
		
			# Gewijzigde kerkelijke status
			if($row_mc[$MCdoop] != $status) {
				$oudeStatus = $row_mc[$MCdoop];			
				if((mc_addtag($email, $tagStatus[$status]) AND mc_rmtag($email, $tagStatus[$oudeStatus])) OR (mc_addtag($email, $tagStatus[$status]) AND $row_mc[$MCdoop] == '')){
					toLog('info', '', $scipioID, "Kerkelijke status gewijzigd ($oudeStatus -> $status) dus gesynced naar MailChimp");
					$sql_update[] = "$MCdoop = '$status'";
				} else {
					toLog('error', '', $scipioID, "Kerkelijke status gewijzigd ($oudeStatus -> $status) maar niet gesynced naar MailChimp");
				}			
			}
		
			# Gewijzigde kerkelijke relatie
			if($row_mc[$MCrelatie] != $relatie) {
				$oudeRelatie = $row_mc[$MCrelatie];						
				if((mc_addtag($email, $tagRelatie[$relatie]) AND mc_rmtag($email, $tagRelatie[$oudeRelatie])) OR (mc_addtag($email, $tagRelatie[$relatie]) AND $row_mc[$MCrelatie] == '')){
					toLog('info', '', $scipioID, "Kerkelijke relatie gewijzigd (". $row_mc[$MCrelatie] ." -> $relatie) dus gesynced naar MailChimp");
					$sql_update[] = "$MCrelatie = '$relatie'";
				} else {
					toLog('error', '', $scipioID, "Kerkelijke relatie gewijzigd (". $row_mc[$MCrelatie] ." -> $relatie) maar niet gesynced naar MailChimp");
				}
			}
			
			
			
			
			
			# Gewijzigd geslacht
			if($row_mc[$MCgeslacht] != $geslacht) {
				$oudeGeslacht = $row_mc[$MCgeslacht];						
				if((mc_addtag($email, $tagGeslacht[$geslacht]) AND mc_rmtag($email, $tagGeslacht[$oudeGeslacht])) OR (mc_addtag($email, $tagGeslacht[$geslacht]) AND $row_mc[$MCgeslacht] == '')){
					toLog('info', '', $scipioID, "Geslacht gewijzigd (". $row_mc[$MCgeslacht] ." -> $geslacht) dus gesynced naar MailChimp");
					$sql_update[] = "$MCgeslacht = '$geslacht'";
				} else {
					toLog('error', '', $scipioID, "Geslacht gewijzigd (". $row_mc[$MCgeslacht] ." -> $geslacht) maar niet gesynced naar MailChimp");
				}
			}			
			
			
			
			
			
		}
		
		# De wijzigingen aan de MC kant moeten ook verwerkt worden in mijn lokale mailchimp-database
		$sql_mc_update = "UPDATE $TableMC SET ". implode(', ', $sql_update)." WHERE $MCID like $scipioID";
		mysqli_query($db, $sql_mc_update);
	}
} while($row = mysqli_fetch_array($result));

toLog('info', '', '', 'Synchronisatie naar MailChimp uitgevoerd');


# Verwijder adressen die al sinds eergisteren niet meer gezien zijn
$dagen = mktime (0, 0, 0, date("n"), (date("j")-2));
$sql_mc_unsub = "SELECT * FROM $TableMC WHERE $MCstatus like 'subscribed' AND $MClastSeen < ". $dagen;
$result_unsub = mysqli_query($db, $sql_mc_unsub);
if($row_unsub = mysqli_fetch_array($result_unsub)) {
	do {
		set_time_limit(3);
		mc_unsubscribe($row_unsub[$MCmail]);
		toLog('info', '', $row_unsub[$MCID], 'Uitschrijving gesynced naar MailChimp');
		mysqli_query($db, "UPDATE $TableMC SET $MCstatus = 'unsubscribed' WHERE $MCID = ". $row_unsub[$MCID]);
	} while($row_unsub = mysqli_fetch_array($result_unsub));
}

?>
