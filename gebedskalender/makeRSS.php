<?php
include_once('../include/functions.php');
include_once('../include/config.php');
$db = connect_db();

# ronde = 0 : dagelijks
# ronde = 1 : wekelijks
# ronde = 2 : maandelijks

for($ronde=0;$ronde<3;$ronde++) {
	$rss = $description = $item = array();
	if($ronde == 0) {
		$start = date("Y-m-d");
		$eind = date("Y-m-d");
		$filename = 'dag.xml';
	} elseif($ronde == 1) {
		$start = date("Y-m-d");
		$eind = date("Y-m-d", (time()+(6*24*60*60)));
		$filename = 'week.xml';
	} elseif($ronde == 2) {
		$start = date("Y-m-d");
		$eind = date("Y-m-d", mktime(0,0,1,(date("n")+1),date("j"), date("Y")));
		$filename = 'maand.xml';
	}
		
	$punten = getGebedspunten($start, $eind);
	
	$description[] = "		<description><![CDATA[".NL;
		
	if($ronde == 0) {
		$data = getGebedspunt($punten[0]);
		
		$description[] = $data['gebedspunt'].NL;
		$pubDate = $data['unix'];
	} elseif($ronde == 1) {
		$description[] = "		<table>".NL;
	
		foreach($punten as $punt) {
			$data = getGebedspunt($punt);		
			$description[] = "			<tr>".NL;
			$description[] = "				<td valign='top' width='15%'>".strftime("%A", $data['unix']) ."</td>".NL;
			$description[] = "				<td>". $data['gebedspunt'] ."</td>".NL;
			$description[] = "			</tr>".NL;
			
			if((isset($pubDate) AND $pubDate > $data['unix']) OR !isset($pubDate)) {
			    $pubDate = $data['unix'];
			}
		}
		$description[] = "		</table>".NL;
		
	} elseif($ronde == 2) {
		$description[] = "<table>".NL;
	
		foreach($punten as $punt) {
			$data = getGebedspunt($punt);		
			$description[] = "			<tr>".NL;
			$description[] = "				<td valign='top' width='20%'>".strftime("%A %e", $data['unix']) ."</td>".NL;
			$description[] = "				<td>". $data['gebedspunt'] ."</td>".NL;
			$description[] = "			</tr>".NL;
			
			if((isset($pubDate) AND $pubDate > $data['unix']) OR !isset($pubDate)) {
			    $pubDate = $data['unix'];
			}
		}
		$description[] = "</table>".NL;
	}
		
	$description[] = "		]]></description>".NL;
		
	$rss[] = "<rss version=\"0.92\">".NL;
	$rss[] = "<channel>".NL;
	$rss[] = "	<title>Gebedspunten</title>".NL;
	$rss[] = "	<link>http://www.koningskerkdeventer.nl</link>".NL;
	$rss[] = "	<description>RSS-feed met gebedspunten</description>".NL;
	$rss[] = "	<lastBuildDate>".date('D, d M Y H:i:s T') ."</lastBuildDate>".NL;
	
	$rss[] = "	<item>".NL;
	$rss[] = implode("", $description);
	$rss[] = "	<pubDate>".date('D, d M Y H:i:s T', $pubDate) ."</pubDate>".NL;
	$rss[] = "	</item>".NL;
	
	
	$rss[] = "</channel>".NL;
	$rss[] = "</rss>".NL;
	
	$fp = fopen($filename, 'w+');
	fwrite($fp, implode("", $rss));
	fclose($fp);
}