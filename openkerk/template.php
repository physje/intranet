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

if(isset($_POST['save'])) {
	$template = $_POST['template']; 
	
	foreach($_POST['item'] as $week => $sub) {
		foreach($sub as $dag => $sub2) {
			foreach($sub2 as $slot => $sub3) {
				foreach($sub3 as $pos => $persoon) {
					//$text[] = $week .' -> '. $dag .' -> '. $uur .' -> '. $persoon .'<br>';
					$sql_delete = "DELETE FROM $TableOpenKerkTemplate WHERE $OKTemplateTemplate = $template AND $OKTemplateWeek = $week AND $OKTemplateDag = $dag AND $OKTemplateTijd = $slot AND $OKTemplatePos = $pos";
					mysqli_query($db, $sql_delete);
					//$text[] = $sql_delete .'<br>';
					
					if($persoon != '') {
						$sql_insert = "INSERT INTO $TableOpenKerkTemplate ($OKTemplateTemplate, $OKTemplateWeek, $OKTemplateDag, $OKTemplateTijd, $OKTemplatePos, $OKTemplatePersoon) VALUES ('$template', '$week', '$dag', '$slot', '$pos', '$persoon')";
						mysqli_query($db, $sql_insert);
						//$text[] = $sql_insert .'<br>';
					}
				}
			}
		}
	}
	$text[] = 'Wijzigingen in het template zijn opgeslagen, deze zijn dus nog niet doorgevoerd in het rooster';
} elseif(isset($_POST['enroll']) OR isset($_POST['enroll_empty'])) {
	$template = $_POST['template']; 
	
	if(isset($_POST['uitrollen'])) {				
		$offset	= 0;
		$start	= mktime(0,0,0,$_POST['start_maand'],$_POST['start_dag'],$_POST['start_jaar']);
		$eind		= mktime(0,0,0,$_POST['eind_maand'],$_POST['eind_dag'],$_POST['eind_jaar']);
		
		do {
			$nieuweDag	= mktime(0,0,0,date('n', $start),(date('j', $start)+$offset));
			$week				= fmod(strftime('%W', $nieuweDag), 2);
			$dag				= strftime('%w', $nieuweDag);
						
			foreach($uren as $slotID => $slot) {
				$startTijd = mktime($slot[0],$slot[1],0,date('n', $nieuweDag),date('j', $nieuweDag), date('Y', $nieuweDag));
				$eindTijd = mktime($slot[2],$slot[3],0,date('n', $nieuweDag),date('j', $nieuweDag), date('Y', $nieuweDag));
				
				$vulling = getOpenKerkVulling($template, $week, $dag, $slotID);
								
				foreach($vulling as $pos => $persoon) {
					# Met name voor vakanties -als het rooster op inschrijven gaat- is het handig om
					# een leeg rooster uit te rollen.
					# Als $_POST['empty'] bekend is, moet er geen persoon worden ingevuld
					if(isset($_POST['empty'])) $persoon = 0;
					
					$sql_insert = "INSERT INTO $TableOpenKerkRooster ($OKRoosterStart, $OKRoosterEind, $OKRoosterPos, $OKRoosterPersoon) VALUES ('$startTijd', '$eindTijd', '$pos', '$persoon')";
					#$sql_insert = "INSERT INTO $TableOpenKerkRooster ($OKRoosterStart, $OKRoosterPos, $OKRoosterPersoon) VALUES (".$tijdstip .", $pos, '$persoon')";
					mysqli_query($db, $sql_insert);
					
					#echo time2str("%a %d %b %H:%M", $startTijd) .'-'. time2str("%H:%M", $eindTijd) .'|'. $pos .'|'. makeName($persoon, 5) .'<br>';					
				}
			}
						
			$offset++;
		} while($nieuweDag < $eind);
		
		if(isset($_POST['empty'])) {
			$text[] = 'Het rooster is op basis van een lege template uitgerold van '. strftime('%e %B', $start) .' tot '. strftime('%e %B', $eind) .'.<br>';
		} else {
			$text[] = 'Het rooster is op basis van template <i>'. $openKerkTemplateNamen[$template] .'</i> uitgerold van '. strftime('%e %B', $start) .' tot '. strftime('%e %B', $eind) .'.<br>';
		}
		
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
		if(isset($_POST['enroll_empty']))	$text[] = "<input type='hidden' name='empty' value='true'>";
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
		$text[] = "		<td width='50%' align='center'><input type='submit' name='enroll' value='Agenda vullen'></td>";
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
			
		#for($uur=$minUur; $uur < $maxUur ; $uur++) {
		foreach($uren as $slotID => $slot) {
			$text[] = "	<tr>";
			$text[] = "		<td>". $slot[0] .":". substr('0'.$slot[1], -2) ." - ". $slot[2] .":". substr('0'.$slot[3], -2) ."</td>";
			for($dag=$minDag; $dag <= $maxDag ; $dag++) {
				$text[] = "		<td>";
				
				$vulling = getOpenKerkVulling($template, $week, $dag, $slotID);
				
				for($positie=0; $positie < $aantal ; $positie++) {
					$text[] = "<select name='item[$week][$dag][$slotID][$positie]'>";
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
	
	#$helft = floor(0.5*($maxDag-$minDag+2));
	$derde = floor(($maxDag-$minDag+2)/3);
		
	$text[] = "	<tr>";
	$text[] = "		<td colspan='$derde' align='center'><input type='submit' name='save' value='Template opslaan'></td>";
	
	if((($maxDag-$minDag+2)-(3*$derde)) == 1) {
		$text[] = "		<td>&nbsp;</td>";
	}
		
	$text[] = "		<td colspan='$derde' align='center'><input type='submit' name='enroll' value='Template uitrollen'></td>";
	$text[] = "		<td colspan='$derde' align='center'><input type='submit' name='enroll_empty' value='Leeg uitrollen'></td>";
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