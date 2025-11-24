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
     */
    public string $opmerkingCluco;

    /**
     * @var int ID van de relatie binnen de eBoekhouden.nl administratie waar de factuur naartoe overgemaakt moet worden
     */
    public int $EB_relatie;

    /**
     * @var int Cluster waar de declaratie onder geboekt moet worden
     */
    public int $cluster;

    /**
     * @var array Post van Jeugd & Gezin waar de declaratie onder geboekt moet worden
     */
    public array $posten;

    /**
     * @var array Array van bijlagen (filename => bestandsnamen)
     */
    public array $bijlagen;
    
    public function __construct()    {        
        # Typisch voorganger
        $this->dienst = 0;
        $this->voorganger = 0;
        $this->hash = '';
        $this->oorspronkelijke_IBAN = '';

        #Beide types
        $this->van = '';
        $this->naar = '';        
        $this->IBAN = '';        
        $this->afstand = 0.0;
        $this->reiskosten = 0.0;
        $this->overigeKosten = [];
        
        # Typisch gemeentelid
        $this->eigenRekening = true;
        $this->opmerkingCluco = '';
        $this->EB_relatie = 0;
        $this->cluster = 0;
        $this->totaal = 0.0;
        $this->posten = [];
        $this->bijlagen = [];
    }
}

?>