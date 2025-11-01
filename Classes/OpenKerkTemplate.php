<?php

/**
 * Template voor een Open Kerk rooster-moment.
 * Om een Object aan te maken moet een template ID, week (0/1), dag (0..6) en tijdsblok worden gegeven.
 */
class OpenKerkTemplate {
    public int $id;
    public array $enroll;
    public int $week;
    public int $dag;
    public int $tijd;
    public int $positie;
    public array $leden;

    /**
     * Construeer een Open Kerk Template
     * @param int $id ID van de template (default = 0)
     * @param int $week ID van de week (default = 0)
     * @param int $dag ID van de dag (default = 0)
     * @param int $tijd ID van het tijdstip (default = 0)
     */
    function __construct($id = 0, $week = 0, $dag = 0, $tijd = 0) {
        if($id > 0 && $dag > 0 && $tijd > 0) {
            $db = new Mysql();

            $this->id = $id;
            $this->week = $week;
            $this->dag = $dag;
            $this->tijd = $tijd;

            $sql = "SELECT * FROM `openkerk_template` WHERE `template` = ". $this->id ." AND `week` = ". $this->week ." AND `dag` = ". $this->dag ." AND `tijd` = ".  $this->tijd;
            $data = $db->select($sql, true);

            $this->leden = array_column($data, 'persoon', 'pos');
            $this->enroll = array_column($data, 'enroll', 'pos');
        } else {
            $this->id       = $id;
            $this->week     = $week;
            $this->dag      = $dag;
            $this->tijd     = $tijd;
            $this->leden    = array();
            $this->enroll   = array();
        }
    }


    /**
     * Sla de template voor de Open Kerk op
     *
     * @return bool Succesvol ja (True) of niet (False)
     */
    public function save() {
        $db = new Mysql();

        $sql = array();

        $sql[] = "DELETE FROM `openkerk_template` WHERE `template` = ". $this->id  ." AND `week` = ". $this->week ." AND `dag` = ". $this->dag ." AND `tijd` = ".  $this->tijd;

        foreach($this->leden as $pos => $lid) {
            $enroll = $this->enroll[$pos];
            $sql[] = "INSERT INTO `openkerk_template` (`template`, `enroll`, `week`, `dag`, `tijd`, `pos`, `persoon`) VALUES ('". $this->id ."', '". $enroll ."', '". $this->week ."', '". $this->dag ."', '". $this->tijd ."', '". $pos ."', '". $lid ."')";
        }

        $status = true;

        foreach($sql as $query) {
            if(!$db->query($query)) {
                $status = false;
            }
        }

        return $status;
    }

    /**
     * Geef een overzicht van alle templates, inclusief naam en ID die er zijn.
     *
     * @return array Array met id als key en naam als value
     */
    public static function getAllTemplates() {
        $db = new Mysql();

        $sql = "SELECT * FROM `openkerk_template_namen` GROUP BY `id`";
        $data = $db->select($sql);
        return array_column($data, 'naam', 'id');
    }
}

?>