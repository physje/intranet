<?php

/**
 * Class voor agenda-items
 */
class Agenda {
	public int $id;
	public int $start;
	public int $eind;
	public string $titel;
	public string $beschrijving;
	public int $eigenaar;

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

	function save() {
		$db = new Mysql;
		$data['start'] = $this->start;
		$data['eind'] = $this->eind;
		$data['beschrijving'] = urlencode($this->beschrijving);
		$data['titel'] = urlencode($data['titel']);
		$data['eigenaar'] = $this->eigenaar;

		if(isset($this -> id)) {
			foreach($data as $key => $value) {
				$set[] = "`$key` = '$value'";
			}
			$sql = "UPDATE `agenda` SET ". implode(', ', $set) ." WHERE `id` = ". $this->id;
			return $db -> query($sql);
		} else {
			$sql = "INSERT INTO `agenda` (`". implode('`, `', array_keys($data)) ."`) VALUES ('". implode("', '", array_values($data)) ."')";
			$db -> query($sql);
			return mysqli_insert_id($db->connection);
		}
	}
}
?>
