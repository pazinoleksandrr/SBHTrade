<?php

use app\services\utilities\Str;

defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('dashboard_model');
    }

    /* This is admin dashboard view */
    public function index()
    {
        close_setup_menu();
        $this->load->model('departments_model');
        $this->load->model('todo_model');
        $data['departments'] = $this->departments_model->get();

        $data['todos'] = $this->todo_model->get_todo_items(0);
        // Only show last 5 finished todo items
        $this->todo_model->setTodosLimit(5);
        $data['todos_finished']            = $this->todo_model->get_todo_items(1);
        $data['upcoming_events_next_week'] = $this->dashboard_model->get_upcoming_events_next_week();
        $data['upcoming_events']           = $this->dashboard_model->get_upcoming_events();
        $data['title']                     = _l('dashboard_string');

        $this->load->model('contracts_model');
        $data['expiringContracts'] = $this->contracts_model->get_contracts_about_to_expire(get_staff_user_id());

        $this->load->model('currencies_model');
        $data['currencies']    = $this->currencies_model->get();
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        $data['activity_log']  = $this->misc_model->get_activity_log();
        // Tickets charts
        $tickets_awaiting_reply_by_status     = $this->dashboard_model->tickets_awaiting_reply_by_status();
        $tickets_awaiting_reply_by_department = $this->dashboard_model->tickets_awaiting_reply_by_department();

        $data['tickets_reply_by_status']              = json_encode($tickets_awaiting_reply_by_status);
        $data['tickets_awaiting_reply_by_department'] = json_encode($tickets_awaiting_reply_by_department);

        $data['tickets_reply_by_status_no_json']              = $tickets_awaiting_reply_by_status;
        $data['tickets_awaiting_reply_by_department_no_json'] = $tickets_awaiting_reply_by_department;

        $data['projects_status_stats'] = json_encode($this->dashboard_model->projects_status_stats());
        $data['leads_status_stats']    = json_encode($this->dashboard_model->leads_status_stats());
        $data['google_ids_calendars']  = $this->misc_model->get_google_calendar_ids();
        $data['bodyclass']             = 'dashboard invoices-total-manual';
        $this->load->model('announcements_model');
        $data['staff_announcements']             = $this->announcements_model->get();
        $data['total_undismissed_announcements'] = $this->announcements_model->get_total_undismissed_announcements();

        $this->load->model('projects_model');
        $data['projects_activity'] = $this->projects_model->get_activity('', hooks()->apply_filters('projects_activity_dashboard_limit', 20));
        add_calendar_assets();
        $this->load->model('utilities_model');
        $this->load->model('estimates_model');
        $data['estimate_statuses'] = $this->estimates_model->get_statuses();

        $this->load->model('proposals_model');
        $data['proposal_statuses'] = $this->proposals_model->get_statuses();

        /*$wps_currency = 'undefined';
        if (is_using_multiple_currencies()) {
            $wps_currency = $data['base_currency']->id;
        }*/
        //$data['weekly_payment_stats'] = json_encode($this->dashboard_model->get_weekly_payments_statistics($wps_currency));
        $where = '';
        if(!is_admin()){
            $staff_id = get_staff_user_id();
            $staff = $this->staff_model->get_with_role($staff_id);
            if(isset($staff->role_name)){
                if($staff->role_name == 'Lead'){
                    $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);
                    if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
                    $where = db_prefix().'leads.assigned in ('.implode(',', $staff_ids).')';
                }
                if($staff->role_name == 'Sales'){
                    $where = [db_prefix() . 'leads.assigned' => $staff_id];
                }
            }
        }
        $data['weekly_deposit_stats'] = json_encode($this->dashboard_model->get_weekly_finance_statistics('all', 'deposit', $where));//$wps_currency));
        $data['weekly_withdrawal_stats'] = json_encode($this->dashboard_model->get_weekly_finance_statistics('all', 'withdrawal', $where));//$wps_currency));

        $data['effectiveness_report'] = [];
        if (is_admin()) {
            $data['effectiveness_report'] = $this->dashboard_model->get_effectiveness_report('this_month', 'all');
        }

        $data['dashboard'] = true;

        $data['user_dashboard_visibility'] = get_staff_meta(get_staff_user_id(), 'dashboard_widgets_visibility');

        if (!$data['user_dashboard_visibility']) {
            $data['user_dashboard_visibility'] = [];
        } else {
            $data['user_dashboard_visibility'] = unserialize($data['user_dashboard_visibility']);
        }
        $data['user_dashboard_visibility'] = json_encode($data['user_dashboard_visibility']);

        $data['tickets_report'] = [];
        if (is_admin()) {
            $data['tickets_report'] = (new \app\services\TicketsReportByStaff())->filterBy('this_month');
        }

        $this->load->model('reports_model');
        $data['leads_staff_report'] = json_encode($this->reports_model->leads_staff_report());//'', '', '', db_prefix().'leads.deleted = 0 AND blocked = 0'));
        $data['departments'] = $this->reports_model->get_departments();

        $data = hooks()->apply_filters('before_dashboard_render', $data);
        $this->load->view('admin/dashboard/dashboard', $data);
    }

    public function staff_report()
    {
        if ($this->input->is_ajax_request()) {
            $where = '';//db_prefix().'leads.deleted = 0 AND blocked = 0';
            /*if(!is_admin()){
                $staff_id = get_staff_user_id();
                $staff = $this->staff_model->get_with_role($staff_id);
                if(isset($staff->role_name)){
                    if($staff->role_name == 'Lead'){
                        $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);
                        if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
                        $where = db_prefix().'leads.assigned in ('.implode(',', $staff_ids).')';
                    }
                }
            }*/
            $this->load->model('reports_model');
            echo json_encode($this->reports_model->leads_staff_report($this->input->post('from_date'), $this->input->post('to_date'), $this->input->post('department'), $where));
            die();
        }
    }

    public function weekly_deposits_statistics($status)
    {
        if ($this->input->is_ajax_request()) {
            $where = '';
            if(!is_admin()){
                $staff_id = get_staff_user_id();
                $staff = $this->staff_model->get_with_role($staff_id);
                if(isset($staff->role_name)){
                    if($staff->role_name == 'Lead'){
                        $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);
                        if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
                        $where = db_prefix().'leads.assigned in ('.implode(',', $staff_ids).')';
                    }
                    if($staff->role_name == 'Sales'){
                        $where = [db_prefix() . 'leads.assigned', $staff_id];
                    }
                }
            }
            echo json_encode($this->dashboard_model->get_weekly_finance_statistics($status, 'deposit', $where));
            die();
        }
    }

    public function monthly_deposits_statistics($status)
    {
        if ($this->input->is_ajax_request()) {
            $where = '';
            if(!is_admin()){
                $staff_id = get_staff_user_id();
                $staff = $this->staff_model->get_with_role($staff_id);
                if(isset($staff->role_name)){
                    if($staff->role_name == 'Lead'){
                        $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);
                        if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
                        $where = db_prefix().'leads.assigned in ('.implode(',', $staff_ids).')';
                    }
                    if($staff->role_name == 'Sales'){
                        $where = [db_prefix() . 'leads.assigned', $staff_id];
                    }
                }
            }
            echo json_encode($this->dashboard_model->get_monthly_finance_statistics($status, 'deposit', $where));
            die();
        }
    }

    public function weekly_withdrawals_statistics($status)
    {
        if ($this->input->is_ajax_request()) {
            $where = '';
            if(!is_admin()){
                $staff_id = get_staff_user_id();
                $staff = $this->staff_model->get_with_role($staff_id);
                if(isset($staff->role_name)){
                    if($staff->role_name == 'Lead'){
                        $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);
                        if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
                        $where = db_prefix().'leads.assigned in ('.implode(',', $staff_ids).')';
                    }
                    if($staff->role_name == 'Sales'){
                        $where = [db_prefix() . 'leads.assigned', $staff_id];
                    }
                }
            }
            echo json_encode($this->dashboard_model->get_weekly_finance_statistics($status, 'withdrawal', $where));
            die();
        }
    }

    public function monthly_withdrawals_statistics($status)
    {
        if ($this->input->is_ajax_request()) {
            $where = '';
            if(!is_admin()){
                $staff_id = get_staff_user_id();
                $staff = $this->staff_model->get_with_role($staff_id);
                if(isset($staff->role_name)){
                    if($staff->role_name == 'Lead'){
                        $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);
                        if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
                        $where = db_prefix().'leads.assigned in ('.implode(',', $staff_ids).')';
                    }
                    if($staff->role_name == 'Sales'){
                        $where = [db_prefix() . 'leads.assigned', $staff_id];
                    }
                }
            }
            echo json_encode($this->dashboard_model->get_monthly_finance_statistics($status, 'withdrawal', $where));
            die();
        }
    }

    /* Chart weekly payments statistics on home page / ajax */
    public function weekly_payments_statistics($currency)
    {
        if ($this->input->is_ajax_request()) {
            echo json_encode($this->dashboard_model->get_weekly_payments_statistics($currency));
            die();
        }
    }

    /* Chart monthly payments statistics on home page / ajax */
    public function monthly_payments_statistics($currency)
    {
        if ($this->input->is_ajax_request()) {
            echo json_encode($this->dashboard_model->get_monthly_payments_statistics($currency));
            die();
        }
    }

    public function ticket_widget($type)
    {
        $data['tickets_report'] = (new \app\services\TicketsReportByStaff())->filterBy($type);
        $this->load->view('admin/dashboard/widgets/tickets_report_table', $data);
    }

    public function effectiveness_widget(/*$type, $status*/)
    {
        $data['effectiveness_report'] = $this->dashboard_model->get_effectiveness_report();//$type, $status);
        //print_r($data);die();
        $this->load->view('admin/dashboard/widgets/effectiveness_report_table', $data);
    }
}