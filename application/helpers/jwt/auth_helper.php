<?php
(defined('BASEPATH')) or exit('No direct script access allowed');

// auth
/*
 * iss = issuer - service which creates token
 * aud = audience - service which uses token
 * exp = expire time
 * nbf = start time
 * iat = issued at
 */
if(!function_exists('generate_jwt')){
    function generate_jwt($person, $ip){
        $secret = SECRET_KEY;
        $c = &get_instance();
        $c->load->helper(['jwt/jwt', 'jwt/a_datetime']);
        $timestamp = date_timestamp_get(date_create());
        mt_srand(intval(substr($timestamp,-16,12)/substr(join(array_map(function ($n) { return sprintf('%03d', $n); }, unpack('C*', $secret))),0,2)));
        $stamp_validator = mt_rand();
        $data = [
            "iss" => SERVICE_NAME,
            "aud" => SPA_URL,
            "exp" => strtotime(add_date(0,0,0,0,0,60)),
            "nbf" => strtotime(NOW),
            "iat" => $timestamp,
            "chk" => $stamp_validator,
            "ip" => $ip,
            /*"os" => $os,
            "browser" => $browser,*/
            /*"username" => $person->username,*/
            "email" => $person->email,
            "sub" => $person->f_id
        ];
        $token = jwt_encode($data, $secret, 'HS512');
        return $token ?? false;
    }
}

if(!function_exists('check_jwt')){
    function check_jwt($token, $ip){
        $c = &get_instance();
        $c->load->helper('jwt/jwt');
        $secret = SECRET_KEY;
        $token_decode = (array)jwt_decode($token, $secret);
        if($token_decode["iss"] != SERVICE_NAME)
            return false;
        if($token_decode["aud"] != SPA_URL)
            return false;
        if($token_decode["exp"] <= strtotime(NOW))
            return false;
        if($token_decode["ip"] != $ip)
            return false;
        /*if($token_decode["id"] != $cookie_contents["id"])
            return false;*/
        mt_srand(intval(substr($token_decode["iat"],-16,12)/substr(join(array_map(function ($n) { return sprintf('%03d', $n); }, unpack('C*', $secret))),0,2)));
        $stamp_validator = mt_rand();
        if($stamp_validator != $token_decode["chk"])
            return false;
        if(invalidated($token))
            return false;
        return true;
    }
}

if(!function_exists('get_jwt_data')){
    function get_jwt_data($token){
        $c = &get_instance();
        $c->load->helper(['jwt/jwt']);
        $secret = SECRET_KEY;
        return (array)jwt_decode($token, $secret);
    }
}

if(!function_exists('get_header')){
    function get_header(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }elseif(isset($_SERVER['HTTP_AUTHORIZATION'])){ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }elseif(function_exists('apache_request_headers')){
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
}

if(!function_exists('get_bearer')){
    function get_bearer(){
        $headers = get_header();
        // HEADER: Get the access token from the header
        if(!empty($headers)){
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return false;
    }
}

if(!function_exists('invalidated')){
    function invalidated($token){
        $c = &get_instance();
        if(empty($c->model)) {
            $c->load->model('AuthModel', 'model');
        }
        $item = $c->model->get_item_special(['token' => $token], T_BLOCKED);
        if($item)
            return true;
        else
            return false;
    }
}

if(!function_exists('get_ip')){
    function get_ip(){
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';

        return $ipaddress;
    }
}