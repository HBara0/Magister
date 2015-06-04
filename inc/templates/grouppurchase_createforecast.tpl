<html>
    <head>
        <title>{$core->settings[systemtitle]} | {$lang->createforecast}</title>
        {$headerinc}

        <script src="{$core->settings[rootdir]}/js/fillreport.js" type="text/javascript"></script>
    </head>
    <body>
        {$header}
    <tr>
        {$menu}
        <td class="contentContainer">
            <h1>{$lang->createforecast}</h1>
            <form name="perform_grouppurchase/create_Form" id="perform_grouppurchase/create_Form" action="index.php?module=grouppurchase/fillforecast" method="post">
                <input type="hidden" name="identifier" value="{$sessionidentifier}"/>
                <div style="display:block;">
                    <div style="display:inline-block;padding:8px;">{$lang->affiliate}</div>
                    <div style="display:inline-block;padding:8px;">{$gp_affiliate}</div>
                </div>
                <div id="budget_supplier" style="display:block;">
                    <div style="display:inline-block;padding:8px;">{$lang->supplier}</div>
                    <div style="display:inline-block;padding:8px;">{$gp_supplierslist}</div> <a href="index.php?module=contents/addentities&amp;type=supplier&amp;referrer=budgeting" target="_blank"><img src="images/addnew.png" border="0" alt="{$lang->add}"></a> <div id="supplierslist_Loading" style="display:inline-block;padding:8px;"></div>
                </div>
                <!--   <div  id="budget_year" style="display:block;">
                       <div style="display:inline-block;padding:8px;">{$lang->year}</div>
                       <div style="display:inline-block;padding:8px; margin-left:20px;"><select name="forecast[year]" title="year" id="year">{$gp_year}</select></div>
                       <div id="years_Loading" style="display:inline-block;padding:8px;"></div>
                   </div>-->
                <div>
                    <div style="display:inline-block;padding:8px;">{$lang->onbehalf}</div>
                    <div style="display:inline-block;padding:8px; margin-left:20px;"><select style="width:150px" name="forecast[onBehalf]" title="year" id="">{$gp_onbehalf}</select></div>
                </div>
                <!--  <div  id="buttons_row" style=" display: none;clear:left;"><input type="submit" value="Proceed" class="button"  /></div>-->
                <div>
                    <hr />
                    <input type="submit" value="Proceed" class="button"/>
                </div>
            </form>
            <div id="perform_grouppurchase/create_Results"></div>
        </td>
    </tr>
    {$footer}
</body>
</html>