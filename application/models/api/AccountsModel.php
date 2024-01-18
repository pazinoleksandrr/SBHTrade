<?php

(defined('BASEPATH')) or exit('No direct script access allowed');

class AccountsModel extends App_Model
{
    public $table_name = '';
    public $table_col_id = 'id';
    public $table_col_guid = 'guid';

    function __construct()
    {
        parent::__construct();
    }

    function field_rules($unique = ['account_number' => true])
    {
        $unique_ac = '';//$unique['account_number'] ? "|is_unique[".$this->table_name.".account_number]" : "";
        return [
            [
                'field' => 'balance',
                'label' => "lang:balance_",
                'rules' => "numeric|trim|max_length[16]"
            ],
            [
                'field' => 'account_number',
                'label' => "lang:account_number_",
                'rules' => "required|trim|max_length[20]" . $unique_ac
            ],
            [
                'field' => 'currency',
                'label' => "lang:currency",
                'rules' => "required|trim|max_length[10]"
            ],
            [
                'field' => 'is_demo',
                'label' => "lang:is_demo_",
                'rules' => "trim|max_length[10]"
            ],
            [
                'field' => 'available_balance',
                'label' => "lang:available_balance_",
                'rules' => "numeric|trim|max_length[16]"
            ],
            [
                'field' => 'frozen_balance',
                'label' => "lang:frozen_balance_",
                'rules' => "numeric|trim|max_length[16]"
            ]/*,
            [
                'field' => 'type',
                'label' => "lang:type",
                'rules' => "required|trim|max_length[4]"
            ]*/
        ];
    }

    function add_item($data = [])
    {
        if ($data) {
            if ($this->table_col_id == '' || $this->table_col_guid == '') {
                $insert = $this->db->insert($this->table_name, $data);
                if ($insert) {
                    $insert_id = $this->db->insert_id();
                    log_activity('New Account Created [ID: ' . $insert_id . ', BTR ID: '.$data['account_number'].']');
                    $this->log_client_activity($insert_id, 'client_account_created_f');
                    return $insert_id;
                } else {
                    return false;
                }
            } else {
                $guid = $this->db->query('SELECT UUID()')->row();
                foreach ($guid as $key => $value) {
                    $guid = $value;
                }
                $insert = $this->db->insert($this->table_name, $data);
                if ($insert) {
                    $insert_id = $this->db->insert_id();
                    log_activity('New Account Created [ID: ' . $insert_id . ', BTR ID: '.$data['account_number'].']');
                    $this->log_client_activity($insert_id, 'client_account_created_f');
                    $guid = $insert_id . '-' . $guid;
                    $this->db->where([$this->table_col_id => $insert_id])->update($this->table_name, [$this->table_col_guid => $guid]);
                    return $insert_id;
                } else {
                    return false;
                }
            }
        } else return false;
    }

    //if where is not an array, then its tblaccounts.id
    function get_item($where)
    {
        if (!is_array($where)) {
            $query = "select " . db_prefix() . "leads.user_id as user_id, ".$this->table_name.".account_number, ".$this->table_name.".balance, 
                ".$this->table_name.".currency, (case when is_demo = 0 then 'false' else 'true' end) as is_demo, available_balance, frozen_balance from ".$this->table_name."
                left join " . db_prefix() . "leads on " . db_prefix() . "leads.id = ".$this->table_name.".client
                where ".$this->table_name.".id = " . $where . " order by ".$this->table_name.".account_number asc";
            $result = $this->db->query($query);
            return $result->row();
        } else {
            $this->db->select(db_prefix() . "leads.user_id as user_id, ".$this->table_name.".account_number, ".$this->table_name.".balance, 
                ".$this->table_name.".currency, (case when is_demo = 0 then 'false' else 'true' end) as is_demo, available_balance, frozen_balance");
            $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id='.$this->table_name.'.client', 'left');
            $row = $this->db->where($where)->get($this->table_name)->row();
            return $row;
        }
    }

    function get_item_with_id($where)
    {
        if (!is_array($where)) {
            $query = "select ".$this->table_name.".id as id, " . db_prefix() . "leads.user_id as user_id, ".$this->table_name.".account_number, ".$this->table_name.".balance, 
                ".$this->table_name.".currency, (case when is_demo = 0 then 'false' else 'true' end) as is_demo, available_balance, frozen_balance from ".$this->table_name."
                left join " . db_prefix() . "leads on " . db_prefix() . "leads.id = ".$this->table_name.".client
                where ".$this->table_name.".id = " . $where . " order by ".$this->table_name.".account_number asc";
            $result = $this->db->query($query);
            return $result->row();
        } else {
            $this->db->select($this->table_name.".id as id, ".db_prefix() . "leads.user_id as user_id, ".$this->table_name.".account_number, ".$this->table_name.".balance, 
                ".$this->table_name.".currency, (case when is_demo = 0 then 'false' else 'true' end) as is_demo, available_balance, frozen_balance");
            $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id='.$this->table_name.'.client', 'left');
            $row = $this->db->where($where)->get($this->table_name)->row();
            return $row;
        }
    }

    function get_list($client_id, $is_demo)
    {
        $where_type = $is_demo == 'all' ? '' : ' AND '.$this->table_name.'.is_demo = '.(int) $is_demo;
        $query = "select " . db_prefix() . "leads.user_id as user_id, ".$this->table_name.".account_number, ".$this->table_name.".balance, 
                ".$this->table_name.".currency, (case when is_demo = 0 then 'false' else 'true' end) as is_demo, available_balance, frozen_balance from ".$this->table_name."
                left join " . db_prefix() . "leads on " . db_prefix() . "leads.id = ".$this->table_name.".client
                where ".$this->table_name.".client = " . $client_id . " ".$where_type." order by ".$this->table_name.".account_number asc";
        $result = $this->db->query($query);
        return $result->result();
    }

    function update_item($where, $data)
    {
        if ($where && $data) {
            $update = $this->db->where($where)->update($this->table_name, $data);
            if ($update) {
                return true;
            } else {
                return false;
            }
        } else return false;
    }

    function delete_item($where)
    {
        if ($where) {
            $delete = $this->db->where($where)->delete($this->table_name);
            if ($delete) {
                log_activity('Account Deleted [Deleted by: react app, ID: ' . $where['id'] . ', BTR ID: '.$where['account_id'].']');
                return true;
            } else {
                return false;
            }
        } else return false;
    }

    //logs
    public function log_client_activity($id, $description, $integration = false, $additional_data = '')
    {
        $log = [
            'date' => date('Y-m-d H:i:s'),
            'description' => $description,
            'leadid' => $id,
            'staffid' => 0,//get_staff_user_id(),
            'additional_data' => $additional_data,
            'full_name' => ''//get_staff_full_name(get_staff_user_id()),
        ];
        if ($integration == true) {
            $log['staffid'] = 0;
            $log['full_name'] = '[CRON]';
        }

        $this->db->insert(db_prefix() . 'lead_activity_log', $log);

        return $this->db->insert_id();
    }

    //common use
    function get_custom_result($query)
    {
        $result = $this->db->query($query);
        return $result->result();
    }

    function get_custom_row($query)
    {
        $result = $this->db->query($query);
        return $result->row();
    }

    function add_item_special($data = [], $table = '')
    {
        if ($data && $table) {
            if ($this->table_col_id == '' || $this->table_col_suid == '') {
                $insert = $this->db->insert($table, $data);
                if ($insert) {
                    return $this->db->insert_id();
                } else {
                    return false;
                }
            } else {
                $suid = $this->db->query('SELECT UUID()')->row();
                foreach ($suid as $key => $value) {
                    $suid = $value;
                }
                $insert = $this->db->insert($table, $data);
                if ($insert) {
                    $insert_id = $this->db->insert_id();
                    $suid = $insert_id . '-' . $suid;
                    $this->db->where([$this->table_col_id => $insert_id])->update($table, [$this->table_col_suid => $suid]);
                    return $insert_id;
                } else {
                    return false;
                }
            }
        } else return false;
    }

    function get_item_special($where = [], $table = '')
    {
        if (isset($table)) {
            return $this->db->where($where)->get($table)->row();
        } else return false;
    }

}