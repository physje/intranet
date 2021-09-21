<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/EB_functions.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$veld['MutatieNr']						= 'Nr';
//$veld['Soort']				= 'Soort';
$veld['Datum']	= 'Datum';
//$veld['Rekening']				= 'Rekening';
//$veld['Relatiecode']					= 'Relatiecode';
$veld['Factuurnummer']				= 'Factuurnummer';
$veld['Boekstuk']					= 'Boekstuk';
$veld['Omschrijving']						= 'Omschrijving';
//$veld['Betalingstermijn']					= 'Betalingstermijn';
//$veld['InExbtw']			= 'InExbtw';


$subVeld['BedragInvoer'] = 'Bedrag';
//$subVeld['BedragExclBTW'] = 'Bedrag (ex BTW)';
//$subVeld['BedragBTW'] = 'BTW-bedrag';
//$subVeld['BedragInclBTW'] = 'Bedrag (incl. BTW)';
//$subVeld['BTWCode'] = 'BTW-code';
//$subVeld['BTWPercentage'] = 'BTW-percentage';
//$subVeld['Factuurnummer'] = 'Factuurnummer';
//$subVeld['TegenrekeningCode'] = 'TegenrekeningCode';
//$subVeld['KostenplaatsID'] = 'KostenplaatsID';

# Default starttijd is maand geleden
# Default einddtijd is vandaag
$startTijd	= mktime(0,0,0,(date("n")-1),date("j"),date("Y"));
$eindTijd		= time();

$bDag		= getParam('bDag', date("d", $startTijd));
$bMaand	= getParam('bMaand', date("m", $startTijd));
$bJaar	= getParam('bJaar', date("Y", $startTijd));

$eDag		= getParam('eDag', date("d", $eindTijd));
$eMaand	= getParam('eMaand', date("m", $eindTijd));
$eJaar	= getParam('eJaar', date("Y", $eindTijd));

$start	= mktime (0,0,0,$bMaand,$bDag,$bJaar);
$end		= mktime (23,59,59,$eMaand,$eDag,$eJaar);

$zoekScherm[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$zoekScherm[] = "<table border=0 align='center'>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td><b>Van</b></td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td><b>Tot</b></td>";
$zoekScherm[] = "	<td rowspan='2'>&nbsp;</td>";
$zoekScherm[] = "	<td rowspan='2'><input type='submit' value='Zoeken' name='submit'></td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td><select name='bDag'>";
for($d=1 ; $d<=31 ; $d++)	{	$zoekScherm[] = "<option value='$d'". ($d == $bDag ? ' selected' : '') .">$d</option>";	}
$zoekScherm[] = "	</select><select name='bMaand'>";
for($m=1 ; $m<=12 ; $m++)	{	$zoekScherm[] = "<option value='$m'". ($m == $bMaand ? ' selected' : '') .">". $maandArray[$m] ."</option>";	}
$zoekScherm[] = "	</select><select name='bJaar'>";
for($j=(date('Y') - 1) ; $j<=(date('Y') + 1) ; $j++)	{	$zoekScherm[] = "<option value='$j'". ($j == $bJaar ? ' selected' : '') .">$j</option>";	}
$zoekScherm[] = "	</select></td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td><select name='eDag'>";
for($d=1 ; $d<=31 ; $d++)	{	$zoekScherm[] = "<option value='$d'". ($d == $eDag ? ' selected' : '') .">$d</option>";	}
$zoekScherm[] = "	</select><select name='eMaand'>";
for($m=1 ; $m<=12 ; $m++)	{	$zoekScherm[] = "<option value='$m'". ($m == $eMaand ? ' selected' : '') .">". $maandArray[$m] ."</option>";	}
$zoekScherm[] = "	</select><select name='eJaar'>";
for($j=(date('Y') - 1) ; $j<=(date('Y') + 1) ; $j++)	{	$zoekScherm[] = "<option value='$j'". ($j == $eJaar ? ' selected' : '') .">$j</option>";	}
$zoekScherm[] = "	</select></td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "</table>";
$zoekScherm[] = "</form>";

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
    "cFilter" => array(
    	"MutatieNr" => 0,
    	"MutatieNrVan" => 0,
    	"MutatieNrTm" => 0,
    	"Factuurnummer" => "",
    	"DatumVan" => date('Y-m-d', $start),
    	"DatumTm" => date('Y-m-d', $end)
    )
  );

  $response = $client->__soapCall("GetMutaties", array($params));
  $Mutaties = $response->GetMutatiesResult->Mutaties;

  // indien een resultaat, dan even een array maken
  if(!is_array($Mutaties->cMutatieList)) $Mutaties->cMutatieList = array($Mutaties->cMutatieList);

  foreach ($Mutaties->cMutatieList as $Mutatie) {
  	if($Mutatie->Rekening == 2000 OR isset($showAllMutaties)) {
  		$cel = array();
  		
  		// Mutatie-details uitpluizen
  		//$MutatieRegels = $Mutatie->MutatieRegels;
  		//$MutatieList = array($MutatieRegels->cMutatieListRegel);
  	
  		foreach($veld as $key => $dummy) {  			
  			if($key == 'Datum') {
  				$tijdstip = strtotime($Mutatie->Datum);
  				$cel[] = time2str('%e %b', $tijdstip);  			
  			} else {
  				$cel[] = $Mutatie-> $key;
  			}
  		}
  		  		
  		
  		foreach($subVeld as $key => $dummy) {
  			$string = $Mutatie->MutatieRegels->cMutatieListRegel->$key;
  			
  			if(($key=='BedragInvoer') OR ($key=='BedragInclBTW')) {
  				$cel[] = formatPrice($string*100, true);
  			} else {
  				$cel[] = $string;
  			}
  		}  		
  		
  		$id = $tijdstip.$Mutatie->Boekstuk;
  		$rij[$id] = "<tr>\n<td>". implode("</td>\n<td>", $cel) ."</td>\n</tr>";
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

foreach($subVeld as $key => $dummy) {
	$kop[] = '<b>'.$dummy .'</b>';
}

ksort($rij);
$reverseRij = array_reverse($rij);

$page[] = '<table>';
$page[] = "<tr>\n<td>". implode("</td>\n<td>", $kop) ."</td>\n</tr>";
$page = array_merge($page, $reverseRij);
$page[] = '</table>';

# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="5%">&nbsp;</td>'.NL;
echo '	<td valign="top">';
echo showBlock(implode("\n", $zoekScherm), 100);
echo '<br>';
echo showBlock(implode("\n", $page), 100);
echo '</td>'.NL;
echo '	<td valign="top" width="5%">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;

?>
