<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
//    '1', // bulk actions
    db_prefix() . 'positions.id as id',
    'REPLACE(symbol, "/USD", "") as symbol',
    'transaction_type',
    db_prefix() . 'positions.status as status',
    'TRIM(`open_price`)+0 as open_price',
    'TRIM(`close_price`)+0 as close_price',
    'TRIM(`take_profit`)+0 as take_profit',
    'TRIM(`stop_loss`)+0 as stop_loss',
    'TRIM(`liquidation_price`)+0 as liquidation_price',
    'TRIM(`amount`)+0 as amount',
    'TRIM(`margin_amount`)+0 as margin_amount',
    'TRIM(`profit`)+0 as profit',
    'TRIM(`pnl_realized`)+0 as pnl_realized',
    'TRIM(`swap`)+0 as swap',
    '(case when '.db_prefix() . 'positions.is_demo = 1 then "demo" else "real" end) as is_demo',
    db_prefix().'positions.dateadded as dateadded',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'positions';

$where = [];

array_push($where, 'AND '.db_prefix().'positions.client="' . $this->ci->db->escape_str($rel_id) . '"');

$join = [
//    'LEFT JOIN ' . db_prefix() . 'accounts ON ' . db_prefix() . 'accounts.id = ' . db_prefix() . 'positions.account',
    'LEFT JOIN ' . db_prefix() . 'leads ON ' . db_prefix() . 'leads.id = ' . db_prefix() . 'positions.client',
//    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'accounts.currency',
];

$custom_fields = get_table_custom_fields('positions');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, '(SELECT value FROM ' . db_prefix() . 'customfieldsvalues WHERE ' . db_prefix() . 'customfieldsvalues.relid=' . db_prefix() . 'positions.id AND ' . db_prefix() . 'customfieldsvalues.fieldid=' . $field['id'] . ' AND ' . db_prefix() . 'customfieldsvalues.fieldto="' . $field['fieldto'] . '" LIMIT 1) as ' . $selectAs);
}

//$aColumns = hooks()->apply_filters('positions_related_table_sql_columns', $aColumns);

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [ db_prefix() . 'positions.position_id']);
//print_r($result);die();

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

//    $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['id'] . '"><label></label></div>';

//    $row[] = '<a href="' . admin_url('positions/view/' . $aRow['id']) . '" onclick="init_positions_modal(' . $aRow['id'] . '); return false;">' . $aRow['id'] . '</a>';
    $row[] = $aRow['id'];

    $row[] = render_tags($aRow['symbol']);
    $row[] = render_tags(_l($aRow['transaction_type'].'_'));
    $row[] = render_tags(_l($aRow['status'].'_'));
    $row[] = $aRow['open_price'];
    $row[] = $aRow['close_price'];
    $row[] = $aRow['take_profit'];
    $row[] = $aRow['stop_loss'];
    $row[] = $aRow['liquidation_price'];
    $row[] = $aRow['amount'];
    $row[] = $aRow['margin_amount'];
    if($aRow['is_demo'] == 'demo' && isset($profit_data_demo[$aRow['symbol']])){
        $d = array_search_id($profit_data_demo[$aRow['symbol']], 'id', $aRow['position_id']);
        $row[] = !empty($d) ? app_format_number($d[0]['profit']) : $aRow['profit'];//$profit_data_demo[$aRow['symbol']][$d[0]]['profit'] ?? $aRow['profit'];
    }elseif($aRow['is_demo'] == 'real' && isset($profit_data_real[$aRow['symbol']])){
        $r = array_search_id($profit_data_real[$aRow['symbol']], 'id', $aRow['position_id']);
        $row[] = !empty($r) ? app_format_number($r[0]['profit']) : $aRow['profit'];//$profit_data_real[$aRow['symbol']][$r[0]]['profit'] ?? $aRow['profit'];
    }else $row[] = $aRow['profit'];
    //$row[] = $aRow['is_demo'] == 'demo' ? (isset($profit_data_demo[$aRow['symbol']]) ? ($d = array_search_id($aRow['id'], $profit_data_demo[$aRow['symbol']], ['$']) ? $profit_data_demo[$aRow['symbol']][$d[0]]['profit'] : $aRow['profit']) : $aRow['profit'])
    //    : (isset($profit_data_real[$aRow['symbol']]) ? ($r = array_search_id($aRow['id'], $profit_data_real[$aRow['symbol']], ['$']) ? $profit_data_real[$aRow['symbol']][$r[0]]['profit'] : $aRow['profit']) : $aRow['profit']);
    $row[] = $aRow['pnl_realized'];
    $row[] = $aRow['swap'];

    $row[] = render_tags(_l($aRow['is_demo'].'_'));//$aRow['is_demo'] == 1 ? _l('demo_') : _l('real_');

    $row[] = _dt($aRow['dateadded']);

    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row = hooks()->apply_filters('positions_related_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}