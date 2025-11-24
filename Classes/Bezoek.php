<?php

/**
 * Class voor het bijhouden van pastoraal bezoeken
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
class Bezoek {
    /**
     * @var int ID van het bezoek
     */
    public int $id;
    
    /**
     * @var bool Komt deze voor in de lokale administratie (false) of is deze nieuw (true)
     */
    public bool $new;
    
    /**
     * @var int ID van de persoon die het bezoek heeft afgelegd
     */
    public int $werker;
    
    /**
     * @var int UNIX-timestamp van het bezoek
     */
    public int $tijdstip;
    
    /**
     * @var int ID van de persoon die het bezoek heeft ontvangen
     */
    public int $lid;
    
    /**
     * @var int ID van soort pastoraal bezoek (soorten staan in $typePastoraat)
     */
    public int $type;
    
    /**
     * @var int ID van de locatie van het pastoraal bezoek (locaties staan in $locatiePastoraat)
     */
    public int $locatie;
    
    /**
     * @var bool Is dit bezoek prive (nog niet gebruikt)
     */
    public bool $prive;
    
    /**
     * @var bool Is dit bezoek zichtbaar voor ouderlingen (nog niet gebruikt)
     */
    public bool $zichtbaarOuderling;
    
    /**
     * @var bool Is dit bezoek zichtbaar voor predikanten (nog niet gebruikt)
     */
    public bool $zichtbaarPredikant;
    
    /**
     * @var bool Is dit bezoek zichtbaar voor pastoraal werkers (nog niet gebruikt)
     */
    public bool $zichtbaarPastoraal;
    
    /**
     * @var string Aantekeningen bij het bezoek
     */
    public string $aantekening;


    /**
     * @param int $id Maak een nieuw bezoek-object aan.
     * Als een ID is gegeven, wordt het object gevuld met data van dat bezoek.
     */
    public function __construct(int $id = 0) {
        $db = new Mysql();
        $this->new = true;

        if($id > 0) {
            $this->id = $id;
            $this->new = false;
            
			$data = $db->select("SELECT * FROM `pastoraat` WHERE `id` = ". $this->id);
            $this->werker = $data['indiener'];
            $this->tijdstip = $data['tijdstip'];
            $this->lid = $data['lid'];
            $this->type = $data['type'];
            $this->locatie = $data['locatie'];
            #$this->prive = ($data['prive'] == 1 ? true : false);
            $this->zichtbaarOuderling = ($data['zicht_oud'] == 1 ? true : false);
            $this->zichtbaarPredikant = ($data['zicht_pred'] == 1 ? true : false);
            $this->zichtbaarPastoraal = ($data['zicht_pas'] == 1 ? true : false);
            $this->aantekening = urldecode($data['aantekening']);
        } else {
            $this->werker = 0;
            $this->tijdstip = 0;
            $this->lid = 0;
            $this->type = 0;
            $this->locatie = 0;
            #$this->prive = false;
            $this->zichtbaarOuderling = true;
            $this->zichtbaarPredikant = true;
            $this->zichtbaarPastoraal = true;
            $this->aantekening = '';
        }
    }

     /**
     * Sla het Bezoek-object op in de database
     * @return bool Succesvol of niet
     */
    public function save() {
        $db = new Mysql();

        $data = $set = array();

        $data['indiener'] = $this->werker;
        $data['tijdstip'] = $this->tijdstip;
        $data['lid'] = $this->lid;
        $data['type'] = $this->type;
        $data['locatie'] = $this->locatie;
        #$data['prive'] = ($this->prive ? '1' : '0');
        $data['zicht_oud'] = ($this->zichtbaarOuderling ? '1' : '0');
        $data['zicht_pred'] = ($this->zichtbaarPredikant ? '1' : '0');
        $data['zicht_pas'] = ($this->zichtbaarPastoraal ? '1' : '0');
        $data['aantekening'] = urlencode($this->aantekening);

        if($this->new) {
            $sql = "INSERT INTO `pastoraat` (`". implode('`, `', array_keys($data)) ."`) VALUES ('". implode("', '", array_values($data)) ."')";
        } else {
            foreach($data as $key => $value) {
                $set[] = "`$key`='$value'";
            }

            $sql = "UPDATE `pastoraat` SET ". implode(', ', $set) ." WHERE `id` = ". $this->id;
        }

        return $db -> query($sql);
    }
}