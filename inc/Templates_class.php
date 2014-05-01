<?php
/*
 * Orkila Central Online System (OCOS)
 * Copyright � 2009 Orkila International Offshore, All Rights Reserved
 *
 * Templates Class
 * $id: Templates_class.php
 * Created:		@zaher.reda
 * Last Update: @zaher.reda 	Feb 20, 2009 | 04:07 PM
 */

class Templates {
    protected $cache = array();

    public function get($title, $raw = 1) {
        global $db, $core;

        if(!isset($this->cache[$title])) {
            if(TEMPLATES_SYSTEM == 'FILE') {
                $base_dir = ROOT.INC_ROOT.'templates/';
                $tplfilepath = $base_dir.$core->sanitize_path($title).'.tpl';
                $handle = fopen($tplfilepath, 'r');
                $template_content = fread($handle, filesize($tplfilepath));
                fclose($handle);
            }
            else {
                $template_content = $db->fetch_field($db->query("SELECT template FROM ".Tprefix."templates WHERE title='".$db->escape_string($title)."'"), 'template');
            }
            $this->cache[$title] = $template_content;
        }
        $template = "<!-- start: {$title} -->\n".$this->cache[$title]."\n<!-- end: {$title} -->";

        if($raw == 1) {
            $template = str_replace("\\'", "'", $db->escape_string($template));
        }

        return $template;
    }

    function dump_templates_to_file_folder() {
        global $db, $template;
        $base_dir = substr(ROOT, 0, strlen(ROOT) - 1).'\\'.substr(INC_ROOT, 0, strlen(INC_ROOT) - 1).'\templates\\';
        $content = '<div style="padding:20px;"><form><hr>';
        $templates_query = $db->query('SELECT * FROM '.Tprefix.'templates');

        if($db->num_rows($templates_query) > 0) {
            while($singletemplate = $db->fetch_assoc($templates_query)) {
                $content.='<br>'.$singletemplate['title'];
                try {
                    $filename = $base_dir.$singletemplate['title'];
                    $filehandle = fopen($filename, 'w');
                    fwrite($filehandle, $singletemplate['template']);
                    fclose($filehandle);
                    $content.=' V';
                }
                catch(Exception $e) {
                    $content.=' X '.$e->getMessage();
                }
            }
        }


        $content.='<br><input type=submit value=send id="sendform"/><hr>';
        $content.='</form><div id=resultsdiv></div></div>';

        $script = '<script>
						$(document).ready(function() {
							$("#sendform").click(function(){
								sharedFunctions.requestAjax("post", "index.php?module=stock/migrate&action=do_migrate","", "resultsdiv","resultsdiv", "html");
							});
						});
					</script>';
        $content.=$script;
        eval("\$debug = \"".$template->get('debug')."\";");
        output_page($debug);
    }

}
?>