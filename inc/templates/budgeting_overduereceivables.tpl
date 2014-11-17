<html>
    <head>
        <title>{$core->settings[systemtitle]} | {$lang->overduereceivables}</title>
        {$headerinc}
        <script type="text/javascript">
            $(function() {
                $("input[id^='clientoverdue_']").live('change keyup live', function() {
                    var id = $(this).attr('id').split("_");
                    var total = 0;
                    $('input[id^=clientoverdue_][id$=' + id[2] + ']').each(function() {
                        if(!jQuery.isEmptyObject(this.value)) {
                            total += parseFloat(this.value);
                        }
                    });
                    $('span[id=total_' + id[2] + ']').text(total);
                });
            });

        </script>
    </head>

    <body>
        {$header}
    <tr>
        {$menu}
        <td class="contentContainer" colspan="2">
            <h1>{$lang->overduereceivables}<br /><small>{$affiliate->name} {$budget_data['year']}</small></h1>
                {$output_currency}
            <form name="perform_budgeting/overduereceivables_Form" id="perform_budgeting/overduereceivables_Form"  action="#" method="post">
                <input type="hidden" id="identifier" name="identifier" value="{$sessionidentifier}">
                <table class="datatable" style="width:100%;table-layout:fixed;">
                    <thead>
                        {$overduereceivables_header}
                        {$overduereceivables_row}
                    </thead>
                    <tbody id="clientsoverdues{$rowid}_tbody" style="width:100%;">
                        {$row}
                    </tbody>
                    <tfoot>
                        <tr><td valign="top">
                                <input name="numrows_clientsoverdues{$rowid}" type="hidden" id="numrows_clientsoverdues{$rowid}" value="{$rowid}">
                                <img src="./images/add.gif" id="ajaxaddmore_budgeting/overduereceivables_clientsoverdues_{$rowid}" alt="{$lang->add}">
                            </td>
                        </tr>
                        <tr>
                            <td style="width:20%;font-weight:bold">{$lang->total}</td><td style="width:10%"></td><td style="width:10%"></td>
                            <td style="width:10%"><span id="total_totalAmount" style="font-weight:bold;">{$totalamount}</span></td>
                            <td style="width:10%"></td><td style="width:10%"></td>
                        </tr>
                    </tfoot>

                </table>
                <hr />

                <input type="{$type}" id="perform_budgeting/overduereceivables_Button" value="Save" class="button"/>
            </form>
            <div id="perform_budgeting/overduereceivables_Results"></div>
        </td>
    </tr>
    {$footer}
</body>
</html>