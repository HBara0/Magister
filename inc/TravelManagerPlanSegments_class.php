<?php
/*
 * Copyright © 2014 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: TravelManagerPlanSegments_Class.php
 * Created:        @tony.assaad    May 23, 2014 | 3:10:41 PM
 * Last Update:    @tony.assaad    May 23, 2014 | 3:10:41 PM
 */

/**
 * Description of TravelManagerPlanSegments_Class
 *
 * @author tony.assaad
 */
class TravelManagerPlanSegments extends AbstractClass {
    protected $data = array();

    const PRIMARY_KEY = 'tmpsid';
    const TABLE_NAME = 'travelmanager_plan_segments';
    const CLASSNAME = __CLASS__;
    const UNIQUE_ATTRS = 'tmpid,sequence,fromDate';

    public function __construct($id = '', $simple = true) {
        parent::__construct($id, $simple);
    }

    protected function read($id = '') {
        global $db;
        $this->data = $db->fetch_assoc($db->query('SELECT * FROM '.Tprefix.self::TABLE_NAME.' WHERE '.self::PRIMARY_KEY.'='.intval($id)));
    }

    public function create(array $segmentdata) {
        global $db, $core, $lang, $errorhandler;
        if(!is_numeric($segmentdata['toDate'])) {
            $segmentdata['toDate'] = strtotime($segmentdata['toDate']);
            $segmentdata['fromDate'] = strtotime($segmentdata['fromDate']);
        }
        if(is_empty($segmentdata['fromDate'], $segmentdata['toDate'], $segmentdata['originCity'], $segmentdata['destinationCity'])) {
            $this->errorcode = 2;
            return false;
        }

        if(value_exists(self::TABLE_NAME, TravelManagerPlan::PRIMARY_KEY, $segmentdata[TravelManagerPlan::PRIMARY_KEY], "(fromDate = {$segmentdata['fromDate']}  OR toDate = {$segmentdata['toDate']}) AND sequence=".$segmentdata['sequence'])) {
            $this->errorcode = 4;
            return false;
        }

        $sanitize_fields = array('fromDate', 'toDate', 'originCity', 'destinationCity');
        foreach($sanitize_fields as $val) {
            $this->supplier[$val] = $core->sanitize_inputs($this->supplier[$val], array('removetags' => true));
        }
        $segmentdata_array = array('tmpid' => $segmentdata['tmpid'],
                'name' => 'Segment_'.$segmentdata['sequence'],
                'fromDate' => $segmentdata['fromDate'],
                'toDate' => $segmentdata['toDate'],
                'originCity' => $segmentdata['originCity'],
                'reason' => $segmentdata['reason'],
                'destinationCity' => $segmentdata['destinationCity'],
                'apiFlightdata' => $segmentdata['apiFlightdata'],
                'createdBy' => $core->user['uid'],
                'sequence' => $segmentdata['sequence'],
                'createdOn' => TIME_NOW,
                'isNoneBusiness' => $segmentdata['isNoneBusiness'],
                'noAccomodation' => $segmentdata['noAccomodation'],
                'affid' => $segmentdata['affid'],
                'eid' => $segmentdata['eid'],
        );

        $db->insert_query(self::TABLE_NAME, $segmentdata_array);
        $this->data[self::PRIMARY_KEY] = $db->last_id();

        $segpurposes = $segmentdata['purpose'];
        if(is_array($segpurposes)) {
            $db->delete_query('travelmanager_plan_segpurposes', "tmpsid='".$this->data[self::PRIMARY_KEY]."'");
            foreach($segpurposes as $purpose) {
                $purpose_data['purpose'] = $purpose;
                $purpose_data['tmpsid'] = $this->data[self::PRIMARY_KEY];
                $segmentpurpose_obj = new TravelManagerPlanSegPurposes();
                $segmentpurpose_obj->set($purpose_data);
                $segmentpurpose_obj->save();
            }
        }
        if(is_array($segmentdata['assign'])) {
            foreach($segmentdata['assign'] as $type) {
                if(is_array($type)) {
                    if($type == 'affid') {
                        $assigned['type'] = 'internal';
                    }
                    elseif($type == 'eid') {
                        $assigned['type'] = 'external';
                    }
                    $assigned['tmpsid'] = $this->data[self::PRIMARY_KEY];
                    foreach($type as $key => $id) {
                        if(empty($id)) {
                            continue;
                        }
                        $assigned['primaryId'] = $id;
                        $assign_obj = new TravelManagerPlanAffient();
                        $assign_obj->set($assigned);
                        $assign_obj->save();
                    }
                }
            }
        }
        if(isset($segmentdata['tmtcid'])) {
            $transptdata['tmpsid'] = $this->data[self::PRIMARY_KEY];

            $transptdata = $segmentdata['tmtcid'];

            /* Initialize the object */
            if(is_array($transptdata)) {
                foreach($transptdata as $checksum => $data) {
                    $chkdata = $data;
                    rsort($chkdata);
                    if(is_array($chkdata[0])) {
                        foreach($data as $id => $transit) {
                            if(!isset($transit['flightNumber'])) {
                                continue;
                            }
                            $transp_obj = new TravelManagerPlanTransps();
                            $transit[self::PRIMARY_KEY] = $this->data[self::PRIMARY_KEY];
                            //$transit['tmtcid'] = $category;

                            $transp_obj->set($transit);
                            $transp_obj->save();
                        }
                    }
                    else {
                        if(isset($data['transpType']) && empty($data['transpType']) || isset($data['tmtcid']) && empty($data['tmtcid']) || (isset($data['fare']) && empty($data['fare']))) {
                            $transp_errorcode = 2;
                            if(empty($data['tmtcid'])) {
                                $field = $lang->trasptype;
                            }
                            else {
                                $field = $lang->transpfees;
                            }
                            $errorhandler->record('requiredfields', $field);
                            if(isset($data['tmtcid']) && empty($data['tmtcid']) && (isset($data['fare']) && empty($data['fare']))) {
                                unset($transp_errorcode);
                            }
                            continue;
                        }
                        $transp_obj = new TravelManagerPlanTransps();
                        // $data['tmtcid'] = $category;
                        $data[self::PRIMARY_KEY] = $this->data[self::PRIMARY_KEY];
                        $transp_obj->set($data);
                        $transp_obj->save();
                    }
                }
                unset($chkdata);
            }
        }

        if(isset($segmentdata['tmhid'])) {
            $leave_obj = $this->get_plan()->get_leave();
            $leavedays = abs($leave_obj->toDate - $leave_obj->fromDate);
            $leavedays = floor($leavedays / (60 * 60 * 24));
            foreach($segmentdata['tmhid'] as $checksum => $hotel) {
//                if(!empty($checksum)) {
//                    $hotelacc = TravelManagerPlanaccomodations::get_data(array('inputChecksum' => $checksum));
//                    /* if hotel not exist in segment accomodation & is not selected Skip! */
//                }
//                if(!is_object($hotelacc) && empty($checksum)) {
//                    continue;
//                }
                $validate_fields = array('priceNight', 'numNights', 'currency');
//                foreach($validate_fields as $hotelfield) {
//                    if(empty($hotel[$hotelfield])) {
//                        return;
//                    }
//                }//////
                if(!isset($hotel['tmhid']) || empty($hotel['tmhid'])) {
                    continue;
                }
                if($hotel['numNights'] > $leavedays) {
                    $hotel = new TravelManagerHotels($hotel['tmhid']);
                    $errorhandler->record($lang->numnightsexceeded.'<br/>', $hotel->name);
                    return $this;
                }
                $hoteldata['tmhid'] = $hotel['tmhid'];
                $hoteldata['inputChecksum'] = $checksum;
                $hoteldata['tmpsid'] = $this->data[self::PRIMARY_KEY];
                $hoteldata['priceNight'] = $hotel['priceNight'];
                $hoteldata['currency'] = $hotel['currency'];
                $hoteldata['numNights'] = $hotel['numNights'];
                $hoteldata['paidBy'] = $hotel['entites'];
                $hoteldata['paidById'] = $hotel['paidById'];
                $accod_obj = new TravelManagerPlanaccomodations();
                $accod_obj->set($hoteldata);
                $accod_obj->save();
            }
        }

        $additionalexpenses = $segmentdata['expenses'];
        if(is_array($additionalexpenses)) {
            foreach($additionalexpenses as $expense) {
                $expensestdata['tmpsid'] = $this->data[self::PRIMARY_KEY];
                $expensestdata['createdBy'] = $core->user['uid'];
                $expensestdata['tmetid'] = $expense['tmetid'];
                $expensestdata['expectedAmt'] = $expense['expectedAmt'];
                $expensestdata['currency'] = $expense['currency'];
                $expensestdata['actualAmt'] = $expense['actualAmt'];
                $expensestdata['description'] = $expense['description'];
                $expensestdata['paidBy'] = $expense['paidBy'];
                $expensestdata['paidById'] = $expense['paidById'];
                $expenses_obj = new Travelmanager_Expenses();
                $expenses_obj->set($expensestdata);
                $expenses_obj->save();
                $this->errorcode = 0;
            }
        }

        $finances_objs = $segmentdata['tmpfid'];
        if(is_array($finances_objs)) {
            foreach($finances_objs as $finances) {
                $financedata['tmpsid'] = $this->data[self::PRIMARY_KEY];
                $financedata['amount'] = $finances['amount'];
                $financedata['currency'] = $finances['currency'];
                $financedata['inputChecksum'] = $finances['inputChecksum'];
                $finance_obj = new TravelManagerPlanFinance();
                $finance_obj->set($financedata);
                $finance_obj->save();
                //   $this->errorcode = 0;
            }
        }

        if(isset($transp_errorcode) && !empty($transp_errorcode)) {
            $this->errorcode = $transp_errorcode;
        }

        return $this;
    }

    public function update(array $segmentdata) {
        global $db, $core, $errorhandler, $lang;
        if(!is_numeric($segmentdata['toDate'])) {
            $segmentdata['toDate'] = strtotime($segmentdata['toDate']);
            $segmentdata['fromDate'] = strtotime($segmentdata['fromDate']);
        }

        $valid_fields = array('fromDate', 'toDate', 'originCity', 'destinationCity', 'reason', 'isNoneBusiness', 'noAccomodation', 'eid', 'affid');
        /* Consider using array intersection */
        foreach($valid_fields as $attr) {
            $segmentnewdata[$attr] = $segmentdata[$attr];
        }

        $segmentnewdata['modifiedBy'] = $core->user['uid'];
        $segmentnewdata['modifiedOn'] = TIME_NOW;
        $db->update_query(self::TABLE_NAME, $segmentnewdata, self::PRIMARY_KEY.'='.intval($this->data[self::PRIMARY_KEY]));
        $segpurposes = $segmentdata['purpose'];
        if(is_array($segpurposes)) {
            $db->delete_query('travelmanager_plan_segpurposes', "tmpsid='".$this->data[self::PRIMARY_KEY]."'");
            foreach($segpurposes as $purpose) {
                $purpose_data['purpose'] = $purpose;
                $purpose_data['tmpsid'] = $this->data[self::PRIMARY_KEY];
                $segmentpurpose_obj = new TravelManagerPlanSegPurposes();
                $segmentpurpose_obj->set($purpose_data);
                $segmentpurpose_obj->save();
            }
        }
        if(is_array($segmentdata['assign'])) {
            foreach($segmentdata['assign'] as $type => $none) {
                if(is_array($none)) {
                    if($type == 'affid') {
                        $assigned['type'] = 'affiliate';
                    }
                    elseif($type == 'eid') {
                        $assigned['type'] = 'entity';
                    }
                    $assigned['tmpsid'] = $this->data[self::PRIMARY_KEY];
                    foreach($none as $key => $id) {
                        if(empty($id)) {
                            continue;
                        }
                        $assigned['primaryId'] = $id;
                        $assigned['inputChecksum'] = $key;
                        $assign_obj = new TravelManagerPlanAffient();
                        $assign_obj->set($assigned);
                        $assign_obj->save();
                    }
                }
            }
        }
        $transptdata = $segmentdata['tmtcid'];
        $trasnp_count = $transp_errorcode = 0;
        if(is_array($transptdata)) {

            foreach($transptdata as $checksum => $data) {
                $chkdata = $data;
                rsort($chkdata);
                if(is_array($chkdata[0])) {
                    foreach($data as $id => $transit) {
                        if(!isset($transit['flightNumber'])) {
                            continue;
                        }
                        $transp_count++;
                        $flightnumber = $transit['flightNumber'];
                        $transit['paidBy'] = $transit['paidBy'];
                        $transit['paidById'] = $transit['paidById'];
                        $transp_obj = new TravelManagerPlanTransps();
                        $transit[self::PRIMARY_KEY] = $this->data[self::PRIMARY_KEY];
                        $transit['tmtcid'] = $transit['tmtcid'];

                        $transp_obj->set($transit);
                        $transp_obj->save();
                    }
                    /* Delete Flight if not checked on modify segment */
                    if(!isset($flightnumber)) {
                        $transpseg = TravelManagerPlanTransps::get_data(array('inputChecksum' => $checksum));
                        if(is_object($transpseg)) {
                            $db->delete_query('travelmanager_plan_transps', 'tmpltid='.$transpseg->tmpltid.'');
                        }
                    }
                }
                else {
                    if(isset($data['transpType']) && empty($data['transpType']) || isset($data['tmtcid']) && empty($data['tmtcid']) || (isset($data['fare']) && empty($data['fare']))) {
                        $transp_errorcode = 2;
                        if(empty($data['tmtcid'])) {
                            $field = $lang->trasptype;
                        }
                        else {
                            $field = $lang->transpfees;
                        }
                        $errorhandler->record('requiredfields', $field);
                        if(isset($data['tmtcid']) && empty($data['tmtcid']) && (isset($data['fare']) && empty($data['fare']))) {
                            unset($transp_errorcode);
                        }
                        continue;
                    }
                    $transp_obj = new TravelManagerPlanTransps();
                    if(isset($data['todelete']) && !empty($data['todelete'])) {
                        $transp_obj = TravelManagerPlanTransps::get_data(array('tmpsid' => $this->data[self::PRIMARY_KEY], 'tmtcid' => $data['tmtcid']));
                        if(is_object($transp_obj)) {
                            $db->delete_query('travelmanager_plan_transps', 'tmtcid='.intval($data['tmtcid']).' AND tmpsid ='.$this->data[self::PRIMARY_KEY].'');
                        }
                        continue;
                    }
                    $transp_count++;
                    // $data['tmtcid'] = $category;
                    $data[self::PRIMARY_KEY] = $this->data[self::PRIMARY_KEY];
                    $transp_obj->set($data);
                    $transp_obj->save();
                }
            }
            unset($chkdata);
        }
        if($transp_count == 0) {
            $transp_errorcode = 2;
            $errorhandler->record('requiredfields', 'Transportations');
        }
        if(is_array($segmentdata['tmhid'])) {
            $segment_hotels['tmhid'] = $segmentdata['tmhid'];
            if(is_array($segment_hotels['tmhid'])) {

                $leave_obj = $this->get_plan()->get_leave();
                $leavedays = abs($leave_obj->toDate - $leave_obj->fromDate);
                $leavedays = floor($leavedays / (60 * 60 * 24));

                $validate_fields = array('priceNight', 'numNights', 'currency');
                foreach($segment_hotels['tmhid'] as $checksum => $hotel) {
                    $hotelacc = TravelManagerPlanaccomodations::get_data(array('inputChecksum' => $checksum));  //$hotel[tmhid]
                    if(!isset($hotel['tmhid']) || empty($hotel['tmhid'])) {
                        if(is_object($hotelacc)) {
                            $hotelacc->delete();
                        }
                        continue;
                    }

                    if($hotel['numNights'] > $leavedays) {
                        $hotel = new TravelManagerHotels($hotel['tmhid']);
                        $this->errorcode = 9;
                        $errorhandler->record($lang->numnightsexceeded.'<br/>', $hotel->name);
                        return $this;
                    }

                    $hoteldata['tmhid'] = $hotel['tmhid'];
                    $hoteldata['tmpsid'] = $this->data[self::PRIMARY_KEY];
                    $hoteldata['priceNight'] = $hotel['priceNight'];
                    $hoteldata['inputChecksum'] = $checksum;
                    $hoteldata['numNights'] = $hotel['numNights'];
                    $hoteldata['currency'] = $hotel['currency'];
                    $hoteldata['paidBy'] = $hotel['entites'];
                    $hoteldata['paidById'] = $hotel['paidById'];
                    $accod_obj = new TravelManagerPlanaccomodations();
                    $accod_obj->set($hoteldata);
                    $accod_obj->save();
                }
            }
        }

        $additionalexpenses = $segmentdata['expenses'];
        if(is_array($additionalexpenses)) {
            foreach($additionalexpenses as $expense) {
                $expensestdata['tmpsid'] = $this->data[self::PRIMARY_KEY];
                $expensestdata['createdBy'] = $core->user['uid'];
                $expensestdata['tmetid'] = $expense['tmetid'];
                $expensestdata['tmeid'] = $expense['tmeid'];
                $expensestdata['expectedAmt'] = $expense['expectedAmt'];
                $expensestdata['currency'] = $expense['currency'];
                $expensestdata['actualAmt'] = $expense['actualAmt'];
                $expensestdata['description'] = $expense['description'];
                $expensestdata['paidBy'] = $expense['paidBy'];
                $expensestdata['paidById'] = $expense['paidById'];
                $expenses_obj = new Travelmanager_Expenses();
                $expenses_obj->set($expensestdata);
                $expenses_obj->save();
                $this->errocode = 0;
            }
        }
        $finances_objs = $segmentdata['tmpfid'];
        if(is_array($finances_objs)) {
            foreach($finances_objs as $finances) {
                $financedata['amount'] = $finances['amount'];
                if(is_empty($financedata['amount'])) {
                    continue;
                }
                $financedata['tmpsid'] = $this->data[self::PRIMARY_KEY];
                $financedata['currency'] = $finances['currency'];
                $financedata['inputChecksum'] = $finances['inputChecksum'];
                $finance_obj = new TravelManagerPlanFinance();
                $finance_obj->set($financedata);
                $finance_obj->save();
            }
        }

        if(isset($transp_errorcode) && !empty($transp_errorcode)) {
            $this->errorcode = $transp_errorcode;
        }

        return $this;
    }

    public function set(array $data) {
        foreach($data as $name => $value) {
            $this->data[$name] = $value;
        }
    }

    public function __set($name, $value) {
        $this->data[$name] = $value;
    }

    /* call the Magical function  get to acces the private attributes */
    public function __get($name) {
        if(array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
    }

//    public function save(array $data = array()) {
//        global $core;
//
//        if(empty($data)) {
//            $data = $this->data;
//        }//get object of and the id and set data and save
//        $tmpsegment = TravelManagerPlanSegments::get_segments(array(TravelManagerPlan::PRIMARY_KEY => $data[TravelManagerPlan::PRIMARY_KEY], 'fromDate' => $data['fromDate'], 'toDate' => $data['toDate']));
//        if(is_object($tmpsegment)) {
//            $this->data['tmpsid'] = $tmpsegment->tmpsid;
//            $tmpsegment->update($data);
//        }
//        else {
//            $this->create($data);
//        }
//    }

    public static function get_segment_byattr($attr, $value, $operator = array()) {
        $data = new DataAccessLayer(__CLASS__, self::TABLE_NAME, self::PRIMARY_KEY);
        return $data->get_objects_byattr($attr, $value, $operator);
    }

    public static function get_segments($filters = null, array $configs = array()) {
        $data = new DataAccessLayer(__CLASS__, self::TABLE_NAME, self::PRIMARY_KEY);
        return $data->get_objects($filters, $configs);
    }

    public function get_plan() {
        return new TravelManagerPlan($this->data['tmpid']);
    }

    public function get_origincity() {
        return new Cities($this->data['originCity']);
    }

    public function get_destinationcity() {
        return new Cities($this->data['destinationCity']);
    }

    public function get() {
        return $this->data;
    }

    public function get_createdBy() {
        return new Users($this->data['createdBy']);
    }

    public function get_errorcode() {
        return $this->errorcode;
    }

    public function get_modifiedBy() {
        return new Users($this->data['modifiedBy']);
    }

    public function get_transportations() {
        return TravelManagerPlanTransps::get_data(array('tmpsid' => $this->data[self::PRIMARY_KEY]), array('returnarray' => true));
    }

//    public function get_transportationscat() {
//        /* get the transportations categories of the transportations related to the segment object we call */
//        return TravelManagerTranspCategories::get_data(array('tmtcid' => $this->get_transportations()->tmtcid));
//    }

    public function get_expenses($config = array()) {
        return Travelmanager_Expenses::get_data(array('tmpsid' => $this->data[self::PRIMARY_KEY]), $config);
    }

    public function get_finance($config = array()) {
        return TravelManagerPlanFinance::get_data(array('tmpsid' => $this->data[self::PRIMARY_KEY]), $config);
    }

    private function get_allapidata() {
        return json_decode($this->apiFlightdata);
    }

    public function display_paidby($paidby, $paidbyid) {
        global $core;
        switch($paidby) {
            case 'myaffiliate':
                $object = new Affiliates($core->user['mainaffiliate']);
//$paidby = $affiliate->name;
                break;
            case 'anotheraff':
                $object = new Affiliates($paidbyid);
// $paidby = $affiliate->name;
                break;
            default:
                $object = $paidby;
        }
        return $object;
    }

    public function parse_segment() {
        global $template, $lang, $core, $db;
        $segmentdate = date('l F d, Y', $this->fromDate);
        $segmentdate .=' - '.date('l F d, Y', $this->toDate);
        $numfmt = new NumberFormatter($lang->settings['locale'], NumberFormatter::CURRENCY);

        $destination_cities = $this->get_origincity()->name.' - '.$this->get_destinationcity()->name;
        $transp_objs = TravelManagerPlanTransps::get_data(array('tmpsid' => $this->data[self::PRIMARY_KEY]), array('returnarray' => true));
        if(is_array($transp_objs)) {
            foreach($transp_objs as $transportation) {
                $transportation->transpType = $transportation->get_transpcategory()->title;
                $paidby = $this->display_paidby($transportation->paidBy, $transportation->paidById);
                if(is_object($paidby)) {
                    $paidby = $paidby->get_displayname();
                }
                if(!empty($transportation->transpDetails)) {
                    $flight_details = TravelManagerAirlines::parse_bestflight($transportation->transpDetails, array(), $sequence, 'selectedflight', array('isMinCost' => $transportation->isMinCost));
                    //  $transp_flightdetails = json_decode($transportation->flightDetails, true);
                    //  $flight_details = $this->parse_flightdetails($transp_flightdetails);
                }
                $tocurr = new Currencies(840);
                $fare = $transportation->get_convertedamount($tocurr);
                $fromcurr = new Currencies($transportation->currency);
                if($transportation->fare != 0 && $fare == 0) {
                    $tocurr->save_fx_rate_fromsource('http://rate-exchange.appspot.com/currency?from='.$fromcurr->alphaCode.'&to='.$tocurr->alphaCode.'', $fromcurr->numCode, $tocurr->numCode);
                    $fare = $transportation->get_convertedamount($fromcurr);
                }
                $fare = $numfmt->formatCurrency(($fare), "USD");
                if($fromcurr != $tocurr) {
                    $fare .='<br/><small>'.$numfmt->formatCurrency($transportation->fare, $fromcurr->alphaCode).'</small>';
                }

                if($transportation->isRoundTrip) {
                    $transportation->isRoundTrip_output = $lang->roundtrip;
                }
                eval("\$segment_transpdetails .= \"".$template->get('travelmanager_viewplan_transpsegments')."\";");
                $flight_details = '';
            }
        }
        if($this->noAccomodation == 0) {
            $accomd_objs = TravelManagerPlanaccomodations::get_data(array('tmpsid' => $this->data[self::PRIMARY_KEY]), array('returnarray' => true));
            if(is_array($accomd_objs)) {
                foreach($accomd_objs as $accomdation) {
                    $paidby = $this->display_paidby($accomdation->paidBy, $accomdation->paidById);
                    if(is_object($paidby)) {
                        $paidby = $paidby->get_displayname();
                    }
                    $tocurr = new Currencies(840);
                    $hotel = $accomdation->get_hotel();
                    $hotel_cur = new Currencies($hotel->currency);
                    $cur_dispname = $hotel_cur->get_displayname();
                    $pricenight = $accomdation->get_convertedamount($tocurr);
                    $fromcurr = new Currencies($accomdation->currency);
                    if($accomdation->priceNight != 0 && $pricenight == 0) {
                        $tocurr->save_fx_rate_fromsource('http://rate-exchange.appspot.com/currency?from='.$fromcurr->alphaCode.'&to='.$tocurr->alphaCode.'', $fromcurr->numCode, $tocurr->numCode);
                        $pricenight = $accomdation->get_convertedamount($fromcurr);
                    }
                    if($fromcurr != $tocurr) {
                        $priceinbasecurr .='<br/><small>'.$numfmt->formatCurrency($accomdation->numNights * $accomdation->priceNight, $fromcurr->alphaCode).'</small>';
                    }
                    $segment_hotel .= '<div style = "width:70%; display: inline-block;"> '.$lang->checkin.' '.$hotel->name.'<br>'.$lang->address.': '.$hotel->addressLine1.'  '.$lang->phone.':'.$hotel->phone.'<span style = "margin-bottom:10px;display:block;"><em>'.$accomdation->numNights.' '.$lang->night.' x $'.$pricenight.' </em></span><br>'.$lang->avgprice.': '.$hotel->avgPrice.'-'.$cur_dispname.'</div>'; // fix the html parse multiple hotl
                    //$segment_hotel.='<br>'.$lang->address.': '.$hotel->addressLine1.'<br>'.$lang->phone.':'.$hotel->phone.'';
//    $segment_hotel .= '<div style = " width:30%; display: inline-block;"> <span> '.$lang->night.' '.$accomdation->numNights.' at $ '.$accomdation->priceNight.' '.$lang->night.'</span></div>'; // fix the html parse multiple hotl
                    $segment_hotel .= '<div style = "width:25%; display: inline-block;font-size:14px; font-weight:bold;text-align:right;margin-left:5px;vertical-align:top;"><span> '.$numfmt->formatCurrency(($accomdation->numNights * $pricenight), "USD").$priceinbasecurr.'</span> <br/> <small style = "font-weight:normal;">[paid by: '.$paidby.']</small></div>'; // fix the html parse multiple hotl
//   $segment_hotelprice .='<div style = " width:45%; display: block;"> Nights '.$accomdation->numNights.' at $ '.$accomdation->priceNight.'/Night</div>';
                }
            }
        }
        else {
            $segment_hotel = 'No Accomodations';
        }
        $additional_expenses = Travelmanager_Expenses::get_data(array('tmpsid' => $this->tmpsid), array('simple' => false, 'returnarray' => true));
        if(is_array($additional_expenses)) {
            foreach($additional_expenses as $additionalexp) {
                $additionalexp_type = new TravelManager_Expenses_Types($additionalexp->tmetid);
                $additional_expenses_details .= '<div style = "display:block;padding:5px 0px 5px 0px;">';
                $paidby = $this->display_paidby($additionalexp->paidBy, $additionalexp->paidById);
                if(is_object($paidby)) {
                    $paidby = $paidby->get_displayname();
                }
                if($additionalexp_type->title == 'Other') {
                    $additionalexp_type->title = $additionalexp->description;
                }

                $tocurr = new Currencies(840);
                $expectedAmt = $additionalexp->get_convertedamount($tocurr);
                $fromcurr = new Currencies($additionalexp->currency);
                if($additionalexp->expectedAmt != 0 && $expectedAmt == 0) {
                    $tocurr->save_fx_rate_fromsource('http://rate-exchange.appspot.com/currency?from='.$fromcurr->alphaCode.'&to='.$tocurr->alphaCode.'', $fromcurr->numCode, $tocurr->numCode);
                    $expectedAmt = $additionalexp->get_convertedamount($fromcurr);
                }
                if($tocurr != $fromcurr) {
                    $expectedAmtinbasecurr = '<br/><small>'.$numfmt->formatCurrency($additionalexp->expectedAmt, $fromcurr->alphaCode).'</small>';
                }
                $additional_expenses_details .= '<div style = "width:70%;display:inline-block;">'.$additionalexp_type->title.'</div>';
                $additional_expenses_details .= '<div style = "width:25%;display:inline-block;font-size:14px;font-weight:bold;text-align:right;vertical-align:top;">'.$numfmt->formatCurrency($expectedAmt, "USD").$expectedAmtinbasecurr.'<br/><small style="font-weight:normal;">[paid by: '.$paidby.' ] </small> </div>';
                $additional_expenses_details .= '</div>';
            }
        }
        $finances = TravelManagerPlanFinance::get_data(array('tmpsid' => $this->tmpsid), array('simple' => false, 'returnarray' => true));
        if(is_array($finances)) {
            foreach($finances as $finance) {
                if($finance->amount == 0) {
                    continue;
                }
                $currency_finan = new Currencies($finance->currency);
                $advanced_payments.='<div style="border-bottom: 1px;border-bottom-style: solid;border-bottom-color: greenyellow">';
                $advanced_payments.='<div style = "width:70%; display: inline-block;">'.$lang->amount.' in '.$currency_finan->get_displayname().'</div>';
                $advanced_payments.='<div style = "width:25%;display:inline-block;font-size:14px;font-weight:bold;text-align:right;vertical-align:top;">'.$finance->amount.'-'.$currency_finan->get_displayname().'</div>';
                $advanced_payments.='</div>';
            }

            eval("\$segment_advancedpayments  = \"".$template->get('travelmanager_viewplan_finances')."\";");
        }
        eval("\$segment_accomdetails  = \"".$template->get('travelmanager_viewplan_accomsegments')."\";");
        eval("\$segment_details .= \"".$template->get('travelmanager_viewplan_segments')."\";");
        return $segment_details;
    }

    public function parse_expensesummary() {
        global $template, $db, $lang;

        $numfmt = new NumberFormatter($lang->settings['locale'], NumberFormatter::CURRENCY);
        $fxrate_query['transp'] = "(CASE WHEN tmpt.currency =840 THEN 1 ELSE (SELECT rate FROM currencies_fxrates WHERE baseCurrency=tmpt.currency AND currency=840 ORDER BY date DESC LIMIT 0, 1)END)";
        $query = $db->query("SELECT tmpltid, tmtcid, sum(fare*{$fxrate_query['transp']}) AS fare FROM ".Tprefix."travelmanager_plan_transps tmpt WHERE tmpsid IN (SELECT tmpsid FROM travelmanager_plan_segments WHERE tmpid =".intval($this->tmpid).") GROUP By tmtcid");
        if($db->num_rows($query) > 0) {
            while($transpexp = $db->fetch_assoc($query)) {
                $transpcat = new TravelManagerTranspCategories($transpexp['tmtcid']);
                $expenses_details .= '<div style = "display:block;padding:5px 0px 5px 0px;">';
                $expenses_details .= '<div style = "width:85%;display:inline-block;">'.$transpcat->title.'</div>';
                $transp_obj = new TravelManagerPlanTransps();
                $expenses_details .= '<div style = "width:10%;display:inline-block;text-align:right;">$'.round($transpexp['fare'], 2).'</div>';
                $expenses_details .= '</div>';
                $expenses_total += $transpexp['fare'];
            }
            /* get hotel expences total night of each segment */
        }

        $fxrate_query['accomodation'] = "(CASE WHEN tmpa.currency =840 THEN 1 ELSE (SELECT rate FROM currencies_fxrates WHERE baseCurrency=tmpa.currency AND currency=840
				ORDER BY date DESC LIMIT 0, 1) END)";
        $expenses['accomodation'] = $db->fetch_field($db->query("SELECT SUM(priceNight*{$fxrate_query['accomodation']}*numNights) AS total FROM ".Tprefix."travelmanager_plan_accomodations tmpa WHERE tmpsid IN (SELECT tmpsid FROM travelmanager_plan_segments WHERE tmpid=".intval($this->tmpid).")"), 'total');
        $expenses['accomodation'] = round($expenses['accomodation'], 2);
        if(empty($expenses['accomodation'])) {
            $expenses['accomodation'] = 0;
        }
        $expenses_total += $expenses['accomodation'];
        // $expenses_subtotal = $numfmt->formatCurrency($expenses_total, "USD");

        $fxrate_query['expenses'] = "(CASE WHEN tme.currency =840 THEN 1 ELSE (SELECT rate FROM currencies_fxrates WHERE baseCurrency=tme.currency AND currency=840
				ORDER BY date DESC LIMIT 0, 1)END)";
        $additional_expenses = $db->query("SELECT tmetid,sum(expectedAmt*{$fxrate_query['expenses']}) AS expectedAmt,description FROM ".Tprefix."travelmanager_expenses tme WHERE tmpsid IN (SELECT tmpsid FROM travelmanager_plan_segments WHERE tmpid =".intval($this->tmpid).") GROUP by tmetid");
        if($db->num_rows($additional_expenses) > 0) {
            $additional_expenses_details = '<div style="display:block;padding:5px 0px 5px 0px;width:15%;" class="subtitle">'.$lang->addexp.'</div>';
            while($additionalexp = $db->fetch_assoc($additional_expenses)) {
                $additionalexp_type = new TravelManager_Expenses_Types($additionalexp['tmetid']);
                $additional_expenses_details .= '<div style = "display:block;padding:5px 0px 5px 0px;">';
                $additional_expenses_details .= '<div style = "width:85%;display:inline-block;">'.$additionalexp_type->title.'</div>';
                $additional_expenses_details .= '<div style = "width:10%;display:inline-block;text-align:right;">'.$numfmt->formatCurrency(round($additionalexp['expectedAmt'], 2), "USD").'</div>';
                $additional_expenses_details .= '</div>';
                $expenses['additional'] += $additionalexp['expectedAmt'];
            }
            $additional_expenses_details .='<div style="display:block;padding:5px 0px 5px 0px;">';
            $additional_expenses_details .='<div style="display:inline-block;width:85%;">'.$lang->additionalexpensestotal.'</div><div style="width:10%; display:inline-block;text-align:right;font-weight:bold;">  '.$numfmt->formatCurrency($expenses['additional'], "USD").'</div></div>';
            $expenses_total += $expenses['additional'];
        }
        $finances = TravelManagerPlanFinance::get_data(array('tmpsid' => $this->tmpsid), array('simple' => false, 'returnarray' => true));
        if(is_array($finances)) {
            foreach($finances as $finance) {
                if($finance->amount == 0) {
                    contine;
                }
                $tocurr = new Currencies(840);
                $amount = $finance->get_convertedamount($tocurr);
                $fromcurr = new Currencies($finance->currency);
                if($amount == 0) {
                    $tocurr->save_fx_rate_fromsource('http://rate-exchange.appspot.com/currency?from='.$fromcurr->alphaCode.'&to='.$tocurr->alphaCode.'', $fromcurr->numCode, $tocurr->numCode);
                    $amount = $finance->get_convertedamount($fromcurr);
                }
                $total_fin_amount+=$amount;
            }
        }
        if($total_fin_amount != 0) {
            $amount_payedinadv.='<div style="border-bottom: 1px;border-bottom-style: solid;border-bottom-color: greenyellow">';
            $amount_payedinadv.='<div style = "width:85%;display:inline-block;">'.$lang->amountpayedinadvance.'</div>';
            $amount_payedinadv .= '<div style = "width:10%;display:inline-block;text-align:right;">'.$numfmt->formatCurrency($total_fin_amount, "USD").'</div>';
            $amount_payedinadv.='</div>';
            $expenses_total+=$total_fin_amount;
        }
        $expenses_total = $numfmt->formatCurrency($expenses_total, "USD");
// $expenses_total = round($expenses_total, 2);
        eval("\$segment_expenses  = \"".$template->get('travelmanager_viewplan_expenses')."\";");
        return $segment_expenses;
    }

    private function parse_flightdetails($flightdata) {
        global $template, $core;
        $allapi_data = $this->get_allapidata();
        if(is_array($flightdata)) {
// parse flight name
            foreach($flightdata[0]['slice'] as $slicenum => $slice) {
                foreach($slice['segment'] as $segmentnu => $segment) {
                    $flight[$segmentnu]['arrivaltime'] = date($core->settings['dateformat']." H:m", strtotime($segment[leg][0][arrivalTime]));
                    $flight[$segmentnu]['departuretime'] = date($core->settings['dateformat']." H:m", strtotime($segment[leg][0][departureTime]));
                    $flight[$segmentnu]['origin'] = $segment['leg'][0]['origin'];
                    $flight[$segmentnu]['destination'] = $segment['leg'][0]['destination'];
                    if(isset($segment['connectionDuration'])) {
                        $flight[$segmentnu]['connectionDuration'] = sprintf('%2dh %2dm', floor($segment['connectionDuration'] / 60), ($segment['connectionDuration'] % 60));
                        $connectionduration = '<small><div class = "display:block; border_top border_bottom" style = "padding: 10px; font-style: italic;">Connection: '.$flight[$segmentnu]['connectionDuration'].'</div></small>';
                    }
                    for($carriernum = 0; $carriernum < count($allapi_data->trips->data->carrier); $carriernum++) {

                        if($segment['flight'] ['carrier'] == $allapi_data->trips->data->carrier[$carriernum]->code) {
                            $flight['carrier'] = $allapi_data->trips->data->carrier[$carriernum]->name;
                            break;
                        }
                    }
                    $flight_details .='<small><div style = "width:40%; display:block;">'.$flight['carrier'].'</div>';
                    $flight_details .= '<div style = "width:55%; display:  block;">Departure '.$flight[$segmentnu]['departuretime'].' '.$flight[$segmentnu]['origin'].' Arrival '.$flight[$segmentnu]['arrivaltime'].' '.$flight[$segmentnu]['destination'].'</div></small>';
                    $flight_details .= $connectionduration;
                    unset($connectionduration, $flight[$segmentnu]['connectionDuration']);
                }
            }
            return $flight_details;
        }
    }

    public function parse_hotels($sequence, array $hotels) {
        global $template, $lang, $core;

        if(is_array($hotels)) {
            foreach($hotels as $hotel) {
                $approved_hotels = $hotel->get();
                if(empty($approved_hotels['avgPrice'])) {
                    $approved_hotels['avgPrice'] = $lang->na;
                }
                if($approved_hotels['currency']) {
                    $currency_obj = new Currencies($approved_hotels['currency']);
                    if(is_object($currency_obj)) {
                        $currency_dispname = $currency_obj->get_displayname();
                    }
                }
                $selectedhotel = TravelManagerPlanaccomodations::get_data(array(self::PRIMARY_KEY => $this->data[self ::PRIMARY_KEY], TravelManagerHotels::PRIMARY_KEY => $hotel->tmhid), array('simple' => false));
                if(is_object($selectedhotel)) {
                    $hotel->isChecked = " checked='checked'";
                    $rescurrency = $selectedhotel->get_currency();
                    $rescurrency_id = $rescurrency->get_id();
                    $checksum = $selectedhotel->inputChecksum;
                    $selected_hotel[$sequence][$checksum]['displaystatus'] = "display:none;";
                    if(!empty($selectedhotel->paidById)) {
                        $selected_hotel[$sequence][$checksum]['displaystatus'] = "display:block;";
                        $affiliate = new Affiliates($selectedhotel->paidById);
                    }
                }
                else {
                    $checksum = generate_checksum('accomodation');
                    $selected_hotel[$sequence][$checksum]['displaystatus'] = "display:none;";
                }

                $review_tools .= '<a href="#'.$approved_hotels['tmhid'].'" id="hotelreview_'.$approved_hotels['tmhid'].'_travelmanager/plantrip_loadpopupbyid" rel="hotelreview_'.$hotel->tmhid.'" title="'.$lang->hotelreview.'"><img src="'.$core->settings['rootdir'].'/images/icons/reviewicon.png" title="'.$lang->readhotelreview.'" alt="'.$lang->readhotelreview.'" border="0" width="16" height="16"></a>';

                $checkbox_hotel = '<input aria-describedby="ui-tooltip-155" title="" name="segment['.$sequence.'][tmhid]['.$checksum.'][tmhid]" id="segment['.$sequence.']['.$checksum.'][tmhid]" value="'.$hotel->tmhid.'" type="checkbox">'.$hotel->name;

                // $paidby_onchangeactions = 'if($(this).find(":selected").val()=="anotheraff"){$("#"+$(this).find(":selected").val()+"_accomodations_'.$sequence.'_'.$checksum.'").show();}else{$("#anotheraff_accomodations_'.$sequence.'_'.$checksum.'").hide();}';
                $paidby_onchangeactions = 'if($(this).find(":selected").val()=="anotheraff"){$("#"+$(this).find(":selected").val()+"_accomodations_'.$sequence.'_'.$checksum.'").effect("highlight",{ color: "#D6EAAC"}, 1500).find("input").first().focus().val("");}else{$("#anotheraff_accomodations_'.$sequence.'_'.$checksum.'").hide();}';
                $paidby_entities = array(
                        'myaffiliate' => $lang->myaffiliate,
                        'supplier' => $lang->supplier,
                        'client' => $lang->client,
                        'myself' => $lang->myself,
                        'anotheraff' => $lang->anotheraff
                );
                $selectlists['paidBy'] = parse_selectlist('segment['.$sequence.'][tmhid]['.$checksum.'][entites]', 5, $paidby_entities, $selectedhotel->paidBy, 0, $paidby_onchangeactions);
                $numfmt = new NumberFormatter($lang->settings['locale'], NumberFormatter::DECIMAL);
                $numfmt->setPattern("#0.###");
                if(is_object($selectedhotel)) {
                    $selectedhotel->total = $numfmt->format($selectedhotel->priceNight * $selectedhotel->numNights);
                }
                $mainaffobj = new Affiliates($core->user['mainaffiliate']);
                $destcity_obj = $this->get_destinationcity();
                $currencies[] = $destcity_obj->get_country()->get_maincurrency();
                $currencies[] = $mainaffobj->get_country()->get_maincurrency();
                $currencies[] = new Currencies(840, true);
                $currencies[] = new Currencies(978, true);
                $currencies = array_unique($currencies);
                $currencies_list = parse_selectlist('segment['.$sequence.'][tmhid]['.$checksum.'][currency]', '3', $currencies, $rescurrency_id, '', '', array('id' => 'currency_'.$sequence.'_'.$checksum.'_list'));
                $leavedays = abs($this->toDate - $this->fromDate);
                $leavedays = floor($leavedays / (60 * 60 * 24));

                eval("\$hotelssegments_output  .= \"".$template->get('travelmanager_plantrip_segment_hotels')."\";");
                $review_tools = $paidby_details = $currencies_list = $currencies = $selected_hotel = $checkbox_hotel = '';
            }
        }

        return $hotelssegments_output;
    }

    public function get_accomodations($config = array()) {
        return TravelManagerPlanaccomodations::get_data(array('tmpsid' => $this->data[self::PRIMARY_KEY]), $config);
    }

}
?>