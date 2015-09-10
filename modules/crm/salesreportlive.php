<?php
/*
 * Copyright © 2014 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: salesreport_direct.php
 * Created:        @zaher.reda    Jun 27, 2014 | 11:59:43 AM
 * Last Update:    @zaher.reda    Jun 27, 2014 | 11:59:43 AM
 */


if(!defined('DIRECT_ACCESS')) {
    die('Direct initialization of this file is not allowed.');
}

if($core->usergroup['crm_canGenerateSalesReports'] == 0) {
    error($lang->sectionnopermission);
}
ini_set('max_execution_time', 100);
$lang->load('crm_salesreport');
if(!$core->input['action']) {
    $affiliates = Affiliates::get_affiliates(array('affid' => $core->user['affiliates']), array('returnarray' => true));
    $affiliates_list = parse_selectlist('affids[]', 2, $affiliates, '');

    $fxtypes_selectlist = parse_selectlist('fxtype', 9, array('lastm' => $lang->lastmonthrate, 'ylast' => $lang->yearlatestrate, 'yavg' => $lang->yearaveragerate, 'mavg' => $lang->monthaveragerate, 'real' => $lang->realrate), 'mavg', 0);

    $dimensions = array('suppliername' => $lang->supplier, 'customername' => $lang->customer, 'productname' => $lang->product, 'segment' => $lang->segment, 'salesrep' => $lang->employee/* ,  'wid' => $lang->warehouse */);
    foreach($dimensions as $dimensionid => $dimension) {
        $dimension_item.='<li class="ui-state-default" id='.$dimensionid.' title="Click and Hold to move the '.$dimension.'">'.$dimension.'</li>';
    }

    eval("\$generatepage = \"".$template->get('crm_generatesalesreport_live')."\";");
    output_page($generatepage);
}
else {
    if($core->input['action'] == 'do_perform_salesreportlive') {
        require_once ROOT.INC_ROOT.'integration_config.php';
        if(empty($core->input['affids'])) {
            output_xml('<status></status><message>No Affiliate selected</message>');
        }

        if(is_empty($core->input['fromDate'])) {
            output_xml('<status></status><message>Please specify the From date</message>');
        }

        /* In-line CSS styles in form of array in order to be compatible with email message */
        $css_styles['table-datacell'] = 'text-align: right;';
        $css_styles['altrow'] = 'background-color: #f7fafd;';
        $css_styles['altrow2'] = 'background-color: #F2FAED;';
        $css_styles['greenrow'] = 'background-color: #F2FAED;';

        $current_date = getdate(TIME_NOW);
        $period['from'] = strtotime($core->input['fromDate']);
        $period['to'] = TIME_NOW;
        if(!empty($core->input['toDate'])) {
            $period['to'] = strtotime($core->input['toDate']);
        }

        if(is_array($core->input['affids'])) {
            foreach($core->input['affids'] as $affid) {
                $affiliate = new Affiliates($affid, false);
                $orgs[] = $affiliate->integrationOBOrgId;
            }
        }
        else {
            $affiliate = new Affiliates($core->input['affids'], false);
            $orgs[] = $affiliate->integrationOBOrgId;
        }
        $currency_obj = new Currencies('USD');

        if(!empty($core->input['spid'])) {
            $orderline_query_where = ' AND ime.localId IN ('.implode(',', $core->input['spid']).')';
        }

        if(!empty($core->input['pid'])) {
            $orderline_query_where .= ' AND imp.localId IN ('.implode(',', $core->input['pid']).')';
        }

        if(!empty($core->input['cid'])) {
            $query_where .= ' AND ime.localId IN ('.implode(',', $core->input['cid']).')';
        }

        $filters = "c_invoice.ad_org_id IN ('".implode("','", $orgs)."') AND docstatus NOT IN ('VO', 'CL') AND (dateinvoiced BETWEEN '".date('Y-m-d 00:00:00', $period['from'])."' AND '".date('Y-m-d 00:00:00', $period['to'])."')";
        $integration = new IntegrationOB($intgconfig['openbravo']['database'], $intgconfig['openbravo']['entmodel']['client']);
        $invoices = $integration->get_saleinvoices($filters);
        $cols = array('month', 'week', 'documentno', 'salesrep', 'customername', 'suppliername', 'productname', 'segment', 'uom', 'qtyinvoiced', 'priceactual', 'linenetamt', 'purchaseprice', 'unitcostlocal', 'costlocal', 'costusd', 'grossmargin', 'grossmarginusd', 'netmargin', 'netmarginusd', 'marginperc');
        if(is_array($invoices)) {
            foreach($invoices as $invoice) {
                $orgcurrency = $invoice->get_organisation()->get_currency();
                $invoice->customername = $invoice->get_customer()->name;
                $invoicelines = $invoice->get_invoicelines();
                $invoice->salesrep = $invoice->get_salesrep()->name;
                if(empty($invoice->salesrep)) {
                    $invoice->salesrep = 'Unknown Sales Rep';
                }

                $invoice->dateinvoiceduts = strtotime($invoice->dateinvoiced);
                $invoice->week = 'Week '.date('W-Y', $invoice->dateinvoiceduts);
                $invoice->month = date('M, Y', $invoice->dateinvoiceduts);
                $invoice->currency = $invoice->get_currency()->iso_code;
                $invoice->usdfxrate = $core->input['fxrate'];
                if(empty($core->input['fxrate'])) {
                    $invoice->usdfxrate = $currency_obj->get_fxrate_bytype($core->input['fxtype'], $invoice->currency, array('from' => strtotime(date('Y-m-d', $invoice->dateinvoiceduts).' 01:00'), 'to' => strtotime(date('Y-m-d', $invoice->dateinvoiceduts).' 24:00'), 'year' => date('Y', $invoice->dateinvoiceduts), 'month' => date('m', $invoice->dateinvoiceduts)), array('precision' => 4));
                }

                if($orgcurrency->iso_code != $invoice->currency) {
                    $invoice->localfxrate = $currency_obj->get_fxrate_bytype($core->input['fxtype'], $invoice->currency, array('from' => strtotime(date('Y-m-d', $invoice->dateinvoiceduts).' 01:00'), 'to' => strtotime(date('Y-m-d', $invoice->dateinvoiceduts).' 24:00'), 'year' => date('Y', $invoice->dateinvoiceduts), 'month' => date('m', $invoice->dateinvoiceduts)), array('precision' => 4), $orgcurrency->iso_code);
                }
                if(empty($invoice->localfxrate)) {
                    $invoice->localfxrate = 1;
                }

                if(empty($invoice->usdfxrate)) {
                    $invoice->usdfxrate = 1;
                }
                if(!is_array($invoicelines)) {
                    continue;
                }
                foreach($invoicelines as $invoiceline) {
                    if($invoiceline->linenetamt == 0) {
                        continue;
                    }
                    $iltrx = $invoiceline->get_transaction();
                    if(is_object($iltrx)) {
                        $outputstack = $iltrx->get_outputstack();
                    }
                    if(is_object($outputstack)) {
                        $inputstack = $outputstack->get_inputstack();
                    }

                    $product = $invoiceline->get_product_local();
                    if(!isset($product->name)) {
                        $product = $invoiceline->get_product();
                        $invoiceline->segment = $product->get_category()->name;

                        if(is_object($inputstack)) {
                            $invoiceline->suppliername = $inputstack->get_supplier()->name;
                        }
                    }
                    else {
                        $invoiceline->suppliername = $product->get_supplier()->name;
                        $invoiceline->segment = $product->get_defaultchemfunction()->get_segment()->title;
                        if(empty($invoiceline->segment)) { /* Temp legacy fallback */
                            $invoiceline->segment = $product->get_segment()['title'];
                        }
                    }

                    if(empty($invoiceline->segment)) {
                        $invoiceline->segment = 'Unknown Segment';
                    }

                    $invoiceline->productname = $product->name;
                    if(empty($invoiceline->suppliername)) {
                        $invoiceline->suppliername = 'Unknown Supplier';
                    }

                    $invoiceline->uom = $invoiceline->get_uom()->uomsymbol;
                    $invoiceline->costlocal = $invoiceline->get_cost();
                    if($invoiceline->qtyinvoiced < 0) {
                        $invoiceline->costlocal = 0 - $invoiceline->costlocal;
                    }

                    if($invoiceline->qtyinvoiced != 0) {
                        $invoiceline->unitcostlocal = $invoiceline->costlocal / $invoiceline->qtyinvoiced;
                        $invoiceline->unitcostusd = $invoiceline->costusd / $invoiceline->qtyinvoiced;
                    }

                    $invoiceline->costusd = $invoiceline->costlocal / $invoice->usdfxrate;

                    if(is_object($inputstack)) {
                        $input_inoutline = $inputstack->get_transcation()->get_inoutline();
                        if(is_object($input_inoutline)) {
                            $ioinvoiceline = $input_inoutline->get_invoiceline();
                            if(is_object($ioinvoiceline)) {
                                $invoiceline->purchaseprice = $ioinvoiceline->priceactual;
                                $invoiceline->purchasecurr = $ioinvoiceline->get_invoice()->get_currency()->uomsymbol;
                                $invoiceline->purchasepriceusd = 0;
                            }
                            unset($ioinvoiceline);
                        }
                        else {
                            $invoiceline->purchaseprice = 0;
                        }
                    }


                    $invoiceline->linenetamt = $invoiceline->linenetamt / 1000;
                    /* Convert to local currency if invoice is in foreign currency */
                    if($orgcurrency->iso_code != $invoice->currency) {
                        $invoiceline->priceactual /= $invoice->localfxrate;
                        $invoiceline->linenetamt /= $invoice->localfxrate;
                    }
                    $invoiceline->costlocal = $invoiceline->costlocal / 1000;
                    $invoiceline->grossmargin = $invoiceline->linenetamt - (($invoiceline->purchaseprice * $invoiceline->qtyinvoiced) / 1000);
                    $invoiceline->grossmarginusd = $invoiceline->grossmargin / $invoice->usdfxrate;
                    $invoiceline->netmargin = $invoiceline->linenetamt - $invoiceline->costlocal;
                    $invoiceline->netmarginusd = $invoiceline->netmargin / $invoice->usdfxrate;
                    $invoiceline->marginperc = $invoiceline->netmargin / $invoiceline->linenetamt;

                    $output .= '<tr>';
                    foreach($cols as $col) {
                        $value = $invoice->{$col};
                        if(empty($value)) {
                            $value = $invoiceline->{$col};
                        }

                        $data[$invoiceline->c_invoiceline_id][$col] = $value;
                    }

                    if($invoiceline->marginperc < 0 || $invoiceline->marginperc > 0.5) {
                        $outliers[$invoiceline->c_invoiceline_id] = $data[$invoiceline->c_invoiceline_id];
                    }
                }
            }
        }
        else {
            //  redirect($url, $delay, $redirect_message);
        }

        $salesreport = '<h1>'.$lang->salesreport.'<small><br />'.$lang->{$core->input['type']}.'<br />Values are in Thousands <small>(Local Currency)</small></small></h1>';
        $salesreport .= '<p><em>The report might have issues in the cost information. If so please report them to the ERP Team.</em></p>';
        if($core->input['type'] == 'analytic' || $core->input['type'] == 'dimensional') {
            $overwrite = array('marginperc' => array('fields' => array('divider' => 'netmargin', 'dividedby' => 'linenetamt'), 'operation' => '/'),
                    'priceactual' => array('fields' => array('divider' => 'linenetamt', 'dividedby' => 'qtyinvoiced'), 'operation' => '/'));

            $formats = array('marginperc' => array('style' => NumberFormatter::PERCENT_SYMBOL));
            $required_fields = array('qtyinvoiced', 'priceactual', 'linenetamt', 'purchaseprice', 'costlocal', 'grossmargin', 'netmargin', 'marginperc');

            if($core->input['type'] == 'analytic') {
                $current_year = date('Y', TIME_NOW);
                $required_tables = array('segmentsummary' => array('segment'), 'salesrepsummary' => array('salesrep'), 'suppliersummary' => array('suppliername'), 'customerssummary' => array('customername'));

                $yearsummary_filter = "EXISTS (SELECT c_invoice_id FROM c_invoice WHERE c_invoice.c_invoice_id=c_invoiceline.c_invoice_id AND issotrx='Y'AND ad_org_id IN ('".implode("','", $orgs)."') AND docstatus NOT IN ('VO', 'CL') AND (dateinvoiced BETWEEN '".date('Y-m-d 00:00:00', strtotime((date('Y', TIME_NOW) - 2).'-01-01'))."' AND '".date('Y-m-d 00:00:00', $period['to'])."'))";
                //$monthdata = $integration->get_sales_byyearmonth($yearsummary_filter);
                $intgdb = $integration->get_dbconn();
                $invoicelines = new IntegrationOBInvoiceLine(null);
                $mdata = $invoicelines->get_data_byyearmonth($yearsummary_filter, array('reportcurrency' => 'USD'));

                if(isset($core->input['generatecharts']) && $core->input['generatecharts'] == 1) {
                    $classifications = $invoicelines->get_classification($mdata, $period);
                }

                $monthdata = $mdata['salerep'];
                if(is_array($monthdata)) {
                    $formatter = new NumberFormatter('EN_en', NumberFormatter::DECIMAL, '#.##');
                    $percformatter = new NumberFormatter('EN_en', NumberFormatter::PERCENT);
                    $salesreport .= '<h2>Monthly Overview by BM</h2>';
                    $salesreport .= '<table width="100%" class="datatable">';
                    $salesreport .= '<tr><th style="font-size:14px; font-weight: bold; background-color: #F1F1F1;">Sales Rep</th>';
                    for($i = 1; $i <= 12; $i++) {
                        $salesreport .= '<th style="font-size:14px; font-weight: bold; background-color: #F1F1F1;">'.DateTime::createFromFormat('m', $i)->format('M').'</th>';
                    }
                    for($y = $current_year; $y >= ($current_year - 1); $y--) {
                        $salesreport .= '<th style="font-size:14px; font-weight: bold; background-color: #F1F1F1;">'.$y.'</th>';
                    }
                    $salesreport .= '</tr>';
                    foreach($monthdata['linenetamt'] as $salerepid => $salerepdata) {
                        $currentyeardata = $salerepdata[$current_year];

                        $salesreport .= '<tr style="'.$rowstyle.'">';
                        $salesrep = new IntegrationOBUser($salerepid, $integration->get_dbconn());
                        if(empty($salesrep->name)) {
                            $salesrep->name = 'Not Specified';
                        }
                        $salesreport .= '<td style="'.$css_styles['table-datacell'].'">'.$salesrep->name.'</td>';

                        for($i = 1; $i <= 12; $i++) {
                            if(!isset($currentyeardata[$i])) {
                                $currentyeardata[$i] = 0;
                            }
                            $salesreport .= '<td style="'.$css_styles['table-datacell'].'">'.$formatter->format($currentyeardata[$i] / 1000).'</td>';
                        }
                        for($y = $current_year; $y >= ($current_year - 1); $y--) {
                            if(!is_array($salerepdata[$y])) {
                                $salerepdata[$y][] = 0;
                            }
                            for($m = 1; $m <= 12; $m++) {
                                $salerepdata[$y][$m] = $salerepdata[$y][$m] / 1000;
                                $yearsummarytotals[$y][$m] += $salerepdata[$y][$m];
                            }
                            $salesreport .= '<td style="'.$css_styles['table-datacell'].'">'.$formatter->format(array_sum($salerepdata[$y])).'</td>';
                        }
                        $salesreport .= '</tr>';
                        if(empty($rowstyle)) {
                            $rowstyle = $css_styles['altrow'];
                        }
                        else {
                            $rowstyle = '';
                        }
                    }

                    if(is_array($classifications) && (isset($core->input['generatecharts']) && $core->input['generatecharts'] == 1)) {
                        $classifications_output .=$invoicelines->parse_classificaton_tables($classifications);
                    }
//                    $invoicelinesdata = new IntegrationOBInvoiceLine(null, $integration->get_dbconn());
//                    $yearsumrawtotals = $invoicelinesdata->get_aggreateddata_byyearmonth(null, $yearsummary_filter." AND c_invoice.issotrx='Y'");
//                    foreach($yearsumrawtotals as $totaldata) {
//                        $yearsummarytotals[$totaldata['year']][$totaldata['month']] = $totaldata['qty'];
//                    }
                    for($y = $current_year; $y >= ($current_year - 1); $y--) {
                        $salesreport .= '<tr style="'.$css_styles['altrow2'].'"><th>Totals ('.$y.')</th>';
                        for($i = 1; $i <= 12; $i++) {
                            $salesreport .= '<th style="text-align: right;">'.$formatter->format($yearsummarytotals[$y][$i]).'</th>';
                        }

                        for($yy = $current_year; $yy >= ($current_year - 1); $yy--) {
                            if($yy != $y) {
                                $salesreport .= '<th style="text-align: center;">-</th>';
                                continue;
                            }
                            if(!is_array($yearsummarytotals[$yy])) {
                                $yearsummarytotals[$yy][] = 0;
                            }
                            $salesreport .= '<th style="text-align: right;">'.$formatter->format(array_sum($yearsummarytotals[$yy])).'</th>';
                        }
                        $salesreport .= '</tr>';
                    }
                    $salesreport .= $classifications_output.'</table>';
                    unset($yearsumrawtotals, $yearsummarytotals, $currentyeardata);

                    /* YTD Comparison */
                    $salesreport .= '<h2>Progression by BM</h2>';
                    $salesreport .= '<table width="100%" class="datatable" style="color:black;">';
                    $salesreport .= '<tr><th style="font-size:14px; font-weight: bold; background-color: #F1F1F1;">Sales Rep</th>';
                    $salesreport .= '<th style="font-size:14px; font-weight: bold; background-color: #F1F1F1; text-align: center;">YTD</th>';
                    $salesreport .= '<th style="font-size:14px; font-weight: bold; background-color: #F1F1F1; text-align: center;">YTD / '.($current_year - 1).'</th>';
                    $salesreport .= '<th style="font-size:14px; font-weight: bold; background-color: #F1F1F1; text-align: center;">'.$current_year.' objective</th>';
                    $salesreport .= '<th style="font-size:14px; font-weight: bold; background-color: #F1F1F1; text-align: center;">YTD / '.$current_year.' objective</th>';
                    $salesreport .= '</tr>';
                    foreach($monthdata['linenetamt'] as $salerepid => $salerepdata) {
                        for($y = $current_year; $y >= ($current_year - 1); $y--) {
                            if(!is_array($salerepdata[$y])) {
                                $salerepdata[$y][] = 0;
                            }

                            foreach($salerepdata[$y] as $key => $val) {
                                if(!empty($val)) {
                                    $salerepdata[$y][$key] = $val / 1000;
                                }
                            }
                        }

                        $salesrep = new IntegrationOBUser($salerepid, $integration->get_dbconn());
                        if(empty($salesrep->name)) {
                            continue;
                        }
                        $salerep_user = Users::get_data_byattr('displayName', $salesrep->name);
                        $salesreport .= '<tr style="'.$rowstyle.'">';
                        $salesreport .= '<td>'.$salesrep->name.'</td>';
                        $salesreport .= '<td style="text-align: right;">'.$formatter->format(array_sum($salerepdata[$current_year])).'</td>';

                        $percentages['prevyear']['linenetamt'] = 0.10;
                        if(array_sum($salerepdata[$current_year - 1]) != 0) {
                            $percentages['prevyear']['linenetamt'] = (array_sum($salerepdata[$current_year]) / array_sum($salerepdata[$current_year - 1]));
                        }
                        $salesreport .= '<td style="text-align: right;">'.$percformatter->format($percentages['prevyear']['linenetamt']).'</td>';

                        /* Get budget */
                        if(is_object($salerep_user)) {
                            $budgetlines = BudgetLines::get_data(array('businessMgr' => $salerep_user->uid, 'bid' => '(SELECT bid FROM budgeting_budgets WHERE year='.$current_year.' AND affid IN ('.implode(',', $core->input['affids']).'))'), array('returnarray' => true, 'operators' => array('bid' => 'IN')));
                            $percentages['budget']['amt'] = 0.10;
                            if(is_array($budgetlines)) {
                                foreach($budgetlines as $budgetline) {
                                    $budget_totals['qty'] += $budgetline->quantity;
                                    $budget_totals['amt'] += $budgetline->get_convertedamount($currency_obj) / 1000;
                                }
                                if(!empty($budget_totals['amt'])) {
                                    $percentages['budget']['amt'] = (array_sum($salerepdata[$current_year]) / $budget_totals['amt']);
                                }
                            }

                            $salesreport .= '<td style="text-align: right;">'.$formatter->format($budget_totals['amt']).'</td>';
                            $salesreport .= '<td style="text-align: right;">'.$percformatter->format($percentages['budget']['amt']).'</td>';
                        }
                        else {
                            $salesreport .= '<td style="text-align: right;">-</td>';
                            $salesreport .= '<td style="text-align: right;">-</td>';
                        }
                        $salesreport .= '</tr>';
                        if(empty($rowstyle)) {
                            $rowstyle = $css_styles['altrow'];
                        }
                        else {
                            $rowstyle = '';
                        }
                        unset($budget_totals, $percentages);
                    }

                    $salesreport .= '</table>';
                    /* YTD Comparison - END */

                    unset($monthdata);
                }
            }
            elseif($core->input['type'] == 'dimensional') {
                $required_tables = array('detailed' => explode(',', $core->input['salereport']['dimension'][0]));
            }


            foreach($required_tables as $tabledesc => $dimensions) {
                $rawdata = $data;
                $dimensionalreport = new DimentionalData();
                $dimensionalreport->set_dimensions(array_combine(range(1, count($dimensions)), array_values($dimensions)));
                $dimensionalreport->set_requiredfields($required_fields);
                $dimensionalreport->set_data($rawdata);
                $salesreport .= '<h2><br />'.$lang->{$tabledesc}.'</h2>';
                $salesreport .= '<table width="100%" class="datatable" style="color:black;">';
                $salesreport .= '<tr><th></th>';
                foreach($required_fields as $field) {
                    if(!isset($lang->{$field})) {
                        $lang->{$field} = $field;
                    }
                    $salesreport .= '<th>'.$lang->{$field}.'</th>';
                }
                $salesreport .= '</tr>';
                $salesreport .= $dimensionalreport->get_output(array('outputtype' => 'table', 'noenclosingtags' => true, 'formats' => $formats, 'overwritecalculation' => $overwrite));
                $salesreport .= '</table>';

                $chart_data = $dimensionalreport->get_data();
                //$chart = new Charts(array('x' => array($previous_year => $previous_year, $current_year => $current_year), 'y' => $barchart_quantities_values), 'bar');
            }
        }
        else {
            $required_details = array('outliers', 'data');
            foreach($required_details as $array) {
                if(!is_array(${$array})) {
                    continue;
                }
                $salesreport .= '<h3>'.ucwords($array).'</h3><table class="datatable">';
                $salesreport .= '<thead><tr class="thead">';
                $tablefilters = '';
                foreach($cols as $col) {
                    if(!isset($lang->{$col})) {
                        $lang->{$col} = ucwords($col);
                    }
                    $salesreport .= '<th>'.$lang->{$col}.'</th>';
                    $tablefilters .= '<th><input class="inlinefilterfield" type="text" style="width: 95%;"/></th>';
                }
                $salesreport .= '</tr>';

                if($core->input['reporttype'] != 'email') {
                    $salesreport .= '<tr>'.$tablefilters.'</tr>';
                }
                $salesreport .= '</thead>';
                unset($tablefilters);
                if(is_array(${$array})) {
                    foreach(${$array} as $iol => $row) {
                        $salesreport .= '<tr>';
                        foreach($cols as $col) {
                            $value = $row[$col];
                            if(strstr($col, 'perc')) {
                                $value = numfmt_format(numfmt_create('en_EN', NumberFormatter::PERCENT), $value);
                            }
                            elseif(is_numeric($value) && $col != 'documentno') {
                                $value = numfmt_format(numfmt_create('en_EN', NumberFormatter::DECIMAL), $value);
                            }
                            $salesreport .= '<td>'.$value.'</td>';
                        }

                        $salesreport .= '</tr>';
                    }
                }
                $salesreport .= '</table><br />';
            }
        }

        if($core->input['reporttype'] == 'email') {
            if(count($core->input['affids']) > 1) {
                error('Cannot send when report contain multiple affiliates');
            }
            $mailer = new Mailer();
            $mailer = $mailer->get_mailerobj();
            $mailer->set_required_contenttypes(array('html'));
            $mailer->set_from(array('name' => 'OCOS Mailer', 'email' => $core->settings['maileremail']));
            $mailer->set_subject('Sales Report '.$affiliate->name.' '.$core->input['fromDate'].' - '.$core->input['toDate']);
            $mailer->set_message($salesreport);
            $recipients = array(
                    $affiliate->get_generalmanager()->email,
                    $affiliate->get_supervisor()->email,
                    $affiliate->get_financialemanager()->email,
                    $core->user_obj->email,
                    Users::get_data(array('uid' => 3))->email/* Always include User 3 */
            );
            $recipients = array_unique($recipients);
            $mailer->set_to($recipients);

            //$mailer->set_to('zaher.reda@orkila.com');
            // print_r($mailer->debug_info());
            // exit;
            $mailer->send();
            if($mailer->get_status() === true) {
                $sentreport = new ReportsSendLog();
                $sentreport->set(array('affid' => $affiliate->get_id(), 'report' => 'salesreport', 'date' => TIME_NOW, 'sentBy' => $core->user['uid'], 'sentTo' => ''))->save();
                unset($core->input['reporttype']);
                redirect('index.php?'.http_build_query($core->input), 1, 'Success');
            }
            else {
                error($lang->errorsendingemail);
            }
            unset($salesreport);
        }
        else {
            if(!is_array($core->input['affids']) || count($core->input['affids']) == 1) {
                $recipients = array(
                        $affiliate->get_generalmanager()->displayName,
                        $affiliate->get_supervisor()->displayName,
                        $affiliate->get_financialemanager()->displayName,
                        $core->user_obj->displayName,
                        Users::get_data(array('uid' => 3))->get_displayname()/* Always include User 3 */);
                $recipients = array_unique($recipients);
                if(is_array($recipients)) {
                    $recipients = array_filter($recipients);
                    $salesreport .= '<hr /><div class="ui-state-highlight ui-corner-all" style="padding-left: 5px; margin-bottom:10px;"><p>This report will be sent to <ul><li>'.implode('</li><li>', $recipients).'</li></ul></p></div>';
                    $salesreport .= '<a href="index.php?reporttype=email&amp;'.http_build_query($core->input).'"><button class="button">Send by email</button></a>';
                }
            }
            eval("\$previewpage = \"".$template->get('crm_previewsalesreport')."\";");
            output_xml('<status>true</status><message><![CDATA['.$previewpage.']]></message>');
        }
    }
}
?>