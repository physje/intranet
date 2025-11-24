<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/DeclaratieVoorganger.php');
include_once('../Classes/Kerkdienst.php');
include_once('../Classes/Voorganger.php');

# Start de sessie en kijk of er een declaratie-object in de sessie staat
# Laad die dan
session_start();
if(!isset($_SESSION['declaratie'])) {	
	$declaratie = new Declaratie();
	$declaratie->type = 'voorganger';
	$_SESSION['declaratie'] = $declaratie;
}
$declaratie = $_SESSION['declaratie'];

if(isset($_REQUEST['hash']))		$declaratie->hash = urldecode($_REQUEST['hash']);
if(isset($_REQUEST['d']))			$declaratie->dienst = $_REQUEST['d'];
if(isset($_REQUEST['v']))			$declaratie->voorganger = $_REQUEST['v'];

if(isset($declaratie->hash) && $declaratie->hash != '') {
	$dienst		= new Kerkdienst($declaratie->dienst);
	$voorganger	= new Voorganger($declaratie->voorganger);

	# De hash klopt
	if(password_verify($dienst->dienst.'$'.$randomCodeDeclaratie.'$'.$voorganger->id,$declaratie->hash)) {			
		$dagdeel  = formatDagdeel($dienst->start);
				
		if(isset($_POST['zeker_weten'])) {
			$page[] = "Uw declaratie staat geregistreed als 'afgezien' en is daarmee afgehandeld.";
			$dienst->declaratieStatus = 9;
			$dienst->save();

			toLog('Afgezien van declaratie door '. $voorganger->getName().' voor dienst op '. date('d-M-Y', $dienst->start));
		} elseif(isset($_POST['toch_niet'])) {
			$declaratieLink = generateDeclaratieLink($dienst->dienst, $voorganger->id);
						
			$page[] = "U ziet <b>niet</b> af van declaratie<br>";
			$page[] = "Klik <a href='$declaratieLink'>hier</a> om door te gaan naar de declaratie-omgeving";
			toLog('Toch niet afgezien van declaratie door '. $voorganger->getName().' voor dienst op '. date('d-M-Y', $dienst->start), 'debug');
		} else {
			$page[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";			
			$page[] = "<table border=0>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='2'><b>Afzien declaratie</b></td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='2'>&nbsp;</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='2'>U staat op het punt af te zien van het indienen van een declaratie voor de $dagdeel van ". date('d-M-Y', $dienst->start).".<br>Weet u dat zeker?</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td colspan='2'>&nbsp;</td>";
			$page[] = "	</tr>";
			$page[] = "	<tr>";
			$page[] = "		<td align='left'><input type='submit' name='toch_niet' value='Nee, toch wel declaratie indienen'></td>";
			$page[] = "		<td align='right'><input type='submit' name='zeker_weten' value='Ja, heel zeker'>";
			$page[] = "	</tr>";
			$page[] = "</table>";			
			$page[] = "</form>";
		}		
	} else {
		# Direct-link om te declareren is niet correct
		$page[] = "Deze link is niet correct.<br><br>";
		$page[] = "Neem contact op met <a href='mailto:$ScriptMailAdress'>de webmaster</a>.";
	}
} else {
	$page[] = "Deze pagina is op incorrecte wijze aangeroepen";
}

# Pagina tonen
echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $page).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

# Sla de declaratie-gegevens op in de sessie
$_SESSION['declaratie'] = $declaratie;
?>
