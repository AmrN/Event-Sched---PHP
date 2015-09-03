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
$events_readable = array(
    array("date" => '2015-29-08 01:42:00', "duration" => 30),
    array("date" => '2015-29-08 03:00:00', "duration" => 30),
    array("date" => '2015-29-08 03:20:00', "duration" => 30),
    array("date" => '2015-29-08 03:30:00', "duration" => 30),
    array("date" => '2015-29-08 05:00:00', "duration" => 30),
    array("date" => '2015-29-08 05:12:00', "duration" => 30),
);

$events = generate_events($events_readable);
$merged = merge_interleaved($events);

echo "<pre>";
print_r($events);
echo"</pre>";
echo "</br></br>";
echo "<pre>";
print_r($merged);
echo"</pre>";
