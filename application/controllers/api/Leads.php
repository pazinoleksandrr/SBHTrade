<?php

(defined('BASEPATH')) or exit('No direct script access allowed');

require APPPATH . 'libraries/rest/RestController.php';

use chriskacerguis\RestServer\RestController;

class Leads extends RestController
{
    function __construct()
    {
        parent::__construct();
//        header('Access-Control-Allow-Origin: ' . SPA_URL);
//        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
//        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
        $this->load->model('leads_model');
        $this->load->helper(['finance', 'orders', 'positions', 'accounts']);
        $this->load->helper('jwt/common_api');
    }

    function index_post()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules($this->leads_model->field_rules_save());
        $data = clear_array($this->input->post());
        if (!isset($data['description'])) $data['description'] = '';
        if (!isset($data['address'])) $data['address'] = '';
        if (!isset($data['last_name'])) $data['last_name'] = '';
        if (!isset($data['first_name'])) $data['first_name'] = '';
        if (!isset($data['email'])) $data['email'] = '';
        $this->form_validation->set_data($data);
        if ($this->form_validation->run()) {
            $id = $this->leads_model->add($data);
            if ($id) {
                $message = $id ? _l('added_successfully', _l('client_')) : '';
                $this->response([
                    'success' => $id ? true : false,
                    'id'      => $id,
                    'message' => $message,
                ], CREATED);
            }
            else {
                $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
            }
        }
        else {
            $this->response(['type' => 'error', 'message' => _l('validation_error'), 'extra' => validation_errors()], UNPROCESSABLE_ENTITY);
        }
    }
}