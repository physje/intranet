<?php
# NL is een nieuwe regel
define("NL", "\n");

# Set locale to Dutch
# e-boekhouden is kieskeurig, dus alleen de tijd
setlocale(LC_TIME, 'nl_NL');

# Mocht er geen timezone bekend zijn : Europe/Amsterdam
date_default_timezone_set('Europe/Amsterdam');

# Tabel- en veldnamen voor de verschillende tabellen in MySQL
$TableUsers										= "leden";
$UserID												= "scipio_id";
$UserStatus										= "status";
$UserAdres										= "kerk_adres";
$UserGeslacht									= "geslacht";
$UserVoorletters							= "voorletters";
$UserVoornaam									= "voornaam";
$UserTussenvoegsel						= "tussenvoegsel";
$UserAchternaam								= "achternaam";
$UserMeisjesnaam							= "meisjesnaam";
$UserStraat										= "straat";
$UserHuisnummer								= "nummer";
$UserHuisletter								= "letter";
$UserToevoeging								= "toevoeging";
$UserPC												= "postcode";
$UserPlaats										= "plaats";
$UserGeboorte									= "geboortedatum";
$UserTelefoon									= "telefoon";
$UserMail											= "email";
$UserFormeelMail							= "formeel";
$UserBelijdenis								= "belijdenis";
$UserBurgelijk								= "burgstaat";
$UserRelatie									= "relatie";
$UserVestiging								= "vestiging";
$UserLastChange								= "last_change";
$UserLastVisit								= "last_visit";
$UserWijk											= "wijk";
$UserEBRelatie								= "eb_code";
$UserUsername									= "username";
$UserPassword									= "password";
$UserNewPassword							= "password_new";
#$UserHash											= "hash";
$UserHashShort								= "hash_short";
$UserHashLong									= "hash_long";
                    					
$TableGroups									= "groepen";
$GroupID											= "id";
$GroupNaam										= "naam";
$GroupHTMLIn									= "html_intern";
$GroupHTMLEx									= "html_extern";
$GroupBeheer									= "beheerder";
$GroupMCTag										= "tag";
                    					
$TableRoosters								= "roosters";
$RoostersID										= "id";
$RoostersNaam									= "naam";
$RoostersBeheerder						= "beheerder";
$RoostersGroep								= "groep";
$RoostersPlanner							= "planner";
$RoostersFields								= "aantal";
$RoostersReminder							= "reminder";
$RoostersMail									= "mail";
$RoostersSubject							= "onderwerp";
$RoostersFrom									= "naam_afzender";
$RoostersFromAddr							= "mail_afzender";
$RoostersGelijk								= "gelijke_diensten";
$RoostersOpmerking						= "opmerking";
$RoostersTextOnly							= "text_only";
$RoostersAlert								= "alert";
$RoostersLastChange						= "last_change";
                    					
$TableGrpUsr									= "group_member";
$GrpUsrGroup									= "commissie";
$GrpUsrUser										= "lid";
                    					
$TableDiensten								= "kerkdiensten";
$DienstID											= "id";
$DienstStart									= "start";
$DienstEind										= "eind";
$DienstVoorganger							= "voorganger";
$DienstCollecte_1							= "collecte_1";
$DienstCollecte_2							= "collecte_2";
$DienstOpmerking							= "opmerking";
$DienstRuiling								= "ruiling";
$DienstLiturgie     					= "liturgie";
$DienstDeclStatus   					= "declaratie_status";
                    					
$TablePlanning								= "planning";
$PlanningDienst								= "dienst";
$PlanningGroup								= "commissie";
$PlanningUser									= "lid";
$PlanningPositie							= "positie";
                    					
$TablePlanningTxt							= "planning_tekst";
$PlanningTxTDienst						= "dienst";
$PlanningTxTGroup							= "rooster";
$PlanningTxTText							= "text";
                    					
$TableAgenda									= "agenda";
$AgendaID 										= "id";
$AgendaStart 									= "start";
$AgendaEind 									= "eind";
$AgendaTitel									= "titel";
$AgendaDescr 									= "beschrijving";
$AgendaOwner									= "eigenaar";
                    					
$TableLog											= "log";
$LogID												= "id";
$LogTime											= "tijd";
$LogType											= "type";
$LogUser											= "dader";
$LogSubject										= "slachtoffer";
$LogMessage										= "message";
                    					
$TableRoosOpm									= "rooster_opmerkingen";
$RoosOpmID										= "id";
$RoosOpmRoos									= "rooster";
$RoosOpmDienst								= "dienst";
$RoosOpmOpmerking							= "opmerking";

$TableVoorganger 							= "predikanten";
$VoorgangerID 								= "id";
$VoorgangerTitel 							= "titel";
$VoorgangerVoor								= "voornaam";
$VoorgangerInit 							= "initialen";
$VoorgangerTussen 						= "tussen";
$VoorgangerAchter 						= "achternaam";
$VoorgangerTel 								= "telefoon";
$VoorgangerTel2 							= "mobiel";
$VoorgangerPVNaam 						= "naam_pv";
$VoorgangerPVTel 							= "tel_pv";
$VoorgangerMail 							= "mail";
$VoorgangerPlaats 						= "plaats";
$VoorgangerDenom							= "kerk";
$VoorgangerStijl							= "stijl";
$VoorgangerOpmerking					= "opmerking";
$VoorgangerAandacht						= "aandachtspunten";
$VoorgangerDeclaratie   			= "declaratie";
$VoorgangerHonorarium					= "honorarium";
$VoorgangerHonorariumOld			= "honorarium_2019";
$VoorgangerHonorariumNew			= "honorarium_2020";
$VoorgangerHonorariumSpecial	= "honorarium_special";
$VoorgangerKM									= "km_vergoeding";
$VoorgangerVertrekpunt				= "vertrekpunt";
$VoorgangerEBRelatie					= "boekhoudenID";
$VoorgangerLastSeen     			= "laatst_voorgaan";
$VoorgangerLastAandacht 			= "laatst_aandacht";

$TableWijkteam								= "wijkteams";
$WijkteamID										= "id";
$WijkteamWijk									= "wijk";
$WijkteamLid									= "lid";
$WijkteamRol									= "rol";
                    					
$TableMC											= "mc_data";
$MCID													= "scipio_id";
$MCgeslacht										= "geslacht";
$MCfname											= "fname";
$MCtname											= "tname";
$MClname											= "lname";
$MCmail												= "mail";
$MCwijk												= "wijk";
$MCmark												= "mark";
$MCstatus											= "status"; 
$MCrelatie										= "relatie";
$MCdoop												= "doop";
$MClastSeen										= "last_seen";
$MClastChecked								= "last_checked";
                    					
$TableCommMC									= "mc_comm";
$CommMCID											= "scipio_id";
$CommMCGroupID								= "group_id";
$ComMClastSeen								= "last_seen";
$ComMClastChecked							= "last_checked";
                    					
$TableLP											= "lp_data";
$LPID													= "scipio_id";
$LPgeslacht										= "geslacht";
$LPVoornaam										= "voornaam";
$LPTussenvoegsel							= "tussenvoegsel";
$LPAchternaam									= "achternaam";
$LPmail												= "mail";
$LPwijk												= "wijk";
$LPmark												= "mark";
$LPstatus											= "status"; 
$LPrelatie										= "relatie";
$LPdoop												= "doop";
$LPlastSeen										= "last_seen";
$LPlastChecked								= "last_checked";
                    					
$TableEBoekhouden 						= "eb_relatie"; # Als deze tabel verwijderd is, kan dit deel ook weg
#$EBoekhoudenID								= "id";
#$EBoekhoudenCode							= "code";
#$EBoekhoudenIBAN							= "iban";
#$EBoekhoudenNaam							= "naam";

$TableEBBoekstuk							= "eb_boekstuk";
$EBBoekstukJaar								= "jaar";
$EBBoekstukVolgNr							= "volgnummer";

$TableEBDeclaratie 						= "eb_declaraties";
$EBDeclaratieID								= "id";
$EBDeclaratieHash							= "hash";
$EBDeclaratieIndiener					= "indiener";
$EBDeclaratieCluster					= "cluster";
$EBDeclaratieStatus						= "status";
$EBDeclaratieDeclaratie				= "declaratie";
$EBDeclaratieTotaal						= "totaal";
$EBDeclaratieTijd							= "tijd";
                    					
$TableConfig									= "config";
$ConfigID											= "id";
$ConfigGroep									= "groep";
$ConfigName										= "name";
$ConfigKey										= "sleutel";
$ConfigValue									= "value";
$ConfigOpmerking							= "comment";
$ConfigAdded									= "added";
                    					
$TablePunten									= "gebed_punten";
$PuntenID											= "id";
$PuntenDatum									= "datum";
$PuntenPunt										= "gebedspunt";
                    					
$TableArchief									= "trinitas_archief";
$ArchiefID										= "id";
$ArchiefJaar									= "jaargang";
$ArchiefNr										= "exemplaar";
$ArchiefHash									= "hash";
$ArchiefDownload							= "download";
$ArchiefPubDate								= "pubDate";
$ArchiefName									= "filename";
$ArchiefSend									= "send";
                    					
$TablePlainText								= "trinitas_plaintext";
$PlainTextID									= "id";
$PlainTextText								= "plain";
                    					
$TableMail										= "mail_log";
$MailID												= "id";
$MailTime											= "tijd";
$MailMail											= "bericht";

$TableGebedKalMailOverzicht   = "GebedKal_mailoverzicht";
$GebedsKalId                  = "id";
$GebedKalCategorie            = "categorie";
$GebedKalContactPersoon       = "contactpersoon";
$GebedKalMailadres            = "mailadres";
$GebedKalOpmerkingen          = "opmerking";

$TableOpenKerkTemplate				= "openkerk_template";
$OKTemplateWeek								= "week";
$OKTemplateDag								= "dag";
$OKTemplateTijd								= "tijd";
$OKTemplatePos								= "pos";
$OKTemplatePersoon						= "persoon";

$TableOpenKerkRooster					= "openkerk_rooster";
$OKRoosterTijd								= "tijd";
$OKRoosterPos									= "pos";
$OKRoosterPersoon							= "persoon";

$TableOpenKerkOpmerking				= "openkerk_opmerking";
$OKOpmerkingTijd							= "tijd";
$OKOpmerkingOpmerking					= "opmerking";
                    					
$ArchiveDir										= 'trinitas';

$wijkArray			= array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
$statusArray		= array('actief', 'afgemeld', 'afgevoerd', 'onttrokken', 'overleden', 'vertrokken');
$burgelijkArray	= array('gehuwd', 'gereg. partner', 'gescheiden', 'ongehuwd', 'weduwe', 'weduwnaar');
$gezinArray			= array('dochter', 'echtgenoot', 'echtgenote', 'gezinshoofd', 'levenspartner', 'zelfstandig', 'zoon');
$kerkelijkArray	= array('belijdend lid', 'betrokkene', 'dooplid', 'gast', 'gedoopt gastlid', 'geen lid', 'ongedoopt kind', 'overige');
$maandArray			= array(1 => 'jan', 2 => 'feb', 3 => 'mrt', 4 => 'apr', 5 => 'mei', 6 => 'jun', 7 => 'jul', 8 => 'aug', 9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'dec');
$maandArrayLang = array(1 => 'januari', 2 => 'februari', 3 => 'maart', 4 => 'april', 5 => 'mei', 6 => 'juni', 7 => 'juli', 8 => 'augustus', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december');
$maandArrayEng	= array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
$letterArray		= array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
$teamRollen			= array(1 => 'Ouderling', 2 => 'Diaken', 3 => 'Wijkco&ouml;rdinator', 4 => 'Bezoekbroeder', 5 => 'Bezoekzuster', 6 => 'Ge&iuml;ntereseerde', 7 => 'Predikant');

$db = connect_db();

# Doorloop de config-tabel en groepeer op naam
# In een array hebben alle value's dezelfde naam
# 	maar wisselende key's en value's
# Bij een integer/string/boolean worden alleen naam en value gebruikt
$sql = "SELECT * FROM $TableConfig GROUP BY $ConfigName";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);

do {
	$name = urldecode($row[$ConfigName]);
	$sql_name = "SELECT * FROM $TableConfig WHERE $ConfigName like '$name'";
	$result_name = mysqli_query($db, $sql_name);
	$row_name = mysqli_fetch_array($result_name);

	do {
		# Als de key niet leeg is, is het dus een array
		if($row_name[$ConfigKey] != '') {
			# maak het nieuwe array-element aan
			$newValue = array(urldecode($row_name[$ConfigKey]) => urldecode($row_name[$ConfigValue]));
			
			# Als de array waar het nieuwe array-element bij hoort al bestaat
			# worden oud en nieuw gemerged en anders is het nieuwe element de array
			if(isset($$name)) {	
				$$name = $$name + $newValue;
			} else {								
				$$name = $newValue;
			}
		} else {
			$$name = urldecode($row_name[$ConfigValue]);
			
			if($row_name[$ConfigValue] == 'true')		$$name = true;
			if($row_name[$ConfigValue] == 'false')	$$name = false;		
		}		
	} while($row_name = mysqli_fetch_array($result_name));
} while($row = mysqli_fetch_array($result));

$ScriptURL	= $ScriptServer.$ScriptURL;
$Version		= $Version.'.'.$VersionCount;

?>
