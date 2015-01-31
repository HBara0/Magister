<html>
    <head>
        <title>{$core->settings[systemtitle]} | {$lang->trainingandvisits}</title>
        {$headerinc}
        <script type="text/javascript">
            $(function() {
                $("input[id^='costaff_']").live('change keyup live', function() {
                    var id = $(this).attr('id').split("_");
                    var total = 0;
                    $('input[id^=costaff_][id$=' + id[2] + ']').each(function() {
                        if(!jQuery.isEmptyObject(this.value)) {
                            total += parseFloat(this.value);
                        }
                    });
                    $('span[id=total_' + id[2] + ']').text(total);
                });
                $("input[id^='cost_']").live('change keyup live', function() {
                    var id = $(this).attr('id').split("_");
                    var subtotalintamount = 0;
                    $('input[id$=' + id[2] + '_' + id[3] + '][id^=cost]').each(function() {
                        if(!jQuery.isEmptyObject(this.value)) {
                            subtotalintamount += parseFloat(this.value);
                        }
                    });
                    $('span[id=subtotal_' + id[2] + '_' + id[3] + ']').text(subtotalintamount);
                });

                $('input[id^="leave_"]').live('click', function() {
                    var id = $(this).attr('id').split("_");
                    $('input[type="submit"][id^="trainingvisitsleaves_"]').attr("disabled", !this.checked);
                });
            });

        </script>
    </head>

    <body>
        {$header}
    <tr>
        {$menu}
        <td class="contentContainer" colspan="1">
            <h1>{$lang->trainingandvisits}<br /><small>{$affiliate->name} {$financialbudget_year} - YEF {$financialbudget_prevyear}</small></h1>
                {$budgeting_tainingvisitleaves}
            <form name="perform_budgeting/trainingvisits_Form" id="perform_budgeting/trainingvisits_Form"  action="#" method="post">
                <input type="hidden" id="identifier" name="identifier" value="{$sessionidentifier}">
                <input name="financialbudget[affid]" value="{$affiliate->affid}" type="hidden">
                <input name="financialbudget[year]" value="{$financialbudget_year}" type="hidden">
                <div class="datatable" style="display: block;width: 100%;">

                    <table width="100%" border="0" cellspacing="0" cellpadding="2">
                        <div class="thead">{$lang->domesticvisit}</div>
                        <thead>
                            <tr style="vertical-align: top;">
                                <td width="17.5%" rowspan="2" valign="top" align="center" class=" border_right">{$lang->event} </td>
                                <td  width="17.5%" class=" border_right"   align="center"  valign="top" align="left">{$lang->company} <a href="index.php?module=contents/addentities&type=customer" target="_blank"><img src="images/addnew.png" border="0" alt="{$lang->add}"></a></td>
                                <td width="17.5%" class=" border_right"   valign="top" align="center">{$lang->date}  </td>
                                <td width="17.5%" class=" border_right" valign="top" align="center">{$lang->purpose}</td>
                                <td width="30%" class=" border_right"    valign="top" align="center">{$lang->costaffonly}</td>

                            </tr>
                        </thead>
                        <tbody id="budgetrainvisitlocal_{$rowid}_tbody" style="width:100%;">
                            {$budgettaininglocalvisits_rows}
                        </tbody>


                        <tr>
                            <td style="width:20%;font-weight:bold">{$lang->total}</td><td style="width:10%"></td><td style="width:10%"></td>
                            <td style="width:30%"></td>    <td style="width:30%"> <span id="total_local" style="font-weight:bold;">{$totallocalamount}</span></td>
                            <td style="width:30%"></td><td style="width:10%"></td>
                        </tr>
                        <tfoot>
                            <tr><td valign="top">
                                    <input name="numrows_budgetrainvisitlocal{$rowid}" type="hidden" id="numrows_budgetrainvisitlocal_{$rowid}" value="{$rowid}">
                                    <input type="hidden" name="ajaxaddmoredata[affid]" id="ajaxaddmoredata_affid" value="{$budget_data[affid]}"/>
                                    <img src="./images/add.gif" id="ajaxaddmore_budgeting/trainingvisits_budgetrainvisitlocal_{$rowid}" alt="{$lang->add}">
                                </td></tr>
                        </tfoot>
                    </table>

                </div>
                <div class="datatable" style="display: block; width: 100%;">
                    <div class="thead">{$lang->intvisit}</div>
                    <table width="100%" border="0" cellspacing="0" cellpadding="2">
                        <thead>
                            <tr style="vertical-align: top;">
                                <td width="14.2%"  valign="top" align="center" class=" border_right">{$lang->event} </td>
                                <td width="14.2%" class=" border_right"  valign="top" align="center">{$lang->bm}  </td>
                                <td width="14.2%" class=" border_right"   valign="top" align="center">{$lang->date}</td>
                                <td width="14.2%" class=" border_right"  valign="top" align="center">{$lang->purpose}</td>
                                <td width="14.2" class=" border_right"   valign="top" align="center">{$lang->planecost}</td>
                                <td width="14.2" class=" border_right"   valign="top" align="center">{$lang->othercost}</td>
                                <td width="14.2" class=" border_right"   valign="top" align="center">{$lang->totalcostaffonly}</td>
                            </tr>
                        </thead>
                        <tbody id="budgetrainvisitint_{$rowid}_tbody" style="width:100%;">
                            {$budgettaininig_intvisits_rows}
                        </tbody>

                        <tr>
                            <td style="width:20%;font-weight:bold">{$lang->total}</td><td style="width:10%"></td><td style="width:10%"></td>
                            <td style="width:30%"></td> <td style="width:30%"> <span id="tsotal_othercssost" style="font-weight:bold;">{$totalintamoussnt}</span></td>
                            <td style="width:30%"></td><td style="width:10%"></td>
                        </tr>

                        <tfoot>

                            <tr><td valign="top">
                                    <input name="numrows_budgetrainvisitint{$rowid}" type="hidden" id="numrows_budgetrainvisitint_{$rowid}" value="{$rowid}">
                                    <img src="./images/add.gif" id="ajaxaddmore_budgeting/trainingvisits_budgetrainvisitint_{$rowid}" alt="{$lang->add}">
                                </td></tr>

                        </tfoot>
                    </table>
                </div>

                <br/>
                <input type="submit" id="perform_budgeting/trainingvisits_Button" value="{$lang->savecaps}" class="button"/>
            </form>
            <div id="perform_budgeting/trainingvisits_Results"></div>
        </body>
    </td>
</tr>
{$footer}
</html>