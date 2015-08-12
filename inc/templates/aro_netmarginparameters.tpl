<a class="header " href="#"><h2>{$lang->netmarginparameters}</h2></a>
<div>
    <table>
        <thead>
            <tr style="font-weight:bold;">
                <td class="border_right" rowspan="2" colspan="3" valign="top" align="center">{$lang->riskandbankratios}</td>
                <td class="border_right" rowspan="2" colspan=3" valign="top" align="center">{$lang->warehousing}</td>
            </tr>
        </thead>
        <tbody id="parmsfornetmargin_{$plrowid}_tbody" style="width:100%;">
            <tr>
                <td><input type="hidden" name="parmsfornetmargin[anpid]" value="{$netmarginparms->anpid}"/></td>
                <td class="border_right" valign="top">{$lang->local} </td>
                <td class="border_right" valign="top">{$lang->intermed}</td>
            </tr>
            <tr>
                <td valign="top">{$lang->yearlybankinterestrate}</td>
                <td class="border_right" ><input type="number" min="0" name="parmsfornetmargin[localBankInterestRate]" id="parmsfornetmargin_localBankInterestRate" value="{$netmarginparms->localBankInterestRate}" style="width:150px;" readonly/></td>
                <td class="border_right" ><input type="number" min="0" name="parmsfornetmargin[intermedBankInterestRate]" id="parmsfornetmargin_intermedBankInterestRate" value="{$netmarginparms->intermedBankInterestRate}" style="width:150px;" readonly/></td>
                <td class="border_right" valign="top">{$lang->warehouse}</td>
                <td id="warehouse_list_td">{$warehouse_list}</td>
            </tr>
            <tr>
                <td valign="top">{$lang->periodofinterest}</td>
                <td class="border_right" ><input type="number" min="0" name="parmsfornetmargin[localPeriodOfInterest]" id="parmsfornetmargin_localPeriodOfInterest" value="{$netmarginparms->localPeriodOfInterest}" style="width:150px;" readonly/></td>
                <td class="border_right" ><input type="number" min="0" name="parmsfornetmargin[intermedPeriodOfInterest]" id="parmsfornetmargin_intermedPeriodOfInterest" value="{$netmarginparms->intermedPeriodOfInterest}" style="width:150px;" readonly/></td>
                <td class="border_right" valign="top">{$lang->rate}</td>
                <td>  <select name="parmsfornetmargin[warehousingRate]" id="parmsfornetmargin_warehousingRate" style="width:150px;" {$disabled[warehousing]}>{$netmarginparms_warehousingRate}</select><input type="hidden" id="parmsfornetmargin_warehousing_disabled" value="1"/></td>
                <td valign="top">{$lang->period}</td>
                <td> <input type="text" name="parmsfornetmargin[warehousingPeriod]" id="parmsfornetmargin_warehousingPeriod" value="{$netmarginparms->warehousingPeriod}" style="width:150px;" {$readonly[warehousing]}/>
                </td>
            </tr>
            <tr>
                <td valign="top">{$lang->riskratio}</td>
                <td class="border_right" ><input type="text" name="parmsfornetmargin[localRiskRatio]" id="parmsfornetmargin_localRiskRatio" value="{$netmarginparms->localRiskRatio}" style="width:150px;" readonly/></td>
                <td class="border_right" ></td>
                <td valign="top">{$lang->totalload}</td>
                <td><input type="text" name="parmsfornetmargin[warehousingTotalLoad]" id="parmsfornetmargin_warehousingTotalLoad" value="{$netmarginparms->warehousingTotalLoad}" style="width:150px;" {$readonly[warehousing]} required/></td>
                <td valign="top">{$lang->uom}</td>
                <td>{$netmarginparms_uomlist}</td>
            </tr>
            <tr>
                <td valign="top">{$lang->interestvalue}</td>
                <td></td>
                <td colspan="4"> <input type="text" name="parmsfornetmargin[interestValue]" id="parmsfornetmargin_interestvalue" value="{$netmarginparms->interestValue}" style="width:150px;" readonly/>
            </tr>

        </tbody>
        <tfoot>

        </tfoot>
    </table>
</div>
</a>