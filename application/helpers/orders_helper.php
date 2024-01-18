<?php

defined('BASEPATH') or exit('No direct script access allowed');

function init_relation_orders_table($table_attributes = [])
{
    $table_data = [
        _l('the_number_sign'),
        _l('account_type_'),
        [
            'name'     => _l('date_'),
            'th_attrs' => [
                'style' => 'width:75px',
                'class' => 'dateadded',
            ],
        ],/*
        [
            'name'     => _l('account_number_'),
            'th_attrs' => [
                'style' => 'width:200px',
            ],
        ],*/
        _l('order_type_'),
        _l('transaction_type_'),
        _l('symbol_'),
        _l('amount_'),
        _l('margin_'),
        _l('price_'),
        _l('tp_'),
        _l('sl_'),
        _l('status_'),
        _l('date_expiration_'),
    ];

    $custom_fields = get_custom_fields('orders', [
        'show_on_table' => 1,
    ]);

    foreach ($custom_fields as $field) {
        array_push($table_data, [
            'name'     => $field['name'],
            'th_attrs' => ['data-type' => $field['type'], 'data-custom-field' => 1],
        ]);
    }

    $table_data = hooks()->apply_filters('orders_related_table_columns', $table_data);

    $name = 'rel-orders';
    if ($table_attributes['data-new-rel-type'] == 'lead') {
        $name = 'rel-orders-leads';
    }

    $table      = '';
    $CI         = &get_instance();
    $table_name = '.table-' . $name;
    $table .= render_datatable($table_data, $name, ['number-index-1'], $table_attributes);

    return $table;
}