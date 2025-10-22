<?php
class Wijk {    
    public string $wijk;
    public array $wijkteam;
    public array $wijkleden;
    public int $type;
    
    public function __construct() {
        $this->wijk = '';
        $this->type = 0;
    }

    /**
     * @return array Geeft een array terug met de leden van het wijkteam en hun rol
     */
    public function getWijkteam() {
        if(!isset($this->wijkteam)) {
            $db = new Mysql();
            $data = $db -> select("SELECT `rol`, `lid` FROM `wijkteams` WHERE `wijk` like '". $this->wijk ."' ORDER BY `rol`", true);        
            $keys = array_column($data, 'lid');
            $values = array_column($data, 'rol');
            $this->wijkteam = array_combine($keys, $values);
        }
        return $this->wijkteam;
    }


    public function getWijkleden() {
        if(!isset($this->wijkleden)) {
            $db = new Mysql();
            $adressen = $db -> select("SELECT `kerk_adres` FROM `leden` WHERE `status` = 'actief' AND `wijk` like '". $this->wijk ."' GROUP BY `kerk_adres` ORDER BY `achternaam`");        
            
            foreach($adressen as $adres) {
                if($this->type == 0) {
                    $leden = $db -> select("SELECT `scipio_id` FROM `leden` WHERE `status` = 'actief' AND `kerk_adres` like '$adres' AND (`relatie` like 'gezinshoofd' OR `relatie` like 'zelfstandig') ORDER BY FIELD(`relatie`,'gezinshoofd') DESC", true);
                } elseif($this->type == 1) {
                    $leden = $db -> select("SELECT `scipio_id` FROM `leden` WHERE `status` = 'actief' AND `kerk_adres` like '$adres' AND `belijdenis` like 'belijdend lid' ORDER BY FIELD(`relatie`,'gezinshoofd') DESC", true);
                } elseif($this->type == 2) {
                    $leden = $db -> select("SELECT `scipio_id` FROM `leden` WHERE `status` = 'actief' AND `kerk_adres` like '$adres' ORDER BY FIELD(`relatie`,'gezinshoofd') DESC", true);
                }
                $data[$adres][] = array_column($leden, 'scipio_id');
            }
            $this->wijkleden = $data;
        }

        return $this->wijkleden;    
    }
}
?>