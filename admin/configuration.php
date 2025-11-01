<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../Classes/Member.php');
include_once('../include/HTML_TopBottom.php');
$requiredUserGroups = array(1);
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$db = new Mysql();

if(isset($_POST['save'])) {
	if(isset($_POST['delete'])) {
		foreach($_POST['delete'] as $id => $value) {
			$sql_delete = "DELETE FROM `config` WHERE `id` = $id";
			if($db->query($sql_delete)) {
				$text[] = $id. ' verwijderd';
			}
		}
	}
	
	foreach($_POST['value'] as $id => $value) {		
		if(isset($_POST['name'][$id])) 	$name = $_POST['name'][$id];
		
		if(isset($_POST['key'][$id])) {
			$key = $_POST['key'][$id];
		} else {
			$key = '';
		}
		
		if(isset($_POST['comment'][$id])) {
			$comment = $_POST['comment'][$id];
		} else {
			$comment = '';
		}
		
		if(isset($_POST['groep'][$id])) {
			$groep = $_POST['groep'][$id];
		} else {
			$groep = 1;
		}
		
		if($id == 999 AND $value != '') {
			$sql_insert = "INSERT INTO `config` (`groep`, `name`, `key`, `value`, `comment`, `added`) VALUES ($groep, '". urlencode($name) ."', '". urlencode($key) ."', '". urlencode($value) ."', '". urlencode($comment) ."', '". time() ."')";
			if($db->query($sql_insert)) {
				$text[] = $name. ' toegevoegd';
			} else {
				$text[] = $sql_insert;
			}
		}
		
		if($id != 999) {
			$sql_update = "UPDATE `config` SET `groep` = '$groep', `name` = '". urlencode($name) ."', `sleutel` = '". urlencode($key) ."', `value` = '". urlencode($value) ."', `comment` = '". urlencode($comment) ."' WHERE `id` = '$id'";
			if(!$db->query($sql_update)) {
			$text[] = "$name niet kunnen updaten";
				echo $sql_update .'<br>';
			}
		}		
	}
}

$text[] = "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
$thead[] = "<table>";
$thead[] = "<thead>";
$thead[] = "<tr>";
$thead[] = "	<th>Verwijder</th>";
if($configMoveGroups) {
	$thead[] = "	<th>Groep</th>";
}
$thead[] = "	<th>Naam</th>";
$thead[] = "	<th>Index</th>";
$thead[] = "	<th>Waarde</th>";
$thead[] = "	<th>Opmerking</th>";
$thead[] = "</tr>";
$thead[] = "</thead>";

$tfooter[] = "</table>";

#$configGroups = array_merge(array(0 => 'Onbekend'), $configGroups);

foreach($configGroups as $groepID => $groepNaam) {
	$sql = "SELECT `name`, COUNT(*) as aantal FROM `config` WHERE `groep` = $groepID GROUP BY `name` ORDER BY `name`";
	$data = $db->select($sql, true);

	if(count($data) > 0) {
		$text = array();
		
		foreach($data as $data_rij) {
			#$name	= $data['name'];
			#$groep	= $groepID;
			$aantal	= $data_rij['aantal'];

			$sql_name = "SELECT * FROM `config` WHERE `name` like '". $data_rij['name'] ."' AND `groep` = $groepID ORDER BY `sleutel`";
			$data_name = $db->select($sql_name, true);

			$first = true;
			
			foreach($data_name as $name_rij) {
				$id = $name_rij['id'];
											
				$text[] = "<tr>";
				$text[] = "	<td><input type='checkbox' name='delete[$id]' value='1'></td>";
				
				if($configMoveGroups) {
					$text[] = "	<td><select name='groep[$id]'>";
					foreach($configGroups as $key => $GroepNaam) {						
						$text[] = "	<option value='$key'". ($key == $groepID ? ' selected' : '') .">$GroepNaam</option>";
					}					
					$text[] = "	</select></td>";
				} else {
					$text[] = "	<input type='hidden' name='groep[$id]' value='$groepID'>";
				}
				
				if($first) {
					#$text[] = "	<td". ($aantal == 1 ? '' : " rowspan='$aantal' valign='top'") ."><input type='text' name='name[$id]' value='". urldecode($name) ."'></td>";
					$text[] = "	<td><input type='text' name='name[$id]' value='". urldecode($name_rij['name']) ."'></td>";
					$first = false;	
				} else {					
					$text[] = "	<td>&nbsp;</td>";
					$text[] = "	<input type='hidden' name='name[$id]' value='".$name_rij['name']."'>";
				}
						
				if($name_rij['sleutel'] != '') {
					$text[] = "	<td><input type='text' name='key[$id]' value='". urldecode($name_rij['sleutel']) ."' size='25'></td>";	
					$text[] = "	<td><input type='text' name='value[$id]' value='". urldecode($name_rij['value']) ."' size='25'></td>";	
				} else {
					$text[] = "	<td colspan='2'><input type='text' name='value[$id]' value='". urldecode($name_rij['value']) ."' size='55'></td>";
				}
				$text[] = "	<td><input type='text' name='comment[$id]' value='". urldecode($name_rij['comment']) ."' size='45'></td>";
				$text[] = "</tr>";
			}
		}
		
		$block[$groepID] = array_merge($thead, $text, $tfooter);
	}
}

$text = array();
#$text[] = "<tr>";
#$text[] = "	<td>&nbsp;</td>";
#$text[] = "	<td colspan='". ($configMoveGroups ? 5 : 4)."'><h2>Nieuwe toevoegen</h2></td>";
#$text[] = "</tr>";		
$text[] = "<tr>";
$text[] = "	<td>&nbsp;</td>";
if($configMoveGroups) {
	$text[] = "	<td>&nbsp;</td>";
}
$text[] = "	<td><input type='text' name='name[999]'></td>";
$text[] = "	<td><input type='text' name='key[999]'></td>";	
$text[] = "	<td><input type='text' name='value[999]''></td>";	
$text[] = "	<td><input type='text' name='comment[999]''></td>";	
$text[] = "</tr>";
#$text[] = "<tr>";
#$text[] = "	<td colspan='". ($configMoveGroups ? 6 : 5)."'>&nbsp;</td>";
#$text[] = "</tr>";
#$text[] = "<tr>";
#$text[] = "	<td colspan='". ($configMoveGroups ? 6 : 5)."' align='center'><input type='submit' name='save' value='Opslaan'></td>";
#$text[] = "</tr>";
#$text[] = "</table>";
#$text[] = "</form>";

$block[] = array_merge($thead, $text, $tfooter);
$configGroups[] = 'Nieuwe toevoegen';

$header[] = '<style>';
$header[] = '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
$header[] = '	td:nth-of-type(1):before { content: "Verwijder"; }';
if($configMoveGroups) {
	$header[] = '	td:nth-of-type(2):before { content: "Groep"; }';
	$header[] = '	td:nth-of-type(3):before { content: "Naam"; }';
	$header[] = '	td:nth-of-type(4):before { content: "Index"; }';
	$header[] = '	td:nth-of-type(5):before { content: "Waarde"; }';
	$header[] = '	td:nth-of-type(6):before { content: "Opmerking"; }';
} else {
	$header[] = '	td:nth-of-type(2):before { content: "Naam"; }';
	$header[] = '	td:nth-of-type(3):before { content: "Index"; }';
	$header[] = '	td:nth-of-type(4):before { content: "Waarde"; }';
	$header[] = '	td:nth-of-type(5):before { content: "Opmerking"; }';
}
$header[] = "}";
$header[] = "</style>";	

$tables = array('default', 'table_rot');

echo showCSSHeader($tables, $header);
echo '<div class="content_vert_kolom_full">'.NL;
echo "<form action='". htmlspecialchars($_SERVER['PHP_SELF']) ."' method='post'>";
foreach($block as $id => $blok) {
	echo "<h1>". $configGroups[$id] ."</h1>";
	echo "<div class='content_block'>".NL. implode(NL, $blok).NL."</div>".NL;
}

echo "<p class='after_table'><input type='submit' name='save' value='Opslaan'></p>";	
echo '</form>';
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>