<?php
include_once ('ValueObjects/MutationId.php');
include_once ('ValueObjects/Date.php');
include_once ('ValueObjects/RelationCode.php');
include_once ('ValueObjects/RelationId.php');
include_once ('ValueObjects/RelationSearch.php');

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
            // The trace param in the SoapClient constructor below will enable trace possibilities.
            // For example: The last sent xml can be requested for analyzing and/or debugging when trace is true by running the following piece of code after a soapCall
            //              echo "REQUEST:\n". htmlentities($this->soapClient->__getLastRequest()). "\n";
            $this->soapClient = new \SoapClient("https://soap.e-boekhouden.nl/soap.asmx?WSDL", array('trace' => false, 'exceptions' => true));

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
            // SoapFault will not be thrown further since this can hang up the program when another exception is occurred before the __destruct is called.
        }
    }

    /**
     * @param Mutation $mutation
     * @return mixed
     * @throws \Exception
     */
    public function addMutation(Mutation $mutation)
    {
        try {
            $params = [
                "SecurityCode2" => $this->securityCode2,
                "SessionID" => $this->sessionId,
                "oMut" => $mutation->getMutationArray()
            ];

            $response = $this->soapClient->__soapCall("AddMutatie", [$params]);

            $this->checkforerror($response, "AddMutatieResult");

            return $response->AddMutatieResult;
        } catch(\SoapFault $soapFault) {
            throw new \Exception('<strong>Soap Exception:</strong> ' . $soapFault);
        }
    }

    /**
     * @param Relation $relation
     * @return mixed
     * @throws \Exception
     */
    public function addRelation(Relation $relation)
    {
        try {
            $params = [
                "SecurityCode2" => $this->securityCode2,
                "SessionID" => $this->sessionId,
                "oRel" => $relation->getEboekhoudenArray()
            ];

            $response = $this->soapClient->__soapCall("AddRelatie", [$params]);

            $this->checkforerror($response, "AddRelatieResult");

            return  $response->AddRelatieResult;
        } catch(\SoapFault $soapFault) {
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
    public function getAllRelations()
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

        return $this->getRelations($params);
    }

    /**
     * @return string $newCode
     */
    public function generateNewCode()
    {
        // Get all the relations
        $relations = $this->getAllRelations();
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

        // Calculate the highest code and create new one by adding 1. Offset for new relatieCode is 9000 for relations created via SOAP API.
        $maxCode = max($codeArray);
        $newCode = "";
        if ( (int)$maxCode < 9000 ) {
            $newCode = "9000";
        } else {
            $newCode = (string)((int)$maxCode + 1);
        }

        return $newCode;
    }

    /**
     * @param  string $iban
     * @return string $code
     */
    public function getRelationByIban($iban)
    {
        // Get all the relations
        $relations = $this->getAllRelations();
        $relations = $relations->Relaties;

        $code = "";

        if (!is_array($relations->cRelatie)) {
            $relations->cRelatie = array($relations->cRelatie);
        }

        // search through the relations to check if there is a match
        foreach ($relations->cRelatie as $Relatie) {
            // Remove spaces
            $relatieIban = str_replace(' ', '', $Relatie->IBAN);
            if ( !strcasecmp($relatieIban, $iban) ) {
                // match!!
                $code = $Relatie->Code;
            }
        }

        return $code;
    }

    /**
     * @param  int    $relationId
     * @return mixed  $relation
     */
    public function getRelationById($relationId)
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

        $relations = $this->getRelations($params);

        if (isset($relations->Relaties->cRelatie)) {
            $relation = $relations->Relaties->cRelatie;
        } else {
            throw new \Exception("Problems with getRelations: RelationId ".$relationId." has no relation results in the reponse.");
        }

        return $relation;
    }

    /**
     * @param $relationCode
     * @return mixed
     */
    public function getRelationByCode($relationCode)
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

        $relations = $this->getRelations($params);

        if (isset($relations->Relaties->cRelatie)) {
            $relation = $relations->Relaties->cRelatie;
        } else {
            throw new \Exception("Problems with getRelations: RelationCode ".$relationCode." has no relation results in the reponse.");
        }

        return $relation;
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

        $relations = $this->getRelations($params);
        $relations = $relations->Relaties;

        $code = array();

        if (isset($relations->cRelatie)) {
            if (!is_array($relations->cRelatie)) {
                $relations->cRelatie = array($relations->cRelatie);
            }

            // Get all the relation codes
            foreach ($relations->cRelatie as $Relatie) {
                array_push($code, $Relatie->Code);
            }
        }

        return $code;
    }

    /**
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    private function getRelations($params)
    {
        try {
            $response = $this->soapClient->__soapCall("GetRelaties", [$params]);

            $this->checkforerror($response, "GetRelatiesResult");

            return $response->GetRelatiesResult;
        } catch(\SoapFault $soapFault) {
            throw new \Exception('<strong>Soap Exception:</strong> ' . $soapFault);
        }
    }

    /**
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public function updateRelation(Relation $relation)
    {
        try {
            $params = [
                "SecurityCode2" => $this->securityCode2,
                "SessionID" => $this->sessionId,
                "oRel" => $relation->getEboekhoudenArray()
            ];

            $response = $this->soapClient->__soapCall("UpdateRelatie", [$params]);

            $this->checkforerror($response, "UpdateRelatieResult");
            return  $response->UpdateRelatieResult;
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
                // Throw the error as an exception
                throw new \SoapFault($LastErrorCode, $LastErrorDescription);
            }
        }
    }
}