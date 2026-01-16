<?php

/**
 * Class voor het versturen van mails specifiek voor de Koningskerk
 * het is een uitbreiding op PHPMailer
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
class KKDMailer extends PHPMailer\PHPMailer\PHPMailer implements KKDConfig {
    const SCRIPTURL = 'localhost';

    /**
     * @var array Array met [adres, naam] van de ontvangers
     */
    public array $ontvangers;
    
    /**
     * @var int ID van het lid dat de mail moet ontvanger. Gebruik $ontvangers als je naam+mailadres wilt gebruiken
     */
    public int $aan;

    /**
     * @var bool Moet het formele mailadres gebruikt worden?
     */
    public bool $formeel;
    
    /**
     * @var bool Moet de ouders in de CC worden meegenomen
     */
    public bool $ouderCC;

    /**
     * @var bool Moet de partner worden opgenomen in de Aan
     */
    public bool $partnerTo;

    /**
     * @var array Array met [adres, naam] van ontvangers die als CC moeten worden meegenomen
     */
    public array $copy;

    /**
     * @var array Array met [adres, naam] van de ontvangers die als BCC moeten worden meegenomen
     */
    public array $blancoCopy;

    /**
     * @var array Array met bijlages
     */
    public array $bijlage;

    /**
     * @var bool Moet de mail getoond worden ipv verstuurd (true) of daadwerkelijk verstuurd (false)
     */
    public bool $testen;

    /**
     * Maak een KKDMailer-object aan en configueer dit met KKD-parameters
     * Default staat testen uit
     */
    function __construct() {
        parent::__construct(true);

        // Server settings
        $this->CharSet = 'utf8mb4_unicode_ci';
        $this->Host			= KKDConfig::MailHost;
        $this->Port         = KKDConfig::MailPort;
        $this->SMTPSecure   = KKDConfig::SMTPSecure;
        $this->SMTPAuth     = KKDConfig::SMTPAuth;
        $this->Username		= KKDConfig::SMTPUsername;
        $this->Password		= KKDConfig::SMTPPassword;

        // Mail setting
        $this->From         = KKDConfig::noReplyAdress;
        $this->FromName     = KKDConfig::ScriptTitle;
        $this->IsHTML(true);
        $this->isSMTP();

        // Varia
        $this->testen       = false;
        $this->formeel      = false;
    }

    /**
     * Verstuur de KKD-mail.
     * @return bool Of versturen succesvol was
     */
    function sendMail() {
        try {
            # Ontvangers (text)
            if(isset($this->ontvangers)) {
                foreach($this->ontvangers as $recipient) {
                    if(isset($recipient[1])) {
                        $this->addAddress($recipient[0], $recipient[1]);
                    } else {
                        $this->addAddress($recipient[0]);
                    }                    
                }
            }

            # Ontvangers (ID)
            if(isset($this->aan)) {
                $gebruiker = new Member($this->aan);

                if($this->formeel) {                    
                    $emailType = 2;
                } else {
                    $emailType = 1;
                }

                $this->addAddress($gebruiker->getMail($emailType), $gebruiker->getName());
            }

            # Partner in de Aan
            if(isset($this->partnerTo) && $this->partnerTo) {
                $p = $gebruiker->getPartner();
                if($p != null) {
                    $partner = new Member($p);
                    $this->addAddress($partner->getMail(), $partner->getName());
                }
            }

            # Ouders in de CC
            if(isset($this->ouderCC) && $this->ouderCC) {
                $ouders = $gebruiker->getParents();

                foreach($ouders as $parent){
                    $ouder = new Member($parent);
                    $this->addCC($ouder->getMail(), $ouder->getName());
                }
            }

            if($this->testen) {
                echo 'Aan : '. implode('|', array_column($this->to, 0)) .'<br>';
                if(count($this->cc) > 0) {
                    echo 'CC : '. implode('|', array_column($this->cc, 0)) .'<br>';
                }
                if(count($this->bcc) > 0) {
                    echo 'BCC : '. implode('|', array_column($this->bcc, 0)) .'<br>';
                }
                echo 'Onderwerp : '. $this->Subject .'<br>';
                echo 'Bericht : '. $this->Body .'<br>';
                if(count($this->attachment) > 0) {
                    echo 'Bijlage : '. implode('|', $this->attachment[0]) .'<br>';
                }
            } else {
                $db = new Mysql();
                $sql = "INSERT INTO `mail_log` (`tijd`, `bericht`) VALUES ('". time() ."', '". urlencode($this->Body) ."')";
                $db->query($sql);

                $this->Body = $this->getKKDHeader().$this->Body.$this->getKKDFooter();                
                $this->send();
            }

            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->ErrorInfo);
            echo $this->ErrorInfo;
            return false;
        }
    }

    function getKKDHeader() {
        $Header  = "<html>".NL;
        $Header .= "<head>".NL;
        $Header .= '		<style>'.NL;
        $Header .= '			* { box-sizing: border-box;}'.NL;
        $Header .= '			body { background-color:#F2F2F2; font-family: Arial, Helvetica, sans-serif; color:#34383D; }'.NL;
        $Header .= '			a { color: #2B153B; text-decoration: underline; }'.NL;
        $Header .= '			a:hover { color: #8C1974; font-weight: bold; text-decoration: none; }'.NL;
        $Header .= '			.middenstuk { width: 700px; background-color:#ffffff; margin: auto; }'.NL;
        $Header .= '			.bredebalk { background-color:#8C1974; height:20px; }'.NL;
        $Header .= '			.dunnebalk { background-color: #2B153B; height: 1px; margin-bottom: 20px; }'.NL;
        $Header .= '			.content_kolom { width: 95%; margin: auto; margin-bottom:20px; }'.NL;
        $Header .= '			.content { padding: 10px; margin-bottom: 15px; }'.NL;
        $Header .= '			.top_logo { margin-top: 10px; margin-left: 50px; overflow: auto; height: auto; }'.NL;
        $Header .= '			img.logo { float: left; width: 600px; height: auto; }'.NL;
        $Header .= '			@media screen and (max-width:700px) { .middenstuk { width: 100%; } img.logo { width: 400px; } }'.NL;
        $Header .= '		</style>'.NL;
        $Header .= "		<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>".NL;
        $Header .= "		<meta name='viewport' content='width=device-width, initial-scale=1'>".NL;
        $Header .= "	</head>".NL;
        $Header .= "	<body>".NL;
        $Header .= "	<div class='middenstuk'>".NL;
        $Header .= "		<div class='bredebalk'>&nbsp;</div>".NL;
        $Header .= "		<div class='content_kolom'>".NL;
        $Header .= "			<div class='top_logo'><a href='". KKDMailer::SCRIPTURL ."'><img class='logo' src='". KKDMailer::SCRIPTURL ."images/logoKoningsKerk.png'></a></div>".NL;
        $Header .= "			<div class='dunnebalk'>&nbsp;</div>".NL;
        $Header .= "			<div class='content'>".NL;

        return $Header;
    }

    function getKKDFooter() {
        $Footer = "			</div>".NL;
        $Footer .= "		<div class='dunnebalk'>&nbsp;</div>".NL;
        $Footer .= "	</div>".NL;
        $Footer .= "	<div class='bredebalk'>&nbsp;</div>".NL;
        $Footer .= "</body>".NL;
        $Footer .= "</html>".NL;

        return $Footer;
    }
}
?>