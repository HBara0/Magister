<?php
/*
 * Copyright © 2015 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: managearodouments.php
 * Created:        @tony.assaad    Feb 11, 2015 | 11:53:19 AM
 * Last Update:    @tony.assaad    Feb 11, 2015 | 11:53:19 AM
 */

if(!defined('DIRECT_ACCESS')) {
    die('Direct initialization of this file is not allowed.');
}
if($core->usergroup['aro_canUseAro'] == 0) {
    //error($lang->sectionnopermission);
    // exit;
}

if(!($core->input['action'])) {
    /* Order idendtifications */
    if($core->usergroup['canViewAllAff'] == 0) {
        $inaffiliates = $core->user['affiliates'];
    }
    foreach($inaffiliates as $affid) {
        $affiliate[$affid] = new Affiliates($affid);
    }

    $affiliate_list = parse_selectlist('orderid[affid]', 1, $affiliate, $orderid[affid], '', '', array('blankstart' => true, 'id' => "affid"));
    $purchasetypes = PurchaseTypes::get_data('name IS NOT NULL', array('returnarray' => true));

    $purchasetypelist = parse_selectlist('orderid[orderType]', 4, $purchasetypes, $orderid['ptid'], '', '', array('blankstart' => true, 'id' => "purchasetype"));

    $mainaffobj = new Affiliates($core->user['mainaffiliate']);

    $currencies = Currencies::get_data();
    $checksum = generate_checksum('odercustomer');
    $rowid = 1;
    $currencies_list = parse_selectlist('orderid[currency]', 4, $currencies, '', '', '', array('blankstart' => 1, 'id' => "currencies"));
    $inspections = array('inspection1' => 'inspection');
    $inspectionlist = parse_selectlist('orderid[inspectionType]', 4, $inspections, '');
    $payment_terms = PaymentTerms::get_data('', array('returnarray' => ture));

    $payment_term = parse_selectlist('customeroder[corder]['.$rowid.']['.$checksum.'][ptid]', 4, $payment_terms, '', '', '', array('blankstart' => 1, 'id' => "paymentermdays_".$rowid));
    $altpayment_term = parse_selectlist('customeroder[altcorder][ptid]', 4, $payment_terms, '', '', '', array('blankstart' => 1, 'id' => "paymentermdays_".$rowid));

    eval("\$aro_managedocuments_orderident= \"".$template->get('aro_managedocuments_orderidentification')."\";");

    eval("\$aro_managedocuments_ordercustomers_rows = \"".$template->get('aro_managedocuments_ordercustomers_rows')."\";");

    eval("\$aro_ordercustomers= \"".$template->get('aro_managedocuments_ordercustomers')."\";");

    //********** ARO Product Lines **************//
    $segments = ProductsSegments::get_segments('');
    $packaging = Packaging::get_data('name IS NOT NULL');
    $uom = Uom::get_data('name IS NOT NULL');
    if(isset($core->input['id'])) {
        $aroorderrequest = AroOrderRequest::get_data(array('aorid' => $core->input['id']));
        if(is_object($aroorderrequest)) {
            $plrowid = 1;
            $productlines = AroRequestLines::get_data(array('aorid' => $aroorderrequest->aorid), array('returnarray' => true));
            if(is_array($productlines)) {
                foreach($productlines as $line) {
                    $productline = $line->get();
                    $segments_selectlist = parse_selectlist('productline['.$plrowid.'][psid]', '', $segments, $productline['psid'], null, null, array('id' => "productline_".$plrowid."_psid", 'placeholder' => 'Overwrite Segment', 'width' => '100%'));
                    $packaging_list = parse_selectlist('productline['.$plrowid.'][packing]', '', $packaging, $productline['packing'], '', '', array('id' => "productline_".$plrowid."packing", 'blankstart' => 1));
                    $uom_list = parse_selectlist('productline['.$plrowid.'][uom]', '', $uom, $productline['uom'], '', '', array('id' => "productline_".$plrowid."_uom", 'blankstart' => 1, 'width' => '70px'));
                    $product = new Products($productline['pid']);
                    $productline[productName] = $product->get_displayname();
//                    $purchasetype = new PurchaseTypes(array('ptid' => $aroorderrequest->orderType));
//                    if($purchasetype->qtyIsNotStored == 1) {
//                        $disabled_fields['daysInStock'] = $disabled_fields['qtyPotentiallySold'] = 'disabled="disabled"';
//                    }
//                    if($productline['daysInStock'] == 0) {
//                        $disabled_fields['qtyPotentiallySold'] = 'disabled="disabled"';
//                    }
                    eval("\$aroproductlines_rows .= \"".$template->get('aro_productlines_row')."\";");
                    $plrowid++;
                }
            }
            else {
                $productline['inputChecksum'] = generate_checksum('pl');
                $segments_selectlist = parse_selectlist('productline['.$plrowid.'][psid]', '', $segments, '', null, null, array('id' => "productline_".$plrowid."_psid", 'placeholder' => 'Overwrite Segment', 'width' => '100%'));
                $packaging_list = parse_selectlist('productline['.$plrowid.'][packing]', '', $packaging, '', '', '', array('id' => "productline_".$plrowid."packing", 'blankstart' => 1));
                $uom_list = parse_selectlist('productline['.$plrowid.'][uom]', '', $uom, '', '', '', array('id' => "productline_".$plrowid."_uom", 'blankstart' => 1, 'width' => '70px'));
                eval("\$aroproductlines_rows .= \"".$template->get('aro_productlines_row')."\";");
            }
        }
    }
    else {
        $plrowid = 1;
        $productline['inputChecksum'] = generate_checksum('pl');
        $segments_selectlist = parse_selectlist('productline['.$plrowid.'][psid]', '', $segments, '', null, null, array('id' => "productline_".$plrowid."_psid", 'placeholder' => 'Overwrite Segment', 'width' => '100%'));
        $packaging_list = parse_selectlist('productline['.$plrowid.'][packing]', '', $packaging, '', '', '', array('id' => "productline_".$plrowid."packing", 'blankstart' => 1));
        $uom_list = parse_selectlist('productline['.$plrowid.'][uom]', '', $uom, '', '', '', array('id' => "productline_".$plrowid."_uom", 'blankstart' => 1, 'width' => '70px'));
        eval("\$aroproductlines_rows .= \"".$template->get('aro_productlines_row')."\";");
    }
    eval("\$aro_productlines = \"".$template->get('aro_fillproductlines')."\";");
    //********** ARO Product Lines **************//
    eval("\$aro_managedocuments= \"".$template->get('aro_managedocuments')."\";");
    output_page($aro_managedocuments);
}
else {
    if($core->input['action'] == 'getexchangerate') {
        $currencyobj = new Currencies($core->input['currency']);
        $rateusd = $currencyobj->get_latest_fxrate($currencyobj->get()['alphaCode'], null, 'USD');
        $exchangerate = array('exchangeRateToUSD' => $rateusd);

        echo json_encode($exchangerate);
    }
    if($core->input ['action'] == 'populatedocnum') {
        $filter['filter']['time'] = '('.TIME_NOW.' BETWEEN effectiveFrom AND effectiveTo)';

        $documentseq_obj = AroDocumentsSequenceConf::get_data(array('time' => $filter['filter']['time'], 'affid' => $core->input['affid'], 'ptid' => $core->input['ptid']), array('simple' => false, 'operators' => array('affid' => 'in', 'ptid' => 'in', 'time' => 'CUSTOMSQLSECURE')));
        if(is_object($documentseq_obj)) {
            /* create the array to be encoded each dimension of the array represent the html element in the form */
            $orderreference = array('cpurchasetype' => $core->input['ptid'], 'orderreference' => $documentseq_obj->prefix.'-'.$documentseq_obj->nextNumber.'-'.date('y', $documentseq_obj->effectiveFrom).'-'.$documentseq_obj->suffix);
            echo json_encode($orderreference); //return json to the ajax request to populate in the form
        }
        else {
            echo json_encode('error');
            exit;
        }
    }
    if($core->input['action'] == 'ajaxaddmore_newcustomer') {
        $rowid = intval($core->input['value']) + 1;
        $checksum = generate_checksum('odercustomer');
        $payment_terms = PaymentTerms::get_data('', array('returnarray' => ture));
        $payment_term = parse_selectlist('customeroder[corder]['.$rowid.']['.$checksum.'][ptid]', 4, $payment_terms, '', '', '', array('blankstart' => 1, 'id' => "paymentermdays_".$rowid));
        eval("\$aro_managedocuments_ordercustomers_rows = \"".$template->get('aro_managedocuments_ordercustomers_rows')."\";");
        output($aro_managedocuments_ordercustomers_rows);
    }
    if($core->input['action'] == 'do_perform_managearodouments') {
        if(isset($core->input['orderid']) && !empty($core->input['orderid']['affid'])) {
            $orderident_obj = new AroOrderRequest ();
            /* get arodocument of the affid and pruchase type */
            $documentseq_obj = AroDocumentsSequenceConf::get_data(array('affid' => $core->input['orderid']['affid'], 'ptid' => $core->input['orderid']['orderType']), array('simple' => false, 'operators' => array('affid' => 'in', 'ptid' => 'in')));
            // $nextsequence_number = $documentseq_obj->get_nextaro_identification();
            //  print_R($nextsequence_number);
            //  exit;

            $orderident_obj->set($core->input);
            $orderident_obj->save();
        }

        foreach($core->input['customeroder']['corder'] as $cusomeroder) {
            foreach($cusomeroder as $order) {
                if(isset($order['cid']) && !empty($order['cid'])) {
                    $ordercust_obj = new AroOrderCustomers();
                    $ordercust_obj->set($order);
                    $ordercust_obj->save();
                }
            }
        }
        if(isset($core->input[customeroder]['altcorder'][altcid])) {
            $ordercust_obj = new AroOrderCustomers();
            $ordercust_obj->set($core->input[customeroder]['altcorder']);
            $ordercust_obj->save();
        }
    }
    if($core->input['action'] == 'getestimatedate') {
        $purchasetype = new PurchaseTypes($core->input['ptid']);
        if($purchasetype->isPurchasedByEndUser != 0) {
            echo json_encode('error');
            exit;
        }

//        $datarray[] = Array(timeAdded => $core->input['avgesdateofsale'],
//                timeRead => $core->input['patmertermdays']
//        );
        // averge of paymnetterdays and them to the  timestamp avgesdateofsale
        $paymentermobj = new PaymentTerms($core->input['paymentermdays']);
        $intervalspayment_terms[] = $paymentermobj->overduePaymentDays; //get days

        $avgesdateofsale = strtotime($core->input['avgesdateofsale']);  // arraysum later
        ///   $avgpaymentterms = array_sum($intervalspayment_terms / count($intervalspayment_terms[0]));
        $est_averagedate = $avgpaymentterms + $avgesdateofsale;
        //    $averagedate = array('avgeliduedate' => $est_averagedate);
        echo json_encode(array('avgeliduedate' => $est_averagedate)); //return json to the ajax request to populate in the form
    }
    if($core->input['action'] == 'ajaxaddmore_productline') {
        $plrowid = intval($core->input['value']) + 1;
        $display = 'none';
        $productlines_data = $core->input['ajaxaddmoredata'];
        $productline['inputChecksum'] = generate_checksum('pl');
        $segments = ProductsSegments::get_segments('');
        $segments_selectlist = parse_selectlist('productline['.$plrowid.'][psid]', '', $segments, '', null, null, array('id' => "productline_".$plrowid."_psid", 'placeholder' => 'Overwrite Segment', 'width' => '100%'));
        $packaging = Packaging::get_data('name IS NOT NULL');
        $packaging_list = parse_selectlist('productline['.$plrowid.'][packing]', '', $packaging, '', '', '', array('id' => "productline_".$plrowid."packing", 'blankstart' => 1));
        $uom = Uom::get_data('name IS NOT NULL');
        $uom_list = parse_selectlist('productline['.$plrowid.'][uom]', '', $uom, '', '', '', array('id' => "productline_".$plrowid."_uom", 'blankstart' => 1));
        eval("\$aroproductlines_rows = \"".$template->get('aro_productlines_row')."\";");
        output($aroproductlines_rows);
    }

    if($core->input ['action'] == 'populateproductlinefields') {
        $productline_obj = new AroRequestLines();
        $rowid = $core->input['rowid'];
        unset($core->input['action'], $core->input['module'], $core->input['rowid']);
        $data = $core->input;
        $productline_data = $productline_obj->calculate_values($data);
        foreach($productline_data as $key => $value) {
            if(!empty($value)) {
                $productline['productline_'.$rowid.'_'.$key] = $value;
            }
        }

        //$purchasetype = new PurchaseTypes(array('ptid' => $core->input['ptid']));
        //if($purchasetype->qtyIsNotStored == 1) {
        //$disabled_fields['daysInStock'] = $disabled_fields['qtyPotentiallySold'] = 'disabled="disabled"';
        // }
        //if($productline['daysInStock'] == 0) {
        // $disabled_fields['qtyPotentiallySold'] = 'disabled="disabled"';

        echo json_encode($productline);
    }
}