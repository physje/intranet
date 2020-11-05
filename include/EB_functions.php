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

function eb_getMutatiesByDate ( $dateFrom, $toDate)
{
  	try {
				global $ebUsername, $ebSecurityCode1, $ebSecurityCode2;
        $ebClient = new eBoekhoudenConnect($ebUsername, $ebSecurityCode1, $ebSecurityCode2);
				
				$mutaties = $ebClient->getMutationsByPeriod($dateFrom, $toDate);
				
				$mutaties = $mutaties->Mutaties;
				//var_dump($mutaties);
        if (!is_array($mutaties->cMutatieList)) {
            $mutaties->cMutatieList = array($mutaties->cMutatieList);
        }

        // Getting the code of each relation
        $codeArray = array();
        foreach ($mutaties->cMutatieList as $Mutatie) {
            var_dump($Mutatie);
        }
				
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
 * Update data van relatie door middel van de e-boekhouden relatie code 
 * @param  string $code         e-boekhouden relatie code
 * @param  array $data         	array met nieuwe gegevens. Indien index in array niet bestaat, wordt dit niet aangepast
 * @return string $exception    alleen in geval van error of SoapFault wordt deze string gereturned met informatie over de fout 
 */
function eb_updateRelatieByCode ($code, $data)
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
            
            if(isset($data['naam']))			$relatie->setCompanyName($data['naam']);			else	$relatie->setCompanyName($relatieOud->Bedrijf);
            if(isset($data['geslacht']))	$relatie->setSex($data['geslacht']);					else	$relatie->setSex($relatieOud->Geslacht);
            if(isset($data['adres']))			$relatie->setAddress($data['adres']);					else	$relatie->setAddress($relatieOud->Adres);
            if(isset($data['postcode']))	$relatie->setPostalcode($data['postcode']);		else	$relatie->setPostalcode($relatieOud->Postcode);
            if(isset($data['plaats']))		$relatie->setCity($data['plaats']);						else	$relatie->setCity($relatieOud->Plaats);
            if(isset($data['mail']))			$relatie->setEmail($data['mail']);						else	$relatie->setEmail($relatieOud->Email);
            if(isset($data['iban']))			$relatie->setIban(cleanIBAN($data['iban']));	else	$relatie->setIban(cleanIBAN($relatieOud->IBAN));
            if(isset($data['notitie']))		$relatie->setNote($data['notitie']);					else	$relatie->setNote($relatieOud->Notitie);            
            if(isset($data['telefoon']))	$relatie->setPhone($data['telefoon']);				else	$relatie->setPhone($relatieOud->Telefoon);
            if(isset($data['mobiel']))		$relatie->setMobile($data['mobiel']);					else	$relatie->setMobile($relatieOud->GSM);
                        
            $response = $ebClient->updateRelation ( $relatie );

        } else {
            // Received relation code is different than the requested relation code
            throw new \Exception("Failure in eb_updateRelatieByCode. Received code is different than the requested code: Rec: ".$relatieOud->Code." Req: ".$code);
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
 * Get relatiecode door middel van het iban nummer
 * @param  string $iban         bankrekening nummer nieuwe relatie
 * @param  string &$code        reference naar e-boekhouden relatie code
 * @return string $exception    alleen in geval van error of SoapFault wordt deze string gereturned met informatie over de fout 
 */
function eb_getRelatieCodeByIban ( $iban, &$code ) {
    try {
        global $ebUsername, $ebSecurityCode1, $ebSecurityCode2;
        $ebClient = new eBoekhoudenConnect($ebUsername, $ebSecurityCode1, $ebSecurityCode2);

        $code = $ebClient->getRelationByIban ( $iban);

    } catch (\Exception $exception) {
        return $exception->getMessage();
    }
}

/**
 * Get relatiecode door middel van het een text search
 * @param  string $searchText   invoer text. E-boekhouden zoekt met de invoer op code, bedrijfsnaam, plaats, contactpersoon en e-mailadres adres
 * @param  array  &$code        reference naar code array e-boekhouden relatie code
 * @return string $exception    alleen in geval van error of SoapFault wordt deze string gereturned met informatie over de fout 
 */
function eb_getRelatieCodeBySearch ( $searchText, &$code ) {
    try {
        global $ebUsername, $ebSecurityCode1, $ebSecurityCode2;
        $ebClient = new eBoekhoudenConnect($ebUsername, $ebSecurityCode1, $ebSecurityCode2);

        $code = $ebClient->getRelationBySearch ( $searchText);

    } catch (\Exception $exception) {
        return $exception->getMessage();
    }
}

/**
 * Maak en verstuur mutatie voor een bestaand e-boekhouden relatie
 * @param  string $code           e-boekhouden relatie code
 * @param  string $boekstukNummer het boekstuknummer (niet langer dan 50 karakters)
 * @param  string $factuurNummer  het factuurnummer (niet langer dan 50 karakters)
 * @param  int    $bedrag         het te declareren bedrag in centen
 * @param  int    $GBR	 	        grootboekrekening (GBR) waar de declaratie op geboekt moet worden
 * @param  string $toelichting    toelichting van de declaratie (niet langer dan 200 karakters) 
 * @return string $exception      alleen in geval van error of SoapFault wordt deze string gereturned met informatie over de fout 
 */
function eb_verstuurDeclaratie ( $code, $boekstukNummer, $factuurNummer, $bedragCenten, $GBR, $toelichting, &$mutatieId )
{
    try {
        global $ebUsername, $ebSecurityCode1, $ebSecurityCode2;
        $ebClient = new eBoekhoudenConnect($ebUsername, $ebSecurityCode1, $ebSecurityCode2);
        
        $soort             = "FactuurOntvangen";
        $datum             = date('Y-m-d');
        $rekening          = "2000";
        $btwCode           = "GEEN";
        $betalingstermijn  = "0";
        $tegenRekeningCode = $GBR;
        $btwPercentage     = 0.0;
        // Ingevoerde bedrag is in centen, omrekenen naar euro's
        //$bedrag            = (double) round($bedragCenten / 100, 2);        
        $bedrag            = number_format(($bedragCenten/100), 2, '.', '');

        $mutatie = new Mutation;
        $mutatie->setKind($soort);
        $mutatie->setDate($datum);
        $mutatie->setAccount($rekening);
        $mutatie->setRelationCode($code);
        $mutatie->setInvoiceNumber($factuurNummer);
        $mutatie->setDescription($toelichting);
        $mutatie->setTermOfPayment($betalingstermijn);
        $mutatie->setBookNumber($boekstukNummer);
        $mutatie->setInOrExVat("IN");
        $mutatie->addMutationLine($bedrag, $btwPercentage, $btwCode, $tegenRekeningCode, 0);

        $response = $ebClient->addMutation ( $mutatie ); 

        $mutatieId = $response->Mutatienummer;
        
    } catch (\Exception $exception) {
        return $exception->getMessage();
    }
}