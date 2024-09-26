<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');

$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$left = $right = array();
$myGroups = getMyGroups($_SESSION['realID']);

if(in_array(1, $myGroups)) {	
	if(isset($_POST['unmask'])) {
		toLog('info', $_SESSION['fakeID'], 'Vermomming afgedaan');
		unset($_SESSION['fakeID']);		
	}
	
	if(isset($_POST['fake_lid']) AND $_POST['fake_lid'] != '') {
		$delen = explode('|', $_POST['fake_lid']);
		$fake_lid = $delen[1];
		
		$_SESSION['fakeID'] = $fake_lid;
		toLog('info', $_SESSION['fakeID'], 'Vermomd aangetrokken');
		
		$right[] = "Vermomd als ". makeName($fake_lid, 5) .".<br>";	
		$right[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
		$right[] = "<p class='after_table'><input type='submit' name='unmask' value='Vermomming afdoen'></p>";
		$right[] = "</form>";
		
	}
	
	$left[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$left[] = "Selecteer als welk lid je je wilt vermommen.<br>";
	$left[] = "<input type='text' id='namen_input' name='fake_lid' placeholder='Begin met typen van naam'><br>";
	$left[] = "<p class='after_table'><input type='submit' name='disguise' value='Vermommen'></p>";
	$left[] = "</form>";
} else {
	$left[] = "Deze pagina is niet voor jou";
}

$header[] = "	<!-- jQuery library -->";
$header[] = "	<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>";
$header[] = "	<!-- jQuery UI library -->";
$header[] = "	<link rel='stylesheet' href='https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css'>";
$header[] = "	<script src='https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js'></script>";
$header[] = "	<script>";
$header[] = "		$(function() {";
$header[] = "		    $(\"#namen_input\").autocomplete({";
$header[] = "		    	minLength: 3,";
$header[] = "		    	source: \"../autocomplete_namen.php\",";
$header[] = "		    	select: function( event, ui ) {";
$header[] = "		    		event.preventDefault();";
$header[] = "		    		$(\"#namen_input\").val(ui.item.selector);";
$header[] = "		    	}";
$header[] = "		    });";
$header[] = "		});";
$header[] = "		</script>";

echo showCSSHeader(array('default'), $header);
echo '<div class="content_vert_kolom">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $left).NL."</div>".NL;
echo "<div class='content_block'>".NL. implode(NL, $right).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom\' -->'.NL;
echo showCSSFooter();

?>