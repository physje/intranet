<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/config_mails.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Mysql.php');
include_once('../Classes/Member.php');
include_once('../Classes/KKDMailer.php');

$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_POST['id'])) {
	$id = key($_POST['id']);
}

if(isset($_POST['volgende'])) {
	$start = ($_POST['start']+25);
} elseif(isset($_POST['vorige'])) {
	$start = ($_POST['start']-25);
} elseif(isset($_POST['start'])) {
	$start = $_POST['start'];
} else {
	$start = 0;
}

$db = new Mysql();

$sql = "SELECT * FROM `mail_log` ORDER BY `tijd` DESC LIMIT $start,25";
$data = $db->select($sql, true);

if(count($data) > 0) {
	$block[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$block[] = "<input type='hidden' name='start' value='$start'>";
	$block[] = "<table border=0>";
	$block[] = "<thead>";
	$block[] = "<tr>";	
	$block[] = "	<th>Tijdstip</th>";
	$block[] = "	<th>Ontvanger</th>";
	$block[] = "	<th>Onderwerp</th>";
	$block[] = "	<th>&nbsp;</th>";
	$block[] = "</tr>";
	$block[] = "</thead>";	
	
	foreach($data as $mail) {
		$mailbericht = unserialize($mail['bericht']);
						
		$block[] = "<tr>";		
		$block[] = "	<td>". time2str('EE d L HH:mm', $mail['tijd']) ."</td>";
		
		if(isset($mailbericht->aan)) {
			if(!is_array($mailbericht->aan)) {
				$ontvanger = new Member($mailbericht->aan);				
			} else {
				$ontvanger = new Member($mailbericht->aan[0]);				
			}
			$block[] = "	<td><a href='../profiel.php?id=". $ontvanger->id ."'>". $ontvanger->getName(5)."</a></td>";		
		} else {
			#var_dump($mailbericht->ontvangers);
			$block[] = "	<td>". implode('|', $mailbericht->ontvangers[0]) ."</td>";
		}

		$block[] = "	<td>". $mailbericht->Subject ."</td>";
		if(isset($id) AND $id == $mail['id']) {	
			$block[] = "	<td><input type='submit' name='id[0]' value='-'></td>";
		} else {
			$block[] = "	<td><input type='submit' name='id[". $mail['id'] ."]' value='+'></td>";
		}
		$block[] = "</tr>";
		
		if(isset($id) AND $id == $mail['id']) {
			$show = array('aan', 'formeel', 'testen', 'From', 'FromName', 'Subject', 'Body');

			$block[] = "<tr>";
			$block[] = "	<td>&nbsp;</td>";		
			$block[] = "	<td colspan='2'>";
			$block[] = "	<table border=1>";
			foreach($mailbericht as $key => $value) {
				if(in_array($key, $show)) {
					$block[] = "<tr>";
					$block[] = "	<td>$key</td>";
					$block[] = "	<td>$value</td>";
					$block[] = "</tr>";
				}
			}
			
			$block[] = "	<tr>";
			$block[] = "		<td>&nbsp;</td>";
			$block[] = "		<td align='center'><a href='composeMail.php?id=$id'>Bewerk deze mail</a></td>";
			$block[] = "	</tr>";
			$block[] = "	</table>";
			$block[] = "</td>";
			$block[] = "</tr>";
		}
	}
	
	/*
	$block[] = "<tr>";
	$block[] = "	<td>&nbsp;</td>";
	
	if($start > 20) {
		$block[] = "	<td align='left'><input type='submit' name='vorige' value='Vorige'></td>";
	} else {
		$block[] = "	<td>&nbsp;</td>";
	}
	
	$block[] = "	<td>&nbsp;</td>";
	$block[] = "	<td align='right'><input type='submit' name='volgende' value='Volgende'></td>";
	$block[] = "</tr>";
	*/	
	$block[] = "</table>";	
	$block[] = "<p class='after_table'>". ($start > 20 ? "<input type='submit' name='vorige' value='Vorige'>" : ''). "&nbsp;<input type='submit' name='volgende' value='Volgende'></p>";	
	$block[] = "</form>";
}

echo showCSSHeader(array('default', 'table_default'));
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $block).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>