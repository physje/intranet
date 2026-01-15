<?php
include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Team.php');
include_once('../Classes/OpenKerkTemplate.php');
include_once('../Classes/OpenKerkRooster.php');

$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 44);
include($cfgProgDir. "secure.php");

$team		= new Team(43);
$namen		= array_merge($team->leden, $extern);
$templates	= OpenKerkTemplate::getAllTemplates();
$block		= array();
$half		= true;

# De boel opslaan indien op 'opslaan' is geklikt, of als op uitrollen is geklikt
# maar alleen als het 1ste scherm van uitrollen moet worden getoond
# niet het 2de scherm ($_POST['uitrollen'])
if(isset($_POST['item'])) {
	$template = $_POST['template'];
	
	for($week = 0; $week < 2 ; $week++) {
		for($dag=$minDag; $dag <= $maxDag ; $dag++) {
			foreach($uren as $slotID => $slot) {
				$personen = $enroll = array();				
				for($pos=0; $pos < $aantal ; $pos++) {
					$store = 0;
					$persoon = $_POST['item'][$week][$dag][$slotID][$pos];
					if(isset($_POST['store'][$week][$dag][$slotID][$pos])) {
						#$store = $_POST['store'][$week][$dag][$slotID][$pos];
						$store = 1;
					}

					if($persoon != '' OR $store > 0) {
						$personen[$pos] = $persoon;
						$enroll[$pos] = $store;
					}					
				}
				$vulling = new OpenKerkTemplate($template, $week, $dag, $slotID);
				$vulling->leden = $personen;
				$vulling->enroll = $enroll;
				$vulling->save();				
			}
		}
	}	
	
	if(isset($_POST['save'])) {
		$block[] = 'Wijzigingen in het template zijn opgeslagen, deze zijn dus nog niet doorgevoerd in het rooster';
		$block[] = '<p>&nbsp;</p>';
	}
}

if(isset($_POST['enroll'])) {
	$template = $_POST['template'];
	
	if(isset($_POST['uitrollen'])) {				
		$offset	= 0;
		$start	= mktime(0,0,0,$_POST['start_maand'],$_POST['start_dag'],$_POST['start_jaar']);
		$eind	= mktime(0,0,0,$_POST['eind_maand'],$_POST['eind_dag'],$_POST['eind_jaar']);
		
		do {
			$nieuweDag	= mktime(0,0,0,date('n', $start),(date('j', $start)+$offset));
			$week				= fmod(time2str('w', $nieuweDag), 2);
			$dag				= time2str('e', $nieuweDag);
												
			# Als er een leeg rooster moet worden uitgerold, alleen op vakantie dagen
			# anders alle dagen van de week
			foreach($uren as $slotID => $slot) {
				$startTijd = mktime($slot[0],$slot[1],0,date('n', $nieuweDag),date('j', $nieuweDag), date('Y', $nieuweDag));
				$eindTijd = mktime($slot[2],$slot[3],0,date('n', $nieuweDag),date('j', $nieuweDag), date('Y', $nieuweDag));				

				$vulling = new OpenKerkTemplate($template, $week, $dag, $slotID);
				$store	= $vulling->enroll;

				# Als er iets moet worden opgeslagen, dan op het rooster zetten
				if(count($store) > 0) {									
					# Als de vulling voor dit tijdslot niet bestaat
					# maar hij moet wél worden uitgerold ($store = 1/true)
					# dan moet dus een leeg veld worden uitgerold
					if(!array_key_exists(0, $vulling->leden) AND $store[0])	$vulling->leden[0] = 0;
					if(!array_key_exists(1, $vulling->leden) AND $store[1])	$vulling->leden[1] = 0;
					
					$rooster = new OpenKerkRooster();
					$rooster->start		= $startTijd;
					$rooster->eind		= $eindTijd;
					$rooster->personen	= $vulling->leden;
					$rooster->save();
				}
			}
			
			$offset++;
		} while($nieuweDag < $eind);
		
		$block[] = 'Het rooster is op basis van template <i>'. $templates[$template] .'</i> uitgerold van '. time2str('d LLLL', $start) .' tot '. time2str('d LLLLF', $eind) .'.<br>';
		$block[] = '<br>';
		$block[] = 'Klik <a href="index.php">hier</a> om door te gaan naar het rooster';
	} else {
		$laatste = OpenKerkRooster::getLastStart();
	
		$start = mktime(0,0,0,date('n', $laatste),date('j', $laatste));
		$eind = $start + (14*24*60*60);
		
		$block[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
		$block[] = "<input type='hidden' name='uitrollen' value='true'>";
		$block[] = "<input type='hidden' name='template' value='$template'>";		
		$block[] = "<table border=0>";
		$block[] = "	<tr>";
		$block[] = "		<td colspan='2'>Selecteer hieronder de start- en einddatum<br>waarvoor het rooster gevuld moet worden.<br><br><i>Let wel op dat de startdatum vervroegen<br>tot dubbelingen in het rooster leidt.</i></td>";
		$block[] = "	</tr>";
		$block[] = "	<tr>";
		$block[] = "		<td colspan='2'>&nbsp;</td>";
		$block[] = "	</tr>";
		$block[] = "	<tr>";
		$block[] = "		<td>Startdatum :</td>";
		$block[] = "		<td><select name='start_dag'>";
		for($d=1;$d<=31;$d++) $block[] = "<option value='$d'". ($d == date('j', $start) ? ' selected' : '') .">$d</option>";
		$block[] = "</select>";		
		$block[] = "<select name='start_maand'>";
		for($m=1;$m<=12;$m++) $block[] = "<option value='$m'". ($m == date('n', $start) ? ' selected' : '') .">". $maandArray[$m] ."</option>";
		$block[] = "</select>";		
		$block[] = "<select name='start_jaar'>";
		for($j=date('Y');$j<=(date('Y')+1);$j++) $block[] = "<option value='$j'". ($j == date('Y', $start) ? ' selected' : '') .">$j</option>";
		$block[] = "</select></td>";
		$block[] = "	</tr>";
		$block[] = "	<tr>";
		$block[] = "		<td>Einddatum :</td>";
		$block[] = "		<td><select name='eind_dag'>";
		for($d=1;$d<=31;$d++) $block[] = "<option value='$d'". ($d == date('j', $eind) ? ' selected' : '') .">$d</option>";
		$block[] = "</select>";		
		$block[] = "<select name='eind_maand'>";
		for($m=1;$m<=12;$m++) $block[] = "<option value='$m'". ($m == date('n', $eind) ? ' selected' : '') .">". $maandArray[$m] ."</option>";
		$block[] = "</select>";		
		$block[] = "<select name='eind_jaar'>";
		for($j=date('Y');$j<=(date('Y')+1);$j++) $block[] = "<option value='$j'". ($j == date('Y', $eind) ? ' selected' : '') .">$j</option>";
		$block[] = "</select></td>";
		$block[] = "	</tr>";
		$block[] = "	<tr>";
		$block[] = "		<td colspan='2'>&nbsp;</td>";
		$block[] = "	</tr>";
		$block[] = "	<tr>";
		$block[] = "		<td width='50%' align='center'><input type='submit' name='terug' value='Terug'></td>";
		$block[] = "		<td width='50%' align='center'><input type='submit' name='enroll' value='Rooster vullen'></td>";
		$block[] = "	</tr>";
		$block[] = "	</table>";
		$block[] = "</form>";
	}		
} elseif(isset($_POST['template'])) {
	# Een keer alle namen ophalen en in een array zetten zodat dit later hergebruikt kan worden
	foreach($namen as $key => $value) {
		if(is_array($value)) {
			$namenArray[$key] = $value['naam'];
		} else {
			$person = new Member($value);
			$namenArray[$value] = $person->getName();
		}
	}
	
	$half = false;
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
				
				$vulling	= new OpenKerkTemplate($template, $week, $dag, $slotID);
				$store		= $vulling->enroll;
								
				for($positie=0; $positie < $aantal ; $positie++) {					
					$text[] = "<input type='checkbox' name='store[$week][$dag][$slotID][$positie]' value='1'". (isset($store[$positie]) && $store[$positie] ? ' checked' : '') ."> <select name='item[$week][$dag][$slotID][$positie]'>";
					$text[] = "<option value=''></option>";
					
					foreach($namenArray as $id => $naam) {
						$text[] = "<option value='$id'". ((isset($vulling->leden[$positie]) AND $vulling->leden[$positie] == $id) ? ' selected' : '') .">". $naam ."</option>";												
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
	$block[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$block[] = "<table border=0 align='center'>";
	$block[] = "	<tr>";
	$block[] = "		<td>Welk template wil je aanpassen</td>";
	$block[] = "	</tr>";
	
	$block[] = "	<tr>";
	$block[] = "		<td><select name='template'>";
	$block[] = "		<option value=''></option>";
		
	foreach($templates as $id => $naam) {
		$block[] = "<option value='$id'>$naam</option>";
	}
	
	$block[] = "</select></td>";
	$block[] = "	</tr>";
	$block[] = "	<tr>";
	$block[] = "		<td>&nbsp;</td>";
	$block[] = "	</tr>";	
	$block[] = "	<tr>";
	$block[] = "		<td align='center'><input type='submit' name='template_select' value='Doorgaan'></td>";
	$block[] = "	</tr>";
	$block[] = "</table>";
	$block[] = "</form>";
	$block[] = "</table>";
	$block[] = "</form>";
}

echo showCSSHeader();

if($half) {
	echo '<div class="content_vert_kolom">'.NL;
	echo "<div class='content_block'>". implode(NL, $block) ."</div>".NL;
	echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
} else {
	echo '<div class="content_vert_kolom_full">'.NL;
	echo "<div class='content_block'>". implode(NL, $text) ."</div>".NL;
	echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
}

echo showCSSFooter();

?>