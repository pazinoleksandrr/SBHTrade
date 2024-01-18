<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Deps_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_current_data()
    {
        $this->db->select("*");
        $this->db->where(['name' => 'Department', 'slug' => 'staff_department', 'type' => 'select']);
        return $this->db->get(db_prefix() . 'customfields')->row_array();
    }

    public function update($data, $id, $name, $value = '')
    {
        if(!is_admin()) return false;
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'customfields', $data);
        if ($this->db->affected_rows() > 0) {
            if($value != ''){
                $this->db->where(['fieldto' => 'staff', 'value' => $value]);
                $this->db->update(db_prefix().'customfieldsvalues', ['value' => $name]);
            }
            log_activity('Department Custom Field Updated [Name: ' . $name . ', ID: ' . $id . ']');

            return true;
        }

        return false;
    }

    public function get_rel_data($val, $id)
    {
        $this->db->select("*");
        $this->db->where(['value' => $val, 'fieldid' => $id]);
        return $this->db->get(db_prefix() . 'customfieldsvalues')->result_array();
    }
}
