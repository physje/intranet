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
	global $TableUsers, $UserUsername, $db;
		
	$sql = "SELECT * FROM $TableUsers WHERE $UserUsername like '". trim($username )."'";
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
	global $TableDiensten, $DienstID, $DienstEind, $db;	
		
	if($fromNow) {
		$startTijd = time();
	} else {
		$startTijd = time() - (31*24*60*60);
	}
	
	$eindTijd = mktime(0,0,0,1,1,date('Y')+5);
	
	return getKerkdiensten($startTijd, $eindTijd);
}



function getKerkdiensten($startTijd, $eindTijd) {
	global $TableDiensten, $DienstID, $DienstActive, $DienstEind, $db;
	
	$id = array();
				
	$sql = "SELECT $DienstID FROM $TableDiensten WHERE $DienstActive = '1' AND $DienstEind BETWEEN $startTijd AND $eindTijd ORDER BY $DienstEind ASC";
		
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$id[] = $row[$DienstID];
		} while($row = mysqli_fetch_array($result));
	}
	return $id;
}


function getKerkdienstDetails($id) {
	global $TableDiensten, $DienstID, $DienstStart, $DienstEind, $DienstVoorganger, $DienstCollecte_1, $DienstCollecte_2, $DienstOpmerking, $DienstRuiling, $DienstSpeciaal, $DienstLiturgie, $db;
		
	$data = $voorgangerData = array();
	
	$sql = "SELECT * FROM $TableDiensten WHERE $DienstID = $id";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {			
		$data['start']					= $row[$DienstStart];
		$data['eind']						= $row[$DienstEind];
		$data['collecte_1']			= urldecode($row[$DienstCollecte_1]);
		$data['collecte_2']			= urldecode($row[$DienstCollecte_2]);
		$data['bijzonderheden']	= urldecode($row[$DienstOpmerking]);
		$voorganger							= $row[$DienstVoorganger];
						
		if($voorganger != 0) {			
			$voorgangerData = getVoorgangerData($voorganger);
			
			$data['voorganger_id']	= $voorganger;
			$data['voorganger']			= strtolower($voorgangerData['titel']).' '.$voorgangerData['init'].' '.($voorgangerData['tussen'] == '' ? '' : $voorgangerData['tussen'].' ').$voorgangerData['achter'];
			if(strtolower($voorgangerData['plaats']) != 'deventer' AND $voorgangerData['plaats'] != '')	$data['voorganger'] .= ' ('.$voorgangerData['plaats'].')';
			$data['voorganger']			= trim($data['voorganger']);
		} else {
			$data['voorganger_id']	= $voorganger;
			#$data['voorganger']			= 'onbekend';
			$data['voorganger']			= '';
		}
				
		$data['ruiling']				= $row[$DienstRuiling];
		$data['speciaal']				= $row[$DienstSpeciaal];
		$data['liturgie']       = urldecode($row[$DienstLiturgie]);
	}
	
	return $data;
}

function getMembers($type = 'all') {
	global $TableUsers, $UserStatus, $UserID, $UserAdres, $UserGeboorte, $UserAchternaam, $UserRelatie, $db;	
		
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
	global $TableGrpUsr, $GrpUsrGroup, $GrpUsrUser, $TableUsers, $UserID, $UserAchternaam, $db;
		
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
	$UserAchternaam, $UserMeisjesnaam, $UserUsername, $UserHashShort, $UserGeboorte, $UserTelefoon, $UserMail, $UserEBRelatie,
	$UserFormeelMail, $UserBelijdenis, $UserLastChange, $UserLastVisit, $UserBurgelijk, $UserRelatie, $UserStraat, $UserHuisnummer,
	$UserHuisletter, $UserToevoeging, $UserPC, $UserPlaats, $UserWijk, $UserHashLong, $UserVestiging, $UserLastChange, $UserLastVisit, $db;
		
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
	$data['jaar']						= substr($row[$UserGeboorte], 0, 4);
	$data['maand']					= substr($row[$UserGeboorte], 5, 2);
	$data['dag']						= substr($row[$UserGeboorte], 8, 2);		
	$data['geb_unix']				= mktime(0,0,0,$data['maand'],$data['dag'],$data['jaar']);
	$data['geboorte']				= substr($row[$UserGeboorte], 0, 8).'01';
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
	$data['eb_code']				= $row[$UserEBRelatie];
	$data['vestiging']			= $row[$UserVestiging];
	$data['change']					= $row[$UserLastChange];
	$data['visit']					= $row[$UserLastVisit];
	
	return $data;
}

function getAllGroups() {
	global $TableGroups, $GroupID, $GroupNaam, $db;
	
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
	global $TableGrpUsr, $GrpUsrGroup, $GrpUsrUser, $GroupNaam, $db;	
		
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
	global $TableGroups, $TableGrpUsr, $GroupBeheer, $GrpUsrGroup, $GrpUsrUser, $GroupID, $GroupNaam, $db;
	
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
	global $TableGroups, $GroupID, $GroupNaam, $GroupHTMLIn, $GroupHTMLEx, $GroupShowIn, $GroupShowEx, $GroupBeheer, $GroupMCTag, $db;
		
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
	global $TableGroups, $GroupID, $GroupMCTag, $db;
	
	$sql = "SELECT * FROM $TableGroups WHERE $GroupMCTag = '$tag'";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		return $row[$GroupID];
	} else {
		return false;
	}
}


function getRoosters($id = 0) {
	global $TableRoosters, $RoostersID, $RoostersNaam, $RoostersGroep, $TableGrpUsr, $GrpUsrGroup, $GrpUsrUser, $db;
	
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
	global $TableRoosters, $TableGroups, $TableGrpUsr, $RoostersID, $RoostersBeheerder, $RoostersPlanner, $GroupID, $GrpUsrGroup, $GrpUsrUser, $db;	
	
	$data = array();	
			
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
	global $TableRoosters, $RoostersID, $RoostersNaam, $RoostersBeheerder, $RoostersGroep, $RoostersPlanner, $RoostersFields, $RoostersReminder, $RoostersMail, $RoostersSubject, $RoostersFrom, $RoostersFromAddr, $RoostersGelijk, $RoostersTextOnly, $RoostersOuderCC, $RoostersPartnerTo, $RoostersAlert, $RoostersOpmerking, $RoostersVoorganger, $db;
	
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
		$data['showVoorganger']	= $row[$RoostersVoorganger];
		$data['ouderMail']	= $row[$RoostersOuderCC];
		$data['partnerMail']	= $row[$RoostersPartnerTo];
		
	}
	return $data;	
}

function getBeheerder($groep) {
	global $TableGroups, $GroupID, $GroupBeheer, $db;
	
	$sql = "SELECT $GroupBeheer FROM $TableGroups WHERE $GroupID = $groep";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	if($row[$GroupBeheer] != 0) {
		return $row[$GroupBeheer];
	} else {
		return false;
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

function updateGebedkalItemById($id, $categorie, $contactpersoon, $mailadres, $opmerking) {
	global $GebedsKalId, $TableGebedKalMailOverzicht, $GebedKalCategorie, $GebedKalContactPersoon, $GebedKalMailadres, $GebedKalOpmerkingen, $db;

	$sql = "UPDATE $TableGebedKalMailOverzicht SET $GebedKalCategorie='$categorie', $GebedKalContactPersoon='$contactpersoon', $GebedKalMailadres='$mailadres', $GebedKalOpmerkingen='$opmerking' WHERE $GebedsKalId=$id";
				
	if(mysqli_query($db, $sql)) {
		return true;
	} else {
		return false;
	}
}

function addGebedkalItem($categorie, $contactpersoon, $mailadres, $opmerking) {
	global $TableGebedKalMailOverzicht, $GebedKalCategorie, $GebedKalContactPersoon, $GebedKalMailadres, $GebedKalOpmerkingen, $db;

	$sql = "INSERT INTO $TableGebedKalMailOverzicht ($GebedKalCategorie, $GebedKalContactPersoon, $GebedKalMailadres, $GebedKalOpmerkingen) VALUES ('$categorie', '$contactpersoon', '$mailadres', '$opmerking')";
				
	if(mysqli_query($db, $sql)) {
		return true;
	} else {
		return false;
	}
}

function getGebedkalItemById($id) {
	global $TableGebedKalMailOverzicht, $GebedsKalId, $GebedKalCategorie, $GebedKalContactPersoon, $GebedKalMailadres, $GebedKalOpmerkingen, $db;

	$data = array();	
			
	$sql = "SELECT * FROM $TableGebedKalMailOverzicht WHERE $GebedsKalId = $id";
	$result = mysqli_query($db, $sql);

	if($row = mysqli_fetch_array($result)) {		
		$data['categorie']	= $row[$GebedKalCategorie];
		$data['contactpersoon']	= $row[$GebedKalContactPersoon];		
		$data['mailadres']	= $row[$GebedKalMailadres];
		$data['opmerking']	= $row[$GebedKalOpmerkingen];		
	}
	return $data;	
}

function getGebedkalAllItems() {
	global $TableGebedKalMailOverzicht, $GebedsKalId, $GebedKalCategorie, $GebedKalContactPersoon, $GebedKalMailadres, $GebedKalOpmerkingen, $db;

	$data = array();
			
	$sql = "SELECT * FROM $TableGebedKalMailOverzicht ORDER BY $GebedKalCategorie";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$data[] = $row;
		} while($row = mysqli_fetch_array($result));		
	}
	return $data;	
}

function removeGebedkalItem($ID) {
	global $TableGebedKalMailOverzicht, $GebedsKalId, $db;
	
	$sql = "DELETE FROM $TableGebedKalMailOverzicht WHERE $GebedsKalId = '$ID'";
	if(mysqli_query($db, $sql)) {
		return true;
	} else {
		return false;
	}
}

function addGroupLid($lidID, $commID) {
	global $TableGrpUsr, $GrpUsrGroup, $GrpUsrUser, $db;
	
	$sql = "INSERT INTO $TableGrpUsr ($GrpUsrGroup, $GrpUsrUser) VALUES ($commID, $lidID)";
	if(mysqli_query($db, $sql)) {
		return true;
	} else {
		return false;
	}
}

function removeGroupLeden($commID) {
	global $TableGrpUsr, $GrpUsrGroup, $db;
	
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
	global $db;
	
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
	global $TablePlanning, $PlanningDienst, $PlanningGroup, $PlanningUser, $PlanningPositie, $db;
	
	$sql = "INSERT INTO $TablePlanning ($PlanningDienst, $PlanningGroup, $PlanningPositie, $PlanningUser) VALUES ($dienst, $rooster, $positie, $persoon)";
	if(mysqli_query($db, $sql)) {
		return true;
	} else {
		return false;
	}
}

function getRoosterVulling($rooster, $dienst) {
	global $TablePlanning, $PlanningDienst, $PlanningGroup, $PlanningUser, $PlanningPositie;
	global $TablePlanningTxt, $PlanningTxTDienst, $PlanningTxTGroup, $PlanningTxTText, $db;
	
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
		$data = '';
		
		$sql = "SELECT $PlanningTxTText FROM $TablePlanningTxt WHERE $PlanningTxTDienst = $dienst AND $PlanningTxTGroup = $rooster";
		$result = mysqli_query($db, $sql);
		if($row = mysqli_fetch_array($result)) {
			$data = $row[$PlanningTxTText];
		}
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

function seniorJunior($id) {
	global $TableUsers, $UserID, $UserVoornaam, $UserTussenvoegsel, $UserAchternaam, $UserMeisjesnaam, $UserGeboorte;
	
	$hoofdpersoon = getMemberDetails($id);
	$voor			= $hoofdpersoon['voornaam'];
	$tussen		= $hoofdpersoon['tussenvoegsel'];
	$achter		= $hoofdpersoon['achternaam'];
	$meisje		= $hoofdpersoon['meisjesnaam'];
				
	# Als ik de global $db gebruik gaat het niet goed met speciale karakters
	# daarom initialiseer ik een nieuwe
	$db = connect_db();
	
	$sql = "SELECT * FROM $TableUsers WHERE	$UserID NOT LIKE $id AND $UserVoornaam like '$voor' AND $UserTussenvoegsel like '$tussen' AND $UserAchternaam like '$achter' AND $UserMeisjesnaam like '$meisje'";
	$result = mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 0) {
		return '';
	} else {
		$geboorte = array();
		$geboorte[$id] = $hoofdpersoon['geb_unix'];
		
		$row = mysqli_fetch_array($result);
		
		do {
			$anderID = $row[$UserID];
			$anderData = getMemberDetails($anderID);
			$geboorte[$anderID] = $anderData['geb_unix'];			
		} while($row = mysqli_fetch_array($result));
		
		asort($geboorte, SORT_NATURAL);
		
		$aantal = count($geboorte);
		$i = 0;
				
		foreach($geboorte as $key => $value) {
			if($key == $id) $pos = $i;
			$i++;
		}
		
		if($aantal == 2) {
			if($pos == 0)	return ' sr.';
			if($pos == 1)	return ' jr.';
		} elseif($aantal == 3) {
			if($pos == 0)	return ' sr.';
			if($pos == 1)	return ' mr.';
			if($pos == 2)	return ' jr.';
		}		
	}	
}


function makeName($id, $type) {
	global $TableUsers, $UserID, $UserVoorletters, $UserVoornaam, $UserTussenvoegsel, $UserAchternaam, $UserMeisjesnaam;
	
	# Als ik de global $db gebruik gaat het niet goed met speciale karakters
	# daarom initialiseer ik een nieuwe
	$db = connect_db();
	
	$sql = "SELECT * FROM $TableUsers WHERE $UserID = $id";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	$voorletters = $voornaam = $tussen = $achter = $achter_m = '';
	
	if(isset($row[$UserVoorletters]) AND $row[$UserVoorletters] != '') {
		$voorletters = $row[$UserVoorletters];
	}
	
	if(isset($row[$UserVoornaam]) AND $row[$UserVoornaam] != '') {
		$voornaam	= ucfirst($row[$UserVoornaam]);		
	} else {
		$voornaam = $voorletters;
	}
	
	if(isset($row[$UserTussenvoegsel]) AND $row[$UserTussenvoegsel] != '') {
		$tussen 	= strtolower($row[$UserTussenvoegsel]);
	}
	
	if(isset($row[$UserAchternaam]) AND $row[$UserAchternaam] != '') {
		$achter 	= ucfirst($row[$UserAchternaam]);
	}
	
	if(isset($row[$UserMeisjesnaam]) AND $row[$UserMeisjesnaam] != '') {
		$achter_m = $row[$UserMeisjesnaam];
	}
	
	# 1 = voornaam																		Alberdien
	# 2 = korte achternaam														Jong
	# 3 = volledige achternaam (man)									de Jong
	# 4 = volledige achternaam (vrouw)								de Jong-van Ginkel
	# 5 = voornaam achternaam (man)										Alberdien de Jong
	# 6 = voornaam achternaam (vrouw)									Alberdien de Jong-van Ginkel
	# 7 = voornaam achternaam (vrouw)									Alberdien van Ginkel	
	# 8 = achternaam, voornaam												Jong; de, Alberdien
	# 9 = voorletters achternaam (man)								A. de Jong
	# 10 = voorletters achternaam (vrouw)							A. de Jong-van Ginkel
	# 11 = voorletters achternaam (vrouw)							A. van Ginkel
	# 12 = voorletters achternaam (man)								A. (Alberdien) de Jong
	# 13 = voorletters achternaam (vrouw)							A. (Alberdien) de Jong-van Ginkel
	# 14 = voorletters achternaam (vrouw)							A. (Alberdien) van Ginkel
	# 15 = volledige achternaam, voornaam (vrouw)			Jong-van Ginkel; de, Alberdien
	# 16 = volledige achternaam, voorletters (vrouw)	Jong-van Ginkel; de, A.
	
	if($achter_m != '' AND ($type == 4 OR $type == 6 OR $type == 10 OR $type == 13 OR $type == 15 OR $type == 16)) {
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
		} elseif($type == 8 OR $type == 15 OR $type == 16) {
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
	} elseif($type == 15) {
		return urldecode($achternaam.', '. $voornaam);
	} elseif($type == 16) {
		return urldecode($achternaam.', '. $voorletters);
	}
}


function getWoonAdres($id) {
	global $TableUsers, $UserID, $UserStraat, $UserHuisnummer, $UserHuisletter, $UserToevoeging, $db;
	
	$sql = "SELECT * FROM $TableUsers WHERE $UserID = $id";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	return $row[$UserStraat].' '.$row[$UserHuisnummer].($row[$UserHuisletter] != '' ? ' '.$row[$UserHuisletter] : '').($row[$UserToevoeging] != '' ? ' '.$row[$UserToevoeging] : '');	
}

function sendMail_new($parameter) {
	global $ScriptURL, $noReplyAdress, $ScriptTitle, $SubjectPrefix;
	#global $MailHeader, $MailFooter;
	global $newMailHeader, $newMailFooter;
	global $SMTPHost, $SMTPPort, $SMTPSSL, $SMTPUsername, $SMTPPassword;
	global $db, $TableMail, $MailID, $MailSuccess, $MailTime, $MailMail;
	
	# $parameter['to'][]					= array(adres, naam);
	# $parameter['subject']				= '';
	# $parameter['message'] 			= '';
	# $parameter['formeel'] 			= '';
	# $parameter['ouderCC'] 			= '';
	# $parameter['partnerTo'] 		= '';
	# $parameter['from']					= '';
	# $parameter['fromName']			= '';
	# $parameter['ReplyTo']				= '';
	# $parameter['ReplyToName']		= '';
	# $parameter['cc'][]					= array(adres, naam);
	# $parameter['bcc'][]					= ''
	# $parameter['attachment'][]	= array('file' => '', 'name' => '');
	# $parameter['testen']
	
	# Controleer of er wel een ontvanger bekend is
	if(isset($parameter['to'])) {
		if(!is_array($parameter['to'])) {			
			$ontvangers = array($parameter['to']);
		} else {
			$ontvangers = $parameter['to'];
		}				
	} else {
		echo 'Geen ontvangers bekend';
		toLog('error', '', 'Geen ontvangers bekend');
		return false;
	}
	
	# Controleer of er wel een bericht bekend is
	if(isset($parameter['message'])) {
		$bericht = $parameter['message'];
		toLog('debug', '', 'bericht toegevoegd (lengte '. strlen($bericht) .')');
	} else {
		echo 'Geen bericht bekend';
		toLog('error', '', 'Geen bericht bekend');
		return false;
	}	
	
	# Controleer of er wel een onderwerp bekend is
	if(isset($parameter['subject'])) {
		$subject = $parameter['subject'];
		toLog('debug', '', 'onderwerp toegevoegd (lengte '. strlen($subject) .')');
	} else {
		echo 'Geen onderwerp bekend';
		toLog('error', '', 'Geen onderwerp bekend');
		return false;
	}	
	
	# Er staat ook een formeel mailadres in de database
	# Met de variabele formeel kan worden aangegeven of deze gebruikt moet worden
	if(isset($parameter['formeel'])) {
		$formeel = $parameter['formeel'];
		toLog('debug', '', 'formeel = '. $formeel);
	} else {
		$formeel = false;
		toLog('debug', '', 'geen formeel adres');
	}
	
	# Even checken of ouders in de CC moeten
	if(!isset($parameter['ouderCC'])) {
		$ouderCC = false;
		toLog('debug', '', 'ouders niet in de CC');
	} else {
		$ouderCC = $parameter['ouderCC'];
		toLog('debug', '', 'ouders in de CC = '. $ouderCC);
	}

	# Even checken of de partner in de Aan moeten
	if(!isset($parameter['partnerTo'])) {
		$partnerTo = false;
		toLog('debug', '', 'partner niet in de Aan');
	} else {
		$partnerTo = true;		
		toLog('debug', '', 'partner in de Aan');
	}	
		
	$mail = new PHPMailer\PHPMailer\PHPMailer;
	
	# Zet de charset juist voor de 'gekke' tekens als ô en ë
	$mail->CharSet = 'utf8mb4_unicode_ci';
			
	if(isset($parameter['from']) AND $parameter['from'] != '') {
		$mail->From = $parameter['from'];
		toLog('debug', '', 'Afzenderadres is gezet op '. $parameter['from']);
	} else {
		$mail->From = $noReplyAdress;
		toLog('debug', '', 'Afzenderadres is gezet op '. $noReplyAdress);
	}
	
	if(isset($parameter['fromName']) AND $parameter['fromName'] != '') {
		$mail->FromName = $parameter['fromName'];
		toLog('debug', '', 'Afzendernaam is gezet op '. $parameter['fromName']);
	} else {
		$mail->FromName = $ScriptTitle;
		toLog('debug', '', 'Afzendernaam is gezet op '. $ScriptTitle);
	}
	
	# Als er een reply-adres ingesteld moet worden		
	if(isset($parameter['ReplyTo']) AND $parameter['ReplyTo'] != '') {
		if(isset($parameter['ReplyToName']) AND $parameter['ReplyToName'] != '') {
			$mail->AddReplyTo($parameter['ReplyTo'], $parameter['ReplyToName']);
			toLog('debug', '', 'Reply-adres is gezet op '. $parameter['ReplyToName'] .' ('. $parameter['ReplyTo'] .')');
		} else {
			$mail->AddReplyTo($parameter['ReplyTo']);
			toLog('debug', '', 'Reply-adres is gezet op '. $parameter['ReplyTo']);
		}
	}
	
	# De personen die in de 'Aan' moeten
	# Met de check of ouders in de 'CC' moeten
	foreach($ontvangers as $ontvanger) {		
		if(count($ontvanger) == 1 AND is_numeric($ontvanger[0])) {
			# Haal de data van de ontvanger op
			# Zoek ook direct de mail op van de ontvanger
			$UserData = getMemberDetails($ontvanger[0]);
			$UserMail	= getMailAdres($ontvanger[0], $formeel);
		
			$mail->AddAddress($UserMail, makeName($ontvanger[0], 5));
			toLog('debug', $ontvanger[0], makeName($ontvanger[0], 5) .' in de Aan opgenomen');
			
			# Als de ouders ook een CC moeten
			# Alleen bij mensen die als relatie 'zoon' of 'dochter' hebben
			if($ouderCC AND ($UserData['relatie'] == 'zoon' OR $UserData['relatie'] == 'dochter')) {
				$ouders = getParents($ontvanger[0]);
				foreach($ouders as $ouder){
					$OuderData = getMemberDetails($ouder);
					if($OuderData['mail'] != $UserMail AND $OuderData['mail'] != '') {
						$mail->AddCC($OuderData['mail']);
						toLog('debug', $ontvanger[0], makeName($ouder, 5) .' ('. $OuderData['mail'] .') als ouder in CC opgenomen');
					}
				}
			}
			
			if($partnerTo AND ($UserData['relatie'] == 'partner' OR $UserData['relatie'] == 'levenspartner' OR $UserData['relatie'] == 'gezinshoofd' OR $UserData['relatie'] == 'echtgenote' OR $UserData['relatie'] == 'echtgenoot')) {
				# Het zou niet moeten kunnen
				# maar $partners zou een array van meer dan 1 kunnen worden
				# als dat zo is staat er een error in de logs
				# voor de zekerheid neem ik index = 0
				$partners = getPartner($ontvanger[0]);				
				$partner = $partners[0];
				
				$PartnerData = getMemberDetails($partner);
				if($PartnerData['mail'] != $UserMail AND $PartnerData['mail'] != '') {
					$mail->AddAddress($PartnerData['mail'], makeName($partner, 5));
					toLog('debug', $ontvanger[0], makeName($partner, 5) .' ('. $PartnerData['mail'] .') als partner toegevoegd');
				}				
			}			
		} elseif(count($ontvanger) == 2) {
			$address	= $ontvanger[0];
			$naam			= $ontvanger[1];			
			$mail->AddAddress($address, $naam);
			toLog('debug', '', $naam .' ('. $address .') in de Aan opgenomen');
		} elseif(count($ontvanger) == 1 AND !is_numeric($ontvanger[0])) {
			$mail->AddAddress($ontvanger[0]);
			toLog('debug', '', $ontvanger[0] .' in de Aan opgenomen');
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
			if(count($ontvanger) == 1 AND is_numeric($ontvanger[0])) {
				$UserData = getMemberDetails($ontvanger[0]);
				$UserMail	= getMailAdres($ontvanger[0], $formeel);
				$mail->AddCC($UserMail, makeName($ontvanger[0], 5));
				toLog('debug', '', $ontvanger[0], makeName($ontvanger[0], 5) .' in de CC opgenomen');
			} elseif(count($ontvanger) == 2) {
				$address = $ontvanger[0];
				$naam = $ontvanger[1];				
				$mail->AddCC($address, $naam);
				toLog('debug', $ontvanger[1], $naam .' ('. $address .') in de CC opgenomen');
			} elseif(count($ontvanger) == 1 AND !is_numeric($ontvanger[0])) {
				$mail->AddCC($ontvanger[0]);
				toLog('debug', '', $ontvanger[0] .' in de CC opgenomen');
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
				toLog('debug', $ontvanger, makeName($ontvanger, 5) .' in de BCC opgenomen');
			} else {
				$mail->AddBCC($ontvanger);
				toLog('debug', '', $ontvanger .' in de BCC opgenomen');
			}
		}		
	}
	
	# Controle op bijlages
	if(isset($parameter['file']) AND $parameter['file'] != "") {
		if(isset($parameter['fileName']) AND $parameter['fileName'] != "") {
			$mail->addAttachment($parameter['file'], $parameter['fileName']);
			toLog('debug', '', $parameter['file'] .' is als '. $parameter['fileName'] .' opgenomen als bijlage');
		} else {
			$mail->addAttachment($parameter['file']);
			toLog('debug', '', $parameter['file'] .' is opgenomen als bijlage');
		}
	}
	
	# Controle op bijlages
	if(isset($parameter['attachment']) AND $parameter['attachment'] != "") {
		foreach($parameter['attachment'] as $bijlage) {
			if(is_array($bijlage) AND count($bijlage) > 1) {
				$mail->addAttachment($bijlage['file'], $bijlage['name']);
				toLog('debug', '', $bijlage['file'] .' is als '. $bijlage['name'] .' opgenomen als bijlage');
			} else {
				$mail->addAttachment($bijlage['file']);
				toLog('debug', '', $bijlage['file'] .' is opgenomen als bijlage');
			}
		}
	}
		
	# Bericht opstellen
	#$HTMLMail = $MailHeader.$bericht.$MailFooter;	
	$HTMLMail = $newMailHeader.$bericht.$newMailFooter;
	
	# Door de variabele testen mee te geven wordt er geen mail verstuurd
	# maar wordt de mail alleen op het scherm getoond
	if(!isset($parameter['testen'])) {
		# Onderwerp instellen
		$mail->Subject	= $SubjectPrefix . trim($subject);
		$mail->IsHTML(true);
		$mail->Body			= $HTMLMail;
		
		$mail->isSMTP();
		$mail->Host				= $SMTPHost;
		$mail->Port       = $SMTPPort;
		$mail->SMTPSecure = $SMTPSSL;
		$mail->SMTPAuth   = true;
		$mail->Username		= $SMTPUsername;
		$mail->Password		= $SMTPPassword;
		
		# We schrijven de mail weg in de database (standaard als niet succes-vol verstuurd)
		# Versturen hem
		# Passen in de database aan dat de mail succesvol is verstuurd
		# Op deze manier kunnen we mislukte mails makkelijk opnieuw versturen
		$sql = "INSERT INTO $TableMail ($MailTime, $MailMail) VALUES (". time() .", '". urlencode(json_encode($parameter))."')";
		
		if(!mysqli_query($db, $sql)) {
			toLog('debug', '', 'Problemen met wegschrijven mail');
			return false;
		} elseif(!$mail->Send()) {
			toLog('error', '', 'Problemen met verzenden mail');
			return false;		
		} else {
			$sql = "UPDATE $TableMail SET $MailSuccess = '1' WHERE $MailID = ". mysqli_insert_id($db);
			mysqli_query($db, $sql);
			return true;
		}
	} else {
		#foreach($parameter as $key => $value) echo $key .' -> '. $value .'<br>';
		#echo $HTMLMail;		
		var_dump($mail);
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
	global $TableUsers, $UserAdres, $UserStatus, $UserID, $db;
	
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

function getPartner($id) {	
	
	$partner = array();
	
	$lidData = getMemberDetails($id);
	
	if($lidData['relatie'] != 'zoon' AND $lidData['relatie'] != 'dochter' AND $lidData['relatie'] != 'inw. persoon' AND $lidData['relatie'] != 'zelfstandig') {
		$familie = getFamilieleden($id);		
		
		foreach($familie as $lid) {
			$data = getMemberDetails($lid);
			if($data['relatie'] != 'zoon' AND $lidData['relatie'] != 'inw. persoon' AND $data['relatie'] != 'dochter' AND $id != $data['id'] AND ($data['relatie'] == 'echtgenote' OR $data['relatie'] == 'echtgenoot' OR $data['relatie'] == 'gezinshoofd' OR $data['relatie'] == 'levenspartner' OR $data['relatie'] == 'partner')) {
				$partner[] = $lid;
			}
		}
	}
	
	if(count($partner) == 1) {
		return $partner;
	} elseif(count($partner) > 1) {
		toLog('error', $lid, "Lijkt meer dan 1 partner te hebben");
		return $partner;
	} else {
		return false;
	}
}

function getJarigen($dag, $maand) {
	global $TableUsers, $UserStatus, $UserID, $UserGeboorte, $db;
	
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

/*
function toLog($type, $dader, $slachtoffer, $message) {
	global $db,$TableLog, $LogID, $LogTime, $LogType, $LogUser, $LogSubject, $LogMessage, $db;
 	
	$tijd = time();	
	$sql = "INSERT INTO $TableLog ($LogTime, $LogType, $LogUser, $LogSubject, $LogMessage) VALUES ($tijd, '$type', '$dader', '$slachtoffer', '". addslashes($message) ."')";
	if(!mysqli_query($db, $sql)) {
		echo "log-error : ". $sql;
	}
}
*/

function toLog($type, $slachtoffer, $message) {
	global $db,$TableLog, $LogID, $LogTime, $LogType, $LogUser, $LogDisguised, $LogSubject, $LogMessage, $cookie_lifetime, $db;
 	
 	if($message != '') {
 		session_start(['cookie_lifetime' => $cookie_lifetime]);
 		 		 		
		$tijd = time();
		$sql = "INSERT INTO $TableLog ($LogTime, $LogType, $LogUser, $LogDisguised, $LogSubject, $LogMessage) VALUES ($tijd, '$type', '". (isset($_SESSION['realID']) ? $_SESSION['realID'] : '') ."', '". (isset($_SESSION['fakeID']) ? $_SESSION['fakeID'] : '') ."', '$slachtoffer', '". addslashes($message) ."')";
		if(!mysqli_query($db, $sql)) {
			echo "log-error : ". $sql;
		}
	}
}

function getParam($name, $default = '') {
	return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
}


function getString($start, $end, $string, $offset) {
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

function getLogData($start, $end, $types, $dader, $subject, $message, $aantal) {
	global $db, $TableLog, $LogID, $LogTime, $LogType, $LogUser, $LogDisguised, $LogSubject, $LogMessage;
		
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
			$Data['vermomming']		= $row[$LogDisguised];
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
	global $TableUsers, $UserStatus, $UserID, $UserWijk, $UserAchternaam, $db;

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

function getWijkledenByAdres($wijk, $type = 0) {
	# type = 0, alleen gezinshoofd of zelfstandig
	# type = 1, alle belijdende leden
	# type = 2, alle leden 
	global $TableUsers, $UserStatus, $UserID, $UserAdres, $UserWijk, $UserAchternaam, $UserRelatie, $UserBelijdenis, $db;
	
	$data = array();
	$sql_adressen = "SELECT $UserAdres FROM $TableUsers WHERE $UserStatus = 'actief' AND $UserWijk like '$wijk' GROUP BY $UserAdres ORDER BY $UserAchternaam";
			
	$result_adressen = mysqli_query($db, $sql_adressen);
	if($row_adressen = mysqli_fetch_array($result_adressen)) {
		do {
			$adres = $row_adressen[$UserAdres];
			
			if($type == 0) {
				$sql_leden = "SELECT $UserID FROM $TableUsers WHERE $UserStatus = 'actief' AND $UserAdres like '$adres' AND ($UserRelatie like 'gezinshoofd' OR $UserRelatie like 'zelfstandig') ORDER BY FIELD($UserRelatie,'gezinshoofd') DESC";
			} elseif($type == 1) {
				$sql_leden = "SELECT $UserID FROM $TableUsers WHERE $UserStatus = 'actief' AND $UserAdres like '$adres' AND $UserBelijdenis like 'belijdend lid' ORDER BY FIELD($UserRelatie,'gezinshoofd') DESC";
			} else {
			  $sql_leden = "SELECT $UserID FROM $TableUsers WHERE $UserStatus = 'actief' AND $UserAdres like '$adres' ORDER BY FIELD($UserRelatie,'gezinshoofd') DESC";			  
			}

			$result_leden = mysqli_query($db, $sql_leden);
			$row_leden = mysqli_fetch_array($result_leden);
			
			do {
				$data[$adres][] = $row_leden[$UserID];
			} while($row_leden = mysqli_fetch_array($result_leden));			
		} while($row_adressen = mysqli_fetch_array($result_adressen));		
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
	global $TableUsers, $UserID, $UserHashLong, $db;
	
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
	global $TableRoosOpm, $RoosOpmRoos, $RoosOpmDienst, $RoosOpmOpmerking, $db;
	
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
	global $TablePlanningTxt, $PlanningTxTDienst, $PlanningTxTGroup, $PlanningTxTText, $db;
	
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
	global $TableRoosOpm, $RoosOpmRoos, $RoosOpmDienst, $RoosOpmOpmerking, $db;
	
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
	global $TableAgenda, $AgendaID, $AgendaOwner, $AgendaStart, $db;
	
	$ids = array();
	
	if($user != 'all')	$where[] = "$AgendaOwner = $user";	
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
	global $TableAgenda, $AgendaID, $AgendaStart, $AgendaEind, $AgendaTitel, $AgendaDescr, $AgendaOwner, $db;
	
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

function getVoorgangers($onlyActive = true) {
	global $TableVoorganger, $VoorgangerID, $VoorgangerActive, $VoorgangerAchter, $db;
	
	$sql = "SELECT * FROM $TableVoorganger ". ($onlyActive ? "WHERE $VoorgangerActive = '1' " : '') ."ORDER BY $VoorgangerAchter";
		
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$ids[] = $row[$VoorgangerID];
		} while($row = mysqli_fetch_array($result));
	}
	
	return $ids;
}

function getLiturgie($id) {
	global $DienstLiturgie, $TableDiensten, $DienstID, $db;
	
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
	global $TableVoorganger, $VoorgangerID, $VoorgangerTitel, $VoorgangerVoor, $VoorgangerInit, $VoorgangerTussen, $VoorgangerAchter, $VoorgangerTel, $VoorgangerTel2, $VoorgangerPVNaam, $VoorgangerPVTel, $VoorgangerMail, $VoorgangerPlaats, $VoorgangerDenom, $VoorgangerOpmerking, $VoorgangerAandacht, $VoorgangerDeclaratie, $VoorgangerLastAandacht, $VoorgangerReiskosten, $VoorgangerStijl, $VoorgangerLastSeen, $VoorgangerLastDataCheck, $db;
	
	$data = array();
		
	$sql = "SELECT * FROM $TableVoorganger WHERE $VoorgangerID = $id";

	$result = mysqli_query($db, $sql);
	
	if($row = mysqli_fetch_array($result)) {
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
		$data['reiskosten'] = $row[$VoorgangerReiskosten];
		$data['last_aandacht'] = $row[$VoorgangerLastAandacht];
		$data['last_voorgaan'] = $row[$VoorgangerLastSeen];
		$data['last_check'] = $row[$VoorgangerLastDataCheck];
	}
		
	return $data;
}


#function getDeclaratieData($voorganger, $tijdstip) {
function getDeclaratieData($voorganger, $dienst) {
	global $TableVoorganger, $VoorgangerID, $VoorgangerHonorarium, $VoorgangerHonorariumNew, $VoorgangerHonorarium2023, $VoorgangerHonorariumSpecial, $VoorgangerKM, $VoorgangerVertrekpunt, $VoorgangerEBRelatie, $db;
	
	$sql = "SELECT * FROM $TableVoorganger WHERE $VoorgangerID = $voorganger";

	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
		
	#$data['honorarium_oud'] = $row[$VoorgangerHonorariumOld];
	$data['honorarium_nieuw'] = $row[$VoorgangerHonorariumNew];
	$data['honorarium_2023'] = $row[$VoorgangerHonorarium2023];	
	$data['honorarium_spec'] = $row[$VoorgangerHonorariumSpecial];
	$data['km_vergoeding'] = $row[$VoorgangerKM];
	$data['reis_van'] = urldecode($row[$VoorgangerVertrekpunt]);
	$data['EB-relatie'] = $row[$VoorgangerEBRelatie];	
	
	$dienstData = getKerkdienstDetails($dienst);
	
	$grens = mktime(1,1,1,1,1,2023);
	
	if($dienstData['speciaal'] == 1) {
		$data['honorarium'] = $row[$VoorgangerHonorariumSpecial];
	} elseif($dienstData['start'] < $grens) {
		$data['honorarium'] = $row[$VoorgangerHonorariumNew];
	} else {
		$data['honorarium'] = $row[$VoorgangerHonorarium2023];
	}
	
	return $data;
}

function setLastAandachtspunten($id) {
	global $TableVoorganger, $VoorgangerLastAandacht, $VoorgangerID, $db;
	
	$sql = "UPDATE $TableVoorganger SET $VoorgangerLastAandacht = ". time() ." WHERE $VoorgangerID = $id";
	mysqli_query($db, $sql);
}

function setVoorgangerLastSeen($id, $tijd) {
	global $TableVoorganger, $VoorgangerLastSeen, $VoorgangerID, $db;
	
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

function getPastor($lid) {
	global $TablePastorVerdeling, $PastorVerdelingLid, $PastorVerdelingPastor, $db;
	
	$pastor = 0;
	
	$sql = "SELECT $PastorVerdelingPastor FROM $TablePastorVerdeling WHERE $PastorVerdelingLid = $lid";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)){
		$pastor = $row[$PastorVerdelingPastor];
	}
	
	return $pastor;
}

function getBezoeker($lid) {
	global $TablePastorVerdeling, $PastorVerdelingLid, $PastorVerdelingBezoeker, $db;
	
	$bezoeker = 0;
	
	$sql = "SELECT $PastorVerdelingBezoeker FROM $TablePastorVerdeling WHERE $PastorVerdelingLid = $lid";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)){
		$bezoeker = $row[$PastorVerdelingBezoeker];
	}
	
	return $bezoeker;
}


/*
function getPastoraleBezoeken($lid, $teamlid) {
	global $TablePastoraat, $PastoraatID, $PastoraatLid, $PastoraatIndiener, $PastoraatZichtOud, $PastoraatZichtPas, $PastoraatZichtPred, $PastoraatTijdstip, $db;
	
	$bezoeken = array();
	
	$data			= getMemberDetails($lid);
	$wijk			= $data['wijk'];
	$wijkteam = getWijkteamLeden($wijk);
	$rol			= $wijkteam[$teamlid];
	
	$sql = "SELECT $PastoraatID FROM $TablePastoraat WHERE $PastoraatLid = ". $lid ." AND ($PastoraatIndiener = ". $teamlid;
	
	if($rol == 1 OR $rol == 4 OR $rol == 5 OR $rol == 7) {
		$sql .= " OR ";
			
		# Ouderling
		if($rol == 1) {
			$sql .= "$PastoraatZichtOud = '1'";
		# Bezoekbroeder/zuster
		} elseif($rol == 4 OR $rol == 5) {
			$sql .= "$PastoraatZichtPas = '1'";
		# Predikant
		} elseif($rol == 7) {
			$sql .= "$PastoraatZichtPred = '1'";
		}
	}						
						
	$sql .= ") ORDER BY $PastoraatTijdstip DESC";
	$result = mysqli_query($db, $sql);
	
	if($row = mysqli_fetch_array($result)) {		
		do {
			$bezoeken[] = $row[$PastoraatID];
		} while($row = mysqli_fetch_array($result));
	}
	
	return $bezoeken;	
}
*/

function getPastoraleBezoeken($lid) {
	#global $TablePastoraat, $PastoraatID, $PastoraatLid, $PastoraatIndiener, $PastoraatZichtOud, $PastoraatZichtPas, $PastoraatZichtPred, $PastoraatTijdstip, $db;
	global $TablePastoraat, $PastoraatID, $PastoraatLid, $PastoraatTijdstip, $db;
	
	$bezoeken = array();
			
	$sql = "SELECT $PastoraatID FROM $TablePastoraat WHERE $PastoraatLid = ". $lid ." ORDER BY $PastoraatTijdstip DESC";
	$result = mysqli_query($db, $sql);
	
	if($row = mysqli_fetch_array($result)) {		
		do {
			$bezoeken[] = $row[$PastoraatID];
		} while($row = mysqli_fetch_array($result));
	}
	
	return $bezoeken;	
}


function getPastoraalbezoekDetails($ID) {
	global $TablePastoraat, $PastoraatID, $PastoraatIndiener, $PastoraatTijdstip, $PastoraatLid, $PastoraatType, $PastoraatLocatie, $PastoraatZichtOud, $PastoraatZichtPred, $PastoraatZichtPas, $PastoraatNote, $db;
	
	$sql = "SELECT * FROM $TablePastoraat WHERE $PastoraatID = ". $ID;
	$result = mysqli_query($db, $sql);
	
	if($row = mysqli_fetch_array($result)) {
		$data = array();
		
		$data['indiener'] 	= $row[$PastoraatIndiener];
		$data['datum'] 			= $row[$PastoraatTijdstip];
		$data['lid'] 				= $row[$PastoraatLid];
		$data['type'] 			= $row[$PastoraatType];
		$data['locatie']		= $row[$PastoraatLocatie];
		$data['ouderling']	= $row[$PastoraatZichtOud];
		$data['predikant']	= $row[$PastoraatZichtPred];
		$data['bezoeker'] 	= $row[$PastoraatZichtPas];
		$data['note']				= $row[$PastoraatNote];
	}
	
	return $data;
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
	# type = 8 : Berg; van der, Catharinus -> Berg; van der, C.M. (bij ontbreken voornaam)
	
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
	
	if($type == 8 AND isset($voornaam)) {
		return $voorgangerAchterNaamABC.', '.$voornaam;
	} elseif($type == 8 AND !isset($voornaam)) {
		$type = 6;
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

function generateDeclaratieLink($dienst, $voorganger, $afzeggen = false) {
	global $randomCodeDeclaratie, $ScriptURL;
	
	# Declaratielink genereren
	$hash = urlencode(password_hash($dienst.'$'.$randomCodeDeclaratie.'$'.$voorganger, PASSWORD_BCRYPT));
	$declaratieLink = $ScriptURL ."declaratie/". ($afzeggen ? 'geenDeclaratie.php' : 'gastpredikant.php') ."?hash=$hash&d=$dienst&v=$voorganger";
	
	return $declaratieLink;
}

function setVoorgangerDeclaratieStatus($status, $dienst) {
	global $db, $TableDiensten, $DienstDeclStatus, $DienstID;
	
	$descr[0] = 'geen';							# standaard status bij nieuwe kerkdienst
	$descr[1] = 'open';							# Status wordt op 'open' gezet in mailVoorganger.php (20 dagen van te voren)
	$descr[2] = 'link verstuurd';		# Status wordt op 'link verstuurd' gezet na versturen van link op de dag van voorgaan
	$descr[3] = 'link bezocht';			# Status wordt op 'link bezocht' gezet als de link uit vorige mail bezocht is
	$descr[4] = 'opgeslagen';				# (nog) niet ingebruik
	$descr[5] = 'bij CluCo';				# (nog) niet ingebruik
	$descr[6] = 'bij lid';					# (nog) niet ingebruik
	$descr[7] = 'afgekeurd';				# (nog) niet ingebruik
	$descr[8] = 'afgerond';					# Status wordt op 'afgerond' gezet als declaratie is ingediend
	$descr[9] = 'afgezien';
	
	$sql = "UPDATE $TableDiensten SET $DienstDeclStatus = $status WHERE $DienstID = $dienst";
	
	if(mysqli_query($db, $sql)) {
		toLog('debug', '', "Declaratie-status van dienst $dienst veranderd in ". $descr[$status]);
		return true;
	} else {
		toLog('error', '', "Aanpassen van declaratie-status van dienst $dienst naar ". $descr[$status] ." is mislukt");
		return false;
	}	
}

function getVoorgangerDeclaratieStatus($dienst) {
	global $db, $TableDiensten, $DienstDeclStatus, $DienstID;
	
	$descr[0] = 'geen';							# standaard status bij nieuwe kerkdienst
	$descr[1] = 'open';							# Status wordt op 'open' gezet in mailVoorganger.php (20 dagen van te voren)
	$descr[2] = 'link verstuurd';		# Status wordt op 'link verstuurd' gezet na versturen van link op de dag van voorgaan
	$descr[3] = 'link bezocht';			# Status wordt op 'link bezocht' gezet als de link uit vorige mail bezocht is
	$descr[4] = 'opgeslagen';				# (nog) niet ingebruik
	$descr[5] = 'bij CluCo';				# (nog) niet ingebruik
	$descr[6] = 'bij lid';					# (nog) niet ingebruik
	$descr[7] = 'afgekeurd';				# (nog) niet ingebruik
	$descr[8] = 'afgerond';					# Status wordt op 'afgerond' gezet als declaratie is ingediend
	$descr[9] = 'afgezien';
	
	$sql = "SELECT $DienstDeclStatus FROM $TableDiensten WHERE $DienstID = $dienst";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	return $row[$DienstDeclStatus];
}



function setDeclaratieStatus($status, $declaratie, $lid) {
	global $db, $TableEBDeclaratie, $EBDeclaratieID, $EBDeclaratieStatus;
	
	$descr[0] = 'geen';								# nog niet in gebruik
	$descr[1] = 'opgeslagen'; 				# nog niet in gebruik
	$descr[2] = 'bij lid'; 						# nog niet in gebruik
	$descr[3] = 'bij CluCo';					# Door gemeentelid
	$descr[4] = 'bij penningmeester';	# Door Cluco
	$descr[5] = 'afgerond';						# Door Penningmeester
	$descr[6] = 'afgekeurd';					# Door Cluco
	$descr[7] = 'verwijderd';					# Door Penningmeester
	$descr[8] = 'investering';				# Door Penningmeester	
	
	$sql = "UPDATE $TableEBDeclaratie SET $EBDeclaratieStatus = $status WHERE $EBDeclaratieID = $declaratie";
		
	if(mysqli_query($db, $sql)) {
		toLog('debug', $lid, "Declaratie-status van declaratie $declaratie veranderd in ". $descr[$status]);
		return true;
	} else {
		toLog('error', $lid, "Aanpassen van declaratie-status van declaratie $declaratie naar ". $descr[$status] ." is mislukt");
		return false;
	}	
}

function getDeclaratieStatus($declaratie, $lid) {
	global $db, $TableEBDeclaratie, $EBDeclaratieID, $EBDeclaratieStatus;
	
	$descr[0] = 'geen';								# nog niet in gebruik
	$descr[1] = 'opgeslagen'; 				# nog niet in gebruik
	$descr[2] = 'bij lid'; 						# nog niet in gebruik
	$descr[3] = 'bij CluCo';					# Door gemeentelid
	$descr[4] = 'bij penningmeester';	# Door Cluco
	$descr[5] = 'afgerond';						# Door Penningmeester
	$descr[6] = 'afgekeurd';					# Door Cluco
	$descr[7] = 'verwijderd';					# Door Penningmeester
	$descr[8] = 'investering';				# Door Penningmeester	
	
	$sql = "SELECT $EBDeclaratieStatus FROM $TableEBDeclaratie WHERE $EBDeclaratieID = $declaratie";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	return $row[$EBDeclaratieStatus];
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
	
	if($end == 'Mari�nburghstraat 4, Deventer') {		
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


function getGebedspunten($start, $eind) {
	global $db, $PuntenID, $TablePunten, $PuntenDatum;
	
	$sql = "SELECT $PuntenID FROM $TablePunten WHERE $PuntenDatum BETWEEN '$start' AND '$eind' ORDER BY $PuntenDatum";
	$result = mysqli_query($db, $sql);
	
	$data = array();

	if($row = mysqli_fetch_array($result)) {
		do {
			$data[] = $row[$PuntenID];
		} while($row = mysqli_fetch_array($result));
	}
	
	return $data;
}

function getGebedspunt($ID) {
	global $db, $PuntenID, $TablePunten, $PuntenDatum, $PuntenPunt;
	
	$sql = "SELECT * FROM $TablePunten WHERE $PuntenID = $ID";

	$result = mysqli_query($db, $sql);	
	$row = mysqli_fetch_array($result);
	
	//$tijdArray = strptime($row[$PuntenDatum], "%Y-%m-%d");
	
	$tijdArray["tm_hour"] = 0;
	$tijdArray["tm_min"] = 0;
	$tijdArray["tm_sec"] = 0;
	$tijdArray["tm_mon"] = substr($row[$PuntenDatum], 5, 2);
	$tijdArray["tm_mday"]= substr($row[$PuntenDatum], 8, 2);
	$tijdArray["tm_year"]= substr($row[$PuntenDatum], 0, 4);
	
	$data['id'] = $row[$PuntenID];
	$data['datum'] = $row[$PuntenDatum];
	$data['unix'] = mktime($tijdArray["tm_hour"], $tijdArray["tm_min"], $tijdArray["tm_sec"], $tijdArray["tm_mon"], $tijdArray["tm_mday"], $tijdArray["tm_year"]);
	$data['gebedspunt'] = urldecode($row[$PuntenPunt]);
	
	return $data;
}

function time2str($format, $time = 0) {
	if($time == 0) {
		$time = time();
	}	
	
	// Check for Windows to find and replace the %e
	// modifier correctly
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
		$format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
	}
	return strftime($format, $time);
}

function generateFilename() {
    $s = strtoupper(md5(uniqid(rand(),true)));
    $guidText = substr($s,0,4) . '-'. date('dmyHis').'-'. substr($s,4);
    return $guidText;
}

function getJaargangen() {
	global $db, $TableArchief, $ArchiefJaar;
	
	$sql = "SELECT * FROM $TableArchief GROUP BY $ArchiefJaar ORDER BY $ArchiefJaar DESC";
	$result	= mysqli_query($db, $sql);
	$row		= mysqli_fetch_array($result);
	
	do {
		$Jaargangen[] = $row[$ArchiefJaar];
	} while($row = mysqli_fetch_array($result));
	
	return $Jaargangen;	
}

function getNrInJaargang($jaargang) {
	global $db, $TableArchief, $ArchiefPubDate, $ArchiefJaar, $ArchiefID;
	
	$sql = "SELECT * FROM $TableArchief WHERE $ArchiefJaar = $jaargang ORDER BY $ArchiefPubDate DESC";
	$result	= mysqli_query($db, $sql);
	$row		= mysqli_fetch_array($result);
	
	do {
		$Nummers[] = $row[$ArchiefID];
	} while($row = mysqli_fetch_array($result));
	
	return $Nummers;	
}

function getTrinitasData($id) {
	global $db, $TableArchief, $ArchiefJaar, $ArchiefNr, $ArchiefHash, $ArchiefPubDate, $ArchiefName, $ArchiefID;
	
	$sql = "SELECT * FROM $TableArchief WHERE $ArchiefID like '$id'";
	$result	= mysqli_query($db, $sql);
	$row		= mysqli_fetch_array($result);
		
	$data['id']				= $row[$ArchiefID];
	$data['jaar']			= $row[$ArchiefJaar];
	$data['nr']				= $row[$ArchiefNr];
	$data['hash']			= $row[$ArchiefHash];
	$data['pubDate']	= $row[$ArchiefPubDate];
	$data['filename']	= $row[$ArchiefName];	
	
	return $data;
}

function makeTrinitasName($id, $type) {
	if($id == '') {
		return $id;
	} else {
		$data = getTrinitasData($id);
	
		# 1 = 2015-05-10
		# 2 = 10 mei 2015
		# 3 = nr. 12 - 10 mei 2015
		# 4 = jr. 9; nr. 12 - 10 mei 2015
		# 5 = nr. 02 - 10 mei 2015
		# 6 = jr. 09; nr. 02 - 10 mei 2015
		# 7 = J09N12 - 10 mei 2015
		# 8 = nr. 12
		# 9 = jr. 9; nr. 12
		# 10 = nr. 12
		# 11 = jr. 09; nr. 02
		# 12 = J09N12
		# 13 = J09N12 - 10.05.15
		# 14 = mei 2014
		
		if($type == 1) {
			return time2str("%Y-%m-%d", $data['pubDate']);
		} elseif($type == 2) {
			return time2str("%e %B %Y", $data['pubDate']);
		} elseif($type == 3) {
			return 'nr. '. $data['nr'] .' - '.time2str("%e %B %Y", $data['pubDate']);
		} elseif($type == 4) {
			return 'jr. '. $data['jaar'] .' nr. '. $data['nr'] .' - '.time2str("%e %B %Y", $data['pubDate']);
		} elseif($type == 5) {
			return 'nr. '. substr('0'.$data['nr'], -2) .' - '.time2str("%d %B %Y", $data['pubDate']);
		} elseif($type == 6) {
			return 'jr. '. substr('0'.$data['jaar'], -2) .' nr. '. substr('0'.$data['nr'], -2) .' - '.time2str("%d %B %Y", $data['pubDate']);
		} elseif($type == 7) {
			return 'J'. substr('0'.$data['jaar'], -2) .'N'. substr('0'.$data['nr'], -2) .' - '.time2str("%d %B %Y", $data['pubDate']);
		} elseif($type == 8) {
			return 'nr. '. $data['nr'];
		} elseif($type == 9) {
			return 'jr. '. $data['jaar'] .' nr. '. $data['nr'];
		} elseif($type == 10) {
			return 'nr. '. substr('0'.$data['nr'], -2);
		} elseif($type == 11) {
			return 'jr. '. substr('0'.$data['jaar'], -2) .' nr. '. substr('0'.$data['nr'], -2);
		} elseif($type == 12) {
			return 'J'. substr('0'.$data['jaar'], -2) .'N'. substr('0'.$data['nr'], -2);
		} elseif($type == 13) {
			return 'J'. substr('0'.$data['jaar'], -2) .'N'. substr('0'.$data['nr'], -2) .' - '.time2str("%d.%m.%y", $data['pubDate']);
		} elseif($type == 14) {
			return time2str("%B %Y", $data['pubDate']);
		} else {
			return 'Trinitas';
		}
	}
}

# Functie om text uit een PDF te lezen
# Gejat van http://www.tero.co.uk/scripts/extract-text-from-pdf.php
function ExtractTextFromPdf ($pdfdata) {
	if (strlen ($pdfdata) < 1000 && file_exists ($pdfdata)) $pdfdata = file_get_contents ($pdfdata); //get the data from file
	if (!trim ($pdfdata)) echo "Error: there is no PDF data or file to process.";
	$result = ''; //this will store the results
	//Find all the streams in FlateDecode format (not sure what this is), and then loop through each of them
	if (preg_match_all ('/<<[^>]*FlateDecode[^>]*>>\s*stream(.+)endstream/Uis', $pdfdata, $m)) foreach ($m[1] as $chunk) {
		$chunk = gzuncompress (ltrim ($chunk)); //uncompress the data using the PHP gzuncompress function
		//If there are [] in the data, then extract all stuff within (), or just extract () from the data directly
		$a = preg_match_all ('/\[([^\]]+)\]/', $chunk, $m2) ? $m2[1] : array ($chunk); //get all the stuff within []
		foreach ($a as $subchunk) if (preg_match_all ('/\(([^\)]+)\)/', $subchunk, $m3)) $result .= join ('', $m3[1]); //within ()
	}
	else echo "Error: there is no FlateDecode text in this PDF file that I can process.";
	return $result; //return what was found
}


function getLastNrsTrinitas($number = 3) {
	global $db, $TableArchief, $ArchiefID, $ArchiefPubDate;
	$data = array();
	
	$grens = time() + (24*60*60);
	$sql = "SELECT * FROM $TableArchief WHERE $ArchiefPubDate < $grens ORDER BY $ArchiefPubDate DESC LIMIT 0,$number";
	
	$result	= mysqli_query($db, $sql);
	if($row	= mysqli_fetch_array($result)) {
		do {
			$data[] = $row[$ArchiefID];
		} while($row = mysqli_fetch_array($result));
	}
	
	return $data;	
}

function isValidEmail($email) {
	if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return true;
	}
	return false;
}

function cleanIBAN($iban) {
	$toClean = $iban;
	
	$toClean = trim($toClean);
	$toClean = strtoupper($toClean);
	$toClean = str_replace(' ', '', $toClean);
	$toClean = str_replace('.', '', $toClean);
	
	return $toClean;
}

function validateIBAN($iban) {
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


function price2RightFormat($price) {
	$toClean = $price;
	
	$toClean = trim($toClean);
	$toClean = str_replace(' ', '', $toClean);
	$toClean = str_replace(',', '.', $toClean);
	
	return $toClean;
}

function calculateTotals($array) {
	$totaal = 0;
	
	foreach($array as $waarde) {
		if($waarde > 0) {
			$price = 100*price2RightFormat($waarde);
			$totaal = $totaal + $price;
		}
	}
	
	return $totaal;
}

function showDeclaratieDetails($input) {
	global $clusters, $declJGPost, $declJGKop;
	
	# $input['key']
	# $input['user']
	# $input['iban']
	# $input['relatie']
	# $input['cluster']
	# $input['overige']
	# $input['overig_price']
	# $input['reiskosten']
	
	if(isset($input['key']) AND $input['key'] != '') {
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Declaratie:</td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td colspan='3'>". $input['key'] ."</td>";
		$page[] = "</tr>";
	}
	
	if(isset($input['user']) AND $input['user'] != '') {
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Naam:</td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td colspan='3'>". makeName($input['user'], 5) ."</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Emailadres:</td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td colspan='3'>". getMailAdres($input['user']) ."</td>";
		$page[] = "</tr>";
	}
	
	if($input['eigen'] == 'Ja') {	
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Rekeningnummer:</td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td colspan='3'>". $input['iban'] ."</td>";
		$page[] = "</tr>";
	}
	
	if($input['eigen'] == 'Nee') {		
		$relatieData = eb_getRelatieDataByCode($input['relatie']);
		
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Begunstigde:</td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td colspan='3'>". $relatieData['naam'] ."<br>". $relatieData['iban'] ."</td>";
		$page[] = "</tr>";
	}
	
	if(isset($input['cluster']) AND $input['cluster'] != '') {
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Cluster onderdeel:</td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td colspan='3'>". $clusters[$input['cluster']] ."</td>";
		$page[] = "</tr>";
	}
			
	/*
	if(isset($input['post']) AND $input['post'] != '') {		
		# Doorloop alle posten en zoek de bijbehorende naam erbij
		foreach($input['post'] as $post) {
			foreach($declJGPost as $subArray) {
				if(isset($subArray[$post]))	$aPost[] = $subArray[$post];
			}
		}
		
		$page[] = "<tr>";
		$page[] = "		<td colspan='2'>Post:</td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td colspan='3'>". implode(', ', $aPost) ."</td>";
		$page[] = "</tr>";
		#$page[] = "<tr>";
		#$page[] = "		<td colspan='2'>Post-omschrijving:</td>";
		#$page[] = "		<td>&nbsp;</td>";
		#$page[] = "		<td colspan='3'>Volgt</td>";
		#$page[] = "</tr>";
	}
	*/
	
	if(isset($input['overige']) AND count($input['overige']) > 0) {
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'><b>Declaraties</b></td>";
		$page[] = "</tr>";
		
		$totaal = calculateTotals($input['overig_price']);
	
		foreach($input['overige'] as $key => $value) {
			if($value != "") {
				$page[] = "<tr>";
				$page[] = "		<td>&nbsp;</td>";
				
				if(isset($input['post'][$key])) {
					$post_nr = $input['post'][$key];
					foreach($declJGPost as $kop => $subArray) {
						if(isset($subArray[$post_nr])) {
							$catagorie = $declJGKop[$kop];
							$post = $subArray[$post_nr];
						}
					}
									
					$page[] = "		<td>$value</td>";
					$page[] = "		<td>&nbsp;</td>";
					$page[] = "		<td>$catagorie -> $post</td>";
				} else {
					$page[] = "		<td colspan='3'>$value</td>";
				}				
				
				$page[] = "		<td>&nbsp;</td>";
				$page[] = "		<td align='right'>". formatPrice(price2RightFormat($input['overig_price'][$key])*100) ."</td>";
				$page[] = "</tr>";				
			}
		}			
	}
	
	if($input['eigen'] == 'Ja' AND isset($input['reiskosten']) AND $input['reiskosten'] > 0) {
		$page[] = "<tr>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td colspan='3'>Reiskosten</td>";
		$page[] = "		<td>&nbsp;</td>";
		$page[] = "		<td align='right'>". formatPrice($input['reiskosten']) ."</td>";
		$page[] = "</tr>";	
		
		$totaal = $totaal + $input['reiskosten'];
	}
			
	$page[] = "<tr>";
	$page[] = "		<td colspan='6'>&nbsp;</td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "		<td colspan='4'><b>Totaal</b></td>";
	$page[] = "		<td>&nbsp;</td>";
	$page[] = "		<td align='right'><b>". formatPrice($totaal) ."</b></td>";
	$page[] = "</tr>";
	$page[] = "<tr>";
	$page[] = "		<td colspan='6'>&nbsp;</td>";
	$page[] = "</tr>";
	
	if(isset($input['bijlage']) AND $input['bijlage'] != '') {
		$page[] = "<tr>";	
		$page[] = "		<td colspan='1' valign='top'><b>Bijlage</b></td>";	
		$page[] = "		<td colspan='5'>";
		
		foreach($input['bijlage'] as $key => $bestand) {
			$page[] = "<li><a href='$bestand' target='blank'>". substr($input['bijlage_naam'][$key], 0, 40).( strlen($input['bijlage_naam'][$key]) > 40 ? '.....' : '')."</a></li>";
		}
		$page[] = "</td>";	
		$page[] = "</tr>";
	}
	
	if(isset($input['opmerking_cluco']) AND $input['opmerking_cluco'] != '') {
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";	
		$page[] = "		<td colspan='6'><b>Opmerking door indiener</b></td>";
		$page[] = "</tr>";	
		$page[] = "<tr>";	
		$page[] = "		<td colspan='6'><i>". $input['opmerking_cluco'] ."</i></td>";
		$page[] = "</tr>";
	}
	
	if(isset($input['opmerking_penning']) AND $input['opmerking_penning'] != '') {
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";	
		$page[] = "		<td colspan='6'><b>Opmerking door cluco</b></td>";
		$page[] = "</tr>";	
		$page[] = "<tr>";	
		$page[] = "		<td colspan='6'><i>". $input['opmerking_penning'] ."</i></td>";
		$page[] = "</tr>";
	}
	
	
	if(isset($input['toelichting_penning']) AND $input['toelichting_penning'] != '') {
		$page[] = "<tr>";
		$page[] = "		<td colspan='6'>&nbsp;</td>";
		$page[] = "</tr>";
		$page[] = "<tr>";	
		$page[] = "		<td colspan='6'><b>Opmerking door penningmeester</b></td>";
		$page[] = "</tr>";	
		$page[] = "<tr>";	
		$page[] = "		<td colspan='6'><i>". $input['toelichting_penning'] ."</i></td>";
		$page[] = "</tr>";
	}
	
	$page[] = "<tr>";
	$page[] = "		<td colspan='6'>&nbsp;</td>";
	$page[] = "</tr>";
	
	return $page;	
}

# Hoewel het idee was dat alles omzetten naar JSON veel problemen verhelpt.
# Veroorzaakt het ook wel wat problemen. Daarom deze functie.
# Om te beginnen worden alle " vervangen door '. Dat is omdat anders het HTML-formulier in de war raakt.
# Vervolgens worden alle velden doorlopen en omgezet in UTF-8, mn van belang voor 'gekke' tekens.
# Vervolgens wordt alles omzetten in JSON-formaat
# En dan de newlines vervangen door een spatie
function encode_clean_JSON($input) {
	$array = $input;
	
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

# https://stackoverflow.com/questions/4117555/simplest-way-to-detect-a-mobile-device-in-php
function isMobile() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}


function setDeclaratieActionDate($declaratie, $tijd = 0) {
	global $db, $TableEBDeclaratie, $EBDeclaratieLastAction, $EBDeclaratieHash;
	
	if($tijd == 0) $tijd = time();
	
	$sql = "UPDATE $TableEBDeclaratie SET $EBDeclaratieLastAction = $tijd WHERE $EBDeclaratieHash like '$declaratie'";
	
	if(mysqli_query($db, $sql)) {
		toLog('debug', '', "Laatste actie van declaratie $declaratie gezet op ". time2str('%a %e %b %H:%M', $tijd));
		return true;
	} else {
		toLog('error', '', "Kon laatste actie van declaratie $declaratie niet instellen op ". time2str('%a %e %b %H:%M', $tijd));
		return false;
	}	
}


function getOpenKerkTemplates() {
	global $db, $TableOpenKerkTemplateNames, $OpenKerkTemplateNamesID, $OpenKerkTemplateNamesName;
	
	$data = array();
	
	$sql = "SELECT * FROM $TableOpenKerkTemplateNames ORDER BY $OpenKerkTemplateNamesID";	
	$result = mysqli_query($db, $sql);
	if($row	= mysqli_fetch_array($result)) {
		do {
			$ID = $row[$OpenKerkTemplateNamesID];
			$data[$ID] = $row[$OpenKerkTemplateNamesName];
		} while($row = mysqli_fetch_array($result));
	}
		
	return $data;	
}


function getOpenKerkTemplateName($id) {
	global $db, $TableOpenKerkTemplateNames, $OpenKerkTemplateNamesID, $OpenKerkTemplateNamesName;
	
	$data = array();
	
	$sql = "SELECT * FROM $TableOpenKerkTemplateNames WHERE $OpenKerkTemplateNamesID = $id";	
	$result = mysqli_query($db, $sql);
	if($row	= mysqli_fetch_array($result)) {
		return $row[$OpenKerkTemplateNamesName];	
	}	
}



function getOpenKerkVulling($template, $week, $dag, $slot) {
	global $db, $TableOpenKerkTemplate, $OKTemplateTemplate, $OKTemplateWeek, $OKTemplateDag, $OKTemplateTijd, $OKTemplatePos, $OKTemplatePersoon;
	
	$data = array();
	$sql = "SELECT * FROM $TableOpenKerkTemplate WHERE $OKTemplateTemplate = $template AND $OKTemplateWeek = '$week' AND $OKTemplateDag = '$dag' AND $OKTemplateTijd = '$slot'";
		
	$result = mysqli_query($db, $sql);

	if($row	= mysqli_fetch_array($result)) {
		do {
			$positie = $row[$OKTemplatePos];
			$data[$positie] = $row[$OKTemplatePersoon];
		} while($row = mysqli_fetch_array($result));
	}
	
	#echo $sql;
	#var_dump($data);
	
	return $data;	
}

function getStore($template, $week, $dag, $slot, $pos) {
	global $db, $TableOpenKerkTemplate, $OKTemplateTemplate, $OKTemplateWeek, $OKTemplateDag, $OKTemplateTijd, $OKTemplatePos, $OKTemplateEnroll;
	
	$sql = "SELECT $OKTemplateEnroll FROM $TableOpenKerkTemplate WHERE $OKTemplateTemplate = $template AND $OKTemplateWeek = '$week' AND $OKTemplateDag = '$dag' AND $OKTemplateTijd = '$slot' AND $OKTemplatePos = '$pos'";
	$result = mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 1) {
		$row = mysqli_fetch_array($result);		
		if($row[$OKTemplateEnroll] == 1) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}


# https://www.kalender-365.nl/feestdagen/2024.html

# Pasen = 1ste zondag in de lente na volle maan
# kei-moeilijk uit te rekenen
# Daarom niet uitrekenen maar volle maan opvragen
/*
function getPasen($jaar) {
	$lente = mktime(0,0,1,3,21,$jaar);
	$url = 'https://www.kalender-365.nl/maan/maanstanden.html';
	$i = 0;
	$doorgaan = true;
	$data = array();

	$contents = file_get_contents($url);
	$rijen = explode('<tr><td>', $contents);

	#echo $lente .'<br>';
	
	do {
		$i++;
		$rij = $rijen[$i];
		$maan = getString('', '</td><td', $rij, 0);
		$tijd = getString('data-value="', '">', $rij, 0);
		$datum = getString('">', '</td>', $tijd[1], 0);
		
		#echo $tijd[0] .'|'. $maan[0] .'|'. $datum[0] .'<br>';
		
		if(($i > (count($rijen)-2)) OR ($maan[0] == '<b>Volle maan</b>' AND ($tijd[0] > $lente))) {	
			$doorgaan = false;
		}		
	} while($doorgaan);
	
	#echo $maan[0] .'|'. $datum[0];
	#echo $tijd[0];
	
	$data['dag']		=	date("j", $tijd[0])+(7-date("N", $tijd[0]));
	$data['maand']	= date("n", $tijd[0]);
	
	return $data;
}
*/

function getPasen($jaar) {
	$url = 'https://www.kalender-365.nl/feestdagen/pasen.html';
		
	$i = 0;
	$doorgaan = true;
	$data = array();

	$contents = file_get_contents($url);
	$rijen = explode('<tr><td', $contents);
	
	$start	= mktime(0,0,0, 1, 1,$jaar);
	$end		= mktime(0,0,0,12,31,$jaar);
		
	do {
		$i++;
		$rij = $rijen[$i];
		
		$tijd = getString('data-value="', '"', $rij, 0);
		$datum = getString('class="dtr tar">', '</td>', $rij, 0);
		
		#echo $tijd[0] .'|'. $datum[0] .'<br>';
		
		if(($i > (count($rijen)-2)) OR ($tijd[0] > $start AND $tijd[0] < $end)) {	
			$doorgaan = false;
		}		
	} while($doorgaan);
	
	$data['dag']		=	date("j", $tijd[0]);
	$data['maand']	= date("n", $tijd[0]);
	
	return $data;
}

function get2FACode($user) {
	global $TableUsers, $User2FA, $UserID, $db;
	
	$sql = "SELECT $User2FA FROM $TableUsers WHERE $UserID = $user";
	$result = mysqli_query($db, $sql);

	if($row	= mysqli_fetch_array($result)) {
		return $row[$User2FA];
	} else {
		return '';
	}
}

function storeLogin($id, $ip, $agent) {
	global $TableLogins, $LoginLid, $LoginIP, $LoginAgent, $LoginTijd, $db;
	global $TableUsers, $UserLastVisit, $UserID;
	
	$sql_1 = "INSERT INTO $TableLogins ($LoginLid, $LoginIP, $LoginAgent, $LoginTijd) VALUES ($id, '$ip', '$agent', NOW())";
	$sql_2 = "UPDATE $TableUsers SET $UserLastVisit = '". time() ."' WHERE $UserID like ". $_SESSION['useID'];
	
	if(mysqli_query($db, $sql_1) AND mysqli_query($db, $sql_2)) {
		return true;
	} else {
		return false;
	}
}

function knownLoginFromIP($id, $ip) {
	global $TableLogins, $LoginLid, $LoginIP, $LoginTijd, $twoFactor_lifetime, $db;
	
	$sql = "SELECT * FROM $TableLogins WHERE $LoginLid like $id AND $LoginIP like '$ip' AND $LoginTijd > NOW() + ". $twoFactor_lifetime ." ORDER BY $LoginTijd";
	
	if($row	= mysqli_fetch_array($result)) {
		return true;
	} else {
		return false;
	}
}

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

?>
