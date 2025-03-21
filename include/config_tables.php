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
$UsersLastSeen								= "last_scipio";
$UserWijk											= "wijk";
$UserEBRelatie								= "eb_code";
$UserUsername									= "username";
$UserPassword									= "password";
$UserNewPassword							= "password_new";
$User2FA											= "2FA_code";
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
$RoostersVoorganger						= "voorganger";
$RoostersTextOnly							= "text_only";
$RoostersOuderCC							= "ouder";
$RoostersPartnerTo						= "partner";
$RoostersAlert								= "alert";
$RoostersLastChange						= "last_change";
                    					
$TableGrpUsr									= "group_member";
$GrpUsrGroup									= "commissie";
$GrpUsrUser										= "lid";
                    					
$TableDiensten								= "kerkdiensten";
$DienstID											= "id";
$DienstActive									= "actief";
$DienstStart									= "start";
$DienstEind										= "eind";
$DienstVoorganger							= "voorganger";
$DienstCollecte_1							= "collecte_1";
$DienstCollecte_2							= "collecte_2";
$DienstOpmerking							= "opmerking";
$DienstRuiling								= "ruiling";
$DienstLiturgie     					= "liturgie";
$DienstSpeciaal     					= "speciaal";
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
$LogDisguised									= "vermomd";
$LogSubject										= "slachtoffer";
$LogMessage										= "message";
                    					
$TableRoosOpm									= "rooster_opmerkingen";
$RoosOpmID										= "id";
$RoosOpmRoos									= "rooster";
$RoosOpmDienst								= "dienst";
$RoosOpmOpmerking							= "opmerking";

$TableVoorganger 							= "predikanten";
$VoorgangerID 								= "id";
$VoorgangerActive							= "actief";
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
$VoorgangerHash								= "hash";
$VoorgangerAandacht						= "aandachtspunten";
$VoorgangerDeclaratie   			= "declaratie";
$VoorgangerReiskosten   			= "reiskosten";
$VoorgangerHonorarium					= "honorarium";
$VoorgangerHonorariumOld			= "honorarium_2019";
$VoorgangerHonorariumNew			= "honorarium_2020";
$VoorgangerHonorarium2023			= "honorarium_2023";
$VoorgangerHonorarium2024			= "honorarium_2024";
$VoorgangerHonorariumSpecial	= "honorarium_special";
$VoorgangerKM									= "km_vergoeding";
$VoorgangerVertrekpunt				= "vertrekpunt";
$VoorgangerEBRelatie					= "boekhoudenID";
$VoorgangerLastSeen     			= "laatst_voorgaan";
$VoorgangerLastAandacht 			= "laatst_aandacht";
$VoorgangerLastDataCheck 			= "laatst_gegevens";

$TableWijkteam								= "wijkteams";
$WijkteamID										= "id";
$WijkteamWijk									= "wijk";
$WijkteamLid									= "lid";
$WijkteamRol									= "rol";
                    					
$TableMC											= "mc_data";
#$MCID													= "scipio_id";
#$MCgeslacht										= "geslacht";
#$MCfname											= "fname";
#$MCtname											= "tname";
#$MClname											= "lname";
#$MCmail												= "mail";
#$MCwijk												= "wijk";
#$MCmark												= "mark";
#$MCstatus											= "status";
#$MCrelatie										= "relatie";
#$MCdoop												= "doop";
#$MCtempTag										= "tempTag";
#$MCleeftijd										= "leeftijd";
#$MClastSeen										= "last_seen";
#$MClastChecked								= "last_checked";
                    					
$TableCommMC									= "mc_comm";
#$CommMCID											= "scipio_id";
#$CommMCGroupID								= "group_id";
#$ComMClastSeen								= "last_seen";
#$ComMClastChecked							= "last_checked";
                    					
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
$LP70plus											= "70_plus";
$LPlastSeen										= "last_seen";
$LPlastChecked								= "last_checked";

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
$EBDeclaratieLastAction				= "last_action";
                    					
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
$MailSuccess									= "succes";
$MailTime											= "tijd";
$MailMail											= "bericht";

$TableGebedKalMailOverzicht   = "GebedKal_mailoverzicht";
$GebedsKalId                  = "id";
$GebedKalCategorie            = "categorie";
$GebedKalContactPersoon       = "contactpersoon";
$GebedKalMailadres            = "mailadres";
$GebedKalOpmerkingen          = "opmerking";

$TableOpenKerkTemplateNames		= "openkerk_template_namen";
$OpenKerkTemplateNamesID			= "id";
$OpenKerkTemplateNamesName		= "naam";

$TableOpenKerkTemplate				= "openkerk_template";
$OKTemplateTemplate						= "template";
$OKTemplateEnroll							= "enroll";
$OKTemplateWeek								= "week";
$OKTemplateDag								= "dag";
$OKTemplateTijd								= "tijd";
$OKTemplatePos								= "pos";
$OKTemplatePersoon						= "persoon";

$TableOpenKerkRooster					= "openkerk_rooster";
$OKRoosterID									= "id";
$OKRoosterStart								= "tijd";
$OKRoosterEind								= "eind";
$OKRoosterPos									= "pos";
$OKRoosterPersoon							= "persoon";

$TableOpenKerkOpmerking				= "openkerk_opmerking";
$OKOpmerkingTijd							= "tijd";
$OKOpmerkingOpmerking					= "opmerking";

$TablePastoraat								= "pastoraat";
$PastoraatID									= "id";
$PastoraatIndiener						= "indiener";
$PastoraatTijdstip						= "tijdstip";
$PastoraatLid									= "lid";
$PastoraatType								= "type";
$PastoraatLocatie							= "locatie";
$PastoraatPrive								= "prive";
$PastoraatZichtOud 						= "zicht_oud";
$PastoraatZichtPred 					= "zicht_pred";
$PastoraatZichtPas						= "zicht_pas";
$PastoraatNote								= "aantekening";

$TablePastorVerdeling					= "pastoraat_verdeling";
$PastorVerdelingLid						= "lid";
$PastorVerdelingPastor				= "pastor";
$PastorVerdelingBezoeker			= "bezoeker";

#$TablePastorConvert						= "pastoraat_convert";
#$PastorConvertFamID						= "fam_id";
#$PastorConvertFamName					= "famname";
#$PastorConvertScipioID				= "scipio";
#$PastorConvertWijk						= "wijk";

#$TablePastorConvertPas				= "pastoraat_convert_pastor";
#$PastorConvertPastor					= "pastor";
#$PastorConvertPastorName			= "naam";
#$PastorConvertPastorScipio		= "scipio";

$TableLogins								= "logins";
$LoginID										= "id";
$LoginLid										= "lid";
$LoginIP										= "ip";
$LoginAgent									= "agent";
$LoginTijd									= "tijd";
                                                  					
$ArchiveDir										= 'trinitas';

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
