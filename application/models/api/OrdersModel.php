<?php
(defined('BASEPATH')) or exit('No direct script access allowed');

class OrdersModel extends App_Model
{
    public $table_name = '';
    public $table_col_id = 'id';
    public $table_col_guid = 'guid';

    function __construct()
    {
        parent::__construct();
    }

    function field_rules()
    {
        return [
            [
                'field' => 'order_id',
                'label' => "lang:id",
                'rules' => "required|trim|is_natural_no_zero|is_unique[$this->table_name.order_id]"
            ],
            [
                'field' => 'account_number',
                'label' => "lang:account_number_",
                'rules' => "required|trim|max_length[20]"
            ],
            [
                'field' => 'symbol',
                'label' => "lang:symbol_",
                'rules' => "required|trim|max_length[15]"
            ],
            [
                'field' => 'order_type',
                'label' => "lang:order_type_",
                'rules' => "required|trim|max_length[15]"
            ],
            [
                'field' => 'transaction_type',
                'label' => "lang:transaction_type_",
                'rules' => "required|trim|max_length[4]"
            ],
            [
                'field' => 'amount',
                'label' => "lang:amount_",
                'rules' => "numeric|trim|max_length[16]"
            ],
            [
                'field' => 'margin_amount',
                'label' => "lang:margin_",
                'rules' => "numeric|trim|max_length[16]"
            ],
            [
                'field' => 'price',
                'label' => "lang:price_",
                'rules' => "numeric|trim|max_length[16]"
            ],
            [
                'field' => 'take_profit',
                'label' => "lang:take_profit_",
                'rules' => "numeric|trim|max_length[16]"
            ],
            [
                'field' => 'stop_loss',
                'label' => "lang:stop_loss_",
                'rules' => "numeric|trim|max_length[16]"
            ],
            [
                'field' => 'status',
                'label' => "lang:status_",
                'rules' => "required|trim|max_length[10]"
            ],
            [
                'field' => 'date_expiration',
                'label' => "lang:date_expiration_",
                'rules' => "trim|max_length[50]"
            ],
            [
                'field' => 'is_demo',
                'label' => "lang:is_demo_",
                'rules' => "trim|max_length[10]"
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
            'account_number' => [
                'field' => 'account_number',
                'label' => "lang:account_number_",
                'rules' => "required|trim|max_length[20]"
            ],
            'symbol' => [
                'field' => 'symbol',
                'label' => "lang:symbol_",
                'rules' => "required|trim|max_length[15]"
            ],
            'order_type' => [
                'field' => 'order_type',
                'label' => "lang:order_type_",
                'rules' => "required|trim|max_length[15]"
            ],
            'transaction_type' => [
                'field' => 'transaction_type',
                'label' => "lang:transaction_type_",
                'rules' => "required|trim|max_length[4]"
            ],
            'amount' => [
                'field' => 'amount',
                'label' => "lang:amount_",
                'rules' => "numeric|trim|max_length[16]"
            ],
            'margin_amount' => [
                'field' => 'margin_amount',
                'label' => "lang:margin_",
                'rules' => "numeric|trim|max_length[16]"
            ],
            'price' => [
                'field' => 'price',
                'label' => "lang:price_",
                'rules' => "numeric|trim|max_length[16]"
            ],
            'take_profit' => [
                'field' => 'take_profit',
                'label' => "lang:take_profit_",
                'rules' => "numeric|trim|max_length[16]"
            ],
            'stop_loss' => [
                'field' => 'stop_loss',
                'label' => "lang:stop_loss_",
                'rules' => "numeric|trim|max_length[16]"
            ],
            'status' => [
                'field' => 'status',
                'label' => "lang:status_",
                'rules' => "required|trim|max_length[10]"
            ],
            'date_expiration' => [
                'field' => 'date_expiration',
                'label' => "lang:date_expiration_",
                'rules' => "trim|max_length[50]"
            ],
            'is_demo' => [
                'field' => 'is_demo',
                'label' => "lang:is_demo_",
                'rules' => "trim|max_length[10]"
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
                    log_activity('New Order Created [ID: ' . $insert_id . ', BTR ID: '.$data['order_id'].']');
                    $this->log_client_activity($insert_id, 'client_order_created_f');
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
                    log_activity('New Order Created [ID: ' . $insert_id . ', BTR ID: '.$data['order_id'].']');
                    $this->log_client_activity($insert_id, 'client_order_created_f');
                    $guid = $insert_id . '-' . $guid;
                    $this->db->where([$this->table_col_id => $insert_id])->update($this->table_name, [$this->table_col_guid => $guid]);
                    return $insert_id;
                } else {
                    return false;
                }
            }
        } else return false;
    }

    //if where is not an array, then its tblorders.id
    function get_item($where)
    {
        if (!is_array($where)) {
            $query = "select " . /*db_prefix() . "orders.id as id, " .*/
                db_prefix() . "orders.order_id as order_id, ".db_prefix()."accounts.account_number, parent_order_id, " . db_prefix() . "leads.user_id as user_id, " . db_prefix() . "orders.position_id, 
                " . db_prefix() . "orders.symbol,  " . db_prefix() . "orders.order_type, " . db_prefix() . "orders.transaction_type, " . db_prefix() . "orders.amount, " . db_prefix() . "orders.margin_amount, 
                " . db_prefix() . "orders.price, " . db_prefix() . "orders.take_profit, " . db_prefix() . "orders.stop_loss, " . db_prefix() . "orders.status, " . db_prefix() . "orders.date_expiration, 
                (case when " . db_prefix() . "orders.is_demo = 0 then 'false' else 'true' end) as is_demo, " . db_prefix() . "orders.dateadded as created_at from " . $this->table_name . " 
                left join " . db_prefix() . "leads on " . db_prefix() . "leads.id = " . db_prefix() . "orders.client
                left join ".db_prefix()."accounts on ".db_prefix()."accounts.id = " . db_prefix() . "orders.account
                where " . db_prefix() . "orders.id = " . $where . " order by " . db_prefix() . "orders.id asc";
            $result = $this->db->query($query);
            return $result->row();
        } else {
            $this->db->select(/*db_prefix() . "orders.id as id, " . */ db_prefix() . "orders.order_id as order_id, ".db_prefix()."accounts.account_number, parent_order_id, 
                " . db_prefix() . "leads.user_id as user_id, " . db_prefix() . "orders.position_id, " . db_prefix() . "orders.symbol,  " . db_prefix() . "orders.order_type, 
                " . db_prefix() . "orders.transaction_type, " . db_prefix() . "orders.amount, 
                " . db_prefix() . "orders.margin_amount, " . db_prefix() . "orders.price, " . db_prefix() . "orders.take_profit, " . db_prefix() . "orders.stop_loss, " . db_prefix() . "orders.status, 
                " . db_prefix() . "orders.date_expiration, (case when " . db_prefix() . "orders.is_demo = 0 then 'false' else 'true' end) as is_demo, " . db_prefix() . "orders.dateadded as created_at");
            $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id=' . db_prefix() . 'orders.client', 'left');
            $this->db->join(db_prefix()."accounts", db_prefix().'accounts.id=' . db_prefix() . 'orders.account', 'left');
            $row = $this->db->where($where)->get($this->table_name)->row();
            return $row;
        }
    }

    function get_list($where)
    {
        $this->db->select(/*db_prefix() . "orders.id as id, ".*/ db_prefix() . "orders.order_id as order_id, ".db_prefix()."accounts.account_number, parent_order_id, 
                " . db_prefix() . "leads.user_id as user_id, " . db_prefix() . "orders.position_id, " . db_prefix() . "orders.symbol,  " . db_prefix() . "orders.order_type, 
                " . db_prefix() . "orders.transaction_type, " . db_prefix() . "orders.amount, 
                " . db_prefix() . "orders.margin_amount, " . db_prefix() . "orders.price, " . db_prefix() . "orders.take_profit, " . db_prefix() . "orders.stop_loss, " . db_prefix() . "orders.status, 
                " . db_prefix() . "orders.date_expiration, (case when " . db_prefix() . "orders.is_demo = 0 then 'false' else 'true' end) as is_demo, " . db_prefix() . "orders.dateadded as created_at");
        $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id=' . db_prefix() . 'orders.client', 'left');
        $this->db->join(db_prefix()."accounts", db_prefix().'accounts.id=' . db_prefix() . 'orders.account', 'left');
        $result = $this->db->where($where)->get($this->table_name)->result();
        return $result;
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
                log_activity('Order Deleted [Deleted by: react app, ID: ' . $where['id'] . ', BTR ID: '.$where['order_id'].']');
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