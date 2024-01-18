<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
//    '1', // bulk actions
    db_prefix() . 'accounts.id as id',
    db_prefix() . 'accounts.account_number as account_number',
    '(case when '.db_prefix() . 'accounts.is_demo = 1 then "demo" else "real" end) as is_demo',
//    db_prefix() . 'accounts.dateadded as dateadded',
    'TRIM(`balance`)+0 as balance',
    'TRIM(`available_balance`)+0 as available_balance',
    'TRIM(`frozen_balance`)+0 as frozen_balance',
    db_prefix() . 'accounts.currency as currency',
    //db_prefix() . 'currencies.name as currency',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'accounts';

$where = [];

array_push($where, 'AND '.db_prefix().'accounts.client="' . $this->ci->db->escape_str($rel_id) . '"');

$join = [
//    'LEFT JOIN ' . db_prefix() . 'accounts ON ' . db_prefix() . 'accounts.id = ' . db_prefix() . 'accounts.account',
    'LEFT JOIN ' . db_prefix() . 'leads ON ' . db_prefix() . 'leads.id = ' . db_prefix() . 'accounts.client',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'accounts.currency',
];

$custom_fields = get_table_custom_fields('accounts');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, '(SELECT value FROM ' . db_prefix() . 'customfieldsvalues WHERE ' . db_prefix() . 'customfieldsvalues.relid=' . db_prefix() . 'accounts.id AND ' . db_prefix() . 'customfieldsvalues.fieldid=' . $field['id'] . ' AND ' . db_prefix() . 'customfieldsvalues.fieldto="' . $field['fieldto'] . '" LIMIT 1) as ' . $selectAs);
}

//$aColumns = hooks()->apply_filters('accounts_related_table_sql_columns', $aColumns);

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

//    $row[] = '<a href="' . admin_url('accounts/view/' . $aRow['id']) . '" onclick="init_accounts_modal(' . $aRow['id'] . '); return false;">' . $aRow['id'] . '</a>';
    $row[] = $aRow['id'];

    $row[] = $aRow['account_number'];

//    $row[] = _dt($aRow['dateadded']);

    /*$outputName = '';

//    $outputName .= '<a href="' . admin_url('accounts/view/' . $aRow['id']) . '" class="display-block main-tasks-table-href-name" onclick="init_accounts_modal(' . $aRow['id'] . '); return false;">' . $aRow['account_number'] . '</a>';
    $outputName .= $aRow['type'];

    $row[]           = $outputName;*/
    $row[] = render_tags(_l($aRow['is_demo'].'_'));

    $row[] = $aRow['balance'];
    $row[] = $aRow['available_balance'];
    $row[] = $aRow['frozen_balance'];
    $row[] = $aRow['currency'];

    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row = hooks()->apply_filters('accounts_related_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}