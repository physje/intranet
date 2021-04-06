<?php
include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 43);
include($cfgProgDir. "secure.php");

$leden = getGroupMembers(43);
$namen = array_merge($leden, $extern);

if(isset($_POST['save'])) {
	foreach($_POST['item'] as $week => $sub) {
		foreach($sub as $dag => $sub2) {
			foreach($sub2 as $uur => $sub3) {
				foreach($sub3 as $pos => $persoon) {
					//$text[] = $week .' -> '. $dag .' -> '. $uur .' -> '. $persoon .'<br>';
					$sql_delete = "DELETE FROM $TableOpenKerkTemplate WHERE $OKTemplateWeek = $week AND $OKTemplateDag = $dag AND $OKTemplateTijd = $uur AND $OKTemplatePos = $pos";
					mysqli_query($db, $sql_delete);
					//$text[] = $sql_delete .'<br>';
					
					if($persoon != '') {
						$sql_insert = "INSERT INTO $TableOpenKerkTemplate ($OKTemplateWeek, $OKTemplateDag, $OKTemplateTijd, $OKTemplatePos, $OKTemplatePersoon) VALUES ('$week', '$dag', '$uur', '$pos', '$persoon')";
						mysqli_query($db, $sql_insert);
						//$text[] = $sql_insert .'<br>';
					}
				}
			}
		}
	}
	$text[] = 'Wijzigingen in het template zijn opgeslagen, deze zijn dus nog niet doorgevoerd in het rooster';
} elseif(isset($_POST['enroll'])) {
	$sql = "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterTijd > ". time() ." ORDER BY $OKRoosterTijd DESC";
	$result = mysqli_query($db, $sql);
	if(mysqli_num_rows($result) == 0) {
		$laatste = time();
	} else {
		$row = mysqli_fetch_array($result);
		$laatste = $row[$OKRoosterTijd];
	}
	
	$start = mktime(0,0,0,date('n', $laatste),date('j', $laatste));	
	$text[] = 'Het rooster zal op basis van dit template worden uitgerold vanaf '. strftime('%e %B', $start) .'.<br>';
	
	for($offset=1 ; $offset <= 14 ; $offset++) {
		$nieuweDag	= mktime(0,0,0,date('n', $start),(date('j', $start)+$offset));
		$week				= fmod(strftime('%W', $nieuweDag), 2);
		$dag				= strftime('%w', $nieuweDag);
		
		for($uur=$minUur; $uur < $maxUur ; $uur++) {
			$tijdstip = mktime($uur,0,0,date('n', $nieuweDag),date('j', $nieuweDag));
			
			$vulling = getOpenKerkVulling($week, $dag, $uur);
			
			foreach($vulling as $pos => $persoon) {
				$sql_insert = "INSERT INTO $TableOpenKerkRooster ($OKRoosterTijd, $OKRoosterPos, $OKRoosterPersoon) VALUES (".$tijdstip .", $pos, '$persoon')";
				mysqli_query($db, $sql_insert);
			}
		}
		
		$text[] = strftime('%a %e %B', $nieuweDag).' -> '. $week .'<br>';
	}
	
	//$huidigeWeek = fmod(strftime('%W', $start), 2);
	
	//$startDatum = mktime(0, 0, 0, date('n'), date('j')+1+(7-strftime('%w')));
	
	/*
	# Oneven week
	if($huidigeWeek == 1) {
		$startDatum = mktime(0, 0, 0, date('n'), date('j')+1+(7-strftime('%w')));
	# Even week
	} else {		
		$startDatum = mktime(0, 0, 0, date('n'), date('j')+8+(7-strftime('%w')));
	}
	
	$text[] = 'Vandaag zitten wij in week '. strftime("%W") .', dat is een '. ($huidigeWeek == 1 ? 'oneven' : 'even') .' week.<br>';
	//$text[] = 'Het rooster zal op basis van dit template worden uitgerold vanaf '. strftime('%e %B', $startDatum) .'.<br>';	
	$text[] = 'Het rooster zal op basis van dit template worden uitgerold.<br>';
	$text[] = 'Daarbij zal het rooster wat al in het systeem staat worden aangevuld met 2 weken.<br>';
	$text[] = 'Elke keer klikken zal dus 2 weken toevoegen.<br>';
	*/
} else {
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<table border=1>";
	for($week = 0; $week < 2 ; $week++) {	
		$text[] = "	<tr>";
		$text[] = "		<td colspan='". ($maxDag-$minDag+2) ."'><h1>".($week == 0 ? 'EVEN' : 'ONEVEN') ." WEKEN</h1></td>";
		$text[] = "	</tr>";		
		$text[] = "	<tr>";
		$text[] = "		<td>&nbsp;</td>";
		for($dag=$minDag; $dag <= $maxDag ; $dag++) {
			$text[] = "		<td>". $dagNamen[$dag] ."</td>";
		}
			
		for($uur=$minUur; $uur < $maxUur ; $uur++) {
			$text[] = "	<tr>";
			$text[] = "		<td>$uur:00 - ". ($uur+1).":00</td>";
			for($dag=$minDag; $dag <= $maxDag ; $dag++) {
				$text[] = "		<td>";
				
				$vulling = getOpenKerkVulling($week, $dag, $uur);
				
				for($positie=0; $positie < $aantal ; $positie++) {
					$text[] = "<select name='item[$week][$dag][$uur][$positie]'>";
					$text[] = "<option value=''></option>";
					
					foreach($namen as $key => $value) {
						if(is_numeric($key)) {
							$text[] = "<option value='$value'". ((isset($vulling[$positie]) AND $value == $vulling[$positie]) ? ' selected' : '') .">". makeName($value, 5)."</option>";
						} else {
							$text[] = "<option value='$key'". ((isset($vulling[$positie]) AND $key == $vulling[$positie]) ? ' selected' : '') .">". $value['naam'] ."</option>";
						}
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
	$text[] = "		<td colspan='$helft'><input type='submit' name='save' value='Template opslaan'></td>";
	
	if((($maxDag-$minDag+2)-(2*$helft)) == 1) {
		$text[] = "		<td>&nbsp;</td>";
	}
	
	$text[] = "		<td colspan='$helft'><input type='submit' name='enroll' value='Template uitrollen'></td>";
	$text[] = "	</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
}


echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;


?>