<?php

include_once('e-boekhouden/eBoekhoudenConnect.php');
include_once('e-boekhouden/Relation.php');
include_once('e-boekhouden/Mutation.php');


/**
 * Maak nieuwe relatie aan in e-boekhouden
 * @param  string $naam         naam van de nieuwe relatie
 * @param  string $geslacht     geslacht van de nieuwe relatie
 * @param  string $adres        adres van de nieuwe relatie
 * @param  string $postcode     postcode van de nieuwe relatie
 * @param  string $plaats       plaats van de nieuwe relatie
 * @param  string $email        e-mail adres van de nieuwe relatie
 * @param  string $iban         bankrekening nummer nieuwe relatie
 * @param  int    &$id          reference naar e-boekhouden relatie id
 * @param  string &$code        reference naar e-boekhouden relatie code
 * @return string $exception    alleen in geval van error of SoapFault wordt deze string gereturned met informatie over de fout 
 */
function eb_maakNieuweRelatieAan ( $naam, $geslacht, $adres, $postcode, $plaats, $email, $iban, &$code, &$id )
{
    try {
        global $ebUsername, $ebSecurityCode1, $ebSecurityCode2;
        $ebClient = new eBoekhoudenConnect($ebUsername, $ebSecurityCode1, $ebSecurityCode2);

        $newCode = $ebClient->generateNewCode();

        $relatie = new Relation;
        $relatie->setId(0);
        $relatie->setRelationCode($newCode);
        $relatie->setCreationDate('');
        $relatie->setCompanyName($naam);
        $relatie->setSex($geslacht);
        $relatie->setAddress($adres);
        $relatie->setPostalcode($postcode);
        $relatie->setCity($plaats);
        $relatie->setEmail($email);
        $relatie->setIban($iban);

        $response = $ebClient->addRelation ( $relatie );

        $id = $response->Rel_ID;
        $code = $newCode;

    } catch (\Exception $exception) {
        return $exception->getMessage();
    }
}

/**
 * Update relatie iban door middel van de e-boekhouden relatie code 
 * @param  string $code         e-boekhouden relatie code
 * @param  string $iban         het nieuwe bankrekening nummer van de bestaande relatie
 * @return string $exception    alleen in geval van error of SoapFault wordt deze string gereturned met informatie over de fout 
 */
function eb_updateRelatieIbanByCode ( $code, $newIban )
{
    try {
        global $ebUsername, $ebSecurityCode1, $ebSecurityCode2;
        $relatie = new Relation;
        $ebClient = new eBoekhoudenConnect($ebUsername, $ebSecurityCode1, $ebSecurityCode2);

        $relatieOud = $ebClient->getRelationByCode($code);

        if ( $code == $relatieOud->Code ) {
            $relatie->setId($relatieOud->ID);
            $relatie->setRelationCode($relatieOud->Code);
            $relatie->setCreationDate($relatieOud->AddDatum);
            $relatie->setCompanyName($relatieOud->Bedrijf);
            $relatie->setSex($relatieOud->Geslacht);
            $relatie->setAddress($relatieOud->Adres);
            $relatie->setPostalcode($relatieOud->Postcode);
            $relatie->setCity($relatieOud->Plaats);
            $relatie->setEmail($relatieOud->Email);
            $relatie->setIban($newIban);

            $response = $ebClient->updateRelation ( $relatie );

        } else {
            // Received relation code is different than the requested relation code
            throw new \Exception("Failure in eb_updateRelatieIbanByCode. Received code is different than the requested code: Rec: ".$relatieOud->Code." Req: ".$code);
        }
        
    } catch (\Exception $exception) {
        return $exception->getMessage();
    }
}

/**
 * Get IBAN nummer van relatie door middel van het e-boekhouden relatie id
 * @param  int    $id           e-boekhouden relatie id
 * @param  string &$iban        reference naar bankrekening nummer nieuwe relatie
 * @return string $exception    alleen in geval van error of SoapFault wordt deze string gereturned met informatie over de fout 
 */
function eb_getRelatieIbanById ( $id, &$iban )
{
    try {
        global $ebUsername, $ebSecurityCode1, $ebSecurityCode2;
        $ebClient = new eBoekhoudenConnect($ebUsername, $ebSecurityCode1, $ebSecurityCode2);
    
        $relatie = $ebClient->getRelationById ( $id );

        $iban = $relatie->IBAN;

    } catch (\Exception $exception) {
        return $exception->getMessage();
    }
}

/**
 * Get IBAN nummer van relatie door middel van de e-boekhouden relatie code
 * @param  string $code         e-boekhouden relatie code
 * @param  string &$iban        reference naar bankrekening nummer nieuwe relatie
 * @return string $exception    alleen in geval van error of SoapFault wordt deze string gereturned met informatie over de fout 
 */
function eb_getRelatieIbanByCode ( $code, &$iban )
{
    try {
        global $ebUsername, $ebSecurityCode1, $ebSecurityCode2;
        $ebClient = new eBoekhoudenConnect($ebUsername, $ebSecurityCode1, $ebSecurityCode2);
    
        $relatie = $ebClient->getRelationByCode ( $code );

        $iban = $relatie->IBAN;

    } catch (\Exception $exception) {
        return $exception->getMessage();
    }
}

/**
 * NOTE: Function below is in progress so not finished:
 * @param  $code         e-boekhouden relatie code
 * @param  $bedrag       het te declareren bedrag in centen
 * @param  $toelichting  toelichting van de declaratie (niet langer dan 200 karakters)
 * @return string $exception    alleen in geval van error of SoapFault wordt deze string gereturned met informatie over de fout 
 */
function eb_verstuurDeclaratie ( $code, $bedrag, $toelichting, &$mutatieId )
{
    try {
        global $ebUsername, $ebSecurityCode1, $ebSecurityCode2;
        $ebClient = new eBoekhoudenConnect($ebUsername, $ebSecurityCode1, $ebSecurityCode2);
        
        $soort             = "FactuurOntvangen";
        $datum             = date('Y-m-d');
        $rekening          = "2000";
        $factuurNummer     = date('Ymd') .+ $code;  // willen we de factuurnummer zo opgebouwd hebben?
        $btwCode           = "GEEN";
        $betalingstermijn  = "";
        $tegenRekeningCode = "40491";
        $btwPercentage     = 0.0;
        // Ingevoerde bedrag is in centen, omrekenen naar euro's
        $bedrag            = (double) $bedrag / 100;

        $mutatie = new Mutation;
        $mutatie->setKind($soort);
        $mutatie->setDate($datum);
        $mutatie->setAccount($rekening);
        $mutatie->setRelationCode($code);
        $mutatie->setInvoiceNumber($factuurNummer);
        $mutatie->setDescription($toelichting);
        $mutatie->setTermOfPayment($betalingstermijn);
        $mutatie->addMutationLine($bedrag, $btwPercentage, $btwCode, $tegenRekeningCode, 0);

        $response = $ebClient->addMutation ( $mutatie ); 

        $mutatieId = $response->Mut_ID; //vermoedelijke variabele naam

    } catch (\Exception $exception) {
        return $exception->getMessage();
    }
}