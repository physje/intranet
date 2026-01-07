<?php

/**
 * Eenvoudige klasse om een nieuw boeknummer aan te maken
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
class Boeknummer {
    /**
     * @var int Jaar van het boeknummer
     */
    public int $jaar;
    
    /**
     * @var int Volgnummer binnen het jaar
     */
    public int $volgnummer;
    
    /**
     * @var string Volledig boeknummer (jaar + volgnummer 0001)
     */
    public string $nummer;

    /**
     * @param int $jaar Jaar voor het boeknummer, standaard huidig jaar
     */
    public function __construct($jaar = 0) {
        $db = new Mysql;

        if($jaar == 0) {
            $this->jaar = date('Y');
        } else {
            $this->jaar = $jaar;
        }

        $sql_jaar = "SELECT `volgnummer` FROM `eb_boekstuk` WHERE `jaar` = ". $this->jaar;
        $data = $db->select($sql_jaar);

        if(count($data) > 0) {
            $this->volgnummer = ($data['volgnummer']+1);
        } else {
            $this->volgnummer = 1;
        }

        $this->nummer = $this->jaar . substr('0000'.$this->volgnummer, -4);

        $this->save();
    }

    /**
     * Sla het boeknummer op.
     * @return bool Resultaat van de save actie
     */
    public function save() {
        $db = new Mysql;

        if($this->volgnummer == 1) {
            $query = "INSERT INTO `eb_boekstuk` (`jaar`, `volgnummer`) VALUES (". $this->jaar .", ". $this->volgnummer .")";
        } else {
            $query = "UPDATE `eb_boekstuk` SET `volgnummer` = '". $this->volgnummer ."' WHERE `jaar` = ". $this->jaar;
        }
        
        return $db->query($query);
    }
}

?>