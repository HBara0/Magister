<?php
/*
 * Orkila Central Online System (OCOS)
 * Copyright © 2009 Orkila International Offshore, All Rights Reserved
 *
 * Main page
 * $id: index.php
 * Created: 	@zaher.reda		Feb 04, 2009 | 10:14 AM
 * Last Update: @zaher.reda 	Sep 20, 2010 | 12:29 AM
 */
define('DIRECT_ACCESS', 1);

require './global.php';

$modules_dir = ROOT.'modules';

$current_module = explode('/', $core->input['module'], 2);
if($core->input['module'] && $current_module[0]) {
    $run_module = $current_module[0];
}
else {
    $defaultmodule = $db->fetch_field($db->query("SELECT defaultModule FROM ".Tprefix."users WHERE uid='{$core->user[uid]}'"), 'defaultModule');
    $run_module = $defaultmodule;
    if(!isset($defaultmodule) || empty($defaultmodule)) {
        $run_module = $core->usergroup['defaultModule'];
        if(!isset($core->usergroup['defaultModule']) || empty($core->usergroup['defaultModule'])) {
            $run_module = 'portal';
        }
    }
    $current_module[1] = false;
}

if($lang->language_file_exists($run_module.'_meta')) {
    $lang->load($run_module.'_meta');
}

if(file_exists(INC_ROOT.$run_module.'_functions.php')) {
    require_once INC_ROOT.$run_module.'_functions.php';
}

$modules_list = parse_moduleslist($run_module);

require $core->sanitize_path($modules_dir.'/'.$run_module.'.php');
if($core->usergroup[$module['globalpermission']] == 1) {
    if($current_module[1] === false) {
        $current_module[1] = $module['homepage'];
    }

    $action_file = $current_module[1].'.php';

    $menu_items = parse_menuitems($run_module);
    eval("\$menu = \"".$template->get('mainmenu')."\";");

    /* Get Module Help Section - Start */
    //$help_document  = $db->fetch_assoc($db->query("SELECT hdid FROM ".Tprefix."helpdocuments WHERE module='{$run_module}' AND relatesTo LIKE '%:\"{$current_module[1]}\";%' LIMIT 0, 1"));
    //echo '<a href="help.php?hdid='.$help_document['hdid'].'" target="_blank">help</a>';
    /* Get Modile Help Section - End */
    require $core->sanitize_path($modules_dir.'/'.$run_module.'/'.$action_file);
}
else {
    error($lang->sectionnopermission);
}
?>