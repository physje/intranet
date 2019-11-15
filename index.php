<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

$memberData = getMemberDetails($_SESSION['ID']);

# Roosters
$allRoosters = getRoosters(0);
$myRoosters = getRoosters($_SESSION['ID']);

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
		
	$blockArray[] = implode("<br>".NL, $txtRooster);
}



# Groepen
$allGroups = getAllGroups();	
$myGroups = getMyGroups($_SESSION['ID']);
if(count($allGroups) > 0) {
	$txtGroepen[] = "<b>Pagina's van teams</b>";
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
			$txtGroepen[] = "<a class='$class' href='group.php?groep=$groep' target='_blank'>".$data['naam']."</a>";
		}
	}	
	$blockArray[] = implode("<br>".NL, $txtGroepen);
}



# Groepen-beheer
$myGroepBeheer = getMyGroupsBeheer($_SESSION['ID']);
if(count($myGroepBeheer) > 0) {
	$txtGroepBeheer[] = "<b>Teams die ik beheer</b>";
	foreach($myGroepBeheer as $groep) {
		$data = getGroupDetails($groep);
		$txtGroepBeheer[] = "<a href='editGroup.php?groep=$groep' target='_blank'>".$data['naam']."</a>";
	}
	$blockArray[] = implode("<br>".NL, $txtGroepBeheer);
}


# Rooster-beheer
$myRoosterBeheer = getMyRoostersBeheer($_SESSION['ID']);
if(count($myRoosterBeheer) > 0) {
	$txtRoosterBeheer[] = "<b>Roosters die ik kan wijzigen</b>";
	foreach($myRoosterBeheer as $rooster) {
		$data = getRoosterDetails($rooster);
		$txtRoosterBeheer[] = "<a href='makeRooster.php?rooster=$rooster' target='_blank'>".$data['naam']."</a>";
	}
	$blockArray[] = implode("<br>".NL, $txtRoosterBeheer);
}


	
# Admin-groepen
if(in_array(1, getMyGroups($_SESSION['ID']))) {	
	$txtGroepAdmin[] = "<b>Beheer teams</b> (Admin)";
	foreach($allGroups as $groep) {
		$data = getGroupDetails($groep);
		$txtGroepAdmin[] = "<a href='editGroup.php?groep=$groep' target='_blank'>".$data['naam']."</a>";
	}
	$alleGroepen[] = "";
	$blockArray[] = implode("<br>".NL, $txtGroepAdmin);
}



# Admin-rooster
if(in_array(1, getMyGroups($_SESSION['ID']))) {
	$adminRoosters[] = "<b>Beheer roosters</b> (Admin)";
	
	foreach($allRoosters as $rooster) {
		$data = getRoosterDetails($rooster);
		$adminRoosters[] = "<a href='makeRooster.php?rooster=$rooster' target='_blank'>".$data['naam']."</a>";
	}
	
	$blockArray[] = implode("<br>".NL, $adminRoosters);
}


# Gegevens wijzigen-deel
# 1 = Admin
# 20 = Preekvoorziening
# 22 = Diaconie
# 28 = Cluster Eredienst
if(in_array(1, getMyGroups($_SESSION['ID'])) OR in_array(20, getMyGroups($_SESSION['ID'])) OR in_array(22, getMyGroups($_SESSION['ID'])) OR in_array(28, getMyGroups($_SESSION['ID']))) {
	$wijzigDeel[] = "<b>Diensten wijzigen</b>";
}

if(in_array(1, getMyGroups($_SESSION['ID'])) OR in_array(20, getMyGroups($_SESSION['ID']))) {
	$wijzigLinks['voorganger/editVoorganger.php'] = 'Gegevens van voorgangers wijzigen';	
	$wijzigLinks['voorganger/voorgangerRooster.php'] = 'Preekrooster invoeren';	
}

if(in_array(1, getMyGroups($_SESSION['ID'])) OR in_array(28, getMyGroups($_SESSION['ID']))) {
	$wijzigLinks['editLiturgie.php'] = 'Liturgie invoeren of aanpassen';
}

if(in_array(1, getMyGroups($_SESSION['ID'])) OR in_array(22, getMyGroups($_SESSION['ID']))) {
	$wijzigLinks['editCollectes.php'] = 'Collecte-doelen invoeren';	
}

if(in_array(1, getMyGroups($_SESSION['ID'])) OR in_array(28, getMyGroups($_SESSION['ID']))) {
	$wijzigLinks['editDiensten.php'] = 'Kerkdiensten wijzigen';	
}

if(is_array($wijzigLinks)) {	
	foreach($wijzigLinks as $link => $naam) {
		$wijzigDeel[] = "<a href='$link' target='_blank'>$naam</a>";
	}
	
	$blockArray[] = implode("<br>".NL, $wijzigDeel);
}

# Admin-deel
if(in_array(1, getMyGroups($_SESSION['ID']))) {
	$adminDeel[] = "<b>Admin</b>";
	
	$adminLinks['admin/generateUsernames.php'] = 'Gebruikersnamen aanmaken';
	$adminLinks['admin/generateDiensten.php'] = 'Kerkdiensten aanmaken';
	$adminLinks['admin/editDiensten.php'] = 'Kerkdiensten wijzigen';	
	$adminLinks['admin/editGroepen.php'] = 'Groepen wijzigen';	
	$adminLinks['admin/editRoosters.php'] = 'Roosters wijzigen';	
	$adminLinks['admin/editWijkteams.php'] = 'Wijkteams wijzigen';	
	$adminLinks['admin/crossCheck.php'] = 'Check databases';
	$adminLinks['admin/log.php'] = 'Bekijk logfiles';
	$adminLinks['admin/sendMail.php'] = 'Verstuur mail';
	$adminLinks['admin/configuration.php'] = 'Configuratie-variabelen';
	$adminLinks['onderhoud/cleanUpDb.php'] = 'Verwijder oude diensten';
	$adminLinks['../dumper/'] = 'Dumper';
	
	foreach($adminLinks as $link => $naam) {
		$adminDeel[] = "<a href='$link' target='_blank'>$naam</a>";
	}
	
	$blockArray[] = implode("<br>".NL, $adminDeel);
}

if(in_array(1, getMyGroups($_SESSION['ID']))) {
	$adminDeel = $adminLinks = array();
	$adminDeel[] = "<b>Mailchimp</b>";

	$adminLinks['mailchimp/sync.php'] = 'Synchroniseren Mailchimp';
	$adminLinks['mailchimp/check.php'] = 'Controleer lokale data in Mailchimp';
	$adminLinks['mailchimp/check_MC.php'] = 'Controleer Mailchimp-data met lokaal';
	
	foreach($adminLinks as $link => $naam) {
		$adminDeel[] = "<a href='$link' target='_blank'>$naam</a>";
	}
	
	$blockArray[] = implode("<br>".NL, $adminDeel);
}

# Koppelingen-deel
if(in_array(1, getMyGroups($_SESSION['ID']))) {
	$koppelDeel[] = "<b>Koppelingen</b>";
	
	$koppelLinks['extern/makeiCal.php'] = 'Persoonlijke iCals aanmaken';
	$koppelLinks['extern/makeiCalScipio.php'] = 'iCal voor Scipio aanmaken';
	$koppelLinks['onderhoud/importOuderlingen.php'] = 'Importeer ambtsdragers';
	//$koppelLinks['onderhoud/importSchriftlezer.php'] = 'Importeer schriftlezers';
	$koppelLinks['scipio/ScipioImport.php'] = 'Scipio-data inladen';
	
	foreach($koppelLinks as $link => $naam) {
		$koppelDeel[] = "<a href='$link' target='_blank'>$naam</a>";
	}
	
	$blockArray[] = implode("<br>".NL, $koppelDeel);
}

# Hyperlinks
$links[] = "<b>Links</b>";
$links[] = "<a href='../../trinitas/' target='_blank'>Trinitas</a>";
$links[] = "<a href='../gebedskalender/' target='_blank'>Gebedskalender</a>";
$links[] = "<a href='http://www.koningskerkdeventer.nl/' target='_blank'>koningskerkdeventer.nl</a>";
$links[] = "<a href='agenda/agenda.php' target='_blank'>Agenda voor Scipio</a>";
$links[] = "<a href='ical/".$memberData['username'].'-'. $memberData['hash_short'] .".ics' target='_blank'>Persoonlijke digitale agenda</a>";
$blockArray[] = implode("<br>".NL, $links);



# Site
$site[] = "<b>Ingelogd als ". makeName($_SESSION['ID'], 5)."</b>";
$site[] = "<a href='account.php' target='_blank'>Account</a>";
$site[] = "<a href='profiel.php' target='_blank'>Profiel</a>";
$site[] = "<a href='ledenlijst.php' target='_blank'>Ledenlijst</a>";
if(in_array(1, getMyGroups($_SESSION['ID']))) {
	$site[] = "<a href='search.php' target='_blank'>Zoeken</a>";
}
$site[] = "<a href='auth/objects/logout.php' target='_blank'>Uitloggen</a>";
$blockArray[] = implode("<br>".NL, $site);


# Jarigen
$jarigen = getJarigen(date("d"), date("m"));
if(count($jarigen) > 0) {
	$jarig[] = "<b>Jarigen vandaag</b>";
	foreach($jarigen as $jarige) {
		$data = getMemberDetails($jarige);
		$jarig[] = "<a href='profiel.php?id=$jarige' target='_blank'>". makeName($jarige, 5)."</a> (". (date("Y")-$data['jaar']).")";
	}
	$blockArray[] = implode("<br>".NL, $jarig);
}


# Jarigen
$jarigen = getJarigen(date("d", (time()+(24*60*60))), date("m", (time()+(24*60*60))));
if(count($jarigen) > 0) {
	$morgen[] = "<b>Jarigen morgen</b>";
	foreach($jarigen as $jarige) {
		$data = getMemberDetails($jarige);
		$morgen[] = "<a href='profiel.php?id=$jarige' target='_blank'>". makeName($jarige, 5)."</a> (". (date("Y")-$data['jaar']).")";
	}
	$blockArray[] = implode("<br>".NL, $morgen);
}

# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '	<td valign="top">'.NL;

$scheiding = floor(count($blockArray)/2);

foreach($blockArray as $key => $block) {
	if($scheiding == $key) {
		echo '	</td>'.NL;
		echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
		echo '	<td valign="top">'.NL;
	}
	echo showBlock($block, 100);
	echo '<p>'.NL;
}
echo '	</td>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;




















//echo $_SESSION['ID'];

/*
# LINKS
$links['account.php']					= 'Account';
$links['archief.php']						= 'Archief';
$links['search.php']						= 'Zoeken op woorden';
$links['auth/objects/logout.php']										= 'Uitloggen';	

foreach($links as $url => $titel) {
	$blockLinks .= "<a href='$url' target='_blank'>$titel</a><br>\n";
}

$blockArray[] = $blockLinks;

# BEHEERDER & ADMIN
if($_SESSION['level'] >= 2) {
	$beheer['exemplaar.php']	= 'Voeg exemplaar Trinitas toe';
	$beheer['sendMail.php']	= 'Verstuur klaarstaande mail';
	$beheer['stats.php']	= 'Bekijk download-statistieken';
	$beheer['stats_user.php']	= 'Bekijk statistieken per gebruiker';
			
	$admin['new_account.php?adminAdd']	= 'Voeg account toe';
		
	if($_SESSION['level'] >= 3) {
		$beheer['sendMail.php?testRun=true']	= 'Test klaarstaande mail';
		
		$admin['account.php?all']	= 'Toon alle accounts';
		//$admin['renewHash.php']	= 'Vernieuw gebruikers-hash';
		$admin['generateURL.php']	= 'Genereer URL';
		$admin['log.php']	= 'Bekijk logfiles';
	}
		
	foreach($beheer as $url => $titel) {
		$blockBeheer .= "<a href='$url' target='_blank'>$titel</a><br>\n";
	}

	foreach($admin as $url => $titel) {
		$blockAdmin .= "<a href='$url' target='_blank'>$titel</a><br>\n";
	}
		
	$blockArray[] = $blockBeheer;
	$blockArray[] = $blockAdmin;
}

//echo $HTMLHeader;
//echo "<tr>\n";
//
//# Als er maar 1 blok is, is het mooier die gecentreerd te hebben
//if($_SESSION['level'] == 1) {
//	echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
//	echo "<td width='50%' valign='center' align='center'>\n";
//# Als er meer blokken zijn, dan gewoon in 2 kolommen bovenaan
//} else {
//	echo "<td width='50%' valign='top' align='center'>\n";
//}
//echo showBlock($blockLinks);
//if($_SESSION['level'] == 1) {
//	echo "</td>\n";
//	echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
//} else {
//	echo "</td><td width='50%' valign='top' align='center'>\n";
//	if(isset($blockBeheer)) {
//		echo showBlock($blockBeheer);
//	}	
//	if(isset($blockAdmin)) {
//		echo "<p>\n";
//		echo showBlock($blockAdmin);
//	}	
//	echo "</td>\n";	
//}
//echo "</tr>\n";
//echo $HTMLFooter;

verdeelBlokken($blockArray);
*/
?>
