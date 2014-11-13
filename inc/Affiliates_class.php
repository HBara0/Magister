<?php
/*
 * Copyright © 2013 Orkila International Offshore, All Rights Reserved
 *
 * Affiliates Class
 * $id: Affiliates_class.php
 * Created:        @zaher.reda    Mar 8, 2013 | 4:51:09 PM
 * Last Update:    @zaher.reda    Mar 8, 2013 | 4:51:09 PM
 */

class Affiliates {
    private $affiliate = array();

    const PRIMARY_KEY = 'affid';
    const TABLE_NAME = 'affiliates';
    const DISPLAY_NAME = 'name';

    public function __construct($id, $simple = TRUE) {
        if(empty($id)) {
            return false;
        }
        $this->read($id, $simple);
    }

    private function read($id, $simple = TRUE) {
        global $db;

        $query_select = 'affid, name, legalName, country, city, integrationOBOrgId, mainCurrency';
        if($simple == false) {
            $query_select = '*';
        }
        $this->affiliate = $db->fetch_assoc($db->query('SELECT '.$query_select.' FROM '.Tprefix.'affiliates WHERE affid='.intval($id)));
    }

    public function get_country() {
        return new Countries($this->affiliate['country']);
    }

    public function get_city($simple = true) {

        if(is_numeric($this->affiliate['city'])) {
            return new Cities($this->affiliate['city'], $simple);
        }
        else {
            return Cities::get_cities(array('name' => $this->affiliate['city'], 'coid' => $this->affiliate['country']));
        }
    }

    public function get_supervisor() {
        if(empty($this->affiliate['supervisor'])) {
            return false;
        }
        return new Users($this->affiliate['supervisor']);
    }

    public function get_generalmanager() {
        if(empty($this->affiliate['generalManager'])) {
            return false;
        }
        return new Users($this->affiliate['generalManager']);
    }

    public function get_hrmanager() {
        if(empty($this->affiliate['hrManager'])) {
            return false;
        }
        return new Users($this->affiliate['hrManager']);
    }

    public function get_financialemanager() {
        if(empty($this->affiliate['finManager'])) {
            return false;
        }
        return new Users($this->affiliate['finManager']);
    }

    public function get_defaultworkshift() {
        if(!empty($this->affiliate['defaultWorkshift'])) {
            return new Workshifts($this->affiliate['defaultWorkshift']);
        }
        return false;
    }

    public function get_users($options = array()) {
        global $db;

        if(is_array($options)) {
            if(isset($options['ismain']) && $options['ismain'] === 1) {
                $query_where_add = ' AND isMain=1';
            }
        }
        $query = $db->query("SELECT DISTINCT(u.uid)
					FROM ".Tprefix."users u
					JOIN ".Tprefix."affiliatedemployees a ON (a.uid=u.uid)
					WHERE a.affid={$this->affiliate['affid']}".$query_where_add." AND u.gid!=7
					ORDER BY displayName ASC");
        while($user = $db->fetch_assoc($query)) {
            $users = new Users($user['uid']);
            if($options['displaynameonly']) {
                $users_affiliates[$user['uid']] = $users->get()['displayName'];
            }
            elseif($options['returnobjects'] == true) {
                $users_affiliates[$user['uid']] = $users;
            }
            else {
                $users_affiliates[$user['uid']] = $users->get();
            }
        }
        return $users_affiliates;
    }

    public function get_suppliers() {
        global $db;
        $additional_where = getquery_entities_viewpermissions('suppliersbyaffid', $this->affiliate['affid'], '', 0, 'ae', 'eid');
        $query = $db->query("SELECT DISTINCT(e.eid)
					FROM ".Tprefix."entities e
					LEFT JOIN ".Tprefix."affiliatedentities ae ON (ae.eid=e.eid)
					WHERE ae.affid={$this->affiliate['affid']} AND isActive=1 AND approved=1 AND type='s'".$additional_where[extra]." ORDER BY companyName ASC");
        while($supplier = $db->fetch_assoc($query)) {
            $suppliers = new Entities($supplier['eid']);
            $suppliers_affiliates[$supplier['eid']] = $suppliers->get();
        }
        return $suppliers_affiliates;
    }

    public function get_customers() {
        global $db;
        $query = $db->query("SELECT DISTINCT(e.eid)
                            FROM ".Tprefix."entities e
                            LEFT JOIN ".Tprefix."affiliatedentities ae ON (ae.eid=e.eid)
                            WHERE ae.affid={$this->affiliate['affid']} AND type='c'".$additional_where[extra]." ORDER BY companyName ASC");
        while($customer = $db->fetch_assoc($query)) {
            $customers[$customer['eid']] = new Entities($customer['eid']);
        }
        return $customers;
    }

    public static function get_affiliates($filters = null, array $configs = array()) {
        $data = new DataAccessLayer(__CLASS__, self::TABLE_NAME, self::PRIMARY_KEY);
        return $data->get_objects($filters, $configs);
    }

    public static function get_affiliate_byname($name) {
        global $db;

        if(!empty($name)) {
            $id = $db->fetch_field($db->query('SELECT affid FROM '.Tprefix.'affiliates WHERE name="'.$db->escape_string($name).'"'), 'affid');
            if(!empty($id)) {
                return new Affiliates($id);
            }
        }
        return false;
    }

    public function get_displayname() {
        return $this->affiliate[self::DISPLAY_NAME];
    }

    public function get() {
        return $this->affiliate;
    }

    public function __get($name) {
        if(isset($this->affiliate[$name])) {
            return $this->affiliate[$name];
        }
        return false;
    }

    public function __isset($name) {
        return isset($this->affiliate[$name]);
    }

    public function parse_link($attributes_param = array('target' => '_blank'), $options = array()) {
        if(is_array($attributes_param)) {
            foreach($attributes_param as $attr => $val) {
                $attributes .= $attr.' "'.$val.'"';
            }
        }

        if(!isset($options['outputvar'])) {
            $options['outputvar'] = self::DISPLAY_NAME;
        }
        if(is_array($attributes_param)) {
            foreach($attributes_param as $attr => $val) {
                $attributes .= $attr.' = "'.$val.'"';
            }
        }
        return '<a href="index.php?module=profiles/affiliateprofile&affid='.$this->affiliate['affid'].'" '.$attributes.'>'.$this->affiliate[$options['outputvar']].'</a>';
    }

    public function get_mainCurrency() {
        print_R($this->affiliate);
        exit;
        return $this->affiliate['mainCurrency'];
    }

}
?>
