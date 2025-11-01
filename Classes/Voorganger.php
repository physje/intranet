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
    public string $stijl;

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

    /**
     * @param int $id ID van de voorganger
     */
    function __construct(int $id = 0) {
        if($id > 0) {
            $db = new Mysql;
            $data = $db->select("SELECT * FROM `predikanten` WHERE `id` = ". $id);

            var_dump($id);
            var_dump($data);

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
            $this->stijl = $data['stijl'];
            $this->opmerkingen = urldecode($data['opmerking']);
            $this->hash = $data['hash'];
            $this->aandachtspunt = ($data['aandachtspunten'] == 1 ? true : false);
            $this->declaratie = ($data['declaratie'] == 1 ? true : false);
            $this->reiskosten = ($data['reiskosten'] == 1 ? true : false);
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
    function getName() {
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
        if($this->nameType == 8 and isset($voornaam)) {
            return $voorgangerAchterNaamABC.', '.$voornaam;
        } elseif($this->nameType == 8 and !isset($voornaam)) {
            $this->nameType = 6;
        }

        # type = 7 : van Wijk
        if($this->nameType == 7) {
            return $voorgangerAchterNaam;
        }

        # type = 6 : Wijk; van, W.M.
        if($this->nameType == 6) {
            return $voorgangerAchterNaamABC.', '.$this->initialen;
        }

        # type = 5 : Wim -> ds. van Wijk (bij ontbreken voornaam)
        if($this->nameType == 5 AND isset($voornaam)) {
            return $voornaam;
        } elseif($this->nameType == 5 AND !isset($voornaam)) {
            $this->nameType = 2;
        }

        # type = 4 : Wim van Wijk -> W.M. van Wijk (bij ontbreken voornaam)
        if($this->nameType == 4 AND isset($voornaam)) {
            return $voornaam .' '.$voorgangerAchterNaam;
        } elseif($this->nameType == 4 AND !isset($voornaam)) {
            $this->nameType = 1;
        }

        # type = 3 : ds. W.M. van Wijk
        if($this->nameType == 3) {
            return lcfirst($this->aanhef).' '. $this->initialen .' '.$voorgangerAchterNaam;
	    }

        # type = 2 : ds. van Wijk
	    if($this->nameType == 2) {
		    return lcfirst($this->aanhef).' '.$voorgangerAchterNaam;
	    }

        # type = 1 : W.M. van Wijk
	    if($this->nameType == 1) {
            return $this->initialen.' '.$voorgangerAchterNaam;
        }
    }

    function save() {

    }
}
?>