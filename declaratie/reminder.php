<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../Classes/Declaratie.php');
include_once('../Classes/Member.php');
include_once('../Classes/KKDMailer.php');

/**
 * Doorloop de array met clustercoordinatoren.
 * De key is het ID van het cluster, de value het ID van de Cluster-coordinator.
 * 
 * Vervolgens vragen we van elk cluster op hoeveel openstaande declaraties er zijn voor dat cluster.
 * Deze declaraties worden vervolgens naar de CluCo gemaild.
 */
foreach($clusterCoordinatoren as $cluster => $clucoID) {	
	$declaraties = Declaratie::getDeclaraties(3, $cluster);

	if(count($declaraties) > 0) {		
		$cluco		= new Member($clucoID);

		$reminderMail = array();
		$reminderMail[] = "Beste ". $cluco->getName(1).",<br>";
		$reminderMail[] = "<br>";
		$reminderMail[] = "De volgende ". (count($declaraties) == 1 ? 'declaratie wacht' : 'declaraties wachten')." op een reactie van jouw :<br>";
		$reminderMail[] = "<ul>";

		foreach($declaraties as $hash) {
			$declaratie = new Declaratie($hash);
			$indiener	= new Member($declaratie->gebruiker);

			$onderwerpen = array();
			if(count($declaratie->overigeKosten) > 0)	$onderwerpen = array_merge($onderwerpen, array_keys($declaratie->overigeKosten));
			if($declaratie->reiskosten > 0)				$onderwerpen = array_merge($onderwerpen, array('reiskosten'));

			$reminderMail[] = "<li>De declaratie van ". $indiener->getName(5)." voor <i>". makeOpsomming($onderwerpen, '</i>, <i>', '</i> en <i>') ."</i> ter waarde van ". formatPrice($declaratie->totaal) ."</li>\n";

		}
		$reminderMail[] = "</ul>";
		$reminderMail[] = "<br>";
		if(count($declaraties) == 1) {
			$reminderMail[] = "Klik <a href='". $ScriptURL ."declaratie/cluco.php?hash=". $cluco->hash_long ."&key=". $declaratie->hash ."'>hier</a> om direct naar de declaratie te gaan.<br>";
		} else {
			$reminderMail[] = "Klik <a href='". $ScriptURL ."declaratie/cluco.php?reset'>hier</a> (inloggen vereist) om direct naar de openstaande declaraties te gaan.<br>";
		}

		$mail = new KKDMailer();
		$mail->aan = $clucoID;
		$mail->Subject	= count($declaraties) ." openstaande ". (count($declaraties) == 1 ? 'declaratie wacht' : 'declaraties wachten')." op reactie";	
		$mail->Body		= implode("\n", $reminderMail);
		if(!$productieOmgeving)	$mail->testen	= true;

		if($mail->sendMail()) {
			toLog("Reminder-mail aan Cluco gestuurd", '', $clucoID);
		} else {
			toLog("Problemen met reminder-mail aan Cluco", 'error', $clucoID);
		}	
	} else {
		toLog('Geen openstaande declaraties voor Cluco', '', $clucoID);
	}
}


/**
 * De penningmeester is iets ingewikkelder omdat er 2 penningmeesters zijn (1 van J&G en 1 voor de rest)
 * 
 * Daarom vragen we eerst alle openstaande declaraties voor de penningmeester op.
 * En maken er 2 lijsten van, key = 1 van J&G en key = 0 voor de rest)
 * Vervolgens lopen wij deze 2 lijsten door om te zien of er een mail naar 1 of beide penningmeesters verstuurd moet worden.
 */
$declaraties = Declaratie::getDeclaraties(2);

if(count($declaraties) > 0) {
	$list = array();

	foreach($declaraties as $hash) {
		$declaratie = new Declaratie($hash);
		$indiener	= new Member($declaratie->gebruiker);
		$cluster	= $declaratie->cluster;

		$onderwerpen = array();
		if(count($declaratie->overigeKosten) > 0)	$onderwerpen = array_merge($onderwerpen, array_keys($declaratie->overigeKosten));
		if($declaratie->reiskosten > 0)				$onderwerpen = array_merge($onderwerpen, array('reiskosten'));

		if($cluster == 2) {
			$list[1][] = "<li>De declaratie van ". $indiener->getName(5)." voor <i>". makeOpsomming($onderwerpen, '</i>, <i>', '</i> en <i>') ."</i> ter waarde van ". formatPrice($declaratie->totaal) ."</li>\n";
		} else {
			$list[0][] = "<li>De declaratie van ". $indiener->getName(5)." voor <i>". makeOpsomming($onderwerpen, '</i>, <i>', '</i> en <i>') ."</i> ter waarde van ". formatPrice($declaratie->totaal) ."</li>\n";
		}
	}

	foreach($list as $id => $lijst) {
		if(count($lijst) > 0) {
			$reminderMail = array();
			$reminderMail[] = "Beste Penningmeester,<br>";
			$reminderMail[] = "<br>";
			$reminderMail[] = "De volgende ". (count($lijst) == 1 ? 'declaratie wacht' : 'declaraties wachten')." op een reactie van jouw :<br>";
			$reminderMail[] = "<ul>";
			$reminderMail[] = implode("\n", $lijst);
			$reminderMail[] = "</ul>";
			$reminderMail[] = "<br>";
			if(count($lijst) == 1) {
				$reminderMail[] = "Klik <a href='". $ScriptURL ."declaratie/penningmeester.php?key=". $declaratie->hash ."'>hier</a> om direct naar de declaratie te gaan.<br>";
			} else {
				$reminderMail[] = "Klik <a href='". $ScriptURL ."declaratie/penningmeester.php?reset'>hier</a> (inloggen vereist) om direct naar de openstaande declaraties te gaan.<br>";
			}

			$mail = new KKDMailer();
			if($id == 1) {
				$mail->ontvangers[] = array($penningmeesterJGAddress, $penningmeesterJGNaam);
			} else {
				$mail->ontvangers[] = array($declaratieReplyAddress, $declaratieReplyName);				
			}			
			$mail->Subject	= count($lijst) ." openstaande ". (count($lijst) == 1 ? 'declaratie wacht' : 'declaraties wachten')." op reactie";	
			$mail->Body		= implode("\n", $reminderMail);
			if(!$productieOmgeving)	$mail->testen	= true;
			
			if($mail->sendMail()) {
				toLog("Reminder-mail aan penningmeester gestuurd");
			} else {
				toLog("Problemen met reminder-mail aan penningmeester", 'error');
			}			
		}
	}
} else {
	toLog('Geen openstaande declaraties voor penningmeester');
}
		

# Pagina tonen
$pageTitle = 'Declaratie';
include_once('../include/HTML_TopBottom.php');

echo showCSSHeader();
echo '<div class="content_vert_kolom">'.NL;

foreach($blocks as $block) {
	echo "<div class='content_block'>". implode("<br>".NL, $page) ."</div>".NL;
}

echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>
