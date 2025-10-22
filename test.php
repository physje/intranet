<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

include_once('include/config_mails.php');
#include_once('include/config.php');
include_once('Classes/KKDConfig.php');
#include_once('Classes/Mysql.php');
#include_once('Classes/Member.php');
#include_once('Classes/KKDMailer.php');
#include_once('include/functions.php');
#include_once('include/HTML_TopBottom.php');

$mail = new PHPMailer();
$mail->CharSet = 'utf8mb4_unicode_ci';
$mail->Subject	= 'Test-bericht';
$mail->From = KKDConfig::noReplyAdress;
$mail->FromName = KKDConfig::ScriptTitle;
$mail->AddAddress('matthijs@draijer.org');
$mail->IsHTML(true);
$mail->Body			= 'Hopseflopt webbie wappie';
$mail->isSMTP();
$mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
$mail->Host			= KKDConfig::MailHost;
$mail->Port       = KKDConfig::MailPort;
$mail->SMTPSecure = KKDConfig::SMTPSecure;
$mail->SMTPAuth   = KKDConfig::SMTPAuth;
$mail->Username		= KKDConfig::SMTPUsername;
$mail->Password		= KKDConfig::SMTPPassword;

#var_dump($mail);

if($mail->send()) {
    echo 'Gelukt';
} else {
    echo $mail->ErrorInfo;
}


?>