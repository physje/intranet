<?php
class Kerkdienst {
    public int $dienst;
    public bool $actief;
    public int $start;
    public int $eind;
    public int $voorganger;
    public string $collecte_1;
    public string $collecte_2;
    public string $opmerking;
    public bool $ruiling;
    public bool $specialeDienst;
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
            $this->ruiling = $data['ruiling'];
            $this->specialeDienst = $data['speciaal'];
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
        $db = new Mysql;
        $data = $db->select("SELECT `id` FROM `kerkdiensten` WHERE `actief` = '1' AND `start` BETWEEN $startTijd AND $eindTijd ORDER BY `eind` ASC");
        
        return $data;
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