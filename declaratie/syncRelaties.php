<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');

$db = connect_db();

$sql = "TRUNCATE $TableEBoekhouden";
mysqli_query($db, $sql);


try {
  $client = new SoapClient("https://soap.e-boekhouden.nl/soap.asmx?WSDL");
  
  // sessie openen en sessionid ophalen
  $params = array(
    "Username" => $ebUsername,
    "SecurityCode1" => $ebSecurityCode1,
    "SecurityCode2" => $ebSecurityCode2
  );
  
  $response = $client->__soapCall("OpenSession", array($params));
  checkforerror($response, "OpenSessionResult");
  
  $SessionID = $response->OpenSessionResult->SessionID;
  echo "SessionID: " . $SessionID;
  echo "<hr>";
  
  // opvragen alle relaties
  $params = array(
    "SecurityCode2" => $ebSecurityCode2,
    "SessionID" => $SessionID,
    cFilter => array(
    	"Trefwoord" => "",
    	"Code" => "",
    	"ID" => 0,
    )
  );
  
  $response = $client->__soapCall("GetRelaties", array($params));
  checkforerror($response, "GetRelaties");
  
  $Relaties = $response->GetRelatiesResult->Relaties;
 
  # indien een resultaat, dan even een array maken
  if(!is_array($Relaties->cRelatie)) $Relaties->cRelatie = array($Relaties->cRelatie);
    
  foreach ($Relaties->cRelatie as $Relatie) {  	
  	$sql = "INSERT INTO $TableEBoekhouden ($EBoekhoudenID, $EBoekhoudenCode, $EBoekhoudenIBAN, $EBoekhoudenNaam) VALUES (". $Relatie->ID .", ". $Relatie->Code .", '". $Relatie->IBAN ."',  '". $Relatie->Bedrijf ."')";
  	mysqli_query($db, $sql);
  } 
  
  // sessie sluiten
  $params = array("SessionID" => $SessionID);
  $response = $client->__soapCall("CloseSession", array($params));
} catch(SoapFault $soapFault) {
  echo '<strong>Er is een fout opgetreden:</strong><br>';
  echo $soapFault;  
}

// standaard error afhandeling
function checkforerror($rawresponse, $sub) {
  $LastErrorCode = $rawresponse->$sub->ErrorMsg->LastErrorCode;
  $LastErrorDescription = $rawresponse->$sub->ErrorMsg->LastErrorDescription;
  if($LastErrorCode <> '') {
    echo '<strong>Er is een fout opgetreden:</strong><br>';
    echo $LastErrorCode . ': ' . $LastErrorDescription;
    exit();
  }
}
?>
