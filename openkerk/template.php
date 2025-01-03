<?php
include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 44);
include($cfgProgDir. "secure.php");

$leden = getGroupMembers(43);
$namen = array_merge($leden, $extern);
$openKerkTemplateNamen = getOpenKerkTemplates();

# De boel opslaan indien op 'opslaan' is geklikt, of als op uitrollen is geklikt
# maar alleen als het 1ste scherm van uitrollen moet worden getoond
# niet het 2de scherm ($_POST['uitrollen'])
#if(isset($_POST['save']) OR isset($_POST['enroll']) AND !isset($_POST['uitrollen'])) {
if(isset($_POST['item'])) {
	$template = $_POST['template'];
	
	for($week = 0; $week < 2 ; $week++) {
		for($dag=$minDag; $dag <= $maxDag ; $dag++) {
			foreach($uren as $slotID => $slot) {
				for($pos=0; $pos < $aantal ; $pos++) {
					$sql_delete = "DELETE FROM $TableOpenKerkTemplate WHERE $OKTemplateTemplate = $template AND $OKTemplateWeek = $week AND $OKTemplateDag = $dag AND $OKTemplateTijd = $slotID AND $OKTemplatePos = $pos";
					mysqli_query($db, $sql_delete);
					#$text[] = $sql_delete .'<br>';
					
					$store = 0;
					$persoon = $_POST['item'][$week][$dag][$slotID][$pos];
					if(isset($_POST['store'][$week][$dag][$slotID][$pos])) {
						$store = $_POST['store'][$week][$dag][$slotID][$pos];
					}
					if($persoon != '' OR $store > 0) {
						$sql_insert = "INSERT INTO $TableOpenKerkTemplate ($OKTemplateTemplate, $OKTemplateEnroll, $OKTemplateWeek, $OKTemplateDag, $OKTemplateTijd, $OKTemplatePos, $OKTemplatePersoon) VALUES ('$template', '$store', '$week', '$dag', '$slotID', '$pos', '$persoon')";
						mysqli_query($db, $sql_insert);
						#$text[] = $sql_insert .'<br>';
					}					
				}
			}
		}
	}	
	
	if(isset($_POST['save'])) {
		$text[] = 'Wijzigingen in het template zijn opgeslagen, deze zijn dus nog niet doorgevoerd in het rooster';
		$text[] = '<p>&nbsp;</p>';
	}
}

if(isset($_POST['enroll'])) {
	$template = $_POST['template']; 
	
	if(isset($_POST['uitrollen'])) {				
		$offset	= 0;
		$start	= mktime(0,0,0,$_POST['start_maand'],$_POST['start_dag'],$_POST['start_jaar']);
		$eind		= mktime(0,0,0,$_POST['eind_maand'],$_POST['eind_dag'],$_POST['eind_jaar']);
		
		do {
			$nieuweDag	= mktime(0,0,0,date('n', $start),(date('j', $start)+$offset));
			$week				= fmod(strftime('%V', $nieuweDag), 2);
			$dag				= strftime('%w', $nieuweDag);
									
			# Als er een leeg rooster moet worden uitgerold, alleen op vakantie dagen
			# anders alle dagen van de week
			foreach($uren as $slotID => $slot) {
				$startTijd = mktime($slot[0],$slot[1],0,date('n', $nieuweDag),date('j', $nieuweDag), date('Y', $nieuweDag));
				$eindTijd = mktime($slot[2],$slot[3],0,date('n', $nieuweDag),date('j', $nieuweDag), date('Y', $nieuweDag));
								
				$vulling = getOpenKerkVulling($template, $week, $dag, $slotID);
				$store[0]	= getStore($template, $week, $dag, $slotID, 0);
				$store[1]	= getStore($template, $week, $dag, $slotID, 1);
				
				# Als de vulling voor dit tijdslot niet bestaat
				# maar hij moet wél worden uitgerold ($store = true)
				# dan moet dus een leeg veld worden uitgerold
				if(!array_key_exists(0, $vulling) AND $store[0])	$vulling[0] = 0;
				if(!array_key_exists(1, $vulling) AND $store[1])	$vulling[1] = 0;

				#echo $template.'|'.$week.'|'.$dag.'|'.$slotID.'|'.count($vulling) .'<br>';
														
				foreach($vulling as $pos => $persoon) {						
					$sql_insert = "INSERT INTO $TableOpenKerkRooster ($OKRoosterStart, $OKRoosterEind, $OKRoosterPos, $OKRoosterPersoon) VALUES ('$startTijd', '$eindTijd', '$pos', '$persoon')";
					mysqli_query($db, $sql_insert);
				}
			}
			
			$offset++;
		} while($nieuweDag < $eind);
		
		$text[] = 'Het rooster is op basis van template <i>'. $openKerkTemplateNamen[$template] .'</i> uitgerold van '. strftime('%e %B', $start) .' tot '. strftime('%e %B', $eind) .'.<br>';
		$text[] = '<br>';
		$text[] = 'Klik <a href="showRooster.php">hier</a> om door te gaan naar het rooster';
	} else {
		$sql = "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterStart > ". time() ." ORDER BY $OKRoosterStart DESC";
		$result = mysqli_query($db, $sql);
		if(mysqli_num_rows($result) == 0) {
			$laatste = time();
		} else {
			$row = mysqli_fetch_array($result);
			$laatste = $row[$OKRoosterStart]+(24*60*60);
		}
	
		$start = mktime(0,0,0,date('n', $laatste),date('j', $laatste));
		$eind = $start + (14*24*60*60);
		
		$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
		$text[] = "<input type='hidden' name='uitrollen' value='true'>";
		$text[] = "<input type='hidden' name='template' value='$template'>";		
		$text[] = "<table border=0>";
		$text[] = "	<tr>";
		$text[] = "		<td colspan='2'>Selecteer hieronder de start- en einddatum<br>waarvoor het rooster gevuld moet worden.<br><br><i>Let wel op dat de startdatum vervroegen<br>tot dubbelingen in het rooster leidt.</i></td>";
		$text[] = "	</tr>";
		$text[] = "	<tr>";
		$text[] = "		<td colspan='2'>&nbsp;</td>";
		$text[] = "	</tr>";
		$text[] = "	<tr>";
		$text[] = "		<td>Startdatum :</td>";
		$text[] = "		<td><select name='start_dag'>";
		for($d=1;$d<=31;$d++) $text[] = "<option value='$d'". ($d == date('j', $start) ? ' selected' : '') .">$d</option>";
		$text[] = "</select>";		
		$text[] = "<select name='start_maand'>";
		for($m=1;$m<=12;$m++) $text[] = "<option value='$m'". ($m == date('n', $start) ? ' selected' : '') .">". $maandArray[$m] ."</option>";
		$text[] = "</select>";		
		$text[] = "<select name='start_jaar'>";
		for($j=date('Y');$j<=(date('Y')+1);$j++) $text[] = "<option value='$j'". ($j == date('Y', $start) ? ' selected' : '') .">$j</option>";
		$text[] = "</select></td>";
		$text[] = "	</tr>";
		$text[] = "	<tr>";
		$text[] = "		<td>Einddatum :</td>";
		$text[] = "		<td><select name='eind_dag'>";
		for($d=1;$d<=31;$d++) $text[] = "<option value='$d'". ($d == date('j', $eind) ? ' selected' : '') .">$d</option>";
		$text[] = "</select>";		
		$text[] = "<select name='eind_maand'>";
		for($m=1;$m<=12;$m++) $text[] = "<option value='$m'". ($m == date('n', $eind) ? ' selected' : '') .">". $maandArray[$m] ."</option>";
		$text[] = "</select>";		
		$text[] = "<select name='eind_jaar'>";
		for($j=date('Y');$j<=(date('Y')+1);$j++) $text[] = "<option value='$j'". ($j == date('Y', $eind) ? ' selected' : '') .">$j</option>";
		$text[] = "</select></td>";
		$text[] = "	</tr>";
		$text[] = "	<tr>";
		$text[] = "		<td colspan='2'>&nbsp;</td>";
		$text[] = "	</tr>";
		$text[] = "	<tr>";
		$text[] = "		<td width='50%' align='center'><input type='submit' name='terug' value='Terug'></td>";
		$text[] = "		<td width='50%' align='center'><input type='submit' name='enroll' value='Rooster vullen'></td>";
		$text[] = "	</tr>";
		$text[] = "	</table>";
		$text[] = "</form>";
	}		
} elseif(isset($_POST['template'])) {
	# Een keer alle namen ophalen en in een array zetten zodat dit later hergebruikt kan worden
	foreach($namen as $key => $value) {
		if(is_array($value)) {
			$namenArray[$key] = $value['naam'];
		} else {
			$namenArray[$value] = makeName($value, 5);
		}
	}
	
	$template = $_POST['template'];
	
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<input type='hidden' name='template' value='$template'>";
	$text[] = "<table border=0>";
	for($week = 0; $week < 2 ; $week++) {	
		$text[] = "	<tr>";
		$text[] = "		<td colspan='". ($maxDag-$minDag+2) ."'><h1>".($week == 0 ? 'EVEN' : 'ONEVEN') ." WEKEN</h1></td>";
		$text[] = "	</tr>";		
		$text[] = "	<tr>";
		$text[] = "		<td>&nbsp;</td>";
		for($dag=$minDag; $dag <= $maxDag ; $dag++) {
			$text[] = "		<td>". $dagNamen[$dag] ."</td>";
		}
				
		foreach($uren as $slotID => $slot) {
			$text[] = "	<tr>";
			$text[] = "		<td>". $slot[0] .":". substr('0'.$slot[1], -2) ." - ". $slot[2] .":". substr('0'.$slot[3], -2) ."</td>";
			for($dag=$minDag; $dag <= $maxDag ; $dag++) {
				$text[] = "		<td>";
				
				$vulling	= getOpenKerkVulling($template, $week, $dag, $slotID);
								
				for($positie=0; $positie < $aantal ; $positie++) {
					$store		= getStore($template, $week, $dag, $slotID, $positie);
					
					$text[] = "<input type='checkbox' name='store[$week][$dag][$slotID][$positie]' value='1'". ($store ? ' checked' : '') ."> <select name='item[$week][$dag][$slotID][$positie]'>";
					$text[] = "<option value=''></option>";
					
					foreach($namenArray as $id => $naam) {
						$text[] = "<option value='$id'". ((isset($vulling[$positie]) AND $vulling[$positie] == $id) ? ' selected' : '') .">". $naam ."</option>";												
					}				
					$text[] = "		</select><br>";
				}
				$text[] = "		</td>";
			}
			$text[] = "	</tr>";
		}
		
		$text[] = "	</tr>";		
		$text[] = "	<tr>";
		$text[] = "		<td colspan='". ($maxDag-$minDag+2) ."'>&nbsp;</td>";
		$text[] = "	</tr>";	
	}
	
	$helft = floor(0.5*($maxDag-$minDag+2));
		
	$text[] = "	<tr>";
	$text[] = "		<td>&nbsp;</td>";
	$text[] = "		<td colspan='". ($maxDag-$minDag+1) ."'><small>De vinkjes voor de posities bepalen welke posities worden uitgerold in het rooster.<br>Door vinkjes aan- of uit te zetten kan dus bepaald worden welke tijdsblokken wel en niet in het rooster geplaatste worden.</small></td>";
	$text[] = "	</tr>";		
	$text[] = "	<tr>";
	$text[] = "		<td colspan='". ($maxDag-$minDag+2) ."'>&nbsp;</td>";
	$text[] = "	</tr>";		
	$text[] = "	<tr>";
	$text[] = "		<td colspan='$helft' align='center'><input type='submit' name='save' value='Template opslaan'></td>";
	
	if((($maxDag-$minDag+2)-(2*$helft)) == 1) {
		$text[] = "		<td>&nbsp;</td>";
	}
		
	$text[] = "		<td colspan='$helft' align='center'><input type='submit' name='enroll' value='Opslaan en aangevinkte namen uitrollen'></td>";
	$text[] = "	</tr>";		
	$text[] = "</table>";
	$text[] = "</form>";
} else {	
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<table border=0 align='center'>";
	$text[] = "	<tr>";
	$text[] = "		<td>Welk template wil je aanpassen</td>";
	$text[] = "	</tr>";
	
	$text[] = "	<tr>";
	$text[] = "		<td><select name='template'>";
	$text[] = "		<option value=''></option>";
		
	foreach($openKerkTemplateNamen as $id => $naam) {
		$text[] = "<option value='$id'>$naam</option>";
	}
	
	$text[] = "</select></td>";
	$text[] = "	</tr>";
	$text[] = "	<tr>";
	$text[] = "		<td>&nbsp;</td>";
	$text[] = "	</tr>";
	
	$text[] = "	<tr>";
	$text[] = "		<td align='center'><input type='submit' name='template_select' value='Doorgaan'></td>";
	$text[] = "	</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
	$text[] = "</table>";
	$text[] = "</form>";
}


echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;


?>