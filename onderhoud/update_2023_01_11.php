<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();
$sql = array();

#$vars[] = array(4, 'declJGKop', 0, 'Algemeen en staf', 'Catagorie in boekhouding Jeugd & Gezin');
#$vars[] = array(4, 'declJGKop', 1, 'Bijbelklas', 'Catagorie in boekhouding Jeugd & Gezin');
#$vars[] = array(4, 'declJGKop', 2, 'Basiscatechese', 'Catagorie in boekhouding Jeugd & Gezin');
#$vars[] = array(4, 'declJGKop', 3, 'Follow light', 'Catagorie in boekhouding Jeugd & Gezin');
#$vars[] = array(4, 'declJGKop', 4, 'Follow', 'Catagorie in boekhouding Jeugd & Gezin');
#$vars[] = array(4, 'declJGKop', 5, 'FollowNext', 'Catagorie in boekhouding Jeugd & Gezin');
#$vars[] = array(4, 'declJGKop', 6, 'Overig', 'Catagorie in boekhouding Jeugd & Gezin');

$vars[] = array(4, 'declJGToelichting', 1, 'Het primaire doel van deze post is om financi&euml;le ruimte bieden voor de professionalisering van de leiding en mentoren van de diverse jeugdgroepen. Deze professionalisering krijgt gestalte door een training, workshop of bijeenkomst georganiseerd door de jeugdwerker. Deze post heeft enkel betrekking op de benodigde (les)materialen, eten, drinken en eventuele huur van een ruimte ten behoeve van de professionalisering.<br><br>Het secundaire doel van deze post is om financi&euml;le ruimte te bieden voor leiding en mentoren van de jeugdgroepen om een cursus te volgen of een bijeenkomst bij te wonen welke relevant is voor hun bijdrage in het jeugdwerk. Daarbij kan per persoon maximaal &eacute;&eacute;n keer hierop aanspraak op worden gemaakt ter waarde van maximaal &euro;25,-. Dit bedrag kan dienen als vergoeding van de reiskosten of de kosten van deelname aan de cursus/bijeenkomst.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 2, 'Doel van deze post is om de onderlinge verbondenheid - bij goede en slechte dagen - te versterken. Dat geldt zowel voor de jongeren als voor de mentoren. Daarnaast maakt deze post het mogelijk om afzwaaiende mentoren een kleine attentie te geven als dank voor hun inzet.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 3, 'Doel van deze post is om de materialen voor de bijbelvertelling te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 4, 'Doel van deze post is om een geschenk aan de kinderen van BK5 als afsluiting van de bijbelklas en de overgang naar de basiscatechese te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 5, '50%  van het bedrag van gemeenteabonnement lerenindekerk.nl. BC gebruikt Spoorzoeken.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 6, 'Doel van deze post is om het ondersteunende materiaal voor de basiscatechese te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 7, 'Doel van deze post is om BC 8 aan het eind van het jaar jongerenbijbels te schenken als stimulering voor het lezen van de bijbel.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 8, 'Doel van deze post is om de materialen voor Follow light te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 9, 'Doel van deze post is om sociale acitiviteiten voor Follow light te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 10, 'Normaliter wordt eten/drinken van de kerk gebruikt dat door het cluster O&B wordt aangeschaft. In enkele gevallen wordt aanvullend eten en drinken gehaald. Het doel van deze post is om dat eten en drinken voor Follow Light te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 11, 'Doel van deze post is om het kamp (1 overnachting) voor Follow Light te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 12, 'Doel van deze post is om de schaatsactiviteit voor Follow Light (met beperkte deelname van andere groepen) te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 13, 'Deze post betreft de bijdrage van de ouders van de kinderen van Follow Light die deelnemen aan het kamp.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 14, '50%  van het bedrag van gemeenteabonnement lerenindekerk.nl. BC gebruikt Spoorzoeken. ', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 15, 'Doel van deze post is om de materialen voor de Follow te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 16, 'Doel van deze post is om sociale acitiviteiten voor de Follow te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 17, 'Normaliter wordt eten/drinken van de kerk gebruikt dat door het cluster O&B wordt aangeschaft. In enkele gevallen wordt aanvullend eten en drinken gehaald. Het doel van deze post is om dat eten en drinken voor Follow te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 18, 'Doel van deze post is om het Followkamp (2 overnachtingen) te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 19, 'Doel van deze post is om het afscheid van Follow 4 te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 20, 'Deze post betreft de bijdrage van de ouders van de kinderen die deelnemen aan het Followkamp.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 21, 'Doel van deze post is om diaconale acitiviteiten door de FollowNext te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 22, 'Doel van deze post is om het gezamenlijk eten van FollowNext te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 23, 'Doel van deze post is om het FollowNext weekend (2 overnachtingen) te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 24, 'Deze post betreft de bijdrage van de ouders van de kinderen die deelnemen aan het FollowNext weekend.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 25, 'Doel van deze post is om ouders toe te rusten voor de christelijke opvoeding van hun kinderen. Daarbij staan vragen centraal als: hoe breng ik het geloof over aan mijn kinderen, hoe ben ik een voorbeeldchristen voor hen en wat kenmerkt een christelijke opvoeding?', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 26, 'Doel van deze post is om de kerstboekjes voor de kinderen tot en met 12 jaar te faciliteren.', 'Toelichting bij post Jeugd & Gezin');
$vars[] = array(4, 'declJGToelichting', 27, 'Doel van deze post is om onvoorziene kosten te dekken.', 'Toelichting bij post Jeugd & Gezin');

$vars[] = array(2, 'noReplyAdress', '', 'noreply@koningskerkdeventer.nl', 'Mailadres dat gebruikt kan worden voor reminder-mails');
$vars[] = array(4, 'penningmeesterJGAddress', '', 'nico.jager@koningskerkdeventer.nl', 'Mailadres penningmeester J&G');
$vars[] = array(4, 'penningmeesterJGNaam', '', 'Penningmeester cluster Jeugd & Gezin', 'Naam penningmeester J&G');

foreach($vars as $var) {
	$sql[] = "INSERT INTO $TableConfig ($ConfigGroep, $ConfigName, $ConfigKey, $ConfigValue, $ConfigOpmerking, $ConfigAdded) VALUES ('". $var[0] ."', '". $var[1] ."', '". $var[2] ."', '". $var[3] ."', '". $var[4] ."', ". time() .");";
}

#$sql[] = "DELETE FROM $TableConfig WHERE $ConfigName like 'configGroups' AND $ConfigKey = 5;";
#$sql[] = "DELETE FROM $TableConfig WHERE $ConfigGroep = 5;";

foreach($sql as $query) {
	echo $query;
	if(mysqli_query($db, $query)) {
		echo " -> gelukt<br>";	
	} else {
		echo "<b> -> mislukt</b><br>";	
	}
}



# Na uitvoeren bestand verwijderen
if($productieOmgeving) {
	$delen = explode('/', parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH));
	unlink(end($delen));
}

?>