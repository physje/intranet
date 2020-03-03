#!/usr/local/bin/php -q
<?
error_reporting(E_ALL);
ini_set("display_errors", 1);
include ('/home/draije1a/public_html/extern/3GK/intranet/include/functions.php');
include ('/home/draije1a/public_html/extern/3GK/intranet/include/config.php');
include ('/home/draije1a/public_html/extern/3GK/intranet/include/LP_functions.php');
include ('/home/draije1a/public_html/extern/3GK/intranet/include/PlancakeEmailParser.php');

$test = true;

if(!$test) {
	/* Read the message from STDIN */
	$fd = fopen("php://stdin", "r");
	$email = ""; // This will be the variable holding the data.
	while (!feof($fd)) {
		$email .= fread($fd, 2048);
	}
	fclose($fd);
} else {
	$filename = 'mail_met_bijlage.txt';
	$handle = fopen($filename, 'r');
	$email = fread($handle, filesize($filename));
	fclose($handle);
}

$emailParser = new PlancakeEmailParser($email);

//echo '<br>getPlainBody : '. $emailParser->getPlainBody();
//echo '<br>getHTMLBody : '. $emailParser->getHTMLBody();
//echo '<br>getTo : '. implode('|', $emailParser->getTo());
//echo '<br>getSubject : '. $emailParser->getSubject();
//echo '<br>getCc : '. implode('|', $emailParser->getCc());

$data['bijlage']	= false;
$data['html']			= $emailParser->getHTMLBody();
$data['plain']		= $emailParser->getPlainBody();
$data['subject']	= $emailParser->getSubject();
//$data['fromName']
//$data['fromAdres']
//$data['toName']
//$data['toAdres']

//$data = parseMail($email);

# Als er een bijlage bijzit gaat het hele feest niet door
if(!isset($data['bijlage'])) {	
	$ontvanger = getString('', '@', $data['toAdres']);
	
	if($ontvanger[0] == 'wijka') {
		$laPostaGroup = 'wzkffisyod';
		$Name = 'Wijkmail A';
	} elseif($ontvanger[0] == 'wijkb') {
		$laPostaGroup = 'wzkffisyod';
		$Name = 'Wijkmail B';
	} elseif($ontvanger[0] == 'wijkc') {
		$laPostaGroup = 'wzkffisyod';
		$Name = 'Wijkmail C';
	} elseif($ontvanger[0] == 'wijkd') {
		$laPostaGroup = 'wzkffisyod';
		$Name = 'Wijkmail D';
	} elseif($ontvanger[0] == 'wijke') {
		$laPostaGroup = 'wzkffisyod';
		$Name = 'Wijkmail E';
	} elseif($ontvanger[0] == 'wijkf') {
		$laPostaGroup = 'wzkffisyod';
		$Name = 'Wijkmail F';
	} elseif($ontvanger[0] == 'wijkg') {
		$laPostaGroup = 'wzkffisyod';
		$Name = 'Wijkmail G';
	} elseif($ontvanger[0] == 'wijkh') {
		$laPostaGroup = 'wzkffisyod';
		$Name = 'Wijkmail H';
	} elseif($ontvanger[0] == 'wijki') {
		$laPostaGroup = 'wzkffisyod';
		$Name = 'Wijkmail I';
	} elseif($ontvanger[0] == 'wijkj') {
		$laPostaGroup = 'wzkffisyod';
		$Name = 'Wijkmail J';
	}
	
	
	if(lp_onList($laPostaGroup, $data['fromAdres'])) {
		//echo 'Onderwerp : '. $data['subject'] .'<br>';
		//echo 'From : '. $data['from'] .'<br>';
		//echo 'To : '. $data['to'] .'<br>';
		//echo 'Bericht : '. $data['plain'];
		
		$input['name'] = $Name .' '. date('d.m.y');
		$input['subject'] = $data['subject'];
		$input['from']['name'] = $data['fromName'];
		$input['from']['email'] = 'matthijs.draijer@koningskerkdeventer.nl';
		$input['list_ids'] = array($laPostaGroup);
		//$input['stats']['ga'] = false;
		//$input['stats']['mtrack'] = false;
		$campaignID = lp_createMail($input);
			
		if(is_array($campaignID)) {
			mail('matthijs@draijer.org', $data['subject'], $campaignID['error']);
		} else {
			if(isset($data['html'])) {
				$bericht = $data['html'];
			} else {
				$bericht = $data['plain'];
			}
			if(lp_populateMail($campaignID, $bericht)) {
				$verzendtijd = time()+3;
				//lp_scheduleMail($campaignID, $verzendtijd);
			}
			//mail('matthijs@draijer.org', $data['subject'], $email);
		}
	} else {
		mail($data['fromAdres'], 'Re: '. $data['subject'], 'Het adres '. $data['fromAdres'] .' is niet bekend als geldig adres als afzender.');
	}
} else {
	mail($data['fromAdres'], 'Re: '. $data['subject'], 'Het lijkt erop dat je een bijlage probeert te sturen. Dat wordt niet ondersteund in LaPosta. Je mail is dus niet verstuurd.');
}


#
# FUNCTIONS
#

/*
function parseMail($input) {
	$mail = trim($input);
		
	$found = strpos_recursive($mail, 'boundary="');
	
	if(count($found) > 1) {
		$out['bijlage'] = true;
		$temp = getString('', '--=', $mail);
		$header = $temp[0];
	} else {
		$regels = explode("\r\n", $mail);
		
		if(count($regels) < 5) {
			$regels = explode("\n", $mail);
		}
		
		if(count($found) == 1) {			
			$laatsteRegel = array_pop($regels);		
			$scheidingsString = substr($laatsteRegel, 0, -2);
			
			//echo '|'. $scheidingsString .'|';
			
			$skip = false;
			$i = 0;
			
			foreach($regels as $regel) {
				if($regel == $scheidingsString) {
					$i++;
					$skip = true;
				}
				
				if(!$skip) {
					$mailDelen[$i][] = $regel;
				}
						
				if($regel == '') {
					$skip = false;
				}
			}
			
			$header			= implode("\n",$mailDelen[0]);
			$mailPlain	= implode("\n",$mailDelen[1]);
			$mailHTML		= implode("\n",$mailDelen[2]);
			
			$out['delim']		= $scheidingsString;
			$out['plain']		= trim($mailPlain);
			$out['html'] 		= cleanMail(trim($mailHTML));						
		} else {
			$eigenlijkeMail = false;			
			$mailPlain = $header = '';
			
			foreach($regels as $regel) {
				if($regel == '') {
					$eigenlijkeMail = true;
				}
				
				if($eigenlijkeMail) {
					$mailPlain .= $regel ."\n";
				} else {
					$header .= $regel ."\n";
				}				
			}
					
			$out['plain']		= trim($mailPlain);			
		}
	}
		
	$from			= getString('From:', "\n", $header);
	$to				= getString('To:', "\n", $header);
	$subject	= getString('Subject:', "\n", $header);
	
	$toArray = seperateAdress(trim($to[0]));	
	$fromArray = seperateAdress(trim($from[0]));
	
	$out['to']			= trim($to[0]);
	$out['toAdres']	= $toArray['adres'];			
	if(count($toArray) == 2) {
		$out['toName'] = $toArray['naam'];		
	}
	
	$out['fromAdres'] = $fromArray['adres'];	
	$out['from']			= trim($from[0]);		
	if(count($fromArray) == 2) {
		$out['fromName'] = $fromArray['naam'];
	}
	
	$out['subject'] = trim($subject[0]);
		
	return $out;
}


function cleanMail($input) {
	$cleanMail = $input;
	
	$cleanMail = str_replace("=\r\n", '', $cleanMail);
	$cleanMail = str_replace("=\n", '', $cleanMail);
	$cleanMail = str_replace("=21", '!', $cleanMail);
	$cleanMail = str_replace("=22", '"', $cleanMail);
	$cleanMail = str_replace("=23", '#', $cleanMail);
	$cleanMail = str_replace("=3D", '=', $cleanMail);
	$cleanMail = str_replace("=7B", '{', $cleanMail);
	$cleanMail = str_replace("=7D", '}', $cleanMail);
	$cleanMail = str_replace("=C3=A9", 'é', $cleanMail);
	
	return $cleanMail;
}
*/

function getString($start, $end, $string) {
	if ($start != '') {
		$startPos = strpos ($string, $start) + strlen($start);
	} else {
		$startPos = 0;
	}
	
	if ($end != '') {
		$eindPos	= strpos ($string, $end, $startPos);
	} else {
		$eindPos = strlen($string);
	}
		
	$text	= substr ($string, $startPos, $eindPos-$startPos);
	$rest	= substr ($string, $eindPos);
		
	return array($text, $rest);
}


function seperateAdress($address) {
	$out = array();
	
	$delen = explode('<', $address);
	
	if(count($delen) == 1) {
		$out['adres'] = trim($address);
	}	elseif(count($delen) == 2) {
		$temp = getString('<', '>', $address);
		
		$out['naam'] = trim($delen[0]);		
		$out['adres'] = trim($temp[0]);
	}	else {
		return false;
	}
	
	return $out;
}

/*
function strpos_recursive($haystack, $needle, $offset = 0, &$results = array()) {
	$offset = strpos($haystack, $needle, $offset);
	if($offset === false) {
		return $results;
	} else {
		$results[] = $offset;
		return strpos_recursive($haystack, $needle, ($offset + 1), $results);
   }
}
*/

?>