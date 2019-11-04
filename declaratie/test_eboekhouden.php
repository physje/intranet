<?php
include_once('../include/config.php');
include_once('../include/EB_functions.php');

/* Test code voor eb_getRelatieIbanById
$ibanNummer = "";
$idNummer = 0;

if ( eb_getRelatieIbanById ($idNummer, $ibanNummer ) ) {
  echo "probleem opgetreden bij het opvragen van iban nummer";
} else {
  echo "iban van ".$idNummer." is: ".$ibanNummer.".";
}

*/

/* Test code voor eb_getRelatieIbanByCode
$ibanNummer = "";
$code = "0";

if ( eb_getRelatieIbanByCode ($code, $ibanNummer ) ) {
  echo "probleem opgetreden bij het opvragen van iban nummer";
} else {
  echo "iban van ".$code." is: ".$ibanNummer.".";
}

*/

/* Test code voor eb_updateRelatieIbanByCode 

$code = "";
$iban = "";

if ( eb_updateRelatieIbanByCode($code, $iban) ) {
  echo "probleem opgetreden bij het aanpassen van een bestaande relatie";
} else {
  echo "nieuwe relatie succesvol aangepast, het eb-ID is: ".$id.".";
}
*/

/* Test code voor eb_maakNieuweRelatieAan 

$naam = "Test2";
$geslacht = "m";
$adres = "AdresTest2";
$postcode = "PostcodeTest2";
$plaats = "PlaatsTest2";
$email = "EmailTest2";
$iban = "IbanTest2";

if ( eb_maakNieuweRelatieAan ($naam, $geslacht, $adres, $postcode, $plaats, $email, $iban, $code, $id) ) {
  echo "probleem opgetreden bij het toevoegen van een nieuwe relatie";
} else {
  echo "nieuwe relatie succesvol toegevoegd, de relatie ID is: ".$id.", en de relatie Code is: ".$code.".";
}
*/

/* IN PROGRESS */
$code = "201"; //test1
$bedrag = 1; // 1 cent (just to be sure)
$toelichting = "Dit is een test mutatie, de gebruikte relatie is niet echt";

if ( eb_verstuurDeclaratie ( $code, $bedrag, $toelichting, $mutatieId ) ) {
  echo "probleem opgetreden bij het toeveogen van een nieuwe mutatie";
} else {
  echo "nieuwe mutatie succesvol toegevoegd, de mutatie ID is: ".$mutatieId."";
}

?>