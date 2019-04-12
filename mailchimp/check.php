<?php
include_once('../include/functions.php');
include_once('../include/MC_functions.php');
include_once('../include/config.php');

$db = connect_db();

# Ga op zoek naar de persoon die het langst niet gecontroleerd is
$sql = "SELECT * FROM $TableMC ORDER BY $MClastChecked ASC LIMIT 0,1";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);
do {
	# 3 seconden per persoon moet voldoende zijn
	set_time_limit(3);
	
	# variabelen definieren vanuit de lokale tabel
	$scipioID	= $row[$MCID];
	$voor			= $row[$MCfname];
	$tussen		= $row[$MCtname];
	$achter		= $row[$MClname];
	$wijk			= $row[$MCwijk];	
	$email		= $row[$MCmail];
	
	# variabelen definieren vanuit de MC-data
	$data = mc_getData($email);
	$tags	= $data['tags'];	
	$segment_id = $tagWijk[$wijk];
	
	# Check of adres wel bestaat in MailChimp
	if($data['status'] != 'subscribed') {
		if(mc_resubscribe($email)) {
			toLog('info', '', $scipioID, 'Opnieuw ingeschreven in MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon niet opnieuw inschrijven in MailChimp');
		}
	}
	
	# Check of naam wel correct is in MailChimp
	if($data['voornaam'] != $voor OR urldecode($data['tussen']) != $tussen OR $data['achter'] != $achter) {
		if(mc_changename($email, $voor, $tussen, $achter)) {
			toLog('info', '', $scipioID, 'Opnieuw ingesteld in MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon naam niet opnieuw instellen in MailChimp');
		}
	}
	
	# Check of de tag 'Wijk ?' aan dit adres hangt
	if(!array_key_exists($segment_id, $tags)) {
		if(mc_addtag($email, $segment_id)) {
			toLog('info', '', $scipioID, 'Wijk opnieuw ingesteld in MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon wijk niet opnieuw instellen in MailChimp');
		}
	}
	
	# Check of de tag 'Scipio' aan dit adres hangt
	if(!array_key_exists($tagScipio, $tags)) {
		if(mc_addtag($email, $tagScipio)) {
			toLog('info', '', $scipioID, 'Scipio-tag opnieuw ingesteld in MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon scipio-tag niet opnieuw instellen in MailChimp');
		}			
	}
	
	# Check of ScipioID wel is ingevuld in MailChimp
	if($data['scipio'] == '') {
		if(mc_addSipioID($email, $scipioID)) {
			toLog('info', '', $scipioID, 'ScipioID toegevoegd in MailChimp');
		} else {
			toLog('error', '', $scipioID, 'Kon scipio-ID niet toevoegen in MailChimp');
		}
	}
	
		
	# De wijzigingen aan de MC kant moeten ook verwerkt worden in mijn lokale mailchimp-database
	$sql_update = "UPDATE $TableMC SET $MClastChecked = ". time() . " WHERE $MCID like $scipioID";
	mysqli_query($db, $sql_update);
	
	toLog('debug', '', $scipioID, 'Gecontroleerd in MailChimp');
	
} while($row = mysqli_fetch_array($result));


?>
