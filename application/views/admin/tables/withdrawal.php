<?php

defined('BASEPATH') or exit('No direct script access allowed');

$has_permission_edit = has_permission('finance', '', 'edit_withdrawal');
$has_permission_delete = has_permission('finance', '', 'delete_withdrawal');
$custom_fields         = get_table_custom_fields('finance');
//$statuses              = $this->ci->finance_model->get_status();
//$statuses = [['id' => 'completed', 'name' => _l('completed_')], ['id' => 'in_process', 'name' => _l('in_process_')]];

$aColumns = [
    db_prefix() . 'finance.id as id',
    db_prefix() . 'finance.dateadded as dateadded',
    db_prefix() . 'leads.name as full_name',
    db_prefix() . 'leads.email as email',
    db_prefix() . 'accounts.account_number as account_number',
    'concat('.db_prefix() . 'staff.firstname, " ", '.db_prefix() . 'staff.lastname) as assignee',
    '(SELECT GROUP_CONCAT(value SEPARATOR ",") FROM ' . db_prefix() . 'customfieldsvalues JOIN ' . db_prefix() . 'customfields ON ' . db_prefix() . 'customfieldsvalues.fieldid = ' . db_prefix() . 'customfields.id WHERE relid=' . db_prefix() . 'leads.assigned AND ' . db_prefix() . 'customfields.fieldto="staff" and ' . db_prefix() . 'customfields.name="Department" and ' . db_prefix() . 'customfields.slug="staff_department" ORDER by value ASC) as department',
    db_prefix() . 'finance.currency as currency',
    db_prefix() . 'finance.amount as amount',
    db_prefix() . 'finance.status as status',
//    db_prefix() . 'finance.type as type',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'finance';

$join = [
    'LEFT JOIN ' . db_prefix() . 'leads ON ' . db_prefix() . 'finance.client = ' . db_prefix() . 'leads.id',
    'LEFT JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'leads.assigned',
    'LEFT JOIN ' . db_prefix() . 'accounts ON ' . db_prefix() . 'accounts.id = ' . db_prefix() . 'finance.account and ' . db_prefix() . 'accounts.is_demo = 0',
];

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'finance.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

$starter = $this->ci->input->post('search')['value'] != '' ? ' and ' : ' where ';
$where  = [$starter.db_prefix() . 'finance.type = "withdrawal" AND deleted = 0 AND blocked = 0'];
$filter = false;

if (has_permission('leads', '', 'view') && $this->ci->input->post('client')) {
    array_push($where, 'AND ' . db_prefix() . 'finance.client =' . $this->ci->db->escape_str($this->ci->input->post('client')));
}

if (has_permission('finance', '', 'view') && $this->ci->input->post('assigned')) {
    array_push($where, 'AND ' . db_prefix() . 'leads.assigned =' . $this->ci->db->escape_str($this->ci->input->post('assigned')));
}

if ($this->ci->input->post('status')
    && count($this->ci->input->post('status')) > 0) {
    array_push($where, 'AND ' . db_prefix() . 'finance.status IN ("' . implode('","', $this->ci->db->escape_str($this->ci->input->post('status'))) . '")');
}

if ($this->ci->input->post('from_date')) {
    array_push($where, 'AND '.db_prefix().'finance.dateadded > "'.$this->ci->db->escape_str($this->ci->input->post('from_date')).'"');
}

if ($this->ci->input->post('to_date')) {
    array_push($where, 'AND '.db_prefix().'finance.dateadded < "'.$this->ci->db->escape_str($this->ci->input->post('to_date')).'"');
}

if ($this->ci->input->post('currency')
    && count($this->ci->input->post('currency')) > 0) {
    array_push($where, 'AND '.db_prefix() . 'finance.currency IN ("' . implode('","', $this->ci->db->escape_str($this->ci->input->post('currency'))) . '")');
}

$where_clients = '';
$staff_ids = [];
if(!is_admin() && !has_permission('leads', '', 'view')){
    $staff_id = get_staff_user_id();
    $this->ci->load->model('staff_model');
    $staff = $this->ci->staff_model->get_with_role($staff_id);
    if(isset($staff->role_name)){
        if($staff->role_name == 'Lead'){
            $staff_ids = $this->ci->staff_model->get_staff_ids_by_lead_id($staff_id);
            if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
            $where_clients = 'AND assigned in ('.implode(',', $staff_ids).')';
        }
        if($staff->role_name == 'Sales'){
            $where_clients = 'AND (assigned ='.$staff_id.')';
        }
    }
}
if (!has_permission('leads', '', 'view')) {
    //array_push($where, 'AND (' . db_prefix() . 'leads.assigned =' . get_staff_user_id() . ')');
    if($this->ci->input->post('assigned') && in_array($this->ci->input->post('assigned'), $staff_ids)) array_push($where, 'AND assigned =' . $this->ci->db->escape_str($this->ci->input->post('assigned')));
    $statement = $where_clients == '' ? 'AND (assigned =' . get_staff_user_id() . ')' : $where_clients;
    $where[] = $statement;
}

$aColumns = hooks()->apply_filters('withdrawal_table_sql_columns', $aColumns);

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$additionalColumns = hooks()->apply_filters('leads_table_additional_columns_sql', [
    db_prefix() . 'leads.assigned',
    db_prefix() . 'finance.addedfrom',
    db_prefix() . 'finance.client',
]);

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalColumns);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $hrefAttr = 'href="' . admin_url('finance/withdrawal/' . $aRow['id']) . '" onclick="init_withdrawal(' . $aRow['id'] . ');return false;"';
    $nameRow  = '<a ' . $hrefAttr . '>' . $aRow['id'] . '</a>';
    $nameRow .= '<div class="row-options">';
    $nameRow .= '<a ' . $hrefAttr . '>' . _l('view') . '</a>';

    if ($has_permission_edit) {
        $nameRow .= ' | <a href="' . admin_url('finance/withdrawal/' . $aRow['id'] . '?edit=true') . '" onclick="init_withdrawal(' . $aRow['id'] . ', true);return false;">' . _l('edit') . '</a>';
    }

    if ($aRow['addedfrom'] == get_staff_user_id() || $has_permission_delete) {
        $nameRow .= ' | <a href="' . admin_url('finance/delete_withdrawal/' . $aRow['id']) . '" class="_delete text-danger">' . _l('delete') . '</a>';
    }
    $nameRow .= '</div>';

    $row[] = $nameRow;

//    $row[] = _d($aRow['dateadded']);
    $row[] = '<span data-toggle="tooltip" data-title="' . _dt($aRow['dateadded']) . '" class="text-has-action is-date">' . time_ago($aRow['dateadded']) . '</span>';

    $row[] = '<a href="' . admin_url('leads/index/' . $aRow['client']) . '">' . $aRow['full_name'] . '</a>';

    $row[] = ($aRow['email'] != '' ? '<a href="mailto:' . $aRow['email'] . '">' . $aRow['email'] . '</a>' : '');

    $row[] = $aRow['account_number'];

    $assignedOutput = '';
    if ($aRow['assignee'] != '') {
        /*$assignedOutput = '<a data-toggle="tooltip" data-title="' . $full_name . '" href="' . admin_url('profile/' . $aRow['assigned']) . '">' . staff_profile_image($aRow['assigned'], [
            'staff-profile-image-small',
            ]) . '</a>';*/
        $assignedOutput = format_members_by_ids_and_names_full_name($aRow['assigned'], $aRow['assignee']);

        // For exporting
        $assignedOutput .= '<span class="hide">' . $aRow['assignee'] . '</span>';
    }

    $row[] = $assignedOutput;

    $row[] = render_tags($aRow['department']);

    $row[] = render_tags($aRow['currency']);
    $row[] = $aRow['amount'];
    $row[] = _l($aRow['status'].'_');

    // Custom fields add values
    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row['DT_RowId'] = 'finance_' . $aRow['id'];

    if ($aRow['assigned'] == get_staff_user_id()) {
        $row['DT_RowClass'] = 'info';
    }

    if (isset($row['DT_RowClass'])) {
        $row['DT_RowClass'] .= ' has-row-options';
    } else {
        $row['DT_RowClass'] = 'has-row-options';
    }

    $row = hooks()->apply_filters('withdrawal_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}