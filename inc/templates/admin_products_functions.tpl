<html>
    <head>
        <title>{$core->settings[systemtitle]} | {$lang->listavailableapplication}</title>
        {$headerinc}
    </head>
    <body>
        {$header}
    <tr>
        {$menu}
        <td class="contentContainer">
            <h3>{$lang->listavailablefunctions}</h3> 
            <table class="datatable">
                <div style="float:right;" class="subtitle"> <a href="#" id="showpopup_cretefunction" class="showpopup"><img  src="{$core->settings[rootdir]}/images/addnew.png" border="0">{$lang->create}</a></div>
                <thead>
                    <tr>   
                        <th>{$lang->name}<a href="{$sort_url}&amp;sortby=name&amp;order=ASC"><img src="../images/sort_asc.gif" border="0" alt="{$lang->sortasc}"/></a><a href="{$sort_url}&amp;sortby=name&amp;order=DESC"><img src="../images/sort_desc.gif" border="0"  alt="{$lang->sortdesc}"/></a></th>
                        <th>{$lang->title}<a href="{$sort_url}&amp;sortby=title&amp;order=ASC"><img src="../images/sort_asc.gif" border="0" alt="{$lang->sortasc}"/></a><a href="{$sort_url}&amp;sortby=title&amp;order=DESC"><img src="../images/sort_desc.gif" border="0"  alt="{$lang->sortdesc}"/></a></th>
                        <th>{$lang->appsegment} </th>

                    </tr>
                </thead>
                <tbody>
                    {$productsapplicationsfunctions_list}
                </tbody>
                <tr>
                    <td>
                        <div style="width:40%; float:left; margin-top:0px;">
                            <form method='post' action='$_SERVER[REQUEST_URI]'>
                                {$lang->perlist}:
                                <input type='text' size='4' id='perpage_field' name='perpage' value='{$core->settings[itemsperlist]}' class="smalltext"/>
                            </form>
                        </div>
                    </td>
                </tr>
            </table>
        </form>
    </td>
</tr>
{$footer}
</body>
</html>
{$popup_createfunction}
