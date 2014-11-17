<?php
/*
 * Copyright © 2014 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: forecastbalancesheet.php
 * Created:        @tony.assaad    Oct 1, 2014 | 12:59:06 PM
 * Last Update:    @tony.assaad    Oct 1, 2014 | 12:59:06 PM
 */

if(!defined('DIRECT_ACCESS')) {
    die('Direct initialization of this file is not allowed.');
}
if($core->usergroup['budgeting_canFillFinBudgets'] == 0) {
    //error($lang->sectionnopermission);
}
if(isset($core->input['identifier']) && !empty($core->input['identifier'])) {
    $sessionidentifier = $core->input['identifier'];
}
else {
    $sessionidentifier = md5(uniqid(microtime()));
}

$session->name_phpsession(COOKIE_PREFIX.'budget_expenses_'.$sessionidentifier);
$session->start_phpsession(480);

if(!isset($core->input['action'])) {
    $session->set_phpsession(array('budget_expenses_'.$sessionidentifier => serialize($core->input['financialbudget'])));
    if(isset($core->input['financialbudget']['year']) && !empty($core->input['financialbudget']['year'])) {
        $financialbudget_year = $core->input['financialbudget']['year'];
        //  $financialbudget_prevyear = $financialbudget_year - 1;
        //$financialbudget_prev2year = $financialbudget_year - 2;
        $affid = $core->input['financialbudget']['affid'];
        $affiliate = new Affiliates($affid);
    }
    $financialbudget = FinancialBudget::get_data(array('affid' => $affid, 'year' => $financialbudget_year), array('simple' => false));

    $budforecastobj = new BudgetForecastAccountsTree();
    if(is_object($financialbudget) && $financialbudget->isFinalized()) {
        $type = 'hidden';
        $accountitems_output = $budforecastobj->parse_account(array('financialbudget' => $financialbudget, 'mode' => 'display'));
    }
    else {
        $accountitems_output = $budforecastobj->parse_account(array('financialbudget' => $financialbudget, 'mode' => 'fill'));
    }
    //$budgetaccounts = BudgetForecastAccountsTree::parse_accounts(array('type' => array('assets' => $assets_accounts, 'liabilities' => $liability_accounts)), array('mode' => 'fill'));
    /* get main currecny of the affiliate being budgeted */

    $affilaite_obj = new Affiliates($affid);
    $currency = $affilaite_obj->get_country()->get_maincurrency();
    if(!empty($currency->alphaCode)) {
        $tocurrency = '840'; //usd
        $currencyto_obj = new Currencies($tocurrency);
        $currency_to = $currencyto_obj->get()['alphaCode'];
        $dal_config = array(
                'operators' => array('fromCurrency' => '=', 'affid' => 'in', 'year' => '='),
                'simple' => false,
                'returnarray' => false
        );
        $fxrates_obj = BudgetFxRates::get_data(array('fromCurrency' => $currency->numCode, 'toCurrency' => $tocurrency, 'affid' => $affid, 'year' => $financialbudget_year,), $dal_config);
        $output_currency = '<div class="ui-state-highlight ui-corner-all" style="padding-left: 5px; padding: 5px; margin-top: 10px; margin-bottom: 10px; display: block;"><span><em>'.$lang->sprint($lang->budgcurrdesc, $currency->alphaCode).'</em></br></span>';
        if(is_object($fxrates_obj)) {
            $output_currency .='<em><strong>'.$lang->exchangerate.'</strong></em></br><span>'.$lang->sprint($lang->currrate, $currency->alphaCode, $currency_to, $fxrates_obj->rate).'</span>';
        }
        $output_currency .='</div>';
    }
    eval("\$budgeting_forecast_balancesheet = \"".$template->get('budgeting_forecast_balancesheet')."\";");
    output_page($budgeting_forecast_balancesheet);
}
else if($core->input['action'] == 'do_perform_forecastbalancesheet') {
    unset($core->input['identifier'], $core->input['module'], $core->input['action']);
    $financialbudget = new FinancialBudget();
    if(!empty($core->input['budgetforecastbs']['liabilities']['total']) && !empty($core->input['budgetforecastbs']['assets']['total'])) {
        $core->input['budgetforecastbs']['equityliabilities']['total'] = ($core->input['budgetforecastbs']['ownersequity']['total'] + $core->input['budgetforecastbs']['liabilities']['total']);
        if($core->input['budgetforecastbs']['equityliabilities']['total'] != $core->input['budgetforecastbs']['assets']['total']) {
            output_xml('<status>false</status><message>'.$lang->totalerror.'</message>');
            exit;
        }
    }
    unset($core->input['budgetforecastbs']['equityliabilities'], $core->input['budgetforecastbs']['assets'], $core->input['budgetforecastbs']['ownersequity'], $core->input['budgetforecastbs']['liabilities']);
    $financialbudget->set($core->input);

    $financialbudget->save();

    switch($financialbudget->get_errorcode()) {
        case 1:
            output_xml('<status>true</status><message>'.$lang->successfullysaved.'</message>');
            break;
        case 2:
            output_xml('<status>false</status><message>'.$lang->fillrequiredfields.'</message>');
            break;
        case 4:
            output_xml('<status>false</status><message>'.$lang->totalerror.'</message>');
            break;
    }
}