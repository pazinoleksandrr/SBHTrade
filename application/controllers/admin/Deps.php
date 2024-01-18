<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Deps extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('deps_model');
        if(!is_admin() && !is_lead_staff()){
            access_denied('Departments');
        }
    }

    /* List all departments */
    public function index($id = '')
    {
        close_setup_menu();

        if (!is_staff_member()) {
            access_denied('Departments');
        }

        if ($this->input->is_ajax_request()) {
            if (!is_staff_member()) {
                ajax_access_denied();
            }
            $this->app->get_table_data('deps');
        }

        $data['title']    = _l('departments');
        // in case accesed the url leads/index/ directly with id - used in search
        $data['departmentid']   = $id;

        $this->load->view('admin/deps/manage', $data);
    }

    public function department($id = '')
    {
        if ($this->input->post()) {
            $message          = '';
            $data             = $this->input->post();

            if (!$this->input->post('id')){
                //create new department
                $dep_data = $this->deps_model->get_current_data();//print_r($dep_data);die();
                $temp = explode(',', $dep_data['options']);
                $departments = array_map('trim', $temp);
                if(in_array(trim($data['name']), $departments)){
//                if(stripos($dep_data['options'], $data['name'])){//if you want to disable similar names, use this
                    echo json_encode([
                        'success'              => true,
                        'message'              => _l('is_referenced_name_', _l('department_lowercase')),
                    ]);
                }else{
                    $new_options = $dep_data['options'] != '' ? $dep_data['options'] .= ', '.trim($data['name']) : trim($data['name']);
                    $update = $this->deps_model->update(['options' => $new_options], $dep_data['id'], trim($data['name']));
                    if ($update) {
                        $success = true;
                        $message = _l('added_successfully', _l('department'));
                    }
                    echo json_encode([
                        'success'              => $success,
                        'message'              => $message,
                    ]);
                }
            } else {
                //update department
                $dep_data = $this->deps_model->get_current_data();//print_r($dep_data);die();
                $temp = explode(',', $dep_data['options']);
                $departments = array_map('trim', $temp);
                if(in_array(trim($data['name']), $departments) && $departments[(int) $data['id'] - 1] != trim($data['name'])){
                    echo json_encode([
                        'success'              => true,
                        'message'              => _l('is_referenced_name_', _l('department_lowercase')),
                    ]);
                }elseif($departments[(int) $data['id'] - 1] == trim($data['name'])){
                    echo json_encode([
                        'success'              => true,
                        'message'              => _l('updated_successfully', _l('department'))
                    ]);
                }else{
                    //if staffs exist at this department, then don't touch it and create new (Perfex CRM logic)
                    //used logic : updates all old staff department fields with updated department data
                    //$this->load->model('staff_model');
                    //$staff_count = total_rows(db_prefix().'customfieldsvalues', ['fieldto' => 'staff', 'value' => $departments[(int) $data['id'] - 1]]);
                    $index = (int) $data['id'] - 1;
                    $old_name = $departments[$index];
                    $departments[$index] = trim($data['name']);
                    $new_options = implode(', ', $departments);//print_r([$id, $departments, $new_options]);die();
                    $update = $this->deps_model->update(['options' => $new_options], $dep_data['id'], trim($data['name']), $old_name);
                    if ($update) {
                        $success = true;
                        $message =_l('updated_successfully', _l('department'));
                    }else{

                        $success = false;
                        $message =_l('failed', _l('department'));
                    }
                    echo json_encode([
                        'success'              => $success,
                        'message'              => $message,
                    ]);
                }
            }
            die;
        }
    }

    public function delete($id)
    {
        if (!$id){
            redirect(admin_url('deps'));
        }
        $dep_data = $this->deps_model->get_current_data();
        $temp = explode(',', $dep_data['options']);
        $departments = array_map('trim', $temp);
        $index = (int) $id - 1;
        $rel = $this->deps_model->get_rel_data($departments[$index], $dep_data['id']);
        if($rel){
            set_alert('warning', _l('is_referenced', _l('department_lowercase')));
        }else{
            $name = $departments[$index];
            unset($departments[$index]);
            $new_options = implode(', ', $departments);
            $update = $this->deps_model->update(['options' => $new_options], $dep_data['id'], $name);
            if ($update) {
                set_alert('success', _l('deleted', _l('department')));
            }else set_alert('warning', _l('problem_deleting', _l('department_lowercase')));
        }
        redirect(admin_url('deps'));
    }

    public function init_staff_table($rel_id)
    {
        if ($this->input->is_ajax_request()) {
            $rel_type = $this->input->get('v');
            $this->app->get_table_data('staff_list_deps', [
                'rel_id'   => $rel_id,
                'rel_type' => $rel_type,
            ]);
        }
    }
}
