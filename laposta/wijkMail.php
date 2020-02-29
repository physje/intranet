<?php
include_once('../include/MIMEmailParser.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/LP_functions.php');
setlocale(LC_TIME, 'nl_NL');
$db = connect_db();

/* Read the message from STDIN */
$fd = fopen("php://stdin", "r");
$email = ""; // This will be the variable holding the data.
while (!feof($fd)) {
	$email .= fread($fd, 1024);
}
fclose($fd);

/*
$filename = "mail.txt";
$handle = fopen($filename, "r");
$email = fread($handle, filesize($filename));
fclose($handle);
*/

$mpars = new MIMEmailParser;
$mpars->parse_messageString($email);

$content			= $mpars->content;
$headers			= $mpars->headers;
$messageType	= $mpars->messageType;

$subject	= $headers['subject'];
$from			= $headers['from'];
$bericht	= $content[0]['content'];

//mail('matthijs@draijer.org', '[resend]'. $subject, $bericht);

//echo 'Onderwerp : '. $subject .'<br>';
//echo 'From : '. $from .'<br>';
//echo 'Bericht : '. json_encode($content);

$input['name'] = 'Wijkmail test';
$input['subject'] = $subject;
$input['from']['name'] = 'Test';
$input['from']['email'] = 'matthijs.draijer@koningskerkdeventer.nl';
//$input['reply_to'] = $from;
$input['list_ids'] = array('wzkffisyod');
//$input['stats']['ga'] = false;
//$input['stats']['mtrack'] = false;
$campaignID = lp_createMail($input);

echo $bericht;

if(lp_populateMail($campaignID, $bericht)) {
	//lp_scheduleMail($campaignID, $verzendtijd);
}

# Kan ik config-files van extern inlezen
# check of afzender in wijk zit
# check of er bijlages bijzitten
# op mailinglist LaPosta zetten

?>