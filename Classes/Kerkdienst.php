<?php

/**
 * Class voor een kerkdienst. Denk daarbij aan starttijd, eindtijd, collectes, voorganger, etc.
 */
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
     * @var string Liturgie van de dienst
     */
    public string $liturgie;

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


    /**
     * Maak een Kerkdienst-object aan
     * Door een dienst-ID (optioneel) op te geven wordt het object gevuld met data van die dienst
     * @param int $dienst ID van de dienst
     */
    function __construct(int $dienst = 0) {
        if($dienst > 0) {
            $db = new Mysql;
            $data = $db->select("SELECT * FROM `kerkdiensten` WHERE `id` = ". $dienst);

            $this->dienst = $dienst;
            $this->actief = ($data['actief'] == 1 ? true : false);
            $this->start = $data['start'];
            $this->eind = $data['eind'];
            $this->voorganger = $data['voorganger'];
            $this->collecte_1 = urldecode($data['collecte_1']);
            $this->collecte_2 = urldecode($data['collecte_2']);
            $this->opmerking = urldecode($data['opmerking']);
            $this->liturgie = urldecode($data['liturgie']);            
            $this->ruiling = ($data['ruiling'] == 1 ? true : false);
            $this->specialeDienst = ($data['speciaal'] == 1 ? true : false);
            $this->declaratieStatus = $data['declaratie_status'];
        } else {
            $this->actief = true;
            $this->start = mktime(9,0,0,date("n"),date("j")+1, date("Y"));
            $this->eind = mktime(9,30,0,date("n"),date("j")+1, date("Y"));
            $this->voorganger = 0;
            $this->collecte_1 = '';
            $this->collecte_2 = '';
            $this->liturgie = '';
            $this->opmerking = '';
            $this->ruiling = false;
            $this->specialeDienst = false;
            $this->declaratieStatus = 0;
        }
    }


    /**
     * Methode om de kerkdiensten in het tijdsblok tussen startTijd en eindTijd op te vragen
     *
     * @param int $startTijd Unix-tijd van het startmoment; 0 = huidige moment
     * @param int $eindTijd Unix-tijd van de eindtijd; 0 = 1 jaar vooruit
     * @return Array met ID's van alle kerkdiensten
     */
    public static function getDiensten(int $startTijd, int $eindTijd) {
        if($startTijd == 0) $startTijd = time();
        if($eindTijd == 0)  $eindTijd = time()+(365*24*60*60);

        $db = new Mysql;
        $data = $db->select("SELECT `id` FROM `kerkdiensten` WHERE `actief` = '1' AND `start` BETWEEN $startTijd AND $eindTijd ORDER BY `eind` ASC", true);

        return array_column($data, "id");
    }


    /**
     * Sla het Kerkdienst-object op in de database
     * @return bool Succesvol opgeslagen of niet
     */
    function save() {
        $db = new Mysql;

        $data['actief'] = $this->actief;
        $data['start'] = $this->start;
        $data['eind'] =  $this->eind;
        $data['voorganger'] = $this->voorganger;
        $data['collecte_1'] = urlencode($this->collecte_1);
        $data['collecte_2'] = urlencode($this->collecte_2);
        $data['liturgie'] = urlencode($this->liturgie);
        $data['opmerking'] = urlencode($this->opmerking);
        $data['ruiling'] = $this->ruiling;
        $data['speciaal'] = $this->specialeDienst;
        $data['declaratie_status'] = $this->declaratieStatus;

        if(isset($this -> dienst)) {
            foreach($data as $key => $value) {
                $set[] = "`$key` = '$value'";
            }
            $sql = "UPDATE `kerkdiensten` SET ". implode(', ', $set) ." WHERE `id` = ". $this->dienst;
        } else {
            $sql = "INSERT INTO `kerkdiensten` (`". implode('`, `', array_keys($data)) ."`) VALUES ('". implode("', '", array_values($data)) ."')";
        }

        return $db -> query($sql);
    }
}
?>