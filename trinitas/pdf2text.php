<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

# http://stackoverflow.com/questions/4660871/mysql-select-all-items-from-table-a-if-not-exist-in-table-b
$sql = "SELECT $ArchiefID FROM $TableArchief a WHERE NOT EXISTS (SELECT * FROM $TablePlainText p WHERE a.$ArchiefID = p.$PlainTextID) LIMIT 0,5";
$result = mysqli_query($db, $sql);

if($row = mysqli_fetch_array($result)) {
	do {
		$id = $row[$ArchiefID];
		$data = getTrinitasData($id);
		$text = ExtractTextFromPdf($ArchiveDir.'/'.$data['filename']);
		
		$sql_insert = "INSERT INTO $TablePlainText ($PlainTextID, $PlainTextText) VALUES ('$id', '". addslashes($text) ."')";
		//echo $sql_insert;
		if(mysqli_query($db, $sql_insert)) echo $data['jaar'] .'-'. $data['nr'] ." gedaan<br>\n";
		
	} while($row = mysqli_fetch_array($result));
} else {
	echo "alles is al ingelezen";
}

?> 