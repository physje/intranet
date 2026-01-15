<?php

/**
 * Maak een datum op.
 *
 * D = Man, Tue, Wed, Thu, Fri, Sat, Sun (%a)
 * l = Sunday to Saturday (%A)
 * j = 1 to 31 (%e)
 * d = 01 to 31 (%d)
 * m = 01 to 12 (%m)
 * M = Jan, Feb, Mar, Apr, May, Jun, Jul, Aug, Sep, Oct, Nov, Dec (%b)
 * F = January to December (%B)
 * Y = 4 digit year (%Y)
 * H = 00 to 23 (%H)
 * i = 00 to 59 (%M)
 *
 * @param string $format In welk format moet de datum worden opgemaakt.
 * @param int $time tijd in UNIX-format
 *
 * @return string opgemaakte datum
 */
function time2str(string $format, int $time = 0) {
	if($time == 0) {
		$time = time();
	}	

	# https://unicode-org.github.io/icu/userguide/format_parse/datetime/

	# d	dd
	# F	LLLL
	# Y yyyy
	# y yy
	# l	EEEE
	# j d
	# H HH
	# i mm
	# M LLL
	# D E
	# W ww
	# N e

	#return strftime($format, $time);	
    #return date($format, $time);

	$dt = new DateTime;
	$dt->setTimestamp($time);

	$formatter = new IntlDateFormatter('nl_NL', IntlDateFormatter::LONG, IntlDateFormatter::LONG);
	$formatter->setPattern($format);

	return $formatter->format($dt);
}



/**
 * Schrijf iets weg in de logfiles.
 * @param string $message Log-bericht
 * @param string $type Type logging (debug, info, error). Default is info
 * @param int $slachtoffer ID van het slachtoffer
 *
 * @return bool Logging succesvol of niet
 */
function toLog(string $message, string $type = '', int $slachtoffer = 0) {	
	global $cookie_lifetime;
 	
 	if($message != '') {
 		if (!session_id()) session_start(['cookie_lifetime' => $cookie_lifetime]);

		$log = new Logging();
		if($type != '')			$log->type = $type;
		if($slachtoffer != 0)	$log->slachtoffer = $slachtoffer;
		$log->bericht = $message;
 		return $log->save();	
	}
}



/**
 * Hoort een hash bij een persoon.
 * @param string $hash Hash van de persoon die inlogd
 * @return false als hash onbekend is, anders ID van persoon
 */
function isValidHash(string $hash) {
	$db = new Mysql();
	$data = $db->select("SELECT `scipio_id` FROM `leden` WHERE `hash_long` like '$hash'");
	
	if(count($data) == 0) {
		return false;
	} else {
		return $data['scipio_id'];
	}
}



/**
 * Hoort een hash bij een voorganger.
 * @param string $hash Hash van de voorganger die probeert in te loggen
 * @return false als hash onbekend is, anders ID van voorganger
 */
function isValidVoorgangerHash(string $hash) {
	$db = new Mysql();
	$data = $db->select("SELECT `id` FROM `predikanten` WHERE `hash` like '$hash'");
	
	if(count($data) == 0) {
		return false;
	} else {
		return $data['id'];
	}
}



/**
 * Zoek een gebruikersID op basis van username of mailadres.
 * Username of mailadres moeten volledig overeenkomen, geen gedeeltelijke overeenkomst.
 * @param string $input String waarop gezocht moet worden.
 * 
 * @return Array Array met userIDs. Als er geen gebruiker is gevonden False.
 */
function getUserByInput($input) {
	$db = new Mysql();
	$sql = "SELECT `scipio_id` FROM `leden` WHERE `username` like '". urlencode($input) ."' OR `email` like '". urlencode($input) ."' OR `formeel` like '". urlencode($input) ."'";
	$data = $db->select($sql, true);
	
	if(count($data) == 0) {
		return false;
	} else {
		return array_column($data, 'scipio_id');
	}
}



/**
 * @param float $price Prijs
 * @param bool $euro Met €-teken
 * 
 * @return string Opgemaakte prijs
 */
function formatPrice(float $price, $euro = true) {
	$input = $price/100;
	
	if($euro) {
		return "&euro;&nbsp;". number_format($input, 2,',','.');
	} else {
		return number_format($input, 2,',','.');
	}
}



/**
 * Bepaal op basis van een dienstID of het een ochtend-, middag- of avonddienst is.
 * @param int $start Starttijd in UNIX-tijd
 * @param bool $dienst Moet 'dienst' achter het dagdeel gezet worden
 *
 * @return string Naam van het dagdeel
 */
function formatDagdeel(int $start, $dienst = true) {
	if(date("H", $start) < 12) {
		$dagdeel = 'ochtend';
	} elseif(date("H", $start) < 18) {
		$dagdeel = 'middag';
	} else {
		$dagdeel = 'avond';
	}
	
	if($dienst)	$dagdeel .= 'dienst';
	
	return $dagdeel;
}



/**
 * Bepaal op basis van de dienstID en waarde van gelijke diensten op het rooster of een dienst wel of niet getoond moet worden op het rooster.
 * @param int $dienst ID van de kerkdienst
 * @param int $gelijk Waarde van het gelijk-veld van het rooster (1=geen, 2=tweede, 3=ochtend, 4=middag/avond, 5=middag, 6=avond)
 *
 * @return bool Moet de dienst getoond worden of niet
 */
function toonDienst(int $dienst, int $gelijk) {
	if($gelijk == 0) {
		return true;
	} else {
		$kerkdienst = new Kerkdienst($dienst);
		$diensten = Kerkdienst::getDiensten(mktime(0,0,0,date("n",$kerkdienst->start),date("j", $kerkdienst->start),date("Y", $kerkdienst->start)), mktime(23,59,59,date("n", $kerkdienst->start),date("j", $kerkdienst->start),date("Y", $kerkdienst->start)));
		$dagdeel = formatDagdeel($kerkdienst->start, false);
		
		if($gelijk == 1 AND $diensten[0] == $dienst) {
			return true;
		} elseif($gelijk == 2 AND ($dagdeel == 'ochtend' OR $dagdeel == 'avond')) {
			return true;
		} elseif($gelijk == 3 AND $dagdeel == 'ochtend') {
			return true;
		} elseif($gelijk == 4 AND ($dagdeel == 'middag' OR $dagdeel == 'avond')) {
			return true;
		} elseif($gelijk == 5 AND $dagdeel == 'middag') {
			return true;
		} elseif($gelijk == 6 AND $dagdeel == 'avond') {
			return true;
		} else {
			return false;
		}
	}
}



/**
 * Haal een parameter op.
 * Mocht de parameter niet bekend zijn, dan wordt de default waarde toegekend
 * @param string $name Naam van de parameter die via $_REQUEST is binnengekomen
 * @param mixed $default Standaardwaarde als de parameter niet is gezet
 * @return mixed Waarde van de parameter of de standaardwaarde
 */
function getParam(string $name, $default = '') {
	return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
}



/**
 * Geef 2 zoek-strings en krijg de tekst die tussen deze 2 strings staat terug 
 * @param string $start Zoek-string vóór de te vinden tekst
 * @param string $end Zoek-string ná de te vinden tekst
 * @param string $string String waarin gezocht moet worden
 * @param int $offset Vanaf waar gezocht moet worden
 * 
 * @return string Tussen start- en end-string in
 */
function getString(string $start, string $end, string $string, int $offset) {
	if ($start != '') {
		$startPos = strpos ($string, $start, $offset) + strlen($start);
	} else {
		$startPos = 0;
	}
	
	if ($end != '') {
		$eindPos	= strpos ($string, $end, $startPos);
	} else {
		$eindPos = strlen($string);
	}
		
	$text	= substr ($string, $startPos, $eindPos-$startPos);
	$rest	= substr ($string, $eindPos);
		
	return array($text, $rest);
}



/**
 * Verwijder een key uit een array.
 *
 * @param array $oldArray Array met alle id's
 * @param id $id id die uit het array gehaald moet worden
 * @return array Array zonder het betreffende id
 */
function excludeID(array $oldArray, int $id) {
	$newArray = [];
	foreach($oldArray as $key => $value) {
		if($key != $id) {
			$newArray[$key] = $value;
		}
	}
	
	return $newArray;
}



/**
 * Maak een opsomming van een array.
 *
 * @param mixed $array	Array waar de opsomming van gemaakt moet wordne
 * @param string $first Scheidingstekens tussen de array-elementen muv de laatste (standaard ,)
 * @param string $last Scheidingsteken voor het laatste array-element (standaard 'en')
 *
 * @return string String met opsomming van array-elementen
 */
function makeOpsomming($array, $first = ',', $last = 'en') {
	if(count($array) > 1) {
		$lastElement = array_pop($array);
		return implode("$first ", $array)." $last ".$lastElement;
	} else {
		return implode("$first ", $array);
	}
}



/**
 * Genereer een bestandsnaam.
 * De bestandsnaam bestaat uit hexadecimale tekens
 * @return string met de bestandsnaam
 */
function generateFilename() {
    $s = strtoupper(md5(uniqid(rand(),true)));
    $guidText = substr($s,0,4) . '-'. date('dmyHis').'-'. substr($s,4);
    return $guidText;
}



/**
 * Controleer of het gegeven mailadres een geldig adres is.
 * @param string $email Het te controleren mailadres
 * 
 * @return bool True (geldig) of False (ongeldig)
 */
function isValidEmail($email) {
	if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return true;
	}
	return false;
}



/**
 * Trim de whitespaces voor en achter de string.
 * Deze werkt ook bij Unicode tekens
 * @param string $string De string waar whitespaces getrimd moeten worden
 * 
 * @return string Getrimde string
 */
function trim_unicode($string) {
	$string = preg_replace('/\s+/u', ' ', $string);
	return trim($string);
}



/**
 * Genereer een wachtwoord
 * @param int lengte van het wachtwoord, default is 8 tekens
 * @return string wachtwoord
 */
function generatePassword ($length = 8) {
	// start with a blank password
	$password = "";

	$klink[] = 'a';
	$klink[] = 'e';
	$klink[] = 'i';
	$klink[] = 'o';
	$klink[] = 'u';
	$klink[] = 'ei';
	$klink[] = 'ij';
	$klink[] = 'ie';

	$mede[] = 'b';
	$mede[] = 'c';
	$mede[] = 'd';
	$mede[] = 'f';
	$mede[] = 'g';
	$mede[] = 'h';
	$mede[] = 'j';
	$mede[] = 'k';
	$mede[] = 'l';
	$mede[] = 'm';
	$mede[] = 'n';
	$mede[] = 'p';
	$mede[] = 'q';
	$mede[] = 'r';
	$mede[] = 's';
	$mede[] = 't';
	$mede[] = 'v';
	$mede[] = 'w';
	$mede[] = 'x';
	$mede[] = 'y';
	$mede[] = 'z';
	$mede[] = 'ch';

	$len_klink = count($klink);
	$len_mede = count($mede);

	// set up a counter for how many characters are in the password so far
	$i = 0;

	// add random characters to $password until $length is reached
	while(strlen($password) < $length) {
		if(fmod($i, 2) == 0) {
			$id = mt_rand(0, $len_mede-1);
			$char = $mede[$id];
		} else {
			$id = mt_rand(0, $len_klink-1);
			$char = $klink[$id];
		}
			
		$password .= $char;
		$i++;
	}

	// done!
	return ucfirst($password);
}



/**
 * Genereer een random code als hash
 * @param int $length Lengte van de code, default is 8
 * 
 * @return string de random code
 */
function generateID($length=8) {    
    $s = strtoupper(bin2hex(openssl_random_pseudo_bytes($length)));
    $guidText = substr($s,0,$length);
    return $guidText;
}



/**
 * Vraag de data van Pasen in een bepaald jaar op
 * @param mixed $jaar Jaar waarvoor Pasen opgezocht moet worden
 * 
 * @return array Array met key 'dag' voor de dag, en key 'maand' voor de maand waarop Pasen valt
 */
function getPasen($jaar) {
	$url = 'https://www.kalender-365.nl/feestdagen/pasen.html';
		
	$i = 0;
	$doorgaan = true;
	$data = array();

	$contents	= file_get_contents($url);
	$rijen		= explode('<tr><td', $contents);
	
	$start		= mktime(0,0,0, 1, 1,$jaar);
	$end		= mktime(0,0,0,12,31,$jaar);
		
	do {
		$i++;
		$rij = $rijen[$i];
		
		$tijd = getString('data-value="', '"', $rij, 0);
		#$datum = getString('class="dtr tar">', '</td>', $rij, 0);
				
		if(($i > (count($rijen)-2)) OR ($tijd[0] > $start AND $tijd[0] < $end)) {	
			$doorgaan = false;
		}		
	} while($doorgaan);
	
	$data['dag']	= date("j", $tijd[0]);
	$data['maand']	= date("n", $tijd[0]);
	
	return $data;
}



/**
 * Zoek de GPS-coordinaten op van een adres
 * @param mixed $q Adres waarop gezocht moet worden
 * 
 * @return Array met latitude- en longitude van het adres
 */
function getCoordinates($q) {
	global $locationIQkey;
		
	$url = "https://eu1.locationiq.com/v1/search.php?key=$locationIQkey";	
	$url .= "&q=". urlencode($q);
	$url .= "&format=json";
			
	$contents		= file_get_contents($url);
	$json				= json_decode($contents, true);		
	$longitude	= $json[0]['lon'];	# 52
	$latitude		= $json[0]['lat']; 	# 6
		
	return array($latitude, $longitude);
}



/**
 * Zoek de reisafstand tussen 2 adressen
 * @param string $start Startpunt
 * @param string $end Eindpunt
 * 
 * @return array Array met heen- en terugreis
 */
function determineAddressDistance(string $start, string $end) {
	global $locationIQkey;
	
	$service = 'matrix';
	$profile = 'driving';
	
	if($end == 'Mariënburghstraat 4, Deventer') {		
		$latitude_end = '52.267184';
		$longitude_end = '6.159086';
	} else {
		$coord_end = getCoordinates($end);
		$latitude_end = $coord_end[0];
		$longitude_end = $coord_end[1];		
		
		# Om niet 2x vlak achter elkaar een request te doen even 1 seconden wachten
		sleep(1);
	}
		
	$coord_start = getCoordinates($start);
	$latitude_start = $coord_start[0];
	$longitude_start = $coord_start[1];
	
	if($longitude_start > 0 AND $latitude_start > 0 AND $longitude_end > 0 AND $latitude_end > 0) {
		$coordinates = "{$longitude_start},{$latitude_start};{$longitude_end},{$latitude_end}";

		# https://locationiq.com/docs-html/index.html#matrix
		$url = "https://eu1.locationiq.com/v1/$service/$profile/$coordinates?key=$locationIQkey&sources=0;1&destinations=1;0&annotations=distance";
	
		$contents		= file_get_contents($url);
		$json				= json_decode($contents, true);
	
		$heen = ($json['distances'][0][0])/1000;
		$terug = ($json['distances'][1][1])/1000;
		
		$afstand = array($heen, $terug);		
	} else {
		$afstand = array(0,0);
	}
		
	return $afstand;
}



/**
 * Genereer een link waarmee een gastpredikant zijn declaratie kan indienen
 * @param int $dienst ID van de dienst waarvoor de link geldt
 * @param int $voorganger ID van de predikant waarvoor de link geldt
 * @param bool $afzeggen Moet het een link worden om af te zien van declaratie
 * 
 * @return string URL van de link voor de declaratie
 */
function generateDeclaratieLink(int $dienst, int $voorganger, $afzeggen = false) {
	global $randomCodeDeclaratie, $ScriptURL;
	
	# Declaratielink genereren
	$hash = urlencode(password_hash($dienst.'$'.$randomCodeDeclaratie.'$'.$voorganger, PASSWORD_BCRYPT));
	$declaratieLink = $ScriptURL ."declaratie/". ($afzeggen ? 'geenDeclaratie.php' : 'gastpredikant.php') ."?hash=$hash&d=$dienst&v=$voorganger";
	
	return $declaratieLink;
}



/**
 * Schoon een IBAN-nummer op
 * @param string $iban IBAN-nummer wat opgeschoond moet worden
 * 
 * @return string Schoongemaakt IBAN-nummer
 */
function cleanIBAN(string $iban) {
	$toClean = $iban;
	
	$toClean = trim($toClean);
	$toClean = strtoupper($toClean);
	$toClean = str_replace(' ', '', $toClean);
	$toClean = str_replace('.', '', $toClean);
	
	return $toClean;
}



/**
 * Controleer of een IBAN een bestaand/valide IBAN-nummer is
 * @param string $iban IBAN wat gecontroleerd moet worden
 * 
 * @return bool Is het een geldig IBAN
 */
function validateIBAN(string $iban) {
    $iban = strtolower(str_replace(' ','',$iban));
    $Countries = array('al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24);
    $Chars = array('a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35);

    if(strlen($iban) == $Countries[substr($iban,0,2)]){
        $MovedChar = substr($iban, 4).substr($iban,0,4);
        $MovedCharArray = str_split($MovedChar);
        $NewString = "";

        foreach($MovedCharArray AS $key => $value){
            if(!is_numeric($MovedCharArray[$key])){
                $MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
            }
            $NewString .= $MovedCharArray[$key];
        }

        if(bcmod($NewString, '97') == 1) {
            return true;
        }
    }
    return false;
}

/**
 * Bereken het totaal van een array met waardes.
 * Negatieve waardes worden genegeerd.
 * Met name gebruikt bij declaraties.
 * @param array $array Array met waardes
 * 
 * @return float Totaal van de waardes in het array
 */
function calculateTotals(array $array) {
	$totaal = 0;
	
	foreach($array as $waarde) {
		if($waarde > 0) {
			$price = price2RightFormat($waarde);
			$totaal = $totaal + $price;
		}
	}
	
	return $totaal;
}

/**
 * Zet een prijs in het juiste formaat.
 * Verwijderd spaties en zet komma's om in punten.
 * @param float $price Prijs
 * 
 * @return float Prijs in het juiste formaat
 */
function price2RightFormat(float $price) {
	$toClean = $price;
	
	$toClean = trim($toClean);
	$toClean = str_replace(' ', '', $toClean);
	$toClean = str_replace(',', '.', $toClean);
	
	return $toClean;
}


/**
 * Geef alle gegevens van de declaratie weer in een tabel
 * 
 * @param Declaratie Declaratie object
 * 
 * @return array Array met HTML-code voor een opgemaakte tabel met de declaratiegegevens
 * 
 */
function showDeclaratieDetails(Declaratie $declaratie) {
	global $clusters;

	$user = new Member($declaratie->gebruiker);

	$page[] = "<tr>";
	$page[] = "		<td><b>Indiener<b></td>";
	$page[] = "		<td>&nbsp;</td>";
	$page[] = "		<td colspan='4' align='right'>". $user->getName(5) ."</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "		<td><b>Datum<b></td>";
	$page[] = "		<td>&nbsp;</td>";
	$page[] = "		<td colspan='4' align='right'>". date('d-m-Y H:i', $declaratie->tijd) ."</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "		<td><b>Cluster<b></td>";
	$page[] = "		<td>&nbsp;</td>";
	$page[] = "		<td colspan='4' align='right'>". $clusters[$declaratie->cluster] ."</td>";
	$page[] = "</tr>";
	
	if($declaratie->begunstigde > 0) {
		$data = eb_getRelatieDataByCode($declaratie->begunstigde);

		$page[] = "<tr>";
		$page[] = "		<td><b>Begunstigde<b></td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td colspan='4' align='right'>".$data['naam'] ."</td>";
		$page[] = "</tr>";
	}

	$page[] = "<tr>";
	$page[] = "		<td colspan='6'>&nbsp;</td>";
	$page[] = "</tr>";

	if($declaratie->reiskosten > 0) {
		$page[] = "<tr>";
		$page[] = "		<td><b>Reiskosten<b></td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td colspan='2'>". $declaratie->van .' <-> '. $declaratie->naar ." (". round($declaratie->afstand, 1) ." km)</td>";
		$page[] = "		<td>". formatPrice($declaratie->reiskosten, true) ."</td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "</tr>";	
	}
		
	if(isset($declaratie->overigeKosten) && count($declaratie->overigeKosten) > 0) {
		$first = true;
		foreach($declaratie->overigeKosten as $item => $bedrag) {
			if($bedrag > 0) {
				$page[] = "<tr>";
				$page[] = "		<td>". ($first ? '<b>Declaratie<b>' : '') ."</td>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td colspan='2'>". $item ."</td>";			
				$page[] = "		<td align='right'>". formatPrice($bedrag, true) ."</td>";
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "</tr>";
				$first = false;
			}
		}		
	}

	$page[] = "<tr>";
	$page[] = "		<td><b>Totaal<b></td>";
	$page[] = "		<td colspan='4'>&nbsp;</td>";
	$page[] = "		<td align='right'><b>". formatPrice($declaratie->totaal, true) ."</b></td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "		<td colspan='6'>&nbsp;</td>";
	$page[] = "</tr>";
	
	if(isset($declaratie->bijlagen) && $declaratie->bijlagen > 0) {
		$first = true;
		foreach($declaratie->bijlagen as $file => $name) {
			$page[] = "<tr>";
			$page[] = "		<td>". ($first ? '<b>Bijlage<b>' : '') ."</td>";
			$page[] = "		<td>&nbsp;</td>";
			$page[] = "		<td colspan='4' align='right'><a href='". $file ."'>". $name  ."</a></b></td>";
			$page[] = "</tr>";
			$first = false;
		}
	}

	if($declaratie->opmerking != '') {
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td><b>Opmerking<b></td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td colspan='4'>". $declaratie->opmerking ."</td>";
	} elseif(count($declaratie->correspondentie) > 0) {
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>&nbsp;</td>";
		$page[] = "</tr>";

		$first = true;
		foreach($declaratie->correspondentie as $regel) {
			$user = new Member($regel['user']);
			$page[] = "<tr>";
			$page[] = "		<td>". ($first ? '<b>Correspondentie<b>' : '') ."</td>";
			$page[] = "		<td colspan='1' valign='top'>". $user->getName(5) ."<br>". date('d-m-y H:i', $regel['time']) ."</td>";
			$page[] = "		<td colspan='4' valign='top'>". $regel['text'] ."</td>";			
			$page[] = "</tr>";
			$first = false;
		}
	}

	return $page;	
}

/**
 * Encodeer een array naar JSON, maar maak het ook schoon.
 *
 * Hoewel het idee was dat alles omzetten naar JSON veel problemen verhelpt.
 * Veroorzaakt het ook wel wat problemen. Daarom deze functie.
 * Om te beginnen worden alle " vervangen door '. Dat is omdat anders het HTML-formulier in de war raakt.
 * Vervolgens worden alle velden doorlopen en omgezet in UTF-8, mn van belang voor 'gekke' tekens.
 * Vervolgens wordt alles omzetten in JSON-formaat
 * En dan de newlines vervangen door een spatie
 * @deprecated Met het gebruik van Declaratie-object niet meer in gebruik
 * 
 * @param array $input Array wat omgezet moet worden
 * 
 * @return string Schoongemaakte JSON-string
 */
function encode_clean_JSON(array $input) {
	$array = $input;
	$newArray = array();
	
	foreach($array as $key => $value) {
		if(is_array($value)) {
			foreach($value as $sub_key => $sub_value) {
				$sub_value = str_replace('"', "'", $sub_value);
				$sub_value = iconv('Windows-1252', 'UTF-8', $sub_value);
				
				$value[$sub_key] = $sub_value;
			}			
		} else {
			$value = str_replace('"', "'", $value);
			$value = iconv('Windows-1252', 'UTF-8', $value);
		}
		
		$newArray[$key] = $value;
	}
	$JSONString = json_encode($newArray);
	$string = str_replace('\r\n', ' ', $JSONString);
		
	return $string;
}



/**
 * @param string String die opgeschoond moet worden
 * 
 * @return string Opgeschoonde string
 */
function cleanDeclaratieString(string $in) {
	$string = $in;

	$string = str_replace('"', '*', $string);
	$string = str_replace('\\r\\n', ' ', $string);

	return $string;	
}

/**
 * Resize een afbeelding naar de opgegeven breedte en hoogte.
 * 
 * Bij declaraties wil men nog wel eens grote foto's uploaden.
 * Omdat de meeste gebruikers geen idee hebben hoe ze moeten resizen,
 * doen we dat hier automatisch.
 *
 * @param string $file Pad naar het bestand wat geresized moet worden
 * @param int $w Breedte van het nieuwe plaatje
 * @param int $h Hoogte van het nieuwe plaatje
 * @param bool $crop Moet het nieuwe plaatje exacte de afmeting hebben van $w en $h (true) of moet er geschaald worden op de grootste waarde
 * 
 * @return string Pad naar het nieuwe plaatje
 */
function resize_image($file, $w, $h, $crop=false) {
	$newFile = 'uploads/'.generateFilename();
	
	list($width, $height) = getimagesize($file);
	$r = $width / $height;
	
	if ($crop) {
		if ($width > $height) {
			$width = ceil($width-($width*abs($r-$w/$h)));
		} else {
			$height = ceil($height-($height*abs($r-$w/$h)));
		}
		
		$newwidth = $w;
		$newheight = $h;
   } else {
   	if ($w/$h > $r) {
   		$newwidth = $h*$r;
   		$newheight = $h;
   	} else {
   		$newheight = $w/$r;
   		$newwidth = $w;
   	}
  }
  
  $src = imagecreatefromjpeg($file);
  $dst = imagecreatetruecolor($newwidth, $newheight);
  imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
  imagejpeg($dst, $newFile, 100);
  
  return $newFile;
}


/**
 * Probeer op basis van een datum-string deze om te zetten naar dd-mm-jjjj formaat
 * @param string $string Datum-string
 * @param string $scheiding Scheidingsteken (tussen dag, maand, jaar)
 * 
 * @return string Datum in formaat dd-mm-jjjj
 */
function guessDate(string $string, string $scheiding) {	
	$string = trim($string);
	$string = str_ireplace('zondag ', '', $string);
	$string = str_ireplace('maandag ', '', $string);
	$string = str_ireplace('dinsdag ', '', $string);
	$string = str_ireplace('woensdag ', '', $string);
	$string = str_ireplace('donderdag ', '', $string);
	$string = str_ireplace('vrijdag ', '', $string);
	$string = str_ireplace('zaterdag ', '', $string);		
	$string = str_ireplace('januari', $scheiding.'01'.$scheiding, $string);
	$string = str_ireplace('februari', $scheiding.'02'.$scheiding, $string);
	$string = str_ireplace('maart', $scheiding.'03'.$scheiding, $string);
	$string = str_ireplace('april', $scheiding.'04'.$scheiding, $string);
	$string = str_ireplace('mei', $scheiding.'05'.$scheiding, $string);
	$string = str_ireplace('juni', $scheiding.'06'.$scheiding, $string);
	$string = str_ireplace('juli', $scheiding.'07'.$scheiding, $string);
	$string = str_ireplace('augustus', $scheiding.'08'.$scheiding, $string);
	$string = str_ireplace('september', $scheiding.'09'.$scheiding, $string);
	$string = str_ireplace('oktober', $scheiding.'10'.$scheiding, $string);
	$string = str_ireplace('november', $scheiding.'11'.$scheiding, $string);
	$string = str_ireplace('december', $scheiding.'12'.$scheiding, $string);
	$string = str_ireplace('sept.', $scheiding.'09'.$scheiding, $string);
	$string = str_ireplace('jan.', $scheiding.'01'.$scheiding, $string);
	$string = str_ireplace('feb.', $scheiding.'02'.$scheiding, $string);
	$string = str_ireplace('mrt.', $scheiding.'03'.$scheiding, $string);
	$string = str_ireplace('apr.', $scheiding.'04'.$scheiding, $string);
	$string = str_ireplace('mei.', $scheiding.'05'.$scheiding, $string);
	$string = str_ireplace('jun.', $scheiding.'06'.$scheiding, $string);
	$string = str_ireplace('jul.', $scheiding.'07'.$scheiding, $string);
	$string = str_ireplace('aug.', $scheiding.'08'.$scheiding, $string);
	$string = str_ireplace('sep.', $scheiding.'09'.$scheiding, $string);
	$string = str_ireplace('okt.', $scheiding.'10'.$scheiding, $string);
	$string = str_ireplace('nov.', $scheiding.'11'.$scheiding, $string);
	$string = str_ireplace('dec.', $scheiding.'12'.$scheiding, $string);
	$string = str_ireplace('sept', $scheiding.'09'.$scheiding, $string);
	$string = str_ireplace('jan', $scheiding.'01'.$scheiding, $string);
	$string = str_ireplace('feb', $scheiding.'02'.$scheiding, $string);
	$string = str_ireplace('mrt', $scheiding.'03'.$scheiding, $string);
	$string = str_ireplace('apr', $scheiding.'04'.$scheiding, $string);
	$string = str_ireplace('mei', $scheiding.'05'.$scheiding, $string);
	$string = str_ireplace('jun', $scheiding.'06'.$scheiding, $string);
	$string = str_ireplace('jul', $scheiding.'07'.$scheiding, $string);
	$string = str_ireplace('aug', $scheiding.'08'.$scheiding, $string);
	$string = str_ireplace('sep', $scheiding.'09'.$scheiding, $string);
	$string = str_ireplace('okt', $scheiding.'10'.$scheiding, $string);
	$string = str_ireplace('nov', $scheiding.'11'.$scheiding, $string);
	$string = str_ireplace('dec', $scheiding.'12'.$scheiding, $string);
	
	$string = str_replace(' '.$scheiding, $scheiding, $string);
	$string = str_replace($scheiding.' ', $scheiding, $string);
	$string = str_replace(' ', '', $string);
	
	$delen = explode($scheiding, $string);
	if(count($delen) == 3) {
		if($delen[2] == '') {
			if(mktime(0,0,0,$delen[1],$delen[0],date('Y')) < time()){
				$delen[2] = date('Y')+1;
			} else {
				$delen[2] = date('Y');
			}
		}		
		$string = implode('-', $delen);
	}
	
	return $string;
}


/**
 * Controleer of een string een datum is
 * @param string $string Te controleren datum
 * 
 * @return bool Is de string een datum of niet
 */
function isDatum(string $string) {
	if(strpos($string, 'januari')) return true;
	if(strpos($string, 'februari')) return true;
	if(strpos($string, 'maart')) return true;
	if(strpos($string, 'april')) return true;
	if(strpos($string, 'mei')) return true;
	if(strpos($string, 'juni')) return true;
	if(strpos($string, 'juli')) return true;
	if(strpos($string, 'augustus')) return true;
	if(strpos($string, 'september')) return true;
	if(strpos($string, 'oktober')) return true;
	if(strpos($string, 'november')) return true;
	if(strpos($string, 'december')) return true;
	if(strpos($string, 'sept')) return true;
	if(strpos($string, 'jan')) return true;
	if(strpos($string, 'feb')) return true;
	if(strpos($string, 'mrt')) return true;
	if(strpos($string, 'apr')) return true;
	if(strpos($string, 'mei')) return true;
	if(strpos($string, 'jun')) return true;
	if(strpos($string, 'jul')) return true;
	if(strpos($string, 'aug')) return true;
	if(strpos($string, 'sep')) return true;
	if(strpos($string, 'okt')) return true;
	if(strpos($string, 'nov')) return true;
	if(strpos($string, 'dec')) return true;
	
	return false;
}

?>