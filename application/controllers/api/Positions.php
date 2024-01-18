<?php

(defined('BASEPATH')) or exit('No direct script access allowed');

require APPPATH . 'libraries/rest/RestController.php';

use chriskacerguis\RestServer\RestController;

class Positions extends RestController
{
    public $form_fields = ['position_id', 'order_id', 'symbol', 'transaction_type', 'status', /*'open_price', 'take_profit', 'stop_loss', */'liquidation_price', 'amount', /*'margin_amount', 'profit', 'pnl_realized'*/];
    public $statuses = ['open', 'close'];

    function __construct()
    {
        parent::__construct();
//        header('Access-Control-Allow-Origin: ' . SPA_URL);
//        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
//        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
        $this->load->model('api/' . get_class($this) . 'Model', 'model');
        $this->model->table_name = db_prefix() . 'positions';
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
                    /*$account = $this->model->get_item_special(['client' => $client->id, 'account_number' => $d_request['account_number']], db_prefix() . 'accounts');
                    if (empty($account)) {
                        $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                    }*/
                    $order = $this->model->get_item_special(['client' => $client->id, 'order_id' => $d_request['order_id']], db_prefix() . 'orders');
                    if (empty($order)) {
                        $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                    }
                    if (!isset($d_request['transaction_type']) || ($d_request['transaction_type'] != 'buy' && $d_request['transaction_type'] != 'sell')) {
                        $this->response(['type' => 'error', 'message' => _l('transaction_type_not_valid')], UNPROCESSABLE_ENTITY);
                    }
                    if (!isset($d_request['status']) || !in_array($d_request['status'], $this->statuses)) {
                        $this->response(['type' => 'error', 'message' => _l('status_not_valid')], UNPROCESSABLE_ENTITY);
                    }
                    if (isset($decoded['swap'])) $d_request['swap'] = $decoded['swap'] !== '' ? $decoded['swap'] : 0;
                    if (isset($decoded['open_price'])) $d_request['open_price'] = $decoded['open_price'] !== '' ? $decoded['open_price'] : 0;
                    if (isset($decoded['close_price'])) $d_request['close_price'] = $decoded['close_price'] !== '' ? $decoded['close_price'] : 0;
                    if (isset($decoded['take_profit'])) $d_request['take_profit'] = $decoded['take_profit'] !== '' ? $decoded['take_profit'] : 0;
                    if (isset($decoded['stop_loss'])) $d_request['stop_loss'] = $decoded['stop_loss'] !== '' ? $decoded['stop_loss'] : 0;
                    //if (isset($decoded['margin_amount'])) $d_request['margin_amount'] = $decoded['margin_amount'] !== '' ? $decoded['margin_amount'] : 0;
                    if (isset($decoded['freeze_amount'])) $d_request['margin_amount'] = $decoded['freeze_amount'] !== '' ? $decoded['freeze_amount'] : 0;
                    if (isset($decoded['profit'])) $d_request['profit'] = $decoded['profit'] !== '' ? $decoded['profit'] : 0;
                    if (isset($decoded['pnl_realized'])) $d_request['pnl_realized'] = $decoded['pnl_realized'] !== '' ? $decoded['pnl_realized'] : 0;
                    if (isset($decoded['is_demo'])) $d_request['is_demo'] = $decoded['is_demo'];
                    if (isset($decoded['created_at'])) $d_request['dateadded'] = validate_date($decoded['created_at']) ?? NOW;
                    $this->load->library('form_validation');
                    $this->form_validation->set_rules($this->model->field_rules());
                    $this->form_validation->set_data($d_request);
                    if ($this->form_validation->run()) {
                        //unset($d_request['account_number']);
                        $d_request['client'] = $client->id;
                        $d_request['order'] = $order->id;
                        unset($d_request['order_id']);
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
        if (isset($decoded['position_id']) && $decoded['position_id'] != '' && $decoded['position_id'] != 'undefined') {
            $item = $this->model->get_item([db_prefix() . 'positions.position_id' => $decoded['position_id'], db_prefix().'positions.is_demo' => $is_demo]);
            if (empty($item)) {
                $this->response(['type' => 'warning', 'message' => _l('no_data')], OK);
            } else {
                //$return_item = api_response_only($item, $this->return_fields);
                $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $item], OK);
            }
        } elseif (isset($decoded['user_id']) && $decoded['user_id'] != '' && $decoded['user_id'] != 'undefined') {
            $client = $this->_get_client($decoded['user_id']);
            if ($client) {
                $where = [db_prefix() . 'positions.client' => $client->id, db_prefix().'positions.is_demo' => $is_demo];
                if (isset($decoded['order_id']) && $decoded['order_id'] != '' && $order = $this->model->get_item_special(['client' => $client->id, 'order_id' => $decoded['order_id']], db_prefix() . 'orders')) {
                    if($order) $where['order'] = $order->id;//$decoded['order_id'];
                }
                $items = $this->model->get_list($where);
                if (empty($items)) {
                    $this->response(['type' => 'warning', 'message' => _l('no_data')], OK);
                } else {
                    $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $items], OK);
                }
            } else {
                $this->response(['type' => 'error', 'message' => _l('user_not_found')], UNPROCESSABLE_ENTITY);
            }
        } else {
            $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
        }
    }

    function edit_post()
    {
        $request = $this->input->raw_input_stream;
        $decoded = json_decode($request, true);
        $decoded = clear_array($decoded);
        if (isset($decoded['position_id']) && $decoded['position_id'] != '' && $decoded['position_id'] != 'undefined') {
            if (!is_array($decoded)) {
                $this->response(['type' => 'error', 'message' => _l('json_error')], BAD_REQUEST);
            } else {
                if ($decoded && $d_request = api_form($decoded, $this->form_fields)) {
                    $client = isset($decoded['user_id']) ? $this->_get_client($decoded['user_id']) : false;
                    if ($client) {
                        /*$account = $this->model->get_item_special(['client' => $client->id, 'account_number' => $d_request['account_number']], db_prefix() . 'accounts');
                        if (empty($account)) {
                            $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                        }*/
                        $order = $this->model->get_item_special(['client' => $client->id, 'order_id' => $d_request['order_id']], db_prefix() . 'orders');
                        if (empty($order)) {
                            $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                        }
                        if (!isset($d_request['transaction_type']) || ($d_request['transaction_type'] != 'buy' && $d_request['transaction_type'] != 'sell')) {
                            $this->response(['type' => 'error', 'message' => _l('transaction_type_not_valid')], UNPROCESSABLE_ENTITY);
                        }
                        if (!isset($d_request['status']) || !in_array($d_request['status'], $this->statuses)) {
                            $this->response(['type' => 'error', 'message' => _l('status_not_valid')], UNPROCESSABLE_ENTITY);
                        }
                        $position = $this->model->get_item_special(['client' => $client->id, 'order' => $order->id, 'position_id' => $decoded['position_id']], db_prefix() . 'positions');
                        if (empty($position)) {
                            $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                        } else {
                            if (isset($decoded['swap'])) $d_request['swap'] = $decoded['swap'] !== '' ? $decoded['swap'] : 0;
                            if (isset($decoded['open_price'])) $d_request['open_price'] = $decoded['open_price'] !== '' ? $decoded['open_price'] : 0;
                            if (isset($decoded['close_price'])) $d_request['close_price'] = $decoded['close_price'] !== '' ? $decoded['close_price'] : 0;
                            if (isset($decoded['take_profit'])) $d_request['take_profit'] = $decoded['take_profit'] !== '' ? $decoded['take_profit'] : 0;
                            if (isset($decoded['stop_loss'])) $d_request['stop_loss'] = $decoded['stop_loss'] !== '' ? $decoded['stop_loss'] : 0;
                            //if (isset($decoded['margin_amount'])) $d_request['margin_amount'] = $decoded['margin_amount'] !== '' ? $decoded['margin_amount'] : 0;
                            if (isset($decoded['freeze_amount'])) $d_request['margin_amount'] = $decoded['freeze_amount'] !== '' ? $decoded['freeze_amount'] : 0;
                            if (isset($decoded['profit'])) $d_request['profit'] = $decoded['profit'] !== '' ? $decoded['profit'] : 0;
                            if (isset($decoded['pnl_realized'])) $d_request['pnl_realized'] = $decoded['pnl_realized'] !== '' ? $decoded['pnl_realized'] : 0;
                            if (isset($decoded['is_demo'])) $d_request['is_demo'] = $decoded['is_demo'];
                            $this->load->library('form_validation');
                            $this->form_validation->set_data($d_request);
                            $this->form_validation->set_rules($this->model->field_rules_update($d_request));
                            if ($this->form_validation->run()) {
                                //unset($d_request['account_number']);
                                //$d_request['client'] = $client->id;
                                //$d_request['order'] = $order->id;
                                unset($d_request['position_id']);
                                unset($d_request['order_id']);
                                $update = $this->model->update_item(['id' => $position->id], $d_request);
                                if ($update) {
                                    $item = $this->model->get_item($position->id);
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
            if ($decoded && $data = api_form($decoded, ['position_id'])) {
                $data['is_demo'] = isset($decoded['is_demo']) ? $decoded['is_demo'] : false;
                $client = isset($decoded['user_id']) ? $this->_get_client($decoded['user_id']) : false;
                if ($client) {
                    $position = $this->model->get_item_special(['client' => $client->id, 'position_id' => $data['position_id'], 'is_demo' => $data['is_demo']], db_prefix() . 'positions');
                    if (empty($position)) {
                        $this->response(['type' => 'warning', 'message' => _l('no_data')], UNPROCESSABLE_ENTITY);
                    } else {
                        $delete = $this->model->delete_item(['position_id' => $position->position_id, 'client' => $client->id, 'id' => $position->id]);
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