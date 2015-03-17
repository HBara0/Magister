<?php
/*
 * Copyright © 2013 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: functions.php
 * Created:        @tony.assaad    Dec 6, 2013 | 11:25:34 AM
 * Last Update:    @tony.assaad    Dec 6, 2013 | 11:25:34 AM
 */

if(!defined("DIRECT_ACCESS")) {
    die("Direct initialization of this file is not allowed.");
}

if($core->usergroup['canAddProducts'] == 0) {
    error($lang->sectionnopermission);
    exit;
}

$lang->load('products_functions');
if(!$core->input['action']) {
    $sort_url = sort_url();
    $applications_obj = SegmentApplications::get_segmentsapplications();
    if(is_array($applications_obj)) {
        foreach($applications_obj as $application_obj) {
            $application = $application_obj->get();
            $application['segment'] = $application_obj->get_segment()->get()['title'];

            if(is_array($application)) {
                $applications_list .= '<option value='.$application['psaid'].'>'.$application['segment'].' - '.$application['title'].'</option>';
                unset($application);
            }
        }
    }

    $functions_obj = ChemicalFunctions::get_functions();
    if(is_array($functions_obj)) {
        /* loop over the returned objects and get their related data */
        foreach($functions_obj as $function_obj) {
            $altrow_class = alt_row($altrow_class);
            $function = $function_obj->get();
            $functionsappseg_objs = $function_obj->get_applications();
            $segmentappfunction_objs = $function_obj->get_segmentapplicationfunction();
            //getting each segmentapplication function primary key (safid) and putting it
            //into a hidden input
            if(is_array($segmentappfunction_objs)) {
                foreach($segmentappfunction_objs as $segmentappfunction_obj) {
                    $segmentappfunction = $segmentappfunction_obj->get();
                    if(empty($segmentappfunction)) {
                        $safid = $lang->na;
                        $segapdescriptions = $lang->na;
                    }
                    $safid = $segmentappfunction['safid'];
                }
            }
            else {
                $safid = $lang->na;
                $segapdescriptions = $lang->na;
            }
            if(is_array($functionsappseg_objs)) {
                foreach($functionsappseg_objs as $safid => $functionsappseg_obj) {
                    $functions_applications = $functionsappseg_obj->get();
                    if(empty($functions_applications)) {
                        $functions_application = $lang->na;
                    }
                    if(is_array($functions_applications)) {
                        $functions_application .= $functions_applications['title'].' - '.$functionsappseg_obj->get_segment()->get()['title'];
                        $functions_application.= '<a href="#'.$safid.'" id="segapdescription_'.$safid.'_products/functions_loadpopupbyid" ><img src="'.$core->settings[rootdir].'/images/addnew.png" border="0"></a><br>';
                    }
                }
            }
            else {
                $functions_application = $lang->na;
            }
            eval("\$productsapplicationsfunctions_list .= \"".$template->get('admin_products_functions_row')."\";");
            $functions_application = '';
            $safid = '';
            $segapdescriptions = '';
        }
    }
    else {
        $productsapplicationsfunctions_list = '<tr><td colspan="3">'.$lang->na.'</td></tr>';
    }
    eval("\$popup_createfunction = \"".$template->get('admin_products_popup_createfunction')."\";");
    eval("\$functionpage = \"".$template->get('admin_products_functions')."\";");
    output_page($functionpage);
}
elseif($core->input['action'] == 'do_create') {
    $function_obj = new ChemicalFunctions();
    $function_obj->create($core->input['chemicalfunctions']);
    switch($function_obj->get_errorcode()) {
        case 0:
            output_xml('<status>true</status><message>'.$lang->successfullysaved.'</message>');
            break;
        case 1:
            output_xml('<status>false</status><message>'.$lang->fillallrequiredfields.'</message>');
            break;
        case 2:
            output_xml('<status>false</status><message>'.$lang->entryexist.'</message>');
            break;
    }
}
elseif($core->input['action'] == 'save_descr') {
    $segapfuncid = $db->escape_string($core->input['segfuncapp']);
    $segapfunct_obj = new SegApplicationFunctions($segapfuncid);
    $fields = array('cfid' => $segapfunct_obj->cfid, 'psaid' => $segapfunct_obj->psaid, 'description' => $core->input['segapdescription']);
    $segapfunct_obj->update($fields);
    switch($segapfunct_obj->get_errorcode()) {
        case 0:
            output_xml('<status>true</status><message>'.$lang->successfullysaved.'</message>');
            break;
        case 1:
            output_xml('<status>false</status><message>'.$lang->fillallrequiredfields.'</message>');
            break;
        case 2:
            output_xml('<status>false</status><message>'.$lang->entryexist.'</message>');
            break;
    }
}
elseif($core->input['action'] == 'get_segapdescription') {
    $segapfuncid = $db->escape_string($core->input['id']);
    $segapfunct_obj = new SegApplicationFunctions($segapfuncid);
    $safid = $segapfunct_obj->safid;
    $segapdescriptions = $segapfunct_obj->get_description();
    eval("\$popup_applicationdescription = \"".$template->get('admin_products_popup_applicationdescription')."\";");
    output($popup_applicationdescription);
}
?>
