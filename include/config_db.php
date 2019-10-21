<?php
# Productie-omgeving
$dbHostname	= "";	// Hostname van de SQL-dB, meestal localhost
$dbUsername	= "";	// Username van de SQL-dB
$dbPassword	= "";	// Password van de SQL-dB
$dbName		= "";	// Database in de SQL-dB

# Sommige scripts mogen alleen vanaf 1 IP (= server) gedraaid worden
$allowedIP	= array('');

$ScriptSever	= '';
$ScriptURL	= $ScriptSever.'';
$ScriptTitle	= 'Koningskerk Roosters';	# Naam van het script (is naam van afzender in mails)
$ScriptMailAdress	= '';			# Mailadres van het script (is mailadres van afzender in mails)
$Version		= '';		# Versie nummer
$SubjectPrefix		= '[3GK] ';		# Voorvoegsel bij de onderwerpregel bij het versturen van mails

# Inloggegevens voor Scipio
$scipioParams = array(
	'Username' => '',
	'Password' => '',
	'Pincode' => ''
);

# Inloggegevens voor e-Boekhouden
$ebUsername = "";
$ebSecurityCode1 = "";
$ebSecurityCode2 = "";

# In de mail naar de voorganger moeten aantal adressen
# als CC en als BCC worden opgenomen
$voorgangerReplyName = '';
$voorgangerReplyAddress = '';

$voorgangerCC = array(
	'' => ''
);
$voorgangerBCC = array(
	'' => ''
);

# Mailchimp gegevens
$MC_apikey = '';
$MC_listid = '';
$MC_server = '';

$tagWijk = array(
	'A' => ,
	'B' => ,
	'C' => ,
	'D' => ,
	'E' => ,
	'F' => ,
	'G' => ,
	'H' => ,
	'I' => ,
	'J' => 
);

# De verschillende kerkelijke relaties hebben allemaal een andere tag in MailChimp
$tagRelatie = array(
	'dochter' => ,
	'echtgenoot' => ,
	'echtgenote' => ,
	'gezinshoofd' => ,
	'levenspartner' => ,
	'zelfstandig' => ,
	'zoon' => 
);

# Kerkelijke status
$tagStatus = array(
	'belijdend lid' => ,
	'betrokkene' => ,
	'dooplid' => 
);

# Als het adres vanuit Scipio komt krijgt die ook een tag
$tagScipio = ;

# De verschillende maillijsten hebben allemaal een ander id in MailChimp
$ID_google = "";
$ID_wijkmails = "";
$ID_gebed_dag = "";
$ID_gebed_week = "";
$ID_gebed_maand = "";
$ID_trinitas = "";
?>