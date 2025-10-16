<?php

class Rooster {
    public int $id;
    public string $naam;
    public int $groep;
    public int $beheerder;
    public int $planner;
    public int $velden;
    public bool $reminder;
    public bool $gelijk;
    public bool $voorganger;
    public bool $opmerking;
    public bool $ouder;
    public bool $partner;
    public bool $tekst;
    public bool $alert;
    public string $mail;
    public string $onderwerp;
    public string $van;
    public string $vanNaam;
    private int $lastChange;

    function __construct($rooster = 0) {
        if($rooster > 0) {            
			$db = new Mysql();
			$data = $db->select("SELECT * FROM `roosters` WHERE `id` = ". $rooster);

            $this->id = $rooster;
            $this->naam = $data['naam'];
            $this->groep = $data['groep'];
            $this->beheerder = $data['beheerder'];
            $this->planner = $data['planner'];
            $this->velden = $data['aantal'];
            $this->reminder = $data['reminder'];
            $this->gelijk = $data['gelijke_diensten'];
            $this->voorganger = $data['voorganger'];
            $this->opmerking = $data['opmerking'];
            $this->ouder = $data['ouder'];
            $this->partner = $data['partner'];
            $this->tekst = $data['text_only'];
            $this->alert = $data['alert'];
            $this->mail = $data['mail'];
            $this->onderwerp = $data['onderwerp'];
            $this->van = $data['mail_afzender'];
            $this->vanNaam = $data['naam_afzender'];
            $this->lastChange = $data['last_change'];
        }        
    }


    public static function getAllRoosters() {
        $db = new Mysql;
        $data = $db->select("SELECT `id` FROM `roosters`");
        
        return $data;
    }

    


    /**
     * @return int ID van het rooster
     */
    function getID() {
        return $this->id;
    }



    /**
     * @return string Naam van het rooster
     */
    function getName() {
        return $this->naam;
    }



    /**
     * @return int ID van het team dat dit rooster beheert
     */
    function getBeheerder() {
        return $this->beheerder;
    }



    /**
     * @return int ID van het team dat dit rooster mag vullen
     */
    function getPlanner() {
        return $this->planner;
    }


    /**
     * @return int ID van het team dat voor dit rooster ingedeeld moet worden
     */
    function getGroep() {
        return $this->groep;
    }



    // /**
    //  * @param int $beheerder ID van het team dat dit rooster beheert
    //  */
    // function setBeheerder(int $beheerder) {
    //     $this->beheerder = $beheerder;
    // }


    
    // /**
    //  * @param int $planner ID van het team dat dit rooster mag vullen
    //  */
    // function setPlanner(int $planner) {
    //     $this->planner = $planner;
    // }


    
    // /**
    //  * @param int $groep ID van het team dat voor dit rooster ingedeeld moet worden
    //  */
    // function setGroep(int $groep) {
    //     $this->groep = $groep;
    // }


    
    // /**
    //  * @param string $name Naam van het rooster
    //  */
    // function setName(string $name) {
    //     $this->naam = $name;
    // }

   /**
     * Sla het rooster op in de MySQL-database
     */
    function save() {
        $db = new Mysql;
        
        if(isset($this -> id)) {
            $db->query("UPDATE `roosters` SET 
            `naam` = ". $this->naam .",
            `groep` = ". $this->groep .",
            `beheerder` = ". $this->beheerder .", 
            `planner` = ". $this->planner .", 
            `aantal` = ". $this->velden .", 
            `reminder` = ". $this->reminder .", 
            `gelijke_diensten` = ". $this->gelijk .", 
            `voorganger` = ". $this->voorganger .", 
            `opmerking` = ". $this->opmerking .", 
            `ouder` = ". $this->ouder .", 
            `partner` = ". $this->partner .", 
            `text_only` = ". $this->tekst .", 
            `alert` = ". $this->alert .", 
            `mail` = ". $this->mail .", 
            `onderwerp` = ". $this->onderwerp .", 
            `mail_afzender` = ". $this->van .", 
            `naam_afzender` = ". $this->vanNaam .", 
            `last_change` = ". time() ." WHERE `id` = ". $this->id);
        } else {            
            $db->query("INSERT INTO `roosters` 
            (`naam`,`groep` ,`beheerder` ,`planner`,`aantal`,`reminder`,`gelijke_diensten`,`voorganger`,`opmerking`,`ouder`,`partner`,`text_only`,`alert`,`mail`,`onderwerp`,`mail_afzender`,`naam_afzender`,`last_change`)
             VALUES
            (". $this->naam .", ". $this->groep .", ". $this->beheerder .", ". $this->planner .", ". $this->velden .", ". $this->reminder .", ". $this->gelijk .", ". $this->voorganger .", ". $this->opmerking .", ". $this->ouder .", ". $this->partner .", ". $this->tekst .", ". $this->alert .", ". $this->mail .", ". $this->onderwerp .", ". $this->van .", ". $this->vanNaam .", ". time() .")");
        }
    }
}

?>