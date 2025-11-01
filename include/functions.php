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