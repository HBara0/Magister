<?php
/* -------Definiton-START-------- */

class ProductCharacteristicValues extends AbstractClass {
    protected $data = array();
    protected $errorcode = 0;

    const PRIMARY_KEY = 'pcvid';
    const TABLE_NAME = 'productcharacteristics_values';
    const SIMPLEQ_ATTRS = '*';
    const UNIQUE_ATTRS = 'pcid,title';
    const CLASSNAME = __CLASS__;
    const DISPLAY_NAME = 'title';

    /* -------Definiton-END-------- */
    /* -------FUNCTIONS-START-------- */
    public function __construct($id = '', $simple = true) {
        parent::__construct($id, $simple);
    }

    public function create(array $data) {
        global $db, $core;
        $table_array = array(
                'pcid' => $data['pcid'],
                'title' => $data['title'],
        );
        $table_array['name'] = generate_alias($data['title']);
        $query = $db->insert_query(self::TABLE_NAME, $table_array);
        if($query) {
            $this->data[self::PRIMARY_KEY] = $db->last_id();
        }
        return $this;
    }

    protected function update(array $data) {
        global $db;
        if(is_array($data)) {
            $update_array['pcid'] = $data['pcid'];
            $update_array['title'] = $data['title'];
        }
        $update_array['name'] = generate_alias($update_array['title']);
        $db->update_query(self::TABLE_NAME, $update_array, self::PRIMARY_KEY.'='.intval($this->data[self::PRIMARY_KEY]));
        return $this;
    }

    public function get_characterstic() {
        return new ProductCharacteristics($this->pcid);
    }

    public function get_displayname() {
        $characterstic = $this->get_characterstic();

        return $characterstic->get_displayname().' - '.parent::get_displayname();
    }

    /* -------FUNCTIONS-END-------- */
}