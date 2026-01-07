<?php
/**
 * Class met configuration-parameters
 * 
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
interface KKDConfig {
    # MySQL
    public const serverName = "localhost";
	public const userName = "root";
    public const passCode = "";
    public const dbName = "kkd";

    # Mail
    public const noReplyAdress = 'noreply@koningskerkdeventer.nl';
    public const ScriptTitle = 'Koningskerk Intranet';
    public const MailHost = 'mail.koningskerkdeventer.nl';
    public const MailPort = '465';
    public const SMTPSecure = 'ssl';
    public const SMTPAuth = true;
    public const SMTPUsername = 'intranet@koningskerkdeventer.nl';
    public const SMTPPassword = 'FQsmEB3lo7wv';
}