<?php

/**
 * Class voor rooster-object voor de Open Kerk
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
class OpenKerkRooster {
    /**
     * @var int UNIX-timestamp van de begintijd
     */
    public int $start;

    /**
     * @var int UNIX-timestamp van de eindtijd
     */
    public int $eind;

    /**
     * @var array Array met personen die op het rooster staan
     */
    public array $personen;

    /**
     * @var string Opmerking bij deze rooster-regel
     */
    public string $opmerking;

    /**
     * Maak object aan voor Open Kerk Template
     * @param int $start Door startijd (optioneel) mee te geven wordt het object gevuld met data voor dit tijdstip
     */
    function __construct($start = 0) {
        $this->start = 0;
        $this->eind = 0;
        $this->personen = array();
        $this->opmerking = '';

        if($start > 0) {
            $db = new Mysql();

            $data = $db->select("SELECT * FROM `openkerk_rooster` WHERE `tijd` = ". $start, true);

            if(count($data) > 0) {
                $this->start = $data[0]['tijd'];
                $this->eind = $data[0]['eind'];
                $this->personen = array_column($data, 'persoon', 'pos');

                $data = $db->select("SELECT * FROM `openkerk_opmerking` WHERE `tijd` = ". $start);
                if(isset($data['opmerking'])) {
                    $this->opmerking = urldecode($data['opmerking']);
                }
            }
        }
    }

    /**
     * Verwijder OpenKerkRooster-object uit de database
     * @return bool Succesvol ja of nee
     */
    function delete() {
        $succes = true;

        $db = new Mysql();

        # Oude rooster-items verwijderen
        if(!$db->query("DELETE FROM `openkerk_rooster` WHERE tijd = ". $this->start)) {
            $succes = false;
        }

        # Oude rooster-opmerkingen verwijderen
        if(!$db->query("DELETE FROM `openkerk_opmerking` WHERE `tijd` = ". $this->start)) {
            $succes = false;
        }

        return $succes;
    }

    /**
     * Sla OpenKerkRooster-Object op in de database
     * @return bool Succesvol ja of nee
     */
    function save() {
        $this->delete();

        $succes = true;

        $db = new Mysql();

        # Nieuwe rooster-items opslaan
        foreach($this->personen as $pos=>$persoon) {
            $sql = "INSERT INTO `openkerk_rooster` (`tijd`, `eind`, `pos`, `persoon`) VALUES ('". $this->start ."', '". $this->eind ."', '". $pos ."', '". $persoon ."')";
            if(!$db->query($sql))   $succes = false;
        }

        # Nieuwe rooster-opmerkingen opslaan
        if($this->opmerking != '') {
            $sql = "INSERT INTO `openkerk_opmerking` (`tijd`, `opmerking`) VALUES (". $this->start .", '". urlencode($this->opmerking) ."')";
            if(!$db->query($sql))   $succes = false;
        }

        return $succes;
    }


    /**
     * Geef een array terug met alle starttijden vanaf nu.
     * Deze startijden kunnen gebruikt worden om een OpenKerkRooster-object aan te maken
     *
     * @return array Array met alle starttijden
     */
    public static function getAllStarts() {
        $db = new Mysql();
        $data = $db->select("SELECT `tijd` FROM `openkerk_rooster` WHERE `tijd` > ". time() ." GROUP BY `tijd` ORDER BY `tijd` ASC");

        return array_column($data, 'tijd');
    }


    /**
     * Geef alle startijden terug
     * @param mixed $start UNIX-timestamp van starttijd
     * @param mixed $eind UNIX-timestamp van eindtijd
     * 
     * @return Array array met starttijden. Deze zijn te gebruiken om rooster-objects aan te maken
     */
    public static function getStarts($start, $eind) {
        $db = new Mysql();
        $data = $db->select("SELECT `tijd` FROM `openkerk_rooster` WHERE `tijd` BETWEEN ". $start ." AND ". $eind ." GROUP BY `tijd` ORDER BY `tijd` ASC");

        return array_column($data, 'tijd');
    }

    /**
     * Geef alle personen terug die op het rooster staan
     * @param mixed $start UNIX-timestamp van starttijd
     * @param mixed $eind UNIX-timestamp van eindtijd
     * 
     * @return Array array met Member-IDs. Deze zijn te gebruiken om Member-objects aan te maken
     */
    public static function getCrew($start, $eind) {
        $db = new Mysql();
        $data = $db->select("SELECT `persoon` FROM `openkerk_rooster` WHERE `tijd` BETWEEN ". $start ." AND ". $eind ." GROUP BY `persoon` ORDER BY `tijd` ASC");

        return array_column($data, 'persoon');
    }


    /**
     * Geef alle starttijden terug wanneer dit lid op het rooster staat
     * @param mixed $start UNIX-timestamp van starttijd
     * @param mixed $eind UNIX-timestamp van eindtijd
     * @param mixed $persoon Member-ID van persoon
     * 
     * @return Array array met starttijden wanneer dit lid op het rooster staat. Deze zijn te gebruiken om rooster-objects aan te maken.
     */
    public static function getShifts($start, $eind, $persoon) {
        $db = new Mysql();
        $data = $db->select("SELECT `tijd` FROM `openkerk_rooster` WHERE `persoon` = $persoon AND `tijd` BETWEEN $start AND $eind", true);

        return array_column($data, 'tijd');
    }



    /**
     * Geef de laatste starttijd terug
     * Zinvol om te bepalen vanaf wanneer het rooster gevuld moet worden.
     *
     * @return int Laatste starttijd in UNIX-timestamp-format
     */
    public static function getLastStart() {
        $db = new Mysql();
        $sql = "SELECT `tijd` FROM `openkerk_rooster` WHERE `tijd` > ". time() ." ORDER BY `tijd` DESC LIMIT 0,1";
        $data = $db->select($sql);

        if(count($data) == 0) {
            return time();
        } else {
            return $data['tijd']+(24*60*60);
        }
    }

    /**
     * Geeft een array terug met alle gebruikers die in de toekomst op het rooster staan.
     *
     * Deze userID kan gebruikt worden om een Member-object aan te maken
     * @return array Array met userIDs
     */
    public static function getAllUsers() {
        $db = new Mysql();
        $sql = "SELECT `persoon` FROM `openkerk_rooster` WHERE `tijd` > ". time() ." GROUP BY `persoon`";
        $data = $db->select($sql, true);

        return array_column($data, 'persoon');
    }
}

?>