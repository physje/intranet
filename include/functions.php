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
 * @param mixed $format In welk format moet de datum worden opgemaakt.
 * @param int $time tijd in UNIX-format
 *
 * @return string opgemaakte datum
 */
function time2str(string $format, int $time = 0) {
	if($time == 0) {
		$time = time();
	}	
	
	// Check for Windows to find and replace the %e
	// modifier correctly
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
		$format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
	}

	/*
	D = Man, Tue, Wed, Thu, Fri, Sat, Sun (%a)
	l = Sunday to Saturday (%A)
	j = 1 to 31 (%e)
	d = 01 to 31 (%d)
	m = 01 to 12 (%m)
	M = Jan, Feb, Mar, Apr, May, Jun, Jul, Aug, Sep, Oct, Nov, Dec (%b)
	F = January to December (%B)
	Y = 4 digit year (%Y)
	H = 00 to 23 (%H)
	i = 00 to 59 (%M)
	*/

	#return strftime($format, $time);	
    return date($format, $time);
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
 * @return [type] Waarde van de parameter of de standaardwaarde
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
?>