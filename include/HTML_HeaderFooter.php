<?php

# Header
$MailHeader	= '<html>'.NL;
$MailHeader	.= '<head>'.NL;
$MailHeader	.= '<style type="text/css">'.NL;
$MailHeader	.= 'body		{ background-color:#F2F2F2; font-family:Arial; color:#34383D; }'.NL;
$MailHeader	.= 'p { margin-top: 30px;}'.NL;
$MailHeader	.= '.seperator	{ border-bottom:1px solid #34383D; }'.NL;
$MailHeader	.= '.onderwerp	{ color:#34383D; font-size:24px; font-weight:bold;}'.NL;
$MailHeader	.= '</style>'.NL;
$MailHeader	.= '</head>'.NL;
$MailHeader	.= '<body>'.NL;
$MailHeader	.= '<table width="700" cellpadding="0" cellspacing="0" align="center" bgcolor="ffffff">'.NL;
$MailHeader	.= '	<tr>'.NL;
$MailHeader	.= '		<td colspan="2" height="20" bgcolor="#8C1974">&nbsp;</td>'.NL;
$MailHeader	.= '	</tr>'.NL;
$MailHeader	.= '	<tr>'.NL;
$MailHeader	.= '		<td colspan="2" height="10">&nbsp;</td>'.NL;
$MailHeader	.= '	</tr>'.NL;
$MailHeader	.= '    <tr>'.NL;
$MailHeader	.= '		<td>'.NL;
$MailHeader	.= '		<table width="630" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">'.NL;
$MailHeader	.= '		<tr>'.NL;
$MailHeader	.= '			<td class="onderwerp" align="left" height="80" valign="bottom"><img src="'. $ScriptURL .'images/logoKoningsKerk.png" height=125 alt="Koningskerk Deventer"></td>'.NL;
$MailHeader	.= '		</tr>'.NL;
$MailHeader	.= '    </table>'.NL;
$MailHeader	.= '    <table width="630" align="center">'.NL;
$MailHeader	.= '			<tr>'.NL;
$MailHeader	.= '				<td colspan="2" class="seperator">&nbsp;</td>'.NL;
$MailHeader	.= '			</tr>'.NL;
$MailHeader	.= '			<tr>'.NL;
$MailHeader	.= '				<td colspan="2">&nbsp;</td>'.NL;
$MailHeader	.= '			</tr>'.NL;
$MailHeader	.= '			<tr>'.NL;
$MailHeader	.= '				<td colspan="2">'.NL;

$MailFooter	= '</td>'.NL;
$MailFooter	.= '</tr>'.NL;
$MailFooter	.= '		<tr>'.NL;
$MailFooter	.= '			<td colspan="2" class="seperator">&nbsp;</td>'.NL;
$MailFooter	.= '		</tr>'.NL;
$MailFooter	.= '		<tr>'.NL;
$MailFooter	.= '			<td colspan="2">&nbsp;</td>'.NL;
$MailFooter	.= '		</tr>'.NL;
$MailFooter	.= '    </table>'.NL;
$MailFooter	.= '		</td>'.NL;
$MailFooter	.= '	</tr>'.NL;
$MailFooter	.= '	<tr>'.NL;
$MailFooter	.= '		<td colspan="2" height="20" bgcolor="#8C1974">&nbsp;</td>'.NL;
$MailFooter	.= '	</tr>'.NL;
$MailFooter	.= '</table>'.NL;
$MailFooter	.= '</table>'.NL;
$MailFooter	.= '<br /><br /><br /><br /><br /><br />'.NL;
$MailFooter	.= '</body>'.NL;
$MailFooter	.= '</html>'.NL;



$newMailHeader  = "<html>".NL;
$newMailHeader .= "<head>".NL;
$newMailHeader .= '		<style>'.NL;
$newMailHeader .= '			* { box-sizing: border-box;}'.NL;
$newMailHeader .= '			body { background-color:#F2F2F2; font-family: Arial, Helvetica, sans-serif; color:#34383D; }'.NL;
$newMailHeader .= '			a { color: #2B153B; text-decoration: underline; }'.NL;
$newMailHeader .= '			a:hover { color: #8C1974; font-weight: bold; text-decoration: none; }'.NL;
$newMailHeader .= '			.middenstuk { width: 700px; background-color:#ffffff; margin: auto; }'.NL;
$newMailHeader .= '			.bredebalk { background-color:#8C1974; height:20px; }'.NL;
$newMailHeader .= '			.dunnebalk { background-color: #2B153B; height: 1px; margin-bottom: 20px; }'.NL;
$newMailHeader .= '			.content_kolom { width: 95%; margin: auto; margin-bottom:20px; }'.NL;
$newMailHeader .= '			.content { padding: 10px; margin-bottom: 15px; }'.NL;
$newMailHeader .= '			.top_logo { margin-top: 10px; margin-left: 50px; overflow: auto; height: auto; }'.NL;
$newMailHeader .= '			img.logo { float: left; width: 600px; height: auto; }'.NL;
$newMailHeader .= '			@media screen and (max-width:700px) { .middenstuk { width: 100%; } img.logo { width: 400px; } }'.NL;
$newMailHeader .= '		</style>'.NL;
$newMailHeader .= "		<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>".NL;
$newMailHeader .= "		<meta name='viewport' content='width=device-width, initial-scale=1'>".NL;
$newMailHeader .= "	</head>".NL;
$newMailHeader .= "	<body>".NL;
$newMailHeader .= "	<div class='middenstuk'>".NL;
$newMailHeader .= "		<div class='bredebalk'>&nbsp;</div>".NL;
$newMailHeader .= "		<div class='content_kolom'>".NL;
$newMailHeader .= "			<div class='top_logo'><a href='". $ScriptURL ."'><img class='logo' src='". $ScriptURL ."images/logoKoningsKerk.png'></a></div>".NL;
$newMailHeader .= "			<div class='dunnebalk'>&nbsp;</div>".NL;
$newMailHeader .= "			<div class='content'>".NL;

$newMailFooter = "			</div>".NL;
$newMailFooter .= "		<div class='dunnebalk'>&nbsp;</div>".NL;
$newMailFooter .= "	</div>".NL;
$newMailFooter .= "	<div class='bredebalk'>&nbsp;</div>".NL;
$newMailFooter .= "</body>".NL;
$newMailFooter .= "</html>".NL;


?>