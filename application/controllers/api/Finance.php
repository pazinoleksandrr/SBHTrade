<?php

(defined('BASEPATH')) or exit('No direct script access allowed');

require APPPATH.'libraries/rest/RestController.php';

use chriskacerguis\RestServer\RestController;

class Finance extends RestController
{
    public $form_fields = ['transaction_id', 'account_number', /*'payment_type', */'amount', 'status', 'type', 'currency'];
    //public $return_fields = ['id', 'account_number', 'date', 'payment_type', 'type', 'amount', 'status', 'id_user'];
    public $statuses = ['new', 'approved', 'canceled', 'waiting', 'success', 'failed', 'expired'];

    function __construct()
    {
        parent::__construct();
//        header('Access-Control-Allow-Origin: ' . SPA_URL);
//        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
//        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
        $this->load->model('api/'.get_class($this) . 'Model', 'model');
        $this->model->table_name = db_prefix() . 'finance';
        $this->load->helper('jwt/common_api');
    }

    private function _get_client($f_id)
    {
        if (!empty($f_id)) {
            $person = $this->model->get_item_special(['user_id' => $f_id, 'deleted' => 0, 'blocked' => 0], db_prefix() . 'leads');
            return $person ?? false;
        } else {
            return false;
        }
    }

    function index_post()
    {
        $request = $this->input->raw_input_stream;
        $decoded = json_decode($request, true);
        $decoded = clear_array($decoded);
        if (!is_array($decoded)) {
            $this->response(['type' => 'error', 'message' => _l('json_error')], BAD_REQUEST);
        } else {
            if ($decoded && $d_request = api_form($decoded, $this->form_fields)) {
                $client = isset($decoded['user_id']) ? $this->_get_client($decoded['user_id']) : false;
                if ($client) {
                    $account = $this->model->get_item_special(['client' => $client->id, 'account_number' => $d_request['account_number'], 'is_demo' => 0], db_prefix() . 'accounts');
                    if (empty($account)) {
                        $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                    }
                    /*if ($d_request['payment_type'] != 'crypto' && $d_request['payment_type'] != 'cash') {
                        $this->response(['type' => 'error', 'message' => _l('payment_type_not_valid')], UNPROCESSABLE_ENTITY);
                    }*/
                    if (!isset($d_request['type']) || ($d_request['type'] != 'deposit' && $d_request['type'] != 'withdrawal')) {
                        $this->response(['type' => 'error', 'message' => _l('finance_type_not_valid')], UNPROCESSABLE_ENTITY);
                    }
                    if (!isset($d_request['status']) || !in_array($d_request['status'], $this->statuses)){
                        $this->response(['type' => 'error', 'message' => _l('status_not_valid')], UNPROCESSABLE_ENTITY);
                    }
                    if(isset($decoded['created_at'])) $d_request['dateadded'] = $decoded['created_at'];
                    $this->load->library('form_validation');
                    $this->form_validation->set_rules($this->model->field_rules());
                    $this->form_validation->set_data($d_request);
                    if ($this->form_validation->run()) {
                        $data = [
                            'client' => $client->id,
                            'transaction_id' => $d_request['transaction_id'],
                            'account' => $account->id,
                            //'payment_type' => $d_request['payment_type'],
                            'amount' => $d_request['amount']/*app_format_number($d_request['amount'])*/,
                            'status' => $d_request['status'],
                            'type' => $d_request['type'],
                            'currency' => $d_request['currency'],
                            'dateadded' => $decoded['created_at'],
                        ];
                        $insert = $this->model->add_item($data);
                        if ($insert) {
                            $item = $this->model->get_item($insert);
                            //$return_item = api_response_only($item, $this->return_fields);
                            $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $item], CREATED);
                        } else {
                            $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
                        }
                    } else {
                        $this->response(['type' => 'error', 'message' => _l('validation_error'), 'extra' => validation_errors()], UNPROCESSABLE_ENTITY);
                    }
                } else {
                    $this->response(['type' => 'error', 'message' => _l('user_not_found')], UNPROCESSABLE_ENTITY);
                }
            } else {
                $this->response(['type' => 'error', 'message' => _l('missing_info')], UNPROCESSABLE_ENTITY);
            }
        }
    }

    function index_get(){
        $request = $this->input->raw_input_stream;
        $decoded = json_decode($request, true);
        $decoded = clear_array($decoded);
        if (isset($decoded['transaction_id']) && $decoded['transaction_id'] != '' && $decoded['transaction_id'] != 'undefined') {
            $item = $this->model->get_item([db_prefix() . 'finance.transaction_id' => $decoded['transaction_id']]);
            if (empty($item)) {
                $this->response(['type' => 'warning', 'message' => _l('no_data')], OK);
            } else {
                //$return_item = api_response_only($item, $this->return_fields);
                $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $item], OK);
            }
        } elseif(isset($decoded['user_id']) && $decoded['user_id'] != '' && $decoded['user_id'] != 'undefined') {
            $client = $this->_get_client($decoded['user_id']);
            if ($client) {
                $where = [db_prefix() . 'finance.client' => $client->id];
                if (isset($decoded['account_number']) && $decoded['account_number'] != '') $where['account_number'] = $decoded['account_number'];
                if (isset($decoded['type']) && $decoded['type'] != '' && $decoded['type'] != 'all') $where[db_prefix() . 'finance.type'] = $decoded['type'];
                $transactions = $this->model->get_list($where);
                if (empty($transactions)) {
                    $this->response(['type' => 'warning', 'message' => _l('no_data')], OK);
                } else {
                    $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $transactions], OK);
                }
            } else {
                $this->response(['type' => 'error', 'message' => _l('user_not_found')], UNPROCESSABLE_ENTITY);
            }
        } else {
            $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
        }
    }

    /*function list_get($client_id = '', $type = '', $account_number = ''){
        $client = $this->_get_client($client_id);
        if ($client) {
            $where = [db_prefix() . 'finance.client' => $client->id];
            if ($account_number != '') $where['account_number'] = $account_number;
            if ($type != '' && $type != 'all') $where[db_prefix() . 'finance.type'] = $type;
            $items = $this->model->get_list($where);
            if (empty($items)) {
                $this->response(['type' => 'warning', 'message' => _l('no_data')], OK);
            } else {
                $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $items], OK);
            }
        }else {
            $this->response(['type' => 'error', 'message' => _l('user_not_found')], UNPROCESSABLE_ENTITY);
        }
    }*/

    function edit_post(){
        $request = $this->input->raw_input_stream;
        $decoded = json_decode($request, true);
        $decoded = clear_array($decoded);
        if (isset($decoded['transaction_id']) && $decoded['transaction_id'] != '' && $decoded['transaction_id'] != 'undefined') {
            if (!is_array($decoded)) {
                $this->response(['type' => 'error', 'message' => _l('json_error')], BAD_REQUEST);
            } else {
                if ($decoded && $d_request = api_form($decoded, $this->form_fields)) {
                    $client = isset($decoded['user_id']) ? $this->_get_client($decoded['user_id']) : false;
                    if ($client) {
                        $account = $this->model->get_item_special(['client' => $client->id, 'account_number' => $d_request['account_number'], 'is_demo' => 0], db_prefix() . 'accounts');
                        if (empty($account)) {
                            $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                        }
                        /*if ($d_request['payment_type'] != 'crypto' && $d_request['payment_type'] != 'cash') {
                            $this->response(['type' => 'error', 'message' => _l('payment_type_not_valid')], UNPROCESSABLE_ENTITY);
                        }*/
                        if (!isset($d_request['type']) || ($d_request['type'] != 'deposit' && $d_request['type'] != 'withdrawal')) {
                            $this->response(['type' => 'error', 'message' => _l('finance_type_not_valid')], UNPROCESSABLE_ENTITY);
                        }
                        if (!isset($d_request['status']) || !in_array($d_request['status'], $this->statuses)) {
                            $this->response(['type' => 'error', 'message' => _l('status_not_valid')], UNPROCESSABLE_ENTITY);
                        }
                        $item = $this->model->get_item_special(['client' => $client->id, 'transaction_id' => $decoded['transaction_id']], db_prefix() . 'finance');
                        if (empty($item)) {
                            $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                        } else {
                            $this->load->library('form_validation');
                            $this->form_validation->set_data($d_request);
                            $this->form_validation->set_rules($this->model->field_rules_update($d_request));
                            if ($this->form_validation->run()) {
                                unset($d_request['account_number']);
                                unset($d_request['transaction_id']);
                                //$d_request['account'] = $account->id;
                                $update = $this->model->update_item(['id' => $item->id], $d_request);
                                if ($update) {
                                    $item_ = $this->model->get_item($item->id);
                                    //$return_item = api_response_only($item_, $this->return_fields);
                                    $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $item_], OK);
                                } else {
                                    $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
                                }
                            } else {
                                $this->response(['type' => 'error', 'message' => _l('validation_error'), 'extra' => validation_errors()], UNPROCESSABLE_ENTITY);
                            }
                        }
                    } else {
                        $this->response(['type' => 'error', 'message' => _l('user_not_found')], UNPROCESSABLE_ENTITY);
                    }
                } else {
                    $this->response(['type' => 'error', 'message' => _l('missing_info')], UNPROCESSABLE_ENTITY);
                }
            }
        } else {
            $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
        }
    }

    /*function index_delete(){
        $request = $this->input->raw_input_stream;
        $decoded = json_decode($request, true);
        $decoded = clear_array($decoded);
        if (!is_array($decoded)) {
            $this->response(['type' => 'error', 'message' => _l('json_error')], BAD_REQUEST);
        } else {
            if ($decoded && $data = api_form($decoded, ['id'])) {
                $client = isset($decoded['id_user']) ? $this->_get_client($decoded['id_user']) : false;
                if ($client) {
                    $item = $this->model->get_item_special(['client' => $client->id, 'guid' => $data['id']], db_prefix() . 'finance');
                    if (empty($item)) {
                        $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                    } else {
                        $delete = $this->model->delete_item(['id' => $item->id]);
                        if ($delete) {
                            $this->response(['type' => 'success', 'message' => _l('succeed')], OK);
                        } else {
                            $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
                        }
                    }
                } else {
                    $this->response(['type' => 'error', 'message' => _l('user_not_found')], UNPROCESSABLE_ENTITY);
                }
            } else {
                $this->response(['type' => 'error', 'message' => _l('missing_info')], UNPROCESSABLE_ENTITY);
            }
        }
    }*/

}