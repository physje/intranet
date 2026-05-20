<?php
$temp = explode('/', $_SERVER['REQUEST_URI']);

if(count($temp) == 4) {
	include_once("Classes/Mysql.php");
} elseif(count($temp) == 5) {
	include_once("../Classes/Mysql.php");
} else {
	include_once("../../Classes/Mysql.php");
}

include_once('version.php');

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


# Het lukt helaas nog niet om 2D-arrays in de config-module op de site te definieren.
# Vandaar hier
# Algemeen en staf
$declJGPost[0][1] = 'Toerusting leiders';
$declJGPost[0][2] = 'Toerusting ouders';
$declJGPost[0][3] = 'Kerstgeschenk kinderen 12-';
$declJGPost[0][4] = 'Lief en leed';
$declJGPost[0][5] = 'Onvoorzien';

# Bijbelklas
$declJGPost[1][6] = 'Materialen';
$declJGPost[1][7] = 'Bijeenkomsten';
$declJGPost[1][8] = 'Activiteiten';
$declJGPost[1][9] = 'Geschenken';

# Basiscatechese
$declJGPost[2][10] = 'Materialen';
$declJGPost[2][11] = 'Bijeenkomsten';
$declJGPost[2][12] = 'Activiteiten';
$declJGPost[2][13] = 'Geschenken';

# Follow light
$declJGPost[3][14] = 'Materialen';
$declJGPost[3][15] = 'Bijeenkomsten';
$declJGPost[3][16] = 'Activiteiten';
$declJGPost[3][17] = 'Ouderbijdrage';

# Follow
$declJGPost[4][18] = 'Materialen';
$declJGPost[4][19] = 'Bijeenkomsten';
$declJGPost[4][20] = 'Activiteiten';
$declJGPost[4][21] = 'Ouderbijdrage';

# FollowNext
$declJGPost[5][22] = 'Materialen';
$declJGPost[5][23] = 'Bijeenkomsten';
$declJGPost[5][24] = 'Activiteiten';
$declJGPost[5][25] = 'Ouder- / eigen bijdrage';

# Youth Alpha
$declJGPost[6][26] = 'Materialen';
$declJGPost[6][27] = 'Bijeenkomsten';
$declJGPost[6][28] = 'Activiteiten';
$declJGPost[6][29] = 'Ouder- / eigen bijdrage';

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