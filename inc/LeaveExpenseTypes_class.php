<?php
/*
 * Copyright � 2014 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: LeaveExpenseTypes_class.php
 * Created:        @tony.assaad    Apr 9, 2014 | 2:38:39 PM
 * Last Update:    @tony.assaad    Apr 9, 2014 | 2:38:39 PM
 */

/**
 * Description of Leaves_expenses
 *
 * @author tony.assaad
 */
class LeaveExpenseTypes {
    private $expencetype = array();

    const PRIMARY_KEY = 'aletid';
    const TABLE_NAME = 'attendance_leaveexptypes';

    public function __construct($id = '', $simple = true) {
        if(isset($id) && !empty($id)) {
            $this->expencetype = $this->read($id, $simple);
        }
    }

    private function read($id, $simple = true) {
        global $db;
        if(empty($id)) {
            return false;
        }
        $query_select = '*';
        if($simple == true) {
            $query_select = 'aletid, name, title';
        }
        return $db->fetch_assoc($db->query('SELECT '.$query_select.' FROM '.Tprefix.self::TABLE_NAME.' WHERE '.self::PRIMARY_KEY.'='.intval($id)));
    }

    public static function get_leaveexpensetypes($filters = array()) {
        global $db;

        $query = $db->query('SELECT * FROM '.Tprefix.self::TABLE_NAME);
        if($db->num_rows($query) > 0) {
            while($expensetype = $db->fetch_assoc($query)) {
                $expensetypes[$expensetype[self::PRIMARY_KEY]] = $expensetype;
            }

            return $expensetypes;
        }
        return false;
    }

    public static function get_exptype_byattr($attr, $value, $simple = true) {
        global $db;

        if(!empty($value) && !empty($attr)) {
            $query = $db->query('SELECT '.self::PRIMARY_KEY.' FROM '.Tprefix.self::TABLE_NAME.' WHERE '.$db->escape_string($attr).'="'.$db->escape_string($value).'"');
            if($db->num_rows($query) > 1) {
                $items = array();
                while($item = $db->fetch_assoc($query)) {
                    $items[$item[self::PRIMARY_KEY]] = new self($item[self::PRIMARY_KEY], $simple);
                }
                $db->free_result($query);
                return $items;
            }
            else {
                if($db->num_rows($query) == 1) {
                    return new self($db->fetch_field($query, self::PRIMARY_KEY), $simple);
                }
                return false;
            }
        }
        return false;
    }

    public function parse_agencylink(Leaves $leave, $agency = 'kayak') {
        $link_patterns = array(
                'kayak' => array('flight' => 'https://www.kayak.com/flights/{FROM_AIRPORT}-{TO_AIRPORT}/{FROM_DATE}/{TO_DATE}',
                        'hotel' => 'https://www.kayak.com/hotels/{CITY},{COUNTRY}/{FROM_DATE}/{TO_DATE}/1guest')
        );

        $leave_info['fromDate_formated'] = date('Y-m-d', $leave->get()['fromDate']);
        $leave_info['toDate_formated'] = date('Y-m-d', $leave->get()['toDate']);

        $destination_city = $leave->get_destinationcity();
        if(!is_object($destination_city)) {
            return false;
        }
        $destination_airport = $destination_city->get_defaultairport();
        if(!is_object($destination_airport)) {
            return false;
        }

        if($agency == 'kayak') {
            if($this->expencetype['isAirFare']) {
                $source_airport = $leave->get_sourcecity()->get_defaultairport();
                if(!is_object($source_airport)) {
                    return false;
                }
                $source_airport_code = $source_airport->get()['iatacode'];
                $link_values = array('FROM_DATE' => $leave_info['fromDate_formated'], 'TO_DATE' => $leave_info['toDate_formated'], 'FROM_AIRPORT' => $source_airport_code, 'TO_AIRPORT' => $destination_airport->get()['iatacode']);
                return '<a href="'.preg_replace('/\{([ A-Z_]+)\}/e', '$link_values["$1"]', $link_patterns[$agency]['flight']).' target="_blank">'.ucwords($agency).'</a>';
            }
            elseif($this->expencetype['isAccommodation']) {
                $link_values = array('FROM_DATE' => $leave_info['fromDate_formated'], 'TO_DATE' => $leave_info['toDate_formated'], 'CITY' => $destination_city->get()['name'], 'COUNTRY' => $destination_city->get_country()->get()['name']);
                return '<a href="'.preg_replace('/\{([ A-Z_]+)\}/e', '$link_values["$1"]', $link_patterns[$agency]['hotel']).' target="_blank">'.ucwords($agency).'</a>';
            }
            return false;
        }
        return false;
    }

    public function get() {
        return $this->expencetype;
    }

}