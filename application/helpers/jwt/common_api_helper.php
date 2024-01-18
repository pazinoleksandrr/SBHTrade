<?php
(defined('BASEPATH')) or exit('No direct script access allowed');

if(!function_exists('api_form')){
    function api_form($data, $to){
        $return_data = [];
        $error = false;
        foreach($to as $key){
            if(isset($data[$key]) && $data[$key] != ''){
                $return_data[$key] = trim($data[$key]);
            }else{
                $error = true;
            }
        }
        return $error ? false : $return_data;
    }
}

if(!function_exists('api_form_nr')){
    function api_form_nr($data, $to){
        $return_data = [];
        foreach($to as $key){
            if(isset($data[$key]) && $data[$key] != ''){
                $return_data[$key] = trim($data[$key]);
            }
        }
        return $return_data;
    }
}

if(!function_exists('api_output')){
    function api_output($long_timeout, $type = '', $message = '', $translate = true, $data = false, $token = ''){
        $return_data = [];
        /*$return_data['is_long'] = $long_timeout;
        $return_data['is_active'] = $translate;*/
        if($type != '') $return_data['type'] = $type;
        if($message != '') $return_data['message'][] = $translate ? _l($message) : $message;//translate($message, 'return') : $message;
        if($data) $return_data['data'] = $data;
        if($token != '') $return_data['token'] = $token;
        if(!empty($return_data)) return json_encode($return_data);
        else return false;
//        {
//            success: true,
//            type: 'success',
//            typeDetail: 'successEmail',
//            link: false,
//            longTime: true,
//            isActive: true,
//            linkDetail: '',
//            linkMessage: '',
//            messages: [
//                "message to display"
//            ]
//        }
    }
}

if(!function_exists('api_output_s')){
    function api_output_s($long_timeout, $string, $type = '', $message = '', $translate = true, $data = [], $token = ''){
        $return_data = [];
        /*$return_data['is_long'] = $long_timeout;
        $return_data['is_active'] = $translate;*/
        if($type != '') $return_data['type'] = $type;
        if($message != '') $return_data['message'][] = $translate ? _l($message, $string) : $message;//translate_special($message, $string, 'return') : $message;
        if(!empty($data)) $return_data['data'] = $data;
        if($token != '') $return_data['token'] = $token;
        if(!empty($return_data)) return json_encode($return_data);
        else return false;
    }
}

if(!function_exists('api_method_validate')){
    function api_method_validate($method){
        return strcasecmp($_SERVER['REQUEST_METHOD'], $method) != 0;
    }
}

if(!function_exists('api_content_type_validate')){
    function api_content_type_validate($content_type_special = '', $explode = false){
        $content_type = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        if($content_type_special !== ''){
            $content_type_string = explode(' ', $content_type);
            if($explode) return (strcasecmp($content_type_string[0], $content_type_special) != 0);
            else return (strcasecmp($content_type, $content_type_special) != 0);
        }else
            return (strcasecmp($content_type, CONTENT_TYPE) != 0);
    }
}

if(!function_exists('api_response_clear')){
    function api_response_clear($data, $fields = []){
        if(is_array($data)) foreach($fields as $f){
            unset($data[$f]);
        }
        if(is_object($data)) foreach($fields as $f){
            unset($data->$f);
        }
        return $data;
    }
}

if(!function_exists('api_response_only')){
    function api_response_only($data, $fields = []){
        is_object($data) ? $return = new stdClass() : $return = [];
        if(is_array($data)) foreach($fields as $k => $f){
            $return[$k] = $data[$f];
        }
        if(is_object($data)) foreach($fields as $f){
            $return->$f = $data->$f;
        }
        return $return;
    }
}

if(!function_exists('clear_array')){
    function clear_array($arr){
        $return = [];
        foreach ($arr as $k => $v){
            if($v == '') $return[$k] = '';
            elseif($v == null) $return[$k] = null;
            else $return[$k] = htmlspecialchars(trim($v));
            //$return[$k] = ($v != null && $v != '') ? htmlspecialchars(trim($v)) : null;
        }
        return $return;
    }
}