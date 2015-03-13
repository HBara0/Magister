<a class="header" href="#"><h2>{$lang->actualpurchase}</h2></a>
<div>
    <p>
    <table>
        <thead>
            <tr style="vertical-align: top;">
                <td class="border_right" rowspan="2" valign="top" align="center" style="width:150px;">{$lang->product}</td>
                <td class="border_right" rowspan="2" valign="top" align="center" style="width:100px;">{$lang->packaging}</td>
                <td class="border_right" rowspan="2" valign="top" align="center" style="width:100px;">{$lang->quantity}</td>
                <td class="border_right" rowspan="2" valign="top" align="center" style="width:100px;">{$lang->totalvalue}</td>
                <td class="border_right" rowspan="2" valign="top" align="center" style="width:150px;">{$lang->estdateofstockentry}</td>
                <td class="border_right" rowspan="2" valign="top" align="center" style="width:100px;">{$lang->shelflife}</td>
                <td class="border_right" rowspan="2" valign="top" align="center" style="width:150px;">{$lang->estdateofsale} </td>
        </thead>

        <tbody id="actualpurchaserow_{$aprowid}_tbody" style="width:100%;">
            {$actualpurchase_rows}
        </tbody>
        <tfoot>
            <tr><td valign="top">
                    <input type="hidden" name="numrows_actualpurchaserow{$aprowid}"  id="numrows_actualpurchaserow_{$aprowid}" value="{$aprowid}">
                    <img src="./images/add.gif" id="ajaxaddmore_aro/managearodouments_actualpurchaserow_{$aprowid}" alt="{$lang->add}" style="display:none;">
                </td>
            </tr>
        </tfoot>

    </table>
</p>
</div>


