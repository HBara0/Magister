<div class="ui-state-highlight ui-corner-all" style="padding-left: 5px; margin-bottom:10px;">
    <span style="font-weight: bold; font-size: 14px;">{$lang->leavedetails}:</span>
    <div style="padding:5px;">
        {$leave->get_displayname()} - <span><a target="_blank" href="index.php?module=attendance/viewleave&id={$leave->lid}">{$lang->viewleave}</a></span>
        <div style="position: relative;">{$lang->purpose}: {$leave_purpose}</div>
        <div style="position: relative;">{$lang->segment}: {$leave_segment}</div>
    </div>
</div>
