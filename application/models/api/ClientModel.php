<?php

(defined('BASEPATH')) or exit('No direct script access allowed');

class ClientModel extends App_Model
{
    public $table_name = '';
    public $table_col_id = '';
    public $table_col_guid = '';

    function __construct()
    {
        parent::__construct();
    }

    function field_rules_save($unique = [/*'username' => true, */ 'email' => true])
    {
        //$unique_username = $unique['username'] ? "|is_unique[$this->table_name.username]" : "";
        $unique_email = $unique['email'] ? "|is_unique[$this->table_name.email]" : "";
        return [
            /*[
                'field' => 'username',
                'label' => "lang:username",
                'rules' => "required|trim|max_length[20]" . $unique_username
            ],*/
            [
                'field' => 'user_id',
                'label' => "lang:id",
                'rules' => "required|trim|is_natural_no_zero|is_unique[$this->table_name.user_id]"
            ],
            [
                'field' => 'email',
                'label' => "lang:email",
                'rules' => "required|trim|max_length[190]|valid_email" . $unique_email
            ],
            /*[
                'field' => 'password',
                'label' => "lang:password",
                'rules' => "required|trim|min_length[8]|max_length[25]"
            ],*/
            [
                'field' => 'first_name',
                'label' => "lang:first_name",
                'rules' => "trim|max_length[30]"
            ],
            [
                'field' => 'last_name',
                'label' => "lang:last_name",
                'rules' => "trim|max_length[30]"
            ],
            'default_language' => [
                'field' => 'default_language',
                'label' => "lang:language",
                'rules' => "trim|max_length[40]"
            ],
            [
                'field' => 'country',
                'label' => "lang:country",
                'rules' => "trim|max_length[50]"
            ],
            [
                'field' => 'phonenumber',
                'label' => "lang:clients_phone",
                'rules' => "trim|max_length[50]"
            ]/*,
            [
                'field' => 'f_id',
                'label' => "lang:id",
                'rules' => "required|trim|is_natural_no_zero|is_unique[$this->table_name.f_id]"
            ],
            [
                'field' => 'language',
                'label' => "lang:language",
                'rules' => "trim|max_length[40]"
            ]*/,
            [
                'field' => 'blocked',
                'label' => "lang:blocked_",
                'rules' => "trim|max_length[10]"
            ],
            [
                'field' => 'deleted',
                'label' => "lang:deleted_",
                'rules' => "trim|max_length[10]"
            ],
            [
                'field' => 'city',
                'label' => "lang:city_",
                'rules' => "trim|max_length[100]"
            ],
            [
                'field' => 'address',
                'label' => "lang:street_",
                'rules' => "trim|max_length[255]"
            ],
            [
                'field' => 'zip',
                'label' => "lang:post_code_",
                'rules' => "trim|max_length[50]"
            ],
            [
                'field' => 'dateadded',
                'label' => "lang:created_at_",
                'rules' => "required|trim|max_length[50]"
            ]
        ];
    }

    function field_rules_update($data)
    {
        $rules = [
            'default_language' => [
                'field' => 'default_language',
                'label' => "lang:language",
                'rules' => "trim|max_length[40]"
            ]/*,
            'password' => [
                'field' => 'password',
                'label' => "lang:password",
                'rules' => "required|trim|min_length[8]|max_length[25]"
            ]*/,
            [
                'field' => 'first_name',
                'label' => "lang:first_name",
                'rules' => "trim|max_length[30]"
            ],
            [
                'field' => 'last_name',
                'label' => "lang:last_name",
                'rules' => "trim|max_length[30]"
            ],
            'country' => [
                'field' => 'country',
                'label' => "lang:country",
                'rules' => "trim|max_length[50]"
            ],
            'phonenumber' => [
                'field' => 'phonenumber',
                'label' => "lang:clients_phone",
                'rules' => "trim|max_length[50]"
            ],
            'blocked' => [
                'field' => 'blocked',
                'label' => "lang:blocked_",
                'rules' => "trim|max_length[10]"
            ],
            'deleted' => [
                'field' => 'deleted',
                'label' => "lang:deleted_",
                'rules' => "trim|max_length[10]"
            ],
            'city' => [
                'field' => 'city',
                'label' => "lang:city_",
                'rules' => "trim|max_length[100]"
            ],
            'address' => [
                'field' => 'address',
                'label' => "lang:street_",
                'rules' => "trim|max_length[255]"
            ],
            'zip' => [
                'field' => 'zip',
                'label' => "lang:post_code_",
                'rules' => "trim|max_length[50]"
            ]
        ];
        $rules_for_return = [];
        foreach ($data as $k => $v) {
            if (isset($rules[$k])) $rules_for_return[] = $rules[$k];
        }
        return $rules_for_return;
    }

    function add_item($data = [])
    {
        if ($data) {
            if ($this->table_col_id == '' || $this->table_col_guid == '') {
                $insert = $this->db->insert($this->table_name, $data);
                if ($insert) {
                    $insert_id = $this->db->insert_id();
                    log_activity('New Client Registered [ID: ' . $insert_id . ', BTR ID: '.$data['user_id'].']');
                    $this->log_client_activity($insert_id, 'not_client_activity_registered');
                    hooks()->do_action('lead_created', $insert_id);
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
                    log_activity('New Client Registered [ID: ' . $insert_id . ', BTR ID: '.$data['user_id'].']');
                    $this->log_client_activity($insert_id, 'not_client_activity_registered');
                    $guid = $insert_id . '-' . $guid;
                    $this->db->where([$this->table_col_id => $insert_id])->update($this->table_name, [$this->table_col_guid => $guid]);
                    hooks()->do_action('lead_created', $insert_id);
                    return $insert_id;
                } else {
                    return false;
                }
            }
        } else return false;
    }

    function get_item($where = [])
    {
        $row = $this->db->where($where)->get($this->table_name)->row();
        return $row;
    }

    function get_item_to_return($where = [])
    {
        $this->db->select(db_prefix()."leads.user_id as user_id, email, first_name, last_name, ".db_prefix()."countries.short_name as country, phonenumber as phone, 
                (case when blocked = 0 then 'false' else 'true' end) as blocked, (case when ".db_prefix()."leads.deleted = 0 then 'false' else 'true' end) as is_deleted, city, address as street, 
                zip as post_code, dateadded as created_at");
        $this->db->join(db_prefix() . 'countries', db_prefix() . 'leads.country = ' . db_prefix() . 'countries.country_id', 'left');
        $row = $this->db->where($where)->get($this->table_name)->row();
        return $row;
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

    //sources
    public function get_source($id = false)
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'leads_sources')->row();
        }

        if (is_array($id)) {
            $this->db->where($id);

            return $this->db->get(db_prefix() . 'leads_sources')->row();
        }

        $this->db->order_by('name', 'asc');

        return $this->db->get(db_prefix() . 'leads_sources')->result_array();
    }

    //country
    function find_country($where){
        $row = $this->db->where($where)->get(db_prefix().'countries')->row();
        return $row ?? 0;
    }

    //common use
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