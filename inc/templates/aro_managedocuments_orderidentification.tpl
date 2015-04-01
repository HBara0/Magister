<a class="header" href="#"><h2>{$lang->orderidentification}</h2></a>
<div>
    <p>
    <table>
        <tbody>
            <tr>
                <td>{$lang->affiliate}</td>
                <td>
                    {$affiliate_list}
                    <input type='hidden' name='aorid' value='{$aroorderrequest->aorid}'/>
                </td>
                <td>{$lang->orderpurchasetype}</td>
                <td>{$purchasetypelist}</td>
                <td>{$lang->buyingcurr}</td>
                <td>{$currencies_list}</td>
            </tr>
            <tr>
                <td>{$lang->orderreference}</td>
                <td><input type="text"  readonly="readonly" name="orderReference" id="orderreference" value="{$aroorderrequest->orderReference}" required="required"/></td>
                <td>Exchange rate to usd</td>
                <td><input type="number"  step="any" name="exchangeRateToUSD" id="exchangeRateToUSD" value="{$aroorderrequest->exchangeRateToUSD}" readonly required="required"/>
                    <input type="hidden" id="exchangeRateToUSD_disabled" value="1"/></td>
            </tr>

            <tr>
                <td>{$lang->inspectiontype}</td>
                <td>{$inspectionlist}</td>
                <td></td>
                <td></td>
            </tr>

        </tbody>
    </table>
</p>
</div>


