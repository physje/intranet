<?php
# keuze voor 2 dagen
# bij wijzigingen niet hele dag weghalen
# 'back up tuingroep' & 'back up schoonmaak'

include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/OpenKerkRooster.php');
include_once('../Classes/Team.php');

$cfgProgDir = '../auth/';
$requiredUserGroups = array(1, 43, 44);
include($cfgProgDir. "secure.php");

$beheerder = new Team(44);
$gebruiker = new Team(43);
$admin = new Team(1);

$namen = array_merge($gebruiker->leden, $extern);
$blokGrootte = (31*24*60*60);

if(isset($_REQUEST['delete'])) {
	$rooster = new OpenKerkRooster($_REQUEST['t']);
	if($rooster->delete()) {
		$text[] = '<i>Moment is van het rooster verwijderd</i>';
	} else {
		$text[] = '<b>Moment is niet van het rooster verwijderd</b>';
	}
}

if(isset($_POST['save'])) {
	# Doorloop alle tijden
	# Verwijder het betreffende item als het leeg is
	# En voer de nieuwe in
	# item[$datum][$slotID][$positie]
	foreach($_POST['item'] as $datum => $sub) {
		foreach($sub as $slotID => $sub2) {
			$start	= mktime($uren[$slotID][0], $uren[$slotID][1], 0, date('n', $datum), date('j', $datum), date('Y', $datum));
			$eind	= mktime($uren[$slotID][2], $uren[$slotID][3], 0, date('n', $datum), date('j', $datum), date('Y', $datum));
			$leden	= array();
			foreach($sub2 as $pos => $persoon) {							
				if($persoon != '' && $persoon != 'leeg' ) {					
					$leden[$pos] = $persoon;
				} else {
					$leden[$pos] = 0;
				}
			}

			$rooster = new OpenKerkRooster($start);
			$rooster->start		= $start;
			$rooster->eind		= $eind;
			$rooster->personen	= $leden;
			$rooster->opmerking	= (isset($_POST['opmerking'][$start]) ? $_POST['opmerking'][$start] : '');
			$rooster->save();
		}
	}		
	$text[] = '<i>Wijzigingen in het rooster zijn opgeslagen</i>';
}

if(isset($_POST['start'])) {
	$start = $_POST['start'];
} else {
	$start = time();
}

if(isset($_POST['next_week'])) {
	$start = ($start + $blokGrootte);
}

$einde = $start + $blokGrootte;

# Een keer alle namen ophalen en in een array zetten zodat dit later hergebruikt kan worden
foreach($namen as $key => $value) {
	if(is_array($value)) {
		$namenArray[$key] = $value['naam'];
	} else {
		$person = new Member($value);
		$namenArray[$value] = $person->getName();
	}
}

# Kijk maximaal een maand vooruit
# Dat om te voorkomen dat de lijst te lang wordt
$lastDag = OpenKerkRooster::getLastStart();
if($lastDag > $einde)	$lastDag = $einde;

$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
$text[] = "<input type='hidden' name='start' value='$start'>";
$text[] = "<table>";
$text[] = "<tr>";
$text[] = "		<td colspan='2'>&nbsp;</td>";
$text[] = "		<td>Opmerkingen</td>";

if(in_array($_SESSION['useID'], $admin->leden) or in_array($_SESSION['useID'], $beheerder->leden)) {
	$text[] = "		<td>&nbsp;</td>";
}

$text[] = "</tr>";

$dag = 0;
$datum = $start;

while($datum < $lastDag) {
	foreach($uren as $slotID => $slot) {
		$row = array();
		$opnemen = false;
		
		$datum		= mktime(0, 0, 0, date('n', $start), (date('j', $start)+$dag), date('Y', $start));
		$tijdstip	= mktime($slot[0], $slot[1], 0, date('n', $start), (date('j', $start)+$dag), date('Y', $start));
		$eind		= mktime($slot[2], $slot[3], 0, date('n', $start), (date('j', $start)+$dag), date('Y', $start));
		$weekdag	= date('w', $tijdstip);
		
		#echo "datum : ". date("d-m-Y", $datum)."<br>";
		
		if(($minDag <= $weekdag) AND ($weekdag <= $maxDag)) {
			$row[] = "<tr>";
			$row[] = "		<td valign='top'>".time2str("E d LLL HH:mm", $tijdstip)." - ".time2str("HH:mm", $eind)."</td>";
			$row[] = "		<td valign='top'>";

			$rooster = new OpenKerkRooster($tijdstip);
					
			for($positie=0; $positie < $aantal ; $positie++) {
				$row[] = "<select name='item[$datum][$slotID][$positie]'>";
				$row[] = "<option value=''></option>";
				$row[] = "<option value='leeg'>[ leeg ]</option>";
								
				# Als er data in de database staat moet deze rij opgenomen worden
				# Mocht deze dag niet voorkomen in de database, dan hoeft deze rij ook niet opgenomen te worden
				if(isset($rooster->personen[$positie]))	$opnemen = true;
				
				foreach($namenArray as $id => $naam) {
					$row[] = "<option value='$id'". ((isset($rooster->personen[$positie]) AND $rooster->personen[$positie] == $id) ? ' selected' : '') .">". $naam ."</option>";												
				}	
								
				$row[] = "		</select>&nbsp;";
			}	
												
			$row[] = "</td>";
			$row[] = "<td><input type='text' name='opmerking[$tijdstip]' value='". ($rooster->opmerking != '' ? $rooster->opmerking : '') ."'></td>";
			if(in_array($_SESSION['useID'], $admin->leden) or in_array($_SESSION['useID'], $beheerder->leden)) {
				$row[] = "		<td><a href='?delete=1&t=$tijdstip' title='Verwijder dit moment uit het rooster'><img src='../images/delete.png'></a></td>";
			}
			$row[] = "</tr>";
		}
		
		if($opnemen) {
			$text = array_merge($text, $row);
		}
			
	}
	$dag++;
}

$text[] = "	<tr>";
$text[] = "		<td colspan='3'>&nbsp;</td>";
$text[] = "	</tr>";
$text[] = "	<tr>";
$text[] = "		<td><input type='submit' name='save' value='Opslaan'></td>";
$text[] = "		<td>&nbsp;</td>";
$text[] = "		<td><input type='submit' name='next_week' value='Toon volgende periode'></td>";
$text[] = "	</tr>";
$text[] = "</table>";
$text[] = "</form>";

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>". implode(NL, $text) ."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>