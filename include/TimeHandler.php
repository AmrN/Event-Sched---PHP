<?php

function get_common_free_time($events, $duration, $date_first, $date_end,
        $time_first, $time_end) {
    
    $result = array();
    $merged_events = merge_interleaved($events);
    
    
    $format = "Y-m-d H:i:s";
    $initial = DateTime::createFromFormat($format, 
            $date_first . ' ' . $time_first);
    $end = DateTime::createFromFormat($format, 
            $date_first . ' ' . $time_end);
    
    
    $next_event = $merged_events->unshift();
    while ($next_event != NULL) {
        if ($next_event["start_time"] > $initial &&
                $next_event["start_time"] < $end) {
            if (duration_fits($initial, $next_event, $duration)) {
                // we found a fit, add time to result array
                
                
                
                
            } else {
                // move $initial to next point
                $initial = $next_event["start_time"] + $next_event["duration"];
            }
            $next_event = $merged_events->unishift();
        } else {
            // we need to move $initial and $end to next period
            
        }
    }
    
    
    
    
    
    
    
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
        $ev["duration"] = $el["duration"];
        $result[] = $ev;
    }
    return $result;
}

