<?php

/**
 * Class voor de planning/vulling van een rooster.
 *
 * Er zijn 2 types rooster.
 *  - een rooster met tekst per dienst
 *  - een rooster waar één of meerdere personen zijn ingedeeld per dienst.
 *
 * Of het een rooster met tekst, of een rooster zonder tekst is, is te achterhalen met de eigenschap 'text_only'
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
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
     * Construeer een rooster-object
     * Door dienst-ID en rooster-ID mee te geven wordt het object gevuld met data van dat rooster en specifieke dienst
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
                    $this->tekst = urldecode($data['text']);
                } else {
                    $this->tekst = '';
                }
                $this->tekst_only = true;
            } else {
                $data = $db->select("SELECT `positie`, `lid` FROM `planning` WHERE `dienst` = ". $this->dienst ." AND `commissie` = ". $this->rooster ." ORDER BY `positie` ASC", true);
                $this->leden = array_column($data, 'lid', 'positie');
                $this->tekst_only = false;
            }

            # Opmerking ophalen
            $data = $db->select("SELECT `opmerking` FROM `rooster_opmerkingen` WHERE `rooster` = ". $this->rooster ." AND `dienst` = ". $this->dienst);
            if(isset($data['opmerking'])) {
                $this->opmerking = urldecode($data['opmerking']);
            } else {
                $this->opmerking = '';
            }
        }
    }

    /**
     * Opslaan van het rooster-object.
     * @return bool True indien gelukt, False indien een of meer queries mislukt is
     */
    function save() {
        $sql = array();

        if($this->tekst_only) {
            # Tekst planning opslaan
            $sql[] = "DELETE FROM `planning_tekst` WHERE `dienst` = ". $this->dienst ." AND `rooster` = ". $this->rooster;

            if(isset($this->tekst) && $this->tekst != '') {
                $sql[] = "INSERT INTO `planning_tekst` (`dienst`, `rooster`, `text`) VALUES (". $this->dienst .", ". $this->rooster .", '". urlencode($this->tekst) ."')";
            }
        } else {
            # Leden planning opslaan
            $sql[] = "DELETE FROM `planning` WHERE `dienst` = ". $this->dienst ." AND `commissie` = ". $this->rooster;

            foreach($this->leden as $positie => $lid) {
                if($lid > 0) {
                    $sql[] = "INSERT INTO `planning` (`dienst`, `commissie`, `lid`, `positie`) VALUES (". $this->dienst .", ". $this->rooster .", ". $lid .", ". $positie .")";
                }
            }

            # Interne opmerking opslaan
            $sql[] = "DELETE FROM `rooster_opmerkingen` WHERE `rooster` = ". $this->rooster ." AND `dienst` = ". $this->dienst;

            if(isset($this->opmerking) AND $this->opmerking != '') {
                $sql[] = "INSERT INTO `rooster_opmerkingen` (`rooster`, `dienst`, `opmerking`) VALUES (". $this->rooster .", ". $this->dienst .", '". urlencode($this->opmerking) ."')";
            }
        }

        $db = new Mysql;
        $status = true;
        foreach($sql as $query) {
            if(!$db->query($query)) {
                $status = false;
            }
        }

        return $status;
    }

   /**
    * Vraag alle leden op die de komende tijd ergens op het rooster staan.
    * Met name van belang voor het maken van iCal-bestanden
    *
    * @return array met lid-IDs
    */
    static function getAllPlannedMembers() {
        $sql = "SELECT `lid` FROM `planning` GROUP BY `lid`";

        $db = new Mysql();
        $data = $db->select($sql, true);
        return array_column($data, 'lid');
    }


   /**
    * Vraag alle momenten op waarop een lid de komende tijd ergens op een rooster staat.
    * Met name van belang voor het maken van iCal-bestanden
    *
    * @param int $member ID van het lid waar gezocht naar moet worden
    *
    * @return array met dienst-IDs als key, en welk rooster-ID als value
    */
    static function getPlannedTimes4Member($member) {
        $sql = "SELECT `planning`.`dienst`, `planning`.`commissie` FROM `planning`, `kerkdiensten` WHERE `planning`.`dienst` = `kerkdiensten`.`id` AND `kerkdiensten`.`start` > ". mktime(0, 0, 0, date('n')-1) ." AND `lid` = ". $member;

        $db = new Mysql();
        $data = $db->select($sql, true);

        if($data) {
            return array_column($data, 'commissie', 'dienst');
        } else {
            return array();
        }
    }
}

?>