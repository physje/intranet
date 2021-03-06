<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

$sql = "CREATE TABLE IF NOT EXISTS $TableConfig (";
$sql .= "  $ConfigID int(3) NOT NULL AUTO_INCREMENT,";
$sql .= "  $ConfigName text NOT NULL,";
$sql .= "  $ConfigKey text NOT NULL,";
$sql .= "  $ConfigValue text NOT NULL,";
$sql .= "  $ConfigOpmerking text NOT NULL,";
$sql .= "  $ConfigAdded int(11) NOT NULL,";
$sql .= "  UNIQUE KEY $ConfigID ($ConfigID)";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=latin1;";
mysqli_query($db, $sql);

$sql = "INSERT INTO $TableConfig ($ConfigID, $ConfigName, $ConfigKey, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES";
$sql .= "(5, 'allowedIP', '0', '127.0.0.1', 'IP-adressen+die+scripts+mogen+runnen', ". time() ."),";
$sql .= "(6, 'ScriptSever', '', 'http%3A%2F%2Flocalhost', '', ". time() ."),";
$sql .= "(7, 'declaratieReplyAddress', '', 'mailadres', '', ". time() ."),";
$sql .= "(10, 'ScriptURL', '', '%2F3GK%2Fintranet%2F', '', ". time() ."),";
$sql .= "(11, 'ScriptTitle', '', 'naam', '', ". time() ."),";
$sql .= "(12, 'ScriptMailAdress', '', 'mailadres', '', ". time() ."),";
$sql .= "(13, 'Version', '', '1.2.3', '', ". time() ."),";
$sql .= "(14, 'SubjectPrefix', '', '%5B3GK%5D+', '', ". time() ."),";
$sql .= "(15, 'scipioParams', 'Username', 'a', '', ". time() ."),";
$sql .= "(16, 'scipioParams', 'Password', 'b', '', ". time() ."),";
$sql .= "(17, 'scipioParams', 'Pincode', '1', '', ". time() ."),";
$sql .= "(18, 'ebUsername', '', 'a', '', ". time() ."),";
$sql .= "(19, 'ebSecurityCode1', '', 'b', '', ". time() ."),";
$sql .= "(20, 'ebSecurityCode2', '', 'c', '', ". time() ."),";
$sql .= "(21, 'randomCodeDeclaratie', '', 'a', '', ". time() ."),";
$sql .= "(22, 'declaratieReplyName', '', 'naam', '', ". time() ."),";
$sql .= "(23, 'locationIQkey', '', 'key', '', ". time() ."),";
$sql .= "(24, 'voorgangerReplyAddress', '', 'mail', '', ". time() ."),";
$sql .= "(25, 'voorgangerReplyName', '', 'naam', '', ". time() ."),";
$sql .= "(26, 'voorgangerCC', 'mail', 'naam', '', ". time() ."),";
$sql .= "(31, 'voorgangerBCC', 'mail', 'naam', '', ". time() ."),";
$sql .= "(32, 'MC_apikey', '', 'key', '', ". time() ."),";
$sql .= "(33, 'MC_listid', '', '123456', '', ". time() ."),";
$sql .= "(34, 'MC_server', '', '', '', ". time() ."),";
$sql .= "(35, 'tagWijk', 'A', '1', 'Mailchimp+tag', ". time() ."),";
$sql .= "(36, 'tagWijk', 'B', '2', 'Mailchimp+tag', ". time() ."),";
$sql .= "(37, 'tagWijk', 'C', '3', 'Mailchimp+tag', ". time() ."),";
$sql .= "(38, 'tagWijk', 'D', '4', 'Mailchimp+tag', ". time() ."),";
$sql .= "(39, 'tagWijk', 'E', '5', 'Mailchimp+tag', ". time() ."),";
$sql .= "(40, 'tagWijk', 'F', '6', 'Mailchimp+tag', ". time() ."),";
$sql .= "(41, 'tagWijk', 'G', '7', 'Mailchimp+tag', ". time() ."),";
$sql .= "(42, 'tagWijk', 'H', '8', 'Mailchimp+tag', ". time() ."),";
$sql .= "(43, 'tagWijk', 'I', '9', 'Mailchimp+tag', ". time() ."),";
$sql .= "(44, 'tagWijk', 'J', '10', 'Mailchimp+tag', ". time() ."),";
$sql .= "(84, 'tagRelatie', 'dochter', '1', 'Mailchimp+tag', ". time() ."),";
$sql .= "(85, 'tagRelatie', 'echtgenoot', '2', 'Mailchimp+tag', ". time() ."),";
$sql .= "(86, 'tagRelatie', 'echtgenote', '3', 'Mailchimp+tag', ". time() ."),";
$sql .= "(87, 'tagRelatie', 'gezinshoofd', '4', 'Mailchimp+tag', ". time() ."),";
$sql .= "(88, 'tagRelatie', 'levenspartner', '5', 'Mailchimp+tag', ". time() ."),";
$sql .= "(89, 'tagRelatie', 'zelfstandig', '6', 'Mailchimp+tag', ". time() ."),";
$sql .= "(90, 'tagRelatie', 'zoon', '7', 'Mailchimp+tag', ". time() ."),";
$sql .= "(91, 'tagScipio', '', '1', 'Mailchimp+tag', ". time() ."),";
$sql .= "(92, 'ID_google', '', 'a', 'Mailchimp+lijst', ". time() ."),";
$sql .= "(93, 'ID_wijkmails', '', 'b', 'Mailchimp+lijst', ". time() ."),";
$sql .= "(94, 'ID_gebed_dag', '', 'c', 'Mailchimp+lijst', ". time() ."),";
$sql .= "(95, 'ID_gebed_week', '', 'd', 'Mailchimp+lijst', ". time() ."),";
$sql .= "(96, 'ID_gebed_maand', '', 'e', 'Mailchimp+lijst', ". time() ."),";
$sql .= "(97, 'ID_trinitas', '', 'f', 'Mailchimp+lijst', ". time() ."),";
$sql .= "(100, 'importRoosters', '3', '9', '', ". time() ."),";
$sql .= "(101, 'importRoosters', '1', '7', 'Sommige+roosters+worden+geimporteerd.', ". time() ."),";
$sql .= "(102, 'importRoosters', '2', '8', 'Deze+moeten+aantal+functionaliteiten+niet+krijgen', ". time() ."),";
$sql .= "(103, 'importRoosters', '4', '10', '', ". time() ."),";
$sql .= "(104, 'lengthShortHash', '', '16', 'Lengte+van+de+lange+hash+%28login+ed%29', ". time() ."),";
$sql .= "(105, 'lengthLongHash', '', '64', 'Lengte+van+de+korte+hash+%28agenda+ed%29', ". time() .");";
mysqli_query($db, $sql);

?>