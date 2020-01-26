<?php
require_once('laposta/Laposta.php');

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
		return array('error' => $e);
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
		return array('error' => $e);
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
		return array('error' => $e);
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
		return array('error' => $e);
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
		return array('error' => $e);
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
		return array('error' => $e);
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
		return array('error' => $e);
	}
}



# $input['name'] (verplicht)
# $input['subject'] (verplicht)
# $input['from']['name'] (verplicht)
# $input['from']['email'] (verplicht)
# $input['reply_to']
# $input['list_ids'] (verplicht)
# $input['stats']['ga'] = false
# $input['stats']['mtrack'] = false
function lp_createMail($input) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);
	
	# new campaign object
	$campaign = new Laposta_Campaign();
	
	$input['type'] = 'regular';
		
	try {
		# create new campaign, insert info as argument
		# $result will contain een array with the response from the server
		$result = $campaign->create($input);
		return $result['campaign']['campaign_id'];
	} catch (Exception $e) {
		return array('error' => $e);
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
		return true;
	} catch (Exception $e) {
		return array('error' => $e);
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
		return true;
	} catch (Exception $e) {
		return array('error' => $e);
	}
}


# $info['name'] (verplicht)
# $info['remarks']
# $info['subscribe_notification_email']
# $info['unsubscribe_notification_email']
function lp_createList($info) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);

	$list = new Laposta_List();
	
	try {
		$result = $list->create($info);
		return $result['list']['list_id'];
	} catch (Exception $e) {
		return array('error' => $e);
	}
}


# $data['name']			(verplicht)
# $data['defaultvalue']
# $data['datatype']	(verplicht)
# $data['datatype_display']
# $data['options']
# $data['required'] (verplicht)
# $data['in_form']	(verplicht)
# $data['in_list']	(verplicht)
function lp_addFieldToList($list, $data) {
	global $LaPostaAPIKey;
	Laposta::setApiKey($LaPostaAPIKey);
	Laposta::setHttpsDisableVerifyPeer(true);
	
	$field = new Laposta_Field($list);
	
	try {
		$result = $field->create($data);		
		return true;		
	} catch (Exception $e) {
		return array('error' => $e);
	}
}

?>