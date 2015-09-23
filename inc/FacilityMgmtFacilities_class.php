<?php
/*
 * Copyright © 2015 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: FacilityMgmtFacilities_class.php
 * Created:        @rasha.aboushakra    Sep 23, 2015 | 9:53:03 AM
 * Last Update:    @rasha.aboushakra    Sep 23, 2015 | 9:53:03 AM
 */

/**
 * Description of FacilityMgmtFacilities_class
 *
 * @author rasha.aboushakra
 */
class FacilityMgmtFacilities extends AbstractClass {
    protected $data = array();

    const PRIMARY_KEY = 'fmfid';
    const TABLE_NAME = 'facilitymgmt_facilities';
    const DISPLAY_NAME = 'name';
    const SIMPLEQ_ATTRS = '*';
    const CLASSNAME = __CLASS__;

    public function __construct($id = '', $simple = true) {
        parent::__construct($id, $simple);
    }

    protected function create(array $data) {

    }

    public function save(array $data = array()) {

    }

    protected function update(array $data) {

    }

    public function get_displayname() {
        return $this->data[self::DISPLAY_NAME];
    }

}