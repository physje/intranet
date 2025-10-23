<?php
interface KKDConfig {
    # MySQL
    public const serverName = "localhost";
	public const userName = "root";
    public const passCode = "";
    public const dbName = "kkd";

    # Mail
    public const noReplyAdress = '';
    public const ScriptTitle = '';
    public const MailHost = '';
    public const MailPort = '';
    public const SMTPSecure = '';
    public const SMTPAuth = true;
    public const SMTPUsername = '';
    public const SMTPPassword = '';
}