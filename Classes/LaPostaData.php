<?php

/**
 * Class om de lokale administratie voor LaPosta bij te houden
 */
class LaPostaData {
    public int $id;
    public bool $new;
    public string $status;
    public string $geslacht;
    public string $voornaam;
    public string $tussenvoegsel;
    public string $achternaam;
	public string $mail;
	public string $wijk;
    public string $doop;
    public string $relatie;
	public string $zeventigPlus;
    public int $lastSeen;
    public int $lastChecked;
    private int $deadline;

    public function __construct($id) {
        $db = new Mysql();

        $this->deadline = 13;
				
		if($id > 0) {			
            $this->id = $id;
            
			$data = $db->select("SELECT * FROM `lp_data` WHERE `scipio_id` = ". $this->id, true);

            if(count($data) > 0) {
                $this->status = $data['status'];
                $this->geslacht = $data['geslacht'];
                $this->voornaam = urldecode($data['voornaam']);
                $this->tussenvoegsel = urldecode($data['tussenvoegsel']);
                $this->achternaam = urldecode($data['achternaam']);
	            $this->mail = urldecode($data['mail']);
	            $this->wijk = $data['wijk'];
                $this->relatie = $data['relatie'];
                $this->doop = $data['doop'];
	            $this->zeventigPlus = ($data['70_plus'] == 1 ? true : false);
                $this->new = false;
            } else {
                $this->new = true;
            }
        }
        
    }

    public function isUnique() : bool {
        $db = new Mysql();
        $deadline = mktime ((date('H')-$this->deadline));

        $sql = "SELECT * FROM `lp_data` WHERE `mail` like '". urlencode($this->mail) ."' AND `last_seen` > ". $deadline;

        $data = $db -> select($sql, true);

        if(count($data) == 0) {
            return true;
        } else {
            return false;
        }
        
    }

    public function save() {
        $db = new Mysql();

        $data = $set = array();

        $data['status'] = $this->status;
        $data['geslacht'] = $this->geslacht;
        $data['voornaam'] = urlencode($this->voornaam);
        $data['tussenvoegsel'] = urlencode($this->tussenvoegsel);
        $data['achternaam'] = urlencode($this->achternaam);
        $data['mail'] = urlencode($this->mail);
        $data['wijk'] = $this->wijk;
        $data['relatie'] = $this->relatie;
        $data['doop'] = $this->doop;
        $data['70_plus'] = ($this->zeventigPlus ? '1' : '0');

        if($this->new) {
            $sql = "INSERT INTO `lp_data` (`". implode('`, `', array_keys($data)) ."`) VALUES ('". implode("', '", array_values($data)) ."')";
        } else {
            foreach($data as $key => $value) {
                $set[] = "`$key`='$value'";
            }

            $sql = "UPDATE `lp_data` SET ". implode(', ', $set) ." WHERE scipio_id = ". $this->id;
        }

        return $db -> query($sql);
    }


    static function getOldAdresses($aantal = 4) {
        $db = new Mysql();

        $deadline = mktime ((date('H')-13));
        $sql = "SELECT * FROM `lp_data` WHERE `status` like 'actief' AND `last_seen` < ". $deadline ." LIMIT 0, ". $aantal;

        $data = $db->select($sql, true);

        return array_column($data, 'scipio_id');
    }

}

?>