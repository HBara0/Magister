/*
 * Copyright © 2015 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: aro_managedocuments.js
 * Created:        @tony.assaad    Feb 19, 2015 | 9:59:17 AM
 * Last Update:    @tony.assaad    Feb 19, 2015 | 9:59:17 AM
 */
$(function() {

    $('.accordion .header').accordion({collapsible: true});
    $('.accordion .header').click(function() {
        $(this).next().toggle();
        return false;
    }).next().hide();
    //--------------------------------------------------------------
    if(typeof getUrlParameter('referrer') !== 'undefined') {
        if(getUrlParameter('referrer') == 'toapprove' || getUrlParameter('referrer') == 'toapprove#' || (myUrl.substring(myUrl.length - 1) == '#' && getUrlParameter('referrer') == 'toapprove')) {
            $("form[id='perform_aro/managearodouments_Form'] :input:not([id^='approvearo'])").attr("disabled", true);
        }
    }
    else {
        if(typeof getUrlParameter('id') !== 'undefined') {
            $.ajax({type: 'post',
                url: rootdir + "index.php?module=aro/managearodouments&action=viewonly",
                data: "id=" + getUrlParameter('id'),
                beforeSend: function() {
                },
                complete: function() {
                    //   $("#modal-loading").dialog("close").remove();
                },
                success: function(returnedData) {
                    if(typeof returnedData != 'undefined' && returnedData.length > 0) {
                        var json = eval("(" + returnedData + ");");

                        if(json['disable'] === 1) {
                            $("form[id='perform_aro/managearodouments_Form'] :input:not([id^='approve_aro'])").attr("disabled", true);
                        }
                    }
                }
            });
        }
    }
///------------------------
    $("input[id='approvearo']").click(function() {
        sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=approvearo&id=' + $("input[id='approvearo_id']").val());
    });
    /*-----------------------------------------------------------
     On change of affid Or purchase type document number, Affiliate policy , approval chain, default aff policy
     On change of affid only Get warehouses
     On Change of purchase type only (check which fields to disable))*/
    $("select[id$='purchasetype'],select[id$='affid']").live('change', function() {
        if(sharedFunctions.checkSession() == false) {
            return;
        }
        $(this).data('affid', $('select[id=affid]').val());
        var affid = $(this).data('affid');
        $(this).data('purchasetype', $('select[id=purchasetype]').val());
        var ptid = $(this).data('purchasetype');
        sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populatedocnum&affid= ' + affid + '&ptid= ' + ptid);
        sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populateaffpolicy&affid= ' + affid + '&ptid= ' + ptid);

        $.ajax({type: 'post',
            url: rootdir + "index.php?module=aro/managearodouments&action=generateapprovalchain",
            data: "affid=" + affid + "&ptid=" + ptid,
            beforeSend: function() {
            },
            complete: function() {
                //   $("#modal-loading").dialog("close").remove();
            },
            success: function(returnedData) {
                $('#aro_approvalcain').html(returnedData);
            }
        });
        $.getJSON(rootdir + 'index.php?module=aro/managearodouments&action=popultedefaultaffpolicy&affid= ' + affid + '&ptid= ' + ptid, function(data) {
            var jsonStr = JSON.stringify(data);
            obj = JSON.parse(jsonStr);
            if((typeof obj === "object") && (obj !== null)) {
                jQuery.each(obj, function(i, val) {
                    if(val == 0) { //If no default intermed policy (Ex: case of LSP)
                        $('select[id^="' + i + '"] option:selected').removeAttr('selected');
                    } else {
                        // var id = val.split(" ");
                        $("select[id^='" + i + "'] option[value='" + val + "']").attr("selected", "true").trigger("change");
                    }
                });
            }
        });
        if($(this).attr('id') === 'affid') {
            /*Get Affiliate Warehouses*/
            $.ajax({type: 'post',
                url: rootdir + "index.php?module=aro/managearodouments&action=getwarehouses",
                data: "affid=" + affid,
                beforeSend: function() {
                    $("body").append("<div id='modal-loading'></div>");
                    $("#modal-loading").dialog({height: 150, modal: true, closeOnEscape: false, title: 'Loading...', resizable: false, minHeight: 0
                    });
                },
                complete: function() {
                    $("#modal-loading").dialog("close").remove();
                },
                success: function(returnedData) {

                    $('#warehouse_list_td').html(returnedData);
                    $('#parmsfornetmargin_warehousingRate').find('option').remove();
                    $('#parmsfornetmargin_warehousingPeriod').val(0);
                }
            });
        }
        if($(this).attr('id') === 'purchasetype') {
            /*Disable days in Stock, QPS and warehousing section according to seleced purchasetype*/
            var ptid = $(this).val();
            $.getJSON(rootdir + 'index.php?module=aro/managearodouments&action=InolveIntermediary&ptid=' + ptid, function(data) {
                //var jsonStr = JSON.stringify(data);
                //obj = JSON.parse(jsonStr);
                // jQuery.each(obj, function(i, val) {
                var fields = ["aff", "paymentterm", "incoterms", "IncotermsDesc", "PaymentTermDesc", "ptAcceptableMargin", "promiseofpayment"];
                if(data == 0) {
                    for(var i = 0; i < fields.length; i++) {
                        $("input[id='partiesinfo_intermed_" + fields[i] + "']").removeAttr("required");
                        $("select[id='partiesinfo_intermed_" + fields[i] + "']").removeAttr("required");
                        $("input[id='pickDate_intermed_" + fields[i] + "']").removeAttr("required");
                        $("select[id='partiesinfo_intermed_" + fields[i] + "'] option[value='0']").remove();
                    }
                    $("input[id='partiesinfo_commission']").attr('value', '0');
                } else {
                    for(var i = 0; i < fields.length; i++) {
                        $("input[id='partiesinfo_intermed_" + fields[i] + "']").attr("required", "true");
                        $("select[id='partiesinfo_intermed_" + fields[i] + "']").attr("required", "true");
                        $("input[id='pickDate_intermed_" + fields[i] + "']").attr("required", "true");
                    }
                }
            });
            $.getJSON(rootdir + 'index.php?module=aro/managearodouments&action=disablefields&ptid=' + ptid, function(data) {
                var jsonStr = JSON.stringify(data);
                obj = JSON.parse(jsonStr);
                jQuery.each(obj, function(i, val) {
                    $("input[id^='" + i + "']").val(val);
                });
                var fields = ["daysInStock", "qtyPotentiallySold"];
                for(var i = 0; i < fields.length; i++) {
                    if($("input[id='productline_" + fields[i] + "_disabled']").val() == 0) {
                        $("input[id$='" + fields[i] + "']").attr('value', '0')
                        $("input[id$='" + fields[i] + "']").attr("readonly", "true");
                        $("input[id$='shelfLife']").attr("readonly", "true");
                    }
                    else {
                        $("input[id$='" + fields[i] + "']").removeAttr("readonly");
                        $("input[id$='shelfLife']").removeAttr("readonly");
                    }
                }
                var warehousing_fields = ["warehouse", "warehousingRate", "warehousingPeriod", "warehousingTotalLoad", "uom"];
                /*
                 * Consider refactoring to avoid loop
                 */
                for(var i = 0; i < warehousing_fields.length; i++) {
                    if($("input[id='parmsfornetmargin_warehousing_disabled']").val() == 0) {
                        $("input[id='parmsfornetmargin_" + warehousing_fields[i] + "']").attr('value', '0');
                        $("input[id='parmsfornetmargin_" + warehousing_fields[i] + "']").attr("readonly", "true");
                        $("select[id='parmsfornetmargin_" + warehousing_fields[i] + "']").append('<option value="0" selected></option>');
                        $("select[id='parmsfornetmargin_" + warehousing_fields[i] + "']").attr("disabled", "true");
                    }
                    else {
                        $("input[id='parmsfornetmargin_" + warehousing_fields[i] + "']").removeAttr("readonly");
                        $("input[id='parmsfornetmargin_" + warehousing_fields[i] + "'],select[id ='parmsfornetmargin_" + warehousing_fields[i] + "']").removeAttr("disabled");
                    }
                }
            });
        }

        /* Loop over all product lines to update the numbers based on the new policy */
//        $("tbody[id^='productline_']").find($("select[id$='_quatity']")).each(function() {
//            var id = $(this).attr('id').split('_');
//            if($("input[id='product_noexception_" + id[1] + "_id_output']").val().length > 0) {
//                $(this).trigger("change");
//            }
//        });
    });
    //-----------------Populate intermediary affiliate policy-----------------------------//
    $("select[id='partiesinfo_intermed_aff']").live('change', function() {
        var ptid = $("select[id='purchasetype']").val();
        var intermedAff = $("select[id='partiesinfo_intermed_aff']").val();
        var estimatedImtermedPayment = $("input[id='pickDate_intermed_estdateofpayment']").val();
        var estimatedManufacturerPayment = $("input[id='pickDate_vendor_estdateofpayment']").val();
        sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populateintermedaffpolicy&ptid= ' + ptid + '&intermedAff=' + intermedAff + '&estimatedImtermedPayment=' + estimatedImtermedPayment + '&estimatedManufacturerPayment=' + estimatedManufacturerPayment);
//        var triggercomm = setTimeout(function() {
//            $("input[id$='_intialPrice']").trigger("change");
//        }, 2000);
    });
    //------------------------------------------------------------------------------------//
    //-----------------Get Exchang Rate  -------------------------------------------------//
    $("#currencies").live('change', function() {
        sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=getexchangerate&currency=' + $(this).val(), function(json) {
            if(json == null) {
                $("input[id='exchangeRateToUSD']").removeAttr("readonly").val('');
            }
            else {
                if(json.exchangeRateToUSD == '') {
                    $("input[id='exchangeRateToUSD']").removeAttr("readonly");
                }
                else {
                    $("input[id='exchangeRateToUSD']").attr("readonly", "true");
                }
            }
        });
    });
    //------------------------------------------------------------------------------------//
    //-------------------Get Warehouse policy parms---------------------------------------//
    $("#parmsfornetmargin_warehouse").live('change', function() {
        var warehouse = $(this).val();
        var ptid = $("#purchasetype").val();
        if(warehouse !== '' && warehouse !== typeof undefined) {
            $.getJSON(rootdir + 'index.php?module=aro/managearodouments&action=populatewarehousepolicy&warehouse= ' + warehouse + '&ptid=' + ptid, function(data) {
                var jsonStr = JSON.stringify(data);
                obj = JSON.parse(jsonStr);
                jQuery.each(obj, function(i, val) {
                    if(i === 'parmsfornetmargin_warehousingRate') {
                        var id = val.split(" ");
                        $("select[id^='" + i + "']").empty().append("<option value='" + id[0] + "' selected>" + val + "</option>");
                    }
                    else if(i === 'parmsfornetmargin_uom') {
                        var id = val.split(" ");
                        $("select[id^='" + i + "'] option[value='" + id[0] + "']").attr("selected", "selected");
                    }
                    else {
                        $("input[id^='" + i + "']").val(val);
                    }
                });
            });
        }
    });
    //------------------------------------------------------------------------------------//
    // If Inco terms are different between intermediary and vendor, freight is mandatory
    $("select[id='partiesinfo_intermed_incoterms'],select[id='partiesinfo_vendor_incoterms']").live('change', function() {
        $("input[id='partiesinfo_freight']").removeAttr("required");
        if($("select[id='partiesinfo_intermed_incoterms']").val() !== '' && $("select[id='partiesinfo_vendor_incoterms']").val() !== '') {
            if($("select[id='partiesinfo_intermed_incoterms']").val() !== $("select[id='partiesinfo_vendor_incoterms']").val()) {
                $("input[id='partiesinfo_freight']").attr("required", "true");
            }
        }
    });
    //----------------------------------------------------------------------------------------------------------------------------//
    //--------------Populate dates of PartiepickDate_estDateOfShipments Information----------------------------//
    //Trigger(s): 10A, 7, 6, 11
    $("input[id='pickDate_estDateOfShipment'],select[id='partiesinfo_intermed_paymentterm'],select[id='partiesinfo_vendor_paymentterm'],input[id='partiesinfo_intermed_ptAcceptableMargin'],#ordersummary_invoicevalue_local").live('change', function() {
        var estDateOfShipment = $("input[id='pickDate_estDateOfShipment']").val();
        var ptAcceptableMargin = $("input[id='partiesinfo_intermed_ptAcceptableMargin']").val();
        var intermedPaymentTerm = $("select[id = 'partiesinfo_intermed_paymentterm']").val();
        $("input[id='pickDate_intermed_estdateofpayment']").attr("disabled", "true");
        if(!(intermedPaymentTerm.length > 0)) {
            $("input[id='pickDate_intermed_estdateofpayment']").removeAttr("disabled");
        }
        var vendorPaymentTerm = $("select[id ='partiesinfo_vendor_paymentterm']").val();
        var ptid = $('select[id=purchasetype]').val();
        var est_local_pay = $("input[id='avgeliduedate']").val();
        var attributes = '&intermedPaymentTerm=' + intermedPaymentTerm + '&vendorPaymentTerm=' + vendorPaymentTerm + '&estDateOfShipment=' + estDateOfShipment + '&ptAcceptableMargin=' + ptAcceptableMargin + '&ptid=' + ptid + '&est_local_pay=' + est_local_pay;
        //Needed for local interest value calculation
        var localBankInterestRate = $("input[id='parmsfornetmargin_localBankInterestRate']").val();
        var totalbuyingvalue_total = 0;
        var totalbuyingvalue_total = $("input[id='ordersummary_invoicevalueusd_intermed']").val();
        if(typeof totalbuyingvalue_total != 'undefined') {
            attributes = attributes + '&totalbuyingvalue_total=' + totalbuyingvalue_total;
        }
        if(typeof localBankInterestRate != undefined) {
            attributes = attributes + '&localBankInterestRate=' + localBankInterestRate;
        }
        var intermedBankInterestRate = $("input[id='parmsfornetmargin_intermedBankInterestRate']").val();
        if(typeof intermedBankInterestRate != 'undefined') {
            attributes = attributes + '&intermedBankInterestRate=' + intermedBankInterestRate;
        }
//Update Total fees : Summation of total fees to be added to the interest value
        var totalintermedfees = 0;
        $("input[id$='freight'],input[id$='bankFees'],input[id$='insurance'],input[id$='legalization'],input[id$='courier'],input[id$='otherFees']").each(function() {
            if(!jQuery.isEmptyObject(this.value)) {
                totalintermedfees += parseFloat(this.value);
            }
        });
        attributes = attributes + '&totalintermedfees=' + totalintermedfees;
        if($(this).attr('id') == 'ordersummary_invoicevalue_local') {
            sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populate_localintvalue' + attributes);
        }
        else {
            sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populatepartiesinfofields' + attributes, function() {
                $("input[id='unitfee_btn']").trigger("click");
            });
        }
    });
    //----------------------------------------------------------------------------------------------------------------------------//
    //-------------on change of est date of sales (actual purchase) Trigger est. local invoice date------------------
    $("input[id^='pickDate_sale_']").live('change', function() {
        $("select[id^='paymentermdays_']").trigger('change');
    });
    //-------------------------------------
    //-------------------------------------------------------------------------------------//
    $("select[id^='paymentermdays_']").live('change', function() {
        var parentContainer = $(this).closest('div');
        var paymentdays = [];
        var salesdates = [];
        parentContainer.children('table').find('tr').each(function() {
            /*check if the customer is selected */
            if($(this).find("input[id^='customer_']").val() !== '') {
                $(this).find('select').each(function() {
                    if($(this).val() !== '') {
                        paymentdays.push($(this).val());
                    }
                });
            }
        });
        $("tbody[id^='actualpurchaserow_']").find("input[id^='altpickDate_sale_']").each(function() {
            if($(this).val() !== '') {
                salesdates.push($(this).val());
            }
        });
        var purchasetype = $("input[id^='purchasetype']").val();
        sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=getestimatedate&paymentermdays[]= ' + paymentdays + '&ptid= ' + purchasetype + '&salesdates[]=' + salesdates, function() {
            // $("select[id='partiesinfo_intermed_paymentterm']").trigger('change');
        });
    });
    //-------------------------------------------------------------------------------------//

    $("input[id='pickDate_estDateOfShipment'],input[id='partiesinfo_transitTime'],input[id='partiesinfo_clearanceTime']").live('change', function() {
        var transitTime = $("input[id='partiesinfo_transitTime']").val();
        var clearanceTime = $("input[id='partiesinfo_clearanceTime']").val();
        var dateOfStockEntry = $("input[id='pickDate_estDateOfShipment']").val();
        var attr = '&';
        if(typeof transitTime != undefined) {
            attr = attr + 'transitTime=' + transitTime;
        }
        if(typeof clearanceTime != undefined) {
            attr = attr + '&clearanceTime=' + clearanceTime;
        }
        if(typeof dateOfStockEntry != undefined) {
            attr = attr + '&dateOfStockEntry=' + dateOfStockEntry;
        }
        $("tbody[id^='actualpurchaserow']").find($("input[id^='pickDate_stock_']")).each(function() {
            var id = $(this).attr('id').split("_");
            sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populateactualpurchase_stockentrydate' + attr + '&rowid=' + id[2]);
        });
    });
    //-------------------------------------------------------------------------------------//
    //Calculate order summary  local interest value
//    $("input[id$='_affBuyingPrice']").live('change', function () {
//        var invoicevalue_local = invoicevalue_local_RIC = 0;
//        $("tbody[id^='productline_']").find($("input[id$='_affBuyingPrice']")).each(function () {
//            var id = $(this).attr('id').split('_');
//            invoicevalue_parameter = parseFloat($("input[id='productline_" + id[1] + "_totalBuyingValue']").val());
//            totalqty = parseFloat($("input[id='productline_" + id[1] + "_quantity']").val());
//
//            if(!isNaN(invoicevalue_parameter)) {
//                invoicevalue_local += invoicevalue_parameter;
//                if(!isNaN(totalqty)) {
//                    invoicevalue_local_RIC += (totalqty * (parseFloat($("input[id='productline_" + id[1] + "_sellingPrice']").val())));
//                }
//            }
//        });
//        sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populate_localintersetvalues&invoicevalue_local_RIC' + invoicevalue_local_RIC + '&ptid=' + $("select[id=purchasetype]").val() + '&invoicevalue_local=' + invoicevalue_local + '&exchangeRateToUSD=' + $("#exchangeRateToUSD").val());
//        $("select[id='partiesinfo_intermed_paymentterm']").trigger("change");
//    });

    //Trigger(s): 21
    var actualpurchaselines_tr;
    $("input[id$='_netMargin'],input[id$='_grossMarginAtRiskRatio']").live('change', function() {
        // var tr = setTimeout(function() {

        var invoicevalue_local = invoicevalue_local_RIC = grossMarginAtRiskRatio = 0;
        $("tbody[id^='productline_']").find($("input[id$='_affBuyingPrice']")).each(function() {
            var id = $(this).attr('id').split('_');
            invoicevalue_parameter = parseFloat($("input[id='productline_" + id[1] + "_totalBuyingValue']").val());
            totalqty = parseFloat($("input[id='productline_" + id[1] + "_quantity']").val());
            if(!isNaN(invoicevalue_parameter)) {
                invoicevalue_local += invoicevalue_parameter;
                if(!isNaN(totalqty)) {
                    invoicevalue_local_RIC += (totalqty * (parseFloat($("input[id='productline_" + id[1] + "_sellingPrice']").val())));
                }
            }
        });
        var id = $(this).attr('id').split("_");
        sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populate_localintersetvalues&invoicevalue_local_RIC' + invoicevalue_local_RIC + '&ptid=' + $("select[id=purchasetype]").val() + '&invoicevalue_local=' + invoicevalue_local + '&exchangeRateToUSD=' + $("#exchangeRateToUSD").val(), function() {
            $("select[id='partiesinfo_intermed_paymentterm']").trigger("change");
            clearTimeout(actualpurchaselines_tr);

            actualpurchaselines_tr = setTimeout(function() {
                addactualpurchaselines(id[1]);
            }, 2000);
        });

    });
    //------------------------------------------//
    $("input[id$='freight'],input[id$='bankFees'],input[id$='insurance'],input[id$='legalization'],input[id$='courier'],input[id$='otherFees']").bind('change', function() {
        var total = 0;
        $("input[id$='freight'],input[id$='bankFees'],input[id$='insurance'],input[id$='legalization'],input[id$='courier'],input[id$='otherFees']").each(function() {
            if(!jQuery.isEmptyObject(this.value)) {
                total += parseFloat(this.value);
            }
        });
        var interestvalue = $("input[id='parmsfornetmargin_interestvalue']").val();
        if(interestvalue.length > 0) {
            total += parseFloat(interestvalue);
        }
        $("input[id='partiesinfo_totalfees']").val(total).trigger("click", $("input[id='unitfee_btn']"));
//        $("input[id$='_intialPrice']").trigger("change");
//        var updateinterestvalue = setTimeout(function() {
//            $("input[id='ordersummary_btn']").trigger("click");
//        }, 2000);
    });
    //------------------------------------------//
    //-------------Disable qtyPotentiallySold if daysInStock=0 ------------------*/
    // Trigger(s): 14
//    $("input[id$='_daysInStock']").live('change keyup', function() {
//        var id = $(this).attr('id').split("_");
//        $("input[id='productline_" + id[1] + "_qtyPotentiallySold']").removeAttr("readonly");
//        if($(this).val() == 0) {
//            $("input[id='productline_" + id[1] + "_qtyPotentiallySold']").attr('value', '0');
//            $("input[id='productline_" + id[1] + "_qtyPotentiallySold']").attr("readonly", "true");
//        }
//    });
    //---------------------------------------------------------------------------//
    //------Form Submitting after 30 seconds--------------//
//    var auto_refresh = setInterval(function() {
//        submitform();
//    }, 30000);
//    function submitform() {     //Form submit function
//        $("input[id^='perform_'][id$='_Button']").trigger("click");
//    }
//---------------------------------------------------//
    //-------------If Vendor is affiliate, such select affiliate not entity and Disable  intermediary section----------------------//
    //Trigger Intermediary Aff Policy
    $("input[id='vendor_isaffiliate']").change(function() {
        $("td[id='vendor_affiliate']").css("display", "none");
        $("input[id='supplier_1_autocomplete']").attr('value', '');
        $("input[id='supplier_1_id']").attr('value', '');
        $("input[id='supplier_1_autocomplete']").removeAttr("disabled");
        var fields = ["aff", "paymentterm", "incoterms", "IncotermsDesc", "PaymentTermDesc", "ptAcceptableMargin", "promiseofpayment"];
        for(var i = 0; i < fields.length; i++) {
            $("input[id='partiesinfo_intermed_" + fields[i] + "']").removeAttr("disabled");
            $("select[id='partiesinfo_intermed_" + fields[i] + "']").removeAttr("disabled");
            $("input[id='pickDate_intermed_" + fields[i] + "']").removeAttr("disabled");
            $("select[id='partiesinfo_intermed_" + fields[i] + "'] option[value='0']").remove();
        }

        if($(this).is(":checked")) {
            var fields = ["aff", "paymentterm", "incoterms", "IncotermsDesc", "PaymentTermDesc", "ptAcceptableMargin", "promiseofpayment"];
            for(var i = 0; i < fields.length; i++) {
                $("input[id='partiesinfo_intermed_" + fields[i] + "']").attr("value", "");
                $("input[id='partiesinfo_intermed_" + fields[i] + "']").attr("disabled", "true");
                $("select[id='partiesinfo_intermed_" + fields[i] + "']").removeAttr("selected");
                $("select[id='partiesinfo_intermed_" + fields[i] + "']").append('<option value="0" selected="selected"></option>');
                $("select[id='partiesinfo_intermed_" + fields[i] + "']").attr("disabled", "true");
                $("input[id='pickDate_intermed_" + fields[i] + "']").attr("value", "");
                $("input[id='pickDate_intermed_" + fields[i] + "']").attr("disabled", "true");
                $("input[id='altpickDate_intermed_" + fields[i] + "']").attr("value", "");
            }
            $("input[id='partiesinfo_commission']").attr("value", "");
            $("input[id='supplier_1_autocomplete']").attr("disabled", "true");
            $("td[id='vendor_affiliate']").css("display", "block");
        }
        $("select[id='partiesinfo_intermed_aff']").trigger("change");
    });
    //----------------------------------------------------------------------------------------------------------------------------//

    //Trigger(s): 20A - 20B
    $("#partiesinfo_totalfees,select[id^='productline_'][id$='_uom'],input[id^='productline_'][id$='_quantity'],input[id^='productline_'][id$='_intialPrice']").live('change', function() {
        var field_id = $(this).attr('id').split('_');
        if($(this).attr('id') != 'partiesinfo_totalfees') {
            var totalamount = totalcommision = intialprice = totalqty = 0;
            $("tbody[id^='productline_']").find($("select[id$='_uom']")).each(function() {
                var id = $(this).attr('id').split('_');
                totalqty = parseFloat($("input[id='productline_" + id[1] + "_quantity']").val());
                var intialprice = parseFloat($("input[id='productline_" + id[1] + "_intialPrice']").val());
                if(!isNaN(totalqty) && !isNaN(intialprice)) {
                    totalamount += totalqty * intialprice; //reference amount
                }
            });
            comm = parseFloat($('input[id=partiesinfo_defaultcommission]').val());
            totalcommision = totalamount * (comm / 100)
            var ptid = $("select[id='purchasetype']").val();
            var totaldiscount = parseFloat($('input[id=partiesinfo_totaldiscount]').val());
            var attributes = '&totalamount=' + totalamount + '&totalcommision=' + totalcommision + '&defaultcomm=' + comm + '&ptid=' + ptid + '&totalDiscount=' + totaldiscount;
            sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=updatecommission' + attributes, function() {
                if(field_id[2] == 'intialPrice') {
                    var trigger = setTimeout(function() {
                        $('#productline_' + field_id[1] + '_grossMarginAtRiskRatio').trigger('change');
                    }, 500);
                }
            });
        }

        $('#unitfee_btn').trigger('click');
    });
    $("select[id^='productline_'][id$='_uom']").live('change', function() {
        var id = $(this).attr('id').split("_");
        /* Calculate Unit Fees*/

        //$("input[id='ordersummary_btn']").trigger("click");
    });
//    $('input[id$="_quantity"],input[id$="_intialPrice"]').live('change', function() {
//        var totalamount = totalcommision = intialprice = totalqty = 0;
//        $("tbody[id^='productline_']").find($("select[id$='_uom']")).each(function() {
//            var id = $(this).attr('id').split('_');
//            totalqty = parseFloat($("input[id='productline_" + id[1] + "_quantity']").val());
//            var intialprice = parseFloat($("input[id='productline_" + id[1] + "_intialPrice']").val());
//            if(!isNaN(totalqty) && !isNaN(intialprice)) {
//                totalamount += totalqty * intialprice; //reference amount
//            }
//        });
//        comm = parseFloat($('input[id=partiesinfo_defaultcommission]').val());
//        totalcommision = totalamount * (comm / 100)
//        var attributes = '&totalamount=' + totalamount + '&totalcommision=' + totalcommision + '&defaultcomm=' + comm;
//        sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=updatecommission' + attributes, function() {
//            $("input[id='partiesinfo_commission']").trigger("change");
//        });
//    });

    // Trigger(s): 16A - 16B - 16C ///input[id$="_intialPrice"] //#partiesinfo_commission
    $('#ordersummary_unitfee').live('change', function() {
        var attrs = '';
        $("tbody[id^='productline_']").find($("select[id$='_uom']")).each(function() {
            var id = $(this).attr('id').split('_');
            attrs = '&intialPrice=' + $("input[id='productline_" + id[1] + "_intialPrice']").val() + '&quantity=' + $("input[id='productline_" + id[1] + "_quantity']").val();
            attrs += "&commission=" + $('input[id=partiesinfo_commission]').val();
            attrs += '&unitfees=' + $("input[id='ordersummary_unitfee']").val();
            attrs += '&rowid=' + id[1] + '&ptid=' + $('select[id=purchasetype]').val();
            sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populateaffbuyingprice' + attrs, function() {
                $("input[id='ordersummary_btn']").trigger("click");
            });
        });
    });
    var fields_array = ["quantity", "qtyPotentiallySold", "intialPrice", "costPrice", "sellingPrice", "daysInStock"];
    // Trigger(s): 15A, 15B, 19, 18A
    $("input[id^='productline_'][id$='_quantity'],input[id^='productline_'][id$='_qtyPotentiallySold'],input[id^='productline_'][id$='_costPrice'],input[id$='_sellingPrice']").live('change', function() {
        /*TEST*/
        var id = $(this).attr('id').split("_");
        if(id[2] == 'costPrice') {
            $("input[id='productline_" + id[1] + "_sellingPrice']").attr("disabled", "true");
            var tr = setTimeout(function() {
                $("input[id='productline_" + id[1] + "_sellingPrice']").removeAttr("disabled");
            }, 1000);
        }


        var fields = '';
        $.each(fields_array, function(index, value) {
            fields += '&' + value + '=' + $("input[id='productline_" + id[1] + "_" + value + "']").val();
        });
        fields += '&ptid=' + $("#purchasetype").val() + '&exchangeRateToUSD=' + $("#exchangeRateToUSD").val();
        var parmsfornetmargin_fields = new Array('localPeriodOfInterest', 'localBankInterestRate', 'warehousingPeriod', 'warehousingTotalLoad', 'intermedBankInterestRate', 'intermedPeriodOfInterest');
        var parmsfornetmargin = '';
        $.each(parmsfornetmargin_fields, function(index, value) {
            parmsfornetmargin += '&' + value + '=' + $("input[id='parmsfornetmargin_" + value + "']").val();
        });
        parmsfornetmargin += '&warehousingRate=' + $("select[id='parmsfornetmargin_warehousingRate']").val();
        parmsfornetmargin += "&commission=" + $('input[id=partiesinfo_commission]').val();
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        var totalquantity = {};
        var totalqty = 0;
        var refernece = 0; //quantity*initialprice;
        $("tbody[id^='productline_']").find($("select[id$='_uom']")).each(function() {
            var id = $(this).attr('id').split('_');
            totalqty = parseFloat($("input[id='productline_" + id[1] + "_quantity']").val());
            intialprice = parseFloat($("input[id='productline_" + id[1] + "_intialPrice']").val());
            refernece += (totalqty * intialprice);
            if(!isNaN(totalqty)) {
                totalquantity[$(this).val()] = parseFloat(totalquantity[$(this).val()] || 0) + totalqty; //Fill array of qty per uom
            }
        });
        var i = 0;
        var qty = totalqtyperuom = {};
        var qtyperunit = '';
        $.each(totalquantity, function(key, value) {
            if(i !== 0) {
                qtyperunit += "_";
            }
            qty[i] = value;
            totalqtyperuom[key] = value;
            qtyperunit += key + ":" + value;
            i++;
        });
        var totalfees = $('input[id=partiesinfo_totalfees]').val();
        /******Cecking Fees**********/
//        var qtyperc = ((parseFloat($("input[id='productline_" + id[1] + "_quantity']").val()) * parseFloat($("input[id='productline_" + id[1] + "_intialPrice']").val())) / refernece) * 100;
//        if(i === 1)// if only one product line
//        {
//            var qtyperc = (parseFloat($("input[id='productline_" + id[1] + "_quantity']").val()) / parseFloat(qty[0])) * 100;
//        }
//        fees = ((qtyperc / 100) * totalfees).toFixed(3);

        var unitfees = $("input[id='ordersummary_unitfee']").val();
        var totalQtyPerUom = totalqtyperuom[$("select[id$='" + id[1] + "_uom']").val()];
        parmsfornetmargin += "&totalQty=" + totalQtyPerUom + "&localRiskRatio=" + $("input[id='parmsfornetmargin_localRiskRatio']").val() + '&unitfees=' + unitfees;
        parmsfornetmargin += "&totalDiscount=" + $("input[id='partiesinfo_totaldiscount']").val();
        sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populateproductlinefields&rowid=' + id[1] + fields + '&parmsfornetmargin=' + parmsfornetmargin, function(json) {
            //   $("input[id='unitfee_btn']").trigger("change");
            if(json["productline_" + id[1] + '_grossMarginAtRiskRatio']) {
                $('#productline_' + id[1] + '_grossMarginAtRiskRatio').trigger('change');
            }
            //$("input[id$='_netMargin']").trigger("change");
        });
    });
//    $("input[id^='productline_']").live('blur', function () {
//        var id = $(this).attr('id').split("_");
//        triggerproductlines(id);
//        addactualpurchaselines(id[1]);
//
//
////        var xxxxx = setTimeout(function() {
////            $("input[id='ordersummary_btn']").trigger("click");
////        }, 3000);
//
//    });

    //needs optimization (loop through array for fields
    $("input[id='ordersummary_btn']").click(function() {
        var totalfees = $('input[id=partiesinfo_totalfees]').val();
        var exchangeRateToUSD = $("#exchangeRateToUSD").val();
        var aff = $('select[id=affid]').val();
        var intermedAff = $("select[id='partiesinfo_intermed_aff']").val();
        attributes = '&exchangeRateToUSD=' + exchangeRateToUSD + '&intermedAff=' + intermedAff + '&aff=' + aff;
        var totalquantity = {};
        var totalfees = {};
        var totalqty = 0;
        var totalfee = 0;
        var totalamount = totalcommision = comm = invoicevalue_local = invoicevalue_local_RIC = 0;
        var invoicevalue_intermed = sellingpriceqty_product = local_netMargin = 0;
        $("tbody[id^='productline_']").find($("select[id$='_uom']")).each(function() {
            var id = $(this).attr('id').split('_');
            totalqty = parseFloat($("input[id='productline_" + id[1] + "_quantity']").val());
            if(!isNaN(totalqty)) {
                totalquantity[$(this).val()] = parseFloat(totalquantity[$(this).val()] || 0) + totalqty;
            }
            totalfee = parseFloat($("input[id='productline_" + id[1] + "_fees']").val());
            if(!isNaN(totalfee)) {
                totalfees[$(this).val()] = parseFloat(totalfees[$(this).val()] || 0) + totalfee;
            }
            var intialprice = parseFloat($("input[id='productline_" + id[1] + "_intialPrice']").val());
            if(!isNaN(totalfee) && !isNaN(intialprice)) {
                invoicevalue_intermed += (totalqty * intialprice);
            }
//s  var invoicevalue_local = invoicevalue_local_RIC = 0;
//   invoicevalue_parameter = parseFloat($("input[id='productline_" + id[1] + "_totalBuyingValue']").val());
//  if(!isNaN(invoicevalue_parameter)) {
//      invoicevalue_local += invoicevalue_parameter;
//        if(!isNaN(totalqty)) {
//           invoicevalue_local_RIC += (totalqty * (parseFloat($("input[id='productline_" + id[1] + "_sellingPrice']").val())));
//       }
//  }
            if(!isNaN(parseFloat($("input[id='productline_" + id[1] + "_netMargin']").val()))) {
                local_netMargin += parseFloat($("input[id='productline_" + id[1] + "_netMargin']").val());
            }
            if(!isNaN((parseFloat($("input[id='productline_" + id[1] + "_sellingPrice']").val()) * totalqty))) {
                sellingpriceqty_product += (parseFloat($("input[id='productline_" + id[1] + "_sellingPrice']").val()) * totalqty);
            }
            totalamount += totalqty * intialprice; //reference amount
            comm = parseFloat($('input[id=partiesinfo_defaultcommission]').val());
            totalcommision = totalamount * (comm / 100);
        });
        var i = 0;
        var qtyperunit = '';
        $.each(totalquantity, function(key, value) {
            qtyperunit += key + ":" + value.toFixed(3);
            qtyperunit += "_";
            i++;
        });
        var j = 0;
        var feeperunit = '';
        $.each(totalfees, function(key, value) {
            feeperunit += key + ":" + value.toFixed(3);
            feeperunit += "_";
            j++;
        });
        var localinvoicevalue_usd = 0;
        localinvoicevalue_usd = $("input[id='ordersummary_invoicevalueusd_local']").val();
        attributes = attributes + '&qtyperunit=' + qtyperunit + '&feeperunit=' + feeperunit + '&invoicevalue_intermed=' + invoicevalue_intermed + '&invoicevalue_local=' + invoicevalue_local + '&invoicevalue_local_RIC=' + invoicevalue_local_RIC + '&local_netMargin=' + local_netMargin;
        attributes = attributes + '&sellingpriceqty_product=' + sellingpriceqty_product + '&totalcommision=' + totalcommision + '&totalamount=' + totalamount + '&defaultcomm=' + comm;
        attributes = attributes + "&ptid=" + $('select[id=purchasetype]').val() + '&localinvoicevalue_usd=' + localinvoicevalue_usd;
        attributes = attributes + "&InterBR=" + $('input[id=parmsfornetmargin_intermedBankInterestRate]').val();
        attributes = attributes + "&POIintermed=" + $('input[id=parmsfornetmargin_intermedPeriodOfInterest]').val();
        attributes = attributes + "&intermedAff=" + $("select[id='partiesinfo_intermed_aff']").val();
        sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populateordersummary' + attributes);
    });
    //---------------------------------------
    $("input[id='unitfee_btn']").click(function() {
        var totalfeespaidbyintermed = $('input[id=partiesinfo_totalfees]').val();
        var totalquantity = {};
        var totalqty = 0;
        var totalfee = 0;
        var refernece = 0; //quantity*initialprice
        $("tbody[id^='productline_']").find($("select[id$='_uom']")).each(function() {
            var id = $(this).attr('id').split('_');
            totalqty = parseFloat($("input[id='productline_" + id[1] + "_quantity']").val());
            intialprice = parseFloat($("input[id='productline_" + id[1] + "_intialPrice']").val());
            refernece += (totalqty * intialprice);
            if(!isNaN(totalqty)) {
                totalquantity[$(this).val()] = parseFloat(totalquantity[$(this).val()] || 0) + totalqty;
            }
        });
        var i = 0;
        var feeperunit = qtyperunit = '';
        var qty = {};
        var totalfees2 = {};
        $.each(totalquantity, function(key, value) {
            qtyperunit += key + ":" + value.toFixed(3);
            qtyperunit += "_";
            qty[i] = value;
            i++;
        });
        var qtyperc = 0;
        $("tbody[id^='productline_']").find($("select[id$='_uom']")).each(function() {
            var id = $(this).attr('id').split('_');
            qtyperc = ((parseFloat($("input[id='productline_" + id[1] + "_quantity']").val()) * parseFloat($("input[id='productline_" + id[1] + "_intialPrice']").val())) / refernece) * 100;
            if(i === 1)// if only one product line
            {
                qtyperc = (parseFloat($("input[id='productline_" + id[1] + "_quantity']").val()) / parseFloat(qty[0])) * 100;
            }
            totalfee = ((qtyperc / 100) * totalfeespaidbyintermed).toFixed(3);
            if(!isNaN(totalfee) && $(this).val() != 0) {
                totalfees2[$(this).val()] = parseFloat(totalfees2[$(this).val()] || 0) + parseFloat(totalfee);
            }
            $("input[id='productline_" + id[1] + "_fees']").val(totalfee);
        });
        $.each(totalfees2, function(key, value) {
            feeperunit += key + ":" + value;
            feeperunit += "_";
        });
        attributes = '&exchangeRateToUSD=' + $("#exchangeRateToUSD").val() + '&qtyperunit=' + qtyperunit + '&feeperunit=' + feeperunit;
        sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=updateunitfee' + attributes, function() {
            $("input[id='ordersummary_unitfee']").trigger("change");
        });
    });
    //------------Total Funds Engaged-------
    $("input[id^='totalfunds_']").live('change', function() {
        var totalfunds = 0;
        $("input[id$='_orderShpInvOverdue'],input[id$='_orderShpInvNotDue'],input[id$='_ordersAppAwaitingShp'],input[id$='_odersWaitingApproval']").each(function() {
            if(!jQuery.isEmptyObject(this.value)) {
                totalfunds += parseFloat(this.value);
            }
        });
        $("input[id='totalfunds_total']").val(totalfunds);
    });
});
var rowid = '';
function addactualpurchaserow(id, callback) {
    if(rowid == '') {
        rowid = $("input[id^='numrows_actualpurchaserow_']").val();
    }

    $("input[id^='numrows_actualpurchaserow']").attr("value", id);
    $("input[id^='numrows_currentstockrow']").attr("value", id);
    sharedFunctions.ajaxAddMore($("img[id='ajaxaddmore_aro/managearodouments_actualpurchaserow_" + rowid + "']"), callback);
    sharedFunctions.ajaxAddMore($("img[id='ajaxaddmore_aro/managearodouments_currentstockrow_" + rowid + "']"));
    return true;
}

function addactualpurchaselines(id) {
    var fields = '';
    var operation = 'create';
    $("tbody[id^='productline_']").find($("input[id^='productline_" + id + "'],select[id^='productline_" + id + "']")).each(function() {
        var field = $(this).attr('id').split('_');
        if(field[2] == 'netMargin' || field[2] == 'netMarginPerc' || field[2] == 'grossMarginAtRiskRatio' || field[2] == 'totalBuyingValue') {
            return true;
        }
        if((($(this).val().length == 0) || ($(this).val() == null))) {
            fields = '';
            return false;
        }
        if(!(($(this).val().length == 0) || ($(this).val() == null))) {//&& field[2] != 'totalBuyingValue'
            var value = $(this).val();
            if(field[2] === 'inputChecksum') {
                if($("input[id='actualpurchase_" + field[1] + "_inputChecksum']").length) {
                    if($("input[id='actualpurchase_" + field[1] + "_inputChecksum']").val() == value) {
                        operation = 'update';
                    }
                }
            }
            fields = fields + "&" + field[2] + "=" + value;
        }
    });
    if(fields != '') {
        fields = fields + "&productName=" + $("input[id$='product_noexception_" + id + "_autocomplete']").val() + "&pid=" + $("input[id$='product_noexception_" + id + "_id_output']").val();
        fields = fields + "&ptid=" + $('select[id=purchasetype]').val();
        fields = fields + "&transitTime=" + $('input[id=partiesinfo_transitTime]').val();
        fields = fields + "&clearanceTime=" + $('input[id=partiesinfo_clearanceTime]').val();
        fields = fields + "&dateOfStockEntry=" + $('input[id=pickDate_estDateOfShipment]').val();
        //       var triggered = $("input[id^='productline_" + id + "_isTriggered']").val();
//        if(triggered == 1) {
//            operation == 'update';
//        }
        if(operation == 'update') {
            var bvalue = $("input[id^='productline_" + id + "_totalBuyingValue']").val();
            if(bvalue.length > 0) {
                fields = fields + "&totalBuyingValue=" + bvalue;
            }
            if($("input[id^='actualpurchase_" + id + "_inputChecksum']").length) {
                sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populateactualpurchaserow&rowid=' + id + '&fields=' + fields);
            }
            if($("input[id^='currentstock_" + id + "_inputChecksum']").length) {
                sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populatecurrentstockrow&rowid=' + id + '&fields=' + fields, function(json) {
                    $("input[id^='pickDate_sale_" + id + "']").trigger('change');
                });
            }
        } else if(operation == 'create') {
            addactualpurchaserow(id, function() {
                var bvalue = $("input[id^='productline_" + id + "_totalBuyingValue']").val();
                if(bvalue.length > 0) {
                    fields = fields + "&totalBuyingValue=" + bvalue;
                }
                sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populateactualpurchaserow&rowid=' + id + '&fields=' + fields);
                sharedFunctions.populateForm('perform_aro/managearodouments_Form', rootdir + 'index.php?module=aro/managearodouments&action=populatecurrentstockrow&rowid=' + id + '&fields=' + fields, function(json) {
                    $("input[id^='pickDate_sale_" + id + "']").trigger('change');
                });
                //$("input[id^='productline_" + id + "_isTriggered']").val(1);

            });
        }
    }
}

function triggerproductlines(id) {
    var fields_array = ["quantity", "qtyPotentiallySold", "intialPrice", "costPrice", "sellingPrice", "daysInStock", "uom"];
    if($.inArray(id[id.length - 1 ], fields_array) != -1) {
        $("tbody[id^='productline_1']").find($("input[id$='_quantity']")).each(function() {
            if(id.join('_') !== $(this).attr('id')) {
                $(this).trigger("change");
            }
        });
    }
}

function getUrlParameter(sParam)
{
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for(var i = 0; i < sURLVariables.length; i++)
    {
        var sParameterName = sURLVariables[i].split('=');
        if(sParameterName[0] == sParam)
        {
            return sParameterName[1];
        }
    }
}