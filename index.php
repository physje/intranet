<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$memberData = getMemberDetails($_SESSION['ID']);

# Data over gebruiker icm roosters opvragen
$allRoosters = getRoosters(0);
$myRoosters = getRoosters($_SESSION['ID']);
$myRoosterBeheer = getMyRoostersBeheer($_SESSION['ID']);

# Data over gebruiker icm groepen opvragen
$allGroups = getAllGroups();	
$myGroups = getMyGroups($_SESSION['ID']);
$myGroepBeheer = getMyGroupsBeheer($_SESSION['ID']);

$blocks = array();

# Roosters
if(count($allRoosters) > 0) {
	$txtRooster[] = "<b>Roosters</b>";
	
	foreach($allRoosters as $rooster) {
		$data = getRoosterDetails($rooster);
		if(in_array($rooster, $myRoosters)) {
			$class = "own";
		} else {
			$class = "general";
		}
		$txtRooster[] = "<a class='$class' href='showRooster.php?rooster=$rooster' target='_blank'>".$data['naam']."</a>";
	}	
	$txtRooster[] = "<a class='$class' href='showCombineRooster.php' target='_blank'>Toon combinatie-rooster</a>";
	$txtRooster[] = "<a class='$class' href='roosterKomendeWeek.php' target='_blank'>Toon rooster komende week</a>";
	$blocks[] = $txtRooster;	
}



# Rooster-beheer
if(count($myRoosterBeheer) > 0) {
	$txtRoosterBeheer[] = "<b>Roosters die ik kan wijzigen</b>";
	foreach($myRoosterBeheer as $rooster) {
		$data = getRoosterDetails($rooster);
		$txtRoosterBeheer[] = "<a href='makeRooster.php?rooster=$rooster' target='_blank'>".$data['naam']."</a>";
	}
	$blocks[] = $txtRoosterBeheer;
}



# Admin-rooster
if(in_array(1, $myGroups)) {
	$adminRoosters[] = "<b>Beheer roosters</b> (Admin)";
	
	foreach($allRoosters as $rooster) {
		$data = getRoosterDetails($rooster);
		$adminRoosters[] = "<a href='makeRooster.php?rooster=$rooster' target='_blank'>".$data['naam']."</a>";
	}
	$blocks[] = $adminRoosters;
}



# Groepen
$showGroupsClass = array();

foreach($allGroups as $groep) {
	$tonen = false;	
	$data = getGroupDetails($groep);
	if(in_array($groep, $myGroups)) {
		$class = "own";
		if($data['html-int'] != "") {
			$tonen = true;
		}
	} else {
		$class = "general";
		if($data['html-ext'] != "") {
			$tonen = true;
		}
	}
	
	if($tonen) {
		$showGroupsClass[$groep] = $class;		
	}	
}

if(count($showGroupsClass) > 0) {
	$txtGroepen[] = "<b>Pagina's van teams</b>";
	
	foreach($showGroupsClass as $groep => $class) {
		$data = getGroupDetails($groep);
		$txtGroepen[] = "<a class='$class' href='group.php?groep=$groep' target='_blank'>".$data['naam']."</a>";
	}	
	$blocks[] = $txtGroepen;
}



# Groepen-beheer
if(count($myGroepBeheer) > 0) {
	$txtGroepBeheer[] = "<b>Teams die ik beheer</b>";
	foreach($myGroepBeheer as $groep) {
		$data = getGroupDetails($groep);
		$txtGroepBeheer[] = "<a href='editGroup.php?groep=$groep' target='_blank'>".$data['naam']."</a>";
	}
	$blocks[] = $txtGroepBeheer;
}



# Admin-groepen
if(in_array(1, $myGroups)) {	
	$txtGroepAdmin[] = "<b>Beheer teams</b> (Admin)";
	foreach($allGroups as $groep) {
		$data = getGroupDetails($groep);
		$txtGroepAdmin[] = "<a href='editGroup.php?groep=$groep' target='_blank'>".$data['naam']."</a>";
	}
	$blocks[] = $txtGroepAdmin;
}



# Bezoek-registratie
# 8 = Ouderlingen
# 9 = Diakenen
# 34 = Predikanten
# 49 = Pastoraat super-user
# 50 = Pastoraal bezoekers
if(in_array(1, $myGroups) OR in_array(8, $myGroups) OR in_array(9, $myGroups) OR in_array(34, $myGroups) OR in_array(49, $myGroups) OR in_array(50, $myGroups)) {
	$BezoekDeel[] = "<b>Bezoekregistratie</b>";
	
	# Doorloop alle wijkteams
	# Als de ingelogde persoon maar in 1 wijkteam zit
	# Link dan direct door naar die wijk
	$hit = array();	
	foreach($wijkArray as $wijk) {
		$wijkteam = getWijkteamLeden($wijk);		
		if(array_key_exists($_SESSION['ID'], $wijkteam))	$hit[] = $wijk;
	}
	$BezoekLinks['pastoraat/index.php'. ((count($hit) == 1) ? '?wijk='. $hit[0] : '')] = 'Registratie bezoeken'. ((count($hit) == 1) ? ' wijk '. $hit[0] : '');
	
	if(in_array(49, $myGroups)) {
		$BezoekLinks['pastoraat/index.php'] = 'Registratie alle wijken';
	}
	
	$BezoekLinks['extern/Korte_handleiding_pastoraal_bezoek_systeem.pdf'] = 'Handleiding';
					
	foreach($BezoekLinks as $link => $naam) {
		$BezoekDeel[] = "<a href='$link' target='_blank'>$naam</a>";
	}
	
	$blocks[] = $BezoekDeel;
}



# Gegevens wijzigen-deel
# 1 = Admin
# 11 = Beamteam
# 20 = Preekvoorziening
# 22 = Diaconie
# 52 = Scipio-beheer
if(in_array(1, $myGroups) OR in_array(11, $myGroups) OR in_array(20, $myGroups) OR in_array(22, $myGroups) OR in_array(52, $myGroups)) {
	$wijzigDeel[] = "<b>Kerkdiensten</b>";
}

if(in_array(1, $myGroups) OR in_array(20, $myGroups)) {
	$wijzigLinks['voorganger/editVoorganger.php'] = 'Gegevens van voorgangers wijzigen';	
	$wijzigLinks['voorganger/voorgangerRooster.php'] = 'Preekrooster invoeren';	
}

if(in_array(1, $myGroups) OR in_array(11, $myGroups) OR in_array(52, $myGroups)) {
	$wijzigLinks['editLiturgie.php'] = 'Liturgie invoeren of aanpassen';
}

if(in_array(1, $myGroups) OR in_array(22, $myGroups) OR in_array(52, $myGroups)) {
	$wijzigLinks['editCollectes.php'] = 'Collecte-doelen invoeren';	
}

if(in_array(1, $myGroups) OR in_array(28, $myGroups) OR in_array(52, $myGroups)) {
	$wijzigLinks['editDiensten.php'] = 'Kerkdiensten wijzigen';	
}

if(isset($wijzigLinks) AND is_array($wijzigLinks)) {	
	foreach($wijzigLinks as $link => $naam) {
		$wijzigDeel[] = "<a href='$link' target='_blank'>$naam</a>";
	}	
	$blocks[] = $wijzigDeel;
}



# LaPosta
$laPostaDeel = $laPostaLinks = array();
$laPostaDeel[] = "<b>LaPosta</b>";

$laPostaLinks['laposta/archief.php'] = 'Mail archief';
if(in_array(1, $myGroups)) {
	$laPostaLinks['laposta/sync.php'] = 'Synchroniseren LaPosta';
}

foreach($laPostaLinks as $link => $naam) {
	$laPostaDeel[] = "<a href='$link' target='_blank'>$naam</a>";
}
	
$blocks[] = $laPostaDeel;



# Open Kerk
if(in_array(1, $myGroups) OR in_array(43, $myGroups) OR in_array(44, $myGroups)) {
	$OpenKerkDeel[] = "<b>Open kerk</b>";
	
	if(in_array(1, $myGroups) OR in_array(44, $myGroups)) {
		$OpenKerkLinks['openkerk/template.php'] = 'Template bekijken/aanpassen';
		$OpenKerkLinks['openkerk/mailen.php'] = 'Rooster mailen';
	}
	
	if(in_array(1, $myGroups) OR in_array(43, $myGroups)) {
		$OpenKerkLinks['openkerk/editRooster.php'] = 'Rooster wijzigen';
	}
		
	$OpenKerkLinks['openkerk/showRooster.php'] = 'Rooster tonen';
	
	foreach($OpenKerkLinks as $link => $naam) {
		$OpenKerkDeel[] = "<a href='$link' target='_blank'>$naam</a>";
	}
	$blocks[] = $OpenKerkDeel;
}




# Admin-deel
if(in_array(1, $myGroups)) {
	$adminDeel[] = "<b>Admin</b>";
	
	$adminLinks['admin/generateUsernames.php'] = 'Gebruikersnamen aanmaken';
	$adminLinks['admin/generateDiensten.php'] = 'Kerkdiensten aanmaken';
	$adminLinks['editDiensten.php'] = 'Kerkdiensten wijzigen';	
	$adminLinks['admin/editGroepen.php'] = 'Groepen wijzigen';	
	$adminLinks['admin/editRoosters.php'] = 'Roosters wijzigen';	
	$adminLinks['admin/editWijkteams.php'] = 'Wijkteams wijzigen';	
	$adminLinks['admin/crossCheck.php'] = 'Check databases';
	$adminLinks['admin/log.php'] = 'Bekijk logfiles';
	$adminLinks['admin/mailLog.php'] = 'Bekijk mail-files';
	$adminLinks['admin/sendMail.php'] = 'Verstuur mail';
	$adminLinks['admin/reviewRechten.php'] = 'Bekijk groepen en rechten';
	$adminLinks['admin/configuration.php'] = 'Configuratie-variabelen';
	$adminLinks['onderhoud/cleanUpDb.php'] = 'Verwijder oude diensten';
	$adminLinks['../dumper/'] = 'Dumper';
	
	foreach($adminLinks as $link => $naam) {
		$adminDeel[] = "<a href='$link' target='_blank'>$naam</a>";
	}	
	$blocks[] = $adminDeel;
}



# e-boekhouden.nl
$EBDeel = $EBLinks = array();
$EBDeel[] = "<b>Declaraties</b>";

$EBLinks['declaratie/'] = 'Dien declaratie in';

if(in_array(1, $myGroups) OR in_array(38, $myGroups)) {
	$EBLinks['declaratie/overzichtDeclaraties.php'] = 'Status declaraties';	
	$EBLinks['declaratie/opschonenOudeBijlages.php'] = 'Verwijder oude bijlages';
}

if(in_array(1, $myGroups)) {
	$EBLinks['declaratie/relatieOverview.php'] = 'Toon alle relaties';
	$EBLinks['declaratie/mutatieOverview.php'] = 'Toon alle mutaties';	
	$EBLinks['declaratie/zoekWeesBijlages.php'] = 'Koppel wees-bijlages';
	//$EBLinks['declaratie/syncRelaties.php'] = 'Synchroniseer relaties naar lokale database';
	$EBLinks['declaratie/editRelatie.php'] = 'Wijzig relaties';
	#$EBLinks['https://secure.e-boekhouden.nl/handleiding/Documentatie_soap.pdf'] = 'SOAP documenatie PDF';
}

foreach($EBLinks as $link => $naam) {
	$EBDeel[] = "<a href='$link' target='_blank'>$naam</a>";
}
$blocks[] = $EBDeel;



# Koppelingen-deel
if(in_array(1, $myGroups) OR in_array(52, $myGroups)) {
	$koppelDeel[] = "<b>Koppelingen</b>";	
	$koppelLinks['extern/makeiCalScipio.php'] = 'Data klaar zetten voor Scipio';	

	if(in_array(1, $myGroups)) {	
		$koppelLinks['extern/makeiCal.php'] = 'Persoonlijke iCals aanmaken';	
		#$koppelLinks['onderhoud/importOuderlingen.php'] = 'Importeer ambtsdragers';
		$koppelLinks['scipio/ScipioImport.php'] = 'Scipio-data inladen';
		$koppelLinks['scipio/exportCollectes.php'] = 'Collectes exporteren voor in Scipio';
	}
	
	foreach($koppelLinks as $link => $naam) {
		$koppelDeel[] = "<a href='$link' target='_blank'>$naam</a>";
	}	
	$blocks[] = $koppelDeel;
}


# Gebedskalender
$gebedsDeel[] = "<b>Gebedskalender</b>";
$gebedsLinks['gebedskalender/overzicht.php#'. date('d')] = 'Gebedskalender';

if(in_array(1, $myGroups) OR in_array(36, $myGroups)) {	
	$gebedsLinks['gebedskalender/import.php'] = 'Import';
	$gebedsLinks['gebedskalender/edit.php'] = 'Wijzig';
	$gebedsLinks['gebedskalender/mailadressenOverzicht.php'] = 'Mailadressen overzicht';
}
	
foreach($gebedsLinks as $link => $naam) {
	$gebedsDeel[] = "<a href='$link' target='_blank'>$naam</a>";
}

$blocks[] = $gebedsDeel;



# Hyperlinks
$links[] = "<b>Links</b>";
$links[] = "<a href='http://www.koningskerkdeventer.nl/' target='_blank'>koningskerkdeventer.nl</a>";
$links[] = "<a href='agenda/agenda.php' target='_blank'>Agenda voor Scipio</a>";
$links[] = "<a href='ical/".$memberData['username'].'-'. $memberData['hash_short'] .".ics' target='_blank'>Persoonlijke digitale agenda</a>";
$blocks[] = $links;



# Site
$site[] = "<b>Ingelogd als ". makeName($_SESSION['ID'], 5)."</b>";
$site[] = "<a href='account.php' target='_blank'>Account</a>";
$site[] = "<a href='profiel.php' target='_blank'>Profiel</a>";
$site[] = "<a href='ledenlijst.php' target='_blank'>Ledenlijst</a>";
if(in_array(1, $myGroups)) {
	$site[] = "<a href='search.php' target='_blank'>Zoeken</a>";
}
$site[] = "<a href='auth/objects/logout.php' target='_blank'>Uitloggen</a>";
$blocks[] = $site;


echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;

foreach($blocks as $block) {
	echo "<div class='content_block'>". implode("<br>".NL, $block) ."</div>".NL;
}

echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>
