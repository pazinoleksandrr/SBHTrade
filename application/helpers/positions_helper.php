<?php

defined('BASEPATH') or exit('No direct script access allowed');

function init_relation_positions_table($table_attributes = [])
{
    $table_data = [
        _l('the_number_sign'),
        _l('symbol_'),
        _l('type_'),
        _l('status_'),
        _l('open_price_'),
        _l('close_price_'),
        _l('take_profit_'),
        _l('stop_loss_'),
        _l('liquidation_price_'),
        _l('amount_'),
        _l('margin_'),
        _l('profit_'),
        _l('pnl_'),
        _l('swap_'),
        _l('is_demo_'),
        [
            'name'     => _l('date_'),
            'th_attrs' => [
                'style' => 'width:75px',
                'class' => 'dateadded',
            ],
        ]
    ];

    $custom_fields = get_custom_fields('positions', [
        'show_on_table' => 1,
    ]);

    foreach ($custom_fields as $field) {
        array_push($table_data, [
            'name'     => $field['name'],
            'th_attrs' => ['data-type' => $field['type'], 'data-custom-field' => 1],
        ]);
    }

    $table_data = hooks()->apply_filters('positions_related_table_columns', $table_data);

    $name = 'rel-positions';
    if ($table_attributes['data-new-rel-type'] == 'lead') {
        $name = 'rel-positions-leads';
    }

    $table      = '';
    $CI         = &get_instance();
    $table_name = '.table-' . $name;
    $table .= render_datatable($table_data, $name, ['number-index-1'], $table_attributes);

    return $table;
}

if(!function_exists('fetch_profit')){
    function fetch_profit($client, $is_demo){
        $url = FRONT_URL.'/crm/get-open-positions-by-user?is_demo='.$is_demo.'&user_id='.$client->user_id;
        $key = FRONT_KEY;

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'X-API-KEY: '.$key,
                'Content-Type: application/json'
            )
        ));

        $response = curl_exec($ch);
        if($response === FALSE){
            //die(curl_error($ch));
            return [
                'status' => 'danger',
                'message' => _l('failed')
            ];
        }

        $response_data = json_decode($response, TRUE);
        //var_dump([$response, $data, $response_data, $client]);die();
        curl_close($ch);

        if((isset($response_data['error']) && $response_data['error']) || (isset($response_data['errors']) && $response_data['errors'])){
            return [
                'status' => 'danger',
                'message' => $response_data['message'] ?? ''
            ];
        }
        if(is_array($response_data) && !empty($response_data)){
            return [
                'status' => 'success',
                'message' => $response_data
            ];
        }else{
            return [
                'status' => 'danger',
                'message' => _l('failed')
            ];
        }
    }
}

if(!function_exists('array_search_id')){
    function array_search_id($array, $key, $value) {

        $results = array();

        // if it is array
        if (is_array($array)) {

            // if array has required key and value
            // matched store result
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            // Iterate for each element in array
            foreach ($array as $subarray) {

                // recur through each element and append result
                $results = array_merge($results,
                    array_search_id($subarray, $key, $value));
            }
        }

        return $results;
    }
}