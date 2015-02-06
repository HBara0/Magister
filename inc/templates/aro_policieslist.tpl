<html>
    <head>
        <title>{$core->settings[systemtitle]} | {$lang->aropolicieslist}</title>
        {$headerinc}
    </head>
    <body>
        {$header}
    <tr> {$menu}
        <td class="contentContainer"><h1>{$lang->aropolicieslist}</h1>
            <form action='$_SERVER[REQUEST_URI]' method="post">
                <table class="datatable">
                    <thead>
                        <tr>
                            <th width="22.5%">{$lang->affiliate} <a href="{$sort_url}&amp;sortby=warehouse&amp;order=ASC"><img src="images/sort_asc.gif" border="0" /></a><a href="{$sort_url}&amp;sortby=warehouse&amp;order=DESC"><img src="images/sort_desc.gif" border="0" /></a></th>
                            <th width="22.5%">{$lang->orderpurchasetype} <a href="{$sort_url}&amp;sortby=purchaseType&amp;order=ASC"><img src="images/sort_asc.gif" border="0" /></a><a href="{$sort_url}&amp;sortby=purchaseType&amp;order=DESC"><img src="images/sort_desc.gif" border="0" /></a></th>
                            <th width="22.5%">{$lang->effromdate} <a href="{$sort_url}&amp;sortby=effromdate&amp;order=ASC"><img src="images/sort_asc.gif" border="0" /></a><a href="{$sort_url}&amp;sortby=effromdate&amp;order=DESC"><img src="images/sort_desc.gif" border="0" /></a></th>
                            <th width="22.5%;">{$lang->eftodate}<a href="{$sort_url}&amp;sortby=eftodate&amp;order=ASC"><img src="images/sort_asc.gif" border="0" /></a><a href="{$sort_url}&amp;sortby=eftodate&amp;order=DESC"><img src="images/sort_desc.gif" border="0" /></a></th>
                            <th width="7%">{$lang->isactive}</th>
                                {$tools}
                            <th>&nbsp;</th>
                        </tr>
                        {$filters_row}
                    </thead>
                    <tbody class="datatable-striped">
                        {$aropolicies_rows}
                    </tbody>

                </table>
            </form>

        </td>
    </tr>
</body>
</html>