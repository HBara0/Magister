<div style="display:block;" >
    <h2 class="subtitle">Possible Transportations</h2>
    <div class="ui-state-highlight ui-corner-all" style="padding: 6px; font-weight: bold;"><a href="{$transpmode_apimaplink}" target="_blank">Visualize Tranpostation Possibilities on Map</a></div>
    {$transsegments_output}
</div>
<h2 class="subtitle" style="padding:8px;width:40%;">{$lang->accomodations}</h2>
<div style="display:block;" id="segment_hotels_{$sequence}">
   <!-- <div class="subtitle">{}Approved Hotels</div>-->

    {$hotelssegments_output}
</div>
<div style="display:block;" id="other_hotels_{$sequence}">
  <!-- <div class="subtitle">{}Approved Hotels</div>-->
    {$otherhotels_output}
</div>
<div style="display:block; width: 100%;" id="segment_expenses_{$sequence}">
    <input name="sequence" type="hidden" id="sequence" value="{$sequence}">
    <h2 class="subtitle" style="padding:8px;width:40%;">{$lang->addexp}</h2>
    <table width="100%" border="1" cellspacing="0" cellpadding="0" style="margin-left: 8px;" class="datatable">
        <tbody id="expenses_{$sequence}_tbody">
            {$segments_expenses_output}

        </tbody>
    </table>
    <span> <img src="./images/add.gif"  id="ajaxaddmore_travelmanager/plantrip_expenses_{$sequence}"  alt="{$lang->add}">
        <input name="numrows_expenses_{$sequence}" type="hidden" id="numrows_expenses_{$sequence}" value="{$rowid}">
    </span>

</div>
