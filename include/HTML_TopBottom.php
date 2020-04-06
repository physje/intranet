<?php
include_once('include/functions.php');
include_once('include/config.php');

$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

$db = connect_db();

$memberData = getMemberDetails($_SESSION['ID']);

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
$HTMLBody .= '<div class="header" id="intranetHeader">'.NL;
$HTMLBody .=   '<div class="column logo">'.NL;
$HTMLBody .=     '<a href="index.php"><img src="'. $ScriptURL .'images/logoKoningsKerk.png" height="100px"></a>'.NL;
$HTMLBody .=   '</div>'.NL;
$HTMLBody .=   '<div class="column text">'.NL;
$HTMLBody .=     '<h2>INTRANET</h2>'.NL;
$HTMLBody .=   '</div>'.NL;
$HTMLBody .= '</div>'.NL;

# ------- Webpage NAVIGATION BAR ------- #
$HTMLBody .= '<div class="navbar" id="intranetNavbar">'.NL;
$HTMLBody .=   '<a href="index.php" class="active">Home</a>'.NL;
$HTMLBody .=   '<a href="ledenlijst.php">Ledenlijst</a>'.NL;
$HTMLBody .=   '<a href="#Roosters">Roosters</a>'.NL;

$HTMLBody .=   '<div class="dropdown" id="dropdownLinks">'.NL;
$HTMLBody .=     '<div class="dropbtn" onclick="dropDownAccount(\'dropdownLinksContent\')">Links'.NL;
$HTMLBody .=       '<i class="fa fa-caret-down"></i>'.NL;
$HTMLBody .=     '</div>'.NL;
$HTMLBody .=     '<div class="dropdown-content" id="dropdownLinksContent">'.NL;

if(!in_array(1, getMyGroups($_SESSION['ID'])) AND !in_array(36, getMyGroups($_SESSION['ID']))) {
	$HTMLBody .= "<a href='../gebedskalender/'>Gebedskalender</a>".NL;
}
$HTMLBody .=       "<a href='ical/".$memberData['username'].'-'. $memberData['hash_short'] .".ics' target='_blank'>Persoonlijke digitale agenda</a>".NL;
$HTMLBody .=       "<a href='http://www.koningskerkdeventer.nl/' target='_blank'>koningskerkdeventer.nl</a>".NL;
$HTMLBody .=       "<a href='agenda/agenda.php'>Agenda voor Scipio</a>".NL;
$HTMLBody .=     '</div>'.NL;
$HTMLBody .=   '</div>'.NL;

$HTMLBody .=     '<div class="navbar-right">'.NL;
$HTMLBody .=       '<div class="dropdown" id="dropdownAccount">'.NL;
$HTMLBody .=         '<div class="dropbtn" onclick="dropDownAccount(\'dropdownAccountContent\')">Ingelogd als '. makeName($_SESSION['ID'], 5).''.NL;
$HTMLBody .=           '<i class="fa fa-caret-down"></i>'.NL;
$HTMLBody .=         '</div>'.NL;
$HTMLBody .=       '<div class="dropdown-content" id="dropdownAccountContent">'.NL;
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
$HTMLFooter .= '</html>'.NL;
$HTMLFooter .= "\n\n<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->";

$HTMLHeader = $HTMLHead.$HTMLBody;

?>
<script>
/** When user clicks on the three strypes the css settings for the navbar will be changed for responsive functionality */
function responsiveNavbar() {
  var x = document.getElementById("intranetNavbar");
  if (x.className === "navbar") {
    x.className += " responsive";
  } else {
    x.className = "navbar";
  }
}

/** When user clicks on the dropdown button toggle between hiding and showing the dropdown content */
function dropDownAccount(id) {
  var el = document.getElementById(id);

  el.style.visibility = "visible";
  el.style.display = el.style.display === "none" ? "inline" : "visible";

  el.classList.toggle("show");
}

/** When the user clicks outside the dropdown window the dropdown menu will be closed */
window.onclick = function(e) {
  matches = e.target.matches ? event.target.matches('.dropbtn') : e.target.msMatchesSelector('.dropbtn');
  if (!matches) {
  var dropdownAccount = document.getElementById("dropdownAccountContent");
  var dropdownLink = document.getElementById("dropdownLinksContent");
    if (dropdownAccount.classList.contains('show')) {
      dropdownAccount.classList.remove('show');
    }
    if (dropdownLink.classList.contains('show')) {
      dropdownLink.classList.remove('show');
    }
  }
}
</script>