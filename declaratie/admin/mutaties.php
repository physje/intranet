<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../Classes/Declaratie.php');
include_once('../Classes/Member.php');

$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$i=0;

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
$eindTijd	= time();

$bDag		= getParam('bDag', date("d", $startTijd));
$bMaand		= getParam('bMaand', date("m", $startTijd));
$bJaar		= getParam('bJaar', date("Y", $startTijd));

$eDag		= getParam('eDag', date("d", $eindTijd));
$eMaand		= getParam('eMaand', date("m", $eindTijd));
$eJaar		= getParam('eJaar', date("Y", $eindTijd));

$start		= mktime (0,0,0,$bMaand,$bDag,$bJaar);
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
  		$data = array();
  		
  		$data[] = "<tr>";
  		
  		// Mutatie-details uitpluizen
  		//$MutatieRegels = $Mutatie->MutatieRegels;
  		//$MutatieList = array($MutatieRegels->cMutatieListRegel);
  	
  		foreach($veld as $key => $dummy) {
  			$data[] = "<td>";
  			if($key == 'Datum') {
  				$tijdstip = strtotime($Mutatie->Datum);
  				$data[] = time2str('d LLL', $tijdstip);  			
  			} else {
  				$data[] = $Mutatie-> $key;
  			}
  			$data[] = "</td>";
  		}
  		  		
  		
  		foreach($subVeld as $key => $dummy) {
  			$string = $Mutatie->MutatieRegels->cMutatieListRegel->$key;
  			
  			$data[] = "<td>";
  			if(($key=='BedragInvoer') OR ($key=='BedragInclBTW')) {
  				$data[] = formatPrice($string*100, true);
  			} else {
  				$$data[] = $string;
  			}
  			$data[] = "</td>";
  		}  		
  		
  		$data[] = "</tr>";
  		
  		$id = $tijdstip.$Mutatie->Boekstuk;
  		$rij[$id] = implode(NL, $data);
  	}
  }

  // sessie sluiten
  $params = array("SessionID" => $SessionID);
  $response = $client->__soapCall("CloseSession", array($params));
} catch(SoapFault $soapFault) {
  echo '<strong>Er is een fout opgetreden:</strong><br>';
  echo $soapFault;
}

ksort($rij);
$reverseRij = array_reverse($rij);

$page[] = '<table>';
$page[] = '<thead>';
$page[] = '<tr>';

foreach($veld as $key => $dummy) {	
	$page[] = "	<th>".$dummy ."</th>";
}
foreach($subVeld as $key => $dummy) {
	$page[] = "	<th>".$dummy ."</th>";
}
$page[] = "</tr>";
$page[] = '</thead>';
$page = array_merge($page, $reverseRij);
$page[] = '</table>';

# Pagina tonen
$header[] = '<style>';
$header[] = '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
foreach($veld as $name) {
	$i++;
	$header[] = '	td:nth-of-type('.$i.'):before { content: "'. $name .'"; }';	
}
foreach($subVeld as $key => $dummy) {
	$i++;
	$header[] = '	td:nth-of-type('.$i.'):before { content: "'. $dummy .'"; }';	
}

$header[] = "}";
$header[] = "</style>";	

$tables = array('default', 'table_rot');

echo showCSSHeader($tables, $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $zoekScherm).NL."</div>".NL;
echo "<div class='content_block'>".NL. implode(NL, $page).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();
?>

?>
