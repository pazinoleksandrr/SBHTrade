<?php

(defined('BASEPATH')) or exit('No direct script access allowed');

class FinanceModel extends App_Model
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
                'field' => 'transaction_id',
                'label' => "lang:id",
                'rules' => "required|trim|is_natural_no_zero|is_unique[$this->table_name.transaction_id]"
            ],
            [
                'field' => 'account_number',
                'label' => "lang:account_number_",
                'rules' => "required|trim|max_length[20]"
            ],
            /*[
                'field' => 'payment_type',
                'label' => "lang:payment_type_",
                'rules' => "required|trim|max_length[10]"
            ],*/
            [
                'field' => 'amount',
                'label' => "lang:amount_",
                'rules' => "required|numeric|trim|max_length[16]"
            ],
            [
                'field' => 'status',
                'label' => "lang:status_",
                'rules' => "required|trim|max_length[10]"
            ],
            [
                'field' => 'type',
                'label' => "lang:type_",
                'rules' => "required|trim|max_length[10]"
            ],
            [
                'field' => 'currency',
                'label' => "lang:currency",
                'rules' => "required|trim|max_length[10]"
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
            /*'payment_type' => [
                'field' => 'payment_type',
                'label' => "lang:payment_type_",
                'rules' => "required|trim|max_length[10]"
            ],*/
            'amount' => [
                'field' => 'amount',
                'label' => "lang:amount_",
                'rules' => "required|numeric|trim|max_length[16]"
            ],
            'status' => [
                'field' => 'status',
                'label' => "lang:status_",
                'rules' => "required|trim|max_length[10]"
            ],
            'type' => [
                'field' => 'type',
                'label' => "lang:type_",
                'rules' => "required|trim|max_length[10]"
            ],
            'currency' => [
                'field' => 'currency',
                'label' => "lang:currency",
                'rules' => "required|trim|max_length[10]"
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
                    log_activity('New ' . ucfirst($data['type']) . ' Created [ID: ' . $insert_id . ', BTR ID: '.$data['transaction_id'].']');
                    $this->log_client_activity($insert_id, 'client_' . $data['type'] . '_created_f');
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
                    log_activity('New ' . ucfirst($data['type']) . ' Created [ID: ' . $insert_id . ', BTR ID: '.$data['transaction_id'].']');
                    $this->log_client_activity($insert_id, 'client_' . $data['type'] . '_created_f');
                    $guid = $insert_id . '-' . $guid;
                    $this->db->where([$this->table_col_id => $insert_id])->update($this->table_name, [$this->table_col_guid => $guid]);

                    if ($data['status'] == 'new' || $data['status'] == 'approved' || $data['status'] == 'success') {
                        $account = $this->get_account($data['account']);
                        if ($data['type'] == 'deposit') $new_balance = $account->balance + $data['amount'];
                        else $new_balance = $account->balance - $data['amount'];
                        $this->db->where(['id' => $data['account'], 'is_demo' => 0]);
                        $this->db->update(db_prefix() . 'accounts', ['balance' => $new_balance/*app_format_number($new_balance)*/]);
                    }

                    return $insert_id;
                } else {
                    return false;
                }
            }
        } else return false;
    }

    //if where is not an array, then its tblfinance.id
    function get_item($where = [])
    {
        if (!is_array($where)) {
            $query = "select " . /*db_prefix() . "finance.id as id, " .*/
                db_prefix() . "finance.transaction_id as transaction_id, " . db_prefix() . "accounts.account_number, " . db_prefix() . "finance.dateadded as created_at, " . db_prefix() . "finance.currency, 
                " . db_prefix() . "finance.type, " . db_prefix() . "finance.amount, " . db_prefix() . "finance.status, " . db_prefix() . "leads.user_id as user_id from " . $this->table_name . " 
                left join " . db_prefix() . "leads on " . db_prefix() . "leads.id = " . db_prefix() . "finance.client
                left join " . db_prefix() . "accounts on " . db_prefix() . "accounts.id = " . db_prefix() . "finance.account and " . db_prefix() . "accounts.is_demo = 0
                where " . db_prefix() . "finance.id = " . $where . " order by " . db_prefix() . "finance.id asc";
            $result = $this->db->query($query);
            return $result->row();
        } else {
            $this->db->select(/*db_prefix() . "finance.id as id, " . */ db_prefix() . "finance.transaction_id as transaction_id, " . db_prefix() . "accounts.account_number, 
                " . db_prefix() . "finance.dateadded as created_at, " . db_prefix() . "finance.currency, 
                " . db_prefix() . "finance.type, " . db_prefix() . "finance.amount, " . db_prefix() . "finance.status, " . db_prefix() . "leads.user_id as user_id");
            $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id=' . db_prefix() . 'finance.client', 'left');
            $this->db->join(db_prefix() . 'accounts', db_prefix() . 'accounts.id=' . db_prefix() . 'finance.account and ' . db_prefix() . 'accounts.is_demo = 0', 'left');
            $row = $this->db->where($where)->get($this->table_name)->row();
            return $row;
        }
    }

    function get_list($where)
    {
        $this->db->select(/*db_prefix() . "finance.id as id, " . */ db_prefix() . "finance.transaction_id as transaction_id, " . db_prefix() . "accounts.account_number, 
                " . db_prefix() . "finance.dateadded as created_at, " . db_prefix() . "finance.currency, 
                " . db_prefix() . "finance.type, " . db_prefix() . "finance.amount, " . db_prefix() . "finance.status, " . db_prefix() . "leads.user_id as user_id");
        $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id=' . db_prefix() . 'finance.client', 'left');
        $this->db->join(db_prefix() . 'accounts', db_prefix() . 'accounts.id=' . db_prefix() . 'finance.account and ' . db_prefix() . 'accounts.is_demo = 0', 'left');
        $result = $this->db->where($where)->get($this->table_name)->result();
        return $result;
    }

    function update_item($where, $data)
    {
        if ($where && $data) {
            $current_data = $this->get($where['id']);

            $update = $this->db->where($where)->update($this->table_name, $data);
            if ($update) {

                if ($data['status'] != $current_data->status && ($data['status'] == 'new' || $data['status'] == 'approved' || $data['status'] == 'success')) {
                    $account = $this->get_account($current_data->account);//$data['account']);
                    if ($current_data->type == 'deposit') $new_balance = $account->balance /*- $current_data->amount*/ + $data['amount'];
                    else $new_balance = $account->balance /*+ $current_data->amount*/ - $data['amount'];
                    $this->db->where(['id' => $current_data->account/*$data['account']*/, 'is_demo' => 0]);
                    $this->db->update(db_prefix() . 'accounts', ['balance' => $new_balance/*app_format_number($new_balance)*/]);
                    $current_data = $this->get($where['id']);
                }
                if ($data['status'] != $current_data->status && ($current_data->status == 'new' || $current_data->status == 'approved' || $current_data->status == 'success')) {
                    $account = $this->get_account($current_data->account);//$data['account']);
                    if ($current_data->type == 'deposit') $new_balance = $account->balance /*+ $current_data->amount*/ - $data['amount'];
                    else $new_balance = $account->balance /*- $current_data->amount*/ + $data['amount'];
                    $this->db->where(['id' => $current_data->account/*$data['account']*/, 'is_demo' => 0]);
                    $this->db->update(db_prefix() . 'accounts', ['balance' => $new_balance/*app_format_number($new_balance)*/]);
                    $current_data = $this->get($where['id']);
                }
                if ($data['amount'] != $current_data->amount && ($data['status'] == 'new' || $data['status'] == 'approved' || $data['status'] == 'success')) {
                    $account = $this->get_account($current_data->account);//$data['account']);
                    if ($current_data->type == 'deposit') $new_balance = $account->balance - $current_data->amount + $data['amount'];
                    else $new_balance = $account->balance + $current_data->amount - $data['amount'];
                    $this->db->where(['id' => $current_data->account/*$data['account']*/, 'is_demo' => 0]);
                    $this->db->update(db_prefix() . 'accounts', ['balance' => $new_balance/*app_format_number($new_balance)*/]);
                }

                return true;
            } else {
                return false;
            }
        } else return false;
    }

    function delete_item($where)
    {
        if ($where) {
            $finance = $this->get($where['id']);

            $delete = $this->db->where($where)->delete($this->table_name);
            if ($delete) {
                log_activity('Finance Deleted [Deleted by: react app, ID: ' . $where['id'] . ', BTR ID: '.$where['transaction_id'].']');

                if ($finance->status == 'completed') {
                    $account = $this->get_account($finance->account);//print_r($account);die();
                    //$currency = get_currency($account->currency);//app_format_money($account->balance, $currency);
                    if ($finance->type == 'deposit') $new_balance = $account->balance - $finance->amount;
                    else $new_balance = $account->balance + $finance->amount;
                    $this->db->where(['id' => $finance->account, 'is_demo' => 0]);
                    $this->db->update(db_prefix() . 'accounts', ['balance' => $new_balance/*app_format_number($new_balance)*/]);
                }

                return true;
            } else {
                return false;
            }
        } else return false;
    }

    public function get($id = '', $type = '', $where = [])
    {
        $this->db->select(db_prefix() . 'finance.*,' . db_prefix() . 'leads.name, ' . db_prefix() . 'leads.email,' . db_prefix() . 'accounts.account_number,
            concat(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as assignee, leads.assigned,
           ' . db_prefix() . 'currencies.id as currency, ' . db_prefix() . 'currencies.symbol, ' . db_prefix() . 'currencies.name as c_name');
        $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id=' . db_prefix() . 'finance.client', 'left');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid=' . db_prefix() . 'leads.assigned', 'left');
        $this->db->join(db_prefix() . 'accounts', db_prefix() . 'accounts.id=' . db_prefix() . 'finance.account and ' . db_prefix() . 'accounts.is_demo = 0', 'left');
        $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id=' . db_prefix() . 'accounts.currency', 'left');

        if ($type) {
            if (is_array($where)) $where[] = db_prefix() . 'finance.type = "' . $type . '"';
            else $where .= ' AND ' . db_prefix() . 'finance.type = "' . $type . '"';
        }
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'finance.id', $id);
            return $this->db->get(db_prefix() . 'finance')->row();
        }

        return $this->db->get(db_prefix() . 'finance')->result_array();
    }

    //accounts
    public function get_account($id = '', $where = [])
    {
        $this->db->select(db_prefix() . 'accounts.*,' . db_prefix() . 'leads.name, ' . db_prefix() . 'leads.email,
            concat(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as assignee, leads.assigned,
           ' . db_prefix() . 'currencies.symbol, ' . db_prefix() . 'currencies.name as c_name');
        $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id=' . db_prefix() . 'accounts.client', 'left');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid=' . db_prefix() . 'leads.assigned', 'left');
        $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id=' . db_prefix() . 'accounts.currency', 'left');

        if (is_numeric($id)) {
            $this->db->where([db_prefix() . 'accounts.id' => $id, 'is_demo' => 0]);
            return $this->db->get(db_prefix() . 'accounts')->row();
        } else {
            $where['is_demo'] = 0;
            $this->db->where($where);
            return $this->db->get(db_prefix() . 'accounts')->result_array();
        }
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