<?php

defined('BASEPATH') or exit('No direct script access allowed');

$ci = $this->_instance;
$ci->load->database();
$ci->db->query('SET SESSION sql_mode =
                  REPLACE(REPLACE(REPLACE(
                  @@sql_mode,
                  "ONLY_FULL_GROUP_BY,", ""),
                  ",ONLY_FULL_GROUP_BY", ""),
                  "ONLY_FULL_GROUP_BY", "")');
$ci->db->select("options");
$ci->db->where(['name' => 'Department', 'slug' => 'staff_department', 'type' => 'select']);
$custom_field = $ci->db->get(db_prefix() . 'customfields')->row_array();
if($custom_field && $custom_field['options']){
    $departments = [];
    $temp = explode(',', $custom_field['options']);
    $departments = array_map('trim', $temp);
    $union_text = '';
    $i = 1;
    foreach ($departments as $d){
        $union_text .= ' UNION ALL SELECT '.$i;
        $i++;
    }
    $ci->db->select("roleid");
    $ci->db->where(['name' => 'Lead']);
    $lead_role = $ci->db->get(db_prefix() . 'roles')->row_array();
    $aColumns = [
        '(n.digit + 1) as o_counter',
        db_prefix() . 'customfields.id as id',
    ];
    $sIndexColumn = 'id';
    $sTable       = db_prefix() . 'customfields';
    $join = [
        'INNER JOIN (SELECT 0 digit '.$union_text.') n ON LENGTH(REPLACE('.db_prefix().'customfields.options, \',\' , \'\')) <= LENGTH('.db_prefix().'customfields.options)-n.digit',
        'LEFT JOIN ' . db_prefix() . 'customfieldsvalues ON ' . db_prefix() . 'customfieldsvalues.fieldid = ' . db_prefix() . 'customfields.id AND '.db_prefix().'customfieldsvalues.value = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX('.db_prefix().'customfields.options, \',\', n.digit+1), \',\', -1))',
        'LEFT JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'customfieldsvalues.relid',
        //'LEFT JOIN (select count(*) as sum_s, '.db_prefix().'customfieldsvalues.value from '.db_prefix().'staff LEFT JOIN '.db_prefix().'customfieldsvalues ON '.db_prefix().'staff.staffid = '.db_prefix().'customfieldsvalues.relid group by '.db_prefix().'customfieldsvalues.value) staffs ON staffs.value = ' . db_prefix() . 'customfieldsvalues.value',
        'LEFT JOIN ' . db_prefix() . 'staff as l_staff ON l_staff.staffid = ' . db_prefix() . 'customfieldsvalues.relid AND '.db_prefix().'customfieldsvalues.value = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX('.db_prefix().'customfields.options, \',\', n.digit+1), \',\', -1)) AND l_staff.role = '.$lead_role['roleid'],
    ];
    $additional_select = [
        ' TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX('.db_prefix().'customfields.options, \',\', n.digit+1), \',\', -1)) opt',
        'count(distinct '.db_prefix() . 'staff.staffid) as staff_count',//'staffs.sum_s as staff_count',//
        'concat(l_staff.firstname, \' \', l_staff.lastname) as lead_staff',
        'l_staff.staffid as lead_staff_id',
        'group_concat(concat(l_staff.firstname, \' \', l_staff.lastname) separator \'---\') as lead_staffs',
        'group_concat(l_staff.staffid separator \'---\') as lead_staff_ids',
        db_prefix() . 'customfieldsvalues.id as cfv_id'
    ];
    $group_by = ' group by TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX('.db_prefix().'customfields.options, \',\', n.digit+1), \',\', -1))';

    $where = [
        'AND '.db_prefix().'customfields.name = \'Department\'',
        'AND '.db_prefix().'customfields.slug = \'staff_department\'',
        'AND '.db_prefix().'customfields.type = \'select\'',
    ];

    if(is_lead_staff()){
        $ci->load->model(['staff_model']);
        $staff_id = get_staff_user_id();
        $staff = $ci->staff_model->get_with_dep($staff_id);
        $where[] = 'AND '.db_prefix().'customfieldsvalues.value = \''.$staff->department.'\'';
    }

    $result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additional_select, $group_by);
    $output  = $result['output'];
    $rResult = $result['rResult'];//print_r($rResult);die();

    foreach ($rResult as $aRow) {
        $row = [];



//    $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['id'] . '"><label></label></div>';

        $hrefAttr = 'href="' . admin_url('deps/index/' . $aRow['o_counter']) . '" onclick="edit_dep(this, ' . $aRow['o_counter'] . ');return false;"';
        $row[]    = $aRow['o_counter'];//'<a ' . $hrefAttr . '>' . $aRow['o_counter'] . '</a>';

        if(is_admin()) {
            $nameRow = '<a ' . $hrefAttr . ' data-name="' . $aRow['opt'] . '">' . $aRow['opt'] . '</a>';
            $nameRow .= '<div class="row-options">';
//    $nameRow .= '<a ' . $hrefAttr . '>' . _l('view') . '</a>';
            $nameRow .= '<a href="' . admin_url('deps/delete/' . $aRow['o_counter']) . '" class="_delete text-danger">' . _l('delete') . '</a>';
            $nameRow .= '</div>';
            $row[] = $nameRow;
        }else{
            $row[] = $aRow['opt'];
        }

        if($aRow['staff_count'] > 0){
            $hrefAttr = 'href="' . /*admin_url('deps/index/' . $aRow['cfv_id']) .*/ '" onclick="init_staff_modal(' . $aRow['cfv_id'] . ', \'' . $aRow['opt'] . '\');return false;"';
            $row[]    = '<a ' . $hrefAttr . '>' . $aRow['staff_count'] . '</a>';
        }else $row[] = $aRow['staff_count'];

        // lead staff
        /* $assignedOutput = '<a data-toggle="tooltip" data-title="' . $aRow['lead_staff'] . '" href="' . admin_url('staff/member/' . $aRow['lead_staff_id']) . '">' . staff_profile_image($aRow['lead_staff_id'], [
                 'staff-profile-image-small',
             ]) . '</a>';
         $assignedOutput .= '<span class="hide">' . $aRow['lead_staff'] . '</span>';*/
        if($aRow['staff_count'] > 1){
            $leads = explode('---', $aRow['lead_staffs']);
            $lead_ids = explode('---', $aRow['lead_staff_ids']);
            if(count($leads) == count($lead_ids)){
                $_data_s = '';
                for($i = 0; $i < count($leads); $i++){
                    /*$_data_s .= '<a href="' . admin_url('staff/member/' . $lead_ids[$i]) . '">' . staff_profile_image($lead_ids[$i], [
                            'staff-profile-image-small',
                        ]) . '</a>';*/
                    $_data_s .= ' <a href="' . admin_url('staff/profile/' . $lead_ids[$i]) . '">' . $leads[$i] . '</a><br>';
                }
                $row[] = $_data_s;
            }else $row[] = '';
        }else{
            /*$_data = '<a href="' . admin_url('staff/member/' . $aRow['lead_staff_id']) . '">' . staff_profile_image($aRow['lead_staff_id'], [
                    'staff-profile-image-small',
                ]) . '</a>';*/
            $_data = ' <a href="' . admin_url('staff/profile/' . $aRow['lead_staff_id']) . '">' . $aRow['lead_staff'] . '</a>';
            /*$_data .= '<div class="row-options">';
            $_data .= '<a href="' . admin_url('staff/member/' . $aRow['lead_staff_id']) . '">' . _l('view') . '</a>';
            $_data .= '</div>';*/
            $row[] = $aRow['lead_staff_id'] ? $_data : '';//$aRow['lead_staff'].'-'.$aRow['lead_staff_id'];
        }

        $output['aaData'][] = $row;
    }
}