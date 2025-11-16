<?php
/**
 * Class om een voorganger toe te voegen, wijzigen of te tonen
 */
class Voorganger {
    /**
     * @var int $id ID van de voorganger
     */
    public int $id;

    /**
     * @var bool $active Is de voorganger actief of niet
     */
    public bool $active;

    /**
     * @var string $aanhef Aanhef van de voorganger (bijv. "ds.", "dr.", etc.)
     */
    public string $aanhef;

    /**
     * @var string $initialen Initialen van de voorganger
     */
    public string $initialen;

    /**
     * @var string $voornaam Voornaam van de voorganger
     */
    public string $voornaam;

    /**
     * @var string $tussenvoegsel Tussenvoegsel bij de achternaam
     */
    public string $tussenvoegsel;

    /**
     * @var string $achternaam Achternaam van de voorganger
     */
    public string $achternaam;

    /**
     * @var string $telefoon Telefoonnummer van de voorganger
     */
    public string $telefoon;

    /**
     * @var string $mobiel Mobiel nummer van de voorganger
     */
    public string $mobiel;

    /**
     * @var string $preekvoorziener Naam van de preekvoorziener
     */
    public string $preekvoorziener;

    /**
     * @var string $preekvoorziener_telefoon Telefoonnummer van de preekvoorziener
     */
    public string $preekvoorziener_telefoon;

    /**
     * @var string $mail E-mailadres van de voorganger
     */
    public string $mail;

    /**
     * @var string $plaats Plaats waar de voorganger werkt
     */
    public string $plaats;

    /**
     * @var string $denominatie Denominatie van de voorganger (NKG, CGK, etc.)
     */
    public string $denominatie;

    /**
     * @var string $stijl Aanspreekstijl van de voorganger (formeel, informeel, etc.)
     */
    #public string $stijl;

    public bool $vousvoyeren;

    /**
     * @var string $opmerkingen Opmerkingen over de voorganger
     */
    public string $opmerkingen;

    /**
     * @var string $hash Unieke hash voor de voorganger
     */
    public string $hash;

    /**
     * @var bool $aandachtspunt Moet de voorganger de aandachtspunten voor de dienst ontvangen
     */
    public bool $aandachtspunt;

    /**
     * @var bool $declaratie Mag de voorganger declareren
     */
    public bool $declaratie;

    /**
     * @var bool $reiskosten Mag de voorganger reiskosten declareren
     */
    public bool $reiskosten;

    /**
     * @var int $nameType Type van naamweergave voor de voorganger
     */
    public int $nameType;

    public float $honorarium;
    public float $honorarium_oud;
    public float $honorarium_special;
    public float $km_vergoeding;
    public int $boekhoud_id;

    public int $last_voorgaan;
    public int $last_aandacht;
    public int $last_data;

    /**
     * @param int $id ID van de voorganger
     */
    function __construct(int $id = 0) {
        if($id > 0) {
            $db = new Mysql;
            $data = $db->select("SELECT * FROM `predikanten` WHERE `id` = ". $id);

            #var_dump($data);

            $this->id = $id;
            $this->active = ($data['actief'] == 1 ? true : false);
            $this->aanhef = $data['titel'];
            $this->initialen = urldecode($data['initialen']);
            $this->voornaam = urldecode($data['voornaam']);
            $this->tussenvoegsel = urldecode($data['tussen']);
            $this->achternaam = urldecode($data['achternaam']);
            $this->telefoon = $data['telefoon'];
            $this->mobiel = $data['mobiel'];
            $this->preekvoorziener = urldecode($data['naam_pv']);
            $this->preekvoorziener_telefoon = $data['tel_pv'];
            $this->mail = urldecode($data['mail']);
            $this->plaats = urldecode($data['plaats']);
            $this->denominatie = urldecode($data['kerk']);
            #$this->stijl = ($data['stijl'] == 1 ? true : false);
            $this->vousvoyeren = ($data['stijl'] == 1 ? true : false);
            $this->opmerkingen = urldecode($data['opmerking']);
            $this->hash = $data['hash'];
            $this->aandachtspunt = ($data['aandachtspunten'] == 1 ? true : false);
            $this->declaratie = ($data['declaratie'] == 1 ? true : false);
            $this->reiskosten = ($data['reiskosten'] == 1 ? true : false);
            $this->last_aandacht = $data['laatst_aandacht'];
            $this->last_voorgaan = $data['laatst_voorgaan'];
            $this->last_data = $data['laatst_gegevens'];
            $this->honorarium_oud = $data['honorarium_2023'];
            $this->honorarium = $data['honorarium_2023'];
            $this->honorarium_special = $data['honorarium_special'];
            $this->km_vergoeding = $data['km_vergoeding'];
            $this->boekhoud_id = $data['boekhoudenID'];
        } else {
            $this->active = true;
            $this->aanhef = '';
            $this->initialen = '';
            $this->voornaam = '';
            $this->tussenvoegsel = '';
            $this->achternaam = '';
            $this->telefoon = '';
            $this->mobiel = '';
            $this->preekvoorziener = '';
            $this->preekvoorziener_telefoon = '';
            $this->mail = '';
            $this->plaats = '';
            $this->denominatie = '';
            #$this->stijl = '';
            $this->vousvoyeren = true;
            $this->opmerkingen = '';
            $this->hash = '';
            $this->aandachtspunt = true;
            $this->declaratie = true;
            $this->reiskosten = true;
            $this->last_aandacht = 0;
            $this->last_voorgaan = 0;
            $this->last_data = 0;
            $this->honorarium = 0;
            $this->honorarium_oud = 0;
            $this->honorarium_special = 0;
            $this->km_vergoeding = 0;
            $this->boekhoud_id = 0;       
        }
        $this->nameType = 3;
    }


    /**
     * @return string Opgemaakte naam van de voorganger op basis van type
     * type = 1 : W.M. van Wijk
     * type = 2 : ds. van Wijk
     * type = 3 : ds. W.M. van Wijk
     * type = 4 : Wim van Wijk -> W.M. van Wijk (bij ontbreken voornaam)
     * type = 5 : Wim -> ds. van Wijk (bij ontbreken voornaam)
     * type = 6 : Wijk; van, W.M.
     * type = 7 : van Wijk
     * type = 8 : Wijk; van, Wim -> Wijk; van, W.M. (bij ontbreken voornaam)     *
     */
    function getName($type = 0) {
        if($type == 0) {
            $type = $this->nameType;
        }

        if($this->tussenvoegsel != '') {
            $voorgangerAchterNaam = lcfirst($this->tussenvoegsel).' '. $this->achternaam;
            $voorgangerAchterNaamABC = $this->achternaam .'; '. lcfirst($this->tussenvoegsel);
        } else {
            $voorgangerAchterNaam = $this->achternaam;
            $voorgangerAchterNaamABC = $this->achternaam;
        }

        if($this->voornaam != "") {
            $voornaam = $this->voornaam;
        }

        # type = 8 : Wijk; van, Wim -> Wijk; van, W.M. (bij ontbreken voornaam)
        if($type == 8 && isset($voornaam)) {
            return $voorgangerAchterNaamABC.', '.$voornaam;
        } elseif($type == 8 && !isset($voornaam)) {
            $type = 6;
        }

        # type = 7 : van Wijk
        if($type == 7) {
            return $voorgangerAchterNaam;
        }

        # type = 6 : Wijk; van, W.M.
        if($type == 6) {
            return $voorgangerAchterNaamABC.', '.$this->initialen;
        }

        # type = 5 : Wim -> ds. van Wijk (bij ontbreken voornaam)
        if($type == 5 && isset($voornaam)) {
            return $voornaam;
        } elseif($type == 5 && !isset($voornaam)) {
            $type = 2;
        }

        # type = 4 : Wim van Wijk -> W.M. van Wijk (bij ontbreken voornaam)
        if($type == 4 && isset($voornaam)) {
            return $voornaam .' '.$voorgangerAchterNaam;
        } elseif($type == 4 && !isset($voornaam)) {
            $type = 1;
        }

        # type = 3 : ds. W.M. van Wijk
        if($type == 3) {
            return lcfirst($this->aanhef).' '. $this->initialen .' '.$voorgangerAchterNaam;
	    }

        # type = 2 : ds. van Wijk
	    if($type == 2) {
		    return lcfirst($this->aanhef).' '.$voorgangerAchterNaam;
	    }

        # type = 1 : W.M. van Wijk
	    if($type == 1) {
            return $this->initialen.' '.$voorgangerAchterNaam;
        }
    }



   /**
    * Geef alle actieve voorgangers
    * @return Array Array met ID's van voorgangers die actief zijn
    */
    static function getVoorgangers() {
        $db = new Mysql;

        $sql = "SELECT * FROM `predikanten` WHERE `actief` like '1' ORDER BY `achternaam`";
        $data = $db->select($sql, true);
        return array_column($data, 'id');
    }



   /**
    * Geef alle voorgangers die frequent voorgaan.
    * Frequent is meer dan 3x 
    * @return Array Array met ID's van voorgangers die frequent voorgaan
    */
    static function getFrequenteVoorgangers() {
        $db = new Mysql;
        $sql = "SELECT `voorganger`, count(*) as aantal FROM `kerkdiensten` GROUP BY `voorganger` HAVING aantal > 2 AND `voorganger` != 0 ORDER BY aantal DESC";
        $data = $db->select($sql, true);
        return array_column($data, 'voorganger');
    }


    /**
     * Sla het voorgangers-object op in de database.
     * @return int/bool Bij een nieuwe (=INSERT) voorganger wordt het id teruggegeven
     * bij een update true/false al naar gelang query geslaagd is
     */
    function save() {
        $db = new Mysql;

        $data['actief'] = ($this->active ? '1' : '0');
        $data['titel'] = $this->aanhef;
        $data['initialen'] = urlencode($this->initialen);
        $data['voornaam'] = urlencode($this->voornaam);
        $data['tussen'] = urlencode($this->tussenvoegsel);
        $data['achternaam'] = urlencode($this->achternaam);
        $data['telefoon'] = $this->telefoon;
        $data['mobiel'] = $this->mobiel;
        $data['naam_pv'] = urlencode($this->preekvoorziener);
        $data['tel_pv'] = $this->preekvoorziener_telefoon;
        $data['mail'] = urlencode($this->mail);
        $data['plaats'] = urlencode($this->plaats);
        $data['kerk'] = urlencode($this->denominatie);
        $data['stijl'] = ($this->vousvoyeren ? '1' : '0');
        $data['opmerking'] = urlencode($this->opmerkingen);
        $data['hash'] = $this->hash;
        $data['aandachtspunten'] = ($this->aandachtspunt ? '1' : '0');
        $data['declaratie'] = ($this->declaratie ? '1' : '0');
        $data['reiskosten'] = ($this->reiskosten ? '1' : '0');

        if(isset($this -> id)) {
            foreach($data as $key => $value) {
                $set[] = "`$key` = '$value'";
            }
            $sql = "UPDATE `predikanten` SET ". implode(', ', $set) ." WHERE `id` = ". $this->id;            
            return $db -> query($sql);
        } else {
            $sql = "INSERT INTO `predikanten` (`". implode('`, `', array_keys($data)) ."`) VALUES ('". implode("', '", array_values($data)) ."')";
            $db -> query($sql);
            return mysqli_insert_id($db->connection);
        }
        
    }
}
?>