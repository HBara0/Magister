<?php
/*
 * Copyright © 2015 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: generatepresentation.php
 * Created:        @hussein.barakat    Jul 7, 2015 | 11:12:30 AM
 * Last Update:    @hussein.barakat    Jul 7, 2015 | 11:12:30 AM
 */

$sheets_order = array(
        'monthlysummary' => 1,
        'cumulativesale' => 2,
        'cumulativeincome' => 3,
        'topsalessuppliers' => 4,
        'topnetsuppliers' => 5,
        'topincomepercsup' => 6,
        'groupsupsales' => 7,
        'groupsupinc' => 8,
        'solvaygroupsale' => 9,
        'solvaygroupinc' => 10,
        'businessmanagersales' => 11,
        'businessmanagerincome' => 12
);
ini_set(max_execution_time, 0);
if(!defined('DIRECT_ACCESS')) {
    die('Direct initialization of this file is not allowed.');
}
if($core->input['export']) {
    $year = date('Y');
    $groupsuppliers = Entities::get_principalsuppliegroups('id');
    if($core->input['affid']) {
        $extra_where = ' AND affid = '.$core->input['affid'];
        $affiliate = new Affiliates(intval($core->input['affid']));
        $country_ids = array();
        $covered_countries = $affiliate->get_coveredcountries();
        if(is_array($covered_countries)) {
            $coveredents = array();
            foreach($covered_countries as $covered_country) {
                $country_ids[] = $covered_country->coid;
            }
            if(is_array($country_ids)) {
                $foreign_entids = IntegrationMediationEntities::get_column('foreignId', array('country' => $country_ids, 'foreignSystem' => '1'), array('returnarray' => true));
                if(is_array($foreign_entids)) {
                    $foreign_salesorders = IntegrationMediationSalesOrders::get_column('foreignId', array('cid' => $foreign_entids), array('returnarray' => true));
                    if(is_array($foreign_salesorders)) {
                        $extra_where_countrysales = ' AND  cid IN (\''.implode('\',\'', $foreign_salesorders).'\')';
                    }
                }
            }
        }
    }
    for($yearlimits = 3; $yearlimits >= 0; $yearlimits--) {
        $cur_year = $year - $yearlimits;
        for($month = 1; $month < 13; $month++) {
            $times[$cur_year][$month]['start'] = strtotime('01-'.sprintf("%02d", $month).'-'.$cur_year.'');
            $times[$cur_year][$month]['end'] = strtotime(date('t', $times[$cur_year][$month]['start']).'-'.sprintf("%02d", $month).'-'.$cur_year.'');
        }
    }

    if(is_array($times)) {
        if(is_array($times)) {
            foreach($times as $year => $month) {
                foreach($month as $montnum => $timestamps) {
                    if(isset($extra_where_countrysales) && !empty($extra_where_countrysales)) {
                        $orders['countrysales'][$year][$montnum] = IntegrationMediationSalesOrders::get_orders('date BETWEEN '.$timestamps['start'].' AND '.$timestamps['end'].$extra_where_countrysales.'AND affid != '.$core->input['affid'], array('returnarray' => true, 'operators' => array('filter' => 'CUSTOMSQLSECURE')));
                    }
                    $orders['affsales'][$year][$montnum] = IntegrationMediationSalesOrders::get_orders('date BETWEEN '.$timestamps['start'].' AND '.$timestamps['end'].$extra_where, array('returnarray' => true));
                    if(is_array($orders)) {
                        foreach($orders as $type => $arrs) {
                            if(is_array($orders[$type][$year][$montnum])) {
                                $lines = array();
                                foreach($orders[$type][$year][$montnum] as $order) {
                                    $order_lines = $order->get_orderlines();
                                    if(is_array($order_lines)) {
                                        foreach($order_lines as $key => $val) {
                                            $lines[$key] = $val;
                                            if(!empty($order->currency)) {
                                                $val->set_ordercur($order->currency);
                                            }
                                            if(!empty($order->salesRepLocalId)) {
                                                $user = new Users($order->salesRepLocalId);
                                                if(is_object($user) && !empty($user->uid)) {
                                                    $val->set_salesrep($user->get_displayname());
                                                }
                                            }
                                            else if(!empty($order->salesRep)) {
                                                $val->set_salesrep($order->salesRep);
                                            }
                                            if(!empty($order->cid)) {
                                                $val->set_customer($order->cid);
                                            }
                                        }
                                    }
                                }
                                $orderlines[$type][$year][$montnum] = $lines;
                            }
                            else {
                                $orderlines[$type][$year][$montnum] = '';
                            }
                        }
                    }
                }
            }
        }
    }
//parse orderlines according to requireements
    if(is_array($orderlines)) {
        $cache = new Cache();
//get full fata for all years-Start
        foreach($orderlines as $type => $actualorderlines) {
            if(is_array($actualorderlines)) {
                foreach($actualorderlines as $year => $months) {
                    if(is_array($months)) {
                        if(!array_filter($months)) {
                            continue;
                        }
                        $groupnames = Entities::get_supgrouparray();
                        if(is_array($groupnames)) {
                            foreach($groupnames as $key => $groupname) {
                                $groupsupplierdsles[$type][$year][$groupname]+=0;
                                $groupsuppliercost[$type][$year][$groupname]+=0;
                                $groupsupplierinc[$type][$year][$groupname]+=0;
                            }
                        }
////////////////////////////////////////////////////////
                        $arr_indices = array('sales', 'income', 'costs');
                        $segmentcategories = SegmentCategories::get_data(array('title is NOT NULL'), array('returnarray' => true, 'simple' => false));
                        if(is_array($segmentcategories)) {
                            foreach($segmentcategories as $segmentcat) {
                                $cache->add('segmentcat', $segmentcat, $segmentcat->scid);
                                foreach($arr_indices as $index) {
                                    $businesssegments[$type][$segmentcat->scid][$year][$index] = 0;
                                }
                            }
                        }
                        ////////////////////////////////////////////////////////
                        foreach($months as $month => $lines) {
                            if(is_array($lines)) {
                                foreach($lines as $line) {
                                    $ordercurrency = $line->get_ordercurr_object();
                                    if(is_object($ordercurrency)) {
                                        $saleexchangerate = $ordercurrency->get_latest_fxrate($ordercurrency->alphaCode, array(), 'USD');
                                    }
                                    else {
                                        continue;
                                    }

                                    if(!isset($line->costCurrency) || empty($line->costCurrency)) {
                                        $costcurrency = new Currencies(840);
                                    }
                                    else {
                                        if($cache->iscached('currency', $line->costCurrency)) {
                                            $costcurrency = $cache->get_cachedval('currency', $line->costCurrency);
                                        }
                                        else {
                                            $costcurrency = Currencies::get_data(array('alphaCode' => $line->costCurrency), array('returnarray' => false));
                                            if(!is_object($costcurrency)) {
                                                continue;
                                            }
                                            $cache->add('currency', $costcurrency, $line->costCurrency);
                                        }
                                    }
                                    $costexchangerate = $costcurrency->get_latest_fxrate($costcurrency->alphaCode, array(), 'USD');
                                    $data[$type][$year][$month]['sales']+=$line->price * $line->quantity / $saleexchangerate / 1000;
                                    $data[$type][$year][$month]['costs']+=$line->cost / $costexchangerate / 1000;
                                    //get supplier id-START
                                    if(isset($line->spid) && !empty($line->spid)) {
                                        $id = $line->spid;
                                        $localsupplier = new Entities($id);
                                    }
                                    else {
                                        if(isset($line->pid) && !empty($line->pid)) {
                                            if($cache->iscached('product', $line->pid)) {
                                                $product = $cache->get_cachedval('product', $line->pid);
                                            }
                                            else {
                                                $product = IntegrationMediationProducts::get_products(array('foreignId' => $line->pid), array('returnarray' => false));
                                            }
                                            if(is_object($product)) {
                                                $cache->add('product', $product, $line->pid);
                                                $localsupplier = $product->get_localsupplier();
                                                if(is_object($localsupplier) && !is_empty($localsupplier->eid)) {
                                                    $id = $localsupplier->eid;
                                                }

                                                $productsegment = $product->get_productsegment();
                                                if(is_object($productsegment)) {
                                                    $productsegmentcat = $productsegment->get_segmentcategory();

                                                    if(is_object($productsegmentcat)) {
                                                        $businesssegments[$type][$productsegmentcat->scid][$year]['sales'] += ($line->price * $line->quantity / $saleexchangerate ) / 1000;
                                                        $businesssegments[$type][$productsegmentcat->scid][$year]['costs'] += ($line->cost / $costexchangerate ) / 1000;
                                                        $businesssegments[$type][$productsegmentcat->scid][$year]['income'] = $suppliers[$type][$id][$year]['sales'] - $suppliers[$type][$id][$year]['costs'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    //get supplier id-END

                                    if(!empty($id)) {
                                        $suppliers[$type][$id][$year]['sales'] += ($line->price * $line->quantity / $saleexchangerate ) / 1000;
                                        $suppliers[$type][$id][$year]['costs'] += ($line->cost / $costexchangerate ) / 1000;
                                        $suppliers[$type][$id][$year]['income'] = $suppliers[$type][$id][$year]['sales'] - $suppliers[$type][$id][$year]['costs'];
                                        if($year == date('Y')) {
                                            $currentyearsups_sales[$type][$id] = $suppliers[$type][$id][$year]['sales'];
                                            $cust = $line->get_customer();
                                            if(!empty($cust)) {
                                                if(is_array($supplier_custids[$type][$id])) {
                                                    if(!in_array($cust, $supplier_custids[$type][$id])) {
                                                        $suppliers_customers[$type][$id] ++;
                                                        $supplier_custids[$type][$id][] = $cust;
                                                    }
                                                }
                                                else {
                                                    $supplier_custids[$type][$id][] = $cust;
                                                }
                                            }
                                            $currentyearsups_costs[$type][$id] = $suppliers[$type][$id][$year]['costs'];
                                            $currentyearsups_income[$type][$id] = $currentyearsups_sales[$type][$id] - $currentyearsups_costs[$id];
                                        }
                                        if(is_array($groupsuppliers[$type])) {
                                            if(isset($groupsuppliers[$type][$id]) && !empty($groupsuppliers[$type][$id])) {
                                                if($groupsuppliers[$type][$id] == 1) {
                                                    if(is_object($localsupplier)) {
                                                        $solvaygroupsale[$type][$year][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $localsupplier->get_displayname())]+=($line->price * $line->quantity / $saleexchangerate ) / 1000;
                                                        $solvaygroupcost[$type][$year][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $localsupplier->get_displayname())]+=($line->cost / $costexchangerate ) / 1000;
                                                        $solvaygroupinc[$type][$year][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $localsupplier->get_displayname())] = $solvaygroupsale[$type][$year][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $localsupplier->get_displayname())] - $solvaygroupcost[$type][$year][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $localsupplier->get_displayname())];
                                                    }
                                                }
                                                $groupname = Entities::get_suppliergroupname($groupsuppliers[$id]);
                                                if($groupname != false) {
                                                    $groupsupplierdsles[$type][$year][$groupname]+=($line->price * $line->quantity / $saleexchangerate ) / 1000;
                                                    $groupsuppliercost[$type][$year][$groupname]+=($line->cost / $costexchangerate ) / 1000;
                                                    $groupsupplierinc[$type][$year][$groupname] = $groupsupplierdsles[$type][$year][$groupname] - $groupsuppliercost[$type][$year][$groupname];
                                                }
                                            }
                                        }
                                    }
                                    //get sales rep -Start
                                    $bm = $line->get_salesrep();
                                    //get sales rep -END
                                    //seperation data related to bms-START
                                    if(isset($bm) && !empty($bm)) {
                                        $businessmansales[$type][$year][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $bm)]+=($line->price * $line->quantity / $saleexchangerate ) / 1000;
                                        $businessmancosts[$type][$year][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $bm)]+=($line->cost / $costexchangerate ) / 1000;
                                        $businessmanincome[$type][$year][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $bm)] = $businessmansales[$type][$year][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $bm)] - $businessmancosts[$type][$year][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $bm)];
                                    }
                                    //seperation data related to bms-End
                                    $id = $bm = '';
                                }
                                $data[$type][$year][$month]['income'] = $data[$type][$year][$month]['sales'] - $data[$type][$year][$month]['costs'];
                                $totalyear[$type][$year]['income']+=$data[$type][$year][$month]['income'];
                                $totalyear[$type][$year]['sales']+=$data[$type][$year][$month]['sales'];
                            }
                            else {
                                $data[$type][$year][$month]['income'] = $data[$type][$year][$month]['sales'] = $data[$type][$year][$month]['costs'] = '';
                            }
                        }
                    }
                }
            }
        }
//get next year data from budget--START
        if($core->input['affid']) {
            $budgets = Budgets::get_data(array('affid' => $core->input['affid'], 'year' => (date('Y') + 1)), array('returnarray' => true, 'simple' => false));
        }
        else {
            $budgets = Budgets::get_data(array('year' => (date('Y') + 1)), array('returnarray' => true, 'simple' => false));
        }
        if(is_array($budgets)) {
            if(is_array($country_ids)) {
                $country_ents = Entities::get_column('eid', array('country' => $country_ids), array('returnarray' => true));
                if(is_array($country_ents)) {
                    $extra_budlines = BudgetLines::get_data(array('cid' => $country_ents), array('returnarray' => true));
                    if(is_array($extra_budlines)) {
                        foreach($extra_budlines as $extra_budline) {
                            $extra_budget = Budgets::get_data(array('bid' => $extra_budline->bid, 'year' => (date('Y') + 1)), array('returnarray' => false, 'simple' => false));
                            if(is_object($extra_budget)) {
                                $currency = $extra_budline->get_currency();
                                if(is_object($currency)) {
                                    if($cache->iscached('exchange', $currency->alphaCode)) {
                                        $product = $cache->get_cachedval('exchange', $currency->alphaCode);
                                    }
                                    else {
                                        $exchangerate = $currency->get_latest_fxrate($currency->alphaCode, array(), 'USD');
                                        if(!empty($exchangerate)) {
                                            $cache->add('exchange', $exchangerate, $currency->alphaCode);
                                        }
                                    }
                                }
                                for($i = 1; $i < 7; $i ++) {
                                    $data['countrysales'][(date('Y') + 1)][$i]['sales'] += (($extra_budline->amount * $extra_budline->s1Perc / 100 ) / 6 ) * $exchangerate;
                                    $data['countrysales'][(date('Y') + 1)][$i]['income'] += (($extra_budline->income * $extra_budline->s1Perc / 100 ) / 6 ) * $exchangerate;
                                    $data['countrysales'][(date('Y') + 1)][$i]['costs'] = $data['countrysales'][(date('Y') + 1)][$i]['sales'] - $data['countrysales'][(date('Y') + 1)][$i]['income'];
                                }

                                for($i = 7; $i < 13; $i++) {
                                    $data['countrysales'][(date('Y') + 1)][$i]['sales'] += (($extra_budline->amount * $extra_budline->s2Perc / 100 ) / 6 ) * $exchangerate;
                                    $data['countrysales'][(date('Y') + 1)][$i]['income'] += (($extra_budline->income * $extra_budline->s2Perc / 100 ) / 6 ) * $exchangerate;
                                    $data['countrysales'][(date('Y') + 1)][$i]['costs'] = $data['countrysales'][(date('Y') + 1)][$i]['sales'] - $data['countrysales'][(date('Y') + 1)][$i]['income'];
                                }
                                // Sales / Income data per Business segment - Start
                                if(!empty($line->psid)) {
                                    $psid = $line->psid;
                                }
                                else {
                                    if($cache->iscached('product', $line->pid)) {
                                        $product = $cache->get_cachedval('product', $line->pid);
                                    }
                                    else {
                                        $product = new Products($line->pid, false);
                                    }
                                    if(is_object($product)) {
                                        $cache->add('product', $product, $line->pid);
                                        $psid = $product->get_segment()['psid'];
                                    }
                                }
                                if($cache->iscached('segment', $psid)) {
                                    $productsegment = $cache->get_cachedval('segment', $psid);
                                }
                                else {
                                    $productsegment = new ProductsSegments($psid, false);
                                }
                                if(is_object($productsegment)) {
                                    $productsegcat = $productsegment->get_segmentcategory()->scid;
                                }

                                $businesssegments['countrysales'][$productsegcat][(date('Y') + 1)]['sales'] += $line->amount * $exchangerate;
                                $businesssegments['countrysales'][$productsegcat][(date('Y') + 1)]['income'] += $line->income * $exchangerate;
                                // Sales / Income data per Business segment - END
                                //supplier part-START
                                if(isset($line->spid) && !empty($budget->spid)) {
                                    $suppliers['countrysales'][$budget->spid][date('Y')]['sales']+=$line->amount * $exchangerate;
                                    $suppliers['countrysales'][$budget->spid][date('Y')]['income']+=$line->income * $exchangerate;
                                    if(is_array($groupsuppliers)) {
                                        if(isset($groupsuppliers[$budget->spid]) && !empty($groupsuppliers[$budget->spid])) {
                                            if($groupsuppliers[$budget->spid] == 1) {
                                                $localsupplier = new Entities($budget->spid);
                                                if(is_object($localsupplier)) {
                                                    $solvaygroupsale['countrysales'][(date('Y') + 1)][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $localsupplier->get_displayname())]+=$line->amount * $exchangerate;
                                                    $solvaygroupinc['countrysales'][(date('Y') + 1)][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $localsupplier->get_displayname())] +=$line->income * $exchangerate;
                                                }
                                            }
                                            $groupname = Entities::get_suppliergroupname($groupsuppliers[$budget->spid]);
                                            if($groupname != false) {
                                                $groupsupplierdsles['countrysales'][(date('Y') + 1)][$groupname]+=$line->amount * $exchangerate;
                                                $groupsupplierinc['countrysales'][(date('Y') + 1)][$groupname] += $line->income * $exchangerate;
                                            }
                                        }
                                    }
                                }
                                //supplier part-END
                                //Business manager part-Start
                                if(isset($line->businessMgr) && !empty($line->businessMgr)) {
                                    $user = new Users($line->businessMgr);
                                    if(is_object($user) && !empty($user->uid)) {
                                        $businessmansales['countrysales'][(date('Y') + 1)][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $user->get_displayname())]+=$line->amount * $exchangerate;
                                        $businessmanincome['countrysales'][(date('Y') + 1)][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $user->get_displayname())] = $line->income * $exchangerate;
                                    }
                                }
                                //Business manager part-End
                            }
                        }
                    }
                }
            }
            foreach($budgets as $budget) {
                $lines = $budget->get_budgetlines_objs();
                if(is_array($lines)) {
                    foreach($lines as $line) {
                        $currency = $line->get_currency();
                        if(is_object($currency)) {
                            if($cache->iscached('exchange', $currency->alphaCode)) {
                                $product = $cache->get_cachedval('exchange', $currency->alphaCode);
                            }
                            else {
                                $exchangerate = $currency->get_latest_fxrate($currency->alphaCode, array(), 'USD');
                                if(!empty($exchangerate)) {
                                    $cache->add('exchange', $exchangerate, $currency->alphaCode);
                                }
                            }
                        }
                        $totalbudg_sales+=$line->amount * $exchangerate;
                        for($i = 1; $i < 7; $i ++) {
                            $data['affsales'][(date('Y') + 1)][$i]['sales'] += (($line->amount * $line->s1Perc / 100 ) / 6 ) * $exchangerate;
                            $data['affsales'][(date('Y') + 1)][$i]['income'] += (($line->income * $line->s1Perc / 100 ) / 6 ) * $exchangerate;
                            $data['affsales'][(date('Y') + 1)][$i]['costs'] = $data['affsales'][(date('Y') + 1)][$i]['sales'] - $data['affsales'][(date('Y') + 1)][$i]['income'];
                        }

                        for($i = 7; $i < 13; $i++) {
                            $data['affsales'][(date('Y') + 1)][$i]['sales'] += (($line->amount * $line->s2Perc / 100 ) / 6 ) * $exchangerate;
                            $data['affsales'][(date('Y') + 1)][$i]['income'] += (($line->income * $line->s2Perc / 100 ) / 6 ) * $exchangerate;
                            $data['affsales'][(date('Y') + 1)][$i]['costs'] = $data['affsales'][(date('Y') + 1)][$i]['sales'] - $data['affsales'][(date('Y') + 1)][$i]['income'];
                        }

                        //get sales by type-START
                        $type = new SaleTypes($line->get_saletype());
                        if(is_object($type)) {
                            $final['affsales']['salespertype'][$type->get_displayname()]['Amount'] += $line->amount * $exchangerate;
                            $salestypes [] = $type->get_displayname();
                        }
                        //get sales by Type-END
                        // Sales / Income data per Business segment - Start
                        if(!empty($line->psid)) {
                            $psid = $line->psid;
                        }
                        else {
                            if($cache->iscached('product', $line->pid)) {
                                $product = $cache->get_cachedval('product', $line->pid);
                            }
                            else {
                                $product = new Products($line->pid, false);
                            }
                            if(is_object($product)) {
                                $cache->add('product', $product, $line->pid);
                                $psid = $product->get_segment()['psid'];
                            }
                        }
                        if($cache->iscached('segment', $psid)) {
                            $productsegment = $cache->get_cachedval('segment', $psid);
                        }
                        else {
                            $productsegment = new ProductsSegments($psid, false);
                        }
                        if(is_object($productsegment)) {
                            $productsegcat = $productsegment->get_segmentcategory()->scid;
                        }

                        $businesssegments['affsales'][$productsegcat][(date('Y') + 1)]['sales'] += $line->amount * $exchangerate;
                        $businesssegments['affsales'][$productsegcat][(date('Y') + 1)]['income'] += $line->income * $exchangerate;
                        // Sales / Income data per Business segment - END
                        //supplier part-START
                        if(isset($line->spid) && !empty($budget->spid)) {
                            $suppliers['affsales'][$budget->spid][date('Y')]['sales']+=$line->amount * $exchangerate;
                            $suppliers['affsales'][$budget->spid][date('Y')]['income']+=$line->income * $exchangerate;
                            if(is_array($groupsuppliers)) {
                                if(isset($groupsuppliers[$budget->spid]) && !empty($groupsuppliers[$budget->spid])) {
                                    if($groupsuppliers[$budget->spid] == 1) {
                                        $localsupplier = new Entities($budget->spid);
                                        if(is_object($localsupplier)) {
                                            $solvaygroupsale['affsales'][(date('Y') + 1)][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $localsupplier->get_displayname())]+=$line->amount * $exchangerate;
                                            $solvaygroupinc['affsales'][(date('Y') + 1)][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $localsupplier->get_displayname())] +=$line->income * $exchangerate;
                                        }
                                    }
                                    $groupname = Entities::get_suppliergroupname($groupsuppliers[$budget->spid]);
                                    if($groupname != false) {
                                        $groupsupplierdsles['affsales'][(date('Y') + 1)][$groupname]+=$line->amount * $exchangerate;
                                        $groupsupplierinc['affsales'][(date('Y') + 1)][$groupname] += $line->income * $exchangerate;
                                    }
                                }
                            }
                        }
                        //supplier part-END
                        //Business manager part-Start
                        if(isset($line->businessMgr) && !empty($line->businessMgr)) {
                            $user = new Users($line->businessMgr);
                            if(is_object($user) && !empty($user->uid)) {
                                $businessmansales['affsales'][(date('Y') + 1)][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $user->get_displayname())]+=$line->amount * $exchangerate;
                                $businessmanincome['affsales'][(date('Y') + 1)][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $user->get_displayname())] = $line->income * $exchangerate;
                            }
                        }
                        //Business manager part-End
                    }
                }
            }
        }
//get next year data from budget--END
//get full fata for all years-End
        if(is_array($data)) {
            foreach($data as $sheettype => $actualdata) {
                if(is_array($actualdata)) {
                    foreach($actualdata as $year => $months) {
                        $curentsales_total = $currentincome_total = 0;
                        if(is_array($months)) {
                            foreach($months as $month => $types) {
                                foreach($types as $type => $number) {
//get cumulative income-START
                                    if($type == 'income') {
                                        $currentincome_total+=intval($number);
                                        $final[$sheettype]['cumulativeincome'][$year][date("F", mktime(0, 0, 0, $month, 10))] = $currentincome_total;
                                        if($year == date('Y')) {
                                            $final[$sheettype]['monthlysummary'][$type][date("F", mktime(0, 0, 0, $month, 10))] = intval($number);
                                        }
                                        continue;
                                    }
//get cumulative income-END
//
//get monthly summary of current year-START
                                    if($year == date('Y')) {
                                        $final[$sheettype]['monthlysummary'][$type][date("F", mktime(0, 0, 0, $month, 10))] = intval($number);
                                    }
//get monthly summary of current year-END
//get cumulative sales-START
                                    if($type == 'sales') {
                                        $curentsales_total+= intval($number);
                                        $final[$sheettype]['cumulativesale'][$year] [date("F", mktime(0, 0, 0, $month, 10))] = $curentsales_total;
                                    }
//get cumulative sales-END
                                }
                            }
                        }
                        else {
                            $final[$sheettype]['cumulativesale'][$year] [date("F", mktime(0, 0, 0, $month, 10))] = $final['monthlysummary'][$type][date("F", mktime(0, 0, 0, $month, 10))] = $final['cumulativeincome'][$year][date("F", mktime(0, 0, 0, $month, 10))] = '';
                        }
                    }
                }
            }
        }
        if(is_array($salestypes) && $totalbudg_sales > 0) {
            foreach($salestypes as $salestype) {
                $final['affsales']['salespertype']['Total']['Amount'] += $final['affsales']['salespertype'][$salestype]['Amount'];
                $final['affsales']['salespertype']['Total']['Percentage'] += number_format(( $final['affsales']['salespertype'][$salestype]['Amount'] / $totalbudg_sales) * 100, 2, '.', ',');
                $final['affsales']['salespertype'][$salestype]['Percentage'] = number_format(( $final['affsales']['salespertype'][$salestype]['Amount'] / $totalbudg_sales) * 100, 2, '.', ',').'%';
            }
            $final['affsales']['salespertype']['Total']['Percentage'] = $final['affsales']['salespertype']['Total']['Percentage'].'%';
        }
//get groupsuppliers sales and net-START
        if(is_array($groupsupplierdsles)) {
            foreach($groupsupplierdsles as $type => $actualdata) {
                if(is_array($actualdata)) {
                    $final[$type]['groupsupsales'] = $actualdata;
                }
            }
        }
        if(is_array($groupsupplierinc)) {
            foreach($groupsupplierinc as $type => $actualdata) {
                if(is_array($actualdata)) {
                    $final[$type]['groupsupinc'] = $actualdata;
                }
            }
        }
//get groupsuppliers sales and net-END
//get solvaygroup sales and net-START
        if(is_array($solvaygroupsale)) {
            foreach($solvaygroupsale as $type => $actualdata) {
                if(is_array($actualdata)) {
                    $final[$type]['solvaygroupsale'] = $actualdata;
                }
            }
        }
        if(is_array($solvaygroupinc)) {
            foreach($solvaygroupinc as $type => $actualdata) {
                if(is_array($actualdata)) {
                    $final[$type]['solvaygroupinc'] = $actualdata;
                }
            }
        }
//get solvaygroup sales and net-END
//get bm sales and costs-START
        if(is_array($businessmansales)) {
            foreach($businessmansales as $type => $actualdata) {
                if(is_array($actualdata)) {
                    $final[$type]['businessmanagersales'] = $actualdata;
                }
            }
        }
        if(is_array($businessmanincome)) {
            foreach($businessmanincome as $type => $actualdata) {
                if(is_array($actualdata)) {
                    $final[$type]['businessmanagerincome'] = $actualdata;
                }
            }
        }
////get bm sales and costs-END
//get top 10 suppliers sales and net=START
        if(is_array($currentyearsups_sales)) {
            foreach($currentyearsups_sales as $type => $actualdata) {
                if(is_array($actualdata)) {
                    asort($actualdata);
                    $top_salessups[$type] = array_slice(array_reverse($actualdata, true), 0, 10, true);
                }
            }
        }
        if(is_array($currentyearsups_income)) {
            foreach($currentyearsups_income as $type => $actualdata) {
                if(is_array($actualdata)) {
                    asort($actualdata);
                    $top_netsups[$type] = array_slice(array_reverse($actualdata, true), 0, 10, true);
                    foreach($actualdata as $eid => $number) {
                        $supplierspec_income[$type][$eid] = $number * 100 / $totalyear [$type][date('Y')]['income'];
                    }
                    if(is_array($supplierspec_income[$type])) {
                        asort($supplierspec_income[$type]);
                        $top_incomeperc[$type] = array_slice(array_reverse($supplierspec_income[$type], true), 0, 10, true);
                    }
                }
            }
        }
        if(is_array($top_incomeperc)) {
            foreach($top_incomeperc as $type => $actualdata) {
                if(is_array($actualdata)) {
                    foreach($actualdata as $supid => $incomeperc) {
                        $customernum = '';
                        if(is_array($suppliers_customers[$type])) {
                            $customernum[$type] = $suppliers_customers[$type][$supid];
                        }
                        if(is_array($currentyearsups_sales[$type])) {
                            $salesperc[$type] = $currentyearsups_sales[$type][$supid] * 100 / $totalyear[$type][date('Y')]['sales'];
                        }
                        $supplier = new Entities($supid);
                        if(is_object($supplier)) {

                            $supname = str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $supplier->get_displayname());
                            $final[$type]['topincomepercsup']['%income'][$supname] = number_format($incomeperc, 2, '.', ',').'%';
                            $final[$type]['topincomepercsup']['%sales'][$supname] = number_format($salesperc[$type], 2, '.', ',').'%';
                            $final[$type]['topincomepercsup']['#customers'][$supname] = $customernum[$type];
                            $supname = '';
                        }
                    }
                }
            }
        }
        if(is_array($top_salessups)) {
            foreach($top_salessups as $saletype => $actualdata) {
                if(is_array($actualdata)) {
                    foreach($actualdata as $supid => $currentsales) {
                        if(is_array($suppliers[$saletype][$supid])) {
                            foreach($suppliers[$saletype][$supid] as $year => $type) {
                                if(is_array($type) && isset($type['sales']) && !empty($type['sales'])) {
                                    $supplier = new Entities($supid);
                                    if(is_object($supplier)) {
                                        $final[$saletype]['topsalessuppliers'][$year][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $supplier->get_displayname())] = $type['sales'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if(is_array($top_netsups)) {
            foreach($top_netsups as $saletype => $actualdata) {
                if(is_array($actualdata)) {
                    foreach($actualdata as $supid => $currentnet) {
                        if(is_array($suppliers[$saletype][$supid])) {
                            foreach($suppliers[$saletype][$supid] as $year => $type) {
                                if(is_array($type) && isset($type['income']) && !empty($type['income'])) {
                                    $supplier = new Entities($supid);
                                    if(is_object($supplier)) {
                                        $final[$saletype]['topnetsuppliers'][$year][str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $supplier->get_displayname())] = $type['income'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
//get top 10 suppliers sales and net=END
        //get Sales/Income per business segment - START
        if(is_array($businesssegments['affsales'])) {
            $segmentcategories = SegmentCategories::get_data(array('name is NOT NULL'), array('returnarray' => true, 'simple' => false));
            $curryear = date('Y');
            $tables = array('businesssegmentsales', 'businesssegmentincome');
            $header_fields = array(($curryear - 3), ($curryear - 2), ($curryear - 1), $curryear, $curryear.' YEF vs '.($curryear - 3).'A',
                    $curryear.' YEF vs '.($curryear - 2).'A', $curryear.' YEF vs '.($curryear - 1).'A', $curryear + 1, $curryear.'YEF vs'.($curryear + 1).'B', '%Weight'.($curryear - 1).'A', '%Weight'.($curryear).'YEF', '%Weight'.($curryear + 1).'B');
            //Intialize Table -Start//
            if(is_array($segmentcategories)) {
                foreach($tables as $tableindex) {
                    foreach($segmentcategories as $segmentcategory) {
                        $category_name = $segmentcategory->title = str_replace(array(' ', '<', '>', '&', '{', '}', '*'), array('-'), $segmentcategory->get_displayname());
                        foreach($header_fields as $field) {
                            $final['affsales'][$tableindex][$category_name][$field] = 0;
                        }
                    }
                    foreach($header_fields as $field) {
                        $final['affsales'][$tableindex]['total'][$field] = ' ';
                    }
                    $categorygroups = array('Segments Groups', 'Life Science', 'Industrials');
                    foreach($categorygroups as $categorygroup) {
                        foreach($header_fields as $field) {
                            if($categorygroup == 'Segments Groups') {
                                $final['affsales'][$tableindex][$categorygroup][$field] = $field;
                            }
                            else {
                                $final['affsales'][$tableindex][$categorygroup][$field] = ' ';
                            }
                        }
                    }
                    foreach($header_fields as $field) {
                        $final['affsales'][$tableindex]['Total'][$field] = ' ';
                    }
                }
            }
            //Intialize Table - END//

            if(is_array($segmentcategories)) {
                foreach($segmentcategories as $segmentcategory) {
                    $category_name = $segmentcategory->title;
                    switch($segmentcategory->catGroup) {
                        case 'LS':
                            $categorygroup = 'Life Science';
                            break;
                        case 'IND':
                            $categorygroup = 'Industrials';
                            break;
                        default:
                            break;
                    }
                    foreach($businesssegments['affsales'] as $scid => $businesssegment_data) {
                        if($scid == $segmentcategory->scid) {
                            foreach($businesssegment_data as $year => $data) {
                                $final['affsales']['businesssegmentsales'][$category_name][$year] = $data['sales'];
                                $final['affsales']['businesssegmentincome'][$category_name][$year] = $data['income'];

                                $final['affsales']['businesssegmentsales']['total'][$year] += $data['sales'];
                                $final['affsales']['businesssegmentincome']['total'][$year] += $data['income'];

                                $final['affsales']['businesssegmentsales'][$categorygroup][$year] +=$data['sales'];
                                $final['affsales']['businesssegmentincome'][$categorygroup][$year] +=$data['income'];
                                $years[$year] = $year;
                            }
                            foreach($tables as $tableindex) {
                                $currentyear_yef[$tableindex] = $final['affsales'][$tableindex][$category_name][date('Y')];
                                foreach($years as $year) {
                                    $value = $final[$tableindex][$category_name][$year];
                                    if($year < date('Y')) {
                                        if($value != 0) {
                                            $final['affsales'][$tableindex][$category_name][date('Y').' YEF vs '.$year.'A'] = (($currentyear_yef[$tableindex] - $value) / $value) * 100;
                                        }
                                    }
                                    if($year == (date('Y') + 1)) {
                                        if($currentyear_yef[$tableindex] != 0) {
                                            $final['affsales'][$tableindex][$category_name][date('Y').'YEF vs'.$year.'B'] = round(($value - $currentyear_yef[$tableindex]) / $currentyear_yef[$tableindex]) * 100;
                                        }
                                    }
                                }
                            }
                        }
                    }
                };
                foreach($tables as $tableindex) {
                    //Sum total of segment categories groups//
                    $totalfields = $header_fields = array($curryear - 3, $curryear - 2, $curryear - 1, $curryear, $curryear + 1);
                    foreach($totalfields as $field) {
                        $final['affsales'][$tableindex]['Total'][$field] = $final['affsales'][$tableindex]['Life Science'][$field] + $final['affsales'][$tableindex]['Industrials'][$field];
                    }

                    //Calculate Weights - START//
                    $categorygroups = array('Life Science', 'Industrials');
                    foreach($years as $year) {
                        switch($year) {
                            case (date('Y') - 1):
                                $weightindex = '%Weight'.$year.'A';
                                break;
                            case (date('Y') + 1):
                                $weightindex = '%Weight'.$year.'B';
                                break;
                            case date('Y'):
                                $weightindex = '%Weight'.$year.'YEF';
                                break;
                            default:
                                break;
                        }
                        foreach($segmentcategories as $segmentcategory) {
                            $category_name = $segmentcategory->title;
                            $value = $final['affsales'][$tableindex][$category_name][$year];
                            if($final['affsales'][$tableindex]['total'][$year] != 0) {
                                $final['affsales'][$tableindex][$category_name][$weightindex] = $value / $final['affsales'][$tableindex]['total'][$year] * 100;
                            }
                        }
                        //category groups percentages
                        foreach($categorygroups as $categorygroup) {
                            $value = $final['affsales'][$tableindex][$categorygroup][$year];
                            if($final['affsales'][$tableindex]['Total'][$year] != 0) {
                                $final['affsales'][$tableindex][$categorygroup][$weightindex] = $value / $final['affsales'][$tableindex]['Total'][$year] * 100;
                            }
                            if($year < date('Y')) {
                                if($value != 0) {
                                    $y = $final['affsales'][$tableindex][$categorygroup][date('Y').' YEF vs '.$year.'A'] = (($final['affsales'][$tableindex][$categorygroup][date('Y')] - $value) / $value) * 100;
                                }
                            }
                            if($year == (date('Y') + 1)) {
                                if($final['affsales'][$tableindex][$categorygroup][date('Y')] != 0) {
                                    $final['affsales'][$tableindex][$categorygroup][date('Y').'YEF vs'.$year.'B'] = round(($value - $final['affsales'][$tableindex][$categorygroup][date('Y')]) / $final['affsales'][$tableindex][$categorygroup][date('Y')]) * 100;
                                }
                            }
                        }
                    }
                    //Calculate Weights - END//
                }
            }
        }

        //get Sales/Income per business segment - END
    }
    $aff = 'All';
    if($core->input['affid']) {
        $affiliate = new Affiliates($core->input['affid']);
        if(is_object($affiliate)) {
            $aff = $affiliate->alias;
        }
    }
//parse contents-START
    if(is_array($final)) {
        $langvariable = $pagedesc = '';
        foreach($final as $saletype => $actualdata) {
            if(is_array($actualdata)) {
                foreach($actualdata as $langvar => $rowhead) {
                    $page = $rows = $tablehead = '';
                    $langvariable = $langvar;
                    $pagedesc = $langvariable.'desc';
                    $colheader = 0;
                    if(is_array($rowhead)) {
                        foreach($rowhead as $row => $theads) {
                            $rows .= '<tr><td>'.$row.'</td>';
                            if(is_array($theads)) {
                                foreach($theads as $thead => $data) {

                                    if(!is_string($data)) {
                                        if($row == 'Segments Groups') {
                                            $rows .= '<td>'.$data.'</td>';
                                        }
                                        else {
                                            $rows.= '<td>'.number_format($data, 2, '.', ',').'</td>';
                                        }
                                    }
                                    else {
                                        $rows.= '<td>'.$data.'</td>';
                                    }
                                    if($colheader == 0) {
                                        $existing_theads[] = $thead;
                                        $tablehead.='<th>'.$thead.'</th>';
                                    }
                                    elseif(!in_array($thead, $existing_theads)) {
                                        $tablehead.='<th>'.$thead.'</th>';
                                    }
                                }
                            }
                            else {
                                $tablehead.='<th>'.$thead.'</th>';
                            }
                            $colheader = 1;
                            $rows.='</tr>';
                        }
                    }
                    $page = '<html xmlns:v = "urn:schemas-microsoft-com:vml" xmlns:o = "urn:schemas-microsoft-com:office:office" xmlns:x = "urn:schemas-microsoft-com:office:excel"
xmlns = "http://www.w3.org/TR/REC-html40">
<head>
   <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
          <meta name=ProgId content=Excel.Sheet>
          <meta name=Generator content="Microsoft Excel 11">
   <!--[if gte mso 9]><xml>
           <x:ExcelWorkbook>
          <x:ExcelWorksheets>
           <x:ExcelWorksheet>
            <x:Name>none</x:Name>
           <x:WorksheetOptions>
     <x:ProtectContents>False</x:ProtectContents>
     <x:ProtectObjects>False</x:ProtectObjects>
     <x:ProtectScenarios>False</x:ProtectScenarios>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
  <x:WindowHeight>9210</x:WindowHeight>
  <x:WindowWidth>19035</x:WindowWidth>
  <x:WindowTopX>0</x:WindowTopX>
  <x:WindowTopY>75</x:WindowTopY>
  <x:ProtectStructure>False</x:ProtectStructure>
  <x:ProtectWindows>False</x:ProtectWindows>
 </x:ExcelWorkbook>
</xml><![endif]-->
</head>
<body><table>
<thead><tr><th>'.$lang->$saletype.' '.$lang->$pagedesc.'</th></tr><tr><th></th>'.$tablehead.'</tr></thead>';
                    $page.='<tbody>'.$rows.'</tbody>';
                    $page.='</table></body></html>';
                    $path = dirname(__FILE__).'\..\..\tmp\\bugetingexport\\'.uniqid($aff.$saletype.$langvariable).'.html';
                    if($sheets_order[$langvariable] > 0) {
                        $allpaths[$sheets_order[$langvariable]][$lang->$saletype.$lang->$langvariable] = $path;
                    }
                    else {
                        $allpaths[100][$lang->$saletype.$lang->$langvariable] = $path;
                    }
                    $handle = fopen($path, 'w') or die('Cannot open file: '.$allpaths);
                    $writefile = file_put_contents($path, $page);
                    continue;
                }
            }
        }
    }
    require dirname(__FILE__).'/../../PHPExcel/Classes/PHPExcel/IOFactory.php';

//Start Excel Convertion
//set excel styles- START
    $style['header'] = array(
            'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => '3D9140')
            ),
            'font' => array(
                    'bold' => true,
                    'color' => array('rgb' => 'FFFFFF'),
                    'size' => 12,
                    'name' => 'Calibri'
            ),
            'alignment' => array(
                    'wrap' => true,
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
            'borders' => array(
                    'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THICK,
                            'color' => array('rgb' => '000000')
                    )
            )
    );
    $style['altrows'] = array(
            'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => 'C3F5F2')
            ),
    );
//set excel styles-END
    $outputFile = $aff."-report.xls";

//es are loaded to PHPExcel using the IOFactory load() method
    if(is_array($allpaths)) {
        $count = 0;
        ksort($allpaths);
        foreach($allpaths as $order => $allpages) {
            foreach($allpages as $title => $path) {
                if($count == 0) {
                    $main_excel = PHPExcel_IOFactory::load($path);
                    $main_excel->getSheet(0)->setTitle($title);

                    $main_excel->getSheet(0)->getStyle('B2:Z2')->applyFromArray($style['header']);
                    for($i = 3; $i <= 8; $i = $i + 2) {
                        $main_excel->getSheet(0)->getStyle('A'.$i.':Z'.$i)->applyFromArray($style['altrows']);
                    }
                    if(($title == $lang->businesssegmentsales) || ($title == $lang->businesssegmentincome)) {
                        $main_excel->getSheet(0)->getStyle('A11:M11')->applyFromArray($style['header']);
                        $main_excel->getSheet(0)->getStyle('A12:M12')->applyFromArray($style['header']);
                        $main_excel->getSheet(0)->getStyle('A15:M15')->applyFromArray($style['header']);
                    }
                    foreach(range('A', 'O') as $col) {
                        $main_excel->getSheet(0)
                                ->getColumnDimension($col)
                                ->setWidth('15');
                    }
                    $count = 1;
                    continue;
                }
                $tempexcel = PHPExcel_IOFactory::load($path);
                $excels[$title] = $tempexcel->getSheet(0);
                $tempexcel = '';
            }
        }
        if(is_array($excels)) {
            foreach($excels as $title => $sheet) {
                $sheet->setTitle($title);
                $sheet->getDefaultStyle()
                        ->getAlignment()
                        ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B2:Z2')->applyFromArray($style['header']);

                for($i = 3; $i <= 9; $i = $i + 2) {
                    $sheet->getStyle('A'.$i.':Z'.$i)->applyFromArray($style['altrows']);
                }
                if(($title == $lang->businesssegmentsales) || ($title == $lang->businesssegmentincome)) {
                    $sheet->getStyle('A11:M11')->applyFromArray($style['header']);
                    $sheet->getStyle('A12:M12')->applyFromArray($style['header']);
                    $sheet->getStyle('A15:M15')->applyFromArray($style['header']);
                }
                foreach(range('A', 'O') as $col) {
                    $sheet->getColumnDimension($col)
                            ->setWidth('15');
                }
                $main_excel->addSheet($sheet);
            }
            ob_clean();
            $objWriter = PHPExcel_IOFactory::createWriter($main_excel, "Excel5");
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=$outputFile");
            $objWriter->save('php://output');
        }
    }
}
if(!$core->input['action']) {
    $affiliates = Affiliates::get_affiliates('affid IN ('.implode(',', $core->user['affiliates']).')', array('returnarray' => true));
    $affiliates_list = parse_selectlist('affid', 1, $affiliates, '', '', '', array('blankstart' => true));
    eval("\$generatepres = \"".$template->get('budgeting_generatepresentation')."\";");
    output_page($generatepres);
}