<div style="width: 30%; display: inline-block; vertical-align: top; margin-bottom: 10px;">{$lang->product}</div><div style="width: 60%; display: inline-block;">
    <input type="text" size="25" name="marketdata[cfpid]" id="chemfunctionproducts_1_autocomplete" size="100" autocomplete="off"  value="{$product->name}"/>
    <input type="hidden" id="chemfunctionproducts_1_id" name="marketdata[cfpid]" value="{$midata->cfpid}"/>
    <input type="hidden" value="1" id="userproducts" name="userproducts" />
    <div id="searchQuickResults_1" class="searchQuickResults" style="display:none;"></div>
    <br />
</div>
<div><a href="#popup_profilesmarketdata" onclick="$('#prof_mkd_chemsubfield').toggle();">or use chemical substance instead.</a></div>