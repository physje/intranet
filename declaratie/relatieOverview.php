<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/EB_functions.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
//$db = connect_db();

$i=0;

//$veld['ID']							= 'ID';
$veld['Code']						= 'Code';
$veld['Bedrijf']				= 'Bedrijf';
//$veld['Contactpersoon']	= 'Contactpersoon';
#$veld['Geslacht']				= 'Geslacht';
# $veld['Adres']					= 'Adres';
#$veld['Postcode']				= 'Postcode';
$veld['Plaats']					= 'Plaats';
//$veld['Land']						= 'Land';
//$veld['Adres2']					= 'Adres2';
//$veld['Postcode2']			= 'Postcode2';
//$veld['Plaats2']				= 'Plaats2';
//$veld['Land2']					= 'Land2';
#$veld['Telefoon']				= 'Telefoon';
#$veld['GSM']				= 'Mobiel';
//$veld['FAX']						= 'FAX';
$veld['Email']					= 'Email';
//$veld['Site']						= 'Site';
//$veld['Notitie']				= 'Notitie';
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
  	$rij[] = "<tr>";
  	  	
  	foreach($veld as $key => $dummy) {
  		$rij[] = "	<td>". ($Relatie-> $key != '' ? $Relatie-> $key : '&nbsp;') ."</td>";
  	}
  	       
    $rij[] = "</tr>";
  }


  // sessie sluiten
  $params = array("SessionID" => $SessionID);
  $response = $client->__soapCall("CloseSession", array($params));
} catch(SoapFault $soapFault) {
  echo '<strong>Er is een fout opgetreden:</strong><br>';
  echo $soapFault;
}

foreach($veld as $key => $dummy) {
	$kop[] = $dummy;
}


$page[] = '<table>';
$page[] = '<thead>';
$page[] = '<tr>';
foreach($veld as $key => $dummy) {
	$page[] = '<th>'. $dummy .'</th>';
}
$page[] = '</tr>';
$page[] = '</thead>';
$page = array_merge($page, $rij);
$page[] = '</table>';

$header[] = '<style>';
$header[] = '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
foreach($veld as $name) {
	$i++;
	$header[] = '	td:nth-of-type('.$i.'):before { content: "'. $name .'"; }';	
}
$header[] = "}";
$header[] = "</style>";	

$tables = array('default', 'table_rot');

echo showCSSHeader($tables, $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $page).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();
?>
