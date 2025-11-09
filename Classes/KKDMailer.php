<?php

/**
 * Class voor het versturen van mails specifiek voor de Koningskerk
 * het is een uitbreiding op PHPMailer
 */
class KKDMailer extends PHPMailer\PHPMailer\PHPMailer implements KKDConfig {
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
                    $this->addAddress($recipient[0], $recipient[1]);
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
                $this->send();
            }

            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->ErrorInfo);
            echo $this->ErrorInfo;
            return false;
        }
    }
}
?>