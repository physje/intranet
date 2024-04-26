<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../include/HTML_HeaderFooter.php');

$db = connect_db();

$groepen = getAllGroups();

foreach($groepen as $groep) {	
	$data = getGroupDetails($groep);
	$leden = getGroupMembers($groep);
	$beheerData = getGroupDetails($data['beheer']);
	
	$text[] = "<a id='groep_$groep'></a><h2>". $data['naam'] ."</h2>\n";	
	$text[] = "ID: $groep<br>\n";	
	$text[] = "Beheer: <a href='#groep_". $data['beheer'] ."'>". $beheerData['naam'] ."</a><br>\n";	
	$text[] = "Leden: <ul>";
	
	foreach($leden as $lid) {
		$text[] = "<li>". makeName($lid, 5) ."</li>\n";
	}
	
	$text[] = "</ul>\n";
	$text[] = "<a href='../editGroup.php?groep=$groep'>Bewerk</a><br>\n";		
	#$text[] = "<br>\n";
}

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;