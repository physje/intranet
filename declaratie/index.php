<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();


# Pagina tonen
echo $HTMLHeader;
echo '<table border=0 width=100%>'.NL;
echo '<tr>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '	<td valign="top">'. showBlock('Hier kan men op termijn zijn of haar declaraties doen', 100). '</td>'.NL;
echo '	<td valign="top" width="50">&nbsp;</td>'.NL;
echo '</tr>'.NL;
echo '</table>'.NL;
echo $HTMLFooter;

# Scherm 1
# kiezen of je declaratie als gastpredikant (A) of gemeentelid (B) wil doen.

# Scherm 2A
# selecteer dienst (uit database kan ik dan predikant halen). Melding dat mail gestuurd zal worden naar bekend adres om te valideren dat we met de juiste predikant te maken hebben.
# Kan dan gelijk een check doen of dienst al geweest is en nog niet eerder gedeclareerd.

#	Scherm 3A
# Toon declaratie-formulier. De eerste rij, met onkostenvergoeding is hierbij "vast"/niet wijzigbaar met tarief wat voor de predikant afgesproken is.
# Daaronder kan men de postcode invullen van het vertrek-adres waarna het systeem de reisafstand automatisch uitrekend op basis van locationiq.com (ik heb een account). Deze kilometers worden vervolgens als default ingevuld in het venster eronder.
# De ingevuld kilometers worden vervolgend automatisch vermenigdvuldigd met €0,35
# Daaronder verschijnt een veld voor overige kosten.

# Scherm 4A
# Toon nogmaals overzicht van declaratie met daaronder de vraag of IBAN nog correct is
# [discussie-punt : willen wij bekende IBAN ook tonen ? => betekent opvragen uit eBoekhouden]

# Scherm 5A
# pas IBAN in relatie aan mocht dat nodig zijn
# Voeg mutatie toe aan eBoekhouden
# Noteer dienst als "gedeclareerd" in database
# Genereer PDF (include/pdf/fpdf.php)
# Stuur PDF naar predikant met begeleidende tekst (rond de 20ste uitbetalen)
# Stuur PDF naar peningmeester.
# Sla PDF lokaal op (jaar/maand-map)

# tabel met predikanten uitbreiden met
#	- tarief
# - postcode (voor afstand berekenen)
# - eBoekhouden relatie

# tabel met dienst-predikant uitbreiden met
#	- declaratie-status (open, link verstuurd, link bezocht, ingestuurd, afgezien)

# functies
#	getEBIDbyIBAN(string iban)
#	getEBIDbyText(string text)
#	getEBRelatie(int id)

# Subtaken
# - Functies voor communicatie met EB
# - Functie om mbv locationiq.com afstand uit te rekenen
# - PDF genereren & opmaken
# - Online workflow

?>
