<?php
/**
 * Class voor het beheren van een team/groep/commissie.
 */
class TeamTemplate {
    private $teamID;
    public $name;
    public $beheerder;
    
    /**
     * @param int $id ID van het reeds bestaande team. Als ID niet gedefinieert is of gelijk aan 0 wordt leeg object aangemaakt
     */
    function __construct(int $id = 0) {
        if($id > 0) {
            $db = new Mysql;
            $data = $db->select("SELECT * FROM `groepen` WHERE `id` = ". $id);
            
            $this->teamID = $data['id'];
            $this->name = $data['naam'];
            $this->beheerder = $data['beheerder'];
        } else {
            $this->teamID = NULL;
            $this->name = NULL;
            $this->beheerder = NULL;
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
        return $this->teamID;
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
     * Sla het team op in de MySQL-database
     */
    function save() {
        $db = new Mysql;

        if($this -> teamID == null) {            
            $db->query("INSERT INTO `groepen` (`naam`, `beheerder`) VALUES (". $this->name .", ". $this->beheerder .")");
        } else {            
            $db->query("UPDATE `groepen` SET `naam` = ".$this->name.", `beheerder` = ".$this->beheerder." WHERE `id` = ". $this->teamID);
        }
    }
}
?>