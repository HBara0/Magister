<?php
/*
 * Copyright © 2015 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: AroDocumentsSequenceConf.php
 * Created:        @tony.assaad    Feb 10, 2015 | 4:00:23 PM
 * Last Update:    @tony.assaad    Feb 10, 2015 | 4:00:23 PM
 */

/**
 * Description of AroDocumentsSequenceConf
 *
 * @author tony.assaad
 */
class AroDocumentsSequenceConf extends AbstractClass {
    protected $data = array();
    protected $errorcode = 0;

    const PRIMARY_KEY = 'adsid';
    const TABLE_NAME = 'aro_documentsequences';
    const DISPLAY_NAME = '';
    const SIMPLEQ_ATTRS = 'adsid,affid,ptid,effectiveFrom,effectiveTo';
    const CLASSNAME = __CLASS__;
    const UNIQUE_ATTRS = 'affid,ptid,effectiveFrom,effectiveTo';

    protected function update(array $data) {
        global $db, $core, $log;
        $required_fields = array('effectiveFrom', 'effectiveTo'); //warehsuoe
        foreach($required_fields as $field) {
            $data[$field] = $core->sanitize_inputs($data[$field], array('removetags' => true, 'allowable_tags' => '<blockquote><b><strong><em><ul><ol><li><p><br><strike><del><pre><dl><dt><dd><sup><sub><i><cite><small>'));
            if(is_empty($data[$field])) {
                $this->errorcode = 2;
                return false;
            }
        }

        $documentsequence_array = array('affid' => $data['affid'],
                'effectiveFrom' => $data['effectiveFrom'],
                'effectiveTo' => $data['effectiveTo'],
                'prefix' => $data['prefix'],
                'incrementBy' => $data['incrementBy'],
                'nextNumber' => $data['nextNumber'],
                'suffix' => $data['suffix'],
                'createdBy' => $core->user['uid'],
                'ptid' => $data['ptid'],
                'createdOn' => TIME_NOW,
        );
        $query = $db->update_query(self::TABLE_NAME, $documentsequence_array, ''.self::PRIMARY_KEY.'='.intval($this->data[self::PRIMARY_KEY]));
        if($query) {
            $this->data[self::PRIMARY_KEY] = $db->last_id();
            $log->record(self::TABLE_NAME, $this->data[self::PRIMARY_KEY]);
            $this->errorcode = 0;
        }
    }

    protected function create(array $data) {
        global $db, $core, $log;
        $required_fields = array('effectiveFrom', 'effectiveTo'); //warehsuoe
        foreach($required_fields as $field) {
            $data[$field] = $core->sanitize_inputs($data[$field], array('removetags' => true, 'allowable_tags' => '<blockquote><b><strong><em><ul><ol><li><p><br><strike><del><pre><dl><dt><dd><sup><sub><i><cite><small>'));
            if(is_empty($data[$field])) {
                $this->errorcode = 2;
                return false;
            }
        }

        $documentsequence_array = array('affid' => $data['affid'],
                'effectiveFrom' => $data['effectiveFrom'],
                'effectiveTo' => $data['effectiveTo'],
                'prefix' => $data['prefix'],
                'incrementBy' => $data['incrementBy'],
                'nextNumber' => $data['nextNumber'],
                'suffix' => $data['suffix'],
                'createdBy' => $core->user['uid'],
                'ptid' => $data['ptid'],
                'createdOn' => TIME_NOW,
        );
        $query = $db->insert_query(self::TABLE_NAME, $documentsequence_array);
        if($query) {
            $this->data[self::PRIMARY_KEY] = $db->last_id();
            $log->record(self::TABLE_NAME, $this->data[self::PRIMARY_KEY]);
            $this->errorcode = 0;
        }
    }

}