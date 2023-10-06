<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$sql_dienst = "SELECT $DienstID FROM $TableDiensten WHERE $DienstEind > ". time() ." ORDER BY $DienstEind ASC LIMIT 0,3";
$result_dienst = mysqli_query($db, $sql_dienst);
if($row_dienst = mysqli_fetch_array($result_dienst)) {		
	$HTML[] = "<div class='row'>";
	
	do {
		$dienst = $row_dienst[$DienstID];		
		$data_dienst = getKerkdienstDetails($dienst);
		
		$dag				= date('d', $data_dienst['start']);
		$maand			= date('M', $data_dienst['start']);
		$begintijd	=	date('H:i', $data_dienst['start']);
		$eindtijd		=	date('H:i', $data_dienst['eind']);		
		$locatie		= 'Koningskerk Deventer';
		
		if(date("H", $data_dienst['start']) < 12) {
			$titel = 'Ochtenddienst';
		} elseif(date("H", $data_dienst['start']) < 18) {
			$titel = 'Middagdienst';
		} else {
			$titel = 'Avonddienst';
		}
		
		if($data_dienst['voorganger'] != '') {
			$titel = $titel .' '. $data_dienst['voorganger'];
		}
		
		# Samenkomst
		$HTML[] = "<div class='col-md-4 col-sm-4'>";
		$HTML[] = "	<article class='mec-past-event mec-event-article mec-clear' itemscope=''>";
		$HTML[] = "	<div class='mec-event-date mec-bg-color-hover mec-border-color-hover mec-color'>";
		$HTML[] = "		<span>$dag</span>$maand</div>";
		$HTML[] = "	<div class='event-detail-wrap'>";
		$HTML[] = "		<h4 class='mec-event-title'>";
		#echo "<a class='mec-color-hover' data-event-id='48176' href='https://www.koningskerkdeventer.nl/events/samenkomst-koningskerk-deventer/' target='_self' rel='noopener'>Samenkomst</a>";
		$HTML[] = $titel;
		#echo "<span class='mec-repeating-label'>Wekelijks</span>";
		$HTML[] = "		<span class='event-color' style='background: #8224e3'>";
		$HTML[] = "		</span>";
		$HTML[] = "		</h4>";
		$HTML[] = "		<div class='mec-time-details'>";
		$HTML[] = "			<span class='mec-start-time'>$begintijd</span> - <span class='mec-end-time'>$eindtijd</span>";
		$HTML[] = "		</div>";
		$HTML[] = "		<div class='mec-event-detail'>";
		$HTML[] = "			<div class='mec-event-loc-place'>$locatie</div>";
		$HTML[] = "		</div>";
		$HTML[] = "	</div>";
		$HTML[] = "	</article>";
		$HTML[] = "</div>";
	} while($row_dienst = mysqli_fetch_array($result_dienst));
	$HTML[] = "</div>";
}

echo implode("\n", $HTML);

## Open kerk
#echo "<div class='col-md-4 col-sm-4'>";
#echo "	<article class='mec-past-event mec-event-article mec-clear' itemscope=''>";
#echo "		<div class='mec-event-date mec-bg-color-hover mec-border-color-hover mec-color'>";
#echo "			<span>06</span>okt";
#echo "		</div>";
#echo "		<div class='event-detail-wrap'>";
#echo "		<h4 class='mec-event-title'>";
##echo "<a class='mec-color-hover' data-event-id='49233' href='https://www.koningskerkdeventer.nl/events/openkerk/' target='_self' rel='noopener'>Open kerk</a>";
#echo "Open Kerk"
#echo "<span class='mec-repeating-label'>Elke doordeweekse dag</span>";
#echo "<span class='event-color' style='background: #e14d43'>";
#echo "</span>";
#echo "</h4>";
#echo "<div class='mec-event-detail'>";
#echo "<div class='mec-event-loc-place'>Koningskerk Deventer</div>";
#echo "</div>";
#echo "</div>";
#echo "</article>";
#echo "</div>";


## Warme kamer
#echo "<div class='col-md-4 col-sm-4'>";
#echo "<article class='mec-past-event mec-event-article mec-clear' itemscope=''> <div class='mec-event-date mec-bg-color-hover mec-border-color-hover mec-color'>";
#echo "<span>11</span>okt</div>";
#echo "<div class='event-detail-wrap'>";
#echo "<h4 class='mec-event-title'>";
#echo "<a class='mec-color-hover' data-event-id='49293' href='https://www.koningskerkdeventer.nl/events/warme-kamer/' target='_self' rel='noopener'>Warme Kamer</a>";
#echo "<span class='mec-repeating-label'>Wekelijks</span>";
#echo "<span class='event-color' style='background: #a3b745'>";
#echo "</span>";
#echo "</h4>";
#echo "<div class='mec-time-details'>";
#echo "<span class='mec-start-time'>13:30</span> - <span class='mec-end-time'>16:00</span>";
#echo "</div>";
#echo "<div class='mec-event-detail'>";
#echo "<div class='mec-event-loc-place'>";
#echo "</div>";
#echo "</div>";
#echo "</div>";
#echo "</article>";
#echo "</div>";

