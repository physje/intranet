<?php

/**
 * Class voor het bijhouden van een declaratie
 * Dit kan zowel van een gastpredikant als voor een gemeentelid zijn.
 * Het bevat geen methoden, alleen properties.
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
class Declaratie
{
    /**
     * @var string Type van declaratie: 'voorganger' of 'gemeentelid'
     */
    public string $type;
    
    /**    
     * @var int ID van de declaratie in de database (alleen voor gemeentelid)
     */
    public int $id;

    /**
     * @var int ID van de dienst waarvoor de declaratie is
     */
    public int $dienst;
    
    /**
     * @var int ID van de voorganger waarvan de declaratie is
     */
    public int $voorganger;
    
    /**
     * @var string HASH van de declaratie
     */
    public string $hash;
    
    /**     
     * @var int ID van de gebruiker die de declaratie indient
     */
    public int $gebruiker;

    /**
     * @var string Vertrekpunt waar de voorganger is vertrokken
     */
    public string $van;
    
    /**
     * @var string Eindbestemming waar de voorganger is aangekomen (meestal KKD)
     */
    public string $naar;
    
    /**
     * @var float Afstand in kilometers voor de reiskosten
     */
    public float $afstand;  
    
    /**
     * @var float Reiskosten in euro's
     */
    public float $reiskosten;
    
    /**
     * @var array Overige kosten als een array van arrays met 'omschrijving' en 'bedrag' elementen
     */
    public array $overigeKosten;

    /**
     * @var float Totaalbedrag van de declaratie in euro's
     */
    public float $totaal;

    /**
     * @var string IBAN-nummer zoals oorspronkelijk opgegeven / in de database
     */
    public string $oorspronkelijke_IBAN;
    
    /**
     * @var string IBAN-nummer zoals opgegeven in de declaratie
     */
    public string $IBAN;

    /**
     * @var bool Moet het bedrag op eigen rekening worden gestort of niet
     */
    public bool $eigenRekening;

    /**
     * @var string Opmerking voor Cluster Coordinator
     * @deprecated Nu algemene propertie $opmerking die algemeen bruikbaar is
     */
    public string $opmerkingCluco;

    /**
     * Opmerking bij een fase van de declaratie.
     * De opmerking wordt doorgegeven aan de Cluco/Penningmeester/Gemeentelid
     * en daarna toegevoegd aan $correspondentie en opnieuw geinitialiseerd
     * @var string Opmerking bij deze fase van de declaratie
     */
    public string $opmerking;

    /**
     * @var array Array met alle correspondentie.
     * Elk element is een array ('time', 'user', 'text')
     */
    public array $correspondentie;

    /**
     * @var int ID van de relatie binnen de eBoekhouden.nl administratie waar de factuur naartoe overgemaakt moet worden
     * @deprecated
     */
    public int $EB_relatie;

    /**
     * @var int Cluster waar de declaratie onder geboekt moet worden
     */
    public int $cluster;

    /**
     * @var int Grootboekrekening-nummer waar declaratie op geboekt moet worden.
     * Deze nummers komen uit eBoekhouden.nl
     */
    public int $GBR;

    /**
     * @var string Het betalingskenmerk wat opgenomen moet worden in de overschrijving
     */
    public string $betalingskenmerk;

    /**
     * @var int ID uit eBoekhouden waar de betaling naar toe moet.
     * Alleen van belang als geld niet op eigen rekening overgemaakt moet worden.
     * @see Declaratie::$eigenRekening
     */
    public int $begunstigde;

    /**
     * Status van de declaratie (alleen gebruikt bij gemeenteleden).
     *      
     * 0 = 'geen' (nog niet in gebruik)
     * 1 = 'opgeslagen' (nog niet in gebruik)
     * 2 = 'bij lid'
     * 3 = 'bij CluCo'
     * 4 = 'bij penningmeester'
     * 5 = 'afgerond'
     * 6 = 'afgekeurd'
     * 7 = 'verwijderd'
     * 8 = 'investering'
     * 
     * @see Kerkdienst::$declaratieStatus
     * Declaratie van voorgangers wordt bijgehouden in Kerkdietst-object (declaratieStatus)
     *
     * @var int Status van de declaratie
     */
    public int $status;

    /**
     * @var string Post van Jeugd & Gezin waar de declaratie onder geboekt moet worden
     */
    public array $posten;

    /**
     * @var array Array van bijlagen (filename => bestandsnamen)
     */
    public array $bijlagen;

    /**
     * @var int UNIX-tijdstip waarop de declaratie is ingediend
     */
    public int $tijd;

    /** 
     * @var int UNIX-tijdstip waarop voor het laatste naar de declaratie gekeken is
     */
    public int $lastAction;
    
    
    /**
     * Maak een nieuw Declaratie object aan
     *
     * Declaraties van voorgangers gaan niet in de database, dus die worden alleen in het object bijgehouden.
     * Declaraties van gemeenteleden worden wel in de database opgeslagen en kunnen worden opgehaald via de hash.
     * Als er dus een string wordt meegegeven, is het een declaratie van een gemeentelid en wordt deze uit de database gehaald.
     * Vanuit backwards compatibiliteit moeten er sommige keys uit de JSON-string in de database wordt gematched op de properties van het object.
     * 
     * @param string $hash hash van de declaratie (alfanumeriek 8 tekens) in de database (optioneel)
     * 
     */
    public function __construct($hash = '') {
        # Initialiseer het object met 'lege' properties

        # Typisch voorganger
        $this->dienst = 0;
        $this->voorganger = 0;        
        $this->oorspronkelijke_IBAN = '';

        #Beide types
        $this->hash = '';
        $this->van = '';
        $this->naar = '';        
        $this->IBAN = '';        
        $this->afstand = 0.0;
        $this->reiskosten = 0.0;
        $this->overigeKosten = [];
                    
        # Typisch gemeentelid
        $this->id = 0;
        $this->gebruiker = 0;
        $this->eigenRekening = true;
        #$this->opmerkingCluco = '';
        $this->opmerking = '';
        $this->betalingskenmerk = '';
        $this->begunstigde = 0;
        $this->correspondentie = [];        
        $this->cluster = 0;
        $this->status = 0;
        $this->GBR = 0;
        $this->totaal = 0.0;
        $this->tijd = time();
        $this->lastAction = time();
        $this->posten = [];
        $this->bijlagen = [];

        if($hash != '') {
            $db = new Mysql();            
            $this->type = 'gemeentelid';
            
            # Laad de declaratie uit de database
            $sql = "SELECT * FROM `eb_declaraties` WHERE `hash` like '". $hash ."'";
            $data = $db->select($sql);

            if(isset($data['id']))          $this->id = $data['id'];
            if(isset($data['hash']))        $this->hash = $data['hash'];
            if(isset($data['indiener']))    $this->gebruiker = intval($data['indiener']);
            if(isset($data['tijd']))        $this->tijd = intval($data['tijd']);
            if(isset($data['last_action'])) $this->lastAction = intval($data['last_action']);
            
            $json = json_decode($data['declaratie'], true);

            # Bij 'nieuwe' declaraties komen de keys uit de JSON overeen met de properties van het object
            foreach($json as $key => $value) {
                if(property_exists($this, $key)) {
                    if(in_array($key, ['overigeKosten', 'posten', 'bijlagen', 'correspondentie'])) {
                        $this->$key = json_decode($value, true);
                    } else {
                        $this->$key = $value;
                    }
                }
            }
            
            # Bij oudere declaraties moeten we het handmatig doen
            if(isset($json['eigen']))       $this->eigenRekening = ($json['eigen'] == 'Ja' ? true : false);
            if(isset($json['cluster']))     $this->cluster = intval($json['cluster']);
            if(isset($json['iban']))        $this->IBAN = $json['iban'];
            if(isset($json['opm_cluco']))   $this->opmerking = $json['opm_cluco'];
            if(isset($json['totaal']))      $this->totaal = floatval($json['totaal']);
            if(isset($json['EBCode']))      $this->begunstigde = intval($json['EBCode']);
            if(isset($json['post']))        $this->posten = $json['post'];
            if(isset($json['reis_van']))    $this->van = $json['reis_van'];
            if(isset($json['reis_naar']))   $this->naar = $json['reis_naar'];
            if(isset($json['km']))          $this->afstand = $json['km'];

            if(isset($json['overig']) && isset($json['overig_price'])) {
                for($i=0; $i < count($json['overig']); $i++) {
                    $this->overigeKosten[] = [
                        'omschrijving'   => $json['overig'][$i],
                        'bedrag'        => floatval(100*$json['overig_price'][$i])
                    ];
                }
            }
            if(isset($json['bijlage']) && isset($json['bijlage_naam'])) {
                for($i=0; $i < count($json['bijlage']); $i++) {
                    $this->bijlagen[$json['bijlage'][$i]] = $json['bijlage_naam'][$i];
                }
            }                      
        }
    }

    static function getDeclaratiesByStatus(int $status, int $cluster = 0) {
        $db = new Mysql();
        $sql = "SELECT `hash` FROM `eb_declaraties` WHERE `status` = ". $status;

        if($cluster > 0) {
            $sql .="  AND `cluster` = $cluster AND `indiener` NOT like '". $_SESSION['useID'] ."'";
        }

        $data = $db->select($sql, true);

        return array_column($data, 'hash');
    }

    public function save() {
        # Sla de declaratie op in de database, maar alleen voor declaraties van gemeenteleden
        if($this->type == 'gemeentelid') {
            $db = new Mysql();

            # Voeg eventuele opmerking toe aan de correspondentie
            if($this->opmerking != '') {
                $this->correspondentie[] = array('time' => time(), 'user' => $_SESSION['useID'], 'text' => cleanDeclaratieString($this->opmerking));
                $this->opmerking = '';
            }

            # Stel de indien-tijd in
			$this->lastAction = time();

            foreach($this as $key => $value) {
                if($value != '' && $value != 0 && $value != [] && $key != '') {
                    $data[$key] = is_array($value) ? addslashes(json_encode($value)) : $value;                    
                } elseif(is_bool($value) && $value === false) {
                    $data[$key] = false;                
                }
            }

            if($this->id == 0) {
                $sql = "INSERT INTO `eb_declaraties` (`hash`, `indiener`, `cluster`, `status`, `declaratie`, `totaal`, `tijd`, `last_action`) VALUES ('". $this->hash ."', ". $this->gebruiker .", ". $this->cluster .", ". $this->status .", '". json_encode($data) ."', ". $this->totaal .", ". $this->tijd .", ". $this->lastAction .")";
            } else {
                $sql = "UPDATE `eb_declaraties` SET `indiener` = ". $this->gebruiker .", `cluster` = ". $this->cluster .", `status` = ". $this->status .", `declaratie` = '". json_encode($data) ."', `totaal` = ". $this->totaal .", `last_action` = ". $this->lastAction ."  WHERE `id` = ". $this->id;
            }

            return $db->query($sql);
        }
    }
}

?>