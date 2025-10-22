<?php

class Member {		
	public int $id;
	public int $adres;
	public string $relatie;
	public string $voorletters;
	public string $voornaam;
	public string $tussenvoegsel;
	public string $achternaam;
	public string $meisjesnaam;
	public string $geslacht;
	public string $email;
	public string $email_formeel;
	public string $username;
	public string $hash;
	public string $hash_short;
	
	public int $nameType;
	public int $emailType;

	public array $teams;
	public array $roosters;
	public array $beheer_teams;
	public array $beheer_roosters;

	public array $familie;
	
	## Methods
	function __construct(int $id = 0) {
		$this->nameType = 2;
		$this->emailType = 1;
		
		if($id > 0) {
			$db = new Mysql();
			$data = $db->select("SELECT * FROM `leden` WHERE `scipio_id` = ". $id);
			
			$this->id = $id;
			$this->adres = $data['kerk_adres'];
			$this->voorletters = $data['voorletters'];
			$this->voornaam = $data['voornaam'];
			$this->tussenvoegsel = $data['tussenvoegsel'];
			$this->achternaam = $data['achternaam'];
			$this->meisjesnaam = $data['meisjesnaam'];
			$this->geslacht = $data['geslacht'];
			$this->relatie = $data['relatie'];
			$this->email = $data['email'];
			$this->email_formeel = $data['formeel'];
		}  	   
	}
	
	
	/**
	 * @return array Geeft een array terug met teams waar dit lid deel van uitmaakt
	 */
	function getTeams() {
		if(!isset($this->teams)) {
			$db = new Mysql();
			$data = $db->select("SELECT `commissie` FROM `group_member` WHERE `lid` like ". $this->id, true);

			$this->teams = array_column($data, 'commissie');	
		}

		return $this->teams;
	}


	/**
	 * @return array Geeft een array terug met roosters waar dit lid op ingepland kan worden
	 */
	function getRoosters() {
		if(!isset($this->roosters)) {
			$db = new Mysql();
			$data = $db->select("SELECT `roosters`.`id` FROM `roosters`, `group_member` WHERE `group_member`.`commissie` = `roosters`.`groep` AND `group_member`.`lid` = ". $this->id, true);

			$this->roosters = array_column($data, 'id');	
		}

		return $this->roosters;
	}

	
	/**
	 * @return array Geeft een array terug met teams die dit lid beheert
	 */
	function getBeheerTeams() {
		if(!isset($this->beheer_teams)) {
			$db = new Mysql();
			$data = $db->select("SELECT `groepen`.`id` FROM `groepen`, `group_member` WHERE `groepen`.`beheerder` = `group_member`.`commissie` AND `group_member`.`lid` = ". $this->id ." ORDER BY `groepen`.`naam`", true);
			
			$this->beheer_teams = array_column($data, 'id');	
		}

		return $this->beheer_teams;
	}

	
	/**
	 * @return array Geeft een array terug met roosters die dit lid beheert
	 */
	function getBeheerRooster() {
		if(!isset($this->beheer_roosters)) {
			$db = new Mysql();
			$data = $db->select("SELECT `roosters`.`id` FROM `roosters`, `groepen`, `group_member` WHERE (`roosters`.`beheerder` = `groepen`.`id` OR `roosters`.`planner` = `groepen`.`id`) AND `groepen`.`id` = `group_member`.`commissie` AND `group_member`.`lid` = ". $this->id ." GROUP BY `roosters`.`id`", true);

			$this->beheer_roosters = array_column($data, "id");			
		}

		return $this->beheer_roosters;
	}


	/**
	 * @return array Geeft een array terug met ID's van familieleden van dit lid
	 */
	function getFamilieLeden() {
		if(!isset($this->familie)) {
			$db = new Mysql();
			$data = $db->select("SELECT `scipio_id` FROM `leden` WHERE `kerk_adres` = ". $this->adres, true);

			$this->familie = array_column($data, "scipio_id");			
		}

		return $this->familie;
	}


	
	/**
	 * @return string Geeft de naam van het lid terug (afhankelijk van nameType)
	 */
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
			case 3:
				return $this->voorletters .' ('. $this->voornaam .') '. $achterFull;
				break;
			default:
				return $voor .' '. $achterFull;
		}
	}
	
	

	/**
	 * @return string Geeft het mailadres van het lid terug (afhankelijk van emailType)
	 */
	function getMail() { 
		# 1 : gewone mail
		# 2 : formeel mailadres

		switch ($this->emailType) {
			case 1:
				return $this->email;
				break;
			case 2:
				return $this->email_formeel;
				break; 		
		}
	}
	
	
	/**
	 * @param string $type Type leden dat opgehaald moet worden: all, volwassen, adressen
	 * 
	 * @return array Geeft een array terug met ID's van alle leden in de database
	 */
	public static function getMembers($type = 'all') {
		$db = new Mysql();

		if($type == 'all') {
			$data = $db->select("SELECT `scipio_id` FROM `leden` WHERE `status` like 'actief' ORDER BY `achternaam`", true);
		} elseif($type == 'volwassen') {
			$data = $db->select("SELECT `scipio_id` FROM `leden` WHERE `status` like 'actief' AND `geboortedatum` < '". (date("Y")-18) ."-". date("m-d") ."' ORDER BY `achternaam`", true);
		} elseif($type == 'adressen') {
			$data = $db->select("SELECT `scipio_id` FROM `leden` WHERE `status` like 'actief' AND (`relatie` like 'gezinshoofd' OR `relatie` like 'zelfstandig') GROUP BY `kerk_adres` ORDER BY `achternaam`", true);
		}
				
		return array_column($data, 'scipio_id');
	}


	/**
	 * @return array|null Geeft een array terug met ID's van de ouders van dit lid, of null als er geen ouders zijn
	 */
	public function getParents() {
		$familie = $this->getFamilieLeden();

		foreach($familie as $lid_id) {
			$lid = new Member($lid_id);

			if(in_array($lid->relatie, ['echtgenote', 'gezinshoofd', 'echtgenoot', 'levenspartner', 'partner'])) {				
				$ouders[] = $lid_id;
			}
		}

		if(count($ouders) > 1) {
			return $ouders;
		} else {
			return null;
		}
	}


	/**
	 * @return int|null Geeft een array terug met ID's van de partner(s) van dit lid, of null als er geen partner is
	 */
	public function getPartner() {
		if(!in_array($this->relatie, ['zoon', 'dochter', 'inw. persoon', 'zelfstandig'])) {			
			$familie = $this->getFamilieLeden();

			$partner = [];
			foreach($familie as $lid_id) {
				$lid = new Member($lid_id);

				if(!in_array($this->relatie, ['zoon', 'dochter', 'inw. persoon']) && $lid_id != $this->id && in_array($lid->relatie, ['echtgenote', 'echtgenoot', 'gezinshoofd', 'levenspartner', 'partner'])) {
					$partner[] = $lid_id;
				}
			}
		}

		if(count($partner) == 1) {
			return $partner[0];
		} elseif(count($partner) > 1) {
			toLog('Meer dan 1 partner geregistreerd: '. implode(', ', $partner), 'error');
			return $partner[0];
		} else {
			return null;
		}		
	}

}

?>