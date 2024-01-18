<?php

(defined('BASEPATH')) or exit('No direct script access allowed');

require APPPATH.'libraries/rest/RestController.php';

use chriskacerguis\RestServer\RestController;

class   Orders extends RestController
{
    public $form_fields = ['order_id', 'account_number', 'symbol', 'order_type', 'transaction_type', 'amount', /*'margin_amount', */'price', /*'take_profit', 'stop_loss', */'status'];
    //public $return_fields = ['id', 'account_number', 'account_type', 'date', 'order_type', 'symbol', 'amount', 'price_open', 'price_close', 'swap', 'fee', 'margin', 'sl', 'tp', 'current_price', 'pl', 'status', 'id_user'];
    public $statuses = ['open', 'filled', 'expired', 'canceled'];

    function __construct()
    {
        parent::__construct();
//        header('Access-Control-Allow-Origin: ' . SPA_URL);
//        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
//        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
        $this->load->model('api/'.get_class($this) . 'Model', 'model');
        $this->model->table_name = db_prefix() . 'orders';
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
                    $this->load->helper('jwt/a_datetime');
                    if(isset($decoded['is_demo'])) $d_request['is_demo'] = $decoded['is_demo'];
                    else $d_request['is_demo'] = false;
                    $account = $this->model->get_item_special(['client' => $client->id, 'account_number' => $d_request['account_number'], 'is_demo' => $d_request['is_demo'] ? 1 : 0], db_prefix() . 'accounts');
                    if (empty($account)) {
                        $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                    }
                    /*if ($d_request['type'] != 'real' && $d_request['type'] != 'demo') {
                        $this->response(['type' => 'error', 'message' => _l('account_type_not_valid')], UNPROCESSABLE_ENTITY);
                    }*/
                    if (!isset($d_request['order_type']) || ($d_request['order_type'] != 'market' && $d_request['order_type'] != 'stop_market')) {
                        $this->response(['type' => 'error', 'message' => _l('order_type_not_valid')], UNPROCESSABLE_ENTITY);
                    }
                    if (!isset($d_request['transaction_type']) || ($d_request['transaction_type'] != 'buy' && $d_request['transaction_type'] != 'sell')) {
                        $this->response(['type' => 'error', 'message' => _l('transaction_type_not_valid')], UNPROCESSABLE_ENTITY);
                    }
                    if (!isset($d_request['status']) || !in_array($d_request['status'], $this->statuses)) {
                        $this->response(['type' => 'error', 'message' => _l('status_not_valid')], UNPROCESSABLE_ENTITY);
                    }
                    $d_request['parent_order_id'] = isset($decoded['parent_order_id']) && $decoded['parent_order_id'] != '' ? $decoded['parent_order_id'] : null;
                    $d_request['position_id'] = isset($decoded['position_id']) && $decoded['position_id'] != '' ? $decoded['position_id'] : null;
                    //$d_request['margin_amount'] = isset($decoded['margin_amount']) && $decoded['margin_amount'] != '' ? $decoded['margin_amount'] : null;
                    $d_request['margin_amount'] = isset($decoded['freeze_amount']) && $decoded['freeze_amount'] != '' ? $decoded['freeze_amount'] : 0;
                    $d_request['take_profit'] = isset($decoded['take_profit']) && $decoded['take_profit'] != '' ? $decoded['take_profit'] : null;
                    $d_request['stop_loss'] = isset($decoded['stop_loss']) && $decoded['stop_loss'] != '' ? $decoded['stop_loss'] : null;
                    $d_request['date_expiration'] = isset($decoded['date_expiration']) && $decoded['date_expiration'] != '' ? (validate_date($decoded['date_expiration']) ?? null) : null;
                    //if(isset($decoded['is_demo'])) $d_request['is_demo'] = $decoded['is_demo'];
                    if(isset($decoded['created_at'])) $d_request['dateadded'] = validate_date($decoded['created_at']) ?? NOW;
                    $this->load->library('form_validation');
                    $this->form_validation->set_rules($this->model->field_rules());
                    $this->form_validation->set_data($d_request);
                    if ($this->form_validation->run()) {
                        unset($d_request['account_number']);
                        $d_request['client'] = $client->id;
                        $d_request['account'] = $account->id;
                        $insert = $this->model->add_item($d_request);
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

    function index_get()
    {
        $request = $this->input->raw_input_stream;
        $decoded = json_decode($request, true);
        $decoded = clear_array($decoded);
        $is_demo = isset($decoded['is_demo']) ? $decoded['is_demo'] : false;
        if (isset($decoded['order_id']) && $decoded['order_id'] != '' && $decoded['order_id'] != 'undefined') {
            $order = $this->model->get_item([db_prefix() . 'orders.order_id' => $decoded['order_id'], db_prefix().'orders.is_demo' => $is_demo]);
            if(empty($order)){
                $this->response(['type' => 'warning', 'message' => _l('no_data')], OK);
            } else {
                //$return_item = api_response_only($order, $this->return_fields);
                $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $order], OK);
            }
        } elseif(isset($decoded['user_id']) && $decoded['user_id'] != '' && $decoded['user_id'] != 'undefined') {
            $client = $this->_get_client($decoded['user_id']);
            if ($client) {
                $where = [db_prefix() . 'orders.client' => $client->id, db_prefix().'orders.is_demo' => $is_demo];
                if (isset($decoded['account_number']) && $decoded['account_number'] != '') $where['account_number'] = $decoded['account_number'];
                if (isset($decoded['transaction_type']) && $decoded['transaction_type'] != '' && $decoded['transaction_type'] != 'all') $where[db_prefix() . 'orders.transaction_type'] = $decoded['transaction_type'];
                $orders = $this->model->get_list($where);
                if (empty($orders)) {
                    $this->response(['type' => 'warning', 'message' => _l('no_data')], OK);
                } else {
                    $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $orders], OK);
                }
            } else {
                $this->response(['type' => 'error', 'message' => _l('user_not_found')], UNPROCESSABLE_ENTITY);
            }
        } else {
            $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
        }
    }

    /*function index_get($order_guid = '')
    {
        if ($order_guid && $order_guid != '' && $order_guid != 'undefined') {
            $order = $this->model->get_item([db_prefix() . 'orders.guid' => $order_guid]);
            if (empty($order)) {
                $this->response(['type' => 'warning', 'message' => _l('no_data')], OK);
            } else {
                $return_item = api_response_only($order, $this->return_fields);
                $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $return_item], OK);
            }
        } else {
            $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
        }
    }*/

    /*function list_get($client_id = '', $account_number = '')
    {
        $client = $this->_get_client($client_id);
        if ($client) {
            if ($account_number != '') $orders = $this->model->get_list([db_prefix() . 'orders.client' => $client->id, 'account_number' => $account_number]);
            else $orders = $this->model->get_list([db_prefix() . 'orders.client' => $client->id]);
            if (empty($orders)) {
                $this->response(['type' => 'warning', 'message' => _l('no_data')], OK);
            } else {
                $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $orders], OK);
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
        if (isset($decoded['order_id']) && $decoded['order_id'] != '' && $decoded['order_id'] != 'undefined') {
            if (!is_array($decoded)) {
                $this->response(['type' => 'error', 'message' => _l('json_error')], BAD_REQUEST);
            } else {
                if ($decoded && $d_request = api_form($decoded, $this->form_fields)) {
                    $client = isset($decoded['user_id']) ? $this->_get_client($decoded['user_id']) : false;
                    if ($client) {
                        $this->load->helper('jwt/a_datetime');
                        if(isset($decoded['is_demo'])) $d_request['is_demo'] = $decoded['is_demo'];
                        else $d_request['is_demo'] = false;
                        $account = $this->model->get_item_special(['client' => $client->id, 'account_number' => $d_request['account_number'], 'is_demo' => $d_request['is_demo'] ? 1 : 0], db_prefix() . 'accounts');
                        if (empty($account)) {
                            $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                        }
                        /*if ($d_request['type'] != 'real' && $d_request['type'] != 'demo') {
                            $this->response(['type' => 'error', 'message' => _l('account_type_not_valid')], UNPROCESSABLE_ENTITY);
                        }*/
                        if (!isset($d_request['order_type']) || ($d_request['order_type'] != 'market' && $d_request['order_type'] != 'stop_market')) {
                            $this->response(['type' => 'error', 'message' => _l('order_type_not_valid')], UNPROCESSABLE_ENTITY);
                        }
                        if (!isset($d_request['transaction_type']) || ($d_request['transaction_type'] != 'buy' && $d_request['transaction_type'] != 'sell')) {
                            $this->response(['type' => 'error', 'message' => _l('transaction_type_not_valid')], UNPROCESSABLE_ENTITY);
                        }
                        if (!isset($d_request['status']) || !in_array($d_request['status'], $this->statuses)) {
                            $this->response(['type' => 'error', 'message' => _l('status_not_valid')], UNPROCESSABLE_ENTITY);
                        }
                        $order = $this->model->get_item_special(['client' => $client->id, 'order_id' => $decoded['order_id']], db_prefix() . 'orders');
                        if (empty($order)) {
                            $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                        } else {
                            $d_request['parent_order_id'] = isset($decoded['parent_order_id']) && $decoded['parent_order_id'] != '' ? $decoded['parent_order_id'] : null;
                            $d_request['position_id'] = isset($decoded['position_id']) && $decoded['position_id'] != '' ? $decoded['position_id'] : null;
                            //$d_request['margin_amount'] = isset($decoded['margin_amount']) && $decoded['margin_amount'] != '' ? $decoded['margin_amount'] : null;
                            $d_request['margin_amount'] = isset($decoded['freeze_amount']) && $decoded['freeze_amount'] != '' ? $decoded['freeze_amount'] : 0;
                            $d_request['take_profit'] = isset($decoded['take_profit']) && $decoded['take_profit'] != '' ? $decoded['take_profit'] : null;
                            $d_request['stop_loss'] = isset($decoded['stop_loss']) && $decoded['stop_loss'] != '' ? $decoded['stop_loss'] : null;
                            $d_request['date_expiration'] = isset($decoded['date_expiration']) && $decoded['date_expiration'] != '' ? (validate_date($decoded['date_expiration']) ?? null) : null;
                            //if(isset($decoded['is_demo'])) $d_request['is_demo'] = $decoded['is_demo'];
                            $this->load->library('form_validation');
                            $this->form_validation->set_data($d_request);
                            $this->form_validation->set_rules($this->model->field_rules_update($d_request));
                            if ($this->form_validation->run()) {
                                unset($d_request['account_number']);
                                unset($d_request['order_id']);
                                $update = $this->model->update_item(['id' => $order->id], $d_request);
                                if ($update) {
                                    $item = $this->model->get_item($order->id);
                                    //$return_item = api_response_only($item, $this->return_fields);
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

    function delete_post()
    {
        $request = $this->input->raw_input_stream;
        $decoded = json_decode($request, true);
        $decoded = clear_array($decoded);
        if (!is_array($decoded)) {
            $this->response(['type' => 'error', 'message' => _l('json_error')], BAD_REQUEST);
        } else {
            if ($decoded && $data = api_form($decoded, ['order_id'])) {
                $data['is_demo'] = isset($decoded['is_demo']) ? $decoded['is_demo'] : false;
                $client = isset($decoded['user_id']) ? $this->_get_client($decoded['user_id']) : false;
                if ($client) {
                    $order = $this->model->get_item_special(['client' => $client->id, 'order_id' => $data['order_id'], 'is_demo' => $data['is_demo']], db_prefix() . 'orders');
                    if (empty($order)) {
                        $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                    } else {
                        $delete = $this->model->delete_item(['order_id' => $order->order_id, 'client' => $client->id, 'id' => $order->id]);
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
    }

}