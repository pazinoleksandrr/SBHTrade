<?php

defined('BASEPATH') or exit('No direct script access allowed');

function sum_from_table_($table, $attr = [])
{
    if (!isset($attr['field'])) {
        show_error('sum_from_table(); function expect field to be passed.');
    }

    $CI = & get_instance();
    if (isset($attr['where'])) {
        if(is_array($attr['where'])){
            $i = 0;
            foreach ($attr['where'] as $key => $val) {
                if (is_numeric($key)) {
                    $CI->db->where($val);
                    unset($attr['where'][$key]);
                }
                $i++;
            }
            $CI->db->where($attr['where']);
        }elseif (strlen($attr['where']) > 0) {
            $CI->db->where($attr['where']);
        }
    }
    $CI->db->select_sum($attr['field']);
    $CI->db->from($table);
    $CI->db->join(db_prefix().'leads', db_prefix().'leads.id='.$table.'.client AND deleted = 0 AND blocked = 0');
    $result = $CI->db->get()->row();

    return $result->{$attr['field']};
}

function get_client_ids_by_assigned($assigned = ''){
    try {
        $CI = & get_instance();
        if(is_numeric($assigned)) {
            $where['assigned'] = $assigned;
            $where['deleted'] = 0;
            $where['blocked'] = 0;
        }else $where = 'assigned in ('.implode(',', $assigned).') AND deleted = 0 AND blocked = 0';
        $CI->db->select(db_prefix().'leads.id');
        $CI->db->where($where);
        $CI->db->from(db_prefix().'leads');
        $query = $CI->db->get();
        return $query->result_array();
    } catch (Exception $ex) {
        return [];
    }
}