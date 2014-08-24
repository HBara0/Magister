<?php
require '../inc/init.php';

$users = Users::get_users('gid!=7 AND uid IN (SELECT uid FROM affiliatedemployees WHERE isMain=1 AND affid=21)', array('returnarray' => true));
foreach($users as $user) {
    unset($leaves);
//$affiliates = Affiliates::get_affiliates('affid=23', array('returnarray' => true));
//foreach($affiliates as $affiliate) {
//    $users = $affiliate->get_users(array('returnobjects' => true, 'ismain' => 1));
//    foreach($users as $user) {

    $affiliate = $user->get_mainaffiliate();

    $hr_info = $user->get_hrinfo();
    if(empty($hr_info['joinDate'])) {
        //continue;
    }

    $leaves_objs = Leaves::get_data('uid='.$user->uid.' AND type IN (1,2,4) AND fromDate>1293840000', array('returnarray' => true));
    if(is_array($leaves_objs)) {
        foreach($leaves_objs as $leave) {
            //$existing_stats = LeavesStats::get_data('uid='.$user->uid.' AND ltid='.$leave->get_type()->ltid.' AND (('.$leave->fromDate.' BETWEEN periodStart AND periodEnd) OR ('.$leave->toDate.' BETWEEN periodStart AND periodEnd))', array('returnarray' => true));
            //if(!is_array($existing_stats)) {
            $leaves[$leave->lid] = $leave->get();
            //}
        }
    }
    $currentyear_stat = Leaves::get_data('uid='.$user->uid.' AND type=1 AND fromDate>'.mktime(0, 0, 0, 1, 1, date('Y', TIME_NOW)));
    if(!is_object($currentyear_stat)) {
        $leaves[] = array('type' => 1, 'uid' => $user->uid, 'fromDate' => TIME_NOW, 'toDate' => TIME_NOW + 1, 'skipWorkingDays' => true);
    }
    if(is_array($leaves)) {
        foreach($leaves as $leave) {
            $stat = new LeavesStats();
            $stat->generate_periodbased($leave);
        }
    }
}
//require '../inc/init.php';
//require '../inc/attendance_functions.php';
//
//$options['type'] = 1;
//$options['periodStart'] = 1325368800;
//$options['periodEnd'] = 1356991140;
//$options['prevPeriodStart'] = 1293832800;
//$options['prevPeriodEnd'] = 1325368740;
//
//$query = $db->query("SELECT l.*, lt.isWholeDay
//			FROM ".Tprefix."leaves l JOIN leavetypes lt ON (lt.ltid=l.type)
//			WHERE ((fromDate BETWEEN {$options[periodStart]} AND {$options[periodEnd]}) OR (toDate BETWEEN {$options[periodStart]} AND {$options[periodEnd]})) AND (type={$options[type]} || countWith={$options[type]})
//			ORDER BY uid ASC, fromDate ASC");
//while($leave = $db->fetch_assoc($query)) {
//    if($leave['toDate'] > $options['periodEnd']) {
//        $leave['toDate'] = $options['periodEnd'];
//    }
//
//    if($leave['fromDate'] < $options['periodStart']) {
//        $leave['fromDate'] = $options['periodStart'];
//    }
//    if($db->fetch_field($db->query("SELECT COUNT(*) AS count FROM leavesapproval WHERE isApproved=0 AND lid={$leave[lid]}"), 'count') == 0) {
//        $leaves_counts[$leave['uid']] += count_workingdays($leave['uid'], $leave['fromDate'], $leave['toDate'], $leave['isWholeDay']);
//    }
//}
//
//
//$query2 = $db->query("SELECT uid, displayName FROM users WHERE gid!=7 ORDER BY displayName ASC");
//while($user = $db->fetch_assoc($query2)) {
//
//    if(!value_exists('leavesstats', 'uid', $user['uid'], "ltid={$options[type]} AND periodStart={$options[periodStart]} AND periodEnd={$options[periodEnd]}")) {
//        $newbalance = array(
//                'fromDate' => $options['periodStart'],
//                'toDate' => $options['periodEnd'],
//                'type' => $options['type'],
//                'uid' => $user['uid'],
//                'workingdays' => 0
//        );
//        echo $user['displayName'].'<br />';
//        update_leavestats_periods($newbalance, 1, false);
//        echo '<br/>';
//    }
//}
//
//$query3 = $db->query("SELECT lt.*, u.displayName
//						FROM ".Tprefix."leavesstats lt JOIN users u ON (u.uid=lt.uid)
//						WHERE ltid={$options[type]} AND periodStart={$options[periodStart]} AND periodEnd={$options[periodEnd]}");
//echo date('d M Y', $options['periodStart']).'-'.date('d M Y', $options['periodEnd']).'<br />';
//echo '<table width="100%" border="1">';
//echo '<tr><td>name</td><td>taken</td><td>actual taken</td><td>balance</td><td>actual balance</td><td>entitled for</td><td>remain prev year</td><td>remain prev year ACT</td></tr>';
//while($leavstat = $db->fetch_assoc($query3)) {
//    $cellstyle = '';
//    if($leavstat['daysTaken'] < $leaves_counts[$leavstat['uid']]) {
//        $cellstyle['taken'] = ' style="color:red;"';
//        //$db->update_query('leavesstats', array('daysTaken' => $leaves_counts[$leavstat['uid']]), 'lsid='.$leavstat['lsid']);
//    }
//    elseif($leavstat['daysTaken'] > $leaves_counts[$leavstat['uid']]) {
//        $cellstyle['taken'] = ' style="color:orange;"';
//        //$db->update_query('leavesstats', array('daysTaken' => $leaves_counts[$leavstat['uid']]), 'lsid='.$leavstat['lsid']);
//    }
//
//    $prevbalance = $db->fetch_assoc($db->query("SELECT lt.*, u.displayName
//					FROM ".Tprefix."leavesstats lt JOIN users u ON (u.uid=lt.uid)
//					WHERE ltid={$options[type]} AND periodStart={$options[prevPeriodStart]} AND periodEnd={$options[prevPeriodEnd]} AND lt.uid={$leavstat[uid]}"));
//
//    if($leavstat['remainPrevYear'] < ($prevbalance['canTake'] - $prevbalance['daysTaken'])) {
//        $cellstyle['prevyear'] = ' style="color:red;"';
//        //$db->update_query('leavesstats', array('remainPrevYear' => ($prevbalance['canTake']-$prevbalance['daysTaken'])), 'lsid='.$leavstat['lsid']);
//    }
//    elseif($leavstat['remainPrevYear'] > ($prevbalance['canTake'] - $prevbalance['daysTaken'])) {
//        $cellstyle['prevyear'] = ' style="color:orange;"';
//        //$db->update_query('leavesstats', array('remainPrevYear' => ($prevbalance['canTake']-$prevbalance['daysTaken'])), 'lsid='.$leavstat['lsid']);
//    }
//
//    echo '<tr>';
//    echo '<td>'.$leavstat['displayName'].'</td><td>'.$leavstat['daysTaken'].'</td><td'.$cellstyle['taken'].'>'.$leaves_counts[$leavstat['uid']].'</td><td>'.($leavstat['canTake'] - $leavstat['daysTaken']).'</td><td>'.($leavstat['canTake'] - $leaves_counts[$leavstat['uid']]).'</td><td>'.$leavstat['entitledFor'].'</td><td>'.$leavstat['remainPrevYear'].'</td><td'.$cellstyle['prevyear'].'>'.($prevbalance['canTake'] - $prevbalance['daysTaken']).'</td>';
//    echo '</tr>';
//}
//echo '</table>';
?>