<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Finance extends AdminController
{
    public $statuses;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('finance_model');
        //$this->statuses = [['id' => 'completed', 'name' => _l('completed_')], ['id' => 'in_process', 'name' => _l('in_process_')]];
        $this->statuses = [
            ['id' => 'new', 'name' => _l('new_')],
            ['id' => 'approved', 'name' => _l('approved_')],
            ['id' => 'cancelled', 'name' => _l('cancelled_')],
            ['id' => 'waiting', 'name' => _l('waiting_')],
            ['id' => 'success', 'name' => _l('success_')],
            ['id' => 'failed', 'name' => _l('failed_')],
            ['id' => 'expired', 'name' => _l('expired_')]
        ];
    }

    /*public function index($id = '')
    {
        //$this->list_finance($id);
    }*/

    public function init_relation_finance($rel_id, $rel_type)
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('finance_relations', [
                'rel_id'   => $rel_id,
                'rel_type' => $rel_type,
            ]);
        }
    }

    function deposit($id = ''){
        //deposit
        if(!$id && $this->input->server('REQUEST_METHOD') === 'GET') $this->list_finance('deposit', 'deposit_');
        else{
            $id = $id == 'new' ? '' : $id;
            if ($this->input->post()) {
                if ($id == '') {
                    $data = $this->input->post();
                    $data['currency'] = 'USD';
                    $data['status'] = 'success';
                    $account_type = $data['account_type'] == 'demo' ? 1 : 0;unset($data['account_type']);
                    $account = $this->finance_model->get_account_by_type($data['client'], $account_type);
                    if($account){
                        $data['account'] = $account->id;
                        $id      = $this->finance_model->add($data, 'deposit');
                        $message = $id ? _l('added_successfully', _l('deposit_')) : '';
                    }else $message = _l('account_doesnt_exist_');

                    echo json_encode([
                        'success'  => $id ? true : false,
                        'id'       => $id,
                        'message'  => $message,
                        'financeView' => $id ? $this->_get_finance_data($id, 'deposit') : [],
                    ]);
                } else {
                    $data = $this->input->post();
                    $data['currency'] = 'USD';
                    $data['status'] = 'success';
                    //$account_type = $data['account_type'] == 'demo' ? 1 : 0;unset($data['account_type']);
                    //$account = $this->finance_model->get_account_by_type($data['client'], $account_type);
                    $message = ''; //$success = false;
//                    if($account){
//                        $data['account'] = $account->id;
                    $success = $this->finance_model->update($data, $id);
//                    }else $message = _l('account_doesnt_exist_');

                    if ($success) {
                        $message = _l('updated_successfully', _l('deposit_'));
                    }
                    echo json_encode([
                        'success'          => $success,
                        'message'          => $message,
                        'id'               => $id,
                        'financeView'      => $this->_get_finance_data($id, 'deposit')//$success ? $this->_get_finance_data($id, 'deposit') : []
                    ]);
                }
                die;
            }else echo json_encode([
                'financeView' => $this->_get_finance_data($id, 'deposit'),
            ]);
        }
    }

    function withdrawal($id = ''){
        //withdrawal
        if(!$id && $this->input->server('REQUEST_METHOD') === 'GET') $this->list_finance('withdrawal', 'withdrawal_');
        else{
            $id = $id == 'new' ? '' : $id;
            if ($this->input->post()) {
                if ($id == '') {
                    $id      = $this->finance_model->add($this->input->post(), 'withdrawal');
                    $message = $id ? _l('added_successfully', _l('withdrawal_')) : '';

                    echo json_encode([
                        'success'  => $id ? true : false,
                        'id'       => $id,
                        'message'  => $message,
                        'financeView' => $id ? $this->_get_finance_data($id, 'withdrawal') : [],
                    ]);
                } else {
                    $data = $this->input->post();
                    unset($data['client'], $data['account']);
                    $message         = '';
                    $success         = $this->finance_model->update($data, $id);

                    if ($success) {
                        $message = _l('updated_successfully', _l('withdrawal_'));
                    }
                    echo json_encode([
                        'success'          => $success,
                        'message'          => $message,
                        'id'               => $id,
                        'financeView'         => $this->_get_finance_data($id, 'withdrawal'),
                    ]);
                }
                die;
            }else echo json_encode([
                'financeView' => $this->_get_finance_data($id, 'withdrawal'),
            ]);
        }
    }

    public function list_finance($type, $title, $id = '')
    {
        close_setup_menu();

        if (!is_staff_member()) {
            access_denied('Finance');
        }
        $this->load->model(['leads_model', 'staff_model']);
        $data['client'] = [];$data['staff'] = [];
        if(is_admin()){
            $data['client']       = $this->leads_model->get('', "((first_name != '' AND last_name != '') OR ".db_prefix()."leads.name != '' OR ".db_prefix()."leads.email != '')");//['first_name != ' => '', 'last_name != ' => '', db_prefix().'leads.name != ' => '']);
            $data['staff']       = $this->staff_model->get('', ['is_not_staff' => 0, 'active' => 1]);
        }else{
            $staff_id = get_staff_user_id();
            $staff = $this->staff_model->get_with_role($staff_id);
            if(isset($staff->role_name)){
                if($staff->role_name == 'Lead'){
                    $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);//print_r($staff_ids);die();
                    if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);//print_r([$staff_ids]);die();
                    //$data['client'] = $this->leads_model->get('', 'assigned in ('.implode(',', $staff_ids).') and first_name != "" and last_name != "" and '.db_prefix().'leads.name != ""');
                    $data['client'] = $this->leads_model->get('', 'assigned in ('.implode(',', $staff_ids).') and ((first_name != "" and last_name != "") or '.db_prefix().'leads.name != "" or '.db_prefix().'leads.email != "")');
                    $data['staff'] = $this->staff_model->get('', 'staffid in ('.implode(',', $staff_ids).') and active = 1 and is_not_staff = 0');
                }
                if($staff->role_name == 'Sales'){
                    //$data['client']       = $this->leads_model->get('', ['assigned' => $staff_id, 'first_name != ' => '', 'last_name != ' => '', db_prefix().'leads.name != ' => '']);
                    $data['client']       = $this->leads_model->get('', "assigned = '".$staff_id."' AND ((first_name != '' AND last_name != '') OR ".db_prefix()."leads.name != '' OR ".db_prefix()."leads.email != '')");
                    $data['staff']       = $this->staff_model->get('', ['is_not_staff' => 0, 'active' => 1, 'staffid' => $staff_id]);
                }
            }
        }
//        $data['staff'] = $this->staff_model->get('', ['active' => 1]);
        $data['statuses'] = $this->statuses;
        $data['title']    = _l($title);
        // in case accesed the url leads/index/ directly with id - used in search
//        $data['leadid']   = $id;

        $this->load->view('admin/finance/manage_'.$type, $data);
    }

    private function _get_finance_data($id = '', $type = '')
    {
        $this->load->model(['leads_model', 'staff_model']);
        $data['clients'] = [];$data['staff'] = [];$staff_ids_of_dep = '';
        if(is_admin()){
            $data['clients']       = $this->leads_model->get('', "((first_name != '' AND last_name != '') OR ".db_prefix()."leads.name != '' OR ".db_prefix()."leads.email != '')");//['first_name != ' => '', 'last_name != ' => '', db_prefix().'leads.name != ' => '']);
            $data['staff']       = $this->staff_model->get('', ['is_not_staff' => 0, 'active' => 1]);
        }else{
            $staff_id = get_staff_user_id();
            $staff = $this->staff_model->get_with_role($staff_id);
            if(isset($staff->role_name)){
                if($staff->role_name == 'Lead'){
                    $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);//print_r($staff_ids);die();
                    if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);//print_r([$staff_ids]);die();
                    //$data['clients'] = $this->leads_model->get('', 'assigned in ('.implode(',', $staff_ids).') and first_name != "" and last_name != "" and '.db_prefix().'leads.name != ""');
                    $data['clients'] = $this->leads_model->get('', 'assigned in ('.implode(',', $staff_ids).') and ((first_name != "" and last_name != "") or '.db_prefix().'leads.name != "" or '.db_prefix().'leads.email != "")');
                    $data['staff'] = $this->staff_model->get('', 'staffid in ('.implode(',', $staff_ids).') and active = 1 and is_not_staff = 0');
                    $staff_ids_of_dep = implode(',', $staff_ids);
                }
                if($staff->role_name == 'Sales'){
                    //$data['clients']       = $this->leads_model->get('', ['assigned' => $staff_id, 'first_name != ' => '', 'last_name != ' => '', db_prefix().'leads.name != ' => '']);
                    $data['clients']       = $this->leads_model->get('', "assigned = '".$staff_id."' AND ((first_name != '' AND last_name != '') OR ".db_prefix()."leads.name != '' OR ".db_prefix()."leads.email != '')");
                    $data['staff']       = $this->staff_model->get('', ['is_not_staff' => 0, 'active' => 1, 'staffid' => $staff_id]);
                }
            }
        }
        $data['openEdit']      = $this->input->get('edit') ? true : false;
        $data['status_id']     = $this->input->get('status_id') ? $this->input->get('status_id') : $this->statuses[1];//get_option('finance_default_status');
        $data['base_currency'] = get_base_currency();

        if (is_numeric($id)) {
            if($staff_ids_of_dep == '') $financeWhere = (has_permission('finance', '', 'view') ? [] : '(assigned = ' . get_staff_user_id() . ' OR '.db_prefix().'finance.addedfrom=' . get_staff_user_id() . ' OR is_public=1)');
            else $financeWhere = (has_permission('finance', '', 'view') ? [] : '(assigned in (' . $staff_ids_of_dep . ') OR '.db_prefix().'finance.addedfrom=' . get_staff_user_id() . ' OR is_public=1)');
            $finance = $this->finance_model->get($id, $type, $financeWhere);

            if (!$finance) {
                header('HTTP/1.0 404 Not Found');
                echo _l($type.'_not_found_');
                die;
            }else{
                if($staff_ids_of_dep == '') $clientWhere = (has_permission('leads', '', 'view') ? [] : '(assigned = ' . get_staff_user_id() . ' OR addedfrom=' . get_staff_user_id() . ' OR is_public=1)');
                else $clientWhere = (has_permission('leads', '', 'view') ? [] : '(assigned in (' . $staff_ids_of_dep . ') OR addedfrom=' . get_staff_user_id() . ' OR is_public=1)');
                $client = $this->leads_model->get($finance->client, $clientWhere);

                $account = $this->finance_model->get_account($finance->account);
            }

            $data[$type]          = $finance;
            $data['client']          = $client;
            $data['account']          = $account;
            //$data['activity_log']  = $this->finance_model->get_finance_activity_log($id);
        }

        $data['statuses'] = $this->statuses;

        $data = hooks()->apply_filters('finance_view_data', $data);

        return [
            'data'          => $this->load->view('admin/finance/data_'.$type, $data, true)
        ];
    }

    function get_accounts(){
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }
        $input = $this->input->post();
        if (!empty($input['client_id'])){
            $accounts = $this->finance_model->get_account('', ['client' => $input['client_id']]);
            if(!empty($accounts)){
                echo json_encode(['status' => 'success', 'data' => $accounts]);
            }else{
                echo json_encode(['status' => 'error', 'msg' => 'fetch accounts: failed']);
            }
        } else {
            echo json_encode(['status' => 'error']);
        }
    }

    public function table_deposit()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }
        $this->app->get_table_data('deposit');
    }

    public function table_withdrawal()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }
        $this->app->get_table_data('withdrawal');
    }

    public function delete_deposit($id)
    {
        if (!$id) {
            redirect(admin_url('finance/deposit'));
        }

        if (!has_permission('finance', '', 'delete_deposit')) {
            access_denied('Delete Deposit');
        }

        $response = $this->finance_model->delete($id, 'deposit');
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('deposit_lowercase_')));
        } elseif ($response === true) {
            set_alert('success', _l('deleted', _l('deposit_')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('deposit_lowercase_')));
        }

        $ref = $_SERVER['HTTP_REFERER'];

        // if user access finance/deposit/ID to prevent redirecting on the same url because will throw 404
        if (!$ref || strpos($ref, 'finance/deposit/' . $id) !== false) {
            redirect(admin_url('finance/deposit'));
        }

        redirect($ref);
    }

    public function delete_withdrawal($id)
    {
        if (!$id) {
            redirect(admin_url('finance/withdrawal'));
        }

        if (!has_permission('finance', '', 'delete_withdrawal')) {
            access_denied('Delete Withdrawal');
        }

        $response = $this->finance_model->delete($id, 'withdrawal');
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('withdrawal_lowercase_')));
        } elseif ($response === true) {
            set_alert('success', _l('deleted', _l('withdrawal_')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('withdrawal_lowercase_')));
        }

        $ref = $_SERVER['HTTP_REFERER'];

        // if user access finance/withdrawal/ID to prevent redirecting on the same url because will throw 404
        if (!$ref || strpos($ref, 'finance/withdrawal/' . $id) !== false) {
            redirect(admin_url('finance/withdrawal'));
        }

        redirect($ref);
    }
}
