<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Voip extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    /* View VoIP */
    public function index()
    {
        if (!has_permission('voip', '', 'view') || !is_admin()) {
            access_denied('voip');
        }

        $this->load->model('payment_modes_model');
        $this->load->model('settings_model');
        $this->load->model('api_keys_model');
        $data = [];

        if ($this->input->post()) {
            if (!has_permission('voip', '', 'view') || !is_admin()) {
                access_denied('voip');
            }
            $post_data = $this->input->post();
            $tmpData   = $this->input->post(null, false);
            if (isset($post_data['settings']['commpeak_crm_id'])) {
                $post_data['settings']['commpeak_crm_id'] = $tmpData['settings']['commpeak_crm_id'];
            }

            if (isset($post_data['settings']['commpeak_client_id'])) {
                $post_data['settings']['commpeak_client_id'] = $tmpData['settings']['commpeak_client_id'];
            }


            $success['settings'] = $this->settings_model->update($post_data);

            if ($success > 0) {
                set_alert('success', _l('settings_updated'));
            }

            redirect(admin_url('voip'), 'refresh');
        }

        $this->load->view('admin/voip/manage', $data);
    }

    function voip_call($client_id = ''){
        if (!is_staff_member()) {
            access_denied('voip_call');
        }

        $this->load->model('leads_model');
        $client = $this->leads_model->get($client_id);
        if($client_id && $client && $client->phonenumber != ''){
            $voip_crm_id = get_option('commpeak_crm_id');
            $voip_client_id = get_option('commpeak_client_id');
            if($voip_crm_id && $voip_client_id){
                $this->load->model('leads_model');
                $staff_login = $this->staff_model->get_with_voip_id(get_staff_user_id());
                if($staff_login && $staff_login->voip_id){
                    $handle=curl_init( "https://click2call.pbx.commpeak.com/call/$voip_crm_id/$voip_client_id/$staff_login->voip_id/$client->phonenumber");

                    curl_setopt($handle, CURLOPT_VERBOSE, true);
                    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);

                    $content = curl_exec($handle);
                    $response = json_decode($content);
                    if(isset($response->success) && $response->message = 'OK'){
                        set_alert('success', _l('request_sent'));
                    }else set_alert('warning', _l('failed'));
                    //var_dump($content);die();
                }else set_alert('warning', _l('check_staff_voip_login'));
            }else set_alert('warning', _l('check_voip_settings'));
        }else set_alert('warning', _l('lead_not_found'));

        $ref = $_SERVER['HTTP_REFERER'];
        // if user access voip/voip_call/ID to prevent redirecting on the same url
        if (!$ref || strpos($ref, 'voip/voip_call/' . $client_id) !== false) {
            redirect(admin_url('leads'));
        }
        redirect($ref);
    }

}