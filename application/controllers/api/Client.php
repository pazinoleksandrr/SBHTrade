<?php

(defined('BASEPATH')) or exit('No direct script access allowed');

require APPPATH.'libraries/rest/RestController.php';

use chriskacerguis\RestServer\RestController;

class Client extends RestController
{
    //private $profile_fields = ['name', 'email', 'f_id', 'default_language'];

    function __construct()
    {
        parent::__construct();
//        header('Access-Control-Allow-Origin: ' . SPA_URL);
//        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
//        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
        $this->load->model('api/'.get_class($this) . 'Model', 'model');
        $this->model->table_name = db_prefix() . 'leads';
        $this->load->helper('jwt/common_api');
    }

    private function _get_client($f_id)
    {
        if (!empty($f_id)) {
            $person = $this->model->get_item_special(['user_id' => $f_id], db_prefix() . 'leads');
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
            if ($decoded && $data = api_form($decoded, [/*'username', */ 'user_id', 'email', /*'password', *//*'first_name', 'last_name'*//*, 'language', 'f_id'*/])) {
                $this->load->helper('jwt/a_datetime');
                //if($decoded['language']) $data['default_language'] = $decoded['language'];
                if($decoded['first_name']) $data['first_name'] = $decoded['first_name'];
                if($decoded['last_name']) $data['last_name'] = $decoded['last_name'];
                if(isset($decoded['language'])) $data['default_language'] = $decoded['language'] == '' ? null : $decoded['language'];
                if(isset($decoded['country'])) $country = $this->model->find_country(['short_name' => $decoded['country']]);
                if(isset($country) && $country) $data['country'] = $country->country_id;
                if(isset($decoded['phone'])) $data['phonenumber'] = $decoded['phone'];
                if(isset($decoded['blocked'])) $data['blocked'] = $decoded['blocked'];
                if(isset($decoded['is_deleted'])) $data['deleted'] = $decoded['is_deleted'];
                if(isset($decoded['city'])) $data['city'] = $decoded['city'];
                if(isset($decoded['street'])) $data['address'] = $decoded['street'];
                if(isset($decoded['post_code'])) $data['zip'] = $decoded['post_code'];
                if(isset($decoded['created_at'])){
                    if($created_date = validate_date($decoded['created_at'])) $data['dateadded'] = $created_date;
                    else $data['dateadded'] = NOW;
                }
//                $this->load->library('form_validation');
//                $this->form_validation->set_rules($this->model->field_rules_save());
//                $this->form_validation->set_data($data);
//                if ($this->form_validation->run()) {
                    //$data['password'] = password_hash(hash("sha512", $data['password'], true), PASSWORD_BCRYPT, ['cost' => 10]);
                    $data['status'] = 2;
                    $data['name'] = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
                    $source = $this->model->get_source(['name' => 'Registration']);
                    if ($source) $data['source'] = $source->id;
                    //$data['dateadded'] = NOW;
                    $insert = $this->model->add_item($data);
                    if ($insert) {
                        $return_item = $this->model->get_item_to_return([db_prefix().'leads.id' => $insert]);
                        $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $return_item], CREATED);
                    } else {
                        $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
                    }
//                } else {
//                    $this->response(['type' => 'error', 'message' => _l('validation_error'), 'extra' => validation_errors()], UNPROCESSABLE_ENTITY);
//                }
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
        if (isset($decoded['user_id']) && $decoded['user_id'] != '' && $decoded['user_id'] != 'undefined') {
            $client = $this->model->get_item_to_return([db_prefix().'leads.user_id' => $decoded['user_id']]);//$this->_get_client_profile( $decoded['id'], $this->profile_fields);
            if ($client) {
//                $return_item = api_response_only($client, $this->profile_fields);
                $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $client], OK);
            } else {
                $this->response(['type' => 'error', 'message' => _l('user_not_found')], UNPROCESSABLE_ENTITY);
            }
        } else {
            $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
        }
    }

    /*private function _get_client_profile($f_id, $field_names = [])
    {
        if (!empty($f_id)) {
            $person = $this->model->get_item(['id' => $f_id]);//['f_id' => $f_id]);
            if ($person) {
                $person_to_return = new Stdclass();
                if (!empty($field_names)) foreach ($field_names as $fn) {
                    $person_to_return->$fn = $person->$fn ?? '';
                } else foreach ($this->profile_fields as $pf) {
                    $person_to_return->$pf = $person->$pf ?? '';
                }
                return $person_to_return;
            } else return false;
        } else {
            return false;
        }
    }*/

    function edit_post()
    {
        $request = $this->input->raw_input_stream;
        $decoded = json_decode($request, true);
        $decoded = clear_array($decoded);
        if (isset($decoded['user_id']) && $decoded['user_id'] != '' && $decoded['user_id'] != 'undefined') {
            if (!is_array($decoded)) {
                $this->response(['type' => 'error', 'message' => _l('json_error')], BAD_REQUEST);
            } else {
                if ($decoded) {// && $data = api_form($decoded, ['email', 'f_id', 'language', 'password'])
                    $client = $this->_get_client($decoded['user_id']);
                    if ($client && $client->deleted == 0/* && $client->blocked == 0*/) {
                        //user cant change email, first_name, last_name
                        if($decoded['first_name']) $data['first_name'] = $decoded['first_name'];
                        if($decoded['last_name']) $data['last_name'] = $decoded['last_name'];
                        if(isset($decoded['language'])) $data['default_language'] = $decoded['language'] == '' ? null : $decoded['language'];
                        if(isset($decoded['country'])) $country = $this->model->find_country(['short_name' => $decoded['country']]);
                        if(isset($country) && $country) $data['country'] = $country->country_id;
                        if(isset($decoded['phone'])) $data['phonenumber'] = $decoded['phone'];
                        if(isset($decoded['blocked'])) $data['blocked'] = $decoded['blocked'];
                        if(isset($decoded['is_deleted'])) $data['deleted'] = $decoded['is_deleted'];
                        if(isset($decoded['city'])) $data['city'] = $decoded['city'];
                        if(isset($decoded['street'])) $data['address'] = $decoded['street'];
                        if(isset($decoded['post_code'])) $data['zip'] = $decoded['post_code'];
                        $this->load->library('form_validation');
                        $this->form_validation->set_data($data);
                        $this->form_validation->set_rules($this->model->field_rules_update($data));
                        if ($this->form_validation->run()) {
                            $data['name'] = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
                            $update = $this->model->update_item(['id' => $client->id], $data);
                            if ($update) {
                                $return_item = $this->model->get_item_to_return([db_prefix().'leads.id' => $client->id]);
                                $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $return_item], OK);
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
        } else {
            $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
        }
    }

//    function synced_post()
//    {
//        $request = $this->input->raw_input_stream;
//        $decoded = json_decode($request, true);
//        $decoded = clear_array($decoded);
//        if (isset($decoded['user_id']) && $decoded['user_id'] != '' && $decoded['user_id'] != 'undefined') {
//            if (!is_array($decoded)) {
//                $this->response(['type' => 'error', 'message' => _l('json_error')], BAD_REQUEST);
//            } else {
//                if ($decoded) {// && $data = api_form($decoded, ['email', 'f_id', 'language', 'password'])
//                    $client = $this->model->get_item_special(['id' => $decoded['crm_user_id']], db_prefix() . 'leads');
//                    if ($client && $client->deleted == 0/* && $client->blocked == 0*/) {
//                        //user cant change email, first_name, last_name
//                        if($decoded['user_id']) $data['user_id'] = $decoded['user_id'];
//                        if($decoded['first_name']) $data['first_name'] = $decoded['first_name'];
//                        if($decoded['last_name']) $data['last_name'] = $decoded['last_name'];
//                        if(isset($decoded['language'])) $data['default_language'] = $decoded['language'] == '' ? null : $decoded['language'];
//                        if(isset($decoded['country'])) $country = $this->model->find_country(['short_name' => $decoded['country']]);
//                        if(isset($country) && $country) $data['country'] = $country->country_id;
//                        if(isset($decoded['phone'])) $data['phonenumber'] = $decoded['phone'];
//                        if(isset($decoded['blocked'])) $data['blocked'] = $decoded['blocked'];
//                        if(isset($decoded['is_deleted'])) $data['deleted'] = $decoded['is_deleted'];
//                        if(isset($decoded['city'])) $data['city'] = $decoded['city'];
//                        if(isset($decoded['street'])) $data['address'] = $decoded['street'];
//                        if(isset($decoded['post_code'])) $data['zip'] = $decoded['post_code'];
//                        $this->load->library('form_validation');
//                        $this->form_validation->set_data($data);
//                        $this->form_validation->set_rules($this->model->field_rules_update($data));
//                        if ($this->form_validation->run()) {
//                            $data['name'] = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
//                            $update = $this->model->update_item(['id' => $client->id], $data);
//                            if ($update) {
//                                $this->load->model(['leads_model']);
//                                $response = $this->leads_model->update_sync_request(['status' => 2], ['rel_id' => $client->id, 'rel_type' => 'client', 'status !=' => '2']);
//                                $return_item = $this->model->get_item_to_return([db_prefix().'leads.id' => $client->id]);
//                                $this->response(['type' => 'success', 'message' => _l('succeed'), 'extra' => $return_item], OK);
//                            } else {
//                                $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
//                            }
//                        } else {
//                            $this->response(['type' => 'error', 'message' => _l('validation_error'), 'extra' => validation_errors()], UNPROCESSABLE_ENTITY);
//                        }
//                    } else {
//                        $this->response(['type' => 'error', 'message' => _l('user_not_found')], UNPROCESSABLE_ENTITY);
//                    }
//                } else {
//                    $this->response(['type' => 'error', 'message' => _l('missing_info')], UNPROCESSABLE_ENTITY);
//                }
//            }
//        } else {
//            $this->response(['type' => 'error', 'message' => _l('failed')], UNPROCESSABLE_ENTITY);
//        }
//    }

}