<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/EB_functions.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
//$db = connect_db();

$veld['MutatieNr']						= 'MutatieNr';
//$veld['Soort']				= 'Soort';
$veld['Datum']	= 'Datum';
//$veld['Rekening']				= 'Rekening';
//$veld['Relatiecode']					= 'Relatiecode';
$veld['Factuurnummer']				= 'Factuurnummer';
$veld['Boekstuk']					= 'Boekstuk';
$veld['Omschrijving']						= 'Omschrijving';
//$veld['Betalingstermijn']					= 'Betalingstermijn';
//$veld['InExbtw']			= 'InExbtw';

try {
  $client = new SoapClient("https://soap.e-boekhouden.nl/soap.asmx?WSDL");
  
  // sessie openen en sessionid ophalen
  $params = array(
    "Username" => $ebUsername,
    "SecurityCode1" => $ebSecurityCode1,
    "SecurityCode2" => $ebSecurityCode2
  );
  
  $response = $client->__soapCall("OpenSession", array($params));  
  $SessionID = $response->OpenSessionResult->SessionID;
  
  // opvragen alle mutaties
  $params = array(
    "SecurityCode2" => $ebSecurityCode2,
    "SessionID" => $SessionID,
    cFilter => array(
    	"MutatieNr" => 0,
    	"MutatieNrVan" => 0,
    	"MutatieNrTm" => 0,
    	"Factuurnummer" => "",
    	"DatumVan" => date('Y-m-d', time()-(14*24*60*60)),
    	"DatumTm" => date('Y-m-d')
    )
  );
  
  $response = $client->__soapCall("GetMutaties", array($params));
  $Mutaties = $response->GetMutatiesResult->Mutaties;
    
  // indien een resultaat, dan even een array maken
  if(!is_array($Mutaties->cMutatieList)) $Mutaties->cMutatieList = array($Mutaties->cMutatieList);
  
  foreach ($Mutaties->cMutatieList as $Mutatie) {
  	if($Mutatie->Rekening == 2000) {
  		$cel = array();
  	
  		foreach($veld as $key => $dummy) {
  			if($key == 'Datum') {
  				$cel[] = date('j\&\n\b\s\p\;M', strtotime($Mutatie->Datum));
  			} else {
  				$cel[] = $Mutatie-> $key;
  			}
  		}
  		
  		$rij[] = "<tr>\n<td>". implode("</td>\n<td>", $cel) ."</td>\n</tr>";
  	}
  }
    
  // sessie sluiten
  $params = array("SessionID" => $SessionID);
  $response = $client->__soapCall("CloseSession", array($params));
} catch(SoapFault $soapFault) {
  echo '<strong>Er is een fout opgetreden:</strong><br>';
  echo $soapFault;  
}

foreach($veld as $key => $dummy) {
	$kop[] = '<b>'.$dummy .'</b>';
}


$page[] = '<table>';
$page[] = "<tr>\n<td>". implode("</td>\n<td>", $kop) ."</td>\n</tr>";
$page = array_merge($page, $rij);
$page[] = '</table>';

# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="5%">&nbsp;</td>'.NL;
echo '	<td valign="top">'. showBlock(implode("\n", $page), 100). '</td>'.NL;
echo '	<td valign="top" width="5%">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;

?>
