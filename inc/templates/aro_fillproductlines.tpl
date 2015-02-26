<a class="header " href="#"><h2>{$lang->fillproductlines}</h2></a>
<div>
    <p>
    <table width="100%">
        <thead>
            <tr style="vertical-align: top;">
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->product}</td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->packaging}</td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->quantity}</td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->uom}</td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->daysinstock}
                    <input type="hidden" id="productline_daysInStock_disabled" value="1"/></td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->qtypotentiallysold}
                    <input type="hidden" id="productline_qtyPotentiallySold_disabled" value="1"/></td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->qtypotentiallysold}(%)</td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->intialprice}</td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->affbuyingprice}<a href="#" title="{$lang->affbuyingpricetooltip}"><img src="./images/icons/question.gif"/></a></td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->totalbuyingvalue}</td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->costprice}<a href="#" title="{$lang->costpricetooltip}"><img src="./images/icons/question.gif"/></a></td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->costpriceatriskratio}</td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->sellingprice}</td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->sellingpriceatriskratio}</td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->netmargin}</td>
                <td class="border_right" rowspan="2" valign="top" align="center">{$lang->netmarginperc}</td>
        </thead>

        <tbody id="productline_{$plrowid}_tbody" style="width:100%;">
            {$aroproductlines_rows}
        </tbody>
        <tfoot>
            <tr><td valign="top">
                    <input name="numrows_productline{$plrowid}" type="hidden" id="numrows_productline_{$plrowid}" value="{$plrowid}">
                    <img src="./images/add.gif" id="ajaxaddmore_aro/managearodouments_productline_{$plrowid}" alt="{$lang->add}">
                </td>
            </tr>
        </tfoot>

    </table>
</p>
</div>


