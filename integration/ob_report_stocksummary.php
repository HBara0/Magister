<?php
/*
 * Copyright © 2013 Orkila International Offshore, All Rights Reserved
 *
 * Stock Summary Report from OB
 * $id: ob_report_stockaging.php
 * Created:        @zaher.reda    Sep 2, 2013 | 1:04:11 PM
 * Last Update:    @zaher.reda    Sep 2, 2013 | 1:04:11 PM
 */

require '../inc/init.php';

define('AUTHCODE', 'X1iIMm7pG06ip6o95HEa');
if($core->input['authCode'] == AUTHCODE) {
    $currency_obj = new Currencies('USD');
    $date_info = getdate_custom(TIME_NOW);

    $db_info = array('database' => 'openbrav_production', 'username' => 'openbrav_appuser', 'password' => '8w8;MFRy4g^3', 'engine' => 'postgre');

    $affiliates_index = array(
            'C08F137534222BD001345BAA60661B97' => 19, //Orkila Tunisia
            '0B366EFAE0524FDAA97A1322A57373BB' => 22, //Orkila East Africa
            'DA0CE0FED12C4424AA9B51D492AE96D2' => 11, //Orkila Nigeria
            'F2347759780B43B1A743BEE40BA213AD' => 23, //Orkila Ghana
            'BD9DC2F7883B4E11A90B02A9A47991DC' => 1, //Orkila Lebanon
            '933EC892369245E485E922731D46FCB1' => 20, //Orkila Senegal
            '51FB1280AB104EFCBBB982D50B3B7693' => 21, //Orkila CI
            '7AD08388D369403A9DF4B8240E3AD7FF' => 27 //Orkila International
    );

    $affiliates_addrecpt = array(
            19 => array(244, 'audrey.sacy', 356),
            22 => array(248, 246, 287, 270, 'audrey.sacy', 356),
            23 => array('zadok.oppong-boahene', 'courage.dzandu', 322, 321, 'audrey.sacy', 'tarek.chalhoub', 63, 356),
            1 => array(12, 333, 182, 43, 'audrey.sacy', 356),
            21 => array(63, 158, 'audrey.sacy', 'bamou.diop', 'annick.kouame', 'abel.laho', 'boulongo.diata', 356),
            27 => array(12, 333, 68, 67, 'audrey.sacy', 342, 30, 356),
            20 => array('michel.mbengue', 'abdoulaye.lo', 'audrey.sacy', 'ansou.dabo', 'fatimatou.diallo', 356),
            11 => array(323, 108, 186, 335, 184, 111, 109, 280, 326, 295, 289, 187, 112, 113, 312, 107, 'audrey.sacy', 356)
    );

    $integration = new IntegrationOB($db_info, 'C08F137534222BD001345B7B2E8F182D', $affiliates_index, 3, array('from' => '2010-01-01'));

    $status = $integration->get_status();
    if(!empty($status)) {
        echo 'Error';
        exit;
    }

    /* Configurations Section - START */
    $report_options = array('roundto' => 0);

    $configs['summary']['info'] = array('title' => 'Stock Details');
    $configs['summary']['output_fields'] = array(
//			'manager' => 'Business Manager',
            'category' => array('source' => array('product', 'category'), 'attribute' => 'value', 'title' => 'Segment', 'styles' => 'width: 5%;'),
            'supplier' => array('source' => 'supplier', 'attribute' => 'value', 'title' => 'Supplier'),
            'product' => array('source' => 'product', 'attribute' => 'name', 'title' => 'Product'),
            'warehouse' => array('source' => 'warehouse', 'attribute' => 'value', 'title' => 'Warehouse'),
//			'lot' => array('source' => array('transaction', 'attributes'), 'attribute' => 'lot', 'title' => 'Lot'),
            'packaging' => array('source' => array('transaction', 'attributes'), 'attribute' => 'packaging', 'title' => 'Packaging'),
            'initialquantity' => array('source' => 'stack', 'attribute' => 'qty', 'title' => 'Qty Received', 'numformat' => true),
            'quantitysold' => array('source' => 'stack', 'attribute' => 'soldqty', 'title' => 'Sold Qty', 'numformat' => true),
            'quantity' => array('source' => 'stack', 'attribute' => 'remaining_qty', 'title' => 'Stock Qty', 'numformat' => true),
            'uom' => array('source' => array('product', 'uom'), 'attribute' => 'uomsymbol', 'title' => 'UoM'),
            'unitcost' => array('source' => null, 'title' => 'Unit Cost', 'numformat' => true),
            'unitcostusd' => array('source' => null, 'title' => 'Unit Cost<br />(USD)', 'numformat' => true),
            'cost' => array('source' => 'stack', 'attribute' => 'remaining_cost', 'title' => 'Cost', 'numformat' => true),
            'costusd' => array('source' => null, 'title' => 'Cost (USD)', 'numformat' => true),
            'inputdate' => array('source' => 'transaction', 'attribute' => 'movementdate', 'title' => 'Entry Date', 'isdate' => true),
            'daysinstock' => array('source' => 'stack', 'attribute' => 'daysinstock', 'title' => 'In Stock<br />(Days)', 'styles' => array(150 => 'background-color: #F1594A; text-align: center;', 120 => 'background-color: #F8C830; text-align: center;', 90 => 'background-color: #F2EB80; text-align: center;', 0 => 'background-color: #ABD25E; text-align: center;')),
            'expirydate' => array('source' => array('transaction', 'attributes'), 'attribute' => 'guaranteedate', 'title' => 'Expiry Date', 'isdate' => true),
            'daystoexpire' => array('source' => array('transaction', 'attributes'), 'attribute' => 'daystoexpire', 'title' => 'Days to Expire', 'styles' => array(0 => 'background-color: #F1594A; text-align: center;', 90 => 'background-color: #F8C830; text-align: center;', 180 => 'background-color: #F2EB80; text-align: center;', 270 => 'background-color: #ABD25E; text-align: center;'))
    );

    $configs['summary']['summary_categories'] = array('category' => 'm_product_category_id', 'warehouse' => 'm_warehouse_id', 'product' => 'm_product_id', 'supplier' => 'c_bpartner_id');
    $configs['summary']['summary_reqinfo'] = array('quantity', 'cost', 'costusd');
    $configs['summary']['summary_order_attr'] = 'costusd';
    $configs['summary']['order_attr'] = 'remaining_cost';
    $configs['summary']['maintable_hiddencols'] = array('supplier', 'warehouse', 'category', 'product', 'packaging', 'uom', 'unitcost', 'inputdate', 'expirydate', 'daystoexpire');
    $configs['summary']['total_types'] = array('initialquantity', 'quantitysold', 'quantity', 'cost', 'costusd');


    $configs['aging']['summary_categories'] = array('category' => 'm_product_category_id', 'warehouse' => 'm_warehouse_id', 'supplier' => 'c_bpartner_id');
    $configs['aging']['summary_reqinfo'] = array('quantity', 'cost', 'range1cost', 'range1qty', /* 'range2cost', 'range2qty', 'range3cost', 'range3qty', */ 'range4cost', 'range4qty', 'range5cost', 'range5qty');
    $configs['aging']['summary_order_attr'] = 'cost';
    $configs['aging']['order_attr'] = 'cost';
    $configs['aging']['maintable_hiddencols'] = array('supplier', 'warehouse', 'category');
    $configs['aging']['total_types'] = array('quantity', 'cost', 'costusd', 'range1cost', 'range1qty', 'range1costusd', /* 'range2cost', 'range2qty', 'range3cost', 'range3qty', */ 'range4cost', 'range4qty', 'range4costusd', 'range5cost', 'range5qty', 'range5costusd');

    $configs['aging']['info'] = array('title' => 'Stock Aging');
    $configs['aging']['output_fields'] = array(
            //			'manager' => 'Business Manager',
            'product' => array('source' => 'product', 'attribute' => 'name', 'title' => 'Product'),
            'supplier' => array('source' => 'supplier', 'attribute' => 'value', 'title' => 'Supplier'),
            'warehouse' => array('source' => 'warehouse', 'attribute' => 'value', 'title' => 'Warehouse'),
            'category' => array('source' => array('product', 'category'), 'attribute' => 'value', 'title' => 'Segment'),
            'quantity' => array('source' => null, 'title' => 'Stock Qty', 'numformat' => true),
            'uom' => array('source' => array('product', 'uom'), 'attribute' => 'uomsymbol', 'title' => 'UoM'),
            'cost' => array('source' => 'entries', 'attribute' => 'cost', 'title' => 'Cost', 'numformat' => true),
            'costusd' => array('source' => null, 'title' => 'Cost (USD)', 'numformat' => true),
            'range1cost' => array('source' => array('entries', 'costs'), 'attribute' => 1, 'title' => '0-90<br />Amt', 'numformat' => true, 'styles' => 'background-color: #ABD25E;', 'chartlinecolor' => array('R' => 171, 'G' => 210, 'B' => 94)),
            'range1costusd' => array('source' => null, 'attribute' => 1, 'title' => '0-90<br />Amt USD', 'numformat' => true, 'styles' => 'background-color: #ABD25E;'),
            'range1qty' => array('source' => array('entries', 'qty'), 'attribute' => 1, 'title' => '0-90<br />Qty', 'numformat' => true, 'styles' => 'background-color: #ABD25E;'),
            'range4cost' => array('source' => array('entries', 'costs'), 'attribute' => 4, 'title' => '90-180<br />Amt', 'numformat' => true, 'styles' => 'background-color: #F8C830;', 'chartlinecolor' => array('R' => 248, 'G' => 200, 'B' => 48)),
            'range4costusd' => array('source' => null, 'attribute' => 4, 'title' => '90-180<br />Amt USD', 'numformat' => true, 'styles' => 'background-color: #F8C830;'),
            'range4qty' => array('source' => array('entries', 'qty'), 'attribute' => 4, 'title' => '90-180<br />Qty', 'numformat' => true, 'styles' => 'background-color: #F8C830;'),
            'range5cost' => array('source' => array('entries', 'costs'), 'attribute' => 5, 'title' => '> 180<br />Amt', 'numformat' => true, 'styles' => 'background-color: #F1594A;', 'chartlinecolor' => array('R' => 241, 'G' => 89, 'B' => 74)),
            'range5costusd' => array('source' => null, 'attribute' => 5, 'title' => '> 180<br />Amt USD', 'numformat' => true, 'styles' => 'background-color: #F1594A;'),
            'range5qty' => array('source' => array('entries', 'qty'), 'attribute' => 5, 'title' => '> 180<br />Qty', 'numformat' => true, 'styles' => 'background-color: #F1594A;')
    );
    /* Configurations Section - END */

    foreach($affiliates_index as $orgid => $affid) {
        $output = $summaries_ouput = '';
        $affiliateobj = new Affiliates($affid, false);
        $affiliate = $affiliateobj->get();
        $affiliate['currency'] = $affiliateobj->get_country()->get_maincurrency()->get()['alphaCode'];

        $integration->set_organisations(array($orgid));
        $inputs = $integration->get_fifoinputs(array($orgid), array('hasqty' => true));
        $fxrates['usd'] = $currency_obj->get_latest_fxrate($affiliate['currency']);

        foreach($configs as $report => $config) {
            $totals = $summaries = array();
            if($report == 'aging') {
                foreach($inputs as $key => $input) {
                    if($input['stack']['daysinstock'] < 29) {
                        $range = 1;
                    }
                    elseif($input['stack']['daysinstock'] < 59) {
                        $range = 1;
                    }
                    elseif($input['stack']['daysinstock'] < 90) {
                        $range = 1;
                    }
                    elseif($input['stack']['daysinstock'] < 180) {
                        $range = 4;
                    }
                    else {
                        $range = 5;
                    }
                    $inputs[$key]['entries']['qty'][$range] += $input['stack']['remaining_qty'];
                    $inputs[$key]['entries']['costs'][$range] += $input['stack']['remaining_cost'];

                    $inputs[$key]['entries']['cost'] += $input['stack']['remaining_cost'];
                }
            }
            $output .= '<h3>'.$config['info']['title'].'</h3>';
            $output .= '<table width="100%" cellspacing="0" cellpadding="5" style="border: 1px solid #CCC; font-size: 10px;" border="0">';
            $output .= '<tr>';
            foreach($config['output_fields'] as $field => $field_configs) {
                if(is_array($field_configs)) {
                    $output .= '<th style="background: #91b64f;">'.$field_configs['title'].'</th>';
                }
                else {
                    $output .= '<th style="background: #91b64f;">'.$field_configs.'</th>';
                }
            }
            $output .= '</tr>';

            reset($config['output_fields']);
            if(is_array($inputs)) {
                $order_attr = $config['order_attr'];
                ${$order_attr} = array();
                foreach($inputs as $data_key => $data_row) {
                    ${$order_attr}[$data_key] = $data_row['stack'][$order_attr];
                }
                array_multisort(${$order_attr}, SORT_DESC, $inputs);

                foreach($inputs as $id => $input) {
                    $output .= '<tr>';
                    foreach($config['output_fields'] as $field => $field_configs) {
                        $output_td_style = '';
                        if(is_array($field_configs) && $field_configs['source'] != null) {
                            if(is_array($field_configs['source'])) {
                                $source_data = '';
                                foreach($field_configs['source'] as $source) {
                                    if(empty($source_data)) {
                                        $source_data = $input[$source];
                                    }
                                    else {
                                        $source_data = $source_data[$source];
                                    }
                                }
                                $output_value = $source_data[$field_configs['attribute']];
                            }
                            else {
                                $output_value = $input[$field_configs['source']][$field_configs['attribute']];
                            }

                            if(empty($output_value) || strstr($output_value, 'Orkila')) {
                                if($field == 'supplier') {
                                    $product = new Products($db->fetch_field($db->query('SELECT localId FROM integration_mediation_products WHERE foreignSystem=3 AND foreignName="'.$input['product']['name'].'"'), 'localId'));
                                    if(!is_object($product)) {
                                        $product = new Products($db->fetch_field($db->query('SELECT pid FROM products WHERE name="'.$input['product']['name'].'"'), 'pid'));
                                    }
                                    $output_value = $product->get_supplier()->get()['companyNameShort'];
                                    if(empty($output_value)) {
                                        $output_value = $product->get_supplier()->get()['companyName'];
                                        if(empty($output_value)) {
                                            $output_value = 'Information Unavailable';
                                        }
                                    }
                                    $input['supplier']['name'] = $input['supplier']['value'] = $output_value;
                                    $input['supplier']['c_bpartner_id'] = md5($output_value);
                                    $inputs[$id]['supplier']['name'] = $inputs[$id]['supplier']['value'] = $output_value;
                                    $inputs[$id]['supplier']['c_bpartner_id'] = $input['supplier']['c_bpartner_id'];
                                }
                            }

                            if(in_array($field, array_keys($config['summary_categories']))) {
                                foreach($config['summary_categories'] as $category => $attribute) {
                                    if(empty($input[$category][$attribute])) {
                                        continue;
                                    }
                                    $summaries[$category][$input[$category][$attribute]]['name'] = $input[$category]['value'];
                                }
                            }
                            if(in_array($field, $config['summary_reqinfo'])) {
                                foreach($config['summary_categories'] as $category => $attribute) {
                                    // $summaries[$category][$input[$category][$attribute]]['name'] = $input[$category]['value'];
                                    if(empty($input[$category][$attribute])) {
                                        continue;
                                    }
                                    if(in_array($field, array('quantity', 'range1qty', 'range2qty', 'range3qty', 'range4qty', 'range5qty'))) {
                                        $summaries[$category][$input[$category][$attribute]][$field][$input['product']['uom']['uomsymbol']] += $output_value;
                                    }
                                    else {

                                        $summaries[$category][$input[$category][$attribute]][$field] += $output_value;
                                    }
                                }
                            }

                            if(in_array($field, $config['total_types'])) {
                                $totals[$field] += $output_value;
                            }

                            if($field_configs['numformat'] == true) {
                                $output_value = number_format($output_value, $report_options['roundto'], '.', ' ');
                                $output_td_style = ' text-align: right;';
                            }

                            if($field_configs['isdate'] == true) {
                                if(strstr($output_value, '.')) {
                                    $output_valueobj = DateTime::createFromFormat('Y-m-d G:i:s.u', $output_value);
                                }
                                else {
                                    $output_valueobj = DateTime::createFromFormat('Y-m-d G:i:s', $output_value);
                                }

                                if($output_valueobj != false) {
                                    $output_value = $output_valueobj->format($core->settings['dateformat']);
                                }
                            }

                            if(isset($field_configs['styles'])) {
                                if(is_array($field_configs['styles'])) {
                                    krsort($field_configs['styles']);
                                    foreach($field_configs['styles'] as $num => $style) {
                                        if($output_value > $num) {
                                            $output_td_style .= $style;
                                            break;
                                        }
                                    }
                                }
                                else {
                                    if(preg_match('/^range[(0-9)]/i', $field)) {
                                        if(!empty($output_value)) {
                                            $output_td_style .= $field_configs['styles'];
                                        }
                                    }
                                    else {
                                        $output_td_style .= $field_configs['styles'];
                                    }
                                }
                            }

                            $output .= '<td style="border: 1px solid #CCC; '.$output_td_style.'">'.$output_value.'</td>';
                            unset($output_value);
                        }
                        else {
                            switch($field) {
                                case 'unitcost':
                                    $output_value = $input['stack']['remaining_cost'] / $input['stack']['remaining_qty'];
                                    $input['unitcost'] = $output_value;
//                                if(in_array($field, $config['total_types'])) {
//                                    $totals[$field] += $output_value;
//                                }
                                    $output .= '<td style="border: 1px solid #CCC; text-align: right;">'.number_format($output_value, $report_options['roundto'], '.', ' ').'</td>';
                                    break;
                                case 'unitcostusd':
                                    $output_value = ($input['stack']['remaining_cost'] / $input['stack']['remaining_qty']) / $fxrates['usd'];
                                    $input['unitcostusd'] = $output_value;
//                                if(in_array($field, $config['total_types'])) {
//                                    $totals[$field] += $output_value;
//                                }

                                    $output .= '<td style="border: 1px solid #CCC; text-align: right;">'.number_format($output_value, $report_options['roundto'], '.', ' ').'</td>';
                                    break;
                                case 'costusd':
                                    $output_value = $input['stack']['remaining_cost'] / $fxrates['usd'];
                                    $input['costusd'] = $output_value;

                                    if(in_array($field, $config['summary_reqinfo'])) {
                                        foreach($config['summary_categories'] as $category => $attribute) {
                                            $summaries[$category][$input[$category][$attribute]][$field] += $output_value;
                                        }
                                    }

                                    $output .= '<td style="border: 1px solid #CCC; text-align: right;">'.number_format($output_value, $report_options['roundto'], '.', ' ').'</td>';

                                    $date_value = $input[$config['output_fields']['inputdate']['source']][$config['output_fields']['inputdate']['attribute']];
                                    if(strstr($date_value, '.')) {
                                        $date_valueobj = DateTime::createFromFormat('Y-m-d G:i:s.u', $date_value);
                                    }
                                    else {
                                        $date_valueobj = DateTime::createFromFormat('Y-m-d G:i:s', $date_value);
                                    }

                                    if(in_array($field, $config['total_types'])) {
                                        $totals[$field] += $output_value;
                                    }
                                    //$totals['costusd'][$date_valueobj->format('Y')][$date_valueobj->format('n')] += $input['stack']['remaining_cost'] / $rate;
                                    break;
                                case 'quantity':
                                    $output_value = array_sum($input['entries']['qty']);
                                    if(in_array($field, $config['summary_reqinfo'])) {
                                        foreach($config['summary_categories'] as $category => $attribute) {
                                            if(empty($input[$category][$attribute])) {
                                                continue;
                                            }
                                            $summaries[$category][$input[$category][$attribute]][$field][$input['product']['uom']['uomsymbol']] += $output_value;
                                        }
                                    }

                                    if(in_array($field, $config['total_types'])) {
                                        $totals[$field] += $output_value;
                                    }
                                    $output .= '<td style="border: 1px solid #CCC; text-align: right;">'.number_format($output_value, $report_options['roundto'], '.', ' ').'</td>';
                                    break;
                                case preg_match('/^range(0-9)+costusd/i', $field):
                                    $output_value = $input['entries']['costs'][$field_configs['attribute']] / $fxrates['usd'];
                                    if(in_array($field, $config['summary_reqinfo'])) {
                                        foreach($config['summary_categories'] as $category => $attribute) {
                                            if(empty($input[$category][$attribute])) {
                                                continue;
                                            }
                                            $summaries[$category][$input[$category][$attribute]][$field] += $output_value;
                                        }
                                    }

                                    if(isset($field_configs['styles'])) {
                                        if(!empty($output_value)) {
                                            $output_td_style .= $field_configs['styles'];
                                        }
                                    }
                                    $output .= '<td style="border: 1px solid #CCC; text-align: right; '.$output_td_style.'">'.number_format($output_value, $report_options['roundto'], '.', ' ').'</td>';

                                    if(in_array($field, $config['total_types'])) {
                                        $totals[$field] += $output_value;
                                    }
                                    break;
                            }
                        }
                    }
                    $output .= '</tr>';

                    if($report == 'summary') {
                        if((!is_numeric($input['transaction']['attributes']['daystoexpire']) && !empty($input['transaction']['attributes']['daystoexpire'])) || ($input['transaction']['attributes']['daystoexpire'] <= 90) && $input['transaction']['attributes']['daystoexpire'] != '') {
                            $expired_entries[] = $input;
                        }
                    }
                }
            }
            else {
                $output .= '<tr><td colspan="16">N/A</td></tr>';
            }
            /* Output main table totals row - START */
            $output .= '<tr>';
            foreach($config['output_fields'] as $field => $field_configs) {
                if(in_array($field, $config['maintable_hiddencols'])) {
                    $output .= '<td style="border: 1px solid #CCC;"></td>';
                    continue;
                }
                $output_value = $totals[$field];
                if(is_numeric($output_value)) {
                    $output_value = number_format($output_value, $report_options['roundto'], '.', ' ');
                }
                $output .= '<td style="border: 1px solid #CCC; font-weight: bold; text-align: right; text-decoration:underline;">'.$output_value.'</td>';
            }
            $output .= '</tr>';
            /* Output main table totals row - END */
            $output .= '</table>';

            /* Parse Summaries - Start */
            if(is_array($summaries)) {
                foreach($summaries as $category => $category_data) {
                    $totals = array();
                    $summaries_ouput .= '<h3>'.$config['output_fields'][$category]['title'].' Summary</h3>';
                    $summaries_ouput .= '<table width="100%" cellspacing="0" cellpadding="5" style="border: 1px solid #CCC; font-size: 10px;" border="0">';
                    $summaries_ouput .= '<tr><th style="background: #91b64f;">'.$config['output_fields'][$category]['title'].'</th>';
                    foreach($config['summary_reqinfo'] as $reqinfo) {
                        $summaries_ouput .= '<th style="background: #91b64f;">'.$config['output_fields'][$reqinfo]['title'].'</th>';
                    }
                    unset($out_field_title);
                    $summaries_ouput .= '</tr>';

                    ${$config['summary_order_attr']} = array();
                    foreach($category_data as $cat_data_key => $cat_data_row) {
                        ${$config['summary_order_attr']}[$cat_data_key] = $cat_data_row[$config['summary_order_attr']];
                    }
                    array_multisort(${$config['summary_order_attr']}, SORT_DESC, $category_data);
                    foreach($category_data as $cat_data_row) {
                        $summaries_ouput .= '<tr>';
                        foreach($cat_data_row as $output_key => $output_value) {
                            $output_td_style = '';
                            if(is_array($output_value)) {
                                $output_values = $output_value;
                                $output_value = '';
                                foreach($output_values as $output_key_temp => $output_value_temp) {
                                    if(in_array($output_key, $config['total_types'])) {
                                        $totals[$output_key][$output_key_temp] += $output_value_temp;
                                    }

                                    if($config['output_fields'][$output_key]['numformat'] == true) {
                                        $output_value_temp = number_format($output_value_temp, $report_options['roundto'], '.', ' ');
                                        $output_td_style = ' text-align: right;';
                                    }
                                    $output_value .= $output_value_temp.' '.$output_key_temp.'<br />';
                                }
                            }
                            else {
                                if($config['output_fields'][$output_key]['numformat']) {
                                    if(in_array($output_key, $config['total_types'])) {
                                        $totals[$output_key] += $output_value;
                                    }
                                    if($config['output_fields'][$output_key]['numformat'] == true) {
                                        $output_value = number_format($output_value, $report_options['roundto'], '.', ' ');
                                        $output_td_style = ' text-align: right;';
                                    }
                                }
                            }

                            if(isset($config['output_fields'][$output_key]['styles'])) {
                                $output_td_style .= $config['output_fields'][$output_key]['styles'];
                            }
                            $summaries_ouput .= '<td style="border: 1px solid #CCC; width: '.(100 / ( count($config['summary_reqinfo']) + 1 ) ).'%; '.$output_td_style.'">'.$output_value.'</td>';
                        }
                        $summaries_ouput .= '</tr>';
                    }
                    /* Output summary table totals row - START */
                    $summaries_ouput .= '<tr>';
                    $summaries_ouput .= '<td style="border: 1px solid #CCC;"></td>';
                    $output_value = null;
                    foreach($config['summary_reqinfo'] as $field) {
                        if(is_array($totals[$field])) {
                            foreach($totals[$field] as $key => $output_value_temp) {
                                if(is_numeric($output_value_temp)) {
                                    $output_value .= number_format($output_value_temp, $report_options['roundto'], '.', ' ').' '.$key.'<br />';
                                }
                            }
                        }
                        else {
                            $output_value = $totals[$field];
                            if(is_numeric($output_value)) {
                                $output_value = number_format($output_value, $report_options['roundto'], '.', ' ');
                            }
                        }
                        $summaries_ouput .= '<td style="border: 1px solid #CCC; font-weight: bold; text-align: right; text-decoration:underline;">'.$output_value.'</td>';
                        $output_value = '';
                    }
                    $summaries_ouput .= '</tr>';
                    /* Output summary table totals row - END */
                    $summaries_ouput .= '</table>';
                }
            }
            /* Parse Summaries - END */
        }
        /* Parse Expired Products Table - START */

        $alerts = '';
        if(is_array($expired_entries)) {
            $totals = null;
            $alerts .= '<div style="font-weight: bold; color: red; font-size:18pt;">The following products have expired or are expiring soon!</div><br /><table width="100%" cellspacing="0" cellpadding="5" style="border: 1px solid #CCC; font-size: 10px;" border="0">';
            $alerts .= '<tr>';
            foreach($configs['summary']['output_fields'] as $field => $field_configs) {
                if(is_array($field_configs)) {
                    $alerts .= '<th style="background: #91b64f;">'.$field_configs['title'].'</th>';
                }
                else {
                    $alerts .= '<th style="background: #91b64f;">'.$field_configs.'</th>';
                }
            }
            $alerts .= '</tr>';

            $order_attr = 'remaining_cost';
            ${$order_attr} = array();
            foreach($expired_entries as $data_key => $data_row) {
                ${$order_attr}[$data_key] = $data_row['stack'][$order_attr];
            }
            array_multisort(${$order_attr}, SORT_DESC, $expired_entries);

            foreach($expired_entries as $id => $input) {
                $alerts .= '<tr>';
                foreach($configs['summary']['output_fields'] as $field => $field_configs) {
                    $output_td_style = '';

                    if(is_array($field_configs) && $field_configs['source'] != null) {
                        if(is_array($field_configs['source'])) {
                            $source_data = '';
                            foreach($field_configs['source'] as $source) {
                                if(empty($source_data)) {

                                    $source_data = $input[$source];
                                }
                                else {
                                    $source_data = $source_data[$source];
                                }
                            }
                            $output_value = $source_data[$field_configs['attribute']];
                        }
                        else {
                            $output_value = $input[$field_configs['source']][$field_configs['attribute']];
                        }

                        if(in_array($field, $configs['summary']['total_types'])) {
                            $totals[$field] += $output_value;
                        }

                        if($field_configs['numformat'] == true) {
                            $output_value = number_format($output_value, $report_options['roundto'], '.', ' ');
                            $output_td_style = ' text-align: right;';
                        }

                        if($field_configs['isdate'] == true) {
                            if(strstr($output_value, '.')) {
                                $output_valueobj = DateTime::createFromFormat('Y-m-d G:i:s.u', $output_value);
                            }
                            else {
                                $output_valueobj = DateTime::createFromFormat('Y-m-d G:i:s', $output_value);
                            }

                            if($output_valueobj != false) {
                                $output_value = $output_valueobj->format($core->settings['dateformat']);
                            }
                        }

                        if(isset($field_configs['styles'])) {
                            if(is_array($field_configs['styles'])) {
                                krsort($field_configs['styles']);
                                foreach($field_configs['styles'] as $num => $style) {
                                    if($output_value > $num) {
                                        $output_td_style .= $style;
                                        break;
                                    }
                                }
                            }
                            else {
                                $output_td_style = $field_configs['styles'];
                            }
                        }

                        $alerts .= '<td style="border: 1px solid #CCC; '.$output_td_style.'">'.$output_value.'</td>';
                        unset($output_value);
                    }
                    else {
                        if(in_array($field, $configs['summary']['total_types'])) {
                            $totals[$field] += $input[$field];
                        }
                        $alerts .= '<td style="border: 1px solid #CCC; text-align: right;">'.number_format($input[$field], $report_options['roundto'], '.', ' ').'</td>';

                        unset($output_value);
                    }
                }
                $alerts .= '</tr>';
            }

            /* Output expired table totals row - START */
            $alerts .= '<tr>';
            foreach($configs['summary']['output_fields'] as $field => $field_configs) {
                if(in_array($field, $configs['summary']['maintable_hiddencols'])) {
                    $alerts .= '<td style="border: 1px solid #CCC;"></td>';
                    continue;
                }
                $output_value = $totals[$field];
                if(is_numeric($output_value)) {
                    $output_value = number_format($output_value, $report_options['roundto'], '.', ' ');
                }
                $alerts .= '<td style="border: 1px solid #CCC; font-weight: bold; text-align: right; text-decoration:underline;">'.$output_value.'</td>';
            }
            $alerts .= '</tr>';
            /* Output expired table totals row - END */
            $alerts .= '</table>';
        }
        unset($expired_entries);
        /* Parse Expired Products Table - END */


        /* Parse Stock Evolution Report - START */
        $aging_scale = array(2 => '90-179', 3 => '180-359', 4 => '>=360');
        $aging_scale_config = array(0, 90, 180, 360);
        $stockevolution_output = '<h3>Stock Evolution</h3>';
        $stockevolution_output .= '<table width="100%" cellspacing="0" cellpadding="5" style="border: 1px solid #CCC; font-size: 10px;" border="0">';
        $stockevolution_output .= '<tr><th style="background: #91b64f;">Month</td><th style="background: #91b64f;">Value K.USD</td>';
        foreach($aging_scale as $key => $age) {
            $stockevolution_output .= '<th style="background: #91b64f;">'.$age.'</td>';
        }
        $stockevolution_output .= '</tr>';

        $first_transaction = $integration->get_firsttransaction(array($orgid));
        if(TIME_NOW - strtotime($first_transaction->get()['trxprocessdate']) > (60 * 60 * 24 * 365 )) {
            $date_from = strtotime((date('Y', TIME_NOW) - 1 ).'-01-31');
        }
        else {
            $date_from = strtotime('last day of this month', strtotime($first_transaction->get()['trxprocessdate']));
        }
        $date_to = strtotime('tomorrow -1 second');
        while($date_from < $date_to) {
            $date = getdate_custom($date_from);

            $costingrule_obj = $integration->get_currcostingrule();
//$stockevolution_data = $integration->get_totalvalue_bydate(date('Y-m-d', $date_from), array('costingalgorithm' => $costingrule_obj->get()['m_costing_algorithm_id']), array($orgid));

            $stockevolution_data = $integration->get_totalvalue_bydate(date('Y-m-d', $date_from), array('method' => 'fifo', 'aging_scale' => $aging_scale_config));
            $chart_data['x'][$date['mon'].$date['year']] = $date['mon'].'-'.$date['year'];
            $chart_data['y']['Total'][$date['mon'].$date['year']] = 0;
            if(is_array($stockevolution_data['value'])) {
                $value = (array_sum($stockevolution_data['value']) / $fxrates['usd']) / 1000;
                $stockevolution_output .= '<tr><td style="border: 1px solid #CCC;">'.$date['month'].' - '.$date['year'].' ('.date('Y-m-d', $date_from).')</td><td style="border: 1px solid #CCC; text-align: right;">'.round($value).'</td>';
                $chart_data['y']['Total'][$date['mon'].$date['year']] = $value;
                unset($value);

                /* Parse Aging Info */
                foreach($aging_scale as $key => $age) {
                    if(isset($stockevolution_data['aging']['value'][$key])) {
//$stockevolution_output .= '<td style="border: 1px solid #CCC;">-</td>';
                        $value = (($stockevolution_data['aging']['value'][$key] / $fxrates['usd']) / 1000 );
                        $stockevolution_output .= '<td style="border: 1px solid #CCC; text-align:right;">'.round($value).'</td>';
                        $chart_data['y'][$age][$date['mon'].$date['year']] = $value;
                        unset($value);
                    }
                    else {
                        $stockevolution_output .= '<td style="border: 1px solid #CCC;">-</td>';
                        $chart_data['y'][$age][$date['mon'].$date['year']] = 0;
                    }
                }

                $stockevolution_output .= '</tr>';
            }
            unset($stockevolution_data);

            $date_from = strtotime(date('Y-m-d', $date_from).' last day of next month'); //$date_from + (60 * 60 * 24 * 7);
        }

        $stockevolution_output .= '</table>';

        foreach($aging_scale as $key => $age) {
            $stockevolution_chart_linecolors[$age] = $configs['aging']['output_fields']['range'.$key.'cost']['chartlinecolor'];
        }

        $stockevolution_chart = new Charts(array('x' => $chart_data['x'], 'y' => $chart_data['y']), 'line', array('path' => '../tmp/charts/', 'labelrotationangle' => 90, 'height' => 400, 'width' => 900, 'yaxisname' => 'K. USD', 'graphareay2margin' => 50, 'scale' => SCALE_START0, 'seriesweight' => 2, 'nosort' => true, 'linescolors' => $stockevolution_chart_linecolors));
//$stockevolution_output = '<img src="data:image/png;base64,'.base64_encode(file_get_contents($stockevolution_chart->get_chart())).'" />'.$stockevolution_output;
        $stockevolution_output = '<img src="cid:stockevolutionchart" />'.$stockevolution_output;
        /* Parse FX Rates Chart - START */
        $currency_rates_year = $currency_obj->get_yearaverage_fxrate_monthbased($affiliate['currency'], $date_info['year'], array('distinct_by' => 'alphaCode', 'precision' => 4, 'monthasname' => true), 'USD'); /* GET the fxrate of previous quarter year */
        $currency_rates_year = array_slice($currency_rates_year, 0, date('n', TIME_NOW));


        $overyears_rates = $currency_obj->get_yearaverage_fxrate_yearbased($affiliate['currency'], 2005, $date_info['year'] - 1, array('distinct_by' => 'alphaCode', 'precision' => 4), 'USD');
        $overyears_rates = $overyears_rates + $currency_rates_year;
        $index1 = 8;
        $index2 = count($overyears_rates) - 1;
        $fxrates_linechart = new Charts(array('x' => array_keys($overyears_rates), 'y' => array('1 USD' => $overyears_rates)), 'line', array('xaxisname' => 'Months ('.$date_info['year'].')', 'yaxisname' => 'USD Rate', 'yaxisunit' => '', 'treshholddata' => array('firstindex' => $index1, 'secondindex' => $index2), 'hasthreshold' => 1, 'width' => 700, 'height' => 200, 'scale' => SCALE_START0, 'path' => '../tmp/charts/', 'writelabel' => true));

        $fxratesoverview_output = '<h3>FX Rates Evolution</h3>';
// $fxratesoverview_output .= '<img src="data:image/png;base64,'.base64_encode(file_get_contents($fxrates_linechart->get_chart())).'" />';
        $fxratesoverview_output .= '<img src="cid:fxratesoverview" />';

        /* Parse FX Rates Chart - END */



        /* Parse Stock Evolution Report - END */

//		$summarytables_headers = '';
//		for($month = 1; $month <= 12; $month++) {
//			$summarytables_headers .= '<th style="background: #91b64f;">'.$month.'</th>';
//		}
//
//		if(is_array($totals)) {
//			foreach($totals as $category) {
//				$output .= '<table width="100%" cellspacing="0" cellpadding="5" style="border: 1px solid #CCC; font-size: 10px;" border="0">';
//				$output .= '<th style="background: #91b64f;">Category</th>';
//				$output .= $summarytables_headers;
//				$output .= '<th style="background: #91b64f;">Total</th>';
//
//				foreach($category as $rowkey => $row) {
//					$output .= '<tr>';
//					$output .= '<td>'.$rowkey.'</td>';
//					for($month = 1; $month <= 12; $month++) {
//						if(isset($row[$month])) {
//							$output .= '<td>'.$row[$month].'</td>';
//						}
//						else {
//							$output .= '<td>0</td>';
//						}
//					}
//					$output .= '<td>'.array_sum($category).'</td>';
//					$output .= '</tr>';
//				}
//				$output .= '</table>';
//			}
//		}
        $message .= '</body></html>';

        $message = '<html><head><title>Stock Report</title></head><body>';
        $message .= '<h3>Stock Summary Report - '.$affiliate['name'].' - Week '.$date_info['week'].' ( '.$affiliate['currency'].' | USD FX Rate:'.$fxrates['usd'].')</h3>';
        $message .= $stockevolution_output.$alerts.$summaries_ouput.$output.$fxratesoverview_output;

        $email_data = array();
        $email_data['to'][] = $affiliateobj->get_generalmanager()->get()['email'];
        $email_data['to'][] = $affiliateobj->get_supervisor()->get()['email'];

        if(isset($affiliates_addrecpt[$affid])) {
            foreach($affiliates_addrecpt[$affid] as $uid) {
                if(!is_numeric($uid)) {
                    $adduser = Users::get_user_byattr('username', $uid);
                }
                else {
                    $adduser = new Users($uid);
                }
                $email_data['to'][] = $adduser->get()['email'];
            }
        }

        $mailer = new Mailer();
        $mailer = $mailer->get_mailerobj();
        $mailer->set_required_contenttypes(array('html'));
        $mailer->set_from(array('name' => 'OCOS Mailer', 'email' => $core->settings['maileremail']));
        $mailer->set_subject('Stock Report - '.$affiliate['name'].' - Week '.$date_info['week']);
        $mailer->set_message($message);

        $mailer->add_attachment($stockevolution_chart->get_chart(), '', array('contentid' => 'stockevolutionchart'));
        $mailer->add_attachment($fxrates_linechart->get_chart(), '', array('contentid' => 'fxratesoverview'));
        $stockevolution_chart->delete_chartfile();
        $fxrates_linechart->delete_chartfile();
        unset($stockevolution_chart, $chart_data, $stockevolution_chart, $overyears_rates);
        $mailer->set_to($email_data['to']);

        //print_r($mailer->debug_info());
        $mailer->send();
        unset($message);
    }
}
?>
