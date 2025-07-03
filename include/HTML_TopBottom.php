<?php

# Header
$HTMLHead	=  "<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->".NL;
$HTMLHead	.= "<!--     Gegenereerd op ". time2str("%A %e %B %Y %H:%M:%S") ."     -->".NL;
$HTMLHead	.= NL;
$HTMLHead	.= '<html>'.NL;
$HTMLHead	.= '<head>'.NL;
$HTMLHead	.= "	<title>$ScriptTitle ". ((isset($pageTitle) AND $pageTitle != '') ? "| $pageTitle" : $Version) ."</title>".NL;
$HTMLHead	.= "	<link rel='stylesheet' type='text/css' href='". $ScriptURL ."include/style.css?".time()."'>".NL;
$HTMLHead	.= "	<link rel='icon' href='". $ScriptURL ."images/logo.ico'>".NL;
$HTMLHead	.= "	<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>".NL;
$HTMLHead	.= "	<meta name='viewport' content='width=device-width, initial-scale=1'>".NL;
$HTMLHead	.= '</head>'.NL;

$HTMLBody	= '<body>'.NL;
$HTMLBody	.= '<table width="95%" cellpadding="0" cellspacing="0" align="center" bgcolor="ffffff" border=0>'.NL;
$HTMLBody	.= '<tr>'.NL;
$HTMLBody	.= '	<td height="20" bgcolor="#8C1974">&nbsp;</td>'.NL;
$HTMLBody	.= '</tr>'.NL;
$HTMLBody	.= '<tr>'.NL;
$HTMLBody	.= '	<td height="10">&nbsp;</td>'.NL;
$HTMLBody	.= '</tr>'.NL;
$HTMLBody	.= '<tr>'.NL;
$HTMLBody	.= '	<td>'.NL;
$HTMLBody	.= '	<table width="95%" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" border=0>'.NL;
$HTMLBody	.= '	<tr>'.NL;
$HTMLBody	.= '		<td width="50">&nbsp;</td>'.NL;
$HTMLBody	.= '		<td width="150"><a href="'. $ScriptURL .'"><img src="'. $ScriptURL .'images/logoKoningsKerk.png" height=150 alt=""></a></td>'.NL;
$HTMLBody	.= '    <td width="75">&nbsp;</td>'.NL;
$HTMLBody	.= '		<td class="onderwerp" align="middle" height="80" valign="middle">&nbsp;</td>'.NL;
$HTMLBody	.= '		<td width="50">&nbsp;</td>'.NL;
$HTMLBody	.= '	</tr>'.NL;
$HTMLBody	.= '  </table>'.NL;
$HTMLBody	.= '  <table width="95%" align="center" border=0>'.NL;
$HTMLBody	.= '	<tr>'.NL;
$HTMLBody	.= '		<td class="seperator">&nbsp;</td>'.NL;
$HTMLBody	.= '	</tr>'.NL;
$HTMLBody	.= '	<tr>'.NL;
$HTMLBody	.= '		<td>&nbsp;</td>'.NL;
$HTMLBody	.= '	</tr>'.NL;
$HTMLBody	.= '	<tr>'.NL;
$HTMLBody	.= '		<td>'.NL;

$HTMLHeader = $HTMLHead.$HTMLBody;


# Footer
$HTMLFooter  = '		</td>'.NL;
$HTMLFooter .= '	</tr>'.NL;
$HTMLFooter .= '	<tr>'.NL;
$HTMLFooter .= '		<td class="seperator">&nbsp;</td>'.NL;
$HTMLFooter .= '	</tr>'.NL;
$HTMLFooter .= '	<tr>'.NL;
$HTMLFooter .= '		<td>&nbsp;</td>'.NL;
$HTMLFooter .= '	</tr>'.NL;
$HTMLFooter .= '  </table>'.NL;
$HTMLFooter .= '	</td>'.NL;
$HTMLFooter .= '</tr>'.NL;
$HTMLFooter .= '<tr>'.NL;
# $HTMLFooter .= '	<td height="20" bgcolor="#34383D">&nbsp;</td>'.NL;
$HTMLFooter .= '	<td height="20" bgcolor="#8C1974">&nbsp;</td>'.NL;
$HTMLFooter .= '</tr>'.NL;
$HTMLFooter .= '</table>'.NL;
$HTMLFooter .= '<br /><br /><br /><br /><br /><br />'.NL;
$HTMLFooter .= '</body>'.NL;
$HTMLFooter .= '</html>'.NL;
$HTMLFooter .= "\n\n<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->";


function showCSSHeader($sheets = array('default'), $header = '', $pageTitle = '') {
	global $pageTitle, $ScriptTitle, $Version, $ScriptURL;
	
	$Header[] = "<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->";
	$Header[] = "<!--     Gegenereerd op ". time2str("%A %e %B %Y %H:%M:%S") ."     -->";
	$Header[] = "";
	$Header[] = '<html>';
	$Header[] = '<head>';
	$Header[] = "	<title>$ScriptTitle ". ((isset($pageTitle) AND $pageTitle != '') ? "| $pageTitle" : $Version) ."</title>";
	
	foreach($sheets as $sheet) {
		$Header[] = "	<link rel='stylesheet' type='text/css' href='". $ScriptURL ."include/style_". $sheet .".css?".time()."'>";
	}
	
	if($header != '') {
		$Header = array_merge($Header, $header);
	}
	
	$Header[] = "	<link rel='icon' href='". $ScriptURL ."images/logo.ico'>";
	$Header[] = "	<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
	$Header[] = "	<meta name='viewport' content='width=device-width, initial-scale=1'>";
	$Header[] = "	<meta property=\"og:locale\" content=\"nl_NL\" />";
	$Header[] = "	<meta property=\"og:site_name\" content=\"$ScriptTitle\" />";
	$Header[] = "	<meta property=\"og:type\" content=\"article\" />";
	$Header[] = "	<meta property=\"og:title\" content=\"". ((isset($pageTitle) AND $pageTitle != '') ? $pageTitle : "") ."\" />";
	$Header[] = "	<meta property=\"og:description\" content=\"Intranet van de Koningskerk in Deventer\" />";
	$Header[] = "	<meta property=\"og:url\" content=\"". $ScriptServer.$_SERVER['PHP_SELF']."\" />";
	$Header[] = "	<meta property=\"og:image\" content=\"". $ScriptURL ."images/logoKoningsKerk.png\" />";	
	$Header[] = '</head>';
	$Header[] = '<body>';	
	$Header[] = '<div class="middenstuk">';
	$Header[] = '	<div class="bredebalk">&nbsp;</div>';
	$Header[] = '	<div class="content">';
	$Header[] = '		<div class="top_logo"><a href="'. $ScriptURL .'"><img class="logo" src="'. $ScriptURL .'images/logoKoningsKerk.png"></a></div>';
	$Header[] = '		<div class="dunnebalk">&nbsp;</div>';
	$Header[] = '		<div class="row">';
	
	return implode(NL, $Header);
}


function showCSSFooter() {
	#$Footer[] = '			</div>';
	$Footer[] = '		</div> <!-- end \'row\' -->';
	$Footer[] = '		<div class="dunnebalk">&nbsp;</div>';
	$Footer[] = '		<div class="bredebalk">&nbsp;</div>';
	$Footer[] = '	</div> <!-- end \'content\' -->';
	$Footer[] = '</div> <!-- end \'middenstuk\' -->';
	$Footer[] = '</body>';
	$Footer[] = '</html>';
	
	return implode(NL, $Footer);
}
?>