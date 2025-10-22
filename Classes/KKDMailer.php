<?php

class KKDMailer extends PHPMailer\PHPMailer\PHPMailer implements KKDConfig {

    /**
     * @var array Array 
     */
    public array $ontvangers;
    /**
     * @var int ID van het lid dat de mail moet ontvanger. Gebruik $ontvangers als je naam+mailadres wilt gebruiken
     */
    public int $aan;
    public string $onderwerp;
    public string $bericht;
    public bool $formeel;
    public bool $ouderCC;
    public bool $partnerTo;
    public string $van;
    public string $vanNaam;
    public string $AntwoordAan;
    public string $AntwoordNaam;
    public array $copy;
    public array $blancoCopy;
    public array $bijlage;
    public bool $testen;
    
    function __construct() {
        parent::__construct(true);

        // Server settings
        $this->isSMTP();
        $this->Host       = KKDConfig::MailHost;
        $this->Port       = KKDConfig::MailPort;
        $this->SMTPSecure = KKDConfig::SMTPSecure;
        $this->SMTPAuth   = KKDConfig::SMTPAuth;
        $this->Username   = KKDConfig::SMTPUsername;
        $this->Password   = KKDConfig::SMTPPassword;

        $this->setFrom(KKDConfig::noReplyAdress, KKDConfig::ScriptTitle);
    }

    function sendMail() {
        try {
            #Afzender
            if(!empty($this->van) AND !empty($this->vanNaam)) {
                $this->setFrom($this->van, $this->vanNaam);
            } elseif(!empty($this->van)) {
                $this->setFrom($this->van, KKDConfig::ScriptTitle);
            } else {
                $this->setFrom(KKDConfig::noReplyAdress, KKDConfig::ScriptTitle);
            }

            # Antwoordadres
            if(!empty($this->AntwoordAan) AND !empty($this->AntwoordNaam)) {
                $this->setFrom($this->AntwoordAan, $this->AntwoordNaam);
            } elseif(!empty($this->AntwoordAan)) {
                $this->setFrom($this->AntwoordAan);            
            }

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
                $partner = new Member($gebruiker->getPartner());
                $this->addAddress($partner->getMail(), $partner->getName());            
            }

            # Ouders in de CC
            if(isset($this->ouderCC) && $this->ouderCC) {
                $ouders = $gebruiker->getParents();

                foreach($ouders as $parent){
                    $ouder = new Member($parent);
                    $this->addCC($ouder->getMail(), $ouder->getName());
                }
            }

            # Adressen in de CC
            if(isset($this->copy) && $this->copy) {
                foreach($this->copy as $recipient) {
                    $this->addCC($recipient[0], $recipient[1]);
                }
            }

            # Adressen in de BCC            
            if(isset($this->blancoCopy) && $this->blancoCopy) {
                foreach($this->blancoCopy as $recipient) {
                    $this->addBCC($recipient[0], $recipient[1]);
                }
            }

            // Attachments
            if(isset($this->bijlage)) {
                foreach($this->attachment as $filePath) {
                    $this->addAttachment($filePath);
                }
            }
            

            // Content
            $this->isHTML(true);
            $this->CharSet = 'utf8mb4_unicode_ci';
            $this->Subject = $this->onderwerp;
            $this->Body    = $this->bericht;

            var_dump($this);

            $this->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->ErrorInfo);
            return false;
        }
    }
}
?>