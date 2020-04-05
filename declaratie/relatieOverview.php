<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/EB_functions.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include_once($cfgProgDir. "secure.php");
//$db = connect_db();

//$veld['ID']							= 'ID';
$veld['Code']						= 'Code';
$veld['Bedrijf']				= 'Bedrijf';
//$veld['Contactpersoon']	= 'Contactpersoon';
//$veld['Geslacht']				= 'Geslacht';
$veld['Adres']					= 'Adres';
$veld['Postcode']				= 'Postcode';
$veld['Plaats']					= 'Plaats';
//$veld['Land']						= 'Land';
//$veld['Adres2']					= 'Adres2';
//$veld['Postcode2']			= 'Postcode2';
//$veld['Plaats2']				= 'Plaats2';
//$veld['Land2']					= 'Land2';
//$veld['Telefoon']				= 'Telefoon';
//$veld['FAX']						= 'FAX';
//$veld['Email']					= 'Email';
//$veld['Site']						= 'Site';
$veld['Notitie']				= 'Notitie';
//$veld['Bankrekening']		= 'Bankrekening';
//$veld['Girorekening']		= 'Girorekening';
//$veld['Aanhef']					= 'Aanhef';
$veld['IBAN']						= 'IBAN';
//$veld['BIC']						= 'BIC';
//$veld['BP']							= 'BP';

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
  $Relaties = $response->GetRelatiesResult->Relaties;
 
  // indien een resultaat, dan even een array maken
  if(!is_array($Relaties->cRelatie)) $Relaties->cRelatie = array($Relaties->cRelatie);
  
  foreach ($Relaties->cRelatie as $Relatie) {
  	$cel = array();
  	
  	foreach($veld as $key => $dummy) {
  		$cel[] = $Relatie-> $key;
  	}
  	
    $rij[] = "<tr>\n<td>". implode("</td>\n<td>", $cel) ."</td>\n</tr>";
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
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '	<td valign="top">'. showBlock(implode("\n", $page), 100). '</td>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;
?>
