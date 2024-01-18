<?php
(defined('BASEPATH')) OR exit('No direct script access allowed');

//datetime helper
if(!function_exists('add_date')){
    function add_date($year = 0, $month = 0, $week = 0, $day = 0, $hour = 0, $minute = 0, $second = 0){
        $str = '';
        if($year != 0) $str .= " +$year years";
        if($month != 0) $str .= " +$month months";
        if($week != 0) $str .= " +$week weeks";
        if($day != 0) $str .= " +$day days";
        if($hour != 0) $str .= " +$hour hours";
        if($minute != 0) $str .= " +$minute minutes";
        if($second != 0) $str .= " +$second seconds";
        return date('Y-m-d H:i:s', strtotime($str));
    }
}

if(!function_exists('is_valid_timestamp')){
    function is_valid_timestamp($timestamp): bool{
        return ((string) (int) $timestamp === $timestamp)
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }
}

if(!function_exists('is_valid_date')){
    function is_valid_date($date, $format = 'Y-m-d H:i:s'): bool{
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
}

if(!function_exists('validate_date')){
    function validate_date($string){
        if(is_valid_date($string)){
            return $string;
        }elseif(is_valid_timestamp($string)){
            return date('Y-m-d H:i:s', $string);
        }else return false;
    }
}