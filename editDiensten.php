<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');

$requiredUserGroups = array(1, 28);
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

# Als er op een knop gedrukt is, het rooster wegschrijven
if(isset($_POST['save']) OR isset($_POST['maanden'])) {	
	foreach($_POST['bijz'] as $dienst => $bijzonderheid) {
		$details	= getKerkdienstDetails($dienst);
		
		# Admin mag dag, maand en jaar wijzigen
		if(in_array(1, getMyGroups($_SESSION['ID']))) {
			$dag				= $_POST['sDag'][$dienst];
			$maand			= $_POST['sMaand'][$dienst];
			$jaar				= $_POST['sJaar'][$dienst];
		} else {
			$dag				= date("d", $details['start']);
			$maand			= date("m", $details['start']);
			$jaar				= date("Y", $details['start']);
		}

		$startTijd	= mktime($_POST['sUur'][$dienst], $_POST['sMin'][$dienst], 0, $maand, $dag, $jaar);
		$eindTijd		= mktime($_POST['eUur'][$dienst], $_POST['eMin'][$dienst], 0, $maand, $dag, $jaar);
		
		$set = array();
		
		$set[] = $DienstStart .' = '. $startTijd;
		$set[] = $DienstEind .' = '. $eindTijd;
		$set[] = $DienstOpmerking .' = \''. urlencode($bijzonderheid) .'\'';
				
		$sql = "UPDATE $TableDiensten SET ". implode(', ', $set)." WHERE $DienstID = ". $dienst;
				
		mysqli_query($db, $sql);
	}
	toLog('info', $_SESSION['ID'], '', 'Diensten bijgewerkt');
}

if(isset($_REQUEST['new'])) {
	$start	= mktime(9,0,0,date("n"),date("j"), date("Y"));
	$eind		= mktime(9,30,0,date("n"),date("j"), date("Y"));		
	$query	= "INSERT INTO $TableDiensten ($DienstStart, $DienstEind) VALUES ('$start', '$eind')";
	$result = mysqli_query($db, $query);
			
	toLog('info', $_SESSION['ID'], '', 'Dienst voor '. date("d-m-Y", $start) .' toegevoegd');
}

if(isset($_REQUEST['delete']) AND !isset($_REQUEST['cancel'])) {
	$details	= getKerkdienstDetails($_REQUEST['id']);
	
	if(isset($_REQUEST['sureDelete'])) {
		$query	= "DELETE FROM $TableDiensten WHERE $DienstID = ". $_REQUEST['id'];
		$result = mysqli_query($db, $query);
			
		toLog('info', $_SESSION['ID'], '', formatDagdeel($details['start']).' van '. date("d-m-Y", $details['start']) .' ['. $_REQUEST['id'] .'] verwijderd');
	} else {
		$text[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
		$text[] = "<input type='hidden' name='delete' value='ja'>";
		$text[] = "<input type='hidden' name='id' value='".$_REQUEST['id']."'>";
		$text[] = "<table>";
		$text[] = "<tr>";
		$text[] = "	<td colspan='2'>Weet je zeker dat je de ".formatDagdeel($details['start']).' van '. time2str('%e %B %Y', $details['start']) ." wilt verwijderen ?</td>";
		$text[] = "</tr>";
		$text[] = "	<td colspan='2'>Als je deze dienst verwijderd, worden ook alle roosters voor deze dienst verwijderd<br>";
		$text[] = "	Verwijder een dienst dus alleen als deze niet doorgaat</td>";
		$text[] = "</tr>";
		$text[] = "<tr>";
		$text[] = "	<td colspan='2'>&nbsp;</td>";
		$text[] = "</tr>";		
		$text[] = "<tr>";
		$text[] = "	<td><input type='submit' name='sureDelete' value='Zeker weten'></td>";
		$text[] = "	<td align='right'><input type='submit' name='cancel' value='Annuleren'></td>";
		$text[] = "</tr>";
		$text[] = "</table>";		
		$text[] = "</form>";		
	}	
}

if(!isset($_REQUEST['delete']) OR (isset($_REQUEST['delete']) AND isset($_REQUEST['sureDelete'])) OR (isset($_REQUEST['delete']) AND isset($_REQUEST['cancel']))) {
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
	$diensten = getKerkdiensten($start, $einde);
	
	$text[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$text[] = "<input type='hidden' name='start' value='$start'>";
	$text[] = "<table border=0>";
	$text[] = "<tr>";
	$text[] = "	<td>Datum</td>";
	$text[] = "	<td>Start</td>";
	$text[] = "	<td>Eind</td>";
	$text[] = "	<td>Bijzonderheid</td>";
	if(in_array(1, getMyGroups($_SESSION['ID']))) {
		$text[] = "	<td>&nbsp;</td>";
	}
	$text[] = "</tr>";
	
	foreach($diensten as $dienst) {
		$data = getKerkdienstDetails($dienst);
		
		$sMin		= date("i", $data['start']);
		$sUur		= date("H", $data['start']);
		
		$eMin		= date("i", $data['eind']);
		$eUur		= date("H", $data['eind']);
		
		$text[] = "<tr>";
		
		if(in_array(1, getMyGroups($_SESSION['ID']))) {
			$sDag			= date("d", $data['start']);
			$sMaand		= date("m", $data['start']);
			$sJaar		= date("Y", $data['start']);
			
			$text[] = "	<td><select name='sDag[$dienst]'>";
			for($d=1; $d<=31 ; $d++) {
				$text[] = "	<option value='$d'". ($d == $sDag ? ' selected' : '') .">$d</option>";
			}
			$text[] = "	</select>";
			$text[] = "	<select name='sMaand[$dienst]'>";
			for($m=1; $m<=12 ; $m++) {
				$text[] = "	<option value='$m'". ($m == $sMaand ? ' selected' : '') .">". $maandArray[$m] . "</option>";
			}
			$text[] = "	</select>";
			$text[] = "	<select name='sJaar[$dienst]'>";
			for($j=date('Y'); $j<=(date('Y')+2) ; $j++) {
				$text[] = "	<option value='$j'". ($j == $sJaar ? ' selected' : '') .">$j</option>";
			}		
			$text[] = "	</select></td>";				
		} else {
			$text[] = "	<td align='right'>". time2str("%a %e %b", $data['start']) ."</td>";
		}
		//$text[] = "	<td align='right'>". date("d m Y", $data['start']) ."</td>";
		$text[] = "	<td><select name='sUur[$dienst]'>";
		for($u=0; $u<24 ; $u++) {
			$text[] = "	<option value='$u'". ($u == $sUur ? ' selected' : '') .">$u</option>";
		}
		$text[] = "	</select>";
		$text[] = "	<select name='sMin[$dienst]'>";
		for($m=0; $m<60 ; $m=$m+15) {
			$text[] = "	<option value='$m'". ($m == $sMin ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>";
		}
		$text[] = "	</select></td>";
		$text[] = "	<td><select name='eUur[$dienst]'>";
		for($u=0; $u<24 ; $u++) {
			$text[] = "	<option value='$u'". ($u == $eUur ? ' selected' : '') .">$u</option>";
		}
		$text[] = "	</select>";
		$text[] = "	<select name='eMin[$dienst]'>";
		for($m=0; $m<60 ; $m=$m+15) {
			$text[] = "	<option value='$m'". ($m == $eMin ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>";
		}
		$text[] = "	</select></td>";	
		$text[] = "	<td><input type='text' name='bijz[$dienst]' value=\"". $data['bijzonderheden'] ."\" size='30'></td>";	
		if(in_array(1, getMyGroups($_SESSION['ID']))) {
			$text[] = "	<td align='right'><a href='?delete=ja&id=$dienst'><img src='images\delete.png'></a></td>";
		}
		$text[] = "</tr>";
	}
	
	#$text[] = "<tr>";
	#$text[] = "<td colspan='5' align='middle'>";
	#$text[] = "<table width='100%'>";
	#$text[] = "<tr>";
	#$text[] = "	<td width='33%' align='left'><input type='submit' name='prev' value='Vorige 3 maanden'></td>";
	#$text[] = "	<td width='33%' align='center'><input type='submit' name='save' value='Diensten opslaan'></td>";
	#$text[] = "	<td width='33%' align='right'><input type='submit' name='next' value='Volgende 3 maanden'></td>";
	#$text[] = "</tr>";
	#$text[] = "</table>";
	#$text[] = "</td>";
	#$text[] = "</tr>";
	$text[] = "</table>";
	$text[] = "<p class='after_table'><input type='submit' name='prev' value='Vorige 3 maanden'>&nbsp;<input type='submit' name='save' value='Diensten opslaan'>&nbsp;<input type='submit' name='next' value='Volgende 3 maanden'></p>";
	$text[] = "</form>";
	
	if(in_array(1, getMyGroups($_SESSION['ID']))) {
		$new[] = "<a href='?new'>Extra dienst toevoegen</a>";
	}
}

$header[] = '<style>';
$header[] = '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
$header[] = '	td:nth-of-type(1):before { content: "Datum"; }';
$header[] = '	td:nth-of-type(2):before { content: "Start"; }';
$header[] = '	td:nth-of-type(3):before { content: "Eind"; }';
$header[] = '	td:nth-of-type(4):before { content: "Bijzonderheid"; }';
$header[] = "}";
$header[] = "</style>";

echo showCSSHeader(array('default', 'table_rot'), $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo '<h1>Kerkdiensten</h1>'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo "<div class='content_block'>".NL. implode(NL, $new).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>

?>