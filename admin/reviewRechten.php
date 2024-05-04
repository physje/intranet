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
	
	$text[] = "<a id='groep_$groep'></a><h2>". $data['naam'] ." ($groep)</h2>\n";
	$text[] = "<table>\n";
	$text[] = "<tr>\n";
	$text[] = "	<td valign='top'>Beheer: <a href='#groep_". $data['beheer'] ."'>". $beheerData['naam'] ."</a>\n";
	$text[] = "	</td>\n";
	$text[] = "	<td valign='top' rowspan='2'>\n";
	$text[] = "	Leden: <ul>";
	
	foreach($leden as $lid) {
		$text[] = "	<li>". makeName($lid, 5) ."</li>\n";
	}
	
	$text[] = "	</ul>\n";
	$text[] = "	</td>\n";
	$text[] = "</tr>\n";
	$text[] = "<tr>\n";
	$text[] = "	<td valign='bottom'>\n";
	$text[] = "	<a href='../editGroup.php?groep=$groep'>Bewerk</a> | <a href='editGroepen.php?id=$groep'>Admin</a>\n";		
	$text[] = "	</td>\n";
	$text[] = "</tr>\n";	
	$text[] = "</table>\n";
	$text[] = "<br>\n";
}

echo $HTMLHeader;
echo implode("\n", $text);
echo $HTMLFooter;