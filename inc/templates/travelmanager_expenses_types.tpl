<tr class="{$altrow}"  style="border:1px gainsboro solid;"id="{$sequence}_{$rowid}"><td>
        <div style="display:inline-block;">{$lang->exptype}</div>
        <div style="display:inline-block; "><select id="segment_expensestype_{$sequence}_{$rowid}" name='segment[{$sequence}][expenses][{$rowid}][tmetid]'   {$onchange_actions}>{$expenses_options}</select></div>
        <div style="display:block;padding:5px">

            <div style="display:none;" id="Other_{$sequence}_{$rowid}">
                <div style="display:inline-block;">{$lang->other} <input name="segment[{$sequence}][expenses][{$rowid}][description]" type="text" value="{$expensestype[$segid][$rowid][otherdesc]}"> </div>
            </div>
        </div>
        <div style="display: inline-block; width:60%;">
            {$expenses_details}
        </div>
        <div style="{$expensestype[$segid][$rowid][display]}  padding: 8px;" id="anotheraff_{$sequence}_{$rowid}" class="border_bottom border_left border_right border_top" >
            <span>Another Affiliate </span>
            <input id="affiliate_{$sequence}_{$rowid}_cache_autocomplete" autocomplete="off" tabindex="8" value="{$expensestype[$segid][$rowid][affiliate]}"  type="text">
            <input id="affiliate_{$sequence}_{$rowid}_cache_id" name="segment[{$sequence}][expenses][{$rowid}][paidById]" value="{$expensestype[$segid][$rowid][affid]}"type="hidden">
        </div>
    </td></tr>



