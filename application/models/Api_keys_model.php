<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Api_keys_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param integer ID (optional)
     * @return mixed
     * Get currency object based on passed id if not passed id return array of all currencies
     */
    public function get($id = false)
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            $api_key = $this->db->get(db_prefix() . 'api_keys')->row();
            return $api_key;
        }
        $api_keys = $this->db->get(db_prefix() . 'api_keys')->result_array();

        return $api_keys;
    }

    /**
     * @param array $_POST data
     * @return boolean
     * Update currency values
     */
    public function edit($data)
    {
        $api_data = $this->db->get(db_prefix() . 'api_keys')->row();
        if (!empty($api_data)) {
            $this->db->where('id', $api_data->id);
            $this->db->update(db_prefix() . 'api_keys', ['api_key' => trim($data)]);
            log_activity('Api key Updated [' . $data . ']');
            return true;
        }
        else {
            $this->db->insert(db_prefix() . 'api_keys', $data);
            $insert_id = $this->db->insert_id();
            if ($insert_id) {
                log_activity('Api key added [Key: ' . $data . ']');
                return true;
            }
        }
        return false;
    }
}
