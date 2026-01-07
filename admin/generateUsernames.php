<?php
/**
 * Dit script genereert gebruikersnamen en wachtwoorden voor alle actieve leden die dat nog niet hebben (lees : nieuwe leden).
 * En zorgt verder dat van inactieve leden het account op inactief wordt gezet.
 *  
 * @package Intranet KKD
 * @author Matthijs Draijer
 * @version 1.0.0
 */
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once('../Classes/Member.php');
include_once('../Classes/Logging.php');

# Omdat de server deze dagelijks moet draaien wordt toegang niet gedaan op basis
# van naam+wachtwoord maar op basis van IP-adres
if(in_array($_SERVER['REMOTE_ADDR'], $allowedIP)) {
	# Eerst de inactieve verwijderen
	Member::setUsersInactive();
	$text[] = 'Gebruikers inactief gezet';
	
	# Zoeken welke actieve leden nog geen username of hash hebben
	$newUsers = Member::getNewUsers();

	foreach($newUsers as $userID) {
		$user = new Member($userID);

		if($user->username == '') {
			$username = $user->generateUsername();
			$password = generatePassword(8);
			
			$user->username = $username;
			$user->password = password_hash($password, PASSWORD_DEFAULT);
			toLog('Account aangemaakt', '', $user->id);
			$text[] = 'Username aangemaakt voor '.  $user->getName(5) ."($username)<br>";
		}

		if($user->hash_short == '' || $user->hash_long == '') {
			if($user->hash_short == '') {
				$user->hash_short = generateID($lengthShortHash);
			}

			if($user->hash_long == '') {
				$user->hash_long = generateID($lengthLongHash);
			}

			$text[] = 'Hash aangemaakt voor '.  $user->getName(5) ."<br>";
			toLog('Hash aangemaakt', 'debug', $user->id);
		}

		if(!$user->save()) {
			toLog('Problemen met opslaan username en hashed', 'error', $user->id);
		}
	}
} else {
	$text[] = 'Geen toegang vanaf '. $_SERVER['REMOTE_ADDR'];
	toLog('Poging handmatige run gebruikersnamen, IP:'.$_SERVER['REMOTE_ADDR'], 'error');
}

echo showCSSHeader();
echo '<div class="content_vert_kolom_full">'.NL;
echo "<div class='content_block'>".NL. implode(NL, $text).NL."</div>".NL;
echo '</div> <!-- end \'content_vert_kolom_full\' -->'.NL;
echo showCSSFooter();

?>