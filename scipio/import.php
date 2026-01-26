<?php
/**
 * Script om ledengegevens uit Scipio te importeren.
 * 
 * Op dit moment staat de cronjob zo ingesteld dat het script 2 keer per dag (4 uur en 16 uur) wordt uitgevoerd.
 * Dit script zorgt dus dat de ledengegevens in het intranet up-to-date blijven met Scipio.
 * Eventuele wijzigingen in de data worden automatisch doorgevoerd in het intranet.
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Wijk.php');
include_once('../Classes/KKDMailer.php');
include_once('../Classes/Mysql.php');

$test = false;
$debug = false;

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres
if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP) || $test) {
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
		$newData = new Member();
		
		$namen = explode(' - ', trim_unicode($element->aanschrijfnaam));
		
		if(count($namen) == 2) {
			$newData->meisjesnaam = trim_unicode($namen[1]);
		} else {
			$newData->meisjesnaam = "";
		}
		
		$delen = explode(' ', $namen[0]);
		
		if(count($delen) == 1) {
			$newData->voorletters	= '';
			$newData->achternaam	= implode(' ', $delen);
			$newData->tussenvoegsel	= '';
		} elseif(count($delen) == 2) {
			$newData->voorletters	= array_shift($delen);
			$newData->achternaam	= array_pop($delen);
			$newData->tussenvoegsel	= '';
		} else {
			$newData->voorletters	= array_shift($delen);
			$newData->achternaam	= array_pop($delen);
			$newData->tussenvoegsel	= implode(' ', $delen);
		}
				
		$newData->id 				= trim_unicode(intval($element->regnr));
		$newData->adres				= trim_unicode($element->pefamilie);
		$newData->voornaam			= trim_unicode($element->roepnaam);
		$newData->geslacht			= trim_unicode($element->geslacht);
		$newData->geboortedatum		= substr($element->gebdatum, 0, 4).'-'.substr($element->gebdatum, 4, 2).'-'.substr($element->gebdatum, 6, 2);
		$newData->status			= trim_unicode($element->status);
		$newData->burgelijk			= trim_unicode($element->burgstaat);
		$newData->doop_belijdenis	= trim_unicode($element->kerkstaat);
		$newData->relatie			= trim_unicode($element->gezinsrelatie);
		$newData->email				= trim_unicode($element->email);
		$newData->telefoon			= trim_unicode($element->telnr);
		$newData->straat			= trim_unicode($element->straat);
		$newData->huisnummer		= trim_unicode(intval($element->huisnr));
		$newData->huisnummer_letter	= trim_unicode($element->huisltr);
		$newData->huisnummer_toevoeging = trim_unicode($element->huisnrtoev);
		$newData->postcode			= trim_unicode($element->postcode);
		$newData->woonplaats		= trim_unicode(ucwords(strtolower($element->plaats)));
		$newData->tijd_wijziging	= trim_unicode($element->mutatiedatum);
		$newData->tijd_scipipo		= time();		
		

		# $element->wijk kan Wijk X zijn of ICF
		# Op deze manier vis ik die laatste eruit, weet nog niet wat ik met die laatste aanmoet
		if(is_numeric(strpos($element->wijk, 'Wijk'))) {
		    $newData->wijk = substr($element->wijk, 5);
		} else {
		    $newData->wijk = $element->wijk;
		}			
		
		# Als er geen voorletters bekendd zijn, deze aanmaken
		if($newData->voorletters == $newData->voornaam) {
			$delen = explode(' ', $newData->voornaam);
			$newData->voorletters = '';
			
			foreach($delen as $naam) {
				if(isset($naam[0]) && $naam[0] != '') {
					$newData->voorletters .= $naam[0].'.';
				}				
			}
		}
		
		# Vestigingsdatum is een string, omzetten naar UNIX-time
		if($element->vestigingsdatum != '') {			
			$jaar = substr($element->vestigingsdatum, 0, 4);
			$maand = substr($element->vestigingsdatum, 4, 2);
			$dag = substr($element->vestigingsdatum, 6, 2);
			
			$newData->tijd_vestiging = mktime(12, 0, 0, $maand, $dag, $jaar);
		}

		#$newData->tijd_wijziging = time();
		
		# Als lid nog niet bestaat -> toevoegen
		if(!$newData->memberExist()) {					
			if(!$newData->save()) {
				 echo "<b>Toevoegen mislukt</b><br>\n";
				 toLog('Toevoegen mislukt', 'error', $newData->id);
			} else {				
				echo $newData->getName(). " toegevoegd<br>\n";
				toLog('Toegevoegd', '', $newData->id);
				
				$item = array();
				$item[] = "<b><a href='". $ScriptURL ."profiel.php?hash=[[hash]]&id=". $newData->id ."'>". $newData->getName(6) ."</a></b> ('". substr($element->gebdatum, 2, 2) .")";
				$item[] = $newData->getWoonadres().(strtolower($newData->woonplaats) != 'deventer' ? ', '.ucwords(strtolower($newData->woonplaats)) : '');
				if($newData->telefoon != '')	$item[] = $newData->telefoon;
				if($newData->email != '')		$item[] = $newData->email;
				$item[] = "";

				$wijk = $newData->wijk;
				$mailBlockNew[$wijk][] = implode("<br>\n", $item);
				$namenLedenNew[$wijk][] = $newData->getName(6);
			}
			
		# bestaat wel -> updaten
		} else {			
			$oldData = new Member(intval($element->regnr));
			
			# Is het een actief of inactief lid?
			# Mocht er wat wijzigen in inactieve leden, dan niet mailen
			$actiefLid = true;
			if($oldData->status != 'actief')	$actiefLid = false;
			
			# Variabele voor gewijzigde data verwijderen, als hij verderop wel bestaat betekent dat dat er data gewijzigd is
			unset($changedData);
			
			# Als de status gewijzigd is
			if($oldData->status != $newData->status) {				
				$changedData['status'] = true;
				toLog('Wijziging Scipio status: '. $oldData->status .' -> '. $newData->status, '', $newData->id);				
			}
			
			# Als het kerkelijk adres gewijzigd is
			if($oldData->adres != $newData->adres) {				
				$changedData['adres'] = true;
				toLog('Wijziging Scipio adres: '. $oldData->adres .' -> '. $newData->adres, '', $newData->id);				
			}
			
			# Als de woonplaats gewijzigd is
			if($oldData->woonplaats != $newData->woonplaats) {				
				$changedData['plaats'] = true;
				toLog('Wijziging Scipio plaats: '. $oldData->woonplaats .' -> '. $newData->woonplaats, '', $newData->id);
			}
						
			# Als de straatnaam gewijzigd is
			if($oldData->straat != $newData->straat) {				
				$changedData['straat'] = true;
				toLog('Wijziging Scipio straat: '. $oldData->straat .' -> '. $newData->straat, '', $newData->id);				
			}
			
			# Als het huisnummer gewijzigd is
			if($oldData->huisnummer != $newData->huisnummer) {				
				$changedData['huisnummer'] = true;
				toLog('Wijziging Scipio huisnummer: '. $oldData->huisnummer .' -> '. $newData->huisnummer, '', $newData->id);
			}	
			
			# Als het telefoonnummer gewijzigd is
			if($oldData->telefoon != $newData->telefoon) {				
				$changedData['tel'] = true;
				toLog('Wijziging Scipio telefoon: '. $oldData->telefoon .' -> '. $newData->telefoon, '', $newData->id);				
			}
			
			# Als het mailadres gewijzigd is
			if($oldData->email != $newData->email) {				
				$changedData['mail'] = true;
				toLog('Wijziging Scipio mail: '. $oldData->email .' -> '. $newData->email, '', $newData->id);				
			}
			
			# Als de wijk gewijzigd is
			if($oldData->wijk != $newData->wijk) {				
				$changedData['wijk'] = true;
				toLog('Wijziging Scipio wijk: '. $oldData->wijk .' -> '. $newData->wijk, '', $newData->id);
			}
			
			if($oldData->relatie != $newData->relatie) {
				$changedData['relatie'] = true;
				toLog('Wijziging Scipio relatie: '. $oldData->relatie .' -> '. $newData->relatie, '', $newData->id);				
			}
			
			# Andere variabelen
			if($oldData->huisnummer_letter != $newData->huisnummer_letter)			toLog('Wijziging Scipio huisletter: '. $oldData->huisnummer_letter .' -> '. $newData->huisnummer_letter, '', $newData->id);
			if($oldData->huisnummer_toevoeging != $newData->huisnummer_toevoeging)	toLog('Wijziging Scipio toevoeging: '. $oldData->huisnummer_toevoeging .' -> '. $newData->huisnummer_toevoeging, '', $newData->id);
			if($oldData->burgelijk != $newData->burgelijk)							toLog('Wijziging Scipio burgerlijk: '. $oldData->burgelijk .' -> '. $newData->burgelijk, '', $newData->id);
			if($oldData->doop_belijdenis != $newData->doop_belijdenis)				toLog('Wijziging Scipio belijdenis: '. $oldData->doop_belijdenis .' -> '. $newData->doop_belijdenis, '', $newData->id);
			if($oldData->achternaam != $newData->achternaam)						toLog('Wijziging Scipio achternaam: '. $oldData->achternaam .' -> '. $newData->achternaam, '', $newData->id);
			if($oldData->meisjesnaam != $newData->meisjesnaam)						toLog('Wijziging Scipio meisjesnaam: '. $oldData->meisjesnaam .' -> '. $newData->meisjesnaam, '', $newData->id);
			
								
			# Kijken of er iets gewijzigd is
			if(isset($changedData) && $actiefLid) {
				# Als er iets gewijzigd is, het tijdstip toevoegen
				$oldData->tijd_wijziging = time();
				
				# Bericht initialiseren
				$temp = array();
				$temp[] = "<b><a href='". $ScriptURL ."profiel.php?hash=[[hash]]&id=". $newData->id ."'>". $newData->getName(6) ."</a></b>";
				if(isset($changedData['status']))															$temp[] = ucfirst($newData->status);
								
				# relatie
				if(isset($changedData['relatie']))															$temp[] = "Kerkelijke status gewijzigd van ". $oldData->relatie .' naar '. $newData->relatie;
				
				# Ander telefoonnummer
				if(isset($changedData['tel']) && $newData->telefoon != '' && $oldData->telefoon !== '')	$temp[] = "Telefoonnummer gewijzigd van ". $oldData->telefoon .' naar '. $newData->telefoon;
				if(isset($changedData['tel']) && $newData->telefoon == '')									$temp[] = "Telefoonnummer ". $oldData->telefoon ." verwijderd";
				if(isset($changedData['tel']) && $oldData->telefoon == '')									$temp[] = "Telefoonnummer ". $newData->telefoon ." toegevoegd";
				
				# Mailadres
				if(isset($changedData['mail']) && $newData->email != '' && $oldData->email != '')			$temp[] = "Mailadres gewijzigd van ". $oldData->email .' naar '. $newData->email;
				if(isset($changedData['mail']) && $newData->email == '')									$temp[] = "Mailadres ". $oldData->email ." verwijderd";
				if(isset($changedData['mail']) && $oldData->email == '')									$temp[] = "Mailadres ". $newData->email ." toegevoegd";
												
				# Verhuizingen
				if(
					(isset($changedData['straat']) && $newData->straat != '') ||
					(isset($changedData['huisnummer']) && $newData->huisnummer != '') || 
					(isset($changedData['plaats']) && $newData->woonplaats != '')) {
						$temp[] = "Verhuisd van ". $oldData->getWoonadres().(strtolower($oldData->woonplaats) != 'deventer' ? ', '. $oldData->woonplaats : '').' naar '. $newData->getWoonadres().(strtolower($newData->woonplaats) != 'deventer' ? ', '. $newData->woonplaats : '');
				}

				if(isset($changedData['wijk']) && !isset($changedData['status'])) {					
					$item = $temp;
					$item[] = "Overgegaan naar wijk ". $newData->wijk;					
					$mailBlockChange[$oldData->wijk][] = implode("<br>\n", $item)."<br>\n";
					$namenLedenChange[$oldData->wijk][] = $newData->getName(5);
					
					$item = $temp;
					$item[] = "Binnengekomen vanuit wijk ". $oldData->wijk;					
					$mailBlockChange[$newData->wijk][] = implode("<br>\n", $item)."<br>\n";
					$namenLedenChange[$newData->wijk][] = $newData->getName(5);
				} else {					
					$mailBlockChange[$oldData->wijk][] = implode("<br>\n", $temp)."<br>\n";
					$namenLedenChange[$oldData->wijk][] = $newData->getName(5);
				}
			}

			$oldData->status = $newData->status;
			$oldData->adres = $newData->adres;
			$oldData->voornaam = $newData->voornaam;
			$oldData->achternaam = $newData->achternaam;
			$oldData->meisjesnaam = $newData->meisjesnaam;
			$oldData->telefoon = $newData->telefoon;
			$oldData->email = $newData->email;
			$oldData->geslacht = $newData->geslacht;
			$oldData->relatie = $newData->relatie;
			$oldData->burgelijk = $newData->burgelijk;
			$oldData->doop_belijdenis = $newData->doop_belijdenis;
			$oldData->straat = $newData->straat;
			$oldData->huisnummer = $newData->huisnummer;
			$oldData->huisnummer_letter = $newData->huisnummer_letter;
			$oldData->huisnummer_toevoeging = $newData->huisnummer_toevoeging;
			$oldData->postcode = $newData->postcode;
			$oldData->woonplaats = $newData->woonplaats;
			$oldData->wijk = $newData->wijk;
			$oldData->tijd_scipipo = time();
			
			# Nieuwe gegevens inladen (of er nu iets gewijzigd is of niet)									
			if(!$oldData->save()) {
				 echo "<b>Updaten mislukt</b><br>\n";
				 toLog('Updaten mislukt', 'error', $newData->id);
			}
		}
	}
	
	/*
	# Adressen die niet meer doorkomen verwijderen
	# Bijvoorbeeld omdat mensen vanuit AVG niet meer gevonden willen worden
	$sql_delete = "DELETE FROM $TableUsers WHERE $UsersLastSeen < ". mktime(date('H')-25);
	$result_delete = mysqli_query($db, $sql_delete);	
	
	if(mysqli_num_rows($result_delete) > 0) {
		toLog('info', '', 'Oudleden verwijderd');		
	} elseif(!$result_delete) {
		toLog('error', '', 'Verwijderen van oudleden : '. $sql_delete);
	}
	*/
		
	if(count($mailBlockNew) > 0 || count($mailBlockChange) > 0) {
		foreach($wijkArray as $wijk) {
			$mailBericht = $subject = $namenWijkteam = $wijkTeam = $andereOntvangers = array();
			$KB_in_CC = false;
						
			# Alleen als er een nieuw of gewijzigd iets is						
			if(isset($mailBlockNew[$wijk]) || isset($mailBlockChange[$wijk])) {
				$w			= new Wijk;
				$w->wijk	= $wijk;
				$wijkTeam	= $w->getWijkteam();
				
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
					$teamLid = new Member($lid);
					$namenWijkteam[$lid] = $teamLid->getName(1);
				}

				foreach($wijkTeam as $lid => $rol) {					
					$ontvanger = new Member($lid);
					$andereOntvangers = excludeID($namenWijkteam, $lid);
					
					$HTMLBericht = implode("\n", $mailBericht).(count($andereOntvangers) > 0 ? "<br>Deze mail is ook naar ". makeOpsomming($andereOntvangers) ." gestuurd." : '');
					
					$replacedBericht = $HTMLBericht;
					$replacedBericht = str_replace('[[hash]]', $ontvanger->hash_long, $replacedBericht);
					$replacedBericht = str_replace('[[voornaam]]', $ontvanger->voornaam, $replacedBericht);
					
					$KKDMail = new KKDMailer();
					$KKDMail->aan		= $ontvanger->id;
					$KKDMail->formeel	= true;
					$KKDMail->Body		= $replacedBericht;
					$KKDMail->Subject	= implode(' en ', $subject);
					$KKDMail->addReplyTo('kerkelijkbureau@koningskerkdeventer.nl', 'Kerkelijk Bureau');
					if(!$productieOmgeving)	$KKDMail->testen	= true;
																				
					if($KKDMail->sendMail()) {
						toLog("Wijzigingsmail wijkteam wijk $wijk verstuurd", '', $ontvanger->id);
						echo "Mail verstuurd naar ". $ontvanger->getName(1) ." (wijkteam wijk $wijk)<br>\n";
					} else {
						toLog("Problemen met wijzigingsmail ". $ontvanger->getName(1) ." (wijkteam wijk $wijk)", 'error', $ontvanger->id);
						echo "Problemen met mail versturen<br>\n";
					}
				}
			}
		}
	}

	toLog('Scipio data ingeladen');
} else {
	toLog('Poging handmatige run Scipio-import, IP:'.$_SERVER['REMOTE_ADDR'], 'error');
}
?>
