<?php

/**
 * Class om de lokale administratie voor LaPosta bij te houden
 */
class LaPostaData {
    /**
     * @var int ID in Scipio
     */
    public int $id;
    
    /**
     * @var bool Komt deze voor in de lokale administratie (false) of is deze nieuw (true)
     */
    public bool $new;
    
    /**
     * @var string Status van dit lid (actief, uitgeschreven, vertrokken, etc.)
     */
    public string $status;
    
    /**
     * @var string Geslacht (M/V)
     */
    public string $geslacht;
    
    /**
     * @var string Voornaam
     */
    public string $voornaam;
    
    /**
     * @var string Tussenvoegsel
     */
    public string $tussenvoegsel;
    
    /**
     * @var string Achternaam
     */
    public string $achternaam;
	
    /**
	 * @var string E-mailadres
	 */
	public string $mail;
	
    /**
	 * @var string Wijk
	 */
	public string $wijk;
    
    /**
     * @var string Doop- of belijdend-lid
     */
    public string $doop;
    
    /**
     * @var string Welke relatie heeft deze persoon (gehuwd, alleenstaand ed)
     */
    public string $relatie;
	
    /**
	 * @var string Is dit lid 70+
	 */
	public string $zeventigPlus;
    
    /**
     * @var int UNIX-tijd waarop dit lid voor het laatst gezien is
     */
    public int $lastSeen;
    
    /**
     * @var int UNIX-tijd waarop dit lid voor het laatst gecontroleerd is
     */
    public int $lastChecked;
    
    /**
     * @var int Aantal uur nadat iemand voor het laatst gezien is, hij/zij als uitgeschreven beschouwd moet worden
     */
    private int $deadline;

    /**
     * Creer een LaPostaData-object met informatie vanuit de lokale LaPosta-database
     * Als ID niet bestaat of niet bekend is, wordt de eigenschap 'new' op True gezet
     * @param mixed $id
     */
    public function __construct(int $id = 0) {
        $db = new Mysql();

        $this->deadline = 13;
				
		if($id > 0) {			
            $this->id = $id;
            $this->new = true;
            
			$data = $db->select("SELECT * FROM `lp_data` WHERE `scipio_id` = ". $this->id);

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
                $this->lastSeen = $data['last_seen'];
                $this->lastChecked = $data['last_checked'];
                $this->new = false;
            }
        }
        
    }

    /**
     * Controleer of dit mailadres recent nog door een actief lid gebruikt wordt.
     * Met name van belang bij echtparen waarbij bv de een overlijd en de ander het mailadres overneemt.
     * Dan moet het mailadres niet uitgeschreven worden
     * @return bool Is het adres uniek/wordt maar door 1 iemand gebruikt?
     */
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

    /**
     * Sla het LaPostaData-object op in de database
     * @return bool Succesvol of niet
     */
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

            $sql = "UPDATE `lp_data` SET ". implode(', ', $set) ." WHERE `scipio_id` = ". $this->id;
        }

        return $db -> query($sql);
    }


   /**
    * Geef een array terug met alle mailadressen die actief zijn, maar niet gezien zijn.
    * @param int $aantal Maximaal aantal mailadressen (om de LaPosta-API niet te overbelasten)
    * 
    * @return array Array met ID's van 'oude' adressen
    */
    static function getOldAdresses($aantal = 4) {
        $db = new Mysql();

        $deadline = mktime ((date('H')-13));
        $sql = "SELECT * FROM `lp_data` WHERE `status` like 'actief' AND `last_seen` < ". $deadline ." LIMIT 0, ". $aantal;

        $data = $db->select($sql, true);

        return array_column($data, 'scipio_id');
    }

}

?>