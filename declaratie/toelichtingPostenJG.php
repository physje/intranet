<?php
include_once('../include/functions.php');
include_once('../include/config.php');

$db = connect_db();

foreach($declJGKop as $id => $kop) {
	$page = array();
	
	foreach($declJGPost[$id] as $post_nr => $titel) {
		$item  = "<b>$titel</b><br>";
		$item .= $declJGToelichting[$post_nr];
		
		$page[] = $item;
	}
	
	$block[$id] = implode('<br><br>', $page);
}

# Pagina tonen
$pageTitle = 'Posten cluster Jeugd & Gezin';
include_once('../include/HTML_TopBottom.php');

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;

foreach($block as $id => $content) {
	echo "<h1>". $declJGKop[$id] ."</h1>";
	echo "<div class='content_block'>".NL. $content .NL."</div>".NL;
}
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>
