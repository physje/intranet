<?php
include_once('../include/config.php');
include_once('../include/EB_functions.php');

/* Test code voor eb_getRelatieIbanById 
$ibanNummer = "";
$idNummer = 0;

$errorResult = eb_getRelatieIbanById ( $idNummer, $ibanNummer );

if ( $errorResult ) {
  echo "probleem opgetreden bij het opvragen van iban nummer <br>".$errorResult;
} else {
  echo "iban van ".$idNummer." is: ".$ibanNummer.".";
}
*/

/* Test code voor eb_getRelatieIbanByCode 
$ibanNummer = "";
$code = "0";

$errorResult = eb_getRelatieIbanByCode ( $code, $ibanNummer );

if ( $errorResult ) {
  echo "probleem opgetreden bij het opvragen van iban nummer <br>".$errorResult;
} else {
  echo "iban van ".$code." is: ".$ibanNummer.".";
}
*/

/* Test code voor eb_updateRelatieIbanByCode 

$code = "";
$iban = "";

$errorResult = eb_updateRelatieIbanByCode( $code, $iban );

if ( $errorResult ) {  
  echo "probleem opgetreden bij het aanpassen van een bestaande relatie <br>".$errorResult;
} else {
  echo "Bestaande relatie succesvol aangepast";
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

$errorResult = eb_maakNieuweRelatieAan ( $naam, $geslacht, $adres, $postcode, $plaats, $email, $iban, $code, $id );

if ( $errorResult ) {
  echo "probleem opgetreden bij het toevoegen van een nieuwe relatie <br>".$errorResult;
} else {
  echo "nieuwe relatie succesvol toegevoegd, de relatie ID is: ".$id.", en de relatie Code is: ".$code.".";
}
*/

/* Test code voor eb_getRelatieCodeByIban 
$iban = "IbanTest2";
$code = "";

$errorResult = eb_getRelatieCodeByIban ( $iban, $code );

if ( $errorResult ) {
  echo "probleem opgetreden bij het opzoeken van de relatiecode <br>".$errorResult;
} 

if ( $code ) {
  echo "Relatie gevonden, iban ".$iban. " hoort bij relatie code: ".$code;
} else {
  echo "Relatie niet gevonden";
}
*/

/* Test code voor eb_getRelatieCodeBySearch 
$text = "EmailTest2";

$errorResult = eb_getRelatieCodeBySearch($text, $codes);

if ( $errorResult ) {
  echo "probleem opgetreden bij het opzoeken van de relatiecodes <br>".$errorResult;
} 

if ( count($codes) == 0 ) {
  echo "Geen relaties gevonden met volgende zoektext: ".$text."<br>";
} else {
  echo count($codes)." relaties gevonden: <br>";
  foreach ($codes as $code) {
    echo $code."<br>";
  }
}
*/

/* IN PROGRESS */
$code = "201"; //test1
$bedrag = 1; // 1 cent (just to be sure)
$toelichting = "Dit is een test mutatie, de gebruikte relatie is niet echt";

if ( eb_verstuurDeclaratie ( $code, $bedrag, $toelichting, $mutatieId ) ) {
  echo "probleem opgetreden bij het toeveogen van een nieuwe mutatie <br";
} else {
  echo "nieuwe mutatie succesvol toegevoegd, de mutatie ID is: ".$mutatieId."";
}

?>