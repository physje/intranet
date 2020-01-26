<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');

$standaardVelden[] = array('name' => 'voornaam', 'datatype' => 'text', 'required' => 'false', 'in_form' => 'true', 'in_list' => 'true');
$standaardVelden[] = array('name' => 'tussenvoegsel', 'datatype' => 'text', 'required' => 'false', 'in_form' => 'true', 'in_list' => 'true');
$standaardVelden[] = array('name' => 'achternaam', 'datatype' => 'text', 'required' => 'false', 'in_form' => 'true', 'in_list' => 'true');
$standaardVelden[] = array('name' => 'geslacht', 'datatype' => 'select_single', 'datatype_display' => 'select', 'options' => array('Man', 'Vrouw'), 'required' => 'false', 'in_form' => 'false', 'in_list' => 'true');
$velden = $standaardVelden;
$velden[] = array('name' => '3GK-adres', 'datatype' => 'select_single', 'datatype_display' => 'radio', 'options' => array('Ja', 'Nee'), 'defaultvalue' => 'Nee', 'required' => 'false', 'in_form' => 'false', 'in_list' => 'false');

# Wijkmails
foreach($LPWijkListID as $wijk => $code) {
	$info['name'] = 'Wijkmail wijk '. $wijk;
	$info['remarks'] = 'Lijst met alle mensen die de wijkmail van wijk '. $wijk .' willen ontvangen';

	$list = lp_createList($info);
	
	$sql = "UPDATE $TableConfig SET $ConfigValue = '$list' WHERE $ConfigName like 'LPWijkListID' AND $ConfigKey like '$wijk'";
	mysqli_query($db, $sql);
	echo $sql .";\n";

	foreach($velden as $veld) {
		lp_addFieldToList($list, $veld);
	}
}

# Trnitas
$info['name'] = 'Trinitas';
$info['remarks'] = 'Lijst met alle mensen die de Trinitas online willen ontvangen';

$list = lp_createList($info);

$sql = "UPDATE $TableConfig SET $ConfigValue = '$list' WHERE $ConfigName like 'LPTrinitasListID'";
mysqli_query($db, $sql);
echo $sql .";\n";


foreach($velden as $veld) {
	lp_addFieldToList($list, $veld);
}

# Koningsmail
$info['name'] = 'Koningsmail';
$info['remarks'] = 'Lijst met alle mensen die de Koningsmail willen ontvangen';

$list = lp_createList($info);

$sql = "UPDATE $TableConfig SET $ConfigValue = '$list' WHERE $ConfigName like 'LPKoningsmailListID'";
mysqli_query($db, $sql);
echo $sql .";\n";

foreach($velden as $veld) {
	lp_addFieldToList($list, $veld);
}


# Maandelijkse gebedskalender
$info['name'] = 'Gebedskalender (maandelijks)';
$info['remarks'] = 'Lijst met alle mensen die maandelijks de gebedskalender willen ontvangen';

$list = lp_createList($info);

$sql = "UPDATE $TableConfig SET $ConfigValue = '$list' WHERE $ConfigName like 'LPGebedMaandListID'";
mysqli_query($db, $sql);
echo $sql .";\n";

foreach($velden as $veld) {
	lp_addFieldToList($list, $veld);
}



# Wekelijks gebedskalender
$info['name'] = 'Gebedskalender (wekelijks)';
$info['remarks'] = 'Lijst met alle mensen die wekelijks de gebedskalender willen ontvangen';

$list = lp_createList($info);

$sql = "UPDATE $TableConfig SET $ConfigValue = '$list' WHERE $ConfigName like 'LPGebedWeekListID'";
mysqli_query($db, $sql);
echo $sql .";\n";

foreach($velden as $veld) {
	lp_addFieldToList($list, $veld);
}



# Dagelijks gebedskalender
$info['name'] = 'Gebedskalender (dagelijks)';
$info['remarks'] = 'Lijst met alle mensen die dagelijks de gebedskalender willen ontvangen';

$list = lp_createList($info);

$sql = "UPDATE $TableConfig SET $ConfigValue = '$list' WHERE $ConfigName like 'LPGebedDagListID'";
mysqli_query($db, $sql);
echo $sql .";\n";

foreach($velden as $veld) {
	lp_addFieldToList($list, $veld);
}

/*
# Ledenlijst
$extraVelden[] = array('name' => 'wijk', 'datatype' => 'select_single', 'datatype_display' => 'select', 'options' => array('A','B','C','D','E','F','G','H','I','J'), 'required' => 'false', 'in_form' => 'false', 'in_list' => 'true');
$extraVelden[] = array('name' => 'geboortedatum', 'datatype' => 'date', 'required' => 'false', 'in_form' => 'false', 'in_list' => 'false');
$extraVelden[] = array('name' => 'scipio id', 'datatype' => 'numeric', 'required' => 'false', 'in_form' => 'false', 'in_list' => 'false');
$extraVelden[] = array('name' => 'status', 'datatype' => 'select_single', 'datatype_display' => 'select', 'options' => array('belijdend lid','dooplid','betrokkene'), 'required' => 'false', 'in_form' => 'false', 'in_list' => 'false');
$extraVelden[] = array('name' => 'relatie', 'datatype' => 'select_single', 'datatype_display' => 'select', 'options' => array('dochter','echtgenoot','echtgenote','gezinshoofd','levenspartner','zelfstandig','zoon'), 'required' => 'false', 'in_form' => 'false', 'in_list' => 'false');
$velden = array_merge($standaardVelden, $extraVelden);

$info['name'] = 'Ledenlijst';
$info['remarks'] = 'Lijst met alle leden van de Koningskerk waar een mailadres van bekend is';

$list = lp_createList($info);

$sql = "UPDATE $TableConfig SET $ConfigValue = '$list' WHERE $ConfigName like 'LPLedenListID'";
mysqli_query($db, $sql);
echo $sql .";\n";

foreach($velden as $veld) {
	lp_addFieldToList($list, $veld);
}
*/