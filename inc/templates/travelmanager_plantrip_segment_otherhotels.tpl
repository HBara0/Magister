<div style="display:block;width:100%; margin-top:15px;margin-bottom :15px; padding:5px; border:1px solid #FCEFA1;">
    <div style="display:inline-block; " class="subtitle">{$lang->addotherhotel}</div>
    <div style="display:block; ">
        <input id="hotels_{$sequence}_cache_hotel_autocomplete" autocomplete="off" tabindex="3" placeholder="{$lang->searchotherhotel}" value=""  type="text" onFocus="getcityid()" >
        <input id="hotels_{$sequence}_cache_hotel_id" name="segment[{$sequence}][tmhid][{$otherhotel_checksum}][tmhid]" value="" type="hidden">
        <input type="hidden" id="hotels_{$sequence}_cache_hotel_city" name="city" value=''/>
        <span> <a style="cursor: pointer;"  rel="{$destcity[ciid]}" id="addnewhotel_{$sequence}_travelmanager/plantrip_loadpopupbyid"><img src="images/addnew.png"  title="{$lang->addotherhotel}" alt="Add" border="0">{$lang->addhotel}</a> </span>
        <br/>
        <div style="display:inline-block;padding:5px;width:15%;">{$lang->pricenight}</div>
        <div style="display:inline-block;padding:5px;width:100px;"><input tabindex="3" style="width:100px"id="pricenight_segacc_0_{$sequence}" accept="numeric" name="segment[{$sequence}][tmhid][{$otherhotel_checksum}][priceNight]" type="text" value=""></div>
        <div style="display:inline-block;padding:5px;width:50px;">{$currencies_list}</div>
        <div style="display:inline-block;padding:5px;width:120px; ">{$lang->numnight}</div>
        <div style="display:inline-block;padding:5px;width:15%;"> <input style="width:100px;" tabindex="3" id="numnight_segacc_0_{$sequence}" accept="numeric" name="segment[{$sequence}][tmhid][{$otherhotel_checksum}][numNights]" type="number" value="" max="{$leavedays}"> </div>

        <div style="display:inline-block;padding:2px;  font-weight: bold; width:15%;" id="total_segacc_0_{$sequence}" ><span>Sub Total:  </span>
        </div>
        <input type="hidden"  disabled value="" id="hotel_{$sequence}_{$otherhotel_checksum}_total" name="segment[{$sequence}][tmhid][{$otherhotel_checksum}][subtotal]">
        <input type="hidden" disabled value="{$otherhotel_checksum}" id="checksum_0_{$sequence}_{$otherhotel_checksum}_tmhid">
        <div style="display:inline-block;padding:5px;width:15%">{$lang->paidby}</div>
        <div style="display:inline-block;width:20%;">
            {$paidbyoptions}
        </div>
    </div>
    <div id="anotheraff_otheraccomodations_{$sequence}_{$otherhotel_checksum}" style="{$otherhotel[displaystatus]} padding: 8px;" class="border_bottom border_left border_right border_top">
        <div style="display:inline-block;width:15%;">{$lang->anotheraff}</div>
        <div style="display:inline-block;width:20%;padding:5px;">
            <input id="affiliate_{$sequence}_0_cache_otheracc_autocomplete" autocomplete="off" tabindex="3" value=""  type="text"></div>
        <input id="affiliate_{$sequence}_0_cache_otheracc_id" name="segment[{$sequence}][tmhid][{$otherhotel_checksum}][paidById]" value="" type="hidden">
    </div>

</div>

<script>
    function getcityid() {
        var city = $('#destinationcity_{$sequence}_cache_id').val();
        if(typeof city != 'undefined') {
            $('#hotels_{$sequence}_cache_hotel_city').val(city);
        }
    }
</script>
