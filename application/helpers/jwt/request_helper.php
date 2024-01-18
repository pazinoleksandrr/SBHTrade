<?php
(defined('BASEPATH')) or exit('No direct script access allowed');

/**
 * Gets the body of the current request.
 */
if(!function_exists('get_request_body')){
    function get_request_body(){
        $input = file_get_contents('php://input');
        parse_str($input, $request);
        return $request;
    }
}