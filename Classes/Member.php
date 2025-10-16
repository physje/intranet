<?php
class Member {	
	## Properties
	#private $scipio_id;
	#private $kerk_adres;
	#private $vestiging;
	#private $last_change;
	#private $last_visit;
	#private $last_scipio;
	#private $username;
	#private $password;
	#private $password_new;
	#private $MFA_code;
	#private $hash_short;
	#private $hash_long;
  
	#public $status;
		
	public $id;
	public $voorletters;
	public $voornaam;
	public $tussenvoegsel;
	public $achternaam;
	public $meisjesnaam;
	public $geslacht;
	public $email;
	public $email_formeel;
	
	private $nameType;
	private $emailType;

	public $teams;
	public $roosters;
	public $beheer_teams;
	public $beheer_roosters;
	
	#public $straat;
	#public $huisnummer;
	#public $letter;
	#public $toevoeging;
	#public $postcode;
	#public $plaats;
	#public $geboortedatum;
	#public $telefoon;
	
	#public $belijdenis;
	#public $burgelijk;
	#public $relatie;
	#public $wijk;
	#public $eb_code;

	## Methods
	function __construct(int $id = 0) {
		$this->nameType = 2;
		$this->emailType = 1;
		
		if($id > 0) {
			$db = new Mysql();
			$data = $db->select("SELECT * FROM `leden` WHERE `scipio_id` = ". $id);
							
			$this->id = $id;
			$this->voorletters = $data['voorletters'];
			$this->voornaam = $data['voornaam'];
			$this->tussenvoegsel = $data['tussenvoegsel'];
			$this->achternaam = $data['achternaam'];
			$this->meisjesnaam = $data['meisjesnaam'];
			$this->geslacht = $data['geslacht'];
			$this->email = $data['email'];
			$this->email_formeel = $data['formeel'];
		}  	   
	}
	
	
	
	function setNameType(int $type) {
		$this->nameType = $type;
	}
	
	
	
	function setMailType(int $type) {
		$this->emailType = $type;
	}  	
	
	

	function getTeams() {
		if(!isset($this->teams)) {
			$db = new Mysql();
			$data = $db->select("SELECT `commissie` FROM `group_member` WHERE `lid` like ". $this->id, true);

			$this->teams = $data;	
		}

		return $this->teams;
	}

	function getRoosters() {
		if(!isset($this->roosters)) {
			$db = new Mysql();
			$data = $db->select("SELECT `commissie` FROM `group_member` WHERE `lid` like ". $this->id, true);
			#$sql = "SELECT $TableRoosters.$RoostersID FROM $TableRoosters, $TableGrpUsr WHERE $TableGrpUsr.$GrpUsrGroup = $TableRoosters.$RoostersGroep AND $TableGrpUsr.$GrpUsrUser = $id";

			$this->roosters = $data;	
		}

		return $this->roosters;
	}

	
	function getBeheerTeams() {
		if(!isset($this->beheer_teams)) {
			$db = new Mysql();
			$data = $db->select("SELECT `groepen`.`id` FROM `groepen`, `group_member` WHERE `groepen`.`beheerder` = `group_member`.`commissie` AND `group_member`.`lid` = ". $this->id ." ORDER BY `groepen`.`naam`");
			
			$this->beheer_teams = $data;	
		}

		return $this->beheer_teams;
	}

	
	function getBeheerRooster() {
		if(!isset($this->beheer_roosters)) {
			$db = new Mysql();
			$data = $db->select("SELECT `roosters`.`id` FROM `roosters`, `group_member` WHERE `roosters`.`beheerder` = `group_member`.`commissie` AND `group_member`.`lid` = ". $this->id ." ORDER BY `roosters`.`naam`");
			# "SELECT $TableRoosters.$RoostersID FROM $TableRoosters, $TableGroups, $TableGrpUsr WHERE ($TableRoosters.$RoostersBeheerder = $TableGroups.$GroupID OR $TableRoosters.$RoostersPlanner = $TableGroups.$GroupID) AND $TableGroups.$GroupID = $TableGrpUsr.$GrpUsrGroup AND $TableGrpUsr.$GrpUsrUser = $id"
			$this->beheer_roosters = $data;	
		}

		return $this->beheer_roosters;
	}


	
	function getName() {
		if($this->voornaam != '') {
			$voor = $this->voornaam;
		} else {
			$voor = $this->voorletters;
		}
		
		if($this->tussenvoegsel != '') {
			$achter = $this->tussenvoegsel .' '. $this->achternaam;
		} else {
			$achter = $this->achternaam;
		}
		
		if($this->meisjesnaam != '') {
			$achterFull = $achter .'-'. $this->meisjesnaam;
		} else {
			$achterFull = $achter;
		}  		
		
		switch ($this->nameType) {
			case 1:
				return $voor;
				break;
			case 2:
				return $voor .' '. $achter;
				break;
			default:
				return $voor .' '. $achterFull;
		}
	}
	
	
	# 1 : gewone mail
	# 2 : formeel mailadres
	function getMail() {  	
		switch ($this->emailType) {
			case 1:
				return $this->email;
				break;
			case 2:
				return $this->email_formeel;
				break; 		
		}
	}  

}

?>