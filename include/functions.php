<?php

/**
 * Maakt verbinding met de database
 * @return mysqli-database-link
 */
function connect_db() {
	global $dbHostname, $dbUsername, $dbPassword, $dbName;
	
	$link = mysqli_connect($dbHostname, $dbUsername, $dbPassword, $dbName) or die("Error " . mysqli_error($link));
	mysqli_set_charset($link, 'utf8mb4');
	
	return $link;
}

/**
 * Genereer een gebruikersnaam voor gebruiker
 * @param int id van de gebruiker
 * @return string gebruikersnaam
 */
function generateUsername($id) {
	$data = getMemberDetails($id);
	
	if($data['voorletters'] != '') {
		$voor = strtoupper(str_replace('.', '', $data['voorletters']));
	}
	
	$achter = ucfirst(str_replace(' ', '', $data['achternaam']));

	$username = $voor.$achter;
	
	while(!isUniqueUsername($username)) {
		if($data['meisjesnaam'] != '') {
			$username = $voor.$achter.ucfirst(str_replace(' ', '', $data['meisjesnaam']));
		} elseif($data['voornaam'] != '') {
			$username = ucfirst(str_replace(' ', '', $data['voornaam'])).$achter;
		} else {
			$username = $voor.$achter.$i;
			$i++;
		}
	}
	
	return $username;
}

/**
 * Controleert of een gebruikersnaam uniek is
 * @param string gebruikersnaam
 * @return boolean
 */
function isUniqueUsername($username) {
	global $TableUsers, $UserUsername;
	$db = connect_db();
	
	$sql = "SELECT * FROM $TableUsers WHERE $UserUsername like '$username'";
	$result = mysqli_query($db, $sql);
	if(mysqli_num_rows($result) == 0) {
		return true;
	} else {
		return false;
	}
}

/**
 * Genereert een wachtwoord
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

function generateID($length=8) { 
    //$s = strtoupper(md5(uniqid(rand(),true))); 
    $s = strtoupper(bin2hex(openssl_random_pseudo_bytes($length)));
    $guidText = substr($s,0,$length); 
    return $guidText;
}

function getAllKerkdiensten($fromNow = false) {
	global $TableDiensten, $DienstID, $DienstEind;
	$db = connect_db();
		
	if($fromNow) {
		$startTijd = time();
	} else {
		$startTijd = time() - (31*24*60*60);
	}
	
	$eindTijd = mktime(0,0,0,1,1,date('Y')+5);
	
	return getKerkdiensten($startTijd, $eindTijd);
}



function getKerkdiensten($startTijd, $eindTijd) {
	global $TableDiensten, $DienstID, $DienstEind;
	$db = connect_db();
	$id = array();
				
	$sql = "SELECT $DienstID FROM $TableDiensten WHERE $DienstEind BETWEEN $startTijd AND $eindTijd ORDER BY $DienstEind ASC";
		
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$id[] = $row[$DienstID];
		} while($row = mysqli_fetch_array($result));
	}
	return $id;
}


function getKerkdienstDetails($id) {
	global $TableDiensten, $DienstID, $DienstStart, $DienstEind, $DienstVoorganger, $DienstCollecte_1, $DienstCollecte_2, $DienstOpmerking, $DienstRuiling, $DienstLiturgie;
	$db = connect_db();
	
	$data = array();
	
	$sql = "SELECT * FROM $TableDiensten WHERE $DienstID = $id";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		$voorgangerData = getVoorgangerData($row[$DienstVoorganger]);
		
		$data['start']					= $row[$DienstStart];
		$data['eind']						= $row[$DienstEind];
		$data['collecte_1']			= urldecode($row[$DienstCollecte_1]);
		$data['collecte_2']			= urldecode($row[$DienstCollecte_2]);
		$data['bijzonderheden']	= urldecode($row[$DienstOpmerking]);
		$data['voorganger_id']	= $row[$DienstVoorganger];
		$data['voorganger']			= strtolower($voorgangerData['titel']).' '.$voorgangerData['init'].' '.($voorgangerData['tussen'] == '' ? '' : $voorgangerData['tussen'].' ').$voorgangerData['achter'];
		if(strtolower($voorgangerData['plaats']) != 'deventer' AND $voorgangerData['plaats'] != '')	$data['voorganger'] .= ' ('.$voorgangerData['plaats'].')';
		$data['voorganger']			= trim($data['voorganger']);
		$data['ruiling']				= $row[$DienstRuiling];
		$data['liturgie']       = urldecode($row[$DienstLiturgie]);
	}
	return $data;
}

function getMembers($type = 'all') {
	global $TableUsers, $UserStatus, $UserID, $UserAdres, $UserGeboorte, $UserAchternaam, $UserRelatie;	
	$db = connect_db();
	
	$data = array();
	
	if($type == 'all') {
		$sql = "SELECT $UserID FROM $TableUsers WHERE $UserStatus = 'actief' ORDER BY $UserAchternaam";
	} elseif($type == 'volwassen') {
		$sql = "SELECT $UserID FROM $TableUsers WHERE $UserStatus = 'actief' AND $UserGeboorte < '". (date("Y")-18) ."-". date("m-d") ."' ORDER BY $UserAchternaam";
	} elseif($type == 'adressen') {
		$sql = "SELECT $UserID FROM $TableUsers WHERE $UserStatus = 'actief' AND ($UserRelatie like 'gezinshoofd' OR $UserRelatie like 'zelfstandig') GROUP BY $UserAdres ORDER BY $UserAchternaam";
	}
		
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$data[] = $row[$UserID];
		} while($row = mysqli_fetch_array($result));		
	}
	return $data;	
}

function getGroupMembers($commID) {
	global $TableGrpUsr, $GrpUsrGroup, $GrpUsrUser;
	global $TableUsers, $UserID, $UserAchternaam;
	$db = connect_db();
	
	$data = array();
	$sql = "SELECT $TableGrpUsr.$GrpUsrUser FROM $TableGrpUsr, $TableUsers WHERE $TableUsers.$UserID = $TableGrpUsr.$GrpUsrUser AND $TableGrpUsr.$GrpUsrGroup = $commID ORDER BY $TableUsers.$UserAchternaam";
	
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$data[] = $row[$GrpUsrUser];
		} while($row = mysqli_fetch_array($result));		
	}
	return $data;	
}

function getMemberDetails($id) {
	global $TableUsers, $UserID, $UserStatus, $UserAdres, $UserGeslacht, $UserVoorletters, $UserVoornaam, $UserTussenvoegsel,
	$UserAchternaam, $UserMeisjesnaam, $UserUsername, $UserHashShort, $UserGeboorte, $UserTelefoon, $UserMail,
	$UserFormeelMail, $UserBelijdenis, $UserLastChange, $UserLastVisit, $UserBurgelijk, $UserRelatie, $UserStraat, $UserHuisnummer,
	$UserHuisletter, $UserToevoeging, $UserPC, $UserPlaats, $UserWijk, $UserHashLong, $UserVestiging, $UserLastChange, $UserLastVisit;
	
	$db = connect_db();
	
	$data = array();
		
	$sql = "SELECT * FROM $TableUsers WHERE $UserID = $id";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
		
	$data['id']							= $row[$UserID];
	$data['status']					= $row[$UserStatus];
	$data['adres']					= $row[$UserAdres];
	$data['geslacht']				= $row[$UserGeslacht];
	$data['belijdenis']			= $row[$UserBelijdenis];
	$data['voorletters']		= $row[$UserVoorletters];
	$data['voornaam']				= $row[$UserVoornaam];
	$data['tussenvoegsel']	= $row[$UserTussenvoegsel];
	$data['achternaam']			= $row[$UserAchternaam];
	$data['meisjesnaam']		= $row[$UserMeisjesnaam];
	$data['username']				= $row[$UserUsername];
	$data['hash_short']			= $row[$UserHashShort];
	$data['hash_long']			= $row[$UserHashLong];
	$data['geboorte']				= $row[$UserGeboorte];		
	$data['jaar']						= substr($row[$UserGeboorte], 0, 4);
	$data['maand']					= substr($row[$UserGeboorte], 5, 2);
	$data['dag']						= substr($row[$UserGeboorte], 8, 2);	
	$data['geb_unix']				= mktime(0,0,0,$data['maand'],$data['dag'],$data['jaar']);
	$data['straat']					= $row[$UserStraat];
	$data['huisnummer']			= $row[$UserHuisnummer];
	$data['huisletter']			= $row[$UserHuisletter];
	$data['toevoeging']			= $row[$UserToevoeging];
	$data['PC']							= $row[$UserPC];
	$data['plaats']					= $row[$UserPlaats];
	$data['wijk']						= $row[$UserWijk];
	$data['burgelijk']			= $row[$UserBurgelijk];
	$data['relatie']				= $row[$UserRelatie];	
	$data['tel']						= $row[$UserTelefoon];
	$data['mail']						= $row[$UserMail];
	$data['form_mail']			= $row[$UserFormeelMail];
	$data['vestiging']			= $row[$UserVestiging];
	$data['change']					= $row[$UserLastChange];
	$data['visit']					= $row[$UserLastVisit];
	
	return $data;
}

function getAllGroups() {
	global $TableGroups, $GroupID, $GroupNaam;	
	$db = connect_db();
	
	$data = array();
	
	$sql = "SELECT $GroupID FROM $TableGroups ORDER BY $GroupNaam";
		
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$data[] = $row[$GroupID];
		} while($row = mysqli_fetch_array($result));		
	}
	return $data;	
}

function getMyGroups($id) {
	global $TableGrpUsr, $GrpUsrGroup, $GrpUsrUser, $GroupNaam;	
	$db = connect_db();
	
	$data = array();
	
	$sql = "SELECT $GrpUsrGroup FROM $TableGrpUsr WHERE $GrpUsrUser = $id";
		
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$data[] = $row[$GrpUsrGroup];
		} while($row = mysqli_fetch_array($result));		
	}
	return $data;	
}

function getMyGroupsBeheer($id) {
	global $TableGroups, $TableGrpUsr, $GroupBeheer, $GrpUsrGroup, $GrpUsrUser, $GroupID, $GroupNaam;
	$db = connect_db();
	$data = array();
	
	$sql = "SELECT $TableGroups.$GroupID FROM $TableGroups, $TableGrpUsr WHERE $TableGroups.$GroupBeheer = $TableGrpUsr.$GrpUsrGroup AND $TableGrpUsr.$GrpUsrUser = $id ORDER BY $TableGroups.$GroupNaam";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$data[] = $row[$GroupID];
		} while($row = mysqli_fetch_array($result));		
	}
	return $data;	
}

function getGroupDetails($id) {
	global $TableGroups, $GroupID, $GroupNaam, $GroupHTMLIn, $GroupHTMLEx, $GroupShowIn, $GroupShowEx, $GroupBeheer, $GroupMCTag;
	$db = connect_db();
	
	$data = array();
	
	$sql = "SELECT * FROM $TableGroups WHERE $GroupID = '$id'";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		$data['naam']	= $row[$GroupNaam];
		$data['html-int']	= urldecode($row[$GroupHTMLIn]);
		$data['html-ext']	= urldecode($row[$GroupHTMLEx]);		
		$data['beheer']	= $row[$GroupBeheer];
		$data['tag']	= $row[$GroupMCTag];
	}
	return $data;	
}


function getGroupIDbyMCtag($tag) {
	global $TableGroups, $GroupID, $GroupMCTag;
	
	$db = connect_db();
	$sql = "SELECT * FROM $TableGroups WHERE $GroupMCTag = '$tag'";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		return $row[$GroupID];
	} else {
		return false;
	}
}


function getRoosters($id = 0) {
	global $TableRoosters, $RoostersID, $RoostersNaam, $RoostersGroep, $TableGrpUsr, $GrpUsrGroup, $GrpUsrUser;
	$db = connect_db();
	
	$data = array();
	
	if($id == 0) {
		$sql = "SELECT $RoostersID FROM $TableRoosters ORDER BY $RoostersNaam ASC";
	} else {
		$sql = "SELECT $TableRoosters.$RoostersID FROM $TableRoosters, $TableGrpUsr WHERE $TableGrpUsr.$GrpUsrGroup = $TableRoosters.$RoostersGroep AND $TableGrpUsr.$GrpUsrUser = $id";
	}
	
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$data[] = $row[$RoostersID];
		} while($row = mysqli_fetch_array($result));		
	}
	return $data;	
}

function getMyRoostersBeheer($id) {	
	global $TableRoosters, $TableGroups, $TableGrpUsr, $RoostersID, $RoostersBeheerder, $RoostersPlanner, $GroupID, $GrpUsrGroup, $GrpUsrUser;	
	
	$data = array();	
	$db = connect_db();
		
	$sql = "SELECT $TableRoosters.$RoostersID FROM $TableRoosters, $TableGroups, $TableGrpUsr WHERE ($TableRoosters.$RoostersBeheerder = $TableGroups.$GroupID OR $TableRoosters.$RoostersPlanner = $TableGroups.$GroupID) AND $TableGroups.$GroupID = $TableGrpUsr.$GrpUsrGroup AND $TableGrpUsr.$GrpUsrUser = $id";	
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {		
		do {
			$data[] = $row[$RoostersID];
		} while($row = mysqli_fetch_array($result));		
	}
	return $data;	
}

function getRoosterDetails($id) {
	global $TableRoosters, $RoostersID, $RoostersNaam, $RoostersBeheerder, $RoostersGroep, $RoostersPlanner, $RoostersFields, $RoostersReminder, $RoostersMail, $RoostersSubject, $RoostersFrom, $RoostersFromAddr, $RoostersGelijk, $RoostersTextOnly, $RoostersAlert, $RoostersOpmerking;
	$db = connect_db();
	
	$data = array();
	
	$sql = "SELECT * FROM $TableRoosters WHERE $RoostersID = '$id'";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		$data['naam']	= $row[$RoostersNaam];
		$data['groep']	= $row[$RoostersGroep];		
		$data['beheerder']	= $row[$RoostersBeheerder];
		$data['planner']	= $row[$RoostersPlanner];		
		$data['aantal']	= $row[$RoostersFields];
		$data['reminder']	= $row[$RoostersReminder];
		$data['text_mail']	= urldecode($row[$RoostersMail]);
		$data['onderwerp_mail']	= urldecode($row[$RoostersSubject]);		
		$data['naam_afzender']	= urldecode($row[$RoostersFrom]);
		$data['mail_afzender']	= urldecode($row[$RoostersFromAddr]);
		$data['gelijk']	= $row[$RoostersGelijk];
		$data['text_only']	= $row[$RoostersTextOnly];
		$data['alert']	= $row[$RoostersAlert];
		$data['opmerking']	= $row[$RoostersOpmerking];
	}
	return $data;	
}

function getBeheerder($groep) {
	global $TableGroups, $GroupID, $GroupBeheer; 
	$db = connect_db();
	
	$sql = "SELECT $GroupBeheer FROM $TableGroups WHERE $GroupID = $groep";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	if($row[$GroupBeheer] != 0) {
		return array($row[$GroupBeheer]);
	} else {
		return array();
	}	
}

#function getBeheerder4Rooster($rooster) {
#	global $TableRoosters, $RoostersGroep, $RoostersID, $TableGroups, $GroupID, $GroupBeheer;
#	$db = connect_db();
#	
#	/*
#	$sql = "SELECT $TableRoosters.$RoostersID FROM $TableRoosters, $TableGroups, $TableGrpUsr WHERE 
#	$TableRoosters.$RoostersGroep = $TableGroups.$GroupID AND
#	$TableGroups.$GroupBeheer = $TableGrpUsr.$GrpUsrGroup AND
#	$TableGrpUsr.$GrpUsrUser = $id";
#	*/
#	
#	$sql = "SELECT $TableGroups.$GroupBeheer FROM $TableRoosters, $TableGroups WHERE $TableRoosters.$RoostersGroep = $TableGroups.$GroupID AND $TableRoosters.$RoostersID = $rooster";
#	$result = mysqli_query($db, $sql);
#	$row = mysqli_fetch_array($result);
#	
#	return $row[$GroupBeheer];	
#}


function addGroupLid($lidID, $commID) {
	global $TableGrpUsr, $GrpUsrGroup, $GrpUsrUser;	
	$db = connect_db();
	
	$sql = "INSERT INTO $TableGrpUsr ($GrpUsrGroup, $GrpUsrUser) VALUES ($commID, $lidID)";
	if(mysqli_query($db, $sql)) {
		return true;
	} else {
		return false;
	}
}

function removeGroupLeden($commID) {
	global $TableGrpUsr, $GrpUsrGroup;	
	$db = connect_db();
	
	$sql = "DELETE FROM $TableGrpUsr WHERE $GrpUsrGroup = $commID";
	if(mysqli_query($db, $sql)) {
		return true;
	} else {
		return false;
	}
}

function removeFromRooster($rooster, $dienst) {
	global $TablePlanning, $PlanningDienst, $PlanningGroup;
	global $TableRoosOpm, $RoosOpmDienst, $RoosOpmRoos;
	global $TablePlanningTxt, $PlanningTxTDienst, $PlanningTxTGroup;
	
	$db = connect_db();
	
	$query[0] = false;
	$query[1] = false;
	$query[2] = false;
	
	$sql = "DELETE FROM $TablePlanning WHERE $PlanningDienst = $dienst AND $PlanningGroup = $rooster";
	if(mysqli_query($db, $sql)) {
		$query[0] = true;
	}
	
	$sql = "DELETE FROM $TableRoosOpm WHERE $RoosOpmDienst = $dienst AND $RoosOpmRoos = $rooster";
	if(mysqli_query($db, $sql)) {
		$query[1] = true;
	}
	
	$sql = "DELETE FROM $TablePlanningTxt WHERE $PlanningTxTDienst = $dienst AND $PlanningTxTGroup = $rooster";
	if(mysqli_query($db, $sql)) {
		$query[2] = true;
	}
}

function add2Rooster($rooster, $dienst, $persoon, $positie) {
	global $TablePlanning, $PlanningDienst, $PlanningGroup, $PlanningUser, $PlanningPositie;
	$db = connect_db();
	
	$sql = "INSERT INTO $TablePlanning ($PlanningDienst, $PlanningGroup, $PlanningPositie, $PlanningUser) VALUES ($dienst, $rooster, $positie, $persoon)";
	if(mysqli_query($db, $sql)) {
		return true;
	} else {
		return false;
	}
}

function getRoosterVulling($rooster, $dienst) {
	global $TablePlanning, $PlanningDienst, $PlanningGroup, $PlanningUser, $PlanningPositie;
	global $TablePlanningTxt, $PlanningTxTDienst, $PlanningTxTGroup, $PlanningTxTText;
	$db = connect_db();
	
	$details = getRoosterDetails($rooster);
	if($details['text_only'] == 0) {
		$data = array();
		
		$sql = "SELECT $PlanningUser FROM $TablePlanning WHERE $PlanningDienst = $dienst AND $PlanningGroup = $rooster ORDER BY $PlanningPositie ASC";
		$result = mysqli_query($db, $sql);
		if($row = mysqli_fetch_array($result)) {
			do {
				$data[] = $row[$PlanningUser];
			} while($row = mysqli_fetch_array($result));		
		}
	} else {
		$sql = "SELECT $PlanningTxTText FROM $TablePlanningTxt WHERE $PlanningTxTDienst = $dienst AND $PlanningTxTGroup = $rooster";
		$result = mysqli_query($db, $sql);
		$row = mysqli_fetch_array($result);
		$data = $row[$PlanningTxTText];		
	}
	return $data;	
}

function convertName($naam) {
	$data['voorletters'] = '';
	$data['voornaam'] = '';
	$data['tussenvoegsel'] = '';
	$data['achternaam'] = '';
	$data['meisjesnaam'] = '';
	
	if(strpos($naam, ' - ')) {
		$delen = explode('-', $naam);
		$data['meisjesnaam'] = trim($delen[1]);
		$string = trim($delen[0]);
	} else {
		$string = $naam;
	}
	
	if(strpos($naam, '(')) {
		$temp = getString('', '(', $string, 0);
		$data['voorletters'] = $temp[0];
		
		$temp = getString('(', ')', $temp[1], 0);
		$data['voornaam'] = $temp[0];
		
		if($temp[1] != ')') {
			$delen = explode(' ', substr($temp[1], 2));
			$data['achternaam'] = array_pop($delen);
			
			if(count($delen) > 0) {				
				$data['tussenvoegsel'] = implode(' ', $delen);
			}
		}
	} else {
		$delen = explode(' ', $string);
		
		if(count($delen) == 1) {
			$data['voornaam'] = $string;
			$data['voorletters'] = $string[0].'.';
		} else {
			$data['achternaam'] = array_pop($delen);
			$data['voorletters'] = array_shift($delen);	
				
			if(count($delen) > 0) {
				$data['tussenvoegsel'] = implode(' ', $delen);
			}
		}
	}
	
	if(!strpos($data['voorletters'], '.')) {
		$delen = explode(' ', $data['voorletters']);
		
		foreach($delen as $naam) {
			$voorletter[] = $naam[0];
		}
		
		$data['voorletters'] = implode('.', $voorletter);
	}

	return $data;
}

function makeName($id, $type) {
	global $TableUsers, $UserID, $UserVoorletters, $UserVoornaam, $UserTussenvoegsel, $UserAchternaam, $UserMeisjesnaam;
	$db = connect_db();
	
	$sql = "SELECT * FROM $TableUsers WHERE $UserID = $id";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	$voorletters = $row[$UserVoorletters];
	
	if($row[$UserVoornaam] != '') {
		$voornaam	= ucfirst($row[$UserVoornaam]);		
	} else {
		$voornaam = $voorletters;
	}
	
	$tussen 	= strtolower($row[$UserTussenvoegsel]);
	$achter 	= ucfirst($row[$UserAchternaam]);
	
	if($row[$UserMeisjesnaam] != '') {
		$achter_m = $row[$UserMeisjesnaam];
	} else {
		$achter_m = '';
	}
	
	# 1 = voornaam												Alberdien
	# 2 = korte achternaam								Jong
	# 3 = volledige achternaam (man)			de Jong
	# 4 = volledige achternaam (vrouw)		de Jong-van Ginkel
	# 5 = voornaam achternaam (man)				Alberdien de Jong
	# 6 = voornaam achternaam (vrouw)			Alberdien de Jong-van Ginkel
	# 7 = voornaam achternaam (vrouw)			Alberdien van Ginkel	
	# 8 = achternaam, voornaam						Jong; de, Alberdien
	# 9 = voorletters achternaam (man)		A. de Jong
	# 10 = voorletters achternaam (vrouw)	A. de Jong-van Ginkel
	# 11 = voorletters achternaam (vrouw)	A. van Ginkel
	# 12 = voorletters achternaam (man)		A. (Alberdien) de Jong
	# 13 = voorletters achternaam (vrouw)	A. (Alberdien) de Jong-van Ginkel
	# 14 = voorletters achternaam (vrouw)	A. (Alberdien) van Ginkel

	
	if($achter_m != '' AND ($type == 4 OR $type == 6 OR $type == 10 OR $type == 13)) {
		$achter .= '-'.$achter_m;
	} elseif($achter_m != '' AND ($type == 7 OR $type == 11)) {
		$achter = $achter_m;
	}
			
	if($tussen == '') {
		$tussenvoegsel	= '';
		$achternaam	= $achter;
	} else {
		$tussenvoegsel= $tussen;
		
		if($type == 2 OR $type == 7) {
			$achternaam	= $achter;
		} elseif($type == 8) {
			$achternaam	= $achter.'; '.$tussen;
		} else {
			$achternaam	= $tussen.' '.$achter;
		}
	}
		
	if($type == 1) {
		return urldecode($voornaam);
	} elseif($type == 2) {
		return urldecode($achternaam);
	} elseif($type == 3 OR $type == 4) {
		return urldecode($achternaam);
	} elseif($type == 5 OR $type == 6 OR $type == 7) {
		return urldecode($voornaam.' '.$achternaam);
	} elseif($type == 8) {
		return urldecode($achternaam.', '.$voornaam);
	} elseif($type == 9 OR $type == 10 OR $type == 11) {
		return urldecode($voorletters .' '. $achternaam);
	} elseif($type == 12 OR $type == 13 OR $type == 14) {
		if($voornaam != $voorletters) {
			return urldecode($voorletters .' ('. $voornaam .') '. $achternaam);
		} else {
			return urldecode($voorletters .' '. $achternaam);
		}
	}
}


function sendMail($ontvanger, $subject, $bericht, $var) {
	global $ScriptURL, $ScriptMailAdress, $ScriptTitle, $SubjectPrefix, $MailHeader, $MailFooter;
	
	# Er staat ook een formeel mailadres in de database
	# Met de variabele formeel kan worden aangegeven of deze gebruikt moet worden
	if(isset($var['formeel'])) {
		$formeel = $var['formeel'];
	} else {
		$formeel = false;
	}
	
	# Haal de data van de ontvanger op
	# Zoek ook direct de mail op van de ontvanger
	$UserData = getMemberDetails($ontvanger);
	$UserMail	= getMailAdres($ontvanger, $formeel);
						
	$HTMLMail = $MailHeader.$bericht.$MailFooter;
		
	$mail = new PHPMailer;	
	$mail->From     = $ScriptMailAdress;
	$mail->FromName = $ScriptTitle;
		
	if(isset($var['ReplyTo']) AND $var['ReplyTo'] != '') {
		if(isset($var['ReplyToName']) AND $var['ReplyToName'] != '') {
			$mail->AddReplyTo($var['ReplyTo'], $var['ReplyToName']);
		} else {
			$mail->AddReplyTo($var['ReplyTo']);
		}
	}
	
	$mail->AddAddress($UserMail, makeName($ontvanger, 5));
	
	$mail->Subject	= $SubjectPrefix . trim($subject);
	$mail->IsHTML(true);
	$mail->Body			= $HTMLMail;
		
	# Als de ouders ook een CC moeten
	# Alleen bij mensen die als relatie 'zoon' of 'dochter' hebben
	if(isset($var['ouderCC']) AND ($UserData['relatie'] == 'zoon' OR $UserData['relatie'] == 'dochter')) {
		$ouders = getParents($ontvanger);
		foreach($ouders as $ouder){
			$OuderData = getMemberDetails($ouder);
			if($OuderData['mail'] != $UserMail AND $OuderData['mail'] != '') {
				$mail->AddCC($OuderData['mail']);
				toLog('debug', $ontvanger, $ouder, makeName($ouder, 5) .' ('. $OuderData['mail'] .') als ouder in CC opgenomen');
			}
		}
	}
		
	if(isset($var['file']) AND $var['file'] != "") {
		if(isset($var['name']) AND $var['name'] != "") {
			$mail->addAttachment($var['file'], $var['name']);
		} else {
			$mail->addAttachment($var['file']);
		}
	}
	
	if(isset($var['BCC']) AND $var['BCC_mail'] != "") {
		$mail->AddBCC($var['BCC_mail']);
	}
			
	if(!$mail->Send()) {
		return false;
	} else {
		return true;
	}
}


function sendMail_new($parameter) {
	global $ScriptURL, $ScriptMailAdress, $ScriptTitle, $SubjectPrefix, $MailHeader, $MailFooter;
	
	# Controleer of er wel een ontvanger bekend is
	if(isset($parameter['to'])) {
		if(is_array($parameter['to'])) {
			$ontvangers = $parameter['to'];
		} else {
			$ontvangers = array($parameter['to']);
		}
	} else {
		echo 'Geen ontvangers bekend';
		exit;
	}
	
	# Controleer of er wel een bericht bekend is
	if(isset($parameter['message'])) {
		$bericht = $parameter['message'];
	} else {
		echo 'Geen bericht bekend';
		exit;
	}	
	
	# Controleer of er wel een onderwerp bekend is
	if(isset($parameter['subject'])) {
		$subject = $parameter['subject'];
	} else {
		echo 'Geen onderwerp bekend';
		exit;
	}	
	
	$mail = new PHPMailer;	
	$mail->From     = $ScriptMailAdress;
	$mail->FromName = $ScriptTitle;
	
	# Er staat ook een formeel mailadres in de database
	# Met de variabele formeel kan worden aangegeven of deze gebruikt moet worden
	if(isset($parameter['formeel'])) {
		$formeel = $parameter['formeel'];
	} else {
		$formeel = false;
	}
	
	# Als er een reply-adres ingesteld moet worden		
	if(isset($parameter['ReplyTo']) AND $parameter['ReplyTo'] != '') {
		if(isset($parameter['ReplyToName']) AND $parameter['ReplyToName'] != '') {
			$mail->AddReplyTo($parameter['ReplyTo'], $parameter['ReplyToName']);
		} else {
			$mail->AddReplyTo($parameter['ReplyTo']);
		}
	}
	
	# De personen die in de 'Aan' moeten
	# Met de check of ouders in de 'CC' moeten
	foreach($ontvangers as $ontvanger) {
		# Haal de data van de ontvanger op
		# Zoek ook direct de mail op van de ontvanger
		$UserData = getMemberDetails($ontvanger);
		$UserMail	= getMailAdres($ontvanger, $formeel);
		
		$mail->AddAddress($UserMail, makeName($ontvanger, 5));
		toLog('debug', '', $ontvanger, makeName($ontvanger, 5) .' in de Aan opgenomen');
		
		# Als de ouders ook een CC moeten
		# Alleen bij mensen die als relatie 'zoon' of 'dochter' hebben
		if(isset($parameter['ouderCC']) AND ($UserData['relatie'] == 'zoon' OR $UserData['relatie'] == 'dochter')) {
			$ouders = getParents($ontvanger);
			foreach($ouders as $ouder){
				$OuderData = getMemberDetails($ouder);
				if($OuderData['mail'] != $UserMail AND $OuderData['mail'] != '') {
					$mail->AddCC($OuderData['mail']);
					toLog('debug', '', $ontvanger, makeName($ouder, 5) .' ('. $OuderData['mail'] .') als ouder in CC opgenomen');
				}
			}
		}		
	}
	
	# De personen die in de 'CC' moeten
	if(isset($parameter['cc'])) {
		if(is_array($parameter['cc'])) {
			$cc_ontvangers	= $parameter['cc'];
		} else {
			$cc_ontvangers	= array($parameter['cc']);
		}		
		
		foreach($cc_ontvangers as $ontvanger) {
			if(is_numeric($ontvanger)) {
				$UserData = getMemberDetails($ontvanger);
				$UserMail	= getMailAdres($ontvanger, $formeel);
				$mail->AddCC($UserMail, makeName($ontvanger, 5));				
			} else {
				$mail->AddCC($ontvanger);
			}
		}
	}
	
	# De personen die in de 'BCC' moeten		
	if(isset($parameter['bcc'])) {
		if(is_array($parameter['bcc'])) {
			$bcc_ontvangers	= $parameter['bcc'];
		} else {
			$bcc_ontvangers	= array($parameter['bcc']);
		}
		
		foreach($bcc_ontvangers as $ontvanger) {
			if(is_numeric($ontvanger)) {				
				$UserMail	= getMailAdres($ontvanger, $formeel);
				$mail->AddBCC($UserMail);
			} else {
				$mail->AddBCC($ontvanger);
			}
		}		
	}
	
	# Controle op bijlages
	if(isset($parameter['file']) AND $parameter['file'] != "") {
		if(isset($parameter['name']) AND $parameter['name'] != "") {
			$mail->addAttachment($parameter['file'], $parameter['name']);
		} else {
			$mail->addAttachment($parameter['file']);
		}
	}
	
	# Bericht opstellen
	$HTMLMail = $MailHeader.$bericht.$MailFooter;
	
	# Onderwerp instellen
	$mail->Subject	= $SubjectPrefix . trim($subject);
	$mail->IsHTML(true);
	$mail->Body			= $HTMLMail;
			
	if(!$mail->Send()) {
		return false;
	} else {
		return true;
	}
}


function showBlock($block, $width) {
	$HTML[] = "<table width='$width%' cellpadding='8' cellspacing='1' bgcolor='#d2d2d2'>";
	$HTML[] = "<tr>                                                                 ";
	$HTML[] = "	<td bgcolor='#ffffff'>$block</td>";
	$HTML[] = "</tr>";
	$HTML[] = "</table>";
		
	return implode(NL, $HTML);
}

function getFamilieleden($id, $all = false) {
	global $TableUsers, $UserAdres, $UserStatus, $UserID;
	$db = connect_db();
	
	$data = array();
	
	$sql = "SELECT $UserID FROM $TableUsers WHERE $UserAdres IN (SELECT $UserAdres FROM $TableUsers WHERE $UserID = $id)";
	if(!$all)	$sql .= "AND $UserStatus = 'actief'";
	
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$data[] = $row[$UserID];
		} while($row = mysqli_fetch_array($result));		
	}
	return $data;	
}

function getParents($id, $hoofd = false) {
	$parents = array();
	$familie = getFamilieleden($id);
		
	foreach($familie as $lid) {
		$data = getMemberDetails($lid);
		if(($data['relatie'] == 'echtgenote' AND !$hoofd) OR $data['relatie'] == 'gezinshoofd') {
			$parents[] = $lid;
		}
	}	
	return $parents;
}

function getJarigen($dag, $maand) {
	global $TableUsers, $UserStatus, $UserID, $UserGeboorte;
	$db = connect_db();
	
	$data = array();
	
	$sql = "SELECT $UserID FROM $TableUsers WHERE $UserStatus = 'actief' AND DAYOFMONTH($UserGeboorte) = $dag AND MONTH($UserGeboorte) = $maand";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$data[] = $row[$UserID];
		} while($row = mysqli_fetch_array($result));		
	}
	return $data;	
}

function toLog($type, $dader, $slachtoffer, $message) {
	global $db,$TableLog, $LogID, $LogTime, $LogType, $LogUser, $LogSubject, $LogMessage;	
	$db = connect_db();
 	
	$tijd = time();	
	$sql = "INSERT INTO $TableLog ($LogTime, $LogType, $LogUser, $LogSubject, $LogMessage) VALUES ($tijd, '$type', '$dader', '$slachtoffer', '". addslashes($message) ."')";
	if(!mysqli_query($db, $sql)) {
		echo "log-error : ". $sql;
	}
}

function getParam($name, $default = '') {
	return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
}

function getLogData($start, $end, $types, $dader, $subject, $message, $aantal) {
	global $db, $TableLog, $LogID, $LogTime, $LogType, $LogUser, $LogSubject, $LogMessage;
		
	if($dader != '') {
		$where[] = "$LogUser = $dader";
	}
	
	if($subject!= '') {
		$where[] = "$LogSubject = $subject";
	}
	
	if(count($types) > 0) {
		foreach($types as $type) {
			$temp[] = "$LogType like '$type'";
		}
		$where[] = '('. implode(" OR ", $temp) .')';
	}
	
	if($message != '') {
		$where[] = "($LogMessage like '%$message%' OR $LogMessage like '$message%' OR $LogMessage like '%$message')";
	}
	
	$where[] = "$LogTime BETWEEN $start AND $end";
	
	$sql = "SELECT * FROM $TableLog WHERE ". implode(" AND ", $where) ." LIMIT 0, $aantal";
			
	$result	= mysqli_query($db, $sql);
	if($row	= mysqli_fetch_array($result)) {
		do {
			$Data['id']						= $row[$LogID];
			$Data['tijd']					= $row[$LogTime];
			$Data['type']					= $row[$LogType];
			$Data['dader']				= $row[$LogUser];
			$Data['slachtoffer']	= $row[$LogSubject];
			$Data['melding']			= $row[$LogMessage];
			
			$LogData[] = $Data;
			unset($Data);
		} while($row = mysqli_fetch_array($result));
	}
	
	return $LogData;	
}

function makeOpsomming($array, $first = ',', $last = 'en') {
	if(count($array) > 1) {
		$lastElement = array_pop($array);
		return implode("$first ", $array)." $last ".$lastElement;
	} else {
		return implode("$first ", $array);
	}
}

function excludeID($oldArray, $id) {
	$newArray = array();
	foreach($oldArray as $key => $value) {
		if($key != $id) {
			$newArray[$key] = $value;
		}
	}
	
	return $newArray;
}

function getWijkMembers($wijk) {
	global $TableUsers, $UserStatus, $UserID, $UserWijk, $UserAchternaam;
	$db = connect_db();
	
	$data = array();
	$sql = "SELECT $UserID FROM $TableUsers WHERE $UserStatus = 'actief' AND $UserWijk like '$wijk' ORDER BY $UserAchternaam";
			
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$data[] = $row[$UserID];
		} while($row = mysqli_fetch_array($result));		
	}
	return $data;	
}

function toonDienst($dienst, $gelijk) {
	if($gelijk == 0) {
		return true;
	} else {
		$details = getKerkdienstDetails($dienst);
		$diensten = getKerkdiensten(mktime(0,0,0,date("n", $details['start']),date("j", $details['start']),date("Y", $details['start'])), mktime(23,59,59,date("n", $details['start']),date("j", $details['start']),date("Y", $details['start'])));
		$dagdeel = formatDagdeel($details['start'], false);
		
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

function array_search_closest($input, $array) {
	# http://php.net/manual/en/function.levenshtein.php
	
	if($input != '') {
		// no shortest distance found, yet
		$shortest = -1;
		
		foreach ($array as $id => $word) {
  	  $lev = levenshtein($input, $word);
  	  if ($lev == 0) {
  	      // closest word is this one (exact match)
  	      $closest = $id;
  	      $shortest = 0;
  	
  	      // break out of the loop; we've found an exact match
  	      break;
  	  }
  	
  	  if ($lev <= $shortest || $shortest < 0) {
  	      // set the closest match, and shortest distance
  	      $closest  = $id;
  	      $shortest = $lev;
  	  }
  	}
  } else {
  	$closest = 0;
  }
  
  return $closest;
}

function isValidHash($hash) {
	global $TableUsers, $UserID, $UserHashLong;
	
	$db = connect_db();
	
	$sql = "SELECT $UserID FROM $TableUsers WHERE $UserHashLong like '$hash'";
	$result	= mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 0) {
		return false;
	} else {
		$row = mysqli_fetch_array($result);
		return $row[$UserID];
	}
}

function updateRoosterOpmerking($rooster, $dienst, $opmerking) {
	global $TableRoosOpm, $RoosOpmRoos, $RoosOpmDienst, $RoosOpmOpmerking;
	
	$db = connect_db();	
	$sql = "SELECT * FROM $TableRoosOpm WHERE $RoosOpmRoos = $rooster AND $RoosOpmDienst = $dienst";
	$result	= mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 0) {
		$sql = "INSERT INTO $TableRoosOpm ($RoosOpmRoos, $RoosOpmDienst, $RoosOpmOpmerking) VALUES ($rooster, $dienst, '". urldecode($opmerking) ."')";
	} else {
		$sql = "UPDATE $TableRoosOpm SET $RoosOpmOpmerking = '". urldecode($opmerking) ."' WHERE $RoosOpmRoos = $rooster AND $RoosOpmDienst = $dienst";
	}	

	return mysqli_query($db, $sql);
}

function updateRoosterText($rooster, $dienst, $invulling) {
	global $TablePlanningTxt, $PlanningTxTDienst, $PlanningTxTGroup, $PlanningTxTText;
	
	$db = connect_db();	
	$sql = "SELECT * FROM $TablePlanningTxt WHERE $PlanningTxTGroup = $rooster AND $PlanningTxTDienst = $dienst";
	$result	= mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 0) {
		$sql = "INSERT INTO $TablePlanningTxt ($PlanningTxTGroup, $PlanningTxTDienst, $PlanningTxTText) VALUES ($rooster, $dienst, '". urldecode($invulling) ."')";
	} else {
		$sql = "UPDATE $TablePlanningTxt SET $PlanningTxTText = '". urldecode($invulling) ."' WHERE $PlanningTxTGroup = $rooster AND $PlanningTxTDienst = $dienst";
	}	

	return mysqli_query($db, $sql);
}


function getRoosterOpmerking($rooster, $dienst) {
	global $TableRoosOpm, $RoosOpmRoos, $RoosOpmDienst, $RoosOpmOpmerking;
	
	$db = connect_db();	
	$sql = "SELECT * FROM $TableRoosOpm WHERE $RoosOpmRoos = $rooster AND $RoosOpmDienst = $dienst";
	$result	= mysqli_query($db, $sql);
	if(mysqli_num_rows($result) != 0) {
		$row = mysqli_fetch_array($result);
		return urldecode($row[$RoosOpmOpmerking]);
	} else {
		return '';
	}
}

function getAgendaItems($user, $tijd) {
	global $TableAgenda, $AgendaID, $AgendaOwner, $AgendaStart;
	
	$db = connect_db();
	if($user != 'all') {
		$where[] = "$AgendaOwner = $user";
	}
	
	$where[] = "$AgendaStart > $tijd";
		
	$sql = "SELECT * FROM $TableAgenda WHERE ". implode(' AND ', $where) ." ORDER BY $AgendaStart";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$ids[] = $row[$AgendaID];
		} while($row = mysqli_fetch_array($result));
	}
	
	return $ids;
}

function getAgendaDetails($id) {
	global $TableAgenda, $AgendaID, $AgendaStart, $AgendaEind, $AgendaTitel, $AgendaDescr, $AgendaOwner;
	
	$db = connect_db();
	$sql = "SELECT * FROM $TableAgenda WHERE $AgendaID like $id";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	$data['start']		= $row[$AgendaStart];
	$data['eind']			= $row[$AgendaEind];
	$data['titel']		= urldecode($row[$AgendaTitel]);
	$data['descr']		= urldecode($row[$AgendaDescr]);
	$data['eigenaar']	= $row[$AgendaOwner];
	
	return $data;
}

function getVoorgangers() {
	global $TableVoorganger, $VoorgangerID, $VoorgangerAchter;
	
	$db = connect_db();
	$sql = "SELECT * FROM $TableVoorganger ORDER BY $VoorgangerAchter";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$ids[] = $row[$VoorgangerID];
		} while($row = mysqli_fetch_array($result));
	}
	
	return $ids;
}

function getLiturgie($id) {
	global $DienstLiturgie, $TableDiensten, $DienstID;

	$db = connect_db();
	$sql = "SELECT $DienstLiturgie FROM $TableDiensten WHERE $DienstID = $id";

	$result = mysqli_query($db, $sql);

	if(mysqli_num_rows($result) == 0) {
		return false;
	} else {
		$row = mysqli_fetch_array($result);
		return urldecode($row[$DienstLiturgie]);
	}

	return $result;
}

function getVoorgangerData($id) {
	global $TableVoorganger, $VoorgangerID, $VoorgangerTitel, $VoorgangerVoor, $VoorgangerInit, $VoorgangerTussen, $VoorgangerAchter, $VoorgangerTel, $VoorgangerTel2, $VoorgangerPVNaam, $VoorgangerPVTel, $VoorgangerMail, $VoorgangerPlaats, $VoorgangerDenom, $VoorgangerOpmerking, $VoorgangerAandacht, $VoorgangerDeclaratie, $VoorgangerLastAandacht, $VoorgangerStijl, $VoorgangerLastSeen;
	
	$db = connect_db();
	$sql = "SELECT * FROM $TableVoorganger WHERE $VoorgangerID = $id";

	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	$data['titel'] = $row[$VoorgangerTitel];		
	$data['init'] = $row[$VoorgangerInit];
	$data['voor'] = $row[$VoorgangerVoor];	
	$data['tussen'] = $row[$VoorgangerTussen];
	$data['achter'] = $row[$VoorgangerAchter];
	$data['tel'] = $row[$VoorgangerTel];
	$data['mail'] = $row[$VoorgangerMail];	
	$data['plaats'] = $row[$VoorgangerPlaats];
	$data['denom'] = $row[$VoorgangerDenom];
	$data['tel2'] = $row[$VoorgangerTel2];
	$data['pv_naam'] = $row[$VoorgangerPVNaam];
	$data['pv_tel'] = $row[$VoorgangerPVTel];		
	$data['stijl'] = $row[$VoorgangerStijl];		
	$data['opm'] = $row[$VoorgangerOpmerking];
	$data['aandacht'] = $row[$VoorgangerAandacht];
	$data['declaratie'] = $row[$VoorgangerDeclaratie];
	$data['last_aandacht'] = $row[$VoorgangerLastAandacht];
	$data['last_voorgaan'] = $row[$VoorgangerLastSeen];
		
	return $data;
}


function getDeclaratieData($voorganger, $tijdstip) {
	global $TableVoorganger, $VoorgangerID, $VoorgangerHonorarium, $VoorgangerHonorariumOld, $VoorgangerHonorariumNew, $VoorgangerHonorariumSpecial, $VoorgangerKM, $VoorgangerVertrekpunt, $VoorgangerEBRelatie;
	
	# 1577836800 = 1-1-2020 00:00:00
	$grens = 1577836800;
	
	$db = connect_db();
	$sql = "SELECT * FROM $TableVoorganger WHERE $VoorgangerID = $voorganger";

	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
					
	if($tijdstip > $grens) {
		$data['honorarium'] = $row[$VoorgangerHonorariumNew];
	} else {
		$data['honorarium'] = $row[$VoorgangerHonorariumOld];
	}
	
	$data['honorarium_oud'] = $row[$VoorgangerHonorariumOld];
	$data['honorarium_nieuw'] = $row[$VoorgangerHonorariumNew];
	$data['honorarium_spec'] = $row[$VoorgangerHonorariumNew];
	$data['km_vergoeding'] = $row[$VoorgangerKM];
	$data['reis_van'] = urldecode($row[$VoorgangerVertrekpunt]);
	$data['EB-relatie'] = $row[$VoorgangerEBRelatie];	
	
	return $data;
}

function setLastAandachtspunten($id) {
	global $TableVoorganger, $VoorgangerLastAandacht, $VoorgangerID;
	
	$db = connect_db();
	$sql = "UPDATE $TableVoorganger SET $VoorgangerLastAandacht = ". time() ." WHERE $VoorgangerID = $id";
	mysqli_query($db, $sql);
}

function setVoorgangerLastSeen($id, $tijd) {
	global $TableVoorganger, $VoorgangerLastSeen, $VoorgangerID;
	
	$db = connect_db();
	$sql = "UPDATE $TableVoorganger SET $VoorgangerLastSeen = $tijd WHERE $VoorgangerID = $id";
	mysqli_query($db, $sql);
}

function getMailAdres($user, $formeel = false) {
	# initialiseren
	$returnAdres = '';
	
	# Data opvragen van de ontvanger
	$gebruikersData = getMemberDetails($user);
	
	# Zoek het juiste mailadres op
	if($formeel AND $gebruikersData['form_mail'] != '') {
		$returnAdres = $gebruikersData['form_mail'];
	} elseif($gebruikersData['mail'] == ''){
		$ouders = getParents($user);
		
		foreach($ouders as $ouder) {
			$ouderData = getMemberDetails($ouder);			
			if($ouderData['mail'] != '' AND $returnAdres == '')	$returnAdres = $ouderData['mail'];
		}
	} else {
		$returnAdres = $gebruikersData['mail'];
	}
	
	return $returnAdres;
}

function getLogMembers($start, $end) {
	global $db, $TableLog, $LogTime, $LogUser, $LogSubject;
	
	$sql = "SELECT $LogUser, $LogSubject FROM $TableLog WHERE $LogTime BETWEEN $start AND $end";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	$export = array();
	
	do {
		$dader = $row[$LogUser];
		$slachtoffer = $row[$LogSubject];
		
		if(!array_key_exists($dader, $export) AND $dader != 0)							$export[$dader] = makeName($dader, 8);		
		if(!array_key_exists($slachtoffer, $export) AND $slachtoffer != 0)	$export[$slachtoffer] = makeName($slachtoffer, 8);
		
	} while($row = mysqli_fetch_array($result));
	
	asort($export);
	
	return array_keys($export);
}

function getWijkteamLeden($wijk) {
	global $TableWijkteam, $WijkteamWijk, $WijkteamLid, $WijkteamRol, $db;
	
	$leden = array();
	
	$sql = "SELECT * FROM $TableWijkteam WHERE $WijkteamWijk like '$wijk' ORDER BY $WijkteamRol";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)){
		do {
			$lid = $row[$WijkteamLid];
			$leden[$lid] = $row[$WijkteamRol];
		} while($row = mysqli_fetch_array($result));
	}
	
	return $leden;	
}

function formatPrice($price, $euro = true) {
	$input = $price/100;
	
	if($euro) {
		return "&euro;&nbsp;". number_format($input, 2,',','.');
	} else {
		return number_format($input, 2,',','.');
	}
}


function formatDagdeel($start, $dienst = true) {
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

function makeVoorgangerName($id, $type) {
	# type = 1 : C.M. van den Berg
	# type = 2 : ds. van den Berg
	# type = 3 : ds. C.M. van den Berg
	# type = 4 : Catharinus van den Berg -> C.M. van den Berg (bij ontbreken voornaam)
	# type = 5 : Catharinus -> ds. van den Berg (bij ontbreken voornaam)	
	# type = 6 : Berg; van der, C.M.
	# type = 7 : van den Berg
	
	
	$voorgangerData = getVoorgangerData($id);
	
	# Achternaam	
	if($voorgangerData['tussen'] != '') {
		$voorgangerAchterNaam = lcfirst($voorgangerData['tussen']).' '. $voorgangerData['achter'];
		$voorgangerAchterNaamABC = $voorgangerData['achter'] .'; '. lcfirst($voorgangerData['tussen']);
	} else {
		$voorgangerAchterNaam = $voorgangerData['achter'];
		$voorgangerAchterNaamABC = $voorgangerData['achter'];
	}
	
	# Voornaam
	if($voorgangerData['voor'] != "") {
		$voornaam = $voorgangerData['voor'];
	}
	
	if($type == 7) {
		return $voorgangerAchterNaam;
	}	
	
	if($type == 6) {
		return $voorgangerAchterNaamABC.', '.$voorgangerData['init'];
	}		
		
	if($type == 5 AND isset($voornaam)) {
		return $voornaam;
	} elseif($type == 5 AND !isset($voornaam)) {
		$type = 2;
	}
	
	if($type == 4 AND isset($voornaam)) {
		return $voornaam .' '.$voorgangerAchterNaam;
	} elseif($type == 4 AND !isset($voornaam)) {
		$type = 1;
	}
		
	if($type == 3) {
		return lcfirst($voorgangerData['titel']).' '.$voorgangerData['init'].' '.$voorgangerAchterNaam;
	}
		
	if($type == 2) {
		return lcfirst($voorgangerData['titel']).' '.$voorgangerAchterNaam;
	}
	
	if($type == 1) {
		return $voorgangerData['init'].' '.$voorgangerAchterNaam;
	}
}

function generateDeclaratieLink($dienst, $voorganger) {
	global $randomCodeDeclaratie, $ScriptURL;
	
	# Declaratielink genereren
	$hash = urlencode(password_hash($dienst.'$'.$randomCodeDeclaratie.'$'.$voorganger, PASSWORD_BCRYPT));
	$declaratieLink = $ScriptURL ."declaratie/gastpredikant.php?hash=$hash&d=$dienst&v=$voorganger";
	
	return $declaratieLink;
}

function setVoorgangerDeclaratieStatus($status, $dienst) {
	global $db, $TableDiensten, $DienstDeclStatus, $DienstID;
	
	$descr[0] = 'geen';
	$descr[1] = 'open';
	$descr[2] = 'link verstuurd';
	$descr[3] = 'link bezocht';
	$descr[4] = 'opgeslagen';
	$descr[5] = 'bij CluCo';
	$descr[6] = 'bij lid';
	$descr[7] = 'afgekeurd';
	$descr[8] = 'afgerond';
	$descr[9] = 'afgezien';
	
	$sql = "UPDATE $TableDiensten SET $DienstDeclStatus = $status WHERE $DienstID = $dienst";
	
	if(mysqli_query($db, $sql)) {
		toLog('debug', '', '', "Declaratie-status van dienst $dienst veranderd in ". $descr[$status]);
		return true;
	} else {
		toLog('error', '', '', "Aanpassen van declaratie-status van dienst $dienst naar ". $descr[$status] ." is mislukt");
		return false;
	}	
}

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

function determineAddressDistance($start, $end) {
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

function generateBoekstukNr($jaar) {
	global $db, $TableEBBoekstuk, $EBBoekstukJaar, $EBBoekstukVolgNr;
	
	$sql_jaar = "SELECT $EBBoekstukVolgNr FROM $TableEBBoekstuk WHERE $EBBoekstukJaar = $jaar";
	$result_jaar = mysqli_query($db, $sql_jaar);
	if($row_jaar = mysqli_fetch_array($result_jaar)){
		$volgnummer = $row_jaar[$EBBoekstukVolgNr]+1;
		
		$sql = "UPDATE $TableEBBoekstuk SET $EBBoekstukVolgNr = $volgnummer WHERE $EBBoekstukJaar = $jaar";
	} else {
		$volgnummer = 1;
		$sql = "INSERT INTO $TableEBBoekstuk ($EBBoekstukJaar, $EBBoekstukVolgNr) VALUES ($jaar, $volgnummer)";
	}
	
	mysqli_query($db, $sql);
	
	return $jaar.substr('0000'.$volgnummer, -4);
}

?>
