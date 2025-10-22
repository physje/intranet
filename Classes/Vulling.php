<?php

/**
 * Class voor de planning/vulling van een rooster
 */
class Vulling {
    /**
     * @var int Dienst ID
     */
    public int $dienst;
    
    /**
     * @var int Rooster ID
     */
    public int $rooster;
    
    /**
     * @var bool Is het een tekst-rooster (true) of een leden-rooster (false)
     */
    public bool $tekst_only;

    /**
     * @var array Leden die voor deze dienst op het rooster staan (positie => lid ID)
     */
    public array $leden;

    /**
     * @var string Omschrijving wat voor deze dienst op het rooster staat
     */
    public string $tekst;
    
    /**
     * @var string Interne opmerking voor deze dienst
     */
    public string $opmerking;


    /**
     * @param int $dienst ID van de kerkdienst
     * @param int $rooster ID van het rooster  
     */
    function __construct($dienst = 0, $rooster = 0) {
        if($dienst > 0 and $rooster > 0) {
            $db = new Mysql;
            $r = new Rooster($rooster);
            $tekst_only = $r->tekst;
            
            $this->dienst = $dienst;
            $this->rooster = $rooster;
            
            # Is het een tekst-rooster of een leden-rooster?
            if($tekst_only) {                
                $data = $db->select("SELECT `text` FROM `planning_tekst` WHERE `dienst` = ". $this->dienst ." AND `rooster` = ". $this->rooster);
                if(isset($data['text'])) {
                    $this->tekst = $data['text'];
                } else {
                    $this->tekst = '';
                }
                $this->tekst_only = true;
            } else {
                $data = $db->select("SELECT `positie`, `lid` FROM `planning` WHERE `dienst` = ". $this->dienst ." AND `commissie` = ". $this->rooster ." ORDER BY `positie` ASC", true);
                $values = array_column($data, 'lid');
                $keys = array_column($data, 'positie');
                $this->leden = array_combine($keys, $values);              
                $this->tekst_only = false;
            }

            # Opmerking ophalen
            $data = $db->select("SELECT `opmerking` FROM `rooster_opmerkingen` WHERE `rooster` = ". $this->rooster ." AND `dienst` = ". $this->dienst);
            if(isset($data['opmerking'])) {
                $this->opmerking = $data['opmerking'];
            } else {
                $this->opmerking = '';
            }
        }       
    }

    /**
     * @return [type]
     */
    function save() {        
        $db = new Mysql;

        if($this->tekst_only) {
            # Tekst planning opslaan
            $db->query("DELETE FROM `planning_tekst` WHERE `dienst` = ". $this->dienst ." AND `rooster` = ". $this->rooster);
            
            if($this->tekst != '') {
                $db->query("INSERT INTO `planning_tekst` (`dienst`, `rooster`, `text`) VALUES (". $this->dienst .", ". $this->rooster .", '". urlencode($this->tekst) ."')");
            }  
        } else {
            # Leden planning opslaan
            $db->query("DELETE FROM `planning` WHERE `dienst` = ". $this->dienst ." AND `commissie` = ". $this->rooster);

            foreach($this->leden as $positie => $lid) {
                if($lid > 0) {
                    $db->query("INSERT INTO `planning` (`dienst`, `commissie`, `lid`, `positie`) VALUES (". $this->dienst .", ". $this->rooster .", ". $lid .", ". $positie .")");
                }
            }

            # Interne opmerking opslaan
            $db->query("DELETE FROM `rooster_opmerkingen` WHERE `rooster` = ". $this->rooster ." AND `dienst` = ". $this->dienst);
                        
            if(isset($this->opmerking) AND $this->opmerking != '') {                
                $db->query("INSERT INTO `rooster_opmerkingen` (`rooster`, `dienst`, `opmerking`) VALUES (". $this->rooster .", ". $this->dienst .", '". urlencode($this->opmerking) ."')");                
            }
        }        
    }   
}

?>