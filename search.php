<?php
/*
 * Orkila Central Online System (OCOS)
 * Copyright © 2009 Orkila International Offshore, All Rights Reserved
 *
 * Search tools
 * $id: search.php
 * Created: 		@zaher.reda			Mar 21, 2009 | 11:40 AM
 * Last Update: 	@zaher.reda			February 28, 2013 | 08:56 AM
 */

require_once 'global.php';

if($core->input['type'] == 'quick') {
    $dofilter = false;
    if(isset($core->input['filter'])) {
        $dofilter = true;
        if(isset($core->input['rid']) && !empty($core->input['rid'])) {
            $report_data = $db->fetch_array($db->query('SELECT affid, spid FROM '.Tprefix.'reports WHERE rid='.intval($core->input['rid'])));
        }
        else {
            if(isset($core->input['cid']) && !empty($core->input['cid'])) {
                $customer_filter = 'er.eid='.intval($core->input['cid']);
            }

            if(isset($core->input['spid']) && !empty($core->input['spid'])) {
                if(is_array($core->input['spid'])) {
                    $core->input['spid'] = array_map(intval, $core->input['spid']);
                    if($core->input['for'] == 'product') {
                        $supplier_filter = 'spid IN ('.implode(',', $core->input['spid']).')';
                    }
                    else {
                        $supplier_filter = 'er.eid IN ('.implode(',', $core->input['spid']).')';
                    }
                }
                else {
                    if($core->input['for'] == 'product') {
                        $supplier_filter = 'spid='.intval($core->input['spid']);
                    }
                    else {
                        $supplier_filter = 'er.eid='.intval($core->input['spid']);
                    }
                }
            }
        }
    }

    if(isset($core->input['for'])) {

        if($core->input['for'] == 'potentialcustomer') {
            $table = 'entities';
            $attributes = array('companyName', 'companyNameAbbr');
            $key_attribute = 'eid';
            $select_attributes = array('companyName');
            $order = array('by' => 'companyName', 'sort' => 'ASC');
            $extra_where .= ' type="pc"';
            $descinfo = 'country';
        }
        if($core->input['for'] == 'allentities') {
            $table = 'entities';
            $attributes = array('companyName', 'companyNameAbbr');
            $key_attribute = 'eid';
            $select_attributes = array('companyName');
            $order = array('by' => 'companyName', 'sort' => 'ASC');
            $descinfo = 'country';
            $extra_where .= ' isActive=1 AND approved=1';
        }
        if($core->input['for'] == 'allcustomertypes') {
            $table = 'entities';
            $attributes = array('companyName', 'companyNameAbbr');
            $key_attribute = 'eid';
            $select_attributes = array('companyName');
            $order = array('by' => 'companyName', 'sort' => 'ASC');
            $extra_where .= ' type IN ("pc", "c")';
            $descinfo = 'country';
        }
        if($core->input['for'] == 'supplier' || $core->input['for'] == 'customer' || $core->input['for'] == 'competitorsupp' || $core->input['for'] == 'competitortradersupp' || $core->input['for'] == 'competitorproducersupp') {
            if($core->input['for'] == 'supplier') {
                $type = 's';
                //  $core->usergroup['canViewAllSupp'] = 1;
                if($core->usergroup['canViewAllSupp'] == 0) {
                    if(is_array($core->user['suppliers']['eid'])) {
                        $inentities = implode(',', $core->user['suppliers']['eid']);
                        $extra_where = 'eid IN ('.$inentities.')';
                    }
                }
            }
            elseif($core->input['for'] == 'competitorsupp') {
                $type = 'cs';
                $extra_where = 'supplierType="t" OR supplierType="p" OR supplierType="b"';
            }
            elseif($core->input['for'] == 'competitortradersupp') {
                $type = 'cs';
                $extra_where = 'supplierType="t"';
            }
            elseif($core->input['for'] == 'competitorproducersupp') {
                $type = 'cs';
                $extra_where = 'supplierType="p"';
            }
            else {
                $type = 'c';
                $core->usergroup['canViewAllCust'] = 1;
                if($core->usergroup['canViewAllCust'] == 0) {
                    $core->user['customers'] = array_map(intval, $core->user['customers']);
                    if(is_array($core->user['customers'])) {
                        $inentities = implode(',', $core->user['customers']);
                        $extra_where = 'eid IN ('.$inentities.')';
                    }
                    if(!empty($extra_where)) {
                        $extra_where .=' AND ';
                    }
                    $extra_where .='eid IN (SELECT affe.eid FROM  affiliatedentities affe
									JOIN entities e on (e.eid=affe.eid)
									JOIN affiliates aff on (aff.affid=affe.affid) where aff.affid in('.implode(',', $core->user['affiliates']).') and e.type="'.$type.'")';
                }
            }
            $table = 'entities';
            $attributes = array('companyName', 'companyNameAbbr', 'companyNameShort');
            $key_attribute = 'eid';
            $select_attributes = array('companyName');
            $order = array('by' => 'companyName', 'sort' => 'ASC');
            $descinfo = 'country';
            if(!empty($extra_where)) {
                $extra_where .= ' AND type="'.$type.'"';
            }
            else {
                $extra_where = 'type="'.$type.'"';
            }
        }
        elseif($core->input['for'] == 'chemicalproducts') {
            $table = 'chemicalsubstances';
            $attributes = array('name', 'casNum', 'synonyms');
            $key_attribute = 'csid';

            $select_attributes = array('name', 'casNum');
            $order = array('by' => 'name', 'sort' => 'ASC');
        }
        elseif($core->input['for'] == 'product') {
            if(isset($core->input['rid']) && !empty($core->input['rid'])) {
                $extra_where .= 'spid = "'.$report_data['spid'].'"';
            }
            if($core->usergroup['canViewAllSupp'] == 0 && !isset($core->input['rid']) && empty($supplier_filter)) {
                if(is_array($core->user['suppliers']['eid'])) {
                    $core->user['suppliers']['eid'] = array_map(intval, $core->user['suppliers']['eid']);
                    $supplier_filter = " spid IN (".implode(',', $core->user['suppliers']['eid']).")";
                }
            }
//			if(isset($core->input['userproducts'])) {
//				$supplier_filter = "spid IN('".implode(',', $core->user['suppliers']['eid'])."')";
//			}
            if(!empty($supplier_filter)) {
                if(!empty($extra_where)) {
                    $extra_where .= ' AND';
                }
                $extra_where .= $supplier_filter;
            }

            $table = 'products';
            $attributes = array('name');
            $key_attribute = 'pid';
            $select_attributes = array('name');
            $order = array('by' => 'name', 'sort' => 'ASC');
            $descinfo = 'genericsegment';
        }
        elseif($core->input['for'] == 'affiliate') {
            $table = 'affiliates';
            $attributes = array('name');
            $key_attribute = 'affid';
            $select_attributes = array('name');
            $order = array('by' => 'name', 'sort' => 'ASC');
        }
        elseif($core->input['for'] == 'chemfunctionproducts') {
            if($core->usergroup['canViewAllsupp'] == 0) {
                if(is_array($core->user['suppliers']['eid'])) {
                    $core->user['suppliers']['eid'] = array_map(intval, $core->user['suppliers']['eid']);
                    $supplier_filter = 'spid IN ('.implode(',', $core->user['suppliers']['eid']).')';
                }
                else {
                    $supplier_filter = 'spid IN (0)';
                }
            }
            if(!empty($supplier_filter)) {
//$extra_where = $supplier_filter;
            }
            $table = 'products';
            $attributes = array('name');
            $key_attribute = 'pid';
            $select_attributes = array('name');
            $order = array('by' => 'name', 'sort' => 'ASC');
            $descinfo = 'productsegment';
        }
        elseif($core->input['for'] == 'chemfunctionchecmical') {
            $table = 'chemicalsubstances';
            $attributes = array('name', 'synonyms');
            $key_attribute = 'csid';
            $select_attributes = array('name');
            $order = array('by' => 'name', 'sort' => 'ASC');
            $descinfo = 'checmicalfunction';
        }
        elseif($core->input['for'] == 'representative' || $core->input['for'] == 'supprepresentative') {
            if(IN_AREA == 'user') {
                if($core->input['for'] == 'supprepresentative') {
                    if(!empty($supplier_filter)) {
                        $extra_where = $supplier_filter;
                    }
                    else {
                        if($core->usergroup['canViewAllSupp'] == 0) {
                            $inentities = implode(',', $core->user['suppliers']['eid']);
                            $extra_where = 'er.eid IN ('.$inentities.')';
                        }
                    }
                    if(!empty($extra_where)) {
                        $extra_where_and = ' AND ';
                    }
                    $extra_where .= $extra_where_and.'e.type="s"';
                }
                else {
                    if(!empty($customer_filter)) {
                        $extra_where = $customer_filter;
                    }
                    else {
                        if($core->usergroup['canViewAllCust'] == 0) {
                            $inentities = implode(',', $core->user['customers']);
                            $extra_where = 'er.eid IN ('.$inentities.')';
                        }
                    }
                    if(!empty($extra_where)) {
                        $extra_where_and = ' AND ';
                    }
                    $extra_where .= $extra_where_and.'e.type="c"';
                }
            }

            if(!empty($supplier_filter) || !empty($customer_filter)) {
                $table = Tprefix.'representatives r LEFT JOIN '.Tprefix.'entitiesrepresentatives er ON (r.rpid=er.rpid) LEFT JOIN '.Tprefix.'entities e ON (e.eid=er.eid)';
            }
            else {
                $extra_where = '';
                $table = Tprefix.'representatives r LEFT JOIN '.Tprefix.'entitiesrepresentatives er ON (r.rpid=er.rpid)';
            }
            $attributes = array('r.name', 'r.email');
            $key_attribute = 'r.rpid';
            $select_attributes = array('name', 'email');
            $order = array('by' => 'name', 'sort' => 'ASC');
        }
        elseif($core->input['for'] == 'user') {
            $table = 'users';
            $attributes = array('firstName', 'lastName', 'displayName');
            $key_attribute = 'uid';
            $select_attributes = array('displayName');
            $order = array('by' => 'firstName', 'sort' => 'ASC');
        }
        elseif($core->input['for'] == 'cities' || $core->input['for'] == 'sourcecity' || $core->input['for'] == 'destinationcity') {
            if(strlen($core->input['value']) < 3) {
                exit;
            }

            if(isset($core->input['coid']) && !empty($core->input['coid'])) {
                $restrictcountry_filter = "coid ='".intval($core->input['coid'])."'";
            }

            if(!empty($restrictcountry_filter)) {
                $extra_where = $restrictcountry_filter;
            }


            $table = 'cities';
            $attributes = array('name');
            $key_attribute = 'ciid';
            $select_attributes = array('name', 'unlocode');
            $extra_info = array('table' => 'countries');
            $descinfo = 'citycountry';
            $order = array('by' => 'name', 'sort' => 'ASC');
        }
        elseif($core->input['for'] == 'countries') {
            $table = 'countries';
            $attributes = array('name');
            $key_attribute = 'coid';
            $select_attributes = array('name');
            $order = array('by' => 'name', 'sort' => 'ASC');
        }
        elseif($core->input['for'] == 'airports') {
            if(strlen($core->input['value']) < 3) {
                exit;
            }
            $table = 'travelmanager_airports';
            $attributes = array('name');
            $key_attribute = 'apid';
            $select_attributes = array('name');
            $order = array('by' => 'name', 'sort' => 'ASC');
        }
        elseif($core->input['for'] == 'hotels') {
            $extra_where = ' isApproved=0';
            if(isset($core->input['city']) && !empty($core->input['city'])) {
                // $restrictdest_filter = "city ='".intval($core->input['city'])."'";
            }
            if(!empty($restrictdest_filter)) {
                // $extra_where .= ' AND '.$restrictdest_filter;
            }
            $table = 'travelmanager_hotels';
            $attributes = array('name');
            $key_attribute = 'tmhid';
            $select_attributes = array('name');
            $descinfo = 'hotelcitycountry';
            //$extra_info = array('table' => 'hotelcountries');
            $order = array('by' => 'name', 'sort' => 'ASC');
        }
        if(isset($core->input['exclude']) && !empty($core->input['exclude'])) {
            if(is_array($core->input['exclude'])) {
                $core->input['exclude'] = array_map(intval, $core->input['exclude']);
            }
            if(empty($extra_where)) {
                $extra_where = "{$key_attribute} NOT IN ({$core->input[exclude]})";
            }
            else {
                $extra_where .= " AND {$key_attribute} NOT IN ({$core->input[exclude]})";
            }
        }
        $results_list = quick_search($table, $attributes, $core->input['value'], $select_attributes, $key_attribute, array('returnType' => $core->input['returnType'], 'order' => $order, 'extra_where' => $extra_where, 'descinfo' => $descinfo));
        $referrer = explode('&', $_SERVER['HTTP_REFERER']);
        $module = substr($referrer[0], strpos(strtolower($referrer[0]), 'module=') + 7);
        if($core->input['for'] == 'supplier') {
            if($core->input['returnType'] != 'json') {
                if(strpos(strtolower($_SERVER['HTTP_REFERER']), ADMIN_DIR) !== false) {
                    $results_list .= "<p><hr />&rsaquo;&rsaquo; <a href='index.php?module=entities/add&amp;type=supplier' target='_blank'>{$lang->add}</a></p>";
                }
                else {
                    $results_list .= "<p><hr />&rsaquo;&rsaquo; <a href='index.php?module=contents/addentities&amp;type=supplier' target='_blank'>{$lang->add}</a></p>";
                }
            }
        }
        /* else
          {
          $results_list .= "<p><hr />&rsaquo;&rsaquo; <a href='#' id='addnew_{$module}_".$core->input['for']."'>{$lang->add}</a></p>";
          } */
        output($results_list);
    }
}
?>