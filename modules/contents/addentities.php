<?php
/*
 * Orkila Central Online System (OCOS)
 * Copyright © 2009 Orkila International Offshore, All Rights Reserved
 *
 * Add entities
 * $module: contents
 * $id: addentities.php
 * Last Update: @zaher.reda 	February 15, 2012 | 10:05 AM
 */
if(!defined('DIRECT_ACCESS')) {
    die('Direct initialization of this file is not allowed.');
}

if($core->usergroup['canAddSuppliers'] == 0 && $core->usergroup['canAddCustomers'] == 0) {
    error($lang->sectionnopermission);
    exit;
}

$lang->load('contents_addentities');
if(!$core->input['action']) {
    if(isset($core->input['type'])) {
        if($core->input['type'] == 'supplier') {
            $selected_type = 's';
        }
        elseif($core->input['type'] == 'competitorsupplier') {
            $selected_type = 'cs';
        }
        else {
            $selected_type = 'c';
            $createreports_disabled = ' disabled';
        }
    }
    else {
        $createreports_disabled = ' disabled';
    }
    if($core->usergroup['canCreateReports'] == 0) {
        $createreports_disabled = ' disabled';
    }

    if($core->usergroup['canAddCustomers'] == 1) {
        $types['c'] = $lang->customer;
        $types['pc'] = $lang->potentialcustomer;
    }

    if($core->usergroup['canAddSuppliers'] == 1) {
        $types['s'] = $lang->supplier;
        $types['cs'] = $lang->competitorsupplier;
    }
    $supptypes = array('t' => $lang->trader, 'p' => $lang->producer, 'b' => $lang->both);
    $presence = array('local' => $lang->local, 'regional' => $lang->regional, 'multinational' => $lang->multinational);

    $supptypes_list = parse_selectlist('supplierType', 1, $supptypes, '', '', '');
    $presence_list = parse_selectlist('presence', 2, $presence, '');
    $types_list = parse_selectlist('type', 1, $types, $selected_type, '', '', array('required' => 'required'));
    $segments_list = parse_selectlist('psid[]', 3, get_specificdata('productsegments', array('psid', 'title'), 'psid', 'title', 'title'), '', 1, '', array('required' => 'required'));

    $affiliates_attributes = array('affid', 'name');
    $affiliates_order = array(
            'by' => 'name',
            'sort' => 'ASC'
    );

    $affiliates = get_specificdata('affiliates', $affiliates_attributes, 'affid', 'name', $affiliates_order);
    $affiliates_list = parse_selectlist('affid[]', 4, $affiliates, '', 1, '', array('required' => 'required'));

    $countries_attributes = array('coid', 'name');
    $countries_order = array(
            'by' => 'name',
            'sort' => 'ASC'
    );

    $countries = get_specificdata('countries', $countries_attributes, 'coid', 'name', $countries_order);
    $countries_list = parse_selectlist('country', 8, $countries, $core->user_obj->get_mainaffiliate()->get_country()->coid, '', '', array('required' => 'required', 'blankstart' => true));
    $companysize = array('', '1-9', '10-49', '50-249', '150-999', '>= 1000');
    $companysize = array_combine($companysize, $companysize);
    $companysize_list = parse_selectlist('companySize', '', $companysize, '', '', '');
    $country = new Countries();
    $countriescodes = $country->get_phonecodes();
    $telephone_intcode_list = parse_selectlist('telephone_intcode', $tabindex, $countriescodes, $selected_options, '', '', array('id' => 'telephone_intcode', 'width' => '125px'));
    $telephone2_intcode_list = parse_selectlist('telephone2_intcode', $tabindex, $countriescodes, $selected_options, '', '', array('id' => 'telephone2_intcode', 'width' => '125px'));
    $fax_intcode_list = parse_selectlist('fax_intcode', $tabindex, $countriescodes, $selected_options, '', '', array('id' => 'fax_intcode', 'width' => '125px'));
    $fax2_intcode_list = parse_selectlist('fax2_intcode', $tabindex, $countriescodes, $selected_options, '', '', array('id' => 'fax2_intcode', 'width' => '125px'));
    eval("\$addpage = \"".$template->get('contents_entities_add')."\";");
    output_page($addpage);
}
else {
    if($core->input['action'] == 'do_perform_addentities') {
        if(isset($core->input['createReports']) && $core->input['createReports'] == 1) {
            $create_reports = true;
        }

        $entity_data = $core->input;
        unset($entity_data['module'], $entity_data['action'], $entity_data['createReports']);
        if($entity_data['type'] == 'pc') {
            //$entity_data['isPotential'] = 1;
        }

        $entity_data['approved'] = 1;
        if($entity_data['type'] == 's') {
            if($core->usergroup['canManageSuppliers'] == 0) {
                $entity_data['approved'] = 0;
            }
        }

        $entity_data['companyName'] = ucwords(strtolower($entity_data['companyName']));
        $entity = new Entities($entity_data);
        if($entity->get_status() === true) {
            log_action($entity->get_eid());
            if($create_reports === true) {
                $current_quarter = currentquarter_info();
                foreach($core->input['affid'] as $key => $val) {
                    $newreport = array(
                            'quarter' => $current_quarter['quarter'],
                            'year' => $current_quarter['year'],
                            'affid' => $val,
                            'spid' => $entity->get_eid(),
                            'initDate' => time(),
                            'status' => 0
                    );
                    $db->insert_query('reports', $newreport);
                }
            }
        }
    }
    elseif($core->input['action'] == 'inlineCheck') {
        if(isset($core->input['attr'], $core->input['value'])) {
            if(value_exists('entities', $core->input['attr'], $core->input['value']) === true) {
                $attribute = $db->escape_string($core->input['attr']);
                $value = $db->escape_string($core->input['value']);

                $eid = $db->fetch_field($db->query("SELECT eid FROM ".Tprefix."entities WHERe {$attribute}='{$value}'"), 'eid');
                //$existing = get_specificdata('affiliatedentities', array('affid'), 'affid', 'affid', '', 0, "eid='{$eid}'");
//                $where = '';
//                if($core->usergroup['canViewAllAff'] == 0) {
//                $where = 'affid IN ('.implode(',', $core->user['affiliates']).')'; // AND affid NOT IN('.implode(',', $existing).')
//                }

                $affiliates_attributes = array('affid', 'name');
                $affiliates_order = array(
                        'by' => 'name',
                        'sort' => 'ASC'
                );

                // $affiliates = get_specificdata('affiliates', $affiliates_attributes, 'affid', 'name', $affiliates_order, 0, $where);
                //if(is_array($affiliates)) {
                //$affiliates_list = parse_selectlist('affid', 1, $affiliates, '');

                eval("\$affiliateentity = \"".$template->get('inline_entityexist')."\";");
                $affiliateentity = "<![CDATA[{$affiliateentity}]]>";
                //  }
                header('Content-type: text/xml+javascript');
                output_xml("<status>false</status><message>{$lang->entityalreadyexists}{$affiliateentity}</message>");
            }
            else {
                $query = $db->query("SELECT eid, companyName
									FROM ".Tprefix."entities
									WHERE SOUNDEX(".$db->escape_string($core->input['attr']).") = SOUNDEX('".$db->escape_string($core->input['value'])."')
									OR ".$db->escape_string($core->input['attr'])." LIKE '%".$db->escape_string($core->input['value'])."%'");
                if($db->num_rows($query) > 0) {
                    while($entity = $db->fetch_assoc($query)) {
                        $possible_matches .= $entity['companyName'].'<br /> ';
                    }
                    $lang->entitymightexist = $lang->sprint($lang->entitymightexist, $possible_matches);
                    output_xml("<status>false</status><message><![CDATA[{$lang->entitymightexist}]]></message>");
                }
                else {
                    output_xml("<status>true</status><message></message>");
                }
            }
        }
    }
    elseif($core->input['action'] == 'do_affiliateentity') {
        $eid = $db->escape_string($core->input['entity']);
        $affid = $db->escape_string($core->input['affid']);

        $affiliate_exists = $db->fetch_field($db->query("SELECT COUNT(*) AS counter FROM ".Tprefix."affiliatedentities WHERE eid='{$eid}' AND affid='{$affid}'"), 'counter');
        if($affiliate_exists == 0) {
            $query = $db->insert_query('affiliatedentities', array('eid' => $eid, 'affid' => $affid));
            if(!$query) {
                output_xml("<status>false</status><message>{$lang->errorwhilejoin}</message>");
            }
        }
        else {
            $exists_register['affiliate'] = true;
        }

        $employee_exists = $db->fetch_field($db->query("SELECT COUNT(*) AS counter FROM ".Tprefix."assignedemployees WHERE eid='{$eid}' AND uid='{$core->user[uid]}' AND affid='{$affid}'"), 'counter');
        if($employee_exists == 0) {
            $db->insert_query('assignedemployees', array('eid' => $eid, 'uid' => $core->user['uid'], 'affid' => $affid));
        }
        else {
            $exists_register['employee'] = true;
        }

        if($exists_register['employee'] == true && $exists_register['affiliate'] == true) {
            output_xml("<status>false</status><message>{$lang->alreadyjoined}</message>");
        }
        else {
            output_xml("<status>true</status><message>{$lang->joinedsuccessfully}</message>");
        }
    }
    elseif($core->input['action'] == 'do_add_representative') {
        $representative = new Entities($core->input, 'add_representative');

        if($representative->get_status() === true) {
            header('Content-type: text/xml+javascript');
            output_xml('<status>true</status><message>{$lang->representativecreated}<![CDATA[<script>$("#popup_addrepresentative").dialog("close");</script>]]></message>');
            //output_xml("<status>true</status><message>{$lang->representativecreated}</message>");
            exit;
        }
        else {
            output_xml("<status>false</status><message>{$lang->errorcreatingreprentative}</message>");
        }
    }
    elseif($core->input['action'] == 'get_addnew_representative') {
        $country = new Countries();
        $countriescodes = $country->get_phonecodes();
        $countries_phonecodes = parse_selectlist('repTelephone[intcode]', '', $countriescodes, '', '', '', array('width' => '125px'));

        $positions = Positions::get_data('name IS NOT null', array('returnarray' => true, 'order' => array('by' => 'name', 'sort' => 'ASC')));
        $positions_selectlist = parse_selectlist('repPosition', '', $positions, '', '', '', array('blankstart' => true, 'width' => '150px'));

        eval("\$addrepresentativebox = \"".$template->get('popup_addrepresentative')."\";");
        output_page($addrepresentativebox);
    }
}
?>