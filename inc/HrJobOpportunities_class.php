<?php
/*
 * Copyright © 2015 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: HrJobOpportunities.php
 * Created:        @rasha.aboushakra    Nov 3, 2015 | 12:42:56 PM
 * Last Update:    @rasha.aboushakra    Nov 3, 2015 | 12:42:56 PM
 */

/**
 * Description of HrJobOpportunities
 *
 * @author rasha.aboushakra
 */
class HrJobOpportunities extends AbstractClass {
    protected $data = array();
    protected $errorcode = 0;

    const PRIMARY_KEY = 'joid';
    const TABLE_NAME = 'hr_jobopprtunities';
    const DISPLAY_NAME = 'reference';
    const SIMPLEQ_ATTRS = '*';
    const CLASSNAME = __CLASS__;
    const REQUIRED_ATTRS = 'affid,employmentType,title,workLocation,responsibilities,shortDesc,unpublishOn,publishOn';
    const UNIQUE_ATTRS = 'affid,title,reference';

    public function __construct($id = '', $simple = true) {
        parent::__construct($id, $simple);
    }

    protected function create(array $data) {
        global $db, $log, $core, $errorhandler, $lang;
        if(!$this->validate_requiredfields($data)) {
            $this->errorcode = 1;
            return false;
        }
        $dates = array('approxJoinDate', 'publishOn', 'unpublishOn', 'publishingTimeZone');
        foreach($dates as $date) {
            if(isset($data[$date]) && !empty($data[$date])) {
                $data[$date] = strtotime($data[$date]);
            }
        }
        $data['createdOn'] = TIME_NOW;
        $data['createdBy'] = $core->user['uid'];

        /* ---SANITIZE INPUTS---START */
        $sanitize_fields = array('reference', 'title', 'shortDesc', 'responsibilities', 'minQualifications', 'prefQualifications');
        foreach($sanitize_fields as $val) {
            $data[$val] = $core->sanitize_inputs($data[$val], array('removetags' => true));
        }
        /* ---SANITIZE INPUTS---END */


        /* Verify if user can HR this affiliate Server side --START */
        if($core->usergroup['hr_canHrAllAffiliates'] == 0) {
            if(!in_array($data['affid'], $core->user['hraffids'])) {
                return false;
            }
        }
        /* Verify if user can HR this affiliate Server side --END */

        if(value_exists(self::TABLE_NAME, 'affid', $data['affid'], '(('.TIME_NOW.' BETWEEN '.$data['publishOn'].' AND '.$data['unpublishOn'].') AND title="'.$data['title'].'" )')) {
            $this->errorcode = 4;
            return false;
        }

        $requiredlangs = $data['requiredlang'];
        unset($data['requiredlang']);

        if(is_array($data)) {
            $query = $db->insert_query(self::TABLE_NAME, $data);
            if($query) {
                $this->data[self::PRIMARY_KEY] = $db->last_id();
                $log->record(self::TABLE_NAME, $this->data[self::PRIMARY_KEY]);
                if(!empty($requiredlangs) && is_array($requiredlangs)) {
                    $langdata[self::PRIMARY_KEY] = $this->data[self::PRIMARY_KEY];
                    foreach($requiredlangs as $langid) {
                        $hrjoblang = new HrJobOpportunitiesLanguage();
                        $langdata['language'] = $langid;
                        $hrjoblang->set($langdata);
                        $hrjoblang->save();
                    }
                }
            }
        }
        return $this;
    }

    protected function update(array $data) {

    }

}