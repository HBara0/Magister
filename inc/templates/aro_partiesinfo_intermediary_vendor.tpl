<table>
    <tbody  width="100%">
        <tr>
            <td style="vertical-align:top;">
                <table>
                    <tr><td></td>
                        <td class="subtitle">{$lang->intermediary}</td>
                        <td class="subtitle">{$lang->vendor}</td>
                    </tr>
                    <tr class="altrow2"><td>{$lang->partiesinvolved}</td>
                        <td>{$affiliates_list['intermed']}</td>
                        <td><input id="supplier_1_autocomplete" autocomplete="off" type="text" value="{$vendor_displayname}" style="width:150px;" {$is_disabled}>
                            <input id="supplier_1_id" name="partiesinfo[vendorEid]"  value="{$aropartiesinfo_obj->vendorEid}" type="hidden">
                            <div id="searchQuickResults_1" class="searchQuickResults" style="display: none;"></div>
                        </td>
                        <td>{$lang->isaff}<input type="checkbox" name="partiesinfo[vendorIsAff]" id="vendor_isaffiliate" value="1" {$checked}></td>
                        <td id="vendor_affiliate" {$display}>{$affiliates_list['vendor']}</td>
                    </tr>
                    <tr><td>{$lang->incoterms}</td>
                        <td>{$incoterms_list['intermed']}</td>
                        <td>{$incoterms_list['vendor']}</td>

                    </tr>
                    <tr class="altrow"><td>{$lang->incotermsdesc}</td>
                        <td><input type="text"  name="partiesinfo[intermedIncotermsDesc]" id="partiesinfo_intermed_IncotermsDesc" value="{$aropartiesinfo_obj->intermedIncotermsDesc}" placeholder="" required='required' style="width:150px;" {$is_disabled}/></td>
                        <td><input type="text"  name="partiesinfo[vendorIncotermsDesc]" id="partiesinfo_vendor_IncotermsDesc" value="{$aropartiesinfo_obj->vendorIncotermsDesc}" placeholder="" required='required' style="width:150px;"/></td>

                    </tr>
                    <tr><td>{$lang->paymentterms}</td>
                        <td>{$paymentterms_list['intermed']}</td>
                        <td>{$paymentterms_list['vendor']}</td>
                    </tr>
                    <tr class="altrow2"><td>{$lang->paymenttermsdesc}</td>
                        <td><input type="text"  name="partiesinfo[intermedPaymentTermDesc]" id="partiesinfo_intermed_PaymentTermDesc" value="{$aropartiesinfo_obj->intermedPaymentTermDesc}" placeholder="" required='required' style="width:150px;" {$is_disabled}/></td>
                        <td><input type="text"  name="partiesinfo[vendorPaymentTermDesc]" id="partiesinfo_vendor_PaymentTermDesc" value="{$aropartiesinfo_obj->vendorPaymentTermDesc}" required='required' style="width:150px;" placeholder="Ex: days from B/L"/></td>
                    </tr>
                    <tr><td>{$lang->commission} <small>{$lang->commisionlimit}</small></td>
                        <td><input type="number" step="any" name="partiesinfo[commission]" id="partiesinfo_commission" value="{$aropartiesinfo_obj->commission}" /></td>
                    </tr>
                    <tr class="altrow2"><td>{$lang->totaldiscount}</td>
                        <td><input type="number" step="any" name="partiesinfo[totalDiscount]" id="partiesinfo_totaldiscount" value="{$aropartiesinfo_obj->totalDiscount}"/></td>
                    </tr>
                </table>
            </td>
        </tr>
    </tbody>
</table>
