<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_HeaderFooter.php');
include_once('../../../general_include/class.phpmailer.php');

$test = false;
$debug = false;

$db = connect_db();

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres
if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP) OR $test) {
//if(true) {
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
		$velden[$UserGeboorte] = substr($element->gebdatum, 0, 4).'-'.substr($element->gebdatum, 4, 2).'-'.substr($element->gebdatum, 6, 2);
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
		$velden[$UserWijk] = substr($element->wijk, -1);
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
				$item[] = $velden[$UserStraat].' '.$velden[$UserHuisnummer].$velden[$UserHuisletter].($velden[$UserToevoeging] != '' ? '-'.$velden[$UserToevoeging] : '');
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
			
			# Variabele voor gewijzigde data verwijderen, als hij zo wel bestaat betekent dat dat er data gewijzigd is
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
			
			# Andere variabelen
			if($oldData['huisletter'] != $velden[$UserHuisletter])								toLog('info', '', $element->regnr, 'Wijziging Scipio huisletter: '. $oldData['huisletter'] .' -> '. $velden[$UserHuisletter]);
			if($oldData['toevoeging'] != $velden[$UserToevoeging])								toLog('info', '', $element->regnr, 'Wijziging Scipio toevoeging: '. $oldData['toevoeging'] .' -> '. $velden[$UserToevoeging]);
			if($oldData['burgelijk'] != $velden[$UserBurgelijk])									toLog('info', '', $element->regnr, 'Wijziging Scipio burgerlijk: '. $oldData['burgerlijk'] .' -> '. $velden[$UserBurgelijk]);
			if($oldData['relatie'] != $velden[$UserRelatie])											toLog('info', '', $element->regnr, 'Wijziging Scipio relatie: '. $oldData['relatie'] .' -> '. $velden[$UserRelatie]);
			if($oldData['belijdenis'] != $velden[$UserBelijdenis])								toLog('info', '', $element->regnr, 'Wijziging Scipio belijdenis: '. $oldData['belijdenis'] .' -> '. $velden[$UserBelijdenis]);
			if(addslashes($oldData['achternaam']) != $velden[$UserAchternaam])		toLog('info', '', $element->regnr, 'Wijziging Scipio achternaam: '. $oldData['achternaam'] .' -> '. $velden[$UserAchternaam]);
			if(addslashes($oldData['meisjesnaam']) != $velden[$UserMeisjesnaam])	toLog('info', '', $element->regnr, 'Wijziging Scipio meisjesnaam: '. $oldData['meisjesnaam'] .' -> '. $velden[$UserMeisjesnaam]);

			# Array klaarmaken
			$update = array();			
			foreach($velden as $veld => $waarde) {
				$update[] = "$veld = '$waarde'";
			}
			
			# Kijken of er iets gewijzigd is
			if(isset($changedData)) {
				# Als er iets gewijzigd is, het tijdstip toevoegen
				$update[] = "$UserLastChange = ". time();
				
				# Bericht initialiseren
				$temp = array();
				$temp[] = "<b><a href='". $ScriptURL ."profiel.php?hash=[[hash]]&id=". $element->regnr ."'>". makeName($element->regnr, 6) ."</a></b>";
				//$temp[] = implode('|', array_keys($changedData));
				
				//if(isset($changedData['status']) AND $velden[$UserStatus] == 'vertrokken')									$temp[] = "Vertrokken";
				//if(isset($changedData['status']) AND $velden[$UserStatus] == 'overleden')										$temp[] = "Overleden";
				if(isset($changedData['status']))																														$temp[] = ucfirst($velden[$UserStatus]);
								
				# Ander telefoonnummer
				if(isset($changedData['tel']) AND $velden[$UserTelefoon] != '' AND $oldData['tel'] !== '')	$temp[] = "Telefoonnummer gewijzigd van ".$oldData['tel'] .' naar '. $velden[$UserTelefoon];
				if(isset($changedData['tel']) AND $velden[$UserTelefoon] == '')															$temp[] = "Telefoonnummer ". $oldData['tel'] ." verwijderd";
				if(isset($changedData['tel']) AND $oldData['tel'] == '')																		$temp[] = "Telefoonnummer ". $velden[$UserTelefoon] ." toegevoegd";
				
				# Mailadres
				if(isset($changedData['mail']) AND $velden[$UserMail] != '' AND $oldData['mail'] != '')			$temp[] = "Mailadres gewijzigd van ".$oldData['mail'] .' naar '. $velden[$UserMail];
				if(isset($changedData['mail']) AND $velden[$UserMail] == '')																$temp[] = "Mailadres ".$oldData['mail'] ." verwijderd";
				if(isset($changedData['mail']) AND $oldData['mail'] == '')																	$temp[] = "Mailadres ". $velden[$UserMail] ." toegevoegd";
				
				# Verhuizingen
				if(isset($changedData['straat']) OR isset($changedData['huisnummer']))											$temp[] = "Verhuisd van ". $oldData['straat'].' '.$oldData['huisnummer'].$oldData['huisletter'].($oldData['toevoeging'] != '' ? '-'.$oldData['toevoeging'] : '').' naar '. $velden[$UserStraat].' '.$velden[$UserHuisnummer].$velden[$UserHuisletter].($velden[$UserToevoeging] != '' ? '-'.$velden[$UserToevoeging] : '');
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
					$variabele['formeel'] = true;
					$data = getMemberDetails($lid);					
					$andereOntvangers = excludeID($namenWijkteam, $lid);
					
					$HTMLBericht = implode("\n", $mailBericht).(count($andereOntvangers) > 0 ? "<br>Deze mail is ook gestuurd naar : ". makeOpsomming($andereOntvangers) : '');
					
					$replacedBericht = $HTMLBericht;
					$replacedBericht = str_replace('[[hash]]', $data['hash_long'], $replacedBericht);
					$replacedBericht = str_replace('[[voornaam]]', $data['voornaam'], $replacedBericht);
					
					if($KB_in_CC) {
						$variabele['BCC'] = true;
						$variabele['BCC_mail'] = '';
						$KB_in_CC = false;
					}
										
					if(sendMail($lid, implode(' en ', $subject), $replacedBericht, $variabele)) {					
						toLog('info', '', $lid, "Wijzigingsmail wijkteam wijk $wijk verstuurd");
						echo "Mail verstuurd naar ". makeName($lid, 1) ." (wijkteam wijk $wijk)<br>\n";
					} else {
						toLog('error', '', $lid, "Problemen met wijzigingsmail ". makeName($lid, 1) ." (wijkteam wijk $wijk)");
						echo "Problemen met mail versturen<br>\n";
					}
					
					unset($param);
					$param['to'][]		= $lid;
					$param['message']	= $replacedBericht;
					$param['subject']	= implode(' en ', $subject);
					$param['formeel'] = true;
					sendMail_new($param);	
					
					//echo 'Onderwerp :'. implode(' en ', $subject) .'<br>';
					//echo 'Bericht :'. $replacedBericht .'<br>';
					
					# Om te zorgen dat kerkelijk bureau niet tig mailtjes krijgt direct BCC-tag verwijderen
					unset($variabele);
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
