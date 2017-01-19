<?php

if (!defined("DIRECT_ACCESS")) {
    die("Direct initialization of this file is not allowed.");
}

if ($core->usergroup['canChangeSettings'] == 0) {
    error($lang->sectionnopermission);
    exit;
}

if (!$core->input['action']) {
    $counter = 0;
    $query = $db->query("SELECT * FROM " . Tprefix . "settings ORDER BY sid ASC");
    while ($setting = $db->fetch_array($query)) {
        ++$counter;
        $class = alt_row($class);

        $settingslist .= "<tr>";
        $settingslist .= "<td class='{$class}' width='35%'><strong>" . $setting['title'] . "</strong><br /><em>" . $setting['description'] . "</em></td>";
        if ($setting['optionscode'] == "text") {
            $option = "<input type='text' value='" . $setting['value'] . "' id='" . $setting['name'] . "' name='" . $setting['name'] . "' tabindex='{$counter}' />";
        }
        elseif ($setting['optionscode'] == "yesno") {
            $option = parse_yesno($setting['name'], $counter, $setting['value']);
        }
        elseif ($setting['optionscode'] == "textarea") {
            $option = "<textarea name='" . $setting['name'] . "' id='" . $setting['name'] . "' cols='30' rows='5' tabindex='{$counter}'>" . $setting['value'] . "</textarea>";
        }

        $settingslist .= "<td class='{$class}'>{$option}</td>";
        $settingslist .= "<tr>";
    }

    eval("\$settingspage = \"" . $template->get("admin_config_settings") . "\";");
    output_page($settingspage);
}
else {
    if ($core->input['action'] == "do_change_settings") {
        unset($core->input['module'], $core->input['action']);

        foreach ($core->input as $key => $val) {
            $db->update_query("settings", array("value" => $val), "name='{$key}'");
        }

        $rebuild = rebuild_settings();
        if ($rebuild) {
            output_xml("<status>true</status><message>{$lang->settingsrebuilt}</message>");
        }
        else {
            output_xml("<status>false</status><message>{$lang->errorrebuildingsettings}</message>");
        }
    }
}

function rebuild_settings() {
    global $db, $core;

    if (!file_exists(ROOT . "inc/settings.php")) {
        $mode = "x";
    }
    else {
        $mode = "w";
    }

    $query = $db->query("SELECT name, value FROM " . Tprefix . "settings ORDER BY name ASC");
    while ($setting = $db->fetch_array($query)) {
        $setting['value'] = str_replace("\"", "\\\"", $setting['value']);
        $settings .= "\$settings['" . $setting['name'] . "'] = \"" . $setting['value'] . "\";\n";
        $core->settings[$setting['name']] = $setting['value'];
    }
    $settings = "<" . "?php\n/*********************************\ \n  DO NOT EDIT THIS FILE\n PLEASE USE the Admin CP\n\*********************************/\n\n{$settings}\n?" . ">";
    $file = @fopen(ROOT . "inc/settings.php", $mode);
    $write = @fwrite($file, $settings);
    @fclose($file);

    if ($write !== false) {
        return true;
    }
    else {
        return false;
    }
}

?>