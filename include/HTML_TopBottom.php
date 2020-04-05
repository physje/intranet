<?php
include_once('include/functions.php');
include_once('include/config.php');

$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

$db = connect_db();

# HTML Header
$HTMLHead	= "<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->\n\n";
$HTMLHead	.= '<html>'.NL;
$HTMLHead	.= '<head>'.NL;
$HTMLHead	.= "	<title>$ScriptTitle $Version</title>\n";
$HTMLHead	.= "	<link rel='stylesheet' type='text/css' href='". $ScriptURL ."include/style2.css'>\n";
$HTMLHead .= "    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>\n"; # icon server will be used for now, later it will be replaced by our own made icons
$HTMLHead	.= "	<link rel='icon' href='". $ScriptURL ."images/logo.ico'>\n";

# HTML Body
$HTMLBody = '<body>'.NL;
$HTMLBody = '<div class="container" id="intranetContainer">'.NL;

# ------- Webpage HEADER ------- #
$HTMLBody .= '<div class="header" id="intranetHead">'.NL;
$HTMLBody .=   '<div class="logo">'.NL;
$HTMLBody .=     '<img src="'. $ScriptURL .'images/logoKoningsKerk.png" height="100px" href="index.php">'.NL;
$HTMLBody .=   '</div>'.NL;
$HTMLBody .=   '<div class="text">'.NL;
$HTMLBody .=     '<h2>INTRANET</h2>'.NL;
$HTMLBody .=   '</div>'.NL;
$HTMLBody .= '</div>'.NL;

# ------- Webpage NAVIGATION BAR ------- #
$HTMLBody .= '<div class="navbar" id="intranetNavbar">'.NL;
$HTMLBody .=   '<a href="index.php" class="active">Home</a>'.NL;
$HTMLBody .=   '<a href="ledenlijst.php">Ledenlijst</a>'.NL;
$HTMLBody .=   '<a href="#Roosters">Roosters</a>'.NL;
$HTMLBody .=   '<a href="#Links">Links</a>'.NL;
$HTMLBody .= ''.NL;
$HTMLBody .=     '<div class="navbar-right">'.NL;
$HTMLBody .=       '<div class="dropdown">'.NL;
$HTMLBody .=         '<button class="dropbtn">Ingelogd als '. makeName($_SESSION['ID'], 5).''.NL;
$HTMLBody .=           '<i class="fa fa-caret-down"></i>'.NL;
$HTMLBody .=         '</button>'.NL;
$HTMLBody .=       '<div class="dropdown-content">'.NL;
$HTMLBody .=         '<a href="account.php">Account</a>'.NL;
$HTMLBody .=         '<a href="profiel.php">Profiel</a>'.NL;
if(in_array(1, getMyGroups($_SESSION['ID']))) {
	$HTMLBody .=         '<a href="search.php">Zoeken</a>'.NL;
}
$HTMLBody .=       '</div>'.NL;
$HTMLBody .=     '</div>'.NL;
$HTMLBody .=     '<a href="auth/objects/logout.php" class="fa fa-sign-out"></a>'.NL;
$HTMLBody .=   '</div>'.NL;
$HTMLBody .=   '<a href="javascript:void(0);" style="font-size:15px;" class="icon" onclick="responsiveNavbar()">&#9776;</a>'.NL;
$HTMLBody .= '</div>'.NL;

# ------- Webpage MAINPAGE ------- #
$HTMLBody .= '<div class="mainpage" style="padding-left:16px">'.NL;
$HTMLBody	.= '  <table width="98%" align="center" border=0>'.NL;
$HTMLBody	.= '	<tr>'.NL;
$HTMLBody	.= '		<td class="seperator">&nbsp;</td>'.NL;
$HTMLBody	.= '	</tr>'.NL;
$HTMLBody	.= '	<tr>'.NL;
$HTMLBody	.= '		<td>&nbsp;</td>'.NL;
$HTMLBody	.= '	</tr>'.NL;
$HTMLBody	.= '	<tr>'.NL;
$HTMLBody	.= '		<td>'.NL;

# ------------------------------------------------------------------------ # 
# ------- This part of the webpage is generated by the php scripts ------- #
# ------------------------------------------------------------------------ # 

$HTMLFooter  = '		</td>'.NL;
$HTMLFooter .= '	</tr>'.NL;
$HTMLFooter .= '	<tr>'.NL;
$HTMLFooter .= '		<td class="seperator">&nbsp;</td>'.NL;
$HTMLFooter .= '	</tr>'.NL;
$HTMLFooter .= '	<tr>'.NL;
$HTMLFooter .= '		<td>&nbsp;</td>'.NL;
$HTMLFooter .= '	</tr>'.NL;
$HTMLFooter .= '  </table>'.NL;
$HTMLFooter .= '</div>'.NL;

# ------- Webpage FOOTER ------- #
$HTMLFooter .= '<div class="footer" id="intranetFooter">'.NL;
$HTMLFooter .= '<img src="'. $ScriptURL .'images/logoKoningsKerk.png">'.NL;
$HTMLFooter .= '<p>Intranet Koningskerk Deventer - 2020</p>'.NL;
$HTMLFooter .= '</div>'.NL;
$HTMLFooter .= ''.NL;
$HTMLFooter .= '</div>'.NL;
$HTMLFooter .= '</body>'.NL;
$HTMLFooter .= "\n\n<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->";

$HTMLHeader = $HTMLHead.$HTMLBody;

?>
<script>
function responsiveNavbar() {
  var x = document.getElementById("responsiveNavbar");
  if (x.className === "navbar") {
    x.className += " responsive";
  } else {
    x.className = "navbar";
  }
}
</script>