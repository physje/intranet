<?php

/**
 * Class voor agenda-items
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
class Agenda {
	/**
	 * @var int ID van het agenda-item
	 */
	public int $id;
	
	/**
	 * @var int Start-tijd van het agenda-item (UNIX-timestamp)
	 */
	public int $start;
	
	/**
	 * @var int Eind-tijd van het agenda-item (UNIX-timestamp)
	 */
	public int $eind;
	
	/**
	 * @var string Titel van het agenda-item
	 */
	public string $titel;
	
	/**
	 * @var string Beschrijving van het agenda-item
	 */
	public string $beschrijving;
	
	/**
	 * @var int Eigenaar van het agenda-item (lid-ID)
	 */
	public int $eigenaar;

    	/**
    	 * @param int $id
    	 */
    	function __construct($id = 0) {
		if($id != 0) {
			$db = new Mysql();
			$data = $db->select("SELECT * FROM `agenda` WHERE `id` = ". $id);

			$this->id = $data['id'];
			$this->start = $data['start'];
			$this->eind = $data['eind'];
			$this->beschrijving = urldecode($data['beschrijving']);
			$this->titel = urldecode($data['titel']);
			$this->eigenaar = $data['eigenaar'];
		} else {
			$this->start = 0;
			$this->eind = 0;
			$this->beschrijving = '';
			$this->titel = '';
		}
	}

	/**
     * Geef alle startijden terug
     * @param mixed $start UNIX-timestamp van starttijd
     * @param mixed $eind UNIX-timestamp van eindtijd
     * 
     * @return Array array met agenda ID's. Deze zijn te gebruiken om agenda-objects aan te maken
     */
    public static function getAgendaItems($start, $eind) {
        $db = new Mysql();
        $data = $db->select("SELECT `id` FROM `agenda` WHERE `start` BETWEEN ". $start ." AND ". $eind ." ORDER BY `start` ASC");

        return array_column($data, 'id');
    }


	/**
	 * Slaat het agenda-item op in de database
	 * @return bool Resultaat van de save-operatie
	 */
	function save() {
		$db = new Mysql;
		$data['start'] = $this->start;
		$data['eind'] = $this->eind;
		$data['beschrijving'] = urlencode($this->beschrijving);
		$data['titel'] = urlencode($this->titel);
		$data['eigenaar'] = $this->eigenaar;

		if(isset($this -> id)) {
			foreach($data as $key => $value) {
				$set[] = "`$key` = '$value'";
			}
			$sql = "UPDATE `agenda` SET ". implode(', ', $set) ." WHERE `id` = ". $this->id;
		} else {
			$sql = "INSERT INTO `agenda` (`". implode('`, `', array_keys($data)) ."`) VALUES ('". implode("', '", array_values($data)) ."')";
			$db -> query($sql);

		}
		return $db -> query($sql);
	}
}
?>
