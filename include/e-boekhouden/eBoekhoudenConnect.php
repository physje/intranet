<?php
include_once ('ValueObjects\MutationId.php');
include_once ('ValueObjects\Date.php');
include_once ('ValueObjects\RelationCode.php');
include_once ('ValueObjects\RelationId.php');
include_once ('ValueObjects\RelationSearch.php');

# Documentatie over de gebruikte soap/api: https://secure.e-boekhouden.nl/handleiding/Documentatie_soap.pdf

/**
 * Class eBoekhoudenConnect
 * @package eBoekhouden
 */
class eBoekhoudenConnect
{
    // Private variables
    private $sessionId;
    private $securityCode2;
    private $soapClient;

    /**
     * eBoekhoudenConnect constructor.
     * @param $username
     * @param $securityCode1
     * @param $securityCode2
     * @throws \Exception
     */
    public function __construct($username, $securityCode1, $securityCode2)
    {
        try {
            $this->soapClient = new \SoapClient("https://soap.e-boekhouden.nl/soap.asmx?WSDL");
            // The line below will enable trace possibilities. For example: The last sent xml can be requested for analyzing and/or debugging.
            // $this->soapClient = new \SoapClient("https://soap.e-boekhouden.nl/soap.asmx?WSDL", array('trace' => 1));

            $params = [
                "Username" => $username,
                "SecurityCode1" => $securityCode1,
                "SecurityCode2" => $securityCode2
            ];
            $response = $this->soapClient->__soapCall("OpenSession", array($params));
            $this->checkforerror($response, "OpenSessionResult");
            $this->sessionId = $response->OpenSessionResult->SessionID;
            $this->securityCode2 = $securityCode2;

        } catch(\SoapFault $soapFault) {
            throw new \Exception('<strong>Soap Exception:</strong> ' . $soapFault);
        }
    }

    /**
     * @throws \Exception
     */
    public function __destruct()
    {
        try {
            $params = array(
                "SessionID" => $this->sessionId
            );
            return $this->soapClient->__soapCall("CloseSession", array($params));
        } catch(\SoapFault $soapFault) {
            throw new \Exception('<strong>Soap Exception:</strong> ' . $soapFault);
        }
    }

    /**
     * @param Mutation $mutation
     * @return mixed
     * @throws \Exception 
     */
    public function addMutation(Mutation $mutation, &$response)
    {
        try {
            $params = [
                "SecurityCode2" => $this->securityCode2,
                "SessionID" => $this->sessionId,
                "oMut" => $mutation->getMutationArray()
            ];

            $response = $this->soapClient->__soapCall("AddMutatie", [$params]);

            if ( $this->checkforerror($response, "AddMutatieResponse") )  {
                return TRUE;
            }
            
            $response = $response->AddMutatieResult;
            return FALSE;
        } catch(\SoapFault $soapFault) {
            throw new \Exception('<strong>Soap Exception:</strong> ' . $soapFault);
        }
    }

    /**
     * @param Relation $relation
     * @return mixed
     * @throws \Exception
     */
    public function addRelation(Relation $relation, &$response)
    {
        try {
            $params = [
                "SecurityCode2" => $this->securityCode2,
                "SessionID" => $this->sessionId,
                "oRel" => $relation->getEboekhoudenArray()
            ];

            $response = $this->soapClient->__soapCall("AddRelatie", [$params]);
            
            if ( $this->checkforerror($response, "AddRelatieResult") ) {
                return TRUE;
            }

            $response = $response->AddRelatieResult;
            return FALSE;            
        } catch(\SoapFault $soapFault) {
            echo "REQUEST:\n". htmlentities($this->soapClient->__getLastRequest()). "\n";
            throw new \Exception('<strong>Soap Exception:</strong> ' . $soapFault);
        }
    }

      /**
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    private function getMutations($params)
    {
        try {
            $response = $this->soapClient->__soapCall("GetMutaties", [$params]);

            $this->checkforerror($response, "GetMutatiesResult");
            return $response->GetMutatiesResult;
        } catch(\SoapFault $soapFault) {
            throw new \Exception('<strong>Soap Exception:</strong> ' . $soapFault);
        }
    }

    
    /**
     * @param $dateFrom
     * @param $toDate
     * @return mixed
     */
    public function getMutationsByPeriod($dateFrom, $toDate)
    {
        $dateFrom = new Date($dateFrom);
        $toDate = new Date($toDate);

        $params = [
            "SecurityCode2" => $this->securityCode2,
            "SessionID" => $this->sessionId,
            "cFilter" => [
                "MutatieNr" => 0,
                "MutatieNrVan" => "",
                "MutatieNrTm" => "",
                "Factuurnummer" => "",
                "DatumVan" => $dateFrom->__toString(),
                "DatumTm" => $toDate->__toString()
            ]
        ];

        return $this->getMutations($params);
    }

    /**
     * @param $mutationId
     * @return mixed
     */
    public function getMutationsByMutationId($mutationId)
    {
        $mutationId = new MutationId($mutationId);

        $params = [
            "SecurityCode2" => $this->securityCode2,
            "SessionID" => $this->sessionId,
            "cFilter" => [
                "MutatieNr" => $mutationId->toInt(),
                "MutatieNrVan" => "",
                "MutatieNrTm" => "",
                "Factuurnummer" => "",
                "DatumVan" => "1980-01-01",
                "DatumTm" => "2049-12-31"
            ]
        ];
        return $this->getMutations($params);
    }

    /**
     * @param $startMutationId
     * @param $endMutationId
     * @return mixed
     */
    public function getMutationsByMutationsInRange($startMutationId, $endMutationId)
    {
        $startMutationId = new MutationId($startMutationId);
        $endMutationId = new MutationId($endMutationId);

        $params = [
            "SecurityCode2" => $this->securityCode2,
            "SessionID" => $this->sessionId,
            "cFilter" => [
                "MutatieNr" => 0,
                "MutatieNrVan" => $startMutationId->toInt(),
                "MutatieNrTm" => $endMutationId->toInt(),
                "Factuurnummer" => "",
                "DatumVan" => "1980-01-01",
                "DatumTm" => "2049-12-31"
            ]
        ];
        return $this->getMutations($params);
    }

    /**
     *
     */
    private function getAllRelations(&$relations)
    {
        $params = [
            "SecurityCode2" => $this->securityCode2,
            "SessionID" => $this->sessionId,
            "cFilter" => [
                "Trefwoord" => "",
                "Code" => "",
                "ID" => ""
            ]
        ];

        return $this->getRelations($params, $relations);
    }

    public function generateNewCode(&$newCode)
    {
        // Get all the relations
        if ($this->getAllRelations($relations)) {
            return TRUE;
        }

        $relations = $relations->Relaties;

        if (!is_array($relations->cRelatie)) {
            $relations->cRelatie = array($relations->cRelatie);
        }

        // Getting the code of each relation
        $codeArray = array();
        foreach ($relations->cRelatie as $Relatie) {
            if (is_numeric($Relatie->Code)) {
                array_push($codeArray, $Relatie->Code);
            }
        }

        // Calculate the highest code and create new one by adding 1
        $maxCode = max($codeArray);
        $newCode = (string)((int)$maxCode + 1);        

        return FALSE;
    }

    /**
     * @param $relationId
     * @return mixed
     */
    public function getRelationById($relationId, &$relation)
    {
        $relationId = new RelationId($relationId);

        $params = [
            "SecurityCode2" => $this->securityCode2,
            "SessionID" => $this->sessionId,
            "cFilter" => [
                "Trefwoord" => "",
                "Code" => "",
                "ID" => $relationId->toInt()
            ]
        ];

        $error = $this->getRelations($params, $relations);

        if (isset($relations->Relaties->cRelatie)) {
            $relation = $relations->Relaties->cRelatie;
        } else {
            $error = TRUE;
        }

        return $error;
    }

    /**
     * @param $relationCode
     * @return mixed
     */
    public function getRelationByCode($relationCode, &$relation)
    {
        $relationCode = new RelationCode($relationCode);

        $params = [
            "SecurityCode2" => $this->securityCode2,
            "SessionID" => $this->sessionId,
            "cFilter" => [
                "Trefwoord" => "",
                "Code" => $relationCode->__toString(),
                "ID" => ""
            ]
        ];

        $error = $this->getRelations($params, $relations);

        if (isset($relations->Relaties->cRelatie)) {
            $relation = $relations->Relaties->cRelatie;
        } else {
            $error = TRUE;
        }

        return $error;
    }

    /**
     * @param $searchString
     * @return mixed
     */
    public function getRelationBySearch($searchString)
    {
        $searchString = new RelationSearch($searchString);

        $params = [
            "SecurityCode2" => $this->securityCode2,
            "SessionID" => $this->sessionId,
            "cFilter" => [
                "Trefwoord" => $searchString->__toString(),
                "Code" => "",
                "ID" => ""
            ]
        ];

        return $this->getRelations($params);
    }

    /**
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    private function getRelations($params, &$relations)
    {
        try {
            $response = $this->soapClient->__soapCall("GetRelaties", [$params]);

            if ($this->checkforerror($response, "GetRelatiesResult")) { 
                return TRUE; 
            }

            $relations = $response->GetRelatiesResult;

            return FALSE; 
        } catch(\SoapFault $soapFault) {
            throw new \Exception('<strong>Soap Exception:</strong> ' . $soapFault);
        }
    }

    /**
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public function updateRelation(Relation $relation, &$response)
    {
        try {
            $params = [
                "SecurityCode2" => $this->securityCode2,
                "SessionID" => $this->sessionId,
                "oRel" => $relation->getEboekhoudenArray()
            ];
            
            $response = $this->soapClient->__soapCall("UpdateRelatie", [$params]);

            if ($this->checkforerror($response, "UpdateRelatieResult")) {
                return TRUE;
            }
            $response = $response->UpdateRelatieResult;
        } catch(\SoapFault $soapFault) {
            throw new \Exception('<strong>Soap Exception:</strong> ' . $soapFault);
        }
    }

    /**
     * @param $rawresponse
     * @param $sub
     */
    private function checkforerror($rawresponse, $sub)
    {
        if (isset($rawresponse->$sub->ErrorMsg->LastErrorCode)) {
            $LastErrorCode = $rawresponse->$sub->ErrorMsg->LastErrorCode;
            $LastErrorDescription = $rawresponse->$sub->ErrorMsg->LastErrorDescription;
            if ($LastErrorCode <> '') {
                /* TODO: hier een error log maken */
                echo '<strong>Er is een fout opgetreden:</strong><br>';
                echo $LastErrorCode . ': ' . $LastErrorDescription;
                return TRUE;
            }
        }
        return FALSE;
    }
}