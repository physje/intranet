<?php
include_once('../include/functions.php');
include_once('../include/EB_functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
$db = connect_db();

$stap = 5;

for($i=0 ; $i<4 ; $i++) {
	$step[] = "$EBDeclaratieLastAction BETWEEN ". mktime(0, 0, 0, date("m"), date("d")-(($i+1)*$stap)) ." AND ". mktime(23, 59, 59, date("m"), date("d")-(($i+1)*$stap));
}

$sql		= "SELECT * FROM $TableEBDeclaratie WHERE ($EBDeclaratieStatus = 3 OR $EBDeclaratieStatus = 4) AND (". implode(' OR ', $step) .")";
$result	= mysqli_query($db, $sql);

if($row = mysqli_fetch_array($result)) {
	do {
		$indiener 	= $row[$EBDeclaratieIndiener];
		$uniqueKey	= $row[$EBDeclaratieHash];
		$cluster		= $row[$EBDeclaratieCluster];
		$totoaal		= $row[$EBDeclaratieTotaal];
		$tijd				= $row[$EBDeclaratieTijd];
		$status			= $row[$EBDeclaratieStatus];
		
		$JSON				= json_decode($row[$EBDeclaratieDeclaratie], true);
		
		if($status == 3) {
			$cluco			= $clusterCoordinatoren[$cluster];		
			$ToAddress	= getMailAdres($cluco);
			$ToName			= makeName($cluco, 5);
		} else {
			$ToAddress	= $declaratieReplyAddress;
			$ToName			= $declaratieReplyName;
		}
		
		$onderwerpen = array();
		
		if(isset($JSON['overig']))			$onderwerpen = array_merge($onderwerpen, $JSON['overig']);		
		if(isset($JSON['reiskosten']))	$onderwerpen = array_merge($onderwerpen, array('reiskosten'));
				
		if($status == 3) {
			$reminderMail[] = "Beste ". makeName($cluco, 1).",<br>";
		} else {
			$reminderMail[] = "Beste Penningmeester,<br>";
		}
		$reminderMail[] = "<br>";
		$reminderMail[] = "De declaratie van ". makeName($indiener, 5) .' van '. time2str('%A %e %B') .' wacht op een reactie van jou.<br>';
		$reminderMail[] = "<br>";
		$reminderMail[] = "Het betreft een declaratie van <i>". makeOpsomming($onderwerpen, '</i>, <i>', '</i> en <i>') ."</i> ter waarde van ". formatPrice($totoaal)."<br>";
		$reminderMail[] = "<br>";
		if($status == 3) {
			$reminderMail[] = "Klik <a href='". $ScriptURL ."declaratie/cluco.php?key=$uniqueKey'>hier</a> (inloggen vereist) om direct naar de declaratie te gaan.<br>";
			$reminderMail[] = "Of klik <a href='". $ScriptURL ."declaratie/cluco.php'>hier</a> (ook inloggen vereist) om direct naar alle openstaande declaraties te gaan.<br>";
		} else {
			$reminderMail[] = "Klik <a href='". $ScriptURL ."declaratie/penningmeester.php?key=$uniqueKey'>hier</a> (inloggen vereist) om direct naar de declaratie te gaan.<br>";
			$reminderMail[] = "Of klik <a href='". $ScriptURL ."declaratie/penningmeester.php'>hier</a> (ook inloggen vereist) om direct naar alle openstaande declaraties te gaan.<br>";
		}	
  	
		$param['to'][]					= array($ToAddress, $ToName);
		$param['subject'] 			= "Declaratie ". makeName($indiener, 5) ." wacht op reactie";	
		$param['message'] 			= implode("\n", $reminderMail);
				
		if(!sendMail_new($param)) {
			if($status == 3) {
				toLog('error', '', $cluco, "Problemen met versturen reminder-mail aan cluco (". makeName($cluco, 5).") voor [$uniqueKey]");
				$page[] = "Er zijn problemen met het versturen van de reminder-mail naar de clustercoordinator.";
			} else {
				toLog('error', '', '', "Problemen met versturen reminder-mail aan penningmeester voor [$uniqueKey]");
				$page[] = "Er zijn problemen met het versturen van de reminder-mail naar de penningmeester.";
			}
		} else {
			if($status == 3) {
				toLog('info', '', '', "Reminder-mail aan cluco (". makeName($cluco, 5).") gestuurd voor [$uniqueKey]");
				$page[] = "Reminder-mail aan cluco (". makeName($cluco, 5).") verstuurd";
			} else {
				toLog('info', '', '', "Reminder-mail aan penningmeester gestuurd voor [$uniqueKey]");
				$page[] = "Reminder-mail aan penningmeester verstuurd";
			}
		}		
	} while($row = mysqli_fetch_array($result));
} else {
	$page[] = "Geen reminders te versturen";
}

# Pagina tonen
$pageTitle = 'Declaratie';
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '	<td valign="top">'. showBlock(implode("\n", $page), 100). '</td>'.NL;
echo '	<td valign="top" width="25%">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;	

?>
