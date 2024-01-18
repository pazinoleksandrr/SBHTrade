<?php

(defined('BASEPATH')) or exit('No direct script access allowed');

require APPPATH.'libraries/rest/RestController.php';

use chriskacerguis\RestServer\RestController;

class Accounts extends RestController
{

    function __construct()
    {
        parent::__construct();
//        header('Access-Control-Allow-Origin: ' . SPA_URL);
//        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
//        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
        $this->load->model('api/'.get_class($this) . 'Model', 'model');
        $this->model->table_name = db_prefix() . 'accounts';
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
            if ($decoded && $d_request = api_form($decoded, ['account_number', 'currency', /*'balance',*/ /*'is_demo', *//*'available_balance', 'frozen_balance'*//*, 'type'*/])) {
                $client = isset($decoded['user_id']) ? $this->_get_client($decoded['user_id']) : false;
                if ($client) {
                    /*if ($d_request['type'] != 'real' && $d_request['type'] != 'demo') {
                        $this->response(['type' => 'error', 'message' => _l('account_type_not_valid')], UNPROCESSABLE_ENTITY);
                    }
                    $currency = $this->model->get_item_special(['name' => $d_request['currency']], db_prefix() . 'currencies');
                    if (!$currency) {
                        $this->response(['type' => 'error', 'message' => _l('currency_not_valid')], UNPROCESSABLE_ENTITY);
                    }*/
                    if(isset($decoded['is_demo'])) $d_request['is_demo'] = $decoded['is_demo'];
                    else $d_request['is_demo'] = false;
                    if(isset($decoded['balance'])) $d_request['balance'] = $decoded['balance'];
                    if(isset($decoded['available_balance'])) $d_request['available_balance'] = $decoded['available_balance'];
                    if(isset($decoded['frozen_balance'])) $d_request['frozen_balance'] = $decoded['frozen_balance'];
                    $this->load->library('form_validation');
                    $this->form_validation->set_rules($this->model->field_rules(['account_number' => true]));
                    $this->form_validation->set_data($d_request);
                    if ($this->form_validation->run()) {
                        $data = [
                            'client' => $client->id,
                            //'account_id' =>  $d_request['account_id'],
                            'currency' =>  $d_request['currency'],//$currency->id,
                            'account_number' => $d_request['account_number'],
                            'balance' => $d_request['balance'] ?? 0/*app_format_number($d_request['balance'])*/,
                            /*'type' => $d_request['type'],*/
                            'is_demo' => $d_request['is_demo'],
                            'available_balance' => $d_request['available_balance'] ?? 0,
                            'frozen_balance' => $d_request['frozen_balance'] ?? 0,
                            'dateadded' => NOW,
                        ];
                        $insert = $this->model->add_item($data);
                        if ($insert) {
                            //$item = $this->model->get_item($insert);
                            $return_item = $this->model->get_item($insert);//api_response_only($item, ['currency', 'account_number', 'balance', 'type', 'id_user']);
                            $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $return_item], CREATED);
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

    function index_get()
    {
        $request = $this->input->raw_input_stream;
        $decoded = json_decode($request, true);
        $decoded = clear_array($decoded);
        $is_demo = isset($decoded['is_demo']) ? $decoded['is_demo'] : false;
        if (isset($decoded['account_number']) && $decoded['account_number'] != '' && $decoded['account_number'] != 'undefined') {
            $account = $this->model->get_item(['account_number' => $decoded['account_number'], 'is_demo' => $is_demo]);
            if (empty($account)) {
                $this->response(['type' => 'warning', 'message' => _l('no_data')], OK);
            } else {
                //$return_item = api_response_only($account, ['currency', 'account_number', 'balance', 'type', 'id_user']);
                $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $account], OK);
            }
        } elseif(isset($decoded['user_id']) && $decoded['user_id'] != '' && $decoded['user_id'] != 'undefined') {
            $client = $this->_get_client($decoded['user_id']);
            if ($client) {
                //if (isset($decoded['is_demo']) && $decoded['is_demo'] != '' && $decoded['is_demo'] == 'all') $is_demo = $decoded['is_demo'];
                $accounts = $this->model->get_list($client->id, $is_demo);
                if (empty($accounts)) {
                    $this->response(['type' => 'warning', 'message' => _l('no_data')], OK);
                } else {
                    $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $accounts], OK);
                }
            } else {
                $this->response(['type' => 'error', 'message' => _l('user_not_found')], UNPROCESSABLE_ENTITY);
            }
        } else {
            $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
        }
    }

    /*function list_get($client_id = '')
    {
        $client = $this->_get_client($client_id);
        if ($client) {
            $accounts = $this->model->get_list($client->id);
            if (empty($accounts)) {
                $this->response(['type' => 'warning', 'message' => _l('no_data')], OK);
            } else {
                $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $accounts], OK);
            }
        } else {
            $this->response(['type' => 'error', 'message' => _l('user_not_found')], UNPROCESSABLE_ENTITY);
        }
    }*/

    function edit_post()
    {
        $request = $this->input->raw_input_stream;
        $decoded = json_decode($request, true);
        $decoded = clear_array($decoded);
        if (isset($decoded['account_number']) && $decoded['account_number'] != '' && $decoded['account_number'] != 'undefined') {
            if (!is_array($decoded)) {
                $this->response(['type' => 'error', 'message' => _l('json_error')], BAD_REQUEST);
            } else {
                if ($decoded && $d_request = api_form($decoded, ['account_number', 'currency', /*'balance',*/ /*'is_demo', *//*'available_balance', 'frozen_balance'*//*, 'type'*/])) {
                    $client = isset($decoded['user_id']) ? $this->_get_client($decoded['user_id']) : false;
                    if ($client) {
                        /*if ($d_request['type'] != 'real' && $d_request['type'] != 'demo') {
                            $this->response(['type' => 'error', 'message' => _l('account_type_not_valid')], UNPROCESSABLE_ENTITY);
                        }
                        $currency = $this->model->get_item_special(['name' => $d_request['currency']], db_prefix() . 'currencies');
                        if (!$currency) {
                            $this->response(['type' => 'error', 'message' => _l('currency_not_valid')], UNPROCESSABLE_ENTITY);
                        }*/
                        if(isset($decoded['is_demo'])) $d_request['is_demo'] = $decoded['is_demo'];
                        else $d_request['is_demo'] = false;
                        if(isset($decoded['balance'])) $d_request['balance'] = $decoded['balance'];
                        if(isset($decoded['available_balance'])) $d_request['available_balance'] = $decoded['available_balance'];
                        if(isset($decoded['frozen_balance'])) $d_request['frozen_balance'] = $decoded['frozen_balance'];
                        $account = $this->model->get_item_with_id(['client' => $client->id, 'account_number' => $decoded['account_number'], 'is_demo' => $d_request['is_demo']]);
                        if (empty($account)) {
                            $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                        } else {
                            $this->load->library('form_validation');
                            $this->form_validation->set_data($d_request);
                            /*if ($d_request['account_number'] && $d_request['account_number'] != $account->account_number) {
                                $this->form_validation->set_rules($this->model->field_rules());
                            } else */$this->form_validation->set_rules($this->model->field_rules(['account_number' => false]));
                            if ($this->form_validation->run()) {
                                $data = [
                                    'currency' => $d_request['currency'],//$currency->id,
                                    //'account_number' => $d_request['account_number'],
                                    'balance' => $d_request['balance']/*app_format_number($d_request['balance'])*/,
                                    //'type' => $d_request['type'],
                                    'is_demo' => $d_request['is_demo'],
                                    'available_balance' => $d_request['available_balance'],
                                    'frozen_balance' => $d_request['frozen_balance'],
                                ];
                                $update = $this->model->update_item(['id' => $account->id], $data);
                                if ($update) {
                                    $item = $this->model->get_item($account->id);
                                    //$return_item = api_response_only($item, ['currency', 'account_number', 'balance', 'type', 'id_user']);
                                    $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $item], OK);
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

    /*function index_delete()
    {
        $request = $this->input->raw_input_stream;
        $decoded = json_decode($request, true);
        $decoded = clear_array($decoded);
        if (!is_array($decoded)) {
            $this->response(['type' => 'error', 'message' => _l('json_error')], BAD_REQUEST);
        } else {
            if ($decoded && $data = api_form($decoded, ['account_number'])) {
                $client = isset($decoded['user_id']) ? $this->_get_client($decoded['user_id']) : false;
                if ($client) {
                    $account = $this->model->get_item(['client' => $client->id, 'account_number' => $data['account_number']]);
                    if (empty($account)) {
                        $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                    } elseif ($account->balance != 0) {
                        $this->response(['type' => 'warning', 'message' => _l('account_is_not_empty')], UNPROCESSABLE_ENTITY);
                    } else {
                        $finance_records = $this->model->get_item_special(['account' => $account->id, 'status != ' => 'completed'], db_prefix() . 'finance');
                        $order_records = $this->model->get_item_special(['account' => $account->id, 'status != ' => 'close'], db_prefix() . 'orders');
                        if ($finance_records) {
                            $this->response(['type' => 'warning', 'message' => _l('account_has_non_completed_records')], UNPROCESSABLE_ENTITY);
                        } elseif ($order_records) {
                            $this->response(['type' => 'warning', 'message' => _l('account_has_open_records')], UNPROCESSABLE_ENTITY);
                        } else {
                            $delete = $this->model->delete_item(['id' => $account->id]);
                            if ($delete) {
                                $this->response(['type' => 'success', 'message' => _l('succeed')], OK);
                            } else {
                                $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
                            }
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