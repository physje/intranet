<?php
include_once('../include/config.php');

# https://secure.e-boekhouden.nl/handleiding/Documentatie_soap.pdf


// variabelen definieren
// zie hiervoor e-boekhouden.nl -> ‘Beheer’ > ‘Instellingen’ > ‘Magento’.
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
  
  //var_dump($response);
  
  $Relaties = $response->GetRelatiesResult->Relaties;
  
  //var_dump($Relaties);
  //is_array($Relaties->cGetRelaties)
  
  // indien een resultaat, dan even een array maken
  if(!is_array($Relaties->cRelatie)) $Relaties->cRelatie = array($Relaties->cRelatie);
  
  
  echo '<table border=1>';
	echo '	<th>ID</td>';
	//echo '	<th>Adddatum</td>';
	echo '	<th>Code</td>';
	echo '	<th>Bedrijf</td>';
	echo '	<th>Contactpersoon</td>';
	echo '	<th>Geslacht</td>';
	echo '	<th>Adres</td>';
	echo '	<th>Postcode</td>';
	echo '	<th>Plaats</td>';
	echo '	<th>Land</td>';
	echo '	<th>Adres2</td>';
	echo '	<th>Postcode2</td>';
	echo '	<th>Plaats2</td>';
	echo '	<th>Land2</td>';
	echo '	<th>Telefoon</td>';
	echo '	<th>FAX</td>';
	echo '	<th>Email</td>';
	echo '	<th>Site</td>';
	echo '	<th>Notitie</td>';
	echo '	<th>Bankrekening</td>';
	echo '	<th>Girorekening</td>';
	//echo '	<th>Btw-nummer</td>';
	echo '	<th>Aanhef</td>';
	echo '	<th>IBAN</td>';
	echo '	<th>BIC</td>';
	echo '	<th>BP</td>';
  foreach ($Relaties->cRelatie as $Relatie) {
    echo '<tr>';
		echo '	<td>'. $Relatie->ID .'</td>';
		//echo '	<td>'. $Relatie->Adddatum .'</td>';
		echo '	<td>'. $Relatie->Code .'</td>';
		echo '	<td>'. $Relatie->Bedrijf .'</td>';
		echo '	<td>'. $Relatie->Contactpersoon .'</td>';
		echo '	<td>'. $Relatie->Geslacht .'</td>';
		echo '	<td>'. $Relatie->Adres .'</td>';
		echo '	<td>'. $Relatie->Postcode .'</td>';
		echo '	<td>'. $Relatie->Plaats .'</td>';
		echo '	<td>'. $Relatie->Land .'</td>';
		echo '	<td>'. $Relatie->Adres2 .'</td>';
		echo '	<td>'. $Relatie->Postcode2 .'</td>';
		echo '	<td>'. $Relatie->Plaats2 .'</td>';
		echo '	<td>'. $Relatie->Land2 .'</td>';
		echo '	<td>'. $Relatie->Telefoon .'</td>';
		echo '	<td>'. $Relatie->FAX .'</td>';
		echo '	<td>'. $Relatie->Email .'</td>';
		echo '	<td>'. $Relatie->Site .'</td>';
		echo '	<td>'. $Relatie->Notitie .'</td>';
		echo '	<td>'. $Relatie->Bankrekening .'</td>';
		echo '	<td>'. $Relatie->Girorekening .'</td>';
		//echo '	<td>'. $Relatie->Btw .'</td>';
		echo '	<td>'. $Relatie->Aanhef .'</td>';
		echo '	<td>'. $Relatie->IBAN .'</td>';
		echo '	<td>'. $Relatie->BIC .'</td>';
		echo '	<td>'. $Relatie->BP .'</td>';
    echo '</tr>';
  }
  echo '</table>';
  
  
  /*
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
  */
  
  
  
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
