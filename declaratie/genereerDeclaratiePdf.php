<?php
include_once('../include/pdf/config.php');
include_once('../include/pdf/3gk_declaratie_table.php');

define('EURO',chr(128));

/**
 * Berken de betaal datum op basis van de declaratie datum
 * @param  string $datum             datum al vergkregen door de date() functie
 * @param  int    $betaalDag         dag van uitbetalen
 * @return string $betaalDatum       betaal datum string formaat "d-m-Y"
 */
function bereken_betaal_datum($datum, $betaalDag) 
{
    $timestamp = strtotime($datum);
    $betaalDatum = "";

    $dag = date('d', $timestamp);
    $maand = date('m', $timestamp);
    $jaar = date('Y', $timestamp);

    if ( $dag < $betaalDag ) {
        $betaalDatum = $betaalDag.date('-m-Y', $timestamp);
    } else {
        if ( $maand == 12 ) {
            $maand = "1";
            $jaar += 1;
        } else {
            $maand += 1;
        }
        $betaalDatum = $betaalDag.$maand."-".$jaar;
    }

    return $betaalDatum;
}

/**
 * Genereer pdf met declaratie gegevens ter administratie
 * @param  string $mutatieNr        mutatie nummer
 * @param  string $mutatieDatum     datum al vergkregen door de date() functie
 * @param  string $naam             naam van de declarant
 * @param  string $adres            adres van de declarant (indien niet bekend leeg laten)
 * @param  string $mailadres        mailadres van de declarant (indien niet bekend leeg laten)
 * @param  string $iban             naam van de declarant
 * @param  array  $declaratieData   array met declaratieData: [['onderdeel1', 'bedrag'], [.., ...] ... ]
 */
function genereer_declaratie_pdf($mutatieNr, $mutatieDatum, $naam, $adres, $mailadres, $iban, $declaratieData)
{
    global $cfgMarge;
    
    $pdf = new PDF_3GK_Table_Declaratie;
    $fontSize = 10;

    $pdf->Ln();
    $pdf->Ln();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont("Helvetica",'',$fontSize);
    $breedte = $pdf->GetPageWidth();

    # Maak tabel met basis informatie over de declaratie
    $betaalDatum = bereken_betaal_datum($mutatieDatum, 20);

    $header = ["Declaratie Nr.", "Declaratie datum", "Verwachte betaaldatum"];
    $data = [['ID: '.$mutatieNr, $mutatieDatum, $betaalDatum]];
    $widths = array_fill(1, (count($header)-1), ($breedte-30-(2*$cfgMarge))/(count($header)-1));
    $widths[0] = 30;
    $pdf->SetWidths($widths);
    $pdf->makeTable($header, $data, false);

    # General information
    $pdf->Ln();
    $pdf->Ln();
    $pdf->Ln();
    $pdf->Ln();
    $pdf->Ln();

    $pdf->SetFont("Helvetica",'B',16);
    $pdf->Write(5, "Contactgegevens");
    $pdf->Ln();
    $pdf->Ln();

    $pdf->SetFont("Helvetica",'',$fontSize);
    $pdf->Cell(25,5, "Naam:", 'R', 0, 'L', 0);
    $pdf->Write(5, $naam);
    $pdf->Ln();

    if ( $adres != "" ) {
        $pdf->Cell(25,5, "Adres:", 'R', 0, 'L', 0);
        $pdf->Write(5, $adres);
        $pdf->Ln();
    }
    
    if ( $mailadres != "" ) {
        $pdf->Cell(25,5, "Mailadres:", 'R', 0, 'L', 0);
        $pdf->Write(5, $mailadres);
        $pdf->Ln();
    }
    
    $pdf->Cell(25,5, "IBAN:", 'R', 0, 'L', 0);
    $pdf->Write(5, $iban);
    $pdf->Ln();
    $pdf->Ln();
    $pdf->Ln();
    $pdf->Ln();
    $pdf->Ln();
    $pdf->Ln();

    $pdf->SetFont("Helvetica",'B',16);
    $pdf->Write(5, "Declaratie overzicht");
    $pdf->Ln();
    $pdf->Ln();

    $pdf->SetFont("Helvetica",'',$fontSize);

    # Maak tabel met details over de declaratie 
    $totaalBedrag = 0.0;
    foreach ($declaratieData as $key => $data) {    	  
        $totaalBedrag += (float)($data[1] / 100);        
        $declaratieData[$key][1] = '€ '.number_format($data[1]/100, 2,',','.');
    }
    
    $totaalBedrag = number_format($totaalBedrag, 2,',','.');
    $totaalBedragRow = ["Totaal uit te betalen bedrag:", "€ ".$totaalBedrag];

    $header = ["Onderdeel", "Bedrag"];
    $data = $declaratieData;
    array_push($data, $totaalBedragRow);

    $widths = array_fill(0, (count($header)), ($breedte-(2*$cfgMarge))/(count($header)));
    $pdf->SetWidths($widths);
    $pdf->makeTable($header, $data, true);

    # Sub text
    $pdf->SetFont("Helvetica",'I',$fontSize);
    $pdf->SetY(-60);
    $pdf->Write(5, "De declaratie is met succes ontvangen en we proberen het te declareren bedrag over te maken op of voor de bovengenoemde datum. Mochten er gegevens op deze declaratie niet kloppen neem dan contact op met de penningmeester van de Koningskerk via onderstaande contactgegevens. ");

    # Genereer de pdf
    $pdf->Output('F', 'PDF/'.$mutatieNr.'.pdf');

}


$mutatieNr = "123_13719";
$mutatieDatum = date("Y-m-d");
$naam = "Jan Janssen";
$adres = "adres 123"; 
$mailadres = "naam@domein.nl";
$iban = "NL01XXXX0123456789";
$declaratieData = [["OnderdeelX", "90.00"], ["OnderdeelY", "17.15"]];


genereer_declaratie_pdf($mutatieNr, $mutatieDatum, $naam, $adres, $mailadres, $iban, $declaratieData);
