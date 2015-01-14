<?php
/*
 * Copyright © 2014 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: BudgetLines_class.php
 * Created:        @tony.assaad    Dec 2, 2014 | 10:05:29 AM
 * Last Update:    @tony.assaad    Dec 2, 2014 | 10:05:29 AM
 */

/**
 * Description of BudgetLines_class
 *
 * @author tony.assaad
 */
/* Budgeting Line Class --START */

class BudgetLines {
    private $budgetline = array();

    const PRIMARY_KEY = 'blid';
    const TABLE_NAME = 'budgeting_budgets_lines';
    const DISPLAY_NAME = '';
    const SIMPLEQ_ATTRS = '*';
    const CLASSNAME = __CLASS__;

    public function __construct($budgetlineid = '') {
        if(!empty($budgetlineid)) {
            $this->budgetline = $this->read($budgetlineid);
        }
    }

    private function read($budgetlineid) {
        global $db;
        if(isset($budgetlineid) && !empty($budgetlineid)) {
            return $db->fetch_assoc($db->query("SELECT bdl.*, bd.bid
                                                FROM ".Tprefix."budgeting_budgets bd
                                                JOIN ".Tprefix."budgeting_budgets_lines bdl ON (bd.bid=bdl.bid)
                                                WHERE bdl.blid='".intval($budgetlineid)."'"));
        }
    }

    public function create($budgetline_data = array()) {
        global $db, $core;

        if(is_array($budgetline_data)) {
            if(empty($budgetline_data['createdBy'])) {
                $budgetline_data['createdBy'] = $core->user['uid'];
            }
            if(empty($budgetline_data['businessMgr'])) {
                $budgetline_data['businessMgr'] = $core->user['uid'];
            }
            unset($budgetline_data['customerName'], $budgetline_data['blid']);

            $this->split_income($budgetline_data);
            $insertquery = $db->insert_query('budgeting_budgets_lines', $budgetline_data);
            if($insertquery) {
                $this->budgetline = $budgetline_data;
                $this->budgetline['blid'] = $db->last_id();
                $this->errorcode = 0;
            }
        }
    }

    public function update($budgetline_data = array()) {
        global $db, $core;
        unset($budgetline_data['customerName']);
        $budgetline_data['modifiedBy'] = $core->user['uid'];
        $budgetline_data['modifiedOn'] = TIME_NOW;

        $this->split_income($budgetline_data);

        if(!isset($budgetline_data['blid'])) {
            $budgetline_data['blid'] = $this->budgetline['blid'];
        }
        $db->update_query('budgeting_budgets_lines', $budgetline_data, 'blid='.$budgetline_data['blid']);
    }

    public function save_interco_line($data) {
        global $core;

        if(empty($data['interCompanyPurchase'])) {
            return;
        }
        $data_toremove = array('bid', 'blid', 'cid', 'interCompanyPurchase');
        $data_zerofill = array('localIncomePercentage', 'localIncomeAmount', 'invoicingEntityIncome');
        $budget = $this->get_budget();
        $data['inputChecksum'] = generate_checksum('bl');
        $data['linkedBudgetLine'] = $this->budgetline['blid'];
        $data['altCid'] = $budget->get_affiliate()->name;
        $data['customerCountry'] = $budget->get_affiliate()->country;
        $data['saleType'] = 6; //Need to be acquire through DAL where isInterCoSale

        if(!empty($this->budgetline['linkedBudgetLine'])) {
            $ic_budgetline = new BudgetLines($this->budgetline['linkedBudgetLine']);
            if(!empty($ic_budgetline->modifiedOn)) {
                return;
            }
            if(is_object($ic_budgetline)) {
                foreach($data_toremove as $attr) {
                    unset($data[$attr]);
                }
                foreach($data_zerofill as $attr) {
                    $data[$attr] = 0;
                }
                $ic_budgetline->update($data);
                return;
            }
        }

        $ic_budget = Budgets::get_data(array('affid' => $data['interCompanyPurchase'], 'spid' => $budget->spid, 'year' => $budget->year), array('simple' => false));
        if(!is_object($ic_budget)) {
            $ic_budget = new Budgets();
            $budgetdata_intercompany = array(
                    'identifier' => substr(uniqid(time()), 0, 10),
                    'year' => $budget->year,
                    'affid' => $data['interCompanyPurchase'],
                    'spid' => $budget->spid,
                    'createdBy' => $core->user['uid'],
                    'createdOn' => TIME_NOW
            );

            $ic_budget->save_budget($budgetdata_intercompany, null);
        }

        foreach($data_toremove as $attr) {
            unset($data[$attr]);
        }
        foreach($data_zerofill as $attr) {
            $data[$attr] = 0;
        }

        $data['bid'] = $ic_budget->bid;
        if(empty($data['bid'])) {
            $ic_budget = Budgets::get_data(array('affid' => $budgetdata_intercompany['affid'], 'spid' => $budget->spid, 'year' => $budget->year), array('simple' => false));
            $data['bid'] = $ic_budget->bid;
        }
        $ic_budgetline = new BudgetLines();
        $ic_budgetline->create($data);

        $this->update(array('linkedBudgetLine' => $ic_budgetline->blid));
    }

    private function split_income(&$budgetline_data) {
        global $core;
        if($core->usergroup['budgeting_canFillLocalIncome'] == 1) {
            if(!empty($budgetline_data['linkedBudgetLine']) && !isset($budgetline_data['blid'])) {
                if(empty($budgetline_data['interCompanyPurchase'])) {
                    return;
                }
            }
            if(empty($budgetline_data['localIncomeAmount']) && $budgetline_data['localIncomeAmount'] != '0') {
                if(!isset($budgetline_data['saleType'])) {
                    return;
                }

                $saletype = new SaleTypes($budgetline_data['saleType']);
                $budgetline_data['localIncomeAmount'] = $budgetline_data['income'];
                $budgetline_data['localIncomePercentage'] = 100;
                $budgetline_data['invoicingEntityIncome'] = 0;
                if($saletype->localIncomeByDefault == 0) {
                    $budgetline_data['localIncomeAmount'] = 0;
                    $budgetline_data['localIncomePercentage'] = 0;
                    $budgetline_data['invoicingEntityIncome'] = $budgetline_data['income'];
                }
            }
            else {
                $budgetline_data['invoicingEntityIncome'] = $budgetline_data['income'] - $budgetline_data['localIncomeAmount'];
            }
        }
    }

    public function delete_interco_line() {
        if(empty($this->budgetline['linkedBudgetLine'])) {
            return;
        }

        $linked_bdlineobj = new BudgetLines($this->budgetline['linkedBudgetLine']);
        /* If this is the initiator bugdet line, don't delete it */
        if(!empty($linked_bdlineobj->interCompanyPurchase)) {
            return;
        }
        /* If linked budget line has not been mondified, then delete it */
        if(empty($linked_bdlineobj->modifiedOn)) {
            $linked_bdlineobj->delete();
        }
    }

    public function delete() {
        global $db;
        $db->delete_query('budgeting_budgets_lines', 'blid='.$this->budgetline['blid']);
    }

    public function get_budget() {
        return new Budgets($this->budgetline['bid']);
    }

    public function get_customer() {
        return new Entities($this->budgetline['cid'], '', false);
    }

    public function get_product() {
        return new Products($this->budgetline['pid']);
    }

    public function get_saletype() {
        return $this->budgetline['saleType'];
    }

    public function get_createuser() {
        return new Users($this->budgetline['createdBy']);
    }

    public function get_businessMgr() {
        return new Users($this->budgetline['businessMgr']);
    }

    public function get_modifyuser() {
        return new Users($this->budgetline['modifiedBy']);
    }

    public function parse_country() {
        global $lang;

        if(!empty($this->budgetline['customerCountry'])) {
            $country = new Countries($this->budgetline['customerCountry']);
        }
        else {
            $country = new Countries($this->get_customer()->get()['country']);
        }

        $country_name = $country->get()['name'];
        if(empty($country_name)) {
            return $lang->na;
        }
        else {
            return $country_name;
        }
    }

    public static function get_budgetline_bydata($data) {
        global $db;
        if(is_array($data)) {
            if(!isset($data['bid']) || empty($data['bid'])) {
                return false;
            }
            $budgetline_bydataquery = $db->query("SELECT * FROM ".Tprefix."budgeting_budgets_lines WHERE pid='".$data['pid']."' AND cid='".$data['cid']."' AND altCid='".$db->escape_string($data['altCid'])."' AND saleType='".$data['saleType']."' AND bid='".$data['bid']."' AND customerCountry='".$data['customerCountry']."' AND psid='".$data['psid']."' AND businessMgr=".$data['businessMgr']);
            if($db->num_rows($budgetline_bydataquery) > 0) {
                return $db->fetch_assoc($budgetline_bydataquery);
            }
            return false;
        }
    }

    public static function get_data($filters = '', $configs = array()) {
        $data = new DataAccessLayer(self::CLASSNAME, self::TABLE_NAME, self::PRIMARY_KEY);
        return $data->get_objects($filters, $configs);
    }

    public static function get_aggregate_bycountry(Countries $country, $by, $filters = array(), $configs = array()) {
        global $db;

        $dal = new DataAccessLayer(self::CLASSNAME, self::TABLE_NAME, self::PRIMARY_KEY);
        if($configs['toCurrency']) {
            $fxrate_query = "*(CASE WHEN budgeting_budgets_lines.originalCurrency=".intval($configs['toCurrency'])." THEN 1 ELSE (SELECT rate FROM budgeting_fxrates WHERE affid=(SELECT affid FROM budgeting_budgets WHERE bid=budgeting_budgets_lines.bid) AND year=(SELECT year FROM budgeting_budgets WHERE bid=budgeting_budgets_lines.bid) AND fromCurrency=budgeting_budgets_lines.originalCurrency AND toCurrency=".intval($configs['toCurrency']).") END)";
        }

        if(isset($configs['vsAffid']) && !empty($configs['vsAffid'])) {
            $by = '(CASE '.$configs['vsAffid'].' = (SELECT affid FROM budgeting_budgets WHERE budgeting_budgets.bid = '.self::TABLE_NAME.'.bid) THEN localIncome ELSE (income-LocalIncome) END)';
        }

        $total = $db->fetch_assoc($db->query('SELECT SUM('.$by.$fxrate_query.') AS total, (CASE WHEN customerCountry = 0 THEN (SELECT country FROM entities WHERE entities.eid = '.self::TABLE_NAME.'.cid) ELSE customerCountry END) AS coid FROM '.self::TABLE_NAME.$dal->construct_whereclause_public($filters, $configs['operators']).' GROUP BY coid HAVING coid = '.$country->coid));
        return $total['total'];
    }

    public static function get_aggregate_byaffiliate(Affiliates $affiliate, $by, $filters = array(), $configs = array()) {
        global $db;

        $dal = new DataAccessLayer(self::CLASSNAME, self::TABLE_NAME, self::PRIMARY_KEY);

        if($configs['toCurrency']) {
            $fxrate_query = "*(CASE WHEN budgeting_budgets_lines.originalCurrency=".intval($configs['toCurrency'])." THEN 1 ELSE (SELECT rate FROM budgeting_fxrates WHERE affid=(SELECT affid FROM budgeting_budgets WHERE bid=budgeting_budgets_lines.bid) AND year=(SELECT year FROM budgeting_budgets WHERE bid=budgeting_budgets_lines.bid) AND fromCurrency=budgeting_budgets_lines.originalCurrency AND toCurrency=".intval($configs['toCurrency']).") END)";
        }
        $total = $db->fetch_assoc($db->query('SELECT SUM('.$by.$fxrate_query.') AS total, (SELECT affid FROM budgeting_budgets WHERE budgeting_budgets.bid = '.self::TABLE_NAME.'.bid) AS affid FROM '.self::TABLE_NAME.$dal->construct_whereclause_public($filters, $configs['operators']).' GROUP BY affid HAVING affid ='.$affiliate->affid));
        return $total['total'];
    }

    public static function get_aggregate_bysupplier(Entities $supplier, $by, $filters = array(), $configs = array()) {
        global $db;

        $dal = new DataAccessLayer(self::CLASSNAME, self::TABLE_NAME, self::PRIMARY_KEY);
        if($configs['toCurrency']) {
            $fxrate_query = "*(CASE WHEN budgeting_budgets_lines.originalCurrency=".intval($configs['toCurrency'])." THEN 1 ELSE (SELECT rate FROM budgeting_fxrates WHERE affid=(SELECT affid FROM budgeting_budgets WHERE bid=budgeting_budgets_lines.bid) AND year=(SELECT year FROM budgeting_budgets WHERE bid=budgeting_budgets_lines.bid) AND fromCurrency=budgeting_budgets_lines.originalCurrency AND toCurrency=".intval($configs['toCurrency']).") END)";
        }
        $total = $db->fetch_assoc($db->query('SELECT SUM('.$by.$fxrate_query.') AS total, (SELECT spid FROM budgeting_budgets WHERE budgeting_budgets.bid = '.self::TABLE_NAME.'.bid) AS spid FROM '.self::TABLE_NAME.$dal->construct_whereclause_public($filters, $configs['operators']).' GROUP BY spid HAVING spid='.$supplier->eid));
        return $total['total'];
    }

    public static function get_top($percent, $attr, $filters = '', $configs = array()) {
        global $db;

        $dal = new DataAccessLayer(self::CLASSNAME, self::TABLE_NAME, self::PRIMARY_KEY);

        if(empty($configs['group'])) {
            $configs['group'] = 'cid, altCid';
        }

        $fx_query = '*(CASE WHEN bbl.originalCurrency = 840 THEN 1
            ELSE (SELECT bfr.rate from budgeting_fxrates bfr WHERE bfr.affid = bb.affid AND bfr.year = bb.year AND bfr.fromCurrency = bbl.originalCurrency AND bfr.toCurrency = 840) END)';
        $sql = 'SELECT SUM('.$attr.$fx_query.') AS '.$attr.' FROM '.self::TABLE_NAME.' bbl JOIN budgeting_budgets bb ON (bb.bid = bbl.bid)'.$dal->construct_whereclause_public($filters, $configs['operators']).' GROUP BY '.$configs['group'].' ORDER BY '.$attr.' DESC';
        $data = $db->query($sql);
        $total = $db->fetch_field($db->query('SELECT SUM('.$attr.$fx_query.') AS total FROM '.self::TABLE_NAME.' bbl JOIN budgeting_budgets bb ON (bb.bid = bbl.bid)'.$dal->construct_whereclause_public($filters, $configs['operators'])), 'total');
        while($values = $db->fetch_assoc($data)) {
            $info['count'] += 1;
            $info['contribution'] += $values[$attr];

            if((($info['contribution'] * 100) / $total) >= $percent) {
                break;
            }
        }
        return $info;
    }

    public function __get($name) {
        if(isset($this->budgetline[$name])) {
            return $this->budgetline[$name];
        }
        return false;
    }

    public function __isset($name) {
        return isset($this->budgetline[$name]);
    }

    public function get() {
        return $this->budgetline;
    }

    public function get_convertedamount(Currencies $tocurrency) {
        $budget = $this->get_budget();

        if($this->originalCurrency == $tocurrency->get_id()) {
            return $this->amount;
        }

        $fxrate = BudgetFxRates::get_data(array('fromCurrency' => $this->originalCurrency, 'toCurrency' => $tocurrency->get_id(), 'year' => $budget->year, 'isBudget' => 1, 'affid' => $budget->affid));
        if(is_object($fxrate)) {
            return $this->amount * $fxrate->rate;
        }
        return false;
    }

    public function get_invoicingentity_income($tocurrency, $year, $affid) {
        global $db;
        $fxrate_query = "(CASE WHEN budgeting_budgets_lines.originalCurrency=".intval($tocurrency)." THEN 1 ELSE (SELECT rate FROM budgeting_fxrates WHERE affid=budgeting_budgets_lines.commissionSplitAffid AND year=".intval($year)." AND fromCurrency=budgeting_budgets_lines.originalCurrency AND toCurrency=".intval($tocurrency).") END)";
        $sql = "SELECT saleType, invoice, SUM(amount*{$fxrate_query}) AS amount, SUM(invoicingEntityIncome*{$fxrate_query}) AS invoicingEntityIncome FROM ".Tprefix."budgeting_budgets_lines WHERE commissionSplitAffid= ".intval($affid)." GROUP BY saleType";
        $query = $db->query($sql);
        if($db->num_rows($query) > 0) {
            while($budget = $db->fetch_assoc($query)) {
                $saletype = new SaleTypes($budget['saleType']);
                if(!empty($saletype->invoiceAffStid)) {
                    $data['current'][$saletype->invoiceAffStid]['oldSaleType'] = $budget['saleType'];
                    $budget['saleType'] = $saletype->invoiceAffStid;
                }

                $data['current'][$budget['saleType']]['amount'] = $budget['amount'];
                $data['current'][$budget['saleType']]['invoicingentityincome'] = $budget['invoicingEntityIncome'];
            }
        }
        return $data;
    }

}
/* Budgeting Line Class --END */