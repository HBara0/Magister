<?php
/*
 * Orkila Central Online System (OCOS)
 * Copyright © 2009 Orkila International Offshore, All Rights Reserved
 * 
 * Survey List
 * $module: attendance
 * $id: surveysList.php	
 * Created: 		@tony.assaad	May 8, 2012 | 12:00 PM
 * Last updated:	@zaher.reda		July 17, 2012 | 10:17 AM
 */

if(!defined('DIRECT_ACCESS')) {
	die('Direct initialization of this file is not allowed.');
}

if(!$core->input['action']) {
	if(!empty($core->input['identifier'])) {
		$sort_url = sort_url();
		$newsurvey = new Surveys($core->input['identifier']);
		$survey = $newsurvey->get_survey();

		$survey['invitations'] = $newsurvey->get_invitations();

		if(($survey['isPublicResults'] == 1 || $survey['createdBy'] == $core->user['uid']) || ($survey['isPublicResults'] == 1 && $survey['isPublicFill'] == 0 && $newsurvey->check_invitation()) || value_exists('surveys_associations', 'id', $core->user['uid'], 'attr="uid" AND sid='.$survey['sid'])) {
			$responses_stats = $newsurvey->get_responses_stats();

			if(is_array($responses_stats)) {
				foreach($responses_stats as $stqid => $question) {
					$pie_data['titles'] = $question['choices']['choice'];
					$pie_data['values'] = $question['choices']['stats'];

					foreach($question['choices']['choice'] as $stqcid => $choice) {
						/* Exit the loop if stats value = 0 */
						if($question['choices']['stats'][$stqcid] == 0 || empty($question['choices']['value'][$stqcid])) {
							continue;
						}
						/* Count of answers for a choice in the question Array */
						$count[$stqid] += $question['choices']['stats'][$stqcid];
						/* Weight is  value of the choice for each question */
						$weighted_sum[$stqid] += ($question['choices']['stats'][$stqcid] * $question['choices']['value'][$stqcid]);
					}

					if(!empty($count[$stqid])) {
						$question['average'] = round($weighted_sum[$stqid] / $count[$stqid], 2);
						$lang->questionaverage_output = '('.$lang->questionaverage.' '.$question['average'].')';
					}
					else {
						$lang->questionaverage_output = $question['average'] = '';
					}
					$pie = new Charts(array('x' => $pie_data['titles'], 'y' => $pie_data['values']), 'bar', array('scale' => SCALE_START0, 'noLegend' => true));
					//$pie = new Charts(array('titles' => $pie_data['titles'], 'values' => $pie_data['values']), 'pie');
					//$chart = '<img src='.$pie->get_chart().' />';
					if($survey['createdBy'] == $core->user['uid']) {
						$chart = '<a href="#question_'.$stqid.'" id="getquestionresponses_'.$stqid.'_'.$survey['identifier'].'"><img src='.$pie->get_chart().' border="0" /></a>';
					}
					else {
						$chart = '<img src='.$pie->get_chart().' />';
					}
					eval("\$questionsstats .= \"".$template->get('surveys_results_questionstat')."\";");
				}
			}
		}

		if($survey['createdBy'] == $core->user['uid']) {
			/* Show resposne list - START */
			$surveys_responses = $newsurvey->get_survey_distinct_responses('', array('sortby' => $core->input['sorbtby'], 'order' => $core->input['order']));

			if(is_array($surveys_responses)) {
				foreach($surveys_responses as $response) {
					$rowclass = alt_row($rowclass);
					$response['time_output'] = date($core->settings['dateformat'].' '.$core->settings['timeformat'], $response['time']);
					if($survey['anonymousFilling'] == 1) {
						$response['respondant'] = ' - ';
						$response['uid'] = '';
					}

					eval("\$responses_rows .= \"".$template->get('surveys_results_responses_row')."\";");
				}
				eval("\$responses = \"".$template->get('surveys_results_responses')."\";");
			}
			else {
				$responses = ' <div class="ui-state-highlight ui-corner-all" style="padding-left: 5px; margin-bottom: 10px;"><p>'.$lang->noresponses.'</p></div>';
			}
			/* END resposne list - START */
			
			/* Parse Invitations Section - START */
			$query = $db->query("SELECT DISTINCT(u.uid), u.*, aff.*, displayName, aff.name AS mainaffiliate, aff.affid
							FROM ".Tprefix."users u JOIN ".Tprefix."affiliatedemployees ae ON (u.uid=ae.uid) JOIN ".Tprefix."affiliates aff ON (aff.affid=ae.affid)
							WHERE gid!='7' AND isMain='1'
							ORDER BY displayName ASC");

			if($db->num_rows($query) > 0) {
				while($user = $db->fetch_assoc($query)) {
					$rowclass = alt_row($rowclass);

					$userpositions = $hiddenpositions = $break = '';

					$user_positions = $db->query("SELECT p.* FROM ".Tprefix."positions p LEFT JOIN ".Tprefix."userspositions up ON (up.posid=p.posid) WHERE up.uid='{$user[uid]}' ORDER BY p.name ASC");
					$positions_counter = 0;

					while($position = $db->fetch_assoc($user_positions)) {
						if(!empty($lang->{$position['name']})) {
							$position['title'] = $lang->{$position['name']};
						}

						if(++$positions_counter > 2) {
							$hidden_positions .= $break.$position['title'];
						}
						else {
							$userpositions .= $break.$position['title'];
						}
						$break = '<br />';
					}

					if($positions_counter > 2) {
						$userpositions = $userpositions.", <a href='#' id='showmore_positions_{$user[uid]}'>...</a> <span style='display:none;' id='positions_{$user[uid]}'>{$hidden_positions}</span>";
					}

					/* Get User Segments - START */
					$user_segments_query = $db->query("SELECT es.*, u.uid ,u.username, ps.title, ps.psid 
												FROM ".Tprefix."employeessegments es
												JOIN ".Tprefix."users u ON (es.uid=u.uid) 
												JOIN ".Tprefix."productsegments ps ON (ps.psid=es.psid)
												WHERE es.uid='{$user[uid]}'
												ORDER BY title ASC");

					while($segment = $db->fetch_assoc($user_segments_query)) {
						$segment_counter = 0;
						$usersegments = $break = '';
						if(++$segment_counter > 2) {
							$hidden_segments .= $break.$segment['title'];
						}
						else {
							$usersegments = $break.$segment['title'];
						}
						$break = '<br />';
					}

					if($segment_counter > 2) {
						$usersegments .= ", <a href='#' id='showmore_segments_{$user[uid]}'>...</a> <span style='display:none;' id='segments_{$user[uid]}'>{$hidden_segments}</span>";
					}
					$checked = '';

					if($newsurvey->check_invitation($user['uid'])) {
						$rowclass = 'greenbackground';
						$checked = ' checked="checked"';
					}
					eval("\$invitations_row .= \"".$template->get('surveys_createsurvey_invitationrows')."\";");
				}
				eval("\$invitations .= \"".$template->get('surveys_results_invitations')."\";");
			}
			/* Parse Invitations Section - END */
		}

		eval("\$surveys_viewresults = \"".$template->get('surveys_results')."\";");
		output_page($surveys_viewresults);
	}
	else {
		redirect($_SERVER['HTTP_REFERER']);
	}
}
else {
	if($core->input['action'] == 'get_questionresponses') {
		$newsurvey = new Surveys($core->input['identifier']);
		$survey = $newsurvey->get_survey();

		if($survey['createdBy'] != $core->user['uid']) {
			exit;
		}

		$questions_responses = $newsurvey->get_question_responses($core->input['question']);
		if(is_array($questions_responses)) {
			$responses_details_output = '<table class="datatable"><tr class="altrow"><th>#</th><th>'.$lang->choices.'</th><th>&nbsp;</th></tr>';
			foreach($questions_responses as $responses) {
				$responses_details_output .= '<tr><td style="width:10%;"><a href="index.php?module=surveys/viewresponse&amp;identifier='.$responses['identifier'].'" target="_blank" >'.$responses['identifier'].'</a></td><td style="width:40%;">'.implode(', ', $responses['choices']).'</td><td>'.$responses['comments'].'</td></tr>';
			}
			$responses_details_output .= '</table>';
		}
		else {
			$responses_details_output = $lang->na;
		}
		echo $responses_details_output;
	}
	elseif($core->input['action'] == 'sendreminders') {
		$survey_identifier = $db->escape_string($core->input['identifier']);
		$newsurvey = new Surveys($survey_identifier);
		$survey = $newsurvey->get_survey();
		
		if($survey['createdBy'] != $core->user['uid']) {
			exit;
		}
		
		$survey['invitations'] = $newsurvey->get_invitations();

		foreach($survey['invitations'] as $invitee) {
			if(($invitee['isDone']) != 1) {
				/* preparing reminder email */
				$surveylink = DOMAIN.'/index.php?module=surveys/fill&amp;identifier='.$survey_identifier;
				if($survey['isExternal'] == 1) {
					$surveylink = 'http://www.orkila.com/surveys/'.$survey_identifier.'/'.$invitee['identifier'];
					$invitee['displayName'] = split('@', $invitee['invitee'])[0];
					
					$email_data = array(
							'to' => $invitee['invitee'],
							'from_email' => $core->user['email'],
							'from' => $core->user['displayName'],
							'subject' => $lang->survey_reminder_subject
					);
				}
				else {
					$email_data = array(
							'to' => $invitee['email'],
							'from_email' => $core->settings['maileremail'],
							'from' => 'OCOS Mailer',
							'subject' => $lang->survey_reminder_subject
					);
				}

				$email_data['message'] = $lang->sprint($lang->survey_reminder_message, $invitee['displayName'], $survey['subject'], $surveylink);
				$mail = new Mailer($email_data, 'php');
			}
		}

		if($mail->get_status() === true) {
			output_xml("<status>true</status><message>{$lang->remindersent}</message>");
			exit;
		}
	}
}
?>