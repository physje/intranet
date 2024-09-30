<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$online = true;
$debug = false;

# Rooster inlezen
if($online) {
	$roosterURL = 'https://docs.google.com/spreadsheets/d/1ZTJ9lzhxNk5PDQCBcLVAwjF-76fVytydW5v6pFTf-yk/pub?output=csv';
	$contents = file_get_contents($roosterURL);
	
	if($debug) {
		$file = fopen('dump.txt', 'w+');
		fwrite($file, $contents);
		fclose($file);
	}
} else {
	$file = fopen('dump.txt', 'r+');
	$contents = fread($file, filesize('dump.txt'));
	fclose($file);
}
$regels = explode("\n", $contents);
$aantal = count($regels);

# Ouderlingen inlezen
$ouderlingen = getGroupMembers(8);
foreach($ouderlingen as $lid) {
	$namenOud[$lid] = makeName($lid, 5);	
}

# Diakenen inlezen
$diakenen = getGroupMembers(9);
foreach($diakenen as $lid) {
	$namenDiak[$lid] = makeName($lid, 5);		
}

for($r=1 ; $r < $aantal ; $r++) {
	$Oud = $Diak = array();
	$regel	= $regels[$r];
	$velden	= explode(",", $regel);
	$a			= count($velden);
	
	$datum		= trim($velden[0]);
		
	for($o=15;$o>12;$o--) {
		$Oud[]		= trim($velden[($a-$o)]);
	}
		
	for($d=8;$d>0;$d--) {
		$Diak[]	= trim($velden[($a-$d)]);
	}
	
	# Als datum bestaat
	if($datum != '') {
		$datumDelen = explode('-', $datum);
		$start = mktime(0,0,0,$datumDelen[1],$datumDelen[0],$datumDelen[2]);
		$eind = mktime(23,59,59,$datumDelen[1],$datumDelen[0],$datumDelen[2]);
		
		if($eind > time()) {
			$diensten = getKerkdiensten($start, $eind);
			$dienstID = $diensten[0];
			
			$vullingOvD	= getRoosterVulling(7, $dienstID);
			$vullingDvD	= getRoosterVulling(10, $dienstID);
			$vullingO		= getRoosterVulling(8, $dienstID);
			$vullingD		= getRoosterVulling(9, $dienstID);
			$details		= getKerkdienstDetails($dienstID);
			
			echo '<b>'. $datum .' ('. $dienstID .')</b><br>';
					
			# Alle ouderlingen doorlopen
			if($Oud[0] != '' AND $dienstID != 0) {
				$ouderlingID = $OvDID = array();
							
				foreach($Oud as $plek => $ouderling) {
					# Met de komst van duo-banen moet er even eea opgeknipt worden
					# Ik ga er vanuit dat de duo banen genoteerd zijn als VOORNAAM MAN en VOORNAAM VROUW ACHTERNAAM

					# Checken of er een duo-naam is
					if(strpos($ouderling, ' en ')) {
						$naamDelen = explode(' en ', $ouderling);
						$delenVrouw = explode(' ', $naamDelen[1]);
						$voornaamMan = $naamDelen[0];
						$voornaamVrouw = $delenVrouw[0];
						$achternaam = $delenVrouw[1];
						
						$id_man = array_search_closest($voornaamMan.' '.$achternaam, $namenOud);
						$id_vrouw = array_search_closest($voornaamVrouw.' '.$achternaam, $namenOud);
						
						if($id_man != 0 AND $id_vrouw != 0) {
							echo $ouderling .' -> '. $namenOud[$id_man] .' ('. $id_man .') & '. $namenOud[$id_vrouw] .' ('. $id_vrouw .')<br>';
							
							if($plek == 0) {
								$OvDID[] = $id_man;
								$OvDID[] = $id_vrouw;								
							} else {
								$ouderlingID[] = $id_man;
								$ouderlingID[] = $id_vrouw;
							}
						}						
					} else {
						$id = array_search_closest($ouderling, $namenOud);
						if($ouderling!= '' AND $id != 0) {
							echo $ouderling .' -> '. $namenOud[$id] .' ('. $id .')<br>';
							
							if($plek == 0) {
								$OvDID[] = $id;
							} else {
								$ouderlingID[] = $id;
							}
						}
					}
				}
				echo '<br>';
				
				# Oude data verwijderen
				removeFromRooster(7, $dienstID);
				removeFromRooster(8, $dienstID);
				
				# Nieuwe data OvD inlezen
				foreach($OvDID as $id => $ouderling) {
					add2Rooster(7, $dienstID, $ouderling, $id);
					if(count($vullingOvD) > 0 AND !in_array($ouderling, $vullingOvD)) {					
						toLog('info', $ouderling, 'Wijziging ouderling van dienst '. date("d-m", $details['start']) .': '. makeName($vullingOvD[$id], 5) .' -> '. makeName($ouderling, 5));
					} else {							
						toLog('debug', $ouderling, 'Ouderling van dienst '. date("d-m", $details['start']) .': '. makeName($ouderling, 5));
					}
				}
				
				# Nieuwe data ouderlingen inlezen
				foreach($ouderlingID as $id => $ouderling) {
					add2Rooster(8, $dienstID, $ouderling, $id);
					if(count($vullingO) > 0 AND !in_array($ouderling, $vullingO)) {
						toLog('info', $ouderling, 'Wijziging ouderling '. date("d-m", $details['start']) .': '. makeName($vullingO[$id], 5) .' -> '. makeName($ouderling, 5));
					} else {
						toLog('debug', $ouderling, 'Ouderling '. date("d-m", $details['start']) .': '. makeName($ouderling, 5));
					}
				}		
			}
		
			# Alle diakenen doorlopen		
			if($Diak[0] != '') {
				$diakenID = array();
				
				foreach($Diak as $diaken) {
					$id = array_search_closest ($diaken, $namenDiak);
					if($diaken != '' AND $id != 0) {
						echo $diaken .' -> '. $namenDiak[$id] .' ('. $id .')<br>';
						$diakenID[] = $id;
					}
				}
				echo '<br>';
				
				# Oude data verwijderen
				removeFromRooster(9, $dienstID);
				removeFromRooster(10, $dienstID);
				
				# Nieuwe data inlezen
				foreach($diakenID as $id => $diaken) {
					$id_old = $id-1;
					if($id == 0) {
						add2Rooster(10, $dienstID, $diaken, $id);
						if($vullingDvD[0] != '' AND $diaken != $vullingDvD[0]) {
							toLog('info', $diaken, 'Wijziging diaken van dienst '. date("d-m", $details['start']) .': '. makeName($vullingDvD[0], 5) .' -> '. makeName($diaken, 5));
						} else {
							toLog('debug', $diaken, 'Diaken van dienst '. date("d-m", $details['start']) .': '. makeName($diaken, 5));
						}
					} else {
						add2Rooster(9, $dienstID, $diaken, $id);
						if($vullingD[$id_old] != '' AND $diaken != $vullingD[$id_old]) {
							toLog('info', $diaken, 'Wijziging diaken '. date("d-m", $details['start']) .': '. makeName($vullingD[$id_old], 5) .' -> '. makeName($diaken, 5));
						} else {
							toLog('debug', $diaken, 'Diaken '. date("d-m", $details['start']) .': '. makeName($diaken, 5));
						}
					}
				}			
			}
		}		
	}
}
toLog('info', '', 'Ambtsdragers opnieuw ingelezen');
?>
