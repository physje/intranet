<?php
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_HeaderFooter.php');
include_once('../../general_include/class.phpmailer.php');
include_once('../../general_include/class.html2text.php');
$db = connect_db();

$FinalHTMLMail = '<b>hoi</b> na wat vet ook wat anders';
$FinalSubjec = 'Leuk onderwerp';
$roosterData['naam_afzender'] = 'Test-mail';
$roosterData['mail_afzender'] = '';

unset($parameter);
$parameter['to'][]				= array(984285);
$parameter['message']			= $FinalHTMLMail;
$parameter['subject']			= $FinalSubject;
$parameter['ReplyToName']	= $roosterData['naam_afzender'];
$parameter['ReplyTo']			= $roosterData['mail_afzender'];						
$parameter['ouderCC']			= true;

if(sendMail_new($parameter)) {
	echo 'Gelukt';
} else {
	echo 'Helaas';
}

?>