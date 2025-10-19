<?php

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
     * @return [type] Slaat de log entry op in de database
     */
    function save() {
        $db = new Mysql();
        return $db->query("INSERT INTO `log` (`tijd`, `type`, `dader`, `vermomd`, `slachtoffer`, `message`) VALUES (". $this->tijd .", '". $this->type ."', '". (isset($_SESSION['realID']) ? $_SESSION['realID'] : '') ."', '". (isset($_SESSION['fakeID']) ? $_SESSION['fakeID'] : '') ."', '". $this->slachtoffer ."', '". addslashes($this->bericht) ."')");
    }
}