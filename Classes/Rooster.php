<?php

class Rooster {
    /**
     * @var int ID van het rooster
     */
    public int $id;

    /**
     * @var string Naam van het rooster
     */
    public string $naam;

    /**
     * @var int ID van het team dat voor dit rooster ingedeeld moet worden
     */
    public int $groep;

    /**
     * @var int ID van het team dat dit rooster beheert
     */
    public int $beheerder;

    /**
     * @var int ID van het team dat dit rooster mag plannen/vullen
     */
    public int $planner;

    /**
     * @var int Aantal velden in het rooster
     */
    public int $velden;

    /**
     * @var bool Moet er een reminder gestuurd worden of niet
     */
    public bool $reminder;

    /**
     * @var int Waarde van het gelijke diensten veld (0=geen, 1=tweede, 2=ochtend, 3=middag/avond, 4=middag, 5=avond)
     */
    public int $gelijk;

    /**
     * @var bool Moet de voorganger tijdens het maken van het rooster getoond worden of niet
     */
    public bool $voorganger;

    /**
     * @var bool Moet er een interne opmerking gemaakt kunnen worden of niet
     */
    public bool $opmerking;

    /**
     * @var bool Moet de ouder in CC worden meegenomen bij de reminder-mail
     */
    public bool $ouder;

    /**
     * @var bool Moet de partner in CC worden meegenomen bij de reminder-mail
     */
    public bool $partner;

    /**
     * @var bool Is dit een tekst-only rooster of niet
     */
    public bool $tekst;

    /**
     * @var int Hoeveek week van te voren moet er een alert gestuurd worden bij bijna aflopen van het rooster
     */
    public int $alert;

    /**
     * @var string Mailtekst voor de remindermail
     */
    public string $mail;

    /**
     * @var string Onderwerp van de remindermail
     */
    public string $onderwerp;

    /**
     * @var string Afzenderadres van de remindermail
     */
    public string $van;

    /**
     * @var string Naam van de afzender van de remindermail
     */
    public string $vanNaam;

    private $lastChange;

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
            $this->voorganger = ($data['voorganger'] == 1 ? true : false);
            $this->opmerking = ($data['opmerking'] == 1 ? true : false);
            $this->ouder = ($data['ouder'] == 1 ? true : false);
            $this->partner = ($data['partner'] == 1 ? true : false);
            $this->tekst = ($data['text_only'] == 1 ? true : false);
            $this->alert = $data['alert'];
            $this->mail = $data['mail'];
            $this->onderwerp = $data['onderwerp'];
            $this->van = $data['mail_afzender'];
            $this->vanNaam = $data['naam_afzender'];
            $this->lastChange = $data['last_change'];
        }        
    }


    /**
     * @return array Geeft een array terug met ID's van alle roosters in de database
     */
    public static function getAllRoosters() {
        $db = new Mysql;
        $data = $db->select("SELECT `id` FROM `roosters`");
        
        return array_column($data, 'id');
    }


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