<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require dirname(__FILE__) . "/../include/DbConnect.php";
require dirname(__FILE__) . "/../include/PassHash.php";
require dirname(__FILE__) . "/../include/DbHandler.php";
require dirname(__FILE__) . "/../include/TimeHandler.php";
$dbHandler = new DbHandler();

//$dbHandler->createUser("someone", "someon3@gmail.com", "123123", "M");

//$dbHandler->createUser("amr", "amr@gmail.com", "123123", "M");
//$res = $dbHandler->hasMultiUserPriv(6, array(7, 4));
//$res = $dbHandler->getPrivForUser(4);
//$res = $dbHandler->addCommentToEvent(3, 6, "this is a comment 2 made by amr on event 3");
//$res = $dbHandler->isUserBelongsToEvent(4, 3);
//$res = $dbHandler->getCommentByID(2);
//$res = $dbHandler->getCommentsForEvent(3);
//$res = $dbHandler->getEventsInTimeRange(array(7), '2010-04-20', '2010-06-01', '05:00:00', '06:00:00');
//$res = new DateTime();
//$res = DateTime::createFromFormat("Y-m-d H:i:s", '1999-02-01 14:43:01');

//$events_readable = array(
//    array("date" => '2015-08-26 01:00:00', "duration" => 30),
//    array("date" => '2015-08-28 01:30:00', "duration" => 30),
//    array("date" => '2015-08-29 03:20:00', "duration" => 30),
//    array("date" => '2015-08-29 03:30:00', "duration" => 60),
//  
//);
//


//        $events = $dbHandler->getEventsInTimeRange(array(7,6), '2015-09-22', 
//                '2015-09-25', '06:00:00', '09:00:00');
//        
//        $result = get_common_free_time($events, 60,
//                '2015-09-22', '2015-09-25', '06:00:00', '09:00:00');
   
      $date = DateTime::createFromFormat("Y-m-d H:i:s", '2015-09-22 06:00:00');
        $ev["start_time"] = $date->getTimestamp();
        $ev["start_time_readable"] = $date->format("Y-m-d H:i:s");

//
//$events = generate_events($events_readable);
//
//
//$date1 = new DateTime();
//$date1->setTimestamp($events[0]["start_time"]);
//
//$date2 = new DateTime();
//$date2->setTimestamp($events[1]["start_time"]);
//
//$res = get_common_free_time($events, 20, '2015-08-20', '2015-08-30', '01:00:00', '04:00:00');
//
//echo $date1->getTimestamp();
echo "<pre>";
print_r($ev);
//echo "</br>";
//print_r($result);
echo"</pre>";