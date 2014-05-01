<head>
    <title>{$core->settings[systemtitle]} | {$lang->fillsurvey}</title>
    {$headerinc}
</head>
<body>
    {$header}
<tr>
    {$menu}
    <td class="contentContainer">
        <h3>{$lang->fillsurvey} - {$survey_details[subject]}</h3>
        <div class="ui-state-highlight ui-corner-all" style="padding-left: 5px; margin-bottom:10px;">
            <p>{$survey_details[description]}</p>
            {$associations_list}
            <span class="smalltext">{$lang->surveydate}: {$survey_details[dateCreated_output]}</span>
        </div>

        <form name="perform_surveys/fill_Form" id="perform_surveys/fill_Form" method="post">
            <input type="hidden" value="{$survey_details[identifier]}" name="identifier" />
            {$questions_list}
            <hr />
            <input type='submit' class='button' value="{$lang->savecaps}" id='perform_surveys/fill_Button'>
        </form>
        <div id="perform_surveys/fill_Results"></div>
    </td> 
</tr>
{$footer}
</body>
</html>