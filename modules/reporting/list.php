<?php
/*
 * Orkila Central Online System (OCOS)
 * Copyright © 2009 Orkila International Offshore, All Rights Reserved
 * 
 * Lists available reports
 * $module: reporting
 * $id: listreports.php	
 * Last Update: @zaher.reda 	July 21, 2011 | 10:22 PM
 */
 
if(!defined('DIRECT_ACCESS'))
{
	die('Direct initialization of this file is not allowed.');
}

if($core->usergroup['canGenerateReports'] == 0) {
	error($lang->sectionnopermission);
	exit;
}

if(!$core->input['action']) {
	$sort_query = 'year DESC, quarter DESC';
	if(isset($core->input['sortby'], $core->input['order'])) {
		$sort_query = $core->input['sortby'].' '.$core->input['order'];
	}
	$sort_url = sort_url();
	
	$limit_start = 0;
	if(isset($core->input['start'])) {
		$limit_start = $db->escape_string($core->input['start']);
	}
	
	
	//if(isset($core->user['auditfor'])) {
	/*	$extra_where = ' AND ';
		foreach($core->user['suppliers']['eid'] as $val) {
			if(in_array($val, $core->user['auditfor'])) {
				$inaffiliates = implode(',', $core->user['auditedaffiliates'][$val]);
			}
			else
			{
				$inaffiliates = implode(',', $core->user['suppliers']['affid'][$val]);
			}
			$extra_where .= $query_or.'(r.spid='.$val.' AND r.affid IN ('.$inaffiliates.'))';
			$multipage_where .= $query_or.'(spid='.$val.' AND affid IN ('.$inaffiliates.'))';
			$query_or = ' OR ';
		}*/
	//}
	/*else
	{
		if($core->usergroup['canViewAllAff'] == 0) {
			$inaffiliates = implode(',', $core->user['affiliates']);
			$extra_where = ' AND r.affid IN ('.$inaffiliates.') ';
			$multipage_where = 'affid IN ('.$inaffiliates.')';
			$query_and = ' AND ';
		}
	
		if($core->usergroup['canViewAllSupp'] == 0) {
			$insuppliers = implode(',', $core->user['suppliers']['eid']);
			$extra_where .= '  AND r.spid IN ('.$insuppliers.') ';	  
			$multipage_where .= $query_and.'spid IN ('.$insuppliers.')';
		}
	}*/
	
	if(isset($core->input['perpage']) && !empty($core->input['perpage'])) {
		$core->settings['itemsperlist'] = $db->escape_string($core->input['perpage']);
	}
	$extra_where = getquery_entities_viewpermissions();
	
	if(!empty($extra_where['multipage'])) {
		$and = ' AND ';
	}
	//$extra_where['multipage'] = 'r.type="q"'.$and.$extra_where['multipage'];
		
	if(isset($extra_where['byspid'][$core->input['filtervalue']])) {
		$extra_where['multipage'] = 'r.type="q"'.$extra_where['byspid'][$core->input['filtervalue']];
	}
	else
	{
		$extra_where['multipage'] = 'r.type="q"'.$and.$extra_where['multipage'];
	}
		
	if(isset($core->input['filterby'], $core->input['filtervalue'])) {
		$extra_where['multipage'] = $db->escape_string($core->input['filterby']).'='.$db->escape_string($core->input['filtervalue']).' AND '.$extra_where['multipage'];
		
		$filterby_prefix = '';
		if($core->input['filterby'] == 'affid') { $filterby_prefix = 'a.'; }
		$filter_where = ' AND '.$filterby_prefix.$db->escape_string($core->input['filterby']).'='.$db->escape_string($core->input['filtervalue']);
	}
	
	$query = $db->query("SELECT r.*, a.affid AS affiliate, a.name AS affiliatename, r.spid AS supplier, s.companyName AS suppliername
						 FROM ".Tprefix."reports r JOIN ".Tprefix."affiliates a ON (a.affid=r.affid) JOIN ".Tprefix."entities s ON (r.spid=s.eid)
						 WHERE r.type='q'{$filter_where}{$extra_where[extra]}
						 ORDER BY {$sort_query}
						 LIMIT {$limit_start}, {$core->settings[itemsperlist]}");
	$filters_required = array('quarter', 'year', 'affid', 'spid');
	$filters_cache = array();
	
	if($db->num_rows($query) > 0) {
		while($report = $db->fetch_assoc($query)) {
			if($report['status'] == 1) {
				$icon_locked = '';
				if($report['isLocked'] == 1) { 
					$icon_locked = '_locked';
				}
				$icon[$report['rid']] = "<a href='index.php?module=reporting/preview&referrer=list&amp;affid={$report[affid]}&amp;spid={$report[spid]}&amp;quarter={$report[quarter]}&amp;year={$report[year]}'><img src='images/icons/report{$icon_locked}.gif' alt='{$report[status]}' border='0'/></a>";
			}
			
			foreach($filters_required as $key) {
				if(!is_array($filters_cache[$key])) {
					$filters_cache[$key] = array();
				}
				
				if(!in_array($report[$key], $filters_cache[$key])) {
					$filters[$key][$report[$key]] = '<a href="index.php?module=reporting/list&filterby='.$key.'&filtervalue='.$report[$key].'"><img src="./images/icons/search.gif" border="0" alt="'.$lang->filterby.'"/></a>';
					$filters_cache[$key][] = $report[$key];
				}
				else
				{
					$filters[$key][$report[$key]] = '';
				}
			}
			
			$report['status'] = parse_status($report['status'], $report['isLocked']);
			$report['statusdetails'] = parse_statusdetails(array('prActivityAvailable' => $report['prActivityAvailable'], 'keyCustAvailable' => $report['keyCustAvailable'], 'mktReportAvailable' => $report['mktReportAvailable']));
			
			if($core->usergroup['canLockUnlockReports'] == 1 || $core->usergroup['reporting_canApproveReports'] == 1) {	
				$checkbox[$report['rid']] = "<input type='checkbox' id='checkbox_{$report[rid]}' name='listCheckbox[]' value='{$report[rid]}'/>";
			}

			$rowclass = '';
			if($report['isApproved'] == 0) {
				$rowclass = 'unapproved';
			}
			if($report['isSent'] == 1) {
				$rowclass = 'greenbackground';
				if($report['isApproved'] == 0) {
					$rowclass = 'yellowbackground';
				}
			}
			eval("\$reportslist .= \"".$template->get('reporting_reportslist_reportrow')."\";");
		}
		
		$multipages = new Multipages('reports r', $core->settings['itemsperlist'], $extra_where['multipage']);
			
		if($core->usergroup['canReadStats'] == 1) {
			$stats_link = "<a href='index.php?module=reporting/stats'><img src='images/icons/stats.gif' alt='{$lang->reportsstats}' border='0'></a>";
		}
	
		$reportslist .= "<tr><td colspan='5'>".$multipages->parse_multipages()."&nbsp;</td><td style='text-align: right;' colspan='2'><a href='".$_SERVER['REQUEST_URI']."&amp;action=exportexcel'><img src='images/icons/xls.gif' alt='{$lang->exportexcel}' border='0' /></a>&nbsp;{$stats_link}</td></tr>";
		if($core->usergroup['canLockUnlockReports'] == 1 || $core->usergroup['reporting_canApproveReports'] == 1) {	
			$moderationtools = "<tr><td colspan='3'>";
			$moderationtools .= "<div id='moderation_reporting/list_Results'></div>&nbsp;";
			
			$moderationtools .= "</td><td style='text-align: right;' colspan='4'><strong>{$lang->moderatintools}:</strong> <select name='moderationtool' id='moderationtools'>";
			$moderationtools .= "<option value='' selected>&nbsp;</option>";
			if($core->usergroup['canLockUnlockReports'] == 1) {
				$moderationtools .= "<option value='lock'>{$lang->lock}</option>";
				$moderationtools .= "<option value='unlock'>{$lang->unlock}</option>";
				$moderationtools .= "<option value='lockunlock'>{$lang->lockunlock}</option>";
			}
			if($core->usergroup['reporting_canApproveReports'] == 1) {
				$moderationtools .= "<option value='approveunapprove'>{$lang->approveunapprove}</option>";
				$moderationtools .= "<option value='finalize'>{$lang->approveunapprove}Finalize</option>";
			}
			
			$moderationtools .= "</select></td></tr>";
		}
	}
	else
	{
		$reportslist = "<tr><td colspan='6' align='center'>{$lang->noreportsavailable}</td></tr>";
	}
	
	eval("\$listpage = \"".$template->get('reporting_reportslist')."\";");
	output_page($listpage);
}
else
{
	if($core->input['action'] == 'get_status') {
		if(empty($core->input['rid'])) {
			exit;	
		}
		$extra_where = getquery_entities_viewpermissions();
		
		$report = $db->fetch_assoc($db->query("SELECT affid, spid, prActivityAvailable, keyCustAvailable, mktReportAvailable
					 FROM ".Tprefix."reports
					 WHERE type='q' AND rid=".$db->escape_string($core->input['rid']).$extra_where['extra']));

		echo parse_statusdetails(array('prActivityAvailable' => $report['prActivityAvailable'], 'keyCustAvailable' => $report['keyCustAvailable'], 'mktReportAvailable' => $report['mktReportAvailable']));
	}
	elseif($core->input['action'] == 'do_moderation') {
		if($core->input['moderationtool'] == 'lock' || $core->input['moderationtool'] == 'unlock' || $core->input['moderationtool'] == 'lockunlock') {
			if($core->usergroup['canLockUnlockReports'] == 1) {	
				if(count($core->input['listCheckbox']) > 0) {
					if($core->input['moderationtool'] == 'lock') { $new_status['isLocked']= 1; }
					if($core->input['moderationtool'] == 'unlock') { $new_status['isLocked'] = 0; }
						
					foreach($core->input['listCheckbox'] as $key => $val) {
						$rid = $db->escape_string($val);

						if($core->input['moderationtool'] == 'lockunlock') {
							list($current_status) = get_specificdata('reports', array('isLocked'), '0', 'isLocked', '', 0, "rid='{$rid}'");
							if($current_status == 0) { $new_status['isLocked'] = 1; } else { $new_status['isLocked'] = 0; }
						}
						
						if($new_status['isLocked'] == 0) {
							$new_status['status'] = 0;
						}
						
						$db->update_query('reports', $new_status, "rid='{$rid}'");
					}
					output_xml("<status>true</status><message>{$lang->lockchanged}</message>");
					$log->record($core->input['listCheckbox'], $core->input['moderationtool']); 
				}
				else
				{
					output_xml("<status>false</status><message>{$lang->selectatleastonereport}</message>"); 
				}
			}
		}
		elseif($core->input['moderationtool'] == 'approveunapprove') {
			if($core->usergroup['reporting_canApproveReports'] == 1) {
				if(count($core->input['listCheckbox']) > 0) {
					foreach($core->input['listCheckbox'] as $key => $val) {
						$rid = $db->escape_string($val);
						list($current_status) = get_specificdata('reports', array('isApproved'), '0', 'isApproved', '', 0, "rid='{$rid}'");
		
						if($current_status == 0) { $new_status['isApproved'] = 1; } else { $new_status['isApproved'] = 0; }

						$db->update_query('reports', $new_status, "rid='{$rid}'");
					}
					output_xml("<status>true</status><message>{$lang->reportsapproved}</message>"); 
					$log->record($core->input['listCheckbox'], $core->input['moderationtool']);
				}
				else
				{
					output_xml("<status>false</status><message>{$lang->selectatleastonereport}</message>"); 
				}
			}
		}
		elseif($core->input['moderationtool'] == 'finalize') {
			if(count($core->input['listCheckbox']) > 0) {
				foreach($core->input['listCheckbox'] as $key => $val) {
					$rid = $db->escape_string($val);

					$db->update_query('reports', array('status' => 1, 'isLocked' => 1), "rid='{$rid}'");
				}
				output_xml("<status>true</status><message>{$lang->reportsapproved}</message>"); 
				$log->record($core->input['listCheckbox'], $core->input['moderationtool']);
			}
		}
	}
	elseif($core->input['action'] == 'exportexcel') {
		$sort_query = 'quarter, year DESC';
		if(isset($core->input['sortby'], $core->input['order'])) {
			$sort_query = $core->input['sortby'].' '.$core->input['order'];
		}
	
		if($core->usergroup['canViewAllAff'] == 0) {
			$inaffiliates = implode(',', $core->user['affiliates']);
			$extra_where = ' AND a.affid IN ('.$inaffiliates.') ';
		}
		if($core->usergroup['canViewAllSupp'] == 0) {
			$insuppliers = implode(',', $core->user['suppliers']);
			$extra_where .= ' AND r.spid IN ('.$insuppliers.') ';
			  
		}	

		$query = $db->query("SELECT a.name AS affiliatename, s.companyName AS suppliername, r.quarter, r.year, r.status, r.isLocked
						 FROM ".Tprefix."reports r, affiliates a, entities s
						 WHERE r.affid=a.affid AND r.spid=s.eid AND r.type='q'{$extra_where}
						 ORDER BY {$sort_query}");
						 
		if($db->num_rows($query) > 0) {
			$reports[0]['affiliatename'] = $lang->affiliate;
			$reports[0]['suppliername'] = $lang->supplier;
			$reports[0]['quarter'] = $lang->quarter;
			$reports[0]['year'] = $lang->year;
			$reports[0]['status'] = $lang->status;
			
			$i= 1;
			while($reports[$i] = $db->fetch_assoc($query)) {
				$reports[$i]['status'] = parse_status($reports[$i]['status'], $reports[$i]['isLocked']);
				unset($reports[$i]['isLocked']);
				$i++;
			}
			$excelfile = new Excel('array', $reports);
		}
	}
}

function parse_status($status, $lock=0) {
	global $lang;
	
	if($status == 1) {
		$status_text = $lang->finalized;
	}
	else
	{
		$status_text = $lang->notfinished;
	}
	
	if($lock == 1) {
		$status_text .=  ' '.$lang->andlocked;
	}
	return $status_text;		
}

function parse_statusdetails($data) {
	global $lang;
	
	if(is_array($data)) {
		foreach($data as $key => $val) {
			$class = '';
			switch($key) {
				case 'prActivityAvailable':
					if($val == 1) { $class = 'green_text'; } else { $class = 'red_text'; }
					$status .= "<div class='".$class."'>{$lang->productactivitydetails}</div>";
					break;
				case 'keyCustAvailable':
					if($val == 1) { $class = 'green_text'; } else { $class = 'red_text'; }
					$status .= "<div class='".$class."'>{$lang->keycustomers}</div>";
					break;
				case 'mktReportAvailable':
					if($val == 1) { $class = 'green_text'; } else { $class = 'red_text'; }
					$status .= "<div class='".$class."'>{$lang->marketreport}</div>";
					break;
				default: break;	
			}
		}
		return $status;
	}
	else
	{
		return false;
	}
}
?>