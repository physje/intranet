<?php
require_once('Laposta/Laposta.php');

# https://api.laposta.nl/doc/?lib=php
# https://github.com/laposta/laposta-api-php



function lp_addMember($list, $email, $custom_fields) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);
	
	# initialize member with list_id
	$member = new Laposta_Member($list);
	
	try {
		# create new member, insert info as argument
		# $result will contain een array with the response from the server
		$result = $member->create(array(
			'ip' => $_SERVER['REMOTE_ADDR'],
			'email' => $email,
			'options' => array(
				'suppress_email_notification' => true,
				'suppress_email_welcome' => true,
				'ignore_doubleoptin' => true			
				),
			'custom_fields' => $custom_fields
		));		
		return true;
	} catch (Exception $e) {
		return $output = array('error' => $e);
	}
}



function lp_updateMember($list, $email, $custom_fields) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);
	
	# initialize member with list_id
	$member = new Laposta_Member($list);
	
	try {
		# update member, insert info as argument
		# $result will contain een array with the response from the server
		$result = $member->update($email, array('custom_fields' => $custom_fields));
		return true;
	} catch (Exception $e) {
		return $output = array('error' => $e);
	}
}



function lp_changeMailAddress($list, $oldEmail, $newEmail) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);
	
	# initialize member with list_id
	$member = new Laposta_Member($list);
	
	try {
		# update member, insert info as argument
		# $result will contain een array with the response from the server
		$result = $member->update($oldEmail, array('email' => $newEmail));
		return true;
	} catch (Exception $e) {
		return $output = array('error' => $e);
	}
}



function lp_unsubscribeMember($list, $email) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);
	
	# initialize member with list_id
	$member = new Laposta_Member($list);
	
	try {
		# update member, insert info as argument
		# $result will contain een array with the response from the server				
		$result = $member->update($email, array('state' => 'unsubscribed'));		
		return true;
	} catch (Exception $e) {
		return $output = array('error' => $e);
	}
}



function lp_resubscribeMember($list, $email) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);
	
	# initialize member with list_id
	$member = new Laposta_Member($list);
	
	try {
		# update member, insert info as argument
		# $result will contain een array with the response from the server				
		$result = $member->update($email, array('state' => 'active'));		
		return true;
	} catch (Exception $e) {
		return $output = array('error' => $e);
	}
}



function lp_getMembers($list) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);

	# initialize member with list_id
	$member = new Laposta_Member($list);
	
	try {
		# get member info, use member_id or email as argument
		# $result will contain een array with the response from the server
		$result = $member->all();
					
		print '<pre>';print_r($result);print '</pre>';
	} catch (Exception $e) {
		return $output = array('error' => $e);
	}
}



function lp_onList($list, $mail) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);

	# initialize member with list_id
	$member = new Laposta_Member($list);
	
	try {
		# get member info, use member_id or email as argument		
		$result = $member->get($mail);		
		return true;		
	} catch (Exception $e) {
		return false;
	}
}




function lp_getMemberData($list, $mail) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);

	# initialize member with list_id
	$member = new Laposta_Member($list);
	
	try {
		# get member info, use member_id or email as argument
		# $result will contain een array with the response from the server
		$result = $member->get($mail);		
		return $result['member'];		
	} catch (Exception $e) {
		return $output = array('error' => $e);
	}
}




function lp_createMail($info) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);
	
	# new campaign object
	$campaign = new Laposta_Campaign();
	
	$input['type'] = 'regular';
	$input['name'] = $info['name'];
	$input['subject'] = $info['subject'];
	$input['from'] = $info['from'];
	//$input['reply_to'] = 'reply@example.net';
	$input['list_ids'] = $info['list_ids'];
	//$input['stats'] = array('ga' => 'false','mtrack' => 'false');
	
	try {
		# create new campaign, insert info as argument
		# $result will contain een array with the response from the server
		$result = $campaign->create($input);
		//print '<pre>';print_r($result);print '</pre>';
		return $result['campaign']['campaign_id'];
	} catch (Exception $e) {
		# you can use the information in $e to react to the exception
		//print '<pre>';print_r($e);print '</pre>';
		return false;
	}
}


function lp_populateMail($campaign_id, $html) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);
	
	# new campaign object
	$campaign = new Laposta_Campaign();
		
	try {
		# create new campaign, insert info as argument
		# $result will contain een array with the response from the server
		$result = $campaign->update($campaign_id, array('html' => $html), 'content');
		#print '<pre>';print_r($result);print '</pre>';		
		return true;
	} catch (Exception $e) {
		# you can use the information in $e to react to the exception
		//print '<pre>';print_r($e);print '</pre>';
		return false;
	}
}

function lp_scheduleMail($campaign_id, $time) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);
	
	# new campaign object
	$campaign = new Laposta_Campaign();
		
	try {
		# create new campaign, insert info as argument
		# $result will contain een array with the response from the server
		$result = $campaign->update($campaign_id, array('delivery_requested' => date('Y-m-d H:i', $time)), 'action', 'schedule');
		#print '<pre>';print_r($result);print '</pre>';		
		return true;
	} catch (Exception $e) {
		# you can use the information in $e to react to the exception
		//print '<pre>';print_r($e);print '</pre>';
		return false;
	}
}

?>