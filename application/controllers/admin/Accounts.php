<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Accounts extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('accounts_model');
    }

    /*public function index($id = '')
    {
        $this->list_accounts($id);
    }*/

    public function init_relation_accounts($rel_id, $rel_type)
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('accounts_relations', [
                'rel_id' => $rel_id,
                'rel_type' => $rel_type,
            ]);
        }
    }
}
