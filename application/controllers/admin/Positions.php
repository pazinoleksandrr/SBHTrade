<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Positions extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        //$this->load->model('positions_model');
    }

    /*public function index($id = '')
    {
        $this->list_positions($id);
    }*/

    public function init_relation_positions($rel_id, $rel_type)
    {
        if ($this->input->is_ajax_request()) {
            $this->load->model('leads_model');
            $client = $this->leads_model->get($rel_id);
            if($client){
                $this->load->helper('positions');
                $profit_data_real = fetch_profit($client, 0);
                $profit_data_real = $profit_data_real['status'] == 'success' ? $profit_data_real['message'] : [];
                $profit_data_demo = fetch_profit($client, 1);
                $profit_data_demo = $profit_data_demo['status'] == 'success' ? $profit_data_demo['message'] : [];
            }
            $this->app->get_table_data('positions_relations', [
                'rel_id' => $rel_id,
                'rel_type' => $rel_type,
                'profit_data_real' => $profit_data_real,
                'profit_data_demo' => $profit_data_demo,
            ]);
        }
    }
}
