<?php
include_once('config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();

$startDag = mktime(0,0,0,date('n'),(date('j')+1));
$eindDag = $startDag + (24*60*60);

$sql		= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterTijd BETWEEN $startDag AND $eindDag GROUP BY $OKRoosterPersoon";
$result	= mysqli_query($db, $sql);

# Niet alle dagen is er een mail te versturen
if($row		= mysqli_fetch_array($result)) {
	do {
		$mail = $parameter = array();
		
		$persoon = $row[$OKRoosterPersoon];
		
		# Vraag de tijden voor deze persoon op
		$sql_tijden			= "SELECT * FROM $TableOpenKerkRooster WHERE $OKRoosterTijd BETWEEN $startDag AND $eindDag AND $OKRoosterPersoon = '". $persoon ."'";
		$result_tijden	= mysqli_query($db, $sql_tijden);
		$row_tijden			= mysqli_fetch_array($result_tijden);
		
		if(is_numeric($persoon)) {
			$mail[] = "Beste ". makeName($persoon, 1) .",<br>";
			$parameter['to'][] = array($persoon);
		} else {
			$mail[] = "Beste ". $extern[$persoon]['voornaam'] .",<br>";
			$parameter['to'][] = array($extern[$persoon]['mail'], $extern[$persoon]['naam']);
		}
		$mail[] = "<br>";
		$mail[] = "dit is een reminder dat je voor morgen op het rooster staat als gastheer/gastvrouw voor Open Kerk<br>";
		$mail[] = "Je bent op de volgende tijden ingedeeld :";
		$mail[] = "<ul>";
		
		do {
			$opmerking = '';
			$startTijd = $row_tijden[$OKRoosterTijd];
			$eindTijd = $startTijd + (60*60);
			
			$sql_opmerking = "SELECT * FROM $TableOpenKerkOpmerking WHERE $OKOpmerkingTijd = ". $startTijd;
			$result_opmerking	= mysqli_query($db, $sql_opmerking);
			if($row_opmerking = mysqli_fetch_array($result_opmerking)) {
				$opmerking = $row_opmerking[$OKOpmerkingOpmerking];
			}		
						
			$mail[] = "<li>". time2str("%a %d %b %H:%M", $startTijd) .'-'. time2str("%H:%M", $eindTijd) .($opmerking != '' ? " (<i>$opmerking</i>)" : '') .'</li>';
			
		} while($row_tijden = mysqli_fetch_array($result_tijden));
		
		$mail[] = "</ul>";
		
		//$parameter['to'][] = array(984285);
		$parameter['subject']				= 'Remindermail';
		$parameter['message'] 			= implode("\n", $mail);
		$parameter['from']					= 'maartendejonge55@gmail.com';
		$parameter['fromName']			= 'Maarten de Jonge';
		
		if(sendMail_new($parameter)) {
			echo 'Goed';
		} else {
			echo 'Fout';
		}
		
	} while($row = mysqli_fetch_array($result));
}

?>