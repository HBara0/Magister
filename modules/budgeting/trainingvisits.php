<?php
/*
 * Copyright © 2014 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: trainingvisits.php
 * Created:        @tony.assaad    Nov 3, 2014 | 9:47:55 AM
 * Last Update:    @tony.assaad    Nov 3, 2014 | 9:47:55 AM
 */

if(!defined('DIRECT_ACCESS')) {
    die('Direct initialization of this file is not allowed.');
}
if($core->usergroup['budgeting_canFillFinBudgets'] == 0) {
    error($lang->sectionnopermission);
}

if(!isset($core->input['action'])) {
    if(isset($core->input['financialbudget']['year']) && !empty($core->input['financialbudget']['year'])) {
        $financialbudget_year = $core->input['financialbudget']['year'];
        // $financialbudget_prevyear = $investprevyear = $financialbudget_year - 1;
    }
    $affid = $core->input['financialbudget']['affid'];

    $affiliate = new Affiliates($affid);
    $rowid = 1;
    /* get budget */
    $financialbudget = FinancialBudget::get_data(array('affid' => $affid, 'year' => $financialbudget_year), array('simple' => false));
    $budgetrainingvisit_obj = BudgetTrainingVisits::get_data(array('bfbid' => $financialbudget->bfbid), array('simple' => false, 'returnarray' => true));

    if(is_array($core->user['auditedaffids'])) {
        foreach($core->user['auditedaffids'] as $auditaffid) {
            $aff_obj = new Affiliates($auditaffid);
            $affiliate_users = $aff_obj->get_users();
            foreach($affiliate_users as $aff_businessmgr) {
                $business_managers[$aff_businessmgr['uid']] = $aff_businessmgr['displayName'];
            }
        }
    }
    else {
        if($core->usergroup['canViewAllEmp'] == 1) {
            $affiliate = new Affiliates($core->user['mainaffiliate']);
            $business_managers = $affiliate->get_users(array('displaynameonly' => true));
        }
        else {
            $business_managers[$core->user['uid']] = $core->user['displayName'];
        }
    }

    $fields = array('purpose', 'event', 'Costaffiliate', 'company', 'inputChecksum', 'bm', 'planCost', 'otherCosts'); //'actualPrevYear', 'budgetPrevYear'
    if(is_array($budgetrainingvisit_obj)) {
        $rowid = 0;
        foreach($budgetrainingvisit_obj as $btvid => $budgetvisit) {
            $rowid ++;
            foreach($fields as $field) {
                if(!empty($budgetvisit->$field)) {
                    $budgetrainingvisit[$field] = $budgetvisit->$field;
                }
                $budgetrainingvisit['date_output'] = date($core->settings['dateformat'], $budgetvisit->date);
                $budgetrainingvisit['Date_formatted'] = date($core->settings['dateformat'], $budgetvisit->date);
            }
            if($budgetvisit->classification === 'local') {
                $entityobj = new Entities($budgetvisit->company);
                $budgetrainingvisit['companyoutput'] = $entityobj->name;
                eval("\$budgettaininglocalvisits_rows .= \"".$template->get('budgeting_tainingvisits_lines')."\";");
            }
            else {
                if(is_array($business_managers)) {
                    foreach($business_managers as $uid => $bm) {
                        $selected = '';
                        if($budgetvisit->bm == $uid) {
                            $selected = ' selected="selected"';
                        }
                        $business_managers_list.='<option value='.$uid.' '.$selected.'>'.$bm.'</option>';
                    }
                    //  $business_managers_list = parse_selectlist('budgetrainingvisit[international]['.$rowid.'][bm]', 7, $business_managers, $selected_options);
                }
                eval("\$budgettaininig_intvisits_rows .= \"".$template->get('budgeting_tainingintvisits_lines')."\";");
            }
        }
    }
    else {
        unset($budgettaininglocalvisits_rows, $budgettaininig_intvisits_rows);

        /* Parse and fill training visit fields based on selected leaves ----START */
        /* recognize that the request includes leave IDs */
        if(isset($core->input['budgetrainingvisit']['leaves']) && $core->input['source'] == 'import' && !empty($core->input['budgetrainingvisit']['leaves'])) {
            $populated_leaves = populate_leaves();
            $affiliate = new Affiliates($core->input['affid']);
            $financialbudget_year = $core->input['year'];
            foreach($populated_leaves as $lid => $populated_leave) {
                $rowid = intval($lid) + 1;
                $budgetrainingvisit[$populated_leave->lid]['date_output'] = date($core->settings['dateformat'], $populated_leave->fromDate);
                $budgetrainingvisit[$populated_leave->lid]['Date_formatted'] = date('d-m-Y', $populated_leave->fromDate);
                $budgetrainingvisit[$populated_leave->lid]['purpose'] = $populated_leave->reason;
                $leave_expenses = $populated_leave->get_expensestotal();
                if(!empty($leave_expenses)) {
                    $budgetrainingvisit[$populated_leave->lid][totalexpenses] = $leave_expenses;
                }
                if(is_array($business_managers)) {
                    foreach($business_managers as $uid => $bm) {
                        $selected = '';
                        if($uid == $populated_leave->uid) {
                            $selected = " selected='selected'";
                        }
                        $business_managers_list.='<option value='.$uid.' '.$selected.'>'.$bm.'</option>';
                    }
                    // $business_managers_list = parse_selectlist('budgetrainingvisit[international]['.$rowid.'][bm]', 7, $business_managers, $selected_options);
                }
                $budgetrainingvisit['inputChecksum'] = generate_checksum('budget');
                eval("\$budgettaininglocalvisits_rows = \"".$template->get('budgeting_tainingvisits_lines')."\";");
                $budgetrainingvisit['inputChecksum'] = generate_checksum('budget');
                eval("\$budgettaininig_intvisits_rows .= \"".$template->get('budgeting_tainingintvisits_lines')."\";");
            }
        }
        else {
            $budgetrainingvisit['inputChecksum'] = generate_checksum('budget');
            eval("\$budgettaininglocalvisits_rows = \"".$template->get('budgeting_tainingvisits_lines')."\";");
            $budgetrainingvisit['inputChecksum'] = generate_checksum('budget');
            eval("\$budgettaininig_intvisits_rows  = \"".$template->get('budgeting_tainingintvisits_lines')."\";");
        }
        /* Parse and fill training visit fields based on selected leaves ----END */

        //eval("\$budgettaininig_intvisits_rows .= \"".$template->get('budgeting_tainingintvisits_lines')."\";");
    }


    /* Fill based on existing leaves  populate existing business leaves  ----START */

    $leave['filter']['type'] = 'SELECT ltid FROM leavetypes WHERE isBusiness=1 ';
    $leave['filter']['affid'] = $affid;
    $leaves_objs = Leaves::get_data($leave['filter'], array('simple' => false, 'operators' => array('type' => 'IN')));
    $lang->load('attendance_messages');
    if(is_array($leaves_objs)) {
        foreach($leaves_objs as $leaves_obj) {
            $leaves_obj->employee = $leaves_obj->get_requester()->get_displayname();
            $leavedate[$leaves_obj->lid] = $lang->sprint($lang->leavedate, date($core->settings['dateformat'], $leaves_obj->fromDate), date($core->settings['dateformat'], $leaves_obj->toDate)); //' from '.date($core->settings['dateformat'], $leaves_obj->fromDate).' TO '.date($core->settings['dateformat'], $leaves_obj->toDate);
            $leaveexpenses = $leaves_obj->get_expensestotal();
            if(!empty($leaveexpenses)) {
                $leaves_obj->totalexpenses = $leaveexpenses;
            }
            eval("\$budgeting_tainingvisitleaves_rows .= \"".$template->get('budgeting_tainingvisits_leavesintegration_rows')."\";");
        }
        eval("\$budgeting_tainingvisitleaves = \"".$template->get('budgeting_tainingvisits_leavesintegration')."\";");
    }

    /* Fill based on existing leaves  populate existing business leaves  ----END */


    eval("\$budgeting_tainingvisit = \"".$template->get('budgeting_tainingvisits')."\";");
    output_page($budgeting_tainingvisit);
}
else if($core->input['action'] == 'ajaxaddmore_budgetrainvisitlocal') {
    $rowid = intval($core->input['value']) + 1;
    $budgetrainingvisit['inputChecksum'] = generate_checksum('budget');
    eval("\$budgettainingvisitssrows = \"".$template->get('budgeting_tainingvisits_lines')."\";");
    output($budgettainingvisitssrows);
}
else if($core->input['action'] == 'ajaxaddmore_budgetrainvisitint') {
    $rowid = intval($core->input['value']) + 1;
    $budgetrainingvisit['inputChecksum'] = generate_checksum('budget');
    if(is_array($core->user['auditedaffids'])) {
        foreach($core->user['auditedaffids'] as $auditaffid) {
            $aff_obj = new Affiliates($auditaffid);
            $affiliate_users = $aff_obj->get_users();
            foreach($affiliate_users as $aff_businessmgr) {
                $business_managers[$aff_businessmgr['uid']] = $aff_businessmgr['displayName'];
            }
        }
    }
    else {
        if($core->usergroup['canViewAllEmp'] == 1) {
            $affiliate = new Affiliates($core->user['mainaffiliate']);
            $business_managers = $affiliate->get_users(array('displaynameonly' => true));
        }
        else {
            $business_managers[$core->user['uid']] = $core->user['displayName'];
        }
    }
    if(is_array($business_managers)) {


        if(is_array($business_managers)) {
            $business_managers_list = '<option> </option>';
            foreach($business_managers as $uid => $bm) {
                $business_managers_list.='<option value='.$uid.' >'.$bm.'</option>';
            }
        }

        //$business_managers_list = parse_selectlist('budgetrainingvisit[international]['.$rowid.'][bm]', 7, $business_managers, $selected_options);
    }

    eval("\$budgettaininig_intvisits_rows = \"".$template->get('budgeting_tainingintvisits_lines')."\";");
    output($budgettaininig_intvisits_rows);
}
else if($core->input['action'] == 'do_perform_trainingvisits') {
    unset($core->input['identifier'], $core->input['module'], $core->input['action']);
    $financialbudget = new FinancialBudget();
    $financialbudget->set($core->input);
    $financialbudget->save();
    switch($financialbudget->get_errorcode()) {

        case 0:
        case 1:
            output_xml('<status>true</status><message>'.$lang->successfullysaved.'</message>');
            break;
        case 2:
            output_xml('<status>false</status><message>'.$lang->fillrequiredfields.'</message>');
            break;
    }
}
//else if($core->input['action'] == 'impodrtleaves') {
//    if(is_array($core->input['budgetrainingvisit']['leaves']) && !empty($core->input['budgetrainingvisit']['leaves'])) {
//        $populated_leaves = populate_leaves();
//        $rowid = intval($core->input['value']) + 1;
//        $budgetrainingvisit['inputChecksum'] = generate_checksum('budget');
//        $budgetrainingvisit[Date_formatted] = $populated_leaves[0][Date_formatted];
//
//        eval("\$budgettaininig_intvisits_rows = \"".$template->get('budgeting_tainingintvisits_lines')."\";");
//
//
//
//        // parse the rows
//        eval("\$budgeting_tainingvisit = \"".$template->get('budgeting_tainingvisits')."\";");
//        output_page($budgeting_tainingvisit);
//
//        exit;
//    }
//}
function populate_leaves() {
    global $core;
    $leaves_import = Leaves::get_data(array('lid' => $core->input['budgetrainingvisit']['leaves']), array('returnarray' => true, 'simple' => false, 'operators' => array('lid' => 'IN')));

    foreach($leaves_import as $leave) {
        $budgetrainingvisit[] = $leave;
    }
    return $budgetrainingvisit;
}
