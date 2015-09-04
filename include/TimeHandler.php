<?php

function get_common_free_time($events, $duration, $date_first, $date_end,
        $time_first, $time_end) {
    
    $result = array();
    $merged_events = merge_interleaved($events);
    
    
    $format = "Y-m-d H:i:s";
    $initial_DT = DateTime::createFromFormat($format, 
            $date_first . ' ' . $time_first);
    $end_DT = DateTime::createFromFormat($format, 
            $date_first . ' ' . $time_end);
    $final_DT = DateTime::createFromFormat("Y-m-d", $date_end);
    
    $next_event = array_shift($merged_events);
    $next_event_DT = new DateTime();
    if ($next_event != NULL) {
        $next_event_DT->setTimestamp($next_event["start_time"]);
    }
    $left_DT = clone $initial_DT;
    $right_DT = clone $end_DT;
    
    // stop when we reach the end of date filter
    while ($end_DT < $final_DT) {
        if ($next_event != NULL) {
            if ($next_event_DT >= $left_DT &&
                    $next_event_DT <= $right_DT) {
                if (duration_fits($left_DT, $next_event_DT, $duration)) {
                    // we found a fit, add time to result array

                    add_time($left_DT, $next_event_DT, $duration, $result);


                }
                
                // move $start to next point
                $left_DT->setTimeStamp($next_event["start_time"]);
                $interval = new DateInterval("PT{$next_event['duration']}M");
                $left_DT->add($interval);
                
                $next_event = array_shift($merged_events);
                if ($next_event != NULL) {
                    $next_event_DT->setTimestamp($next_event["start_time"]);
                }
            } else {
                if (duration_fits($left_DT, $right_DT, $duration)) {
                    
                
                    add_time($left_DT, $end_DT, $duration, $result);
                    // we need to move $initial and $end to next period
                    move_to_next_day($initial_DT, $end_DT);

                    $left_DT = clone $initial_DT;
                    $right_DT = clone $end_DT;
                }

            }
        } else {
            if (duration_fits($left_DT, $right_DT, $duration)) {
                add_time($left_DT, $end_DT, $duration, $result);
  
            }
            
            // we need to move $initial and $end to next period
            move_to_next_day($initial_DT, $end_DT);

            $left_DT = clone $initial_DT;
            $right_DT = clone $end_DT;
        }
    }
    
    return $result;
}


function add_time($left_DT, $right_DT, $duration, &$result) {
    $timestamp_left_mins = $left_DT->getTimeStamp() / 60;
    $timestamp_right_mins = $right_DT->getTimeStamp() / 60;
    
    $diff = $timestamp_right_mins - $timestamp_left_mins;
    
    $times_fit = floor($diff / $duration);
    array_push($result, array(
        "start_time" => $left_DT->getTimestamp(),
        "times_fit" => $times_fit,
        "start_time_readable" => $left_DT->format("Y-m-d H:i:s")    
        
    ));
}

function move_to_next_day($initial_DT, $end_DT) {
    $interval = new DateInterval("P1D");
    $initial_DT->add($interval);
    $end_DT->add($interval);
}

function duration_fits($left_DT, $right_DT, $duration) {
    $timestamp_left_mins = $left_DT->getTimeStamp() / 60;
    $timestamp_right_mins = $right_DT->getTimeStamp() / 60;
    
    $diff = $timestamp_right_mins - $timestamp_left_mins;
    
    if ($diff >= $duration) {
        return TRUE;
    }
    
    return FALSE;
    
}


function merge_interleaved($events) {
    $i = 0;
    $result = array();
    while ($i < count($events)) {
        
        $ev_initial = $events[$i];
        
        $i2 = $i + 1;
        
        // merge initial event with following interleaved events
        while ($i2 < count($events)) {
            
            $ev_next = $events[$i2];
            $ev_initial_start = $ev_initial["start_time"] / 60;
            $ev_initial_end = $ev_initial_start + $ev_initial["duration"];
            $ev_next_start = $ev_next["start_time"] / 60;
            $ev_next_end = $ev_next_start + $ev_next["duration"];
            
            if ($ev_next_start <= $ev_initial_end) {
                $ev_new = array();
                $ev_new["start_time"] = $ev_initial["start_time"];
                $ev_new["start_time_readable"] = $ev_initial["start_time_readable"];
                
                $new_duration = ($ev_next_end > $ev_initial_end) ? 
                        $ev_next_end - $ev_initial_start :
                        $ev_initial_end - $ev_initial_start;
                
                $ev_new["duration"] = $new_duration;
                $ev_initial = $ev_new;
                
            } else {
                break;
            }
            $i2 += 1;
            
        }
        $result[] = $ev_initial;
        $i = $i2;
    }
    return $result;
}

function generate_events($array) {
    $result = array();
    
    foreach ($array as $el) {
        $ev = array();
        $date = DateTime::createFromFormat("Y-m-d H:i:s", $el["date"]);
        $ev["start_time"] = $date->getTimestamp();
        $ev["start_time_readable"] = $date->format("Y-m-d H:i:s");
        $ev["duration"] = $el["duration"];
        
        $result[] = $ev;
        
    }
    return $result;
}

