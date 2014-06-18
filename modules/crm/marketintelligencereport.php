<?php
/*
 * Copyright © 2014 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: marketintelligencereport.php
 * Created:        @tony.assaad    Mar 10, 2014 | 2:59:34 PM
 * Last Update:    @tony.assaad    Mar 10, 2014 | 2:59:34 PM
 */


if(!defined('DIRECT_ACCESS')) {
    die('Direct initialization of this file is not allowed.');
}
//if($core->usergroup['crm_canGenerateMIRep'] == 0) {
//	error($lang->sectionpermision);
//}
if(!$core->input['action']) {
    $identifier = substr(md5(microtime(uniqid())), 0, 10);
    // Here we get affiliate for user assigned to, or he can audit
    $afffiliates_users = $core->user['affiliates'] + $core->user['auditfor'];
    foreach($afffiliates_users as $affid => $affiliates) {
        $selected = '';
        $affiliate_obj = new Affiliates($affiliates);
        $affiliates_data = $affiliate_obj->get();
        if($affiliates_data['affid'] == $core->user['mainaffiliate']) {
            $selected = " selected='selected'";
        }
        $affiliates_list.='<option value='.$affiliates_data['affid'].' '.$selected.'>'.$affiliates_data['name'].'</option>';
    }

    // Here we get  suppliers that the user is assigned to or work with an affiliate that he can audit
    if($core->usergroup['canViewAllSupp'] == 0) {
        $insupplier = implode(',', $core->user['suppliers']['eid']);
        $supplier_where = " eid IN ({$insupplier})";
    }
    else {
        $supplier_where = " type='s'";
    }
    $user_suppliers = get_specificdata('entities', array('eid', 'companyName'), 'eid', 'companyName', array('by' => 'companyName', 'sort' => 'ASC'), 1, "{$supplier_where}");

    $user_obj = new Users();
    $affiliatesaudit_objs = $user_obj->get_auditedaffiliates();
    if(is_array($affiliatesaudit_objs)) {
        foreach($affiliatesaudit_objs as $affid => $affiliatesaudit_obj) {
            $affiliatedaudit_suppliers = $affiliatesaudit_obj->get_suppliers();
            $selected = '';
            foreach($affiliatedaudit_suppliers as $affiliatedaudit_supplier) {
                if(in_array($affiliatedaudit_supplier['eid'], $core->user['suppliers']['eid'])) {
                    $selected = " selected='selected'";
                }
                $suppliers_list.='<option value='.$affiliatedaudit_supplier['eid'].' '.$selected.'>'.$affiliatedaudit_supplier['companyName'].'</option>';
            }
        }
    }
    //Here we get customer that the user is assigned to or work with an affiliate that he can audit
    $incusomters = implode(',', $core->user['customers']);
    $customer_where = " eid IN ({$incusomters})";

    $users_customers = get_specificdata('entities', array('eid', 'companyName'), 'eid', 'companyName', array('by' => 'companyName', 'sort' => 'ASC'), 1, "{$customer_where}");
    if(is_array($affiliatesaudit_objs)) {
        foreach($affiliatesaudit_objs as $affid => $affiliatesaudit_obj) {
            $affiliatesaudit_customersobjs = $affiliatesaudit_obj->get_customers();
            if(is_array($affiliatesaudit_customersobjs)) {
                foreach($affiliatesaudit_customersobjs as $affiliatesaudit_customersobj) {
                    $affiliatesaudit_customer = $affiliatesaudit_customersobj->get();
                    if(in_array($affiliatesaudit_customer['eid'], $core->user['customers'])) {
                        $selected = " selected='selected'";
                    }
                    $customers_list.='<option value='.$affiliatesaudit_customer['eid'].' '.$selected.'>'.$affiliatesaudit_customer['companyName'].'</option>';
                }
            }
        }
    }
    /* customer types */
    $potential_custobjs = Customers::get_customers(array('type' => 'c', 'supplierType' => 'pc'));

    if(is_array($potential_custobjs)) {
        foreach($potential_custobjs as $potential_custobj) {
            $potential_customername = $potential_custobj->companyName;
            $potential_customerlist.='<option value='.$potential_custobj->eid.' '.$selected.'>'.$potential_custobj->companyName.'</option>';
        }
    }
    // Get User  segments the user is assigned to, assigned to supervise, or is coordinator for
    $user = new Users($core->user['uid']);
    $user_segmentsobjs = $user->get_segments();
    if(is_array($user_segmentsobjs)) {

        foreach($user_segmentsobjs as $key => $user_segmentsobj) {

            $userassigned_segments[$user_segmentsobj->get()['psid']] = $user_segmentsobj->get()['title'];
        }
    }
    //$userassigned_segments = $user->get_segments();
    //$user_coordinator_segments = $user->get_coordinatesegments();
    //Get segment cooridnator
    if(is_array($userassigned_segments) && is_array($user_coordinator_segments)) {
        $user_segments = array_merge($userassigned_segments, $user_coordinator_segments);
    }
    else {
        $user_segments = $userassigned_segments;
    }
    $selected = '';

    foreach($user_segments as $psid => $user_segment) {
        if(in_array($psid, $core->user['suppliers']['eid'])) {
            $selected = " selected='selected'";
        }
        $segmentlist.='<option value='.$psid.' '.$selected.'>'.$user_segment.'</option>';
    }

    // Get business manager that report to the user or are assigned to a main affiliate that the user is auditing
    $bmreported = $user->get_reportingto();
    // user assigned= $core->user['auditfor']
    if(is_array($core->user['auditedaffids'])) {
        foreach($core->user['auditedaffids'] as $auditaffid) {
            $aff_obj = new Affiliates($auditaffid);
            $affiliate_users = $aff_obj->get_users(array('ismain' => 1));
            if(is_array($affiliate_users)) {
                foreach($affiliate_users as $aff_businessmgr) {
                    $business_managers[$aff_businessmgr['uid']] = $aff_businessmgr['displayName'];
                }
            }
        }
        $business_managerslist = parse_selectlist('mireport[filter][managers][]', 5, $business_managers, $core->user['uid'], 1, '', '');
    }


    //, 'spid' => $lang->supplier,'spid' => $lang->supplier, 'cid' => $lang->customer, 'psid' => $lang->segment, 'coid' => $lang->customercountry
    $dimensions = array('affid' => $lang->affiliate, 'eptid' => $lang->endproductype, 'pid' => $lang->product, 'cid' => $lang->customer, 'spid' => $lang->supplier, 'psid' => $lang->segment, 'affid' => $lang->affiliate);

    foreach($dimensions as $dimensionid => $dimension) {
        $dimension_item.='<li class="ui-state-default" id='.$dimensionid.' title="Click and Hold to move the '.$dimension.'">'.$dimension.'</li>';
    }
    eval("\$mireport_options = \"".$template->get('crm_marketintelligence_report_options')."\";");
    output($mireport_options);
}
?>
