<?php

/**
 * Voor het loggen van acties op de site
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
class Logging {
    /**
     * @var int ID van de log entry
     */
    public int $id;

    /**
     * @var int Tijdstip van de log entry in UNIX-tijd
     */
    public int $tijd;

    /**
     * @var string Type log entry (info, debug, error)
     */
    public string $type;

    /**
     * @var int ID van de dader
     */
    public int $dader;

    /**
     * @var int ID van de persoon iendien gebeurd onder vermomming
     */
    public int $vermomd;

    /**
     * @var int ID van het slachtoffer
     */
    public int $slachtoffer;

    /**
     * @var string Bericht van de log entry
     */
    public string $bericht;



    /**
     * Maak een logging-object aan
     * Default niveau is 'info'
     * @param int $id Indien ID van de log entry bekend is, worden de gegevens uit de database geladen
     */
    function __construct($id = 0) {
        if($id != 0) {
            $db = new Mysql();
			$data = $db->select("SELECT * FROM `leden` WHERE `scipio_id` = ". $id);

            $this->id = $data['id'];
            $this->tijd = $data['tijd'];
            $this->type = $data['type'];
            $this->dader = $data['dader'];
            $this->vermomd = $data['vermomd'];
            $this->slachtoffer = $data['slachtoffer'];
            $this->bericht = urldecode($data['message']);
        } else {
            $this->tijd = time();
            $this->type = 'info';
            $this->slachtoffer = 0;
        }
    }


    /**
     * Slaat het log-object op in de database.
     * @return bool Succesvol of niet
     */
    function save() {
        $db = new Mysql();
        return $db->query("INSERT INTO `log` (`tijd`, `type`, `dader`, `vermomd`, `slachtoffer`, `message`) VALUES (". $this->tijd .", '". $this->type ."', '". (isset($_SESSION['realID']) ? $_SESSION['realID'] : '') ."', '". (isset($_SESSION['fakeID']) ? $_SESSION['fakeID'] : '') ."', '". $this->slachtoffer ."', '". addslashes($this->bericht) ."')");
    }



    /**
     * Vraag logging op die voldoet aan de filter
     * @param int $start Starttijd in UNIX-timestamp
     * @param int $end Eindtijd in UNIX-timestamp
     * @param array $types Array met types (error, info, debug)
     * @param int $dader Member-ID van de persoon die de handeling doet
     * @param int $slachtoffer Member-ID van de persoon waar de handeling betrekking op heeft
     * @param string $message Tekst die in de logging moet voorkomen
     * @param int $aantal Maximaal aantal log-items
     *
     * @return array Log-items die voldoen aan het filter
     */
    public static function getLogging(int $start, int $end, array $types, int $dader, int $slachtoffer, string $message, int $aantal) {
        if($dader != 0) {
            $where[] = "`dader` = $dader";
        }

        if($slachtoffer!= 0) {
            $where[] = "`slachtoffer` = $slachtoffer";
        }

        if(count($types) > 0) {
            foreach($types as $type) {
                $temp[] = "`type` like '$type'";
            }
            $where[] = '('. implode(" OR ", $temp) .')';
        }

        if($message != '') {
            $where[] = "(`message` like '%$message%' OR `message` like '$message%' OR `message` like '%$message')";
        }

        $where[] = "`tijd` BETWEEN $start AND $end";

        $query = "SELECT * FROM `log` WHERE ". implode(" AND ", $where) ." LIMIT 0, $aantal";

        $db = new Mysql();
        $data = $db->select($query, true);

        return $data;
    }
}

?>