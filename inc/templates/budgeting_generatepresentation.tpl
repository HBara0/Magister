<html>
    <head>
        <title>{$core->settings[systemtitle]} | {$lang->generatepresentation}</title>
        {$headerinc}
    </head>
    <body>
        {$header}
    <tr>
        {$menu}
        <td class="contentContainer">
            <h1>{$lang->generatepresentation}</h1>
            <form name="perform_budgeting/generatepresentation_Form" id="perform_budgeting/generatepresentation_Form" action="#" method="post">
                <div>
                    {$lang->chooseanafiiliate}  {$affiliates_list}
                    <input type="hidden" name="export" value="1">
                </div>
                <hr>
                <input type="submit" value="{$lang->downloadreport}" class="button">
            </form>
        </td>
    </tr>
    {$footer}
</body>
</html>