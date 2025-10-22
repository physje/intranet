<?php

class Kerkdienst {
    /**
     * @var int ID van de kerkdienst
     */
    public int $dienst;
    /**
     * @var bool Is de kerkdienst actief of niet
     */
    public bool $actief;    
    /**
     * @var int Starttijd van de kerkdienst in UNIX-tijd
     */
    public int $start;
    /**
     * @var int Eindtijd van de kerkdienst in UNIX-tijd
     */
    public int $eind;
    /**
     * @var int ID van de voorganger
     */
    public int $voorganger;
    /**
     * @var string Omschrijving 1ste collecte
     */
    public string $collecte_1;
    /**
     * @var string Omschrijving 2de collecte
     */
    public string $collecte_2;
    /**
     * @var string Interne opmerking bij de kerkdienst
     */
    public string $opmerking;
    /**
     * @var bool Is dit een geruilde kerkdienst of niet
     */
    public bool $ruiling;
    /**
     * @var bool Is dit een speciale dienst of niet
     */
    public bool $specialeDienst;
    /**
     * @var int Declaratie-status van de voorganger van deze kerkdienst
     */ 
    public int $declaratieStatus;

    function __construct($dienst = 0)
    {
        if($dienst > 0) {
            $db = new Mysql;
            $data = $db->select("SELECT * FROM `kerkdiensten` WHERE `id` = ". $dienst);

            $this->dienst = $dienst;
            $this->actief = $data['actief'];
            $this->start = $data['start'];
            $this->eind = $data['eind'];
            $this->voorganger = $data['voorganger'];
            $this->collecte_1 = urldecode($data['collecte_1']);
            $this->collecte_2 = urldecode($data['collecte_2']);
            $this->opmerking = urldecode($data['opmerking']);
            $this->ruiling = ($data['ruiling'] == 1 ? true : false);
            $this->specialeDienst = ($data['speciaal'] == 1 ? true : false);
            $this->declaratieStatus = $data['declaratie_status'];
        } else {
            $this->start = time()+300;
            $this->eind = $this->start + 3600;
        }       
    }


    /**
     * Methode om de kerkdiensten in het tijdsblok tussen startTijd en eindTijd op te vragen
     * 
     * @param int $startTijd Unix-tijd van het startmoment
     * 
     * @param int $eindTijd Unix-tijd van de eindtijd
     * 
     * @return Array met ID's van alle kerkdiensten
     */
    public static function getDiensten(int $startTijd, int $eindTijd) {
        if($startTijd == 0) $startTijd = time();
        if($eindTijd == 0)  $eindTijd = time()+(365*24*60*60);
        
        $db = new Mysql;
        $data = $db->select("SELECT `id` FROM `kerkdiensten` WHERE `actief` = '1' AND `start` BETWEEN $startTijd AND $eindTijd ORDER BY `eind` ASC", true);

        return array_column($data, "id");
    }


    function save() {
        $db = new Mysql;

        if(isset($this -> dienst)) {
            $db -> query("UPDATE `kerkdiensten` SET 
                `actief` = ". $this->actief .",
                `start` = ". $this->start .",
                `eind` = ". $this->eind .",
                `voorganger` = ". $this->voorganger .",
                `collecte_1` = ". urlencode($this->collecte_1) .",
                `collecte_2` = ". urlencode($this->collecte_2) .",
                `opmerking` = ". urlencode($this->opmerking) .",
                `ruiling` = ". $this->ruiling .",
                `speciaal` = ". $this->specialeDienst .",
                `declaratie_status` = ". $this->declaratieStatus ."            
                WHERE `id` = ". $this->dienst);
        } else {
            $db -> query("INSERT INTO `kerkdiensten` (`start`, `eind`) VALUES (". $this->start .",". $this->eind .")");
        }
    }




}
?>