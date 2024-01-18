<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Finance_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
//        $this->load->model('projects_model');
//        $this->load->model('staff_model');
    }

    //finance
    public function get($id = '', $type = '', $where = [])
    {
        $this->db->select(db_prefix() . 'finance.*,' . db_prefix() . 'leads.name, ' . db_prefix() . 'leads.email,' . db_prefix() . 'accounts.account_number,
            concat('.db_prefix() . 'staff.firstname, " ", '.db_prefix() . 'staff.lastname) as assignee, leads.assigned');
        //'.db_prefix().'currencies.id as currency, '.db_prefix().'currencies.symbol, '.db_prefix().'currencies.name as c_name');
        $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id=' . db_prefix() . 'finance.client', 'left');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid=' . db_prefix() . 'leads.assigned', 'left');
        $this->db->join(db_prefix() . 'accounts', db_prefix() . 'accounts.id=' . db_prefix() . 'finance.account', 'left');
        //$this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id=' . db_prefix() . 'accounts.currency', 'left');

        if($type) {
            if(is_array($where)) $where[] = db_prefix() . 'finance.type = "' . $type.'"';
            else $where .= ' AND '.db_prefix() . 'finance.type = "' . $type.'"';
        }
        $this->db->where('deleted = 0 AND blocked = 0');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'finance.id', $id);
            return $this->db->get(db_prefix() . 'finance')->row();
        }

        return $this->db->get(db_prefix() . 'finance')->result_array();
    }

    public function add($data, $type)
    {
        $data['dateadded']   = date('Y-m-d H:i:s');
        $data['addedfrom']   = get_staff_user_id();

        $data = hooks()->apply_filters('before_deposit_added', $data);

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        //$data['amount'] = app_format_number($data['amount']);
        $data['status'] = trim($data['status']);
        $data['currency'] = trim($data['currency']);
        $data['type'] = $type;

        $guid = $this->db->query('SELECT UUID()')->row();
        foreach ($guid as $key => $value) {
            $guid = $value;
        }
        $this->db->insert(db_prefix() . 'finance', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            $guid = $insert_id.'-'.$guid;
            $this->db->where(['id' => $insert_id])->update(db_prefix() . 'finance', ['guid' => $guid]);
            log_activity('New Finance ('.$type.') Added [ID: ' . $insert_id . ']');
            //$this->log_finance_activity($insert_id, 'not_finance_activity_created');

            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            if($data['status'] == 'new' || $data['status'] == 'approved' || $data['status'] == 'success') {
                $account = $this->get_account($data['account']);
                if ($type == 'deposit') $new_balance = $account->balance + $data['amount'];
                else $new_balance = $account->balance - $data['amount'];
                $this->db->where('id', $data['account']);
                $this->db->update(db_prefix() . 'accounts', ['balance' => $new_balance/*app_format_number($new_balance)*/]);
            }

            return $insert_id;
        }

        return false;
    }

    public function update($data, $id)
    {
        unset($data['account_id']);
        $current_data = $this->get($id);
        $affectedRows = 0;
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }

        //$data['amount'] = app_format_number($data['amount']);
        $data['status'] = trim($data['status']);
        $data['currency'] = trim($data['currency']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'finance', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Finance ('.$current_data->type.') Updated [ID: ' . $id . ']');

            if ($data['status'] != $current_data->status && ($data['status'] == 'new' || $data['status'] == 'approved' || $data['status'] == 'success')) {
                $account = $this->get_account($current_data->account);//$data['account']);
                if ($current_data->type == 'deposit') $new_balance = $account->balance /*- $current_data->amount*/ + $data['amount'];
                else $new_balance = $account->balance /*+ $current_data->amount*/ - $data['amount'];
                $this->db->where(['id' => $current_data->account/*$data['account']*/, 'is_demo' => 0]);
                $this->db->update(db_prefix() . 'accounts', ['balance' => $new_balance/*app_format_number($new_balance)*/]);
                $current_data = $this->get($id);
            }
            if ($data['status'] != $current_data->status && ($current_data->status == 'new' || $current_data->status == 'approved' || $current_data->status == 'success')) {
                $account = $this->get_account($current_data->account);//$data['account']);
                if ($current_data->type == 'deposit') $new_balance = $account->balance /*+ $current_data->amount*/ - $data['amount'];
                else $new_balance = $account->balance /*- $current_data->amount*/ + $data['amount'];
                $this->db->where(['id' => $current_data->account/*$data['account']*/, 'is_demo' => 0]);
                $this->db->update(db_prefix() . 'accounts', ['balance' => $new_balance/*app_format_number($new_balance)*/]);
                $current_data = $this->get($id);
            }
            if ($data['amount'] != $current_data->amount && ($data['status'] == 'new' || $data['status'] == 'approved' || $data['status'] == 'success')) {
                $account = $this->get_account($current_data->account);//$data['account']);
                if ($current_data->type == 'deposit') $new_balance = $account->balance - $current_data->amount + $data['amount'];
                else $new_balance = $account->balance + $current_data->amount - $data['amount'];
                $this->db->where(['id' => $current_data->account/*$data['account']*/, 'is_demo' => 0]);
                $this->db->update(db_prefix() . 'accounts', ['balance' => $new_balance/*app_format_number($new_balance)*/]);
            }

            return true;
        }
        if ($affectedRows > 0) {
            return true;
        }

        return false;
    }

    public function delete($id, $type = '')
    {
        $affectedRows = 0;

        hooks()->do_action('before_finance_deleted', $id);

        $finance = $this->get($id);

        $where = ['id' => $id];
        if($type != '') $where['type'] = $type;
        $this->db->where($where);
        $this->db->delete(db_prefix() . 'finance');
        if ($this->db->affected_rows() > 0) {
            log_activity('Finance ('.$type.') Deleted [Deleted by: ' . get_staff_full_name() . ', ID: ' . $id . ']');

            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'finance');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            // TODO: should we store deposit/withdrawal logs ?
            /*$this->db->where('financeid', $id);
            $this->db->delete(db_prefix() . 'finance_activity_log');*/

            // Change balance
            if($finance->status == 'completed') {
                $account = $this->get_account($finance->account);//print_r($account);die();
                //$currency = get_currency($account->currency);//app_format_money($account->balance, $currency);
                if ($type == 'deposit') $new_balance = $account->balance - $finance->amount;
                else $new_balance = $account->balance + $finance->amount;
                $this->db->where('id', $finance->account);
                $this->db->update(db_prefix() . 'accounts', ['balance' => $new_balance/*app_format_number($new_balance)*/]);
            }

            $affectedRows++;
        }
        if ($affectedRows > 0) {
            hooks()->do_action('after_finance_deleted', $id);
            return true;
        }

        return false;
    }

    //account
    public function get_account($id = '', $where = [])
    {
        $this->db->select(db_prefix() . 'accounts.*,' . db_prefix() . 'leads.name, ' . db_prefix() . 'leads.email,
            concat('.db_prefix() . 'staff.firstname, " ", '.db_prefix() . 'staff.lastname) as assignee, leads.assigned');
        //'.db_prefix().'currencies.symbol, '.db_prefix().'currencies.name as c_name');
        $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id=' . db_prefix() . 'accounts.client', 'left');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid=' . db_prefix() . 'leads.assigned', 'left');
        //$this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id=' . db_prefix() . 'accounts.currency', 'left');

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'accounts.id', $id);
            return $this->db->get(db_prefix() . 'accounts')->row();
        }else {
            $this->db->where($where);
            return $this->db->get(db_prefix() . 'accounts')->result_array();
        }
    }

    public function get_account_by_type($client_id = '', $type = '')
    {
        $this->db->select(db_prefix() . 'accounts.*,' . db_prefix() . 'leads.name, ' . db_prefix() . 'leads.email');
        $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id=' . db_prefix() . 'accounts.client', 'left');

        $this->db->where([db_prefix() . 'accounts.client' => $client_id, 'is_demo' => $type]);
        return $this->db->get(db_prefix() . 'accounts')->row();
    }

    //status
    public function get_status($id = '', $where = [])
    {
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'finance_status')->row();
        }

        $statuses = $this->app_object_cache->get('finance-all-statuses');

        if (!$statuses) {
            $this->db->order_by('statusorder', 'asc');

            $statuses = $this->db->get(db_prefix() . 'finance_status')->result_array();
            $this->app_object_cache->add('finance-all-statuses', $statuses);
        }

        return $statuses;
    }


}
