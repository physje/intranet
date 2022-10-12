<?php
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('shared.php');

$db = connect_db();

if(time() < $sluiting) {
    if(isset($_REQUEST['token'])) {
	    if(validVotingCode($_REQUEST['token'])) {
		    if(uniqueVotingCode($_REQUEST['token'])) {
			    if(isset($_POST['save'])) {				
				    $sql_token = "UPDATE `votingcodes` SET `time` = ". time().", `keuze` = '". $_POST['keuze'] ."' WHERE `votingtoken` LIKE '". $_POST['token'] ."'";
				
    				if(mysqli_query($db, $sql_token)) {
	    				$text[] = 'Dank voor het uitbrengen van uw stem';
		    		} else {
			    		$text[] = 'Helaas kon uw stem niet worden weggeschreven';
    				}
	    		} else {
		    		$text[] = "<form action='stemming.php' method='post'>";
			    	$text[] = "<input type='hidden' name='token' value='". $_REQUEST['token'] ."'>";
    				$text[] = "Staat u achter het voorstel ds. Reinier Kramer te beroepen?<br>";
	    			$text[] = "<br>";
				
		    		foreach($opties as $id => $naam) {
			    		#$text[] = "<input type='radio' name='keuze' value='$id'".($preKeuze == $id ? ' checked' : '') ."> $naam<br>";
				    	$text[] = "<input type='radio' name='keuze' value='$id'> $naam<br>";
    				}			
				
	    			$text[] = "<br>";
		    		$text[] = "<input type='submit' name='save' value='Stem uitbrengen'><br>";
			    	$text[] = "</form>";
    			}
	    	} else {
		    	$text[] = 'Deze stem is al een keer uitgebracht';
    		}		
	    } else {
		    $text[] = 'Dit is geen geldige stem-link';
	    }
    } else {
	    $text[] = 'Dit is geen stem-link';
    }
} else {
    $text[] = 'De stemming is gesloten sinds '. time2str('%e %B %H:%M', $sluiting);
}

echo $HTMLHeader;
echo implode(NL, $text);
echo $HTMLFooter;

?>