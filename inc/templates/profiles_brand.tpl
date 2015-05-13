<html>
    <head>
        <title>{$core->settings[systemtitle]} | {$page_title}</title>
        {$headerinc}
        <script>
            $(function() {
                $('#brands_1_id_output').live('change', function() {
                    if($('#brands_1_id_output').val() > 0) {
                        $('input[id="customer_1_autocomplete"]').attr('disabled', 'disabled');
                        $('input[id="customer_1_id"]').val('0');
                        $('input[id="customer_1_autocomplete"]').val('');
                    }
                    else {
                        $('input[id="customer_1_autocomplete"]').removeAttr('disabled');
                    }
                });
                $('input[id ="brands_1_autocomplete"]').live('change', function() {
                    $('input[id="customer_1_autocomplete"]').removeAttr('disabled');
                });
            });
        </script>
    </head>
    <body>
        {$header}
    <tr>
        {$menu}
        <td class="contentContainer">
            <h1>{$page_title_header}<small><br />{$customername}</small></h1>
            <div style="display:inline-block;width:45%">{$clone_button}</div><div style="width:50%;display:inline-block;color: #91B64F;">{$reviewed}</div>
            <div style="display:inline-block;width:45%"></div>
            <div style="width:50%;display:inline-block;">
                <div id="perform_profiles/brandprofile_Results"></div>
                <form action="#" method="post" id="perform_profiles/brandprofile_Form" id="perform_profiles/brandprofile_Form">
                    <input type="hidden" name="ebpid" value="{$core->input[ebpid]}"/>
                    {$reviewbtn}
                </form>
            </div>
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                {$endproducts_list}
                {$chemsubstance_list}
                {$products_list}
                {$ingredients_list}
            </table>
        </td>
    </tr>
    {$footer}
    {$pop_clone}
</body>
</html>