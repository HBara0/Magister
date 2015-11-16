<?php
/*
 * Copyright © 2015 Orkila International Offshore, All Rights Reserved
 *
 * [Provide Short Descption Here]
 * $id: reporting_auditorperformance.php
 * Created:        @hussein.barakat    11-Nov-2015 | 15:29:46
 * Last Update:    @hussein.barakat    11-Nov-2015 | 15:29:46
 */
require '../inc/init.php';
$lang = new Language('english', 'user');
$lang->load('global');
$lang->load('reporting_meta');
$data = ReportingQr::auditor_ratings();
$quarter = currentquarter_info(true);
if($quarter['quarter'] == 1) {
    $quarter['quarter'] = 4;
    $quarter['year'] = $quarter['year'] - 1;
}
else {
    $quarter['quarter'] --;
}
$outputmessage = '<div style="width:100%;text-align:center"><h2>Quarterly Report Auditors Performance Report : Q'.$quarter['quarter'].'/'.$quarter['year'].'</h2></div>';
if(is_array($data)) {
    foreach($data as $uid => $coordata) {
        $totalreports = 0;
        $supsids = array();
        $coord_obj = new Users($uid);
        $unfsupsids = array();
        $finsupsids = array();
        $unflate = $finlate = 0;
        if(is_array($coordata)) {
            foreach($coordata as $supplierid => $numbers) {
                if($supplierid == 'count') {
                    continue;
                }
                if(is_array($numbers)) {
                    foreach($numbers as $type => $offsetdata) {
                        $affiliate = new Affiliates($type);

                        $maxfin = $maxdue = 0;
                        if(is_array($offsetdata)) {
                            foreach($offsetdata as $offsettype => $offset) {
                                if($offsettype == 'remaining') {
                                    $color = 'green';
                                    $symbol = '&#x2713;';
                                    $linestatus = $lang->remaining;
                                    if($offset < 0) {
                                        if(round(-$offset / 60 / 60 / 24) > 60) {
                                            continue;
                                        }
                                        if($maxdue > $offset) {
                                            $maxdue = round(-$offset / 60 / 60 / 24);
                                        }
                                        $offset = -$offset;
                                        $linetitle = $lang->notfinalized;
                                        $color = 'red';
                                        $linestatus = $lang->unflateby;
                                        $symbol = '&#x2717;';
                                        if(!in_array($supplierid, $unfsupsids)) {
                                            $unflate ++;
                                            $unfsupsids[] = $supplierid;
                                        }
                                    }
                                    $offset_output = round($offset / 60 / 60 / 24);
                                    $listitems_unfin .= '<tr><td style="text-align:center"><span style="font-family:bold;color:'.$color.'">'.$symbol.'</span>'.$affiliate->get_displayname().' </td><td> '.$linestatus.' '.$offset_output.' days</td></tr>';
                                }
                                else {
                                    $color = 'green';
                                    $symbol = '&#x2713;';
                                    $linestatus = $lang->finalizedearlyby;
                                    if($offset > 0) {
                                        if($maxfin < $offset) {
                                            $maxfin = round($offset / 60 / 60 / 24);
                                        }
                                        $linetitle = $lang->finalized;
                                        $color = 'red';
                                        $linestatus = $lang->finalizelateby;
                                        $symbol = '&#x2717;';
                                        if(!in_array($supplierid, $finsupsids)) {
                                            $finlate++;
                                            $finsupsids[] = $supplierid;
                                        }
                                    }
                                    else {
                                        $offset = -$offset;
                                    }
                                    $offset_output = round($offset / 60 / 60 / 24);
                                    $listitems_fin .= '<tr><td style="text-align:center"><span style="font-family:bold;color:'.$color.'">'.$symbol.'</span>'.$affiliate->get_displayname().' </td><td> '.$linestatus.' '.$offset_output.' days</td></tr>';
                                }
                                unset($offset_output, $linestatus);
                            }
                        }
                    }
                }
                if(!in_array($supplierid, $supsids)) {
                    $supplier = new Entities($supplierid);
                    $totalreports ++;
                    $supsids[] = $supplierid;
                    if($maxdue != 0) {
                        $supstatus = 'Report Still Unfinalized : '.$maxdue.' days remaining';
                        $suppoutput .= $listitems_unfin;
                    }
                    elseif($maxfin != 0) {
                        $supstatus = 'Finalized : '.$maxfin.' days late from the 15th of the month';
                        $suppoutput .= $listitems_fin;
                    }
                    else {
                        $supstatus = 'Finalized And Sent On Time';
                    }
                    $backcolor = 'green';
                    if($avgscore < 3) {
                        $backcolor = 'F04122';
                    }
                    $supplieroutput .= '<tr><th style="border-left: 1px solid black;float:right;width:75%;background-color:'.$backcolor.';color:#f8ffcc"> '.$supplier->get_displayname().'</th><td style="border: 1px solid #CCC;float:left;width:75%;background-color:'.$backcolor.';color:#f8ffcc"> '.$supstatus.'</div></td></tr>';
                    $supplieroutput .= $suppoutput;
                }
                unset($supplier, $backcolor, $suppoutput, $supstatus, $listitems_unfin, $listitems_fin);
            }
        }
        if($unflate > 0) {
            $auditorstatus_un = $unflate;
            $totalundone = $unflate;
        }
        if($finlate > 0) {
            $auditorstatus_fin = $finlate;
            $totalundone += $finlate;
        }
        if($totalundone > 0) {
            if($totalreports = $totalundone) {
                $avgscore = 0;
            }
            else {
                $avgscore = 5 - (($totalundone * 5 ) / $totalreports);
            }
        }
        else {
            $avgscore = 5;
        }
        $avg_color = 'green';
        if($avgscore < 3) {
            $avg_color = '#F04122';
        }
        $outputmessage .= '<table style="border-collapse: collapse;border-spacing: 0; " width="75%" align="center" ><thead ><tr style="border:1px solid black;"><th style="height:30px;color: white;background-color:'.$avg_color
                .';text-align:left" width="60%">'.$coord_obj->get_displayname().'</th><th style="color: white;background-color: '.$avg_color
                .';"><span style=" font-size:25%;  vertical-align: baseline; border-radius: .20em; white-space: nowrap;font-weight: 700;padding: .2em .4em '
                .'.3em;height:15px;font-size: 15px;color:'.$avg_color.'; background-color: #f8ffcc;text-align: center">'.$avgscore
                .'</span> / 5</th></tr></thead>';
        $outputmessage.='<tr style="border:1px solid black;border-bottom: 2px double black"><td> '.$lang->totalreports.'</td><td> '.$totalreports.' </td></tr><tr style="border:1px solid black;"><td>Unfinalized And Late</td><td>'.$auditorstatus_un
                .'</td></tr><tr style="border:1px solid black;"><td>Sent Late </td><td style="border:1px solid black;">'.$auditorstatus_fin.'</td></tr>';
        $outputmessage.=$listitems.$supplieroutput.'</table><hr>';
        $outputmessage.='';
        $auditorstatus_fin = $auditorstatus_un = 0;
        unset($supplieroutput, $avg_color, $avgscore, $totalundone);
    }
}
print($outputmessage);
exit;
