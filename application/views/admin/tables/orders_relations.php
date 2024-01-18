<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
//    '1', // bulk actions
    db_prefix() . 'orders.id as id',
    '(case when '.db_prefix() . 'orders.is_demo = 1 then "demo" else "real" end) as is_demo',
    db_prefix() . 'orders.dateadded as dateadded',
    db_prefix() . 'orders.order_type as order_type',
    'transaction_type',
    'REPLACE(symbol, "/USD", "") as symbol',
    'TRIM('.db_prefix() . 'orders.amount)+0 as order_amount',
    'TRIM(`margin_amount`)+0 as margin_amount',
    'TRIM(`price`)+0 as price',
    'TRIM(`take_profit`)+0 as take_profit',
    'TRIM(`stop_loss`)+0 as stop_loss',
    db_prefix() . 'orders.status as status',
    'date_expiration',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'orders';

$where = [];

array_push($where, 'AND '.db_prefix().'orders.client="' . $this->ci->db->escape_str($rel_id) . '"');

$join = [
//    'LEFT JOIN ' . db_prefix() . 'accounts ON ' . db_prefix() . 'accounts.id = ' . db_prefix() . 'orders.account',
    'LEFT JOIN ' . db_prefix() . 'leads ON ' . db_prefix() . 'leads.id = ' . db_prefix() . 'orders.client',
//    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'accounts.currency',
];

$custom_fields = get_table_custom_fields('orders');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, '(SELECT value FROM ' . db_prefix() . 'customfieldsvalues WHERE ' . db_prefix() . 'customfieldsvalues.relid=' . db_prefix() . 'orders.id AND ' . db_prefix() . 'customfieldsvalues.fieldid=' . $field['id'] . ' AND ' . db_prefix() . 'customfieldsvalues.fieldto="' . $field['fieldto'] . '" LIMIT 1) as ' . $selectAs);
}

//$aColumns = hooks()->apply_filters('orders_related_table_sql_columns', $aColumns);

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where);
//print_r($result);die();

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

//    $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['id'] . '"><label></label></div>';

//    $row[] = '<a href="' . admin_url('orders/view/' . $aRow['id']) . '" onclick="init_orders_modal(' . $aRow['id'] . '); return false;">' . $aRow['id'] . '</a>';
    $row[] = $aRow['id'];

    $row[] = render_tags(_l($aRow['is_demo'].'_'));//$aRow['is_demo'] == 1 ? _l('demo_') : _l('real_');

    $row[] = _dt($aRow['dateadded']);

    $outputName = '';

//    $outputName .= '<a href="' . admin_url('orders/view/' . $aRow['id']) . '" class="display-block main-tasks-table-href-name" onclick="init_orders_modal(' . $aRow['id'] . '); return false;">' . $aRow['account_number'] . '</a>';
    $outputName .= render_tags(_l($aRow['order_type'].'_'));

    $row[]           = $outputName;

    $row[] = render_tags(_l($aRow['transaction_type'].'_'));
    $row[] = render_tags($aRow['symbol']);
    $row[] = $aRow['order_amount'];
    $row[] = $aRow['margin_amount'];
    $row[] = $aRow['price'];
    $row[] = $aRow['take_profit'];
    $row[] = $aRow['stop_loss'];
    $row[] = render_tags(_l($aRow['status'].'_'));
    $row[] = _dt($aRow['date_expiration']);

    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row = hooks()->apply_filters('orders_related_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}