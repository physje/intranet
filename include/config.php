<?php
$temp = explode('/', $_SERVER['REQUEST_URI']);
if(count($temp) == 4) {
	include_once("Classes/Mysql.php");
} else {
	include_once("../Classes/Mysql.php");
}

# NL is een nieuwe regel
define("NL", "\n");

# Set locale to Dutch
# e-boekhouden is kieskeurig, dus alleen de tijd
setlocale(LC_TIME, 'nl_NL');

# Mocht er geen timezone bekend zijn : Europe/Amsterdam
date_default_timezone_set('Europe/Amsterdam');

$wijkArray			= array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'ICF');
$statusArray		= array('actief', 'afgemeld', 'afgevoerd', 'onttrokken', 'overleden', 'vertrokken');
$burgelijkArray	= array('gehuwd', 'gereg. partner', 'gescheiden', 'ongehuwd', 'weduwe', 'weduwnaar');
$gezinArray			= array('dochter', 'echtgenoot', 'echtgenote', 'gezinshoofd', 'levenspartner', 'zelfstandig', 'zoon');
$kerkelijkArray	= array('belijdend lid', 'betrokkene', 'dooplid', 'gast', 'gedoopt gastlid', 'geen lid', 'ongedoopt kind', 'overige');
$maandArray			= array(1 => 'jan', 2 => 'feb', 3 => 'mrt', 4 => 'apr', 5 => 'mei', 6 => 'jun', 7 => 'jul', 8 => 'aug', 9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'dec');
$maandArrayLang = array(1 => 'januari', 2 => 'februari', 3 => 'maart', 4 => 'april', 5 => 'mei', 6 => 'juni', 7 => 'juli', 8 => 'augustus', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december');
$maandArrayEng	= array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
$letterArray		= array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
$teamRollen			= array(1 => 'Ouderling', 2 => 'Diaken', 3 => 'Wijkco&ouml;rdinator', 4 => 'Bezoekbroeder', 5 => 'Bezoekzuster', 6 => 'Ge&iuml;ntereseerde', 7 => 'Predikant');

$VersionCount	= 252;

# Het lukt helaas nog niet om 2D-arrays in de config-module op de site te definieren.
# Vandaar hier
$declJGPost[0][1] = 'Professionalisering leiding';
$declJGPost[0][2] = 'Lief en leed';

$declJGPost[1][3] = 'Materialen';
$declJGPost[1][4] = 'Geschenken bij afscheid BK 5';

$declJGPost[2][5] = 'Lesmateriaal';
$declJGPost[2][6] = 'Materialen';
$declJGPost[2][7] = 'Geschenken bij afscheid BC8';

$declJGPost[3][8] = 'Materialen';
$declJGPost[3][9] = 'Sociale activiteiten';
$declJGPost[3][10] = 'Eten en drinken';
$declJGPost[3][11] = 'Kamp';
$declJGPost[3][12] = 'Schaatsactiviteit';
$declJGPost[3][13] = 'Ouderbijdrage kamp';

$declJGPost[4][14] = 'Lesmateriaal';
$declJGPost[4][15] = 'Materialen';
$declJGPost[4][16] = 'Sociale activiteiten';
$declJGPost[4][17] = 'Eten en Drinken';
$declJGPost[4][18] = 'Kamp';
$declJGPost[4][19] = 'Afscheid F4';
$declJGPost[4][20] = 'Ouderbijdrage kamp';

$declJGPost[5][21] = 'Diaconale activiteit';
$declJGPost[5][22] = 'Gezamenlijk eten';
$declJGPost[5][23] = 'Weekend';
$declJGPost[5][24] = 'Ouderbijdrage kamp';

$declJGPost[6][25] = 'Geloof en opvoeding toerusting ouders';
$declJGPost[6][26] = 'Kerstboekjes kinderen tot en met 12 jaar';
$declJGPost[6][27] = 'Onvoorzien';


# Doorloop de config-tabel en groepeer op naam
# In een array hebben alle value's dezelfde naam
# 	maar wisselende key's en value's
# Bij een integer/string/boolean worden alleen naam en value gebruikt
$db = new Mysql();
$data = $db->select("SELECT `name` FROM `config` GROUP BY `name`", true);
$names = array_column($data, 'name');

foreach($names as $name) {
	$db = new Mysql();
    $config = $db->select("SELECT * FROM `config` WHERE `name` like '$name'", true);

	foreach($config as $row) {
		# Als de key niet leeg is, is het dus een array
		if($row['sleutel'] != '') {
			# maak het nieuwe array-element aan
			$newValue = array(urldecode($row['sleutel']) => urldecode($row['value']));
			
			# Als de array waar het nieuwe array-element bij hoort al bestaat
			# worden oud en nieuw gemerged en anders is het nieuwe element de array
			if(isset($$name)) {	
				$$name = $$name + $newValue;
			} else {								
				$$name = $newValue;
			}
		} else {
			$$name = urldecode($row['value']);			
			
			if($row['value'] == 'true')		$$name = true;
			if($row['value'] == 'false')	$$name = false;
		}		
	}
}

$ScriptURL	= $ScriptServer.$ScriptURL;
$Version	= $Version.'.'.$VersionCount;

?>