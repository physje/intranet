<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../Classes/Member.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Logging.php');
include_once('../include/HTML_TopBottom.php');

# 1 = Admin
# 28 = Cluster Eredienst
# 52 = Scipio-beheer

$requiredUserGroups = array(1, 28, 52);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$new = array();

$ik = new Member($_SESSION['useID']);
$myGroups = $ik->getTeams();

# Als er op een knop gedrukt is, het rooster wegschrijven
if(isset($_POST['save']) OR isset($_POST['maanden'])) {	
	foreach($_POST['bijz'] as $dienstID => $bijzonderheid) {
		$kerkdienst = new Kerkdienst($dienstID);
		$kerkdienst->opmerking = $bijzonderheid;
		
		# Admin mag dag, maand en jaar wijzigen
		if(in_array(1, $myGroups) OR in_array(28, $myGroups)) {
			$dag	= $_POST['sDag'][$dienstID];
			$maand	= $_POST['sMaand'][$dienstID];
			$jaar	= $_POST['sJaar'][$dienstID];
		} else {
			$dag	= date("d", $kerkdienst->start);
			$maand	= date("m", $kerkdienst->start);
			$jaar	= date("Y", $kerkdienst->start);
		}

		$kerkdienst->start = mktime($_POST['sUur'][$dienstID], $_POST['sMin'][$dienstID], 0, $maand, $dag, $jaar);
		$kerkdienst->eind = mktime($_POST['eUur'][$dienstID], $_POST['eMin'][$dienstID], 0, $maand, $dag, $jaar);		
		if(!$kerkdienst->save())	toLog('Dienst '. $kerkdienst->dienst .' kon niet worden bijgewerkt', 'error');
	}
	toLog('Diensten bijgewerkt');
}

if(isset($_REQUEST['new'])) {
	$dienst = new Kerkdienst();
	$dienst->start = mktime(9,0,0,date("n"),date("j")+1, date("Y"));
	$dienst->eind = mktime(9,30,0,date("n"),date("j")+1, date("Y"));
	$dienst->opmerking = 'Handmatig toegevoegd op '.date("d-m-Y H:i");

	if($dienst->save()) {
		toLog('Dienst voor '. date("d-m-Y", $dienst->start) .' toegevoegd');
	} else {
		toLog('Dienst voor '. date("d-m-Y", $dienst->start) .' kon niet worden toegevoegd', 'error');
	}	
}

if(isset($_REQUEST['delete']) AND (in_array(1, $myGroups) OR in_array(28, $myGroups))) {
	$dienst = new Kerkdienst($_REQUEST['id']);
	$dienst->actief = false;
	if($dienst->save()) {
		toLog(formatDagdeel($dienst->start).' van '. date("d-m-Y", $dienst->start) .' ['. $_REQUEST['id'] .'] op inactief gezet');
	} else {
		toLog(formatDagdeel($dienst->start).' van '. date("d-m-Y", $dienst->start) .' ['. $_REQUEST['id'] .'] kon niet op inactief gezet worden', 'error');	
	}	
}

if(true) {
	$blokGrootte = (92*24*60*60);
	
	if(isset($_POST['start'])) {
		$start = $_POST['start'];
	} else {
		$start = time();
	}
	
	if(isset($_POST['next'])) {
		$start = ($start + $blokGrootte);
	}
	
	if(isset($_POST['prev'])) {
		$start = ($start - $blokGrootte);
	}	
	
	$einde = $start + $blokGrootte;
	
	# Haal alle kerkdiensten binnen een tijdsvak op
	$diensten = Kerkdienst::getDiensten($start, $einde);
	
	$text[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$text[] = "<input type='hidden' name='start' value='$start'>";
	$text[] = "<table border=0>";
	$text[] = "<tr>";
	$text[] = "	<td>Datum</td>";
	$text[] = "	<td>Start</td>";
	$text[] = "	<td>Eind</td>";
	$text[] = "	<td>Bijzonderheid</td>";
	$text[] = "	<td>Trouw/Begrafenis</td>";
	if(in_array(1, $myGroups) OR in_array(28, $myGroups)) {
		$text[] = "	<td>&nbsp;</td>";
	}
	$text[] = "</tr>";
	
	foreach($diensten as $DienstID) {
		$dienst = new Kerkdienst($DienstID);
		
		$sMin		= date("i", $dienst->start);
		$sUur		= date("H", $dienst->start);
		
		$eMin		= date("i", $dienst->eind);
		$eUur		= date("H", $dienst->eind);
		
		$text[] = "<tr>";
		
		if(in_array(1, $myGroups) OR in_array(28, $myGroups)) {
			$sDag		= date("d", $dienst->start);
			$sMaand		= date("m", $dienst->start);
			$sJaar		= date("Y", $dienst->start);
			
			$text[] = "	<td><select name='sDag[$DienstID]'>";
			for($d=1; $d<=31 ; $d++) {
				$text[] = "	<option value='$d'". ($d == $sDag ? ' selected' : '') .">$d</option>";
			}
			$text[] = "	</select>";
			$text[] = "	<select name='sMaand[$DienstID]'>";
			for($m=1; $m<=12 ; $m++) {
				$text[] = "	<option value='$m'". ($m == $sMaand ? ' selected' : '') .">". $maandArray[$m] . "</option>";
			}
			$text[] = "	</select>";
			$text[] = "	<select name='sJaar[$DienstID]'>";
			for($j=date('Y'); $j<=(date('Y')+2) ; $j++) {
				$text[] = "	<option value='$j'". ($j == $sJaar ? ' selected' : '') .">$j</option>";
			}		
			$text[] = "	</select></td>";				
		} else {
			$text[] = "	<td align='right'>". time2str("E d LLL", $data->start) ."</td>";
		}
		
		$text[] = "	<td><select name='sUur[$DienstID]'>";
		for($u=0; $u<24 ; $u++) {
			$text[] = "	<option value='$u'". ($u == $sUur ? ' selected' : '') .">$u</option>";
		}
		$text[] = "	</select>";
		$text[] = "	<select name='sMin[$DienstID]'>";
		for($m=0; $m<60 ; $m=$m+15) {
			$text[] = "	<option value='$m'". ($m == $sMin ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>";
		}
		$text[] = "	</select></td>";
		$text[] = "	<td><select name='eUur[$DienstID]'>";
		for($u=0; $u<24 ; $u++) {
			$text[] = "	<option value='$u'". ($u == $eUur ? ' selected' : '') .">$u</option>";
		}
		$text[] = "	</select>";
		$text[] = "	<select name='eMin[$DienstID]'>";
		for($m=0; $m<60 ; $m=$m+15) {
			$text[] = "	<option value='$m'". ($m == $eMin ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>";
		}
		$text[] = "	</select></td>";	
		$text[] = "	<td><input type='text' name='bijz[$DienstID]' value=\"". $dienst->opmerking ."\" size='30'></td>";	
		$text[] = "	<td><input type='checkbox' name='spec[$DienstID]' value='1'". ($dienst->specialeDienst ? ' checked' : '') ."></td>";	
		if(in_array(1, $myGroups) OR in_array(28, $myGroups)) {
			$text[] = "	<td align='right'><a href='?delete=ja&id=$DienstID'><img src='..\images\delete.png'></a></td>";
		}
		$text[] = "</tr>";
	}
	
	$text[] = "</table>";
	$text[] = "<p class='after_table'><input type='submit' name='prev' value='Vorige 3 maanden'>&nbsp;<input type='submit' name='save' value='Diensten opslaan'>&nbsp;<input type='submit' name='next' value='Volgende 3 maanden'></p>";
	$text[] = "</form>";
	
	if(in_array(1, $myGroups) OR in_array(28, $myGroups)) {
		$new[] = "<a href='?new'>Extra dienst toevoegen</a>";
	}
}

$header[] = '<style>';
$header[] = '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
$header[] = '	td:nth-of-type(1):before { content: "Datum"; }';
$header[] = '	td:nth-of-type(2):before { content: "Start"; }';
$header[] = '	td:nth-of-type(3):before { content: "Eind"; }';
$header[] = '	td:nth-of-type(4):before { content: "Bijzonderheid"; }';
$header[] = '	td:nth-of-type(5):before { content: "Trouw- of begrafenis"; }';
$header[] = "}";
$header[] = "</style>";

echo showCSSHeader(array('default', 'table_rot'), $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>Kerkdiensten</h1>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
if(count($new) > 0)	echo "<div class='content_block'>".NL. implode(NL, $new).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>