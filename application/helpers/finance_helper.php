<?php

defined('BASEPATH') or exit('No direct script access allowed');

function init_relation_finance_table($table_attributes = [])
{
    $table_data = [
        _l('the_number_sign'),
        [
            'name'     => _l('date_'),
            'th_attrs' => [
                'style' => 'width:75px',
                'class' => 'dateadded',
            ],
        ],
        [
            'name'     => _l('account_number_'),
            'th_attrs' => [
                'style' => 'width:200px',
            ],
        ],
        _l('currency'),
        _l('amount_'),
        /*_l('currency'),*/
        _l('status_'),
        _l('type_'),
    ];

    $custom_fields = get_custom_fields('finance', [
        'show_on_table' => 1,
    ]);

    foreach ($custom_fields as $field) {
        array_push($table_data, [
            'name'     => $field['name'],
            'th_attrs' => ['data-type' => $field['type'], 'data-custom-field' => 1],
        ]);
    }

    $table_data = hooks()->apply_filters('finance_related_table_columns', $table_data);

    $name = 'rel-finance';
    if ($table_attributes['data-new-rel-type'] == 'lead') {
        $name = 'rel-finance-leads';
    }

    $table      = '';
    $CI         = &get_instance();
    $table_name = '.table-' . $name;
    $table .= render_datatable($table_data, $name, ['number-index-1'], $table_attributes);

    return $table;
}

function render_finance_status_select($statuses, $selected = '', $lang_key = '', $name = 'status', $select_attrs = [], $exclude_default = false)
{
    foreach ($statuses as $key => $status) {
        if ($status['isdefault'] == 1) {
            if ($exclude_default == false) {
                $statuses[$key]['option_attributes'] = ['data-subtext' => _l('finance_converted_to_client')];
            } else {
                unset($statuses[$key]);
            }

            break;
        }
    }

    return render_select($name, $statuses, ['id', 'name'], $lang_key, $selected, $select_attrs);
}