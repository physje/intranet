<?php
/**
 * Class voor het werken met wijken zoals het beheren van het wijkteam en wijkleden
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
class Wijk {
    /**
     * @var string Letter van de wijk
     */
    public string $wijk;

    /**
     * @var array Array met wijkteam-leden. key = lid-id, value = rol
     */
    public array $wijkteam;

    /**
     * @var array Array met wijkleden. Afhankelijk van type
     */
    public array $wijkleden;

    /**
     * @var int Bepaald hoe de array met wijkleden wordt teruggegeven. 1 = adressen, 2 = belijdende, 3 = alle
     */
    public int $type;

    public function __construct() {
        $this->wijk = '';
        $this->type = 0;
        $this->wijkleden = array();
        $this->wijkteam = array();
    }

    /**
     * @return array Geeft een array terug met de leden van het wijkteam en hun rol
     */
    public function getWijkteam() {
        if(count($this->wijkteam) == 0) {
            $db = new Mysql();
            $data = $db -> select("SELECT `rol`, `lid` FROM `wijkteams` WHERE `wijk` like '". $this->wijk ."' ORDER BY `rol`", true);

            $keys = array_column($data, 'lid');
            $values = array_column($data, 'rol');
            $this->wijkteam = array_combine($keys, $values);
        }
        return $this->wijkteam;
    }


    /**
     * Geef alle wijkleden.
     *
     * Door object->type kan gekozen worden voor
     *  - gezinshoofden & zelfstandige (type = 0)
     *  - belijdende leden (type = 1)
     *  - iedereen (type = 2)
     *
     * @return array Array met alle leden van de wijk.
     */
    public function getWijkleden() {
        if(count($this->wijkleden) == 0) {
            $data = array();

            $db = new Mysql();
            $adressen = $db -> select("SELECT `kerk_adres` FROM `leden` WHERE `status` = 'actief' AND `wijk` like '". $this->wijk ."' GROUP BY `kerk_adres` ORDER BY `achternaam`", true);
            $adressen = array_column($adressen, 'kerk_adres');

            foreach($adressen as $adres) {
                if($this->type == 0) {
                    $sql = "SELECT `scipio_id` FROM `leden` WHERE `status` = 'actief' AND `kerk_adres` like '$adres' AND (`relatie` like 'gezinshoofd' OR `relatie` like 'zelfstandig') ORDER BY FIELD(`relatie`,'gezinshoofd') DESC";
                    #$leden = $db -> select("SELECT `scipio_id` FROM `leden` WHERE `status` = 'actief' AND `kerk_adres` like '$adres' AND (`relatie` like 'gezinshoofd' OR `relatie` like 'zelfstandig') ORDER BY FIELD(`relatie`,'gezinshoofd') DESC", true);
                } elseif($this->type == 1) {
                    $sql = "SELECT `scipio_id` FROM `leden` WHERE `status` = 'actief' AND `kerk_adres` like '$adres' AND `belijdenis` like 'belijdend lid' ORDER BY FIELD(`relatie`,'gezinshoofd') DESC";
                    #$leden = $db -> select("SELECT `scipio_id` FROM `leden` WHERE `status` = 'actief' AND `kerk_adres` like '$adres' AND `belijdenis` like 'belijdend lid' ORDER BY FIELD(`relatie`,'gezinshoofd') DESC", true);
                } elseif($this->type == 2) {
                    $sql = "SELECT `scipio_id` FROM `leden` WHERE `status` = 'actief' AND `kerk_adres` like '$adres' ORDER BY FIELD(`relatie`,'gezinshoofd') DESC";
                    #$leden = $db -> select("SELECT `scipio_id` FROM `leden` WHERE `status` = 'actief' AND `kerk_adres` like '$adres' ORDER BY FIELD(`relatie`,'gezinshoofd') DESC", true);
                }
                $leden = $db -> select($sql, true);
                $data = array_merge($data, array_column($leden, 'scipio_id'));
            }
            $this->wijkleden = $data;
        }

        return $this->wijkleden;
    }

    /**
     * Sla het Wijk-object op.
     *
     * @return bool True als opslaan succesvol is, False als er iets fout gegaan is
     */
    public function save() {
        $db = new Mysql();
        $db->query("DELETE FROM `wijkteams` WHERE `wijk` like '". $this->wijk ."'");

        $result = true;
        foreach($this->wijkteam as $key => $value) {
            if($key > 0 && $value > 0) {
                $sql = "INSERT INTO `wijkteams` (`lid`, `rol`, `wijk`) VALUES ('". $key ."', '". $value ."', '". $this->wijk ."')";
                if(!$db->query($sql)) {
                    $result = false;
                }
            }
        }
    }
}
?>