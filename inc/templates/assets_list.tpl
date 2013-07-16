<html>
    <head>
        <title>{$core->settings[systemtitle]} | {$lang->listpotentialsupplier}</title>
        {$headerinc}
        <script lang="javascript">
            $(function() {
       
            $('tr[id^="asset_"]').live('mouseover',function() {
            var id = $(this).attr("id").split("_"); 
                $('tr').not('[id$='+id[1]+']').removeClass('highlight');
                $('a[id^=deleteasset_]').not('[rel^="delete_'+id[1]+'"]') .css('display','none');
                $('a[id^=editasset_]').not('[rel^="edit_'+id[1]+'"]') .css('display','none');
                $('tr[id$='+id[1]+']').toggleClass('highlight');
                $('a[rel^="delete_'+id[1]+'"]').css('display','block');
                $('a[rel^="edit_'+id[1]+'"]').css('display','block');
               
        });
    
            });

        </script>
    </head>
        <body>
        {$header}
    <tr> {$menu}
        <td class="contentContainer"><h3>{$lang->listasset}</h3>
              <form action='$_SERVER[REQUEST_URI]' method="post">
                 <table class="datatable">
                <thead>
                    <tr>
                        <th style="width:20%">{$lang->title} <a href="{$sort_url}&amp;sortby=title&amp;order=ASC"><img src="images/sort_asc.gif" border="0" /></a><a href="{$sort_url}&amp;sortby=title&amp;order=DESC"><img src="images/sort_desc.gif" border="0" /></a></th>
                        <th style="width:16%;">{$lang->affiliate}</th>
                        <th style="width:16%;">{$lang->description} <a href="{$sort_url}&amp;sortby=description&amp;order=ASC"><img src="images/sort_asc.gif" border="0" /></a><a href="{$sort_url}&amp;sortby=description&amp;order=DESC"><img src="images/sort_desc.gif" border="0" /></a></th>
                        <th style="width:16%;">{$lang->type} <a href="{$sort_url}&amp;sortby=title&amp;order=ASC"><img src="images/sort_asc.gif" border="0" /></a><a href="{$sort_url}&amp;sortby=title&amp;order=DESC"><img src="images/sort_desc.gif" border="0" /></a></th>
                        <th style="width:16%;">{$lang->status} <a href="{$sort_url}&amp;sortby=status&amp;order=ASC"><img src="images/sort_asc.gif" border="0" /></a><a href="{$sort_url}&amp;sortby=status&amp;order=DESC"><img src="images/sort_desc.gif" border="0" /></a> </th>
                        <th style="width:20%;">{$lang->createdon} <a href="{$sort_url}&amp;sortby=createdon&amp;order=ASC"><img src="images/sort_asc.gif" border="0" /></a><a href="{$sort_url}&amp;sortby=createdon&amp;order=DESC"><img src="images/sort_desc.gif" border="0" /></a> </th>
                    
                    </tr>
                    {$filters_row}
                </thead>
                <tbody>
                         {$assets_listrow}
                </tbody>

            </table>
              </form>
     <div style="width:40%; float:left; margin-top:0px;">
			<form method='post' action='$_SERVER[REQUEST_URI]'>
				{$lang->perlist}:
				<input type='text' size='4' id='perpage_field' name='perpage' value='{$core->settings[itemsperlist]}' class="smalltext"/>
			</form>
		</div>
            </td>
    </tr>
    
    </body>
   
</html>