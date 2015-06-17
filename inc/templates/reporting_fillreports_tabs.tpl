<html>
    <head>
        <title>{$core->settings[systemtitle]} | {$lang->reportdetails} - Q{$core->input[quarter]} {$core->input[year]} / {$core->input[supplier]} - {$core->input[affiliate]}</title>
        {$headerinc}
        <link href="{$core->settings[rootdir]}/css/rateit.min.css" rel="stylesheet" type="text/css">
        <script src="{$core->settings[rootdir]}/js/fillreport.js" type="text/javascript"></script>
        <script src="{$core->settings[rootdir]}/js/jquery.rateit.min.js" type="text/javascript"></script>
        <script>
            $(function () {
                var tabs = $("#reporttabs").tabs();
                var tabcounter = tabs.find(".ui-tabs-nav").find('li').length + 1; //find the  lenght of li tabs and increment by 1
            {$header_ratingjs}
                $('#previewed_button').live('click', function () {
                    $('input[id="previewed_value"]').each(function (i, obj) {
                        $(obj).val('1');

                    });
                    $('input[id="save_productsactivity_reporting/fillreport_Button"]').click();
                    setTimeout(function () {
                        $('input[id="save_marketreport_reporting/fillreport_Button"]').click()
                    }, 2000
                            );

                    setTimeout(function () {
                        $('#previewed_value').each(function (i, obj) {
                            $(obj).val('');
                        });
                    }, 4000
                            );
                });
            });
        </script>
        <style type="text/css">
            .ui-tabs-nav li {
                width:200px;
            }

            .ui-icon,.ui-icon-close {
                cursor: pointer;
            }
        </style>
    </head>

    <body>
        {$header}
    <tr>
        {$menu}

        <td class="contentContainer">
            <h1>{$lang->reportdetails}<div style="font-style:italic; font-size:12px; color:#888;">Q{$core->input[quarter]} {$core->input[year]} / {$core->input[supplier]} - {$core->input[affiliate]}</div></h1>
            <input type="hidden" id="transfill" name="transfill" value="{$transfill}">
            <div class="ui-state-highlight ui-corner-all" style="padding-left: 5px; margin-bottom:10px;"><p><strong>Welcome to the new QR filling process; you no longer need to go back and forth in pages, simply switch tabs.<br />
                        The market report now has a dedicated section for competition, please make sure to use it.</strong></div>

            <div id="reporttabs">
                <ul>
                    <li><a href="#reporttabs-1">{$lang->productactivitydetails}</a></li>
                    <li><a href="#reporttabs-2">{$lang->marketreport}</a></li>
                </ul>

                <div id="reporttabs-1">
                    {$productsactivitypage}
                </div>
                <div id="reporttabs-2">
                    {$marketreportpage}
                </div>
        </td>
    </tr>
    <tr><td></td><td colspan="3">Product Activity Status:<div id="save_productsactivity_reporting/fillreport_Results"></div>
    <tr><td></td><td colspan="3">Market Report Status:            <div id="save_marketreport_reporting/fillreport_Results"></div>


        </td></tr>
        {$footer}
        {$addproduct_popup}
</body>
</html>