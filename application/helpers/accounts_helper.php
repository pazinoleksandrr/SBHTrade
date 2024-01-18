<?php

defined('BASEPATH') or exit('No direct script access allowed');

function init_relation_accounts_table($table_attributes = [])
{
    $table_data = [
        _l('the_number_sign'),
        _l('account_number_'),
        _l('account_type_'),
        /*[
            'name'     => _l('date_'),
            'th_attrs' => [
                'style' => 'width:75px',
                'class' => 'dateadded',
            ],
        ],*/
        _l('balance_'),
        _l('available_balance_'),
        _l('frozen_balance_'),
        _l('currency'),
    ];

    $custom_fields = get_custom_fields('accounts', [
        'show_on_table' => 1,
    ]);

    foreach ($custom_fields as $field) {
        array_push($table_data, [
            'name'     => $field['name'],
            'th_attrs' => ['data-type' => $field['type'], 'data-custom-field' => 1],
        ]);
    }

    $table_data = hooks()->apply_filters('accounts_related_table_columns', $table_data);

    $name = 'rel-accounts';
    if ($table_attributes['data-new-rel-type'] == 'lead') {
        $name = 'rel-accounts-leads';
    }

    $table      = '';
    $CI         = &get_instance();
    $table_name = '.table-' . $name;
    $table .= render_datatable($table_data, $name, ['number-index-1'], $table_attributes);

    return $table;
}