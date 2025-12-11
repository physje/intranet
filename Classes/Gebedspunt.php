<?php

/**
 * Class voor het beheren van gebedspunten.
 * Een gebedspunt bevat een ID, een dag, maand, jaar en de tekst van het gebedspunt.
 * 
 * Met deze class kunnen gebedspunten worden aangemaakt, opgehaald uit de database of weggeschreven worden naar de database
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
class Gebedspunt {
    /**
     * @var int
     */
    public int $id;
    
    /**
     * @var DateTime
     * @deprecated
     */
    public DateTime $datum;

    /**
     * @var int Dag [0-31] van het gebedspunt
     */
    public int $dag;
    
    /**
     * @var int Maand [1-12] van het gebedspunt
     */
    public int $maand;
    
    /**
     * @var int Jaar [YYYY] van het gebedspunt
     */
    public int $jaar;

    /**
     * @var int UNIX-timestamp van het gebedspunt
     */
    public int $unix;
    
    /**
     * @var string Eigenlijke gebedspunt
     */
    public string $gebedspunt;

    /**
     * Maak een gebedspunt aan.
     * @param int $id Optioneel ID.
     * Als ID bekend is wordt het object met data van dit gebedspunt uit de database gevuld.
     */
    public function __construct($id = 0) {        
        $this->id = $id;
        $this->dag = 0;
        $this->maand = date('m');
        $this->jaar = date('Y');
        $this->gebedspunt = '';

        if($id > 0) {
            $db = new Mysql;

            $sql = "SELECT * FROM `gebed_punten` WHERE `id` = ". $this->id;
            $data = $db->select($sql);

            $this->maand        = substr($data['datum'], 5, 2);
            $this->dag          = substr($data['datum'], 8, 2);
            $this->jaar         = substr($data['datum'], 0, 4);            
            $this->gebedspunt   = urldecode($data['gebedspunt']);
        }
        $this->unix         = mktime(0, 0, 1, $this->maand, $this->dag, $this->jaar);
    }

   /**
    * Vraag gebedspunten op.
    * @param mixed $start Begindatum (YYYY-MM-DD) vanaf waar gezocht moet worden
    * @param mixed $eind Einddatum (YYYY-MM-DD) tot wanneer gezocht moet worden
    * 
    * @return array Array met ID's die gebruikt kunnen worden om een Gebedspunt-object aan te maken
    */
    static function getPunten($start, $eind) {
        $db = new Mysql;
        $sql = "SELECT `id` FROM `gebed_punten` WHERE `datum` BETWEEN '$start' AND '$eind' ORDER BY `datum`";

        $data = $db->select($sql, true);

        return array_column($data, 'id');
    }

    /**
     * Sla het gebedspunt op in de databaee
     * @return bool Succesvol opslaan of niet
     */
    public function save() {
        $db = new Mysql;

        $datum = $this->jaar.'-'.substr('0'.$this->maand, -2).'-'.substr('0'.$this->dag, -2);

        $sql_delete = "DELETE FROM `gebed_punten` WHERE `datum` like '$datum'";
        $query[] = $sql_delete;

        $sql_insert = "INSERT INTO `gebed_punten` (`datum`, `gebedspunt`) VALUES ('". $datum ."', '". urlencode(trim($this->gebedspunt)) ."')";
		$query[] = $sql_insert;
        
        foreach($query as $sql) {
            if(!$db->query($sql))   return false;
        }

        return true;        
    }
}