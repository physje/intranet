<?php
/**
 * Class voor het beheren van de leden in een team.
 * Het is mogelijk om leden toe te voegen en te verwijderen
*/
class Team {
    public int $team;
    public string $name;
    public int $beheerder;
    public array $leden;
    
    /**
     * @param int $team ID van het reeds bestaande team. Als ID gelijk is aan 0 wordt leeg object aangemaakt
     */
    function __construct(int $team) {
        if($team > 0) {
            $db = new Mysql;
            $details = $db->select("SELECT * FROM `groepen` WHERE `id` = ". $team);
            $groep = $db->select("SELECT * FROM `group_member` WHERE `commissie` = ". $team, true);
            
            $leden = array();
            foreach($groep as $row) {                
                $leden[] = $row['lid'];
            }

            $this->team = $team;
            $this->name = $details['naam'];
            $this->beheerder = $details['beheerder'];
            $this->leden = $leden;            
        } else {
            return false;
        }
    }


    /**
     * @return Array met ID's van alle teams
     */
    public static function getAllTeams() {
        $db = new Mysql;
        $data = $db->select("SELECT `id` FROM `groepen`");
        
        return $data;
    }
    


    /**
     * @return int ID van het team
     */
    function getID() {
        return $this->team;
    }



    /**
     * @return string Naam van het team
     */
    function getName() {
        return $this->name;
    }



    /**
     * @return int ID van het team dat dit team beheert
     */
    function getBeheerder() {
        return $this->beheerder;
    }



    /**
     * @param int $beheerder ID van het team dat dit team beheert
     */
    function setBeheerder(int $beheerder) {
        $this->beheerder = $beheerder;
    }
    

    
    /**
     * @param string $name Naam van het team
     */
    function setName(string $name) {
        $this->name = $name;
    }



    /**
     * Verwijder alle leden zodat je met een 'schone lei' begint
     */
    function emptyLeden() {
        $this->leden = array();
    }



    /**
     * @param int $lid ID van het lid wat aan de groep moet worden toegevoegd
     */
    function addLid(int $lid) {
        if(!in_array($lid, $this->leden)) {
            $this->leden[] = $lid;
        }        
    }



    /**
     * @param int $lid ID van het lid wat uit de groep verwijderd moet worden
     */
    function removeLid(int $lid) {
        $key = array_search($lid, $this->leden);
        unset($this->leden[$key]);
    }



    /**
     * Sla het team op in de MySQL-database
     */
    function save() {
        $db = new Mysql;
        
        if(isset($this -> team)) { 
            $db->query("UPDATE `groepen` SET `naam` = ".$this->name.", `beheerder` = ".$this->beheerder." WHERE `id` = ". $this->team);            
        } else {            
            $db->query("INSERT INTO `groepen` (`naam`, `beheerder`) VALUES (". $this->name .", ". $this->beheerder .")");
        }

        if(isset($this -> leden)) {
            $db->query("DELETE FROM `group_member` WHERE `commissie` = ". $this->team);

            foreach($this->leden as $lid) {
                $db->query("INSERT INTO `group_member` (`commissie`, `lid`) VALUES (". $this->team .", $lid)");
            }
        }

    }
}
?>