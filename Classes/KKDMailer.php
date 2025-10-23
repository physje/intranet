<?php

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
     * @var bool Moet de ouders in de CC worden meegenomen
     */
    public bool $ouderCC;

    /**
     * @var bool Moet de partner worden opgenomen in de Aan
     */
    public bool $partnerTo;
    public array $copy;
    public array $blancoCopy;
    public array $bijlage;
    public bool $testen;
    
    function __construct() {
        parent::__construct(true);

        // Server settings
        $this->CharSet = 'utf8mb4_unicode_ci';
        $this->Host			= KKDConfig::MailHost;
        $this->Port       = KKDConfig::MailPort;
        $this->SMTPSecure = KKDConfig::SMTPSecure;
        $this->SMTPAuth   = KKDConfig::SMTPAuth;
        $this->Username		= KKDConfig::SMTPUsername;
        $this->Password		= KKDConfig::SMTPPassword;
        
        $this->From = KKDConfig::noReplyAdress;
        $this->FromName = KKDConfig::ScriptTitle;
        
        $this->testen = false;
        
        $this->IsHTML(true);
        $this->isSMTP();
    }

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
                $this->addAddress($gebruiker->getMail(), $gebruiker->getName());
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
                var_dump($this);
            } else {
                $this->send();
            }

            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->ErrorInfo);
            return false;
        }
    }
}
?>