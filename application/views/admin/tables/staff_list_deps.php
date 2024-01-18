<?php

defined('BASEPATH') or exit('No direct script access allowed');

$cfv = @$this->ci->db->query('select * from '.db_prefix().'customfieldsvalues where id = '.$rel_id)->row();

$dep_name = $cfv ? $cfv->value : $rel_type;

$staffs = @$this->ci->db->select(db_prefix().'customfieldsvalues.relid')->where(['value' => $dep_name])->from(db_prefix().'customfieldsvalues')->get()->result_array();
if(!empty($staffs)) $staffs = array_map('implode', $staffs);

$aColumns = [
    'concat('.db_prefix() . 'staff.firstname, " ", '.db_prefix() . 'staff.lastname) as full_name',
    db_prefix() . 'staff.email as email',
    'clients.sum_c as clients',
    db_prefix() . 'staff.last_login as last_login',
];

$sIndexColumn = 'staffid';
$sTable       = db_prefix() . 'staff';

$__post      = @$this->ci->input->post();
$where_state = ((isset($__post['search'])) && $__post['search']['value'] != '') ? 'AND' : 'WHERE';
$where = [
    $where_state.' staffid in ('.implode(',', $staffs).') and active = 1 and is_not_staff = 0'
];

$join = [
    'LEFT JOIN ( select count(*) as sum_c, assigned from ' . db_prefix() . 'leads where deleted = 0 AND blocked = 0 group by assigned) clients ON clients.assigned = ' . db_prefix() . 'staff.staffid',
];

$additional_select = [
    db_prefix() . 'staff.staffid as id'
];
$group_by = ' group by '.db_prefix().'staff.staffid';

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additional_select, $group_by);
//print_r($result);die();

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $row[] = '<a href="' . admin_url('staff/profile/' . $aRow['id']) . '">' . $aRow['full_name'] . '</a>';
    //$row[] = $aRow['full_name'];
    $row[] = $aRow['email'];
    $row[] = $aRow['clients'];
    $row[] = $aRow['last_login'] ? '<span data-toggle="tooltip" data-title="' . _dt($aRow['last_login']) . '" class="text-has-action is-date">' . time_ago($aRow['last_login']) . '</span>' : '---';

    $output['aaData'][] = $row;
}