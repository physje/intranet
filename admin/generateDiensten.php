<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Logging.php');
include_once('../Classes/Member.php');

$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$querys = array();

if(isset($_POST['save'])) {	
	$startTijd	= mktime(0, 0, 1, 1, 1, $_POST['jaar']);
	$eindTijd	= mktime(23, 59, 59, 12, 31, $_POST['jaar']);
	$i = $eind = 0;
	$doorgaan = true;
		
	while($doorgaan) {
		$offset		= (7-date("N", $startTijd)) + (7*$i);
		
		if($eind < $eindTijd) {
			# Ochtenddienst
			if(isset($_POST['ochtend'])) {				
				$dienst = new Kerkdienst();
				$dienst->start	= mktime(10,0,0,date("n", $startTijd),(date("j", $startTijd)+$offset), date("Y", $startTijd));
				$dienst->eind	= mktime(11,30,0,date("n", $startTijd),(date("j", $startTijd)+$offset), date("Y", $startTijd));
				$dienst->save();
			}

			# Middagdienst
			if(isset($_POST['middag'])) {
				$dienst = new Kerkdienst();
				$dienst->start	= mktime(16,30,0,date("n", $startTijd),(date("j", $startTijd)+$offset), date("Y", $startTijd));
				$dienst->eind	= mktime(18,0,0,date("n", $startTijd),(date("j", $startTijd)+$offset), date("Y", $startTijd));
				$dienst->save();
			}

			$eind = $dienst->eind;
			$i++;
		} else {
			$doorgaan = false;
		}
		toLog('Diensten aangemaakt');
	}
	
	# Mocht Goede Vrijdag, Hemelvaart of omschrijvingen moeten worden toegevoegd
	# Dan even opvragen op welke data Pasen valt
	if(isset($_POST['vrijdag']) OR isset($_POST['hemelvaart']) OR isset($_POST['omschrijving'])) {
		$DataPasen = getPasen($_POST['jaar']);
	}	
		
	# Biddag (Biddag wordt altijd op de tweede woensdag van maart gehouden)
	if(isset($_POST['biddag'])) {
		$offset = 0;
		
		# Op welke dag valt 1 maart
		$marker = date("N", mktime(0, 0, 1, 3, 1, $_POST['jaar']));
				
		# $marker = 1 (maandag)		=> 10-3
		# $marker = 2 (dinsdag)		=> 9-3
		# $marker = 3 (woensdag)	=> 8-3
		# $marker = 4 (donderdag)	=> 14-3
		# $marker = 5 (vrijdag)		=> 13-3
		# $marker = 6 (zaterdag)	=> 12-3
		# $marker = 7 (zondag)		=> 11-3
		
		# Als $marker > 4 (lees 1 maart is na woensdag), dan week erbij op
		if($marker > 3)	$offset = 7;
						
		$dienst = new Kerkdienst();
		$dienst->start	= mktime(19, 30, 0, 3, (11-$marker+$offset), $_POST['jaar']);
		$dienst->eind	= mktime(21, 00, 0, 3, (11-$marker+$offset), $_POST['jaar']);
		$dienst->opmerking = 'Biddag';
		$dienst->save();
		toLog('Biddag aangemaakt', 'debug');
	}
		
	# Goede vrijdag (Goede vrijdag is de vrijdag voor Pasen = Eerste Paasdag min 2 dagen)
	if(isset($_POST['vrijdag']) AND count($DataPasen) > 1) {				
		$dienst = new Kerkdienst();
		$dienst->start		= mktime(19, 30, 0, $DataPasen['maand'], ($DataPasen['dag']-2), $_POST['jaar']);
		$dienst->eind		= mktime(21, 00, 0, $DataPasen['maand'], ($DataPasen['dag']-2), $_POST['jaar']);
		$dienst->opmerking	= 'Goede vrijdag';
		$dienst->save();
		toLog('Goede vrijdag aangemaakt', 'debug');
	}
	
	# Hemelvaart (Hemelvaart is 39 dagen na Eerste Paasdag)
	if(isset($_POST['vrijdag']) AND count($DataPasen) > 1) {				
		$dienst = new Kerkdienst();
		$dienst->start		= mktime(10, 00, 0, $DataPasen['maand'], ($DataPasen['dag']+39), $_POST['jaar']);
		$dienst->eind		= mktime(11, 30, 0, $DataPasen['maand'], ($DataPasen['dag']+39), $_POST['jaar']);
		$dienst->opmerking	= 'Hemelvaart';
		$dienst->save();
		toLog('Hemelvaart aangemaakt', 'debug');
	}
	
	# Dankdag (wordt iedere eerste woensdag van november gehouden)
	if(isset($_POST['dankdag'])) {
		$offset = 0;
		
		# Op welke dag valt 1 november
		$marker = date("N", mktime(0, 0, 1, 11, 1, $_POST['jaar']));

		# $marker = 1 (maandag)		=> 3-11
		# $marker = 2 (dinsdag)		=> 2-11
		# $marker = 3 (woensdag)	=> 1-11
		# $marker = 4 (donderdag)	=> 7-11
		# $marker = 5 (vrijdag)		=> 6-11
		# $marker = 6 (zaterdag)	=> 5-11
		# $marker = 7 (zondag)		=> 4-11

		# Als $marker > 4 (lees 1 november is na woensdag), dan week bij $marker erop
		if($marker > 3)	$offset = 7;

		$dienst = new Kerkdienst();
		$dienst->start	= mktime(19, 30, 0, 11, (4-$marker+$offset), $_POST['jaar']);
		$dienst->eind	= mktime(21, 00, 0, 11, (4-$marker+$offset), $_POST['jaar']);
		$dienst->opmerking = 'Dankdag';
		$dienst->save();
		toLog('Dankdag aangemaakt', 'debug');
	}
	
	# Dienst van 1ste kerstdag inplannen
	if(isset($_POST['kerst'])) {
		# Op welke dag valt 25 december
		$marker = date("N", mktime(0, 0, 1, 12, 25, $_POST['jaar']));
		
		# Als 1ste Kerstdag op zondag valt, hoeft er geen extra dienst toegevoegd te worden
		if($marker < 7) {
			$dienst = new Kerkdienst();
			$dienst->start	= mktime(10, 00, 0, 12, 25, $_POST['jaar']);
			$dienst->eind	= mktime(11, 30, 0, 12, 25, $_POST['jaar']);
			$dienst->opmerking = '1ste Kerstdag';
			$dienst->save();
			toLog('Kerst aangemaakt', 'debug');
		}
	}
	
	# Oudjaars-dienst inplannen
	if(isset($_POST['oudjaar'])) {		
		# Op welke dag valt 31 december
		$marker = date("N", mktime(0, 0, 1, 12, 31, $_POST['jaar']));
		
		# Als Oud & Nieuw op zondag valt, hoeft er geen extra dienst toegevoegd te worden
		if($marker < 7) {
			$dienst = new Kerkdienst();
			$dienst->start	= mktime(19, 30, 0, 12, 31, $_POST['jaar']);
			$dienst->eind	= mktime(21, 30, 0, 12, 31, $_POST['jaar']);
			$dienst->opmerking = 'Oudjaar';
			$dienst->save();
			toLog('Oudjaar aangemaakt', 'debug');
		}
	}
} else {
	$future = time()+(10*365*24*60*60);
	$diensten	= Kerkdienst::getDiensten(0,$future);
	$dienst		= new Kerkdienst(end($diensten));
	$offset		= 24*60*60;
	
	$Jaar	= getParam('Jaar', date("Y", $dienst->eind+$offset));
			
	$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'>Genereer diensten voor <select name='jaar'>";
	for($j=date("Y"); $j<=(date("Y")+10) ; $j++) {
		$text[] = "	<option value='$j'". ($j == $Jaar ? ' selected' : '') .">$j</option>";
	}
	$text[] = "	</select></td>";	
	$text[] = "</tr>";	
	$text[] = "<tr>";
	$text[] = "	<td><input type='checkbox' name='ochtend' value='1' checked> Ochtenddiensten</td>";
	$text[] = "	<td><input type='checkbox' name='middag' value='1' checked> Middagdiensten</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td><input type='checkbox' name='biddag' value='1'> Biddag</td>";
	$text[] = "	<td><input type='checkbox' name='dankdag' value='1'> Dankdag</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td><input type='checkbox' name='kerst' value='1'> Kerst</td>";
	$text[] = "	<td><input type='checkbox' name='oudjaar' value='1'> Oudjaar</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td><input type='checkbox' name='vrijdag' value='1'> Goede Vrijdag</td>";
	$text[] = "	<td><input type='checkbox' name='hemelvaart' value='1'> Hemelvaart</td>";
	$text[] = "</tr>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td><input type='checkbox' name='omschrijving' value='1'> Omschrijvingen</td>";
	$text[] = "	<td>&nbsp;</td>";
	$text[] = "</tr>";
	$text[] = "</table>";
	$text[] = "<p class='after_table'><input type='submit' name='save' value='Genereer'></p>";	
	$text[] = "</form>";
	
	# Pasen en Pinksteren rekenen is een ramp; Die moeten dus even handmatig gecontroleerd worden
	# Pasen (zoek de eerste volle maan op of na 21 maart | zoek de eerstvolgende zondag na deze volle maan. Voila, je hebt Eerste Paasdag te pakken)
	# Pinksteren (Eerste Pinksterdag is dus tien dagen na Hemelvaart)	
}

# Dit kan pas doorlopen worden als hierboven de diensten zijn ingevoerd
# Vandaar deze wat gekunstelde oplossing door eerst diensten toe te voegen en pas daarna de omschrijvingen
if(isset($_POST['omschrijving'])) {
	$details[] = array(18, 12, 24, 12, '4e Advent');
	$details[] = array(11, 12, 17, 12, '3e Advent');
	$details[] = array(4, 12, 10, 12, '2e Advent');
	$details[] = array(27, 11, 3, 12, '1e Advent');
	$details[] = array(20, 11, 26, 11, 'Zondag Voleinding');
	
	# Alleen als de datum van Pasen bekend is kan Pasen en Pinksteren worden toegevoegd
	if(count($DataPasen) > 1) {
		$details[] = array($DataPasen['dag'], $DataPasen['maand'], $DataPasen['dag'], $DataPasen['maand'], 'Pasen');
		$details[] = array(($DataPasen['dag']+49), $DataPasen['maand'], ($DataPasen['dag']+49), $DataPasen['maand'], 'Pinksteren');
	}
			
	$details[] = array(8, 1, 14, 1, 'Heilig Avondmaal');
	$details[] = array(8, 3, 14, 3, 'Heilig Avondmaal');
	$details[] = array(8, 5, 14, 5, 'Heilig Avondmaal');
	$details[] = array(8, 7, 14, 7, 'Heilig Avondmaal');
	$details[] = array(8, 9, 14, 9, 'Heilig Avondmaal');
	$details[] = array(8, 11, 14, 11, 'Heilig Avondmaal');
	
	$details[] = array(25, 1, 31, 1, 'Doopzondag');
	$details[] = array(22, 2, 28, 2, 'Doopzondag');
	$details[] = array(25, 3, 31, 3, 'Doopzondag');
	$details[] = array(24, 4, 30, 4, 'Doopzondag');
	$details[] = array(25, 5, 31, 5, 'Doopzondag');
	$details[] = array(24, 6, 30, 6, 'Doopzondag');
	$details[] = array(25, 7, 31, 7, 'Doopzondag');
	$details[] = array(25, 8, 31, 8, 'Doopzondag');
	$details[] = array(24, 9, 30, 9, 'Doopzondag');
	$details[] = array(25, 10, 31, 10, 'Doopzondag');
	$details[] = array(24, 11, 30, 11, 'Doopzondag');
	#$details[] = array(25, 12, 31, 12, 'Doopzondag');
		
	$details[] = array(25, 12, 25, 12, '1ste Kerstdag');
	$details[] = array(31, 12, 31, 12, 'Oudjaar');
				
	foreach($details as $dag) {
		$startTijd = mktime(0, 0, 1, $dag[1], $dag[0], $_POST['jaar']);
		$eindTijd = mktime(23, 59, 59, $dag[3], $dag[2], $_POST['jaar']);
		
		#$diensten = getKerkdiensten($startTijd, $eindTijd);
		$diensten = Kerkdienst::getDiensten($startTijd, $eindTijd);
		
		foreach($diensten as $dienstID) {
			$dienst = new Kerkdienst($dienstID);
			
			# Alleen als een dienst op zondag valt de omschrijving toevoegen
			# Als Kerst en Oud & Nieuw niet op een zondag valt, is hierboven de dienst al toegevoegd 
			# en valt hij hier terecht eruit
			if(date("N", $dienst->start) == 7) {				
				if($dienst->opmerking != '') {
					$dienst->opmerking .' - '. $dag[4];
				} else {
					$dienst->opmerking = $dag[4];
				}				
				
				$dienst->save();
			}
		}
	}
	toLog('Omschrijvingen toegevoegd', 'debug');
}

echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();


?>