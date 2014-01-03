<?php
/*
 * Orkila Central Online System (OCOS)
 * Copyright © 2009 Orkila International Offshore, All Rights Reserved
 *
 * Get users that has birthday of this Month and  Send  Birthday Notification To affiliate Hr
 * $id: hr_monthly_birthdaynotification.php	
 * Created:	   	@tony.assaad		February 28, 2012 | 4:13 PM
 * Modified:	   	@tony.assaad			March 05, 2012 | 4:06 PM
 */

require_once '../inc/init.php';

$current_date = getdate(TIME_NOW);

$users_query = $db->query("SELECT displayName AS employeeName, u.uid, ae.affid, birthDate, FROM_UNIXTIME(birthDate, '%d') as birthDay, FROM_UNIXTIME(birthDate, '%Y') as birthYear
						   FROM ".Tprefix."users u
							JOIN ".Tprefix."userhrinformation uh ON (u.uid=uh.uid)
							JOIN ".Tprefix."affiliatedemployees ae ON (ae.uid=u.uid)
							WHERE u.gid !=7 AND FROM_UNIXTIME(birthDate, '%c')='{$current_date[mon]}' AND (birthDate IS NOT NULL AND birthDate!=0) AND isMain=1 
							GROUP BY u.uid");

if($db->num_rows($users_query) > 0) {
	while($users_birthdays = $db->fetch_assoc($users_query)) {
		/* create array and withh affid key and in each affiliate add array with  userid key => containing  the sql result value. */
		$birthday_affid[$users_birthdays['affid']][$users_birthdays['uid']] = $users_birthdays;
	}

	$hraffliate_query = $db->query("SELECT affid, name, hrManager, generalManager FROM ".Tprefix."affiliates");
	if($db->num_rows($hraffliate_query) > 0) {
		while($hr_affiliates = $db->fetch_assoc($hraffliate_query)) {
			if(empty($hr_affiliates['hrManager'])) {
				$hr_affiliates['hrManager'] = $hr_affiliates['generalManager'];
			}

			$recepient_details = $db->fetch_assoc($db->query("SELECT uid, displayName, email FROM ".Tprefix."users WHERE uid={$hr_affiliates[hrManager]}"));
			$hr_affid[$recepient_details['uid']][$hr_affiliates['affid']] = $recepient_details;
		}
	}

	foreach($hr_affid as $affuid => $recepient_details) {
		$body_message = '';
		foreach($hr_affid[$affuid] as $affid => $recepient_details) {
			if(is_array($birthday_affid[$affid]) && !empty($birthday_affid[$affid])) {
				foreach($birthday_affid[$affid] as $uid => $user) {
					$body_message .= '<li>'.$user['employeeName'].',  '.date('l jS', mktime(0, 0, 0, $current_date['mon'], $user['birthDay'], $current_date['year'])).' ('.($current_date['year'] - $user['birthYear']).' years old)</li>';
				}
			}
		}
		if(empty($body_message)) {
			continue;
		}

		/* build the email_data array to pass the argument to the mail object */
		$email_data = array(
				'to' => $recepient_details['email'],
				'from_email' => $core->settings['maileremail'],
				'from' => 'OCOS Mailer',
				'subject' => 'Employee birthdays during '.$current_date['month'],
				'message' => 'Hello '.$recepient_details['displayName'].',<br />The Following birthdays are taking during '.$current_date['month'].'</br></br />'.$body_message
		);

		$mail = new Mailer($email_data, 'php');
		if($mail->get_status() === true) {
			$log->record('hrbirthdaynotification', array('to' => $recepient_details['email']), 'emailsent');
		}
		else {
			$log->record('hrbirthdaynotification', array('to' => $recepient_details['email']), 'emailnotsent');
		}
	}
}
?>