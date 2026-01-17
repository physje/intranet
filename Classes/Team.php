<?php
/**
 * Class voor het beheren van de leden in een team.
 * Het is mogelijk om leden toe te voegen en te verwijderen
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
class Team {
    /**
     * @var int ID van het team
     */
    public int $id;
    /**
     * @var string Naam van het team
     */
    public string $name;

    /**
     * @var int ID van het team die dit team kan beheren/wijzigen
     */
    public int $beheerder;

    /**
     * @var array Array met ID's van leden van dit team
     */
    public array $leden;

    /**
     * @param int $team ID van het reeds bestaande team. Als ID gelijk is aan 0 wordt leeg object aangemaakt
     */
    function __construct(int $team) {
        if($team > 0) {
            $db = new Mysql;
            $details = $db->select("SELECT * FROM `groepen` WHERE `id` = ". $team);
            $groep = $db->select("SELECT `group_member`.`lid` FROM `group_member`, `leden` WHERE `group_member`.`lid` = `leden`.`scipio_id` AND `group_member`.`commissie` = ". $team ." ORDER BY `leden`.`achternaam`", true);

            $this->id = $team;
            $this->name = urldecode($details['naam']);
            $this->beheerder = $details['beheerder'];
            $this->leden = array_column($groep, 'lid');
        } else {
            $this->id = 0;
            $this->name = '';
            $this->beheerder = 1;
            $this->leden = array();
        }
    }


    /**
     * Geef array met alle ID's van teams die bestaan
     * @return Array array met team-ID's
     */
    public static function getAllTeams() : array {
        $db = new Mysql;
        $data = $db->select("SELECT `id` FROM `groepen` ORDER BY `naam`");

        return array_column($data, 'id');
    }



    /**
     * Verwijder alle leden zodat je met een 'schone lei' begint
     */
    function emptyLeden() {
        $this->leden = array();
    }



    /**
     * Voeg lid toe aan groep
     * @param int $lid ID van het lid
     */
    function addLid(int $lid) {
        if(!in_array($lid, $this->leden)) {
            $this->leden[] = $lid;
        }
    }



    /**
     * Verwijder lid uit groep
     * @param int $lid ID van het lid
     */
    function removeLid(int $lid) {
        $key = array_search($lid, $this->leden);
        unset($this->leden[$key]);
    }



    /**
     * Sla het team op in de MySQL-database
     * @return bool True indien gelukt, False indien een of meer queries mislukt is
     */
    function save() {
        $db = new Mysql;

        if($this -> id > 0) {
            $sql[] = "UPDATE `groepen` SET `naam` = '". urlencode($this->name) ."', `beheerder` = ".$this->beheerder." WHERE `id` = ". $this->id;
        } else {
            $sql[] = "INSERT INTO `groepen` (`naam`, `beheerder`) VALUES ('". urlencode($this->name) ."', ". $this->beheerder .")";
        }

        if(isset($this -> leden)) {
            $sql[] = "DELETE FROM `group_member` WHERE `commissie` = ". $this->id;

            foreach($this->leden as $lid) {
                $sql[] = "INSERT INTO `group_member` (`commissie`, `lid`) VALUES (". $this->id .", $lid)";
            }
        }

        $status = true;
        foreach($sql as $query) {
            if(!$db->query($query)) {
                $status = false;
            }
        }

        return $status;

    }

    /**
     * Verwijder het team en bijbehorende rooster uit de database
     * @return bool True indien gelukt, False indien een of meer queries mislukt is
     */
    function delete() {
        $db = new Mysql();

        $sql[] = "DELETE FROM `groepen` WHERE `id` = ". $this->id;
        $sql[] = "DELETE FROM `group_member` WHERE `commissie` = ". $this->id;
        $sql[] = "DELETE FROM `roosters` WHERE `groep` = ". $this->id;

        $status = true;
        foreach($sql as $query) {
            if(!$db->query($query)) {
                $status = false;
            }
        }

        return $status;
    }
}
?>