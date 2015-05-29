<?php
/*
 * Copyright © 2013 Orkila International Offshore, All Rights Reserved
 *
 * Events Class
 * $id: Events.php
 * Created:        @tony.assaad    Oct 16, 2013 | 1:53:26 PM
 * Last Update:    @tony.assaad    Oct 16, 2013 | 1:53:26 PM
 */

/**
 * Description of Events
 *
 * @author tony.assaad
 */
class Events extends AbstractClass {
    protected $errorcode = 0;
    protected $data = array();

    const PRIMARY_KEY = 'ceid';
    const TABLE_NAME = 'calendar_events';
    const DISPLAY_NAME = '';
    const CLASSNAME = __CLASS__;
    const SIMPLEQ_ATTRS = 'ceid, title, description,fromDate,toDate,place,publishOnWebsite';
    const UNIQUE_ATTRS = 'alias';

    public function __construct($id = '', $simple = false, $options = array()) {
        parent::__construct($id, $simple);
    }

    protected function create(array $data) {
        global $db, $core;

        if($this->validate_requiredfields($data)) {
            $this->errorcode = 1;
            return false;
        }
        $fields = array('title', 'description', 'place', 'boothNum', 'type', 'isPublic', 'publishOnWebsite', 'isFeatured');
        foreach($fields as $field) {
            $event_data[$field] = $data[$field];
        }
        $event_data['alias'] = generate_alias($data['title']);
        $event_data['identifier'] = substr(md5(uniqid(microtime())), 0, 10);
        $event_data['description'] = $event_data['description'];
        $event_data['fromDate'] = strtotime($data['fromDate'].' '.$data['fromTime']);
        $event_data['toDate'] = strtotime($data['toDate'].' '.$data['toTime']);
        $event_data['createdOn'] = TIME_NOW;
        $event_data['createdBy'] = $data['uid'] = $core->user['uid'];
        $event_data['isFeatured'] = $data['isFeatured'];
        $event_data['isPublic'] = $data['isPublic'];
        $event_data['refreshLogoOnWebsite'] = $data['refreshLogoOnWebsite'];
        $event_data['tags'] = $data['tags'];
        unset($event_data['restrictto']);
        // $data['restricto'] = implode(',', $ $data['restricto']);
        //  'affid' => $core->input['event']['affid'],
        //'spid' => $core->input['event']['spid'],
        parent::create($event_data);
        //$query = $db->insert_query(self::TABLE_NAME, $event_data);
        //$this->data = $event_data;
        //$this->data[self::PRIMARY_KEY] = $db->last_id();

        /* Parse incoming Attachemtns - START */
        $data['attachments'] = $_FILES['attachments'];

        if(!empty($data['attachments']['name'][0])) {
            $upload_param['upload_allowed_types'] = array('image/jpeg', 'image/gif', 'image/png', 'application/zip', 'application/pdf', 'application/x-pdf', 'application/msword', 'application/vnd.ms-powerpoint', 'text/plain', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
            if(is_array($data['attachments'])) {
                $upload_obj = new Uploader('attachments', $core->input, $upload_param['upload_allowed_types'], 'putfile', 5242880, 1, 1); //5242880 bytes = 5 MB (1024);
                $attachments_path = './uploads/eventsattachments';
                $upload_obj->set_upload_path($attachments_path);
                $upload_obj->process_file();
                $attachments = $upload_obj->get_filesinfo();

                if($upload_obj->get_status() != 4) {
                    ?>
                    <script language="javascript" type="text/javascript">
                        $(function () {
                            top.$("#upload_Result").html("<span class='red_text'><?php echo $upload_obj->parse_status($upload_obj->get_status());?></span>");
                        });
                    </script>
                    <?php
                    exit;
                }
            }
        }
        /* Parse incoming Attachemtns - END */
    }

    protected function update(array $data) {
        global $db, $core;

        if($this->validate_requiredfields($data)) {
            $this->errorcode = 1;
            return false;
        }
        $fields = array('title', 'description', 'place', 'boothNum', 'type', 'isPublic', 'publishOnWebsite', 'isFeatured', 'logo');
        foreach($fields as $field) {
            $event_data[$field] = $data[$field];
        }
        $event_data['description'] = $event_data['description'];
        $event_data['fromDate'] = strtotime($data['fromDate'].' '.$data['fromTime']);
        $event_data['toDate'] = strtotime($data['toDate'].' '.$data['toTime']);
        $event_data['editedOn'] = TIME_NOW;
        $event_data['editedBy'] = $core->user['uid'];
        $event_data['isFeatured'] = $data['isFeatured'];
        $event_data['isPublic'] = $data['isPublic'];
        $event_data['refreshLogoOnWebsite'] = $data['refreshLogoOnWebsite'];
        $event_data['tags'] = $data['tags'];
        unset($event_data['restrictto']);
        //'affid' => $core->input['event']['affid'],
        //'spid' => $core->input['event']['spid'],
        $db->update_query(self::TABLE_NAME, $event_data, self::PRIMARY_KEY.'='.intval($this->data[self::PRIMARY_KEY]));
        $event_data[self::PRIMARY_KEY] = $this->data[self::PRIMARY_KEY];
        $this->data = $event_data;
    }

    public function get_eventbypriority($attributes = array()) {
        global $db;
        $events_query = $db->query("SELECT  ce.*,ce.title AS eventtitle FROM ".Tprefix."calendar_events ce JOIN ".Tprefix."calendar_eventtypes cet ON(cet.cetid=ce.type)
						   WHERE ce.publishOnWebsite=1  AND  (".TIME_NOW." BETWEEN ce.fromDate  AND ce.toDate)
						   ORDER BY ce.fromDate, find_in_set(ce.".key($attributes).",'".$attributes[key($attributes)]."') DESC LIMIT 0,2");

        if($db->num_rows($events_query) > 0) {
            while($eventsrows = $db->fetch_assoc($events_query)) {
                $eventsrow[$eventsrows['cmsnid']] = $eventsrows;
            }
            return $eventsrow;
        }
    }

    public static function get_affiliatedevents($affiliates = array(), $options = array()) {
        global $db, $core;
        if(is_array($options)) {
            if(isset($options['ismain']) && $options['ismain'] === 1) {
                $query_where_add = ' AND isMain=1';
            }
        }
        $events_aff = $db->query("SELECT ce.* FROM ".Tprefix."calendar_events ce
								JOIN ".Tprefix."affiliatedemployees a ON (a.affid=ce.affid)
								WHERE a.uid=".$core->user['uid']." AND a.affid in (".(implode(',', $affiliates)).") ".$query_where_add." ");
        if($db->num_rows($events_aff) > 0) {
            while($aff_events = $db->fetch_assoc($events_aff)) {
                $affiliate_events[$aff_events['ceid']] = $aff_events;
            }
            return $affiliate_events;
        }
    }

    public static function get_events_bytype($type) {
        global $db;

        return $this->events = $db->fetch_assoc($db->query("SELECT  ce.*,ce.title AS eventtitle FROM ".Tprefix."calendar_events ce
								JOIN ".Tprefix."calendar_eventtypes cet ON(cet.cetid=ce.type)
								WHERE cet.name=".$db->escape_string($type).""));
    }

    public function get_invited_users() {
        global $db;
        $invitess_query = $db->query("SELECT ceiid, uid FROM ".Tprefix."calendar_events_invitees WHERE ceid=".intval($this->data['ceid']));
        if($db->num_rows($invitess_query) > 0) {
            while($invitee = $db->fetch_assoc($invitess_query)) {
                $invitees[$invitee['ceiid']] = new Users($invitee['uid']);
            }
            return $invitees;
        }
        return false;
    }

    public function get() {
        return $this->data;
    }

    private function validate_requiredfields(array $data = array()) {
        global $core, $db;
        if(is_array($data)) {
            $required_fields = array('title', 'description', 'fromDate', 'toDate');
            foreach($required_fields as $field) {
                if(empty($data[$field]) && $data[$field] != '0') {
                    $this->errorcode = 2;
                    return true;
                }
            }
        }
    }

}
?>