<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Orders extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('orders_model');
    }

    /*public function index($id = '')
    {
        $this->list_orders($id);
    }*/

    public function init_relation_orders($rel_id, $rel_type)
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('orders_relations', [
                'rel_id' => $rel_id,
                'rel_type' => $rel_type,
            ]);
        }
    }
}
