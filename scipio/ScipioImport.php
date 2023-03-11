<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_HeaderFooter.php');

$test = false;
$debug = false;

$db = connect_db();

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres
if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP) OR $test) {
	$client = new SoapClient("ScipioConnect.wsdl");
	
	if(!$test) {
		$object = $client->__soapCall("GetLedenOverzicht", array($scipioParams));
		$temp =  (array) $object;
		$xmlfile = $temp['GetLedenOverzichtResult'];
		if($debug) {
			$file = fopen('dump.txt', 'w+');
			fwrite($file, $xmlfile);
			fclose($file);
		}
	} else {
		$xmlfile = file_get_contents('dump.txt');
	}
	
	$xml = new SimpleXMLElement($xmlfile);
	
	$mailBlockNew = $mailBlockChange = array();
	
	foreach ($xml->persoon as $element) {
		set_time_limit(10);
		
		$namen = explode(' - ', trim($element->aanschrijfnaam));
		
		if(count($namen) == 2) {
			$velden[$UserMeisjesnaam] = trim($namen[1]);
		} else {
			$velden[$UserMeisjesnaam] = "";
		}
		
		$delen = explode(' ', $namen[0]);
		
		$velden[$UserVoorletters] = array_shift($delen);
		$velden[$UserAchternaam] = array_pop($delen);
		$velden[$UserTussenvoegsel] = implode(' ', $delen);		
		$velden[$UserAdres] = trim($element->pefamilie);
		$velden[$UserID] = trim($element->regnr);
		//$velden[] = $element->aanschrijfnaam;
		$velden[$UserVoornaam] = trim($element->roepnaam);
		$velden[$UserGeslacht] = trim($element->geslacht);
		#$velden[$UserGeboorte] = substr($element->gebdatum, 0, 4).'-'.substr($element->gebdatum, 4, 2).'-'.substr($element->gebdatum, 6, 2);
		$velden[$UserGeboorte] = substr($element->gebdatum, 0, 4).'-'.substr($element->gebdatum, 4, 2).'-01';
		$velden[$UserStatus] = trim($element->status);
		$velden[$UserBurgelijk] = trim($element->burgstaat);
		$velden[$UserBelijdenis] = trim($element->kerkstaat);
		$velden[$UserRelatie] = trim($element->gezinsrelatie);
		$velden[$UserMail] = trim($element->email);
		$velden[$UserStraat] = trim($element->straat);
		$velden[$UserHuisnummer] = trim($element->huisnr);
		$velden[$UserHuisletter] = trim($element->huisltr);
		$velden[$UserToevoeging] = trim($element->huisnrtoev);
		$velden[$UserPC] = trim($element->postcode);
		$velden[$UserPlaats] = trim($element->plaats);
		$velden[$UserVestiging] = trim($element->vestigingsdatum);
		$velden[$UsersLastSeen] = time();
		
		# $element->wijk kan Wijk X zijn of ICF
		# Op deze manier vis ik die laatste eruit, weet nog niet wat ik met die laatste aanmoet
		if(is_numeric(strpos($element->wijk, 'Wijk'))) {
		    $velden[$UserWijk] = substr($element->wijk, 5);
		} else {
		    $velden[$UserWijk] = $element->wijk;
		}
		
		//$velden[] = 'sectie';
		//$velden[] = 'mutatiedatum';
		$velden[$UserTelefoon] = trim($element->telnr);
		
		# Als er geen voorletters bekendd zijn, deze aanmaken
		if($velden[$UserVoorletters] == $velden[$UserVoornaam]) {
			$delen = explode(' ', $velden[$UserVoornaam]);
			$velden[$UserVoorletters] = '';
			
			foreach($delen as $naam) {
				$velden[$UserVoorletters] .= $naam[0].'.';
			}
		}
		
		# Vestigingsdatum is een string, omzetten naar UNIX-time
		if($velden[$UserVestiging] != '') {			
			$jaar = substr($velden[$UserVestiging], 0, 4);
			$maand = substr($velden[$UserVestiging], 4, 2);
			$dag = substr($velden[$UserVestiging], 6, 2);
			
			$velden[$UserVestiging] = mktime(12, 0, 0, $maand, $dag, $jaar);
		}
		
		# Even alle velden doorlopen om slashes toe te voegen
		foreach($velden as $key => $value) {
			$velden[$key] = addslashes($value);
		}
				
		# Komt het lid al voor ?
		$sql_check = "SELECT $UserID FROM $TableUsers WHERE $UserID like '". $element->regnr ."'";
		$result = mysqli_query($db, $sql_check);
		
		# Nee -> Toevoegen
		if(mysqli_num_rows($result) == 0) {		
			$sql_insert = "INSERT INTO $TableUsers (". implode(', ', array_keys($velden)) .") VALUES ('". implode("', '", array_values($velden)) ."')";
			if(!mysqli_query($db, $sql_insert)) {
				 echo '<b>'. $sql_insert ."</b><br>\n";
				 toLog('error', '', $element->regnr, 'Toevoegen mislukt');
			} else {
				echo makeName($element->regnr, 5). " toegevoegd<br>\n";
				toLog('info', '', $element->regnr, 'Toegevoegd');
				
				$item = array();
				$item[] = "<b><a href='". $ScriptURL ."profiel.php?hash=[[hash]]&id=". $element->regnr ."'>". makeName($element->regnr, 6) ."</a></b> ('". substr($element->gebdatum, 2, 2) .")";
				$item[] = $velden[$UserStraat].' '.$velden[$UserHuisnummer].$velden[$UserHuisletter].($velden[$UserToevoeging] != '' ? '-'.$velden[$UserToevoeging] : '').(strtolower($velden[$UserPlaats]) != 'deventer' ? ', '.ucwords(strtolower($velden[$UserPlaats])) : '');
				if($velden[$UserTelefoon] != '')	$item[] = $velden[$UserTelefoon];
				if($velden[$UserMail] != '')			$item[] = $velden[$UserMail];
				$item[] = "";				
				$wijk = $velden[$UserWijk];
				$mailBlockNew[$wijk][] = implode("<br>\n", $item);
				$namenLedenNew[$wijk][] = makeName($element->regnr, 6);
			}
			
		# Ja -> updaten
		} else {
			$oldData = getMemberDetails($element->regnr);
			
			# Is het een actief of inactief lid?
			# Mocht er wat wijzigen in inactieve leden, dan niet mailen
			$actiefLid = true;
			if($oldData['status'] != 'actief')	$actiefLid = false;
			
			# Variabele voor gewijzigde data verwijderen, als hij verderop wel bestaat betekent dat dat er data gewijzigd is
			unset($changedData);
			
			# Als de status gewijzigd is
			if($oldData['status'] != $velden[$UserStatus]) {
				$changedData['status'] = true;
				toLog('info', '', $element->regnr, 'Wijziging Scipio status: '. $oldData['status'] .' -> '. $velden[$UserStatus]);
			}
			
			# Als het kerkelijk adres gewijzigd is
			if($oldData['adres'] != $velden[$UserAdres]) {
				$changedData['adres'] = true;
				toLog('info', '', $element->regnr, 'Wijziging Scipio adres: '. $oldData['adres'] .' -> '. $velden[$UserAdres]);
			}
			
			# Als het kerkelijk adres gewijzigd is
			if(addslashes($oldData['plaats']) != $velden[$UserPlaats]) {
				$changedData['plaats'] = true;
				toLog('info', '', $element->regnr, 'Wijziging Scipio plaats: '. $oldData['plaats'] .' -> '. $velden[$UserPlaats]);
			}
						
			# Als de straatnaam gewijzigd is
			if(addslashes($oldData['straat']) != $velden[$UserStraat]) {
				$changedData['straat'] = true;
				toLog('info', '', $element->regnr, 'Wijziging Scipio straat: '. $oldData['straat'] .' -> '. $velden[$UserStraat]);
			}
			
			# Als het huisnummer gewijzigd is
			if($oldData['huisnummer'] != $velden[$UserHuisnummer]) {
				$changedData['huisnummer'] = true;
				toLog('info', '', $element->regnr, 'Wijziging Scipio huisnummer: '. $oldData['huisnummer'] .' -> '. $velden[$UserHuisnummer]);
			}	
			
			# Als het telefoonnummer gewijzigd is
			if($oldData['tel'] != $velden[$UserTelefoon]) {
				$changedData['tel'] = true;
				toLog('info', '', $element->regnr, 'Wijziging Scipio telefoon: '. $oldData['tel'] .' -> '. $velden[$UserTelefoon]);
			}
			
			# Als het mailadres gewijzigd is
			if($oldData['mail'] != $velden[$UserMail]) {
				$changedData['mail'] = true;
				toLog('info', '', $element->regnr, 'Wijziging Scipio mail: '. $oldData['mail'] .' -> '. $velden[$UserMail]);
			}
			
			# Als de wijk gewijzigd is
			if($oldData['wijk'] != $velden[$UserWijk]) {
				$changedData['wijk'] = true;
				toLog('info', '', $element->regnr, 'Wijziging Scipio wijk: '. $oldData['wijk'] .' -> '. $velden[$UserWijk]);
			}
			
			if($oldData['relatie'] != $velden[$UserRelatie]) {
				$changedData['relatie'] = true;
				toLog('info', '', $element->regnr, 'Wijziging Scipio relatie: '. $oldData['relatie'] .' -> '. $velden[$UserRelatie]);
			}
			
			# Andere variabelen
			if($oldData['huisletter'] != $velden[$UserHuisletter])								toLog('info', '', $element->regnr, 'Wijziging Scipio huisletter: '. $oldData['huisletter'] .' -> '. $velden[$UserHuisletter]);
			if($oldData['toevoeging'] != $velden[$UserToevoeging])								toLog('info', '', $element->regnr, 'Wijziging Scipio toevoeging: '. $oldData['toevoeging'] .' -> '. $velden[$UserToevoeging]);
			if($oldData['burgelijk'] != $velden[$UserBurgelijk])									toLog('info', '', $element->regnr, 'Wijziging Scipio burgerlijk: '. $oldData['burgelijk'] .' -> '. $velden[$UserBurgelijk]);			
			if($oldData['belijdenis'] != $velden[$UserBelijdenis])								toLog('info', '', $element->regnr, 'Wijziging Scipio belijdenis: '. $oldData['belijdenis'] .' -> '. $velden[$UserBelijdenis]);
			if(addslashes($oldData['achternaam']) != $velden[$UserAchternaam])		toLog('info', '', $element->regnr, 'Wijziging Scipio achternaam: '. $oldData['achternaam'] .' -> '. $velden[$UserAchternaam]);
			if(addslashes($oldData['meisjesnaam']) != $velden[$UserMeisjesnaam])	toLog('info', '', $element->regnr, 'Wijziging Scipio meisjesnaam: '. $oldData['meisjesnaam'] .' -> '. $velden[$UserMeisjesnaam]);

			# Array klaarmaken
			$update = array();			
			foreach($velden as $veld => $waarde) {
				$update[] = "$veld = '$waarde'";
			}
			
			# Kijken of er iets gewijzigd is
			if(isset($changedData) AND $actiefLid) {
				# Als er iets gewijzigd is, het tijdstip toevoegen
				$update[] = "$UserLastChange = ". time();
				
				# Bericht initialiseren
				$temp = array();
				$temp[] = "<b><a href='". $ScriptURL ."profiel.php?hash=[[hash]]&id=". $element->regnr ."'>". makeName($element->regnr, 6) ."</a></b>";
				//$temp[] = implode('|', array_keys($changedData));
				
				//if(isset($changedData['status']) AND $velden[$UserStatus] == 'vertrokken')									$temp[] = "Vertrokken";
				//if(isset($changedData['status']) AND $velden[$UserStatus] == 'overleden')										$temp[] = "Overleden";
				if(isset($changedData['status']))																														$temp[] = ucfirst($velden[$UserStatus]);
								
				# relatie
				if(isset($changedData['relatie']))																													$temp[] = "Kerkelijke status gewijzigd van ". $oldData['relatie'] .' naar '. $velden[$UserRelatie];
				
				# Ander telefoonnummer
				if(isset($changedData['tel']) AND $velden[$UserTelefoon] != '' AND $oldData['tel'] !== '')	$temp[] = "Telefoonnummer gewijzigd van ".$oldData['tel'] .' naar '. $velden[$UserTelefoon];
				if(isset($changedData['tel']) AND $velden[$UserTelefoon] == '')															$temp[] = "Telefoonnummer ". $oldData['tel'] ." verwijderd";
				if(isset($changedData['tel']) AND $oldData['tel'] == '')																		$temp[] = "Telefoonnummer ". $velden[$UserTelefoon] ." toegevoegd";
				
				# Mailadres
				if(isset($changedData['mail']) AND $velden[$UserMail] != '' AND $oldData['mail'] != '')			$temp[] = "Mailadres gewijzigd van ".$oldData['mail'] .' naar '. $velden[$UserMail];
				if(isset($changedData['mail']) AND $velden[$UserMail] == '')																$temp[] = "Mailadres ".$oldData['mail'] ." verwijderd";
				if(isset($changedData['mail']) AND $oldData['mail'] == '')																	$temp[] = "Mailadres ". $velden[$UserMail] ." toegevoegd";
												
				# Verhuizingen
				if((isset($changedData['straat']) AND $velden[$UserStraat] != '') OR (isset($changedData['huisnummer']) AND $velden[$UserHuisnummer] != '') OR (isset($changedData['plaats']) AND $velden[$UserPlaats] != ''))	$temp[] = "Verhuisd van ". $oldData['straat'].' '.$oldData['huisnummer'].$oldData['huisletter'].($oldData['toevoeging'] != '' ? '-'.$oldData['toevoeging'] : '').(strtolower($oldData['plaats']) != 'deventer' ? ', '.ucwords(strtolower($oldData['plaats'])) : '').' naar '. $velden[$UserStraat].' '.$velden[$UserHuisnummer].$velden[$UserHuisletter].($velden[$UserToevoeging] != '' ? '-'.$velden[$UserToevoeging] : '').(strtolower($velden[$UserPlaats]) != 'deventer' ? ', '.ucwords(strtolower($velden[$UserPlaats])) : '');
				if(isset($changedData['wijk']) AND !isset($changedData['status'])) {
					$oudeWijk = $oldData['wijk'];
					$nieuweWijk = $velden[$UserWijk];
					
					$item = $temp;
					$item[] = "Overgegaan naar wijk ". $nieuweWijk;					
					$mailBlockChange[$oudeWijk][] = implode("<br>\n", $item)."<br>\n";
					$namenLedenChange[$oudeWijk][] = makeName($element->regnr, 5);
					
					$item = $temp;
					$item[] = "Binnengekomen vanuit wijk ". $oudeWijk;					
					$mailBlockChange[$nieuweWijk][] = implode("<br>\n", $item)."<br>\n";
					$namenLedenChange[$nieuweWijk][] = makeName($element->regnr, 5);
				} else {
					$wijk = $oldData['wijk'];
					$mailBlockChange[$wijk][] = implode("<br>\n", $temp)."<br>\n";
					$namenLedenChange[$wijk][] = makeName($element->regnr, 5);
				}				
			}
			
			# Nieuwe gegevens inladen (of er nu iets gewijzigd is of niet)				
			$sql_update = "UPDATE $TableUsers SET ". implode(', ', $update) ." WHERE $UserID like '". $element->regnr ."'";
			
			if(!mysqli_query($db, $sql_update)) {
				 echo '<b>'. $sql_update ."</b><br>\n";
				 toLog('error', '', $element->regnr, 'Updaten mislukt');
				 echo $sql_update .'<br>';				
			}
		}
	}
	
	# Adressen die niet meer doorkomen verwijderen
	# Bijvoorbeeld omdat mensen vanuit AVG niet meer gevonden willen worden
	$sql_delete = "DELETE FROM $TableUsers WHERE $UsersLastSeen < ". mktime(date('H')-25);
	$result_delete = mysqli_query($db, $sql_delete);	
	
	if(mysqli_num_rows($result_delete) > 0) {
		toLog('info', '', 'Oudleden verwijderd');		
	} elseif(!$result_delete) {
		toLog('error', '', 'Verwijderen van oudleden : '. $sql_delete);
	}
		
	if(count($mailBlockNew) > 0 OR count($mailBlockChange) > 0) {
		foreach($wijkArray as $wijk) {
			$mailBericht = $subject = $namenWijkteam = $wijkTeam = $andereOntvangers = array();
			$KB_in_CC = true;
						
			# Alleen als er een nieuw of gewijzigd iets is						
			if(isset($mailBlockNew[$wijk]) OR isset($mailBlockChange[$wijk])) {				
				$wijkTeam = getWijkteamLeden($wijk);
				
				foreach($wijkTeam as $lid => $dummy)	$namenWijkteam[$lid] = makeName($lid, 1);
				
				$mailBericht[] = "Beste [[voornaam]],<br>";
				$mailBericht[] = "<br>";
				$mailBericht[] = "In de ledenadministratie zijn zaken veranderd voor wijk $wijk<br>";
				
				if(isset($mailBlockNew[$wijk])) {
					$mailBericht[] = "<h3>Nieuwe wijk". (count($mailBlockNew[$wijk]) > 1 ? 'genoten' : 'genoot') ."</h3>";
					$mailBericht[] = implode("<br>\n", $mailBlockNew[$wijk]);
					
					if(count($namenLedenNew[$wijk]) < 4) {
						$subject[] = makeOpsomming($namenLedenNew[$wijk]) .' '. (count($namenLedenNew[$wijk]) > 1 ? 'zijn' : 'is een') .' nieuwe wijk'. (count($namenLedenNew[$wijk]) > 1 ? 'genoten' : 'genoot');
					} else {
						$subject[] = count($namenLedenNew[$wijk]) .' nieuwe wijkgenoten';
					}
				}
			
				if(isset($mailBlockChange[$wijk])) {
					$mailBericht[] = "<h3>Gewijzigde gegevens</h3>";
					$mailBericht[] = implode("<br>\n", $mailBlockChange[$wijk]);
					
					if(count($namenLedenChange[$wijk]) < 4) {
						$subject[] = 'de gegevens van '. makeOpsomming($namenLedenChange[$wijk]) .' zijn gewijzigd';
					} else {
						$subject[] = 'de gegevens van '. count($namenLedenChange[$wijk]) .' wijkgenoten zijn gewijzigd';
					}
				}
				
				foreach($wijkTeam as $lid => $rol) {					
					$data = getMemberDetails($lid);					
					$andereOntvangers = excludeID($namenWijkteam, $lid);
					
					$HTMLBericht = implode("\n", $mailBericht).(count($andereOntvangers) > 0 ? "<br>Deze mail is ook naar ". makeOpsomming($andereOntvangers) ." gestuurd." : '');
					
					$replacedBericht = $HTMLBericht;
					$replacedBericht = str_replace('[[hash]]', $data['hash_long'], $replacedBericht);
					$replacedBericht = str_replace('[[voornaam]]', $data['voornaam'], $replacedBericht);
					
					unset($param);
					$param['to'][]				= array($lid);
					$param['message']			= $replacedBericht;
					$param['subject']			= implode(' en ', $subject);
					$param['formeel'] 		= true;
					$param['ReplyTo']			= 'kerkelijkbureau@koningskerkdeventer.nl';
					$param['ReplyToName']	= 'Kerkelijk Bureau';
															
					if(sendMail_new($param)) {
						toLog('info', '', $lid, "Wijzigingsmail wijkteam wijk $wijk verstuurd");
						echo "Mail verstuurd naar ". makeName($lid, 1) ." (wijkteam wijk $wijk)<br>\n";
					} else {
						toLog('error', '', $lid, "Problemen met wijzigingsmail ". makeName($lid, 1) ." (wijkteam wijk $wijk)");
						echo "Problemen met mail versturen<br>\n";
					}
															
					//echo 'Onderwerp :'. implode(' en ', $subject) .'<br>';
					//echo 'Bericht :'. $replacedBericht .'<br>';
					
					# Alle parameters voor mailen resetten
					unset($param);
				}
			}
		}
	}

	toLog('info', '', '', 'Scipio data ingeladen');
} else {
	toLog('error', '', '', 'Poging handmatige run Scipio-import, IP:'.$_SERVER['REMOTE_ADDR']);
}
?>
