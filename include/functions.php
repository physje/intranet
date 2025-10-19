<?php

/**
 * @param mixed $format
 * @param int $time
 * 
 * @return [type]
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
	Y = 4 digit year (%Y)
	H = 00 to 23 (%H)
	i = 00 to 59 (%M)
	*/

	#return strftime($format, $time);	
    return date($format, $time);
}


/**
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
 * @param string $hash Hash van de persoon die inlogd
 * 
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
 * @param int $start Starttijd in UNIX-tijd
 * @param bool $dienst Moet 'dienst' achter het dagdeel gezet worden
 * 
 * @return [type] Naam van het dagdeel
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
?>