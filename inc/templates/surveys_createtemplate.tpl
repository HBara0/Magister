<html>
<head>
<title>{$core->settings[systemtitle]} | {$lang->createtemplate}</title>
{$headerinc}
<script type="text/javascript">
    $(function() { 	
            $("select[id$='_[type]']").live('change', function() {
                if(sharedFunctions.checkSession() == false) {
                    return;	
                }
                var id = $(this).attr("id").split("_"); 
                if($(this).val() != "") {
                    $.ajax({type: 'post',
                        url: rootdir + "index.php?module=surveys/createsurveytemplate&action=parsetype",
                        data: {questiontype:$("select[id^='section_"+id[1]+"_[questions]_"+id[3]+"_[type]']").val(),sectionid:id[1],questionid:id[3]},
                        beforeSend: function() {
                           $("select[id^='section_"+id[1]+"_[questions]_"+id[3]+"_[type]']").after("<img id='section_"+id[1]+"_[questions]_"+id[3]+"_[type][loading]' style='padding: 5px;' src='" + imagespath + "/loading-bar.gif' alt='" + loading_text + "' border='0' />");
                        },
                        complete: function() {
                            $("img[id='section_"+id[1]+"_[questions]_"+id[3]+"_[type][loading]']").remove();
                        }
                    });
                }
            });

            $("select[id$='[validationType]']").live('change', function() {               
                var id = $(this).attr("id").split("_");
                var valMatch = ["minchars", "maxchars"];
                $("tr[id='section_"+id[1]+"_[questions]_"+id[3]+"_[validationCriterion]']").css("display", "none");
                    if(jQuery.inArray($(this).val(), valMatch) != -1){
                        $("tr[id='section_"+id[1]+"_[questions]_"+id[3]+"_[validationCriterion]']").css("display", "table-row");
                    }
                 }
            );              
    }); 
</script>

</head>
<body>
{$header}
<tr>
    {$menu}
    <td class="contentContainer">
        <h3>{$lang->createtemplate}</h3>
        <form name="perform_surveys/createsurveytemplate_Form" id="perform_surveys/createsurveytemplate_Form" action="#" method="post">
             <input type="hidden" id="action" name="action" value="{$action}" />
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr><td colspan='2'><span class="subtitle">{$lang->basictemplateinfo}</span></td></tr>
            <tr>
                <td width="20%">{$lang->surveytemplatetitle}</td>
                <td width="80%"><input name="title" id="title" type="text" size="30" required="requierd"></td>
            </tr>
            <tr>
                <td>{$lang->category}</td>
                <td>
                    {$surveycategories_list}
                </td>
            </tr>
            <tr>
                <td>{$lang->publicallyavailable}</td>
                <td>{$radiobuttons[isPublic]}</td>
            </tr>
            <tr>
                <td>{$lang->forceanonymousfilling}</td>
                <td>{$radiobuttons[forceAnonymousFilling]}</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: left; padding:0; margin:0px;">
                    <table width="100%">
                        <thead>
                            <tr>
                                <td colspan="2"><hr /><span class="subtitle">{$lang->surveyquestions}</span></td>
                            </tr>
                        </thead>
                        <tbody id="section{$section_rowid}_tbody">
                            {$newsection}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4"><img src="./images/add.gif" id="ajaxaddmore_surveys/createsurveytemplate_section_{$section_rowid}"  border="0" alt="{$lang->add}">
                                    <input name="numrows_section{$section_rowid}" type="hidden" id="numrows_section{$section_rowid}" value="{$section_rowid}"></td>
                            </tr>
                        </tfoot>
                    </table>    
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <hr />
                    <input type="submit" value="{$lang->$action}" id="perform_surveys/createsurveytemplate_Button" tabindex="26" class="button"/>
                    <input type="reset" value="{$lang->reset}" class="button" />
                </td>
            </tr>
        </table>
        <div id="perform_surveys/createsurveytemplate_Results"></div>  
        </form>  
    </td>
</tr>
{$footer}
</body>
</html>