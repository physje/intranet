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

?>