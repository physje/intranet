<?php
/**
 * Class voor een lid in de kerkelijke administratie
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
class Member {
	/**
	 * @var Mysql
	 */
	public Mysql $db;
	
	/**
	 * @var int ID van het lid, komt overeen met het Scipio-ID
	 */
	public int $id;
	
	/**
	 * @var string Status van het lid, denk aan actief, vertrokken, ontrokken, etc.
	 */
	public string $status;
	
	/**
	 * @var int ID van het kerkelijk adres. Leden met hetzelfde kerkelijk adres wonen op hetzelfde adres.
	 * Let-op, volwassen kinderen die nog thuis wonen hebben een ander kerkelijk adres.
	 */
	public int $adres;
	
	/**
	 * @var string Type geslacht van het lid, M voor mannen/jongens en V voor vrouwen/meisjes
	 */
	public string $geslacht;
	
	/**
	 * @var string Voorletters van het lid
	 */
	public string $voorletters;
	
	/**
	 * @var string Voornaam (roepnaam) van het lid
	 */
	public string $voornaam;
	
	/**
	 * @var string Tussenvoegsel bij de achternaam van het lid
	 */
	public string $tussenvoegsel;
	
	/**
	 * @var string Achternaam van het lid
	 */
	public string $achternaam;

	/**
	 * @var string Geboortenaam van het lid, met name bij getrouwde vrouwen
	 */
	public string $meisjesnaam;

	/**
	 * @var string Straat waar het lid woont
	 */
	public string $straat;
	
    /**
	 * @var int Huisnummer waar het lid woont
	 */
	public int $huisnummer;
	
	/**
	 * @var string Letter van het huisnummer waar het lid woont
	 */
	public string $huisnummer_letter;
	
	/**
	 * @var string Toevoeging bij het huisnummer waar het lid woont
	 */
	public string $huisnummer_toevoeging;
	
	/**
	 * @var string Postcode van het lid
	 */
	public string $postcode;
	
	/**
	 * @var string Woonplaats van het lid
	 */
	public string $woonplaats;
	
	/**
	 * @var string Telefoonnummer van het lid
	 */
	public string $telefoon;
	
	/**
	 * @var string Persoonlijke mailadres van het lid
	 */
	public string $email;
	
	/**
	 * @var string Formele/Koningskerk-adres van het lid
	 */
	public string $email_formeel;
	
	/**
	 * @var string Geboortedatum (YYY-MM-DD) van het lid.
	 */
	public string $geboortedatum;
	
	/**
	 * @var string Relationele status van het lid (gezinshoofd, zoon, dochter)
	 */
	public string $relatie;
	
	/**
	 * @var string Is het lid doop- of belijdend lid
	 */
	public string $doop_belijdenis;
	
	/**
	 * @var string Wat (gehuwd, gescheiden, zelfstandig)
	 */
	public string $burgelijk;
	
	/**
	 * @var int UNIX-tijd van tijdstip van vestiging
	 */
	public int $tijd_vestiging;
	
	/**
	 * @var int UNIX-tijd van tijdstip van laatste wijziging
	 */
	public int $tijd_wijziging;
	
	/**
	 * @var int UNIX-tijd van tijdstip van laatste inlog
	 */
	public int $tijd_bezoek;
	
	/**
	 * @var int UNIX-tijd van tijdstip van laatste synchronisatie met Scipio
	 */
	public int $tijd_scipipo;
	
	/**
	 * @var int ID in eBoekhouden.nl
	 */
	public int $boekhouden;
	
	/**
	 * @var string MFA-code (nog niet in gebruik)
	 */
	public string $MFA_code;
	
	/**
	 * @var string Gebruikersnaam van het lid
	 */
	public string $username;
	
	/**
	 * @var string Versleutelde password van het lid
	 */
	public string $password;
	
	/**
	 * @var string Wijk (A-J, ICF) waarin het lid woont
	 */
	public string $wijk;
	
	/**
	 * @var string alfanumerieke reeks van 64-tekens waarmee het lid geïdentificeerd kan worden zonder in te loggen
	 */
	public string $hash_long;
	
	/**
	 * @var string alfanumerieke reeks van 16-tekens waarmee het lid geïdentificeerd kan worden zonder in te loggen	 
	 */
	public string $hash_short;
	
	/**
	 * @var int Type naamgeving.
	 * @see Member::getName() voor voorbeelden
	 */
	public int $nameType;
	
	/**
	 * @var int Type mailadres.
	 * 1 = normale adres
	 * 2 = formele adres
	 */
	public int $emailType;

	/**
	 * @var int Geboortjaar van lid
	 */
	public int $geboorte_jaar;
	
	/**
	 * @var int Geboortmeaand van lid
	 */
	public int $geboorte_maand;
	
	/**
	 * @var int Geboortedag van lid
	 */
	public int $geboorte_dag;

	/**
	 * @var array ID van teams of groepen waar lid in zit
	 */
	public array $teams;
	
	/**
	 * @var array ID van roosters waar lid op staat of op kan staan
	 */
	public array $roosters;
	
	/**
	 * @var array ID van teams of groepen die lid beheert
	 */
	public array $beheer_teams;
	
	/**
	 * @var array ID van roosters die lid beheert
	 */
	public array $beheer_roosters;
	
	/**
	 * @var array ID van roosters die lid plant
	 */
	public array $planning_roosters;
	
	/**
	 * @var array Lid-IDS van familieleden van het lid
	 */
	public array $familie;
	
	/**
	 * @var int Lid-ID van de ouderling van het lid
	 */
	public int $pastor;
	
	/**
	 * @var int Lid-ID van de bezoekbroeder- of zuster van het lid
	 */
	public int $bezoeker;
	
	## Methods
	function __construct(int $id = 0) {
		$db = new Mysql();
		$this->id = 0;
		$this->db = $db;
		$this->nameType = 2;
		$this->emailType = 1;
		$this->status = '';
		$this->adres = 0;
		$this->geslacht = '';
		$this->voorletters = '';
		$this->voornaam = '';
		$this->tussenvoegsel = '';
		$this->achternaam = '';
		$this->meisjesnaam = '';
		$this->straat = '';
		$this->huisnummer = 0;
		$this->huisnummer_letter = '';
		$this->huisnummer_toevoeging = '';
		$this->postcode = '';
		$this->woonplaats = '';
		$this->wijk = '';
		$this->telefoon = '';
		$this->email = '';
		$this->email_formeel = '';
		$this->geboortedatum = '';			
		$this->relatie = '';
		$this->doop_belijdenis = '';
		$this->burgelijk = '';
		$this->tijd_vestiging = 0;
		$this->tijd_wijziging = 0;
		$this->tijd_bezoek = 0;
		$this->tijd_scipipo = 0;
		$this->boekhouden = 0;			
		$this->MFA_code = '';
		$this->username = '';
		$this->password = '';
		$this->hash_long = '';
		$this->hash_short = '';
		
		if($id > 0) {			
			$data = $db->select("SELECT * FROM `leden` WHERE `scipio_id` = ". $id);
						
			$this->id = $id;
			$this->status = $data['status'];
			$this->adres = $data['kerk_adres'];
			$this->geslacht = $data['geslacht'];
			$this->voorletters = $data['voorletters'];
			$this->voornaam = urldecode($data['voornaam']);
			$this->tussenvoegsel = urldecode($data['tussenvoegsel']);
			$this->achternaam = urldecode($data['achternaam']);
			$this->meisjesnaam = urldecode($data['meisjesnaam']);
			$this->straat = urldecode($data['straat']);
			$this->huisnummer = ($data['nummer'] == '' ? 0 : $data['nummer']);
			$this->huisnummer_letter = $data['letter'];
			$this->huisnummer_toevoeging = $data['toevoeging'];
			$this->postcode = $data['postcode'];
			$this->woonplaats = urldecode($data['plaats']);
			$this->wijk = $data['wijk'];
			$this->telefoon = $data['telefoon'];
			$this->email = urldecode($data['email']);
			$this->email_formeel = urldecode($data['formeel']);
			$this->geboorte_jaar = substr($data['geboortedatum'], 0, 4);
			$this->geboorte_maand = substr($data['geboortedatum'], 5, 2);
			$this->geboorte_dag = substr($data['geboortedatum'], 8, 2);			
			$this->geboortedatum = $data['geboortedatum'];			
			$this->relatie = $data['relatie'];
			$this->doop_belijdenis = $data['belijdenis'];
			$this->burgelijk = $data['burgstaat'];
			$this->tijd_vestiging = $data['vestiging'];
			$this->tijd_wijziging = $data['last_change'];
			$this->tijd_bezoek = $data['last_visit'];
			$this->tijd_scipipo = $data['last_scipio'];
			$this->boekhouden = $data['eb_code'];			
			$this->MFA_code = $data['2FA_code'];
			$this->username = urldecode($data['username']);
			$this->password = $data['password_new'];
			$this->hash_long = urldecode($data['hash_long']);
			$this->hash_short = urldecode($data['hash_short']);
		} 
	}


	/**
	 * Controleer of het id van een Member-object al in de database staat.
	 * @return bool True als wel bekend, False indien onbekend
	 */
	function memberExist() : bool {
		$db = $this->db;
		$data = $db->select("SELECT * FROM `leden` WHERE `scipio_id` = ". $this->id, true);
		
		if(count($data) == 0) {
			return false;			
		} else {
			return true;
		}

	}
	
	
	/**
	 * Geeft een array terug met teams waar dit lid deel van uitmaakt.
	 * @return array array met teams
	 */
	function getTeams() {
		if(!isset($this->teams)) {
			$db = $this->db;			
			$data = $db->select("SELECT `commissie` FROM `group_member` WHERE `lid` like ". $this->id, true);

			$this->teams = array_column($data, 'commissie');	
		}

		return $this->teams;
	}


	/**
	 * Geeft een array terug met roosters waar dit lid op ingepland kan worden.
	 * @return array array met roosters
	 */
	function getRoosters() {
		if(!isset($this->roosters)) {
			$db = $this->db;
			$data = $db->select("SELECT `roosters`.`id` FROM `roosters`, `group_member` WHERE `group_member`.`commissie` = `roosters`.`groep` AND `group_member`.`lid` = ". $this->id, true);

			$this->roosters = array_column($data, 'id');	
		}

		return $this->roosters;
	}

	
	/**
	 * Geeft een array terug met teams die dit lid beheert.
	 * @return array array met teams
	 */
	function getBeheerTeams() {
		if(!isset($this->beheer_teams)) {
			$db = $this->db;
			$data = $db->select("SELECT `groepen`.`id` FROM `groepen`, `group_member` WHERE `groepen`.`beheerder` = `group_member`.`commissie` AND `group_member`.`lid` = ". $this->id ." ORDER BY `groepen`.`naam`", true);
			
			$this->beheer_teams = array_column($data, 'id');	
		}

		return $this->beheer_teams;
	}

	
	/**
	 * Geeft een array terug met roosters die dit lid beheert.
	 * @return array array met roosters
	 */
	function getBeheerRooster() {
		if(!isset($this->beheer_roosters)) {
			$db = $this->db;
			$data = $db->select("SELECT `roosters`.`id` FROM `roosters`, `groepen`, `group_member` WHERE `roosters`.`beheerder` = `groepen`.`id` AND `groepen`.`id` = `group_member`.`commissie` AND `group_member`.`lid` = ". $this->id ." GROUP BY `roosters`.`id`", true);

			$this->beheer_roosters = array_column($data, "id");			
		}

		return $this->beheer_roosters;
	}
	
	
	/**
	 * Geeft een array terug met roosters die dit lid mag plannen.
	 * @return array array met roosters
	 */
	function getPlannerRooster() {
		if(!isset($this->planning_roosters)) {
			$db = $this->db;
			$query = "SELECT `roosters`.`id` FROM `roosters`, `group_member` WHERE
			`roosters`.`planner` = `group_member`.`commissie` AND
			`group_member`.`lid` = ". $this->id ." GROUP BY `roosters`.`id`";

			$data = $db->select($query, true);

			$this->planning_roosters = array_column($data, "id");			
		}

		return $this->planning_roosters;
	}


	/**
	 * Geeft een array terug met ID's van familieleden van dit lid
	 * @return array array met lid-IDs
	 */
	function getFamilieLeden() {
		if(!isset($this->familie)) {
			$db = $this->db;
			$data = $db->select("SELECT `scipio_id` FROM `leden` WHERE `kerk_adres` = ". $this->adres, true);

			$this->familie = array_column($data, "scipio_id");			
		}

		return $this->familie;
	}

	/**
	 * Geef ID van de ouderling
	 * @return int ID van de ouderling
	 */
	function getPastor() : int {
		if(!isset($this->pastor)) {
			$this->pastor = 0;

			$db = $this->db;
			$data = $db->select("SELECT `pastor` FROM `pastoraat_verdeling` WHERE `lid` = ". $this->id);

			if(isset($data['pastor'])) {
				$this->pastor = $data['pastor'];
			}			
		}

		return $this->pastor;
	}

	/**
	 * Sla de ouderling en bezoek-broeder of -zuster van het lid op
	 * @return bool Succesvol of niet
	 */
	function setPastor() : bool {
		$db = $this->db;

		$sql = array();
		$sql[] = "DELETE FROM `pastoraat_verdeling` WHERE `lid` = ". $this->id;
		$sql[] = "INSERT INTO `pastoraat_verdeling` (`pastor`, `bezoeker`, lid) VALUES (". $this->pastor .", ". $this->bezoeker .", ". $this->id .")";

		foreach($sql as $query) {			
			if(!$db->query($query)){
				return false;
			}
		}

		return true;
	}

	/**
	 * Geef ID van de bezoek-broeder of -zuster
	 * @return int ID van de bezoek-broeder of -zuster
	 */
	function getBezoeker() : int {
		if(!isset($this->bezoeker)) {
			$this->bezoeker = 0;

			$db = $this->db;
			$data = $db->select("SELECT `bezoeker` FROM `pastoraat_verdeling` WHERE `lid` = ". $this->id);

			if(isset($data['bezoeker'])) {
				$this->bezoeker = $data['bezoeker'];
			}			
		}

		return $this->bezoeker;
	}
	

	/**
	 * Geef het opgemaakte woonadres (straat+huisnummer+letter+toevoeging) terug van het lid
	 * @return string Woonadres
	 */
	function getWoonadres() {
		return $this->straat .' '. $this->huisnummer.($this->huisnummer_letter != '' ? ' '.$this->huisnummer_letter : '').($this->huisnummer_toevoeging != '' ? ' '.$this->huisnummer_toevoeging : '');
	}


	/**
	 * Geef een array terug met ID's van pastorale bezoeken
	 * @return array ID van pastorale bezoeken
	 */
	function getPastoraleBezoeken() {
		$db = $this->db;
		$data = $db->select("SELECT `id` FROM `pastoraat` WHERE `lid` = ". $this->id ." ORDER BY `tijdstip` DESC", true);
		return array_column($data, "id");
	}

	/**
	 * Geeft een array terug met ID's van de ouders van dit lid.
	 * @return array array met lid-IDs
	 */
	function getParents() {
		$kind		= array('zoon', 'dochter');
		$volwassen	= array('echtgenote', 'gezinshoofd', 'echtgenoot', 'levenspartner', 'partner');
		$ouders = array();

		# Alleen als de persoon zelf als kind in de boeken staat
		if(in_array($this->relatie, $kind)) {
			$familie = $this->getFamilieLeden();			

			foreach($familie as $lid_id) {
				$lid = new Member($lid_id);

				if(in_array($lid->relatie, ['echtgenote', 'gezinshoofd', 'echtgenoot', 'levenspartner', 'partner'])) {				
					$ouders[] = $lid_id;
				}
			}
		}

		return $ouders;
	}


	/**
	 * Geeft een int terug met ID van de partner van dit lid.
	 * @return int Int met ID van de partner van dit lid. Bij meerdere partners (uitzonderlijk) is het een array
	 */
	function getPartner() {
		$partner = [];

		if(!in_array($this->relatie, ['zoon', 'dochter', 'inw. persoon', 'zelfstandig'])) {			
			$familie = $this->getFamilieLeden();
			
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


	/**
	 * Controleert of een gebruikersnaam uniek is.
	 * @param string gebruikersnaam
	 * @return boolean Uniek ja of nee
	 */
	function isUniqueUsername($username) {
		$db = $this->db;
		$sql = "SELECT * FROM `leden` WHERE `username` like '". trim($username)."' AND `scipio_id` <> ". $this->id;

		$data = $db->select($sql, true);

		if(count($data) == 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Genereer een gebruikersnaam voor deze gebruiker
	 * @return string gebruikersnaam
	 */
	function generateUsername() {	
		if($this->voorletters != '') {
			$voor = strtoupper(str_replace('.', '', $this->voorletters));
		}
	
		$achter = ucfirst(str_replace(' ', '', $this->achternaam));

		$username = $voor.$achter;

		$i = 1;
	
		while(!$this->isUniqueUsername($username)) {
			if($this->meisjesnaam != '') {
				$username = $voor.$achter.ucfirst(str_replace(' ', '', $this->meisjesnaam));
			} elseif($this->voornaam != '') {
				$username = ucfirst(str_replace(' ', '', $this->voornaam)).$achter;
			} elseif($this->geboorte_jaar > 0) {
				$username = $voor.$achter.$this->geboorte_jaar;
			} else {
				$username = $voor.$achter.$i;
				$i++;
			}
		}
		
		return $username;
	}

	/**
	 * Geeft de naam van het lid terug (afhankelijk van nameType).
	 * 1 = voornaam										Alberdien
	 * 2 = korte achternaam								Jong
	 * 3 = volledige achternaam (man)					de Jong
	 * 4 = volledige achternaam (vrouw)					de Jong-van Ginkel
	 * 5 = voornaam achternaam (man)					Alberdien de Jong
	 * 6 = voornaam achternaam (vrouw)					Alberdien de Jong-van Ginkel
	 * 7 = voornaam achternaam (vrouw)					Alberdien van Ginkel	
	 * 8 = achternaam, voornaam							Jong; de, Alberdien
	 * 9 = voorletters achternaam (man)					A. de Jong
	 * 10 = voorletters achternaam (vrouw)				A. de Jong-van Ginkel
	 * 11 = voorletters achternaam (vrouw)				A. van Ginkel
	 * 12 = voorletters achternaam (man)				A. (Alberdien) de Jong
	 * 13 = voorletters achternaam (vrouw)				A. (Alberdien) de Jong-van Ginkel
	 * 14 = voorletters achternaam (vrouw)				A. (Alberdien) van Ginkel
	 * 15 = volledige achternaam, voornaam (vrouw)		Jong-van Ginkel; de, Alberdien
	 * 16 = volledige achternaam, voorletters (vrouw)	Jong-van Ginkel; de, A.
	
	 * @return string String met naam
	 */
	function getName($type = 5) {
		$voorletters = $voornaam = $tussen = $achter = $achter_m = '';

		if($this->voorletters != '') {
			$voorletters = $this->voorletters;
		}
		
		if($this->voornaam != '') {
			$voornaam	= $this->voornaam;		
		} else {
			$voornaam = $voorletters;
		}
		
		if($this->tussenvoegsel != '') {
			$tussen 	= strtolower($this->tussenvoegsel);
		}
		
		if($this->achternaam != '') {
			$achter 	= ucfirst($this->achternaam);
		}
		
		if($this->meisjesnaam != '') {
			$achter_m = $this->meisjesnaam;
		}

		if($achter_m != '' AND ($type == 4 OR $type == 6 OR $type == 10 OR $type == 13 OR $type == 15 OR $type == 16)) {
			$achter .= '-'.$achter_m;
		} elseif($achter_m != '' AND ($type == 7 OR $type == 11)) {
			$achter = $achter_m;
		}
				
		if($tussen == '') {			
			$achternaam	= $achter;
		} else {					
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
	
	

	/**
	 * Geeft het mailadres van het lid terug (afhankelijk van emailType)
	 * 
	 * Hiervoor wordt eerst het eigen mailadres opgevraagd.
	 * Als deze leeg is wordt het mailadres van de ouders opgevraagd en als die niet bestaan het adres van de partner.
	 * Voor het formele mailadres wordt eerst gekeken of het formele adres is ingevuld,
	 * zo niet dan wordt het standaard mailadres teruggegeven. Dat kan dus ook het adres van de ouders of partner zijn zoals hierboven.
	 * 
	 * @return string String met mailadres
	 */
	function getMail($type = 1) {        
		# 1 : gewone mail
		# 2 : formeel mailadres

		if($this->email != '') {
			$plain = $this->email;
		} else {
			# Persoonlijke mailadres bestaat niet
			# Check of er ouders zijn met een mailadres
			$ouders = $this->getParents();
			$prtnr = $this->getPartner();
											
			if(count($ouders) > 0) {
				$ouder = new Member($ouders[0]);
				$plain = $ouder->getMail($type);
			} elseif($prtnr != '') {				
				$partner = new Member($prtnr);
				$plain = $partner->getMail($type);
			} else {
				$plain = '';
			}						
		}

		if($this->email_formeel != '') {
			$formeel = $this->email_formeel;
		} else {
			$formeel = $plain;
		}

		switch ($type) {
			case 1:
				return $plain;
				break;
			case 2:
				return $formeel;
				break; 		
		}
	}

	/**
	 * Sla het Member-object op in de database
	 * @return bool Succesvol of niet
	 */
	function save() {
		$db = new Mysql;
		$data = $set = array();

		$data['scipio_id'] = $this->id;
		$data['status'] = $this->status;
		$data['kerk_adres'] = $this->adres;
		$data['geslacht'] = $this->geslacht;
		$data['voorletters'] = $this->voorletters;
		$data['voornaam'] = urlencode(trim($this->voornaam));
		$data['tussenvoegsel'] = urlencode(trim($this->tussenvoegsel));
		$data['achternaam'] = urlencode(trim($this->achternaam));
		$data['meisjesnaam'] = urlencode(trim($this->meisjesnaam));
		$data['straat'] = urlencode(trim($this->straat));
		$data['nummer'] = $this->huisnummer;
		$data['letter'] = $this->huisnummer_letter;
		$data['toevoeging'] = $this->huisnummer_toevoeging;
		$data['postcode'] = $this->postcode;
		$data['plaats'] = urlencode(trim($this->woonplaats));
		$data['wijk'] = $this->wijk;
		$data['telefoon'] = $this->telefoon;
		$data['email'] = urlencode(trim($this->email));
		$data['formeel'] = urlencode(trim($this->email_formeel));
		$data['geboortedatum'] = $this->geboortedatum;		
		$data['relatie'] = $this->relatie;
		$data['belijdenis'] = $this->doop_belijdenis;
		$data['burgstaat'] = $this->burgelijk;
		$data['vestiging'] = $this->tijd_vestiging;
		$data['last_change'] = $this->tijd_wijziging;
		$data['last_visit'] = $this->tijd_bezoek;
		$data['last_scipio'] = $this->tijd_scipipo;
		$data['eb_code'] = $this->boekhouden;
		$data['2FA_code'] = $this->MFA_code;
		$data['username'] = urlencode(trim($this->username));
		$data['password_new'] = trim($this->password);
		$data['hash_long'] = urlencode(trim($this->hash_long));
		$data['hash_short'] = urlencode(trim($this->hash_short));
		
		foreach($data as $key => $value) {
			$set[] = "`$key`='$value'";
		}

		if($this->memberExist()) {
            foreach($data as $key => $value) {
                $set[] = "`$key` = '$value'";
            }
            $sql = "UPDATE `leden` SET ". implode(', ', $set) ." WHERE `scipio_id` = ". $this->id;			
        } else {
            $sql = "INSERT INTO `leden` (`". implode('`, `', array_keys($data)) ."`) VALUES ('". implode("', '", array_values($data)) ."')";
			echo $sql;
        }

		return $db -> query($sql);
	}


	/**
	 * Geeft een array terug met ID's van alle leden in de database.
	 * @param string $type Type leden dat opgehaald moet worden: all, volwassen, adressen
	 * @return array array met lid-IDs
	 */
	static function getMembers($type = 'all') {
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
	 * Vraag alle leden met een mailadres op.
	 * Mailadres is daarbij alles met een @-teken (%40 omdat urlencoded) erin	
	 * @return array Array met key = scipio-ID, value = mailadres
	 */
	static function getMailadressen() {
		$db = new Mysql();

		$data = $db->select("SELECT `scipio_id`, `email` FROM `leden` WHERE `email` like '%%40%' AND `status` like 'actief' GROUP BY `email`", true);
						
		return array_column($data, 'email', 'scipio_id');
	}



	/**
	 * Trek de toegang in van leden die niet de status 'actief' hebben.
 	 * @return bool Succesvol of niet
 	 */
	static function setUsersInactive() {
		$db = new Mysql();
		
		$sql = "UPDATE `leden` SET `username` = '', `hash_short` = '', `hash_long` = '' WHERE `status` NOT like 'actief'";

		return $db->query($sql);
	}



	/**
	 * Maak username en hashes aan voor actieve leden waar deze ontbreken
	 * @return array Array met scipio-IDs
	 */
	static function getNewUsers() {
		$db = new Mysql();
		$sql = "SELECT * FROM `leden` WHERE `status` like 'actief' AND (`username` like '' OR `hash_short` like '' OR `hash_long` like '') ORDER BY `voornaam`";
		
		$data = $db->select($sql, true);
						
		return array_column($data, 'scipio_id');
	}

	/**
	 * Vraag leden op op basis van wijk of eerste letter van de achternaam
 	* @param string $letter Beginletter of wijk waarop gezocht moet worden
 	* @param bool $wijk Zoeken in wijk (True) of op eerste letter van achternaam (False)
 	* 
 	* @return array Array met scipio-IDs
 	*/
	static function getMembersByLetter(string $letter, $wijk = false) {
		$db = new Mysql();

		if($wijk) {
			$data = $db->select("SELECT `scipio_id` FROM `leden` WHERE `status` like 'actief' AND `wijk` like '$letter' ORDER BY `achternaam`", true);
		} else {
			$data = $db->select("SELECT `scipio_id` FROM `leden` WHERE `status` like 'actief' AND `achternaam` like '". $letter ."%' ORDER BY `achternaam`", true);
		}
				
		return array_column($data, 'scipio_id');
	}
}

?>
