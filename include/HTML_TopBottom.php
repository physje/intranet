<?php

function showCSSHeader($sheets = array('default'), $header = '', $pageTitle = '') {
	global $pageTitle, $ScriptTitle, $Version, $ScriptServer, $ScriptURL;
	
	$Header[] = "<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->";
	$Header[] = "<!--     Gegenereerd op ". time2str("EEEE d LLL YYYY HH:mm:ss") ."     -->";
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
	$Footer[] = '		</div> <!-- end \'content\' -->';
	$Footer[] = '	<div class="bredebalk">&nbsp;</div>';
	$Footer[] = '</div> <!-- end \'middenstuk\' -->';
	$Footer[] = '</body>';
	$Footer[] = '</html>';
	
	return implode(NL, $Footer);
}
?>