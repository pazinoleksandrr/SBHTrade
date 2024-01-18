<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
//    '1', // bulk actions
    db_prefix() . 'finance.id as id',
    db_prefix() . 'finance.dateadded as dateadded',
    db_prefix() . 'accounts.account_number as account_number',
    db_prefix() . 'finance.currency as currency',
    'TRIM(`amount`)+0 as amount',
    /*db_prefix() . 'currencies.name as currency',*/
    db_prefix() . 'finance.status as finance_status',
    db_prefix() . 'finance.type as finance_type',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'finance';

$where = [];

array_push($where, 'AND '.db_prefix().'finance.client="' . $this->ci->db->escape_str($rel_id) . '"');

$join = [
    'LEFT JOIN ' . db_prefix() . 'accounts ON ' . db_prefix() . 'accounts.id = ' . db_prefix() . 'finance.account',
    'LEFT JOIN ' . db_prefix() . 'leads ON ' . db_prefix() . 'leads.id = ' . db_prefix() . 'finance.client',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'accounts.currency',
];

$custom_fields = get_table_custom_fields('finance');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, '(SELECT value FROM ' . db_prefix() . 'customfieldsvalues WHERE ' . db_prefix() . 'customfieldsvalues.relid=' . db_prefix() . 'finance.id AND ' . db_prefix() . 'customfieldsvalues.fieldid=' . $field['id'] . ' AND ' . db_prefix() . 'customfieldsvalues.fieldto="' . $field['fieldto'] . '" LIMIT 1) as ' . $selectAs);
}

//$aColumns = hooks()->apply_filters('finance_related_table_sql_columns', $aColumns);

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

//    $row[] = '<a href="' . admin_url('finance/'.$aRow['finance_type'].'/' . $aRow['id']) . '" onclick="init_'.$aRow['finance_type'].'(' . $aRow['id'] . '); return false;">' . $aRow['id'] . '</a>';
    $row[] = $aRow['id'];

    $row[] = _dt($aRow['dateadded']);

    $outputName = '';

//    $outputName .= '<a href="' . admin_url('finance/account/' . $aRow['id']) . '" class="display-block main-tasks-table-href-name" onclick="init_account_modal(' . $aRow['id'] . '); return false;">' . $aRow['account_number'] . '</a>';
    $outputName .= $aRow['account_number'];

    $row[]           = $outputName;

    $row[] = render_tags($aRow['currency']);
    $row[] = $aRow['amount'];
    /*$row[] = $aRow['currency'];*/
    $row[] = render_tags(_l($aRow['finance_status'].'_'));
    $row[] = render_tags(_l($aRow['finance_type'].'_'));

    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row = hooks()->apply_filters('finance_related_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}