<?php

// variabelen definieren
// zie hiervoor e-boekhouden.nl -> ‘Beheer’ > ‘Instellingen’ > ‘Magento’.
try {
  $client = new SoapClient("https://soap.eboekhouden.nl/soap.asmx?WSDL");
  $Username = "[username]";
  $SecurityCode1 = "[securitycode1]";
  $SecurityCode2 = "[securitycode2]";

  // sessie openen en sessionid ophalen
  $params = array(
    "Username" => $Username,
    "SecurityCode1" => $SecurityCode1,
    "SecurityCode2" => $SecurityCode2
  );
  
  $response = $client->__soapCall("OpenSession", array($params));
  checkforerror($response, "OpenSessionResult");
  
  $SessionID = $response->OpenSessionResult->SessionID;
  echo "SessionID: " . $SessionID;
  echo "<hr>";
  
  // opvragen alle grootboekrekeningen van de categorie balans
  $params = array(
    "SecurityCode2" => $SecurityCode2,
    "SessionID" => $SessionID,
    cFilter => array(
      "ID" => 0,
      "Code" => "",
      "Categorie" => "BAL"
    )
  );
  
  $response = $client->__soapCall("GetGrootboekrekeningen", array($params));
  checkforerror($response, "GetGrootboekrekeningenResult");
  
  $Rekeningen = $response->GetGrootboekrekeningenResult->Rekeningen;
  
  // indien een resultaat, dan even een array maken
  if(!is_array($Rekeningen->cGrootboekrekening)) $Rekeningen->cGrootboekrekening = array($Rekeningen->cGrootboekrekening);
  
  // weergeven van alle opgehaalde grootboekrekeningen...
  echo '<table>';
  echo '<tr><th>ID</th><th>Code</th><th>Omschrijving</th>';
  echo '<th>Categorie</th><th>Groep</th></tr>';
  foreach ($Rekeningen->cGrootboekrekening as $Rekening) {
    echo '<tr>';
    echo '<td>' . $Rekening->ID . '</td>';
    echo '<td>' . $Rekening->Code . '</td>';
    echo '<td>' . $Rekening->Omschrijving . '</td>';
    echo '<td>' . $Rekening->Categorie . '</td>';
    echo '<td>' . $Rekening->Groep . '</td>';
    echo '</tr>';
  }
  echo '</table>';
  
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
