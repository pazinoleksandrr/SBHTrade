<?php

defined('BASEPATH') or exit('No direct script access allowed');

use Carbon\CarbonInterval;

class Dashboard_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return array
     * Used in home dashboard page
     * Return all upcoming events this week
     */
    public function get_upcoming_events()
    {
        $monday_this_week = date('Y-m-d', strtotime('monday this week'));
        $sunday_this_week = date('Y-m-d', strtotime('sunday this week'));

        $this->db->where("(start BETWEEN '$monday_this_week' and '$sunday_this_week')");
        $this->db->where('(userid = ' . get_staff_user_id() . ' OR public = 1)');
        $this->db->order_by('start', 'desc');
        $this->db->limit(6);

        return $this->db->get(db_prefix() . 'events')->result_array();
    }

    /**
     * @param  integer (optional) Limit upcoming events
     * @return integer
     * Used in home dashboard page
     * Return total upcoming events next week
     */
    public function get_upcoming_events_next_week()
    {
        $monday_this_week = date('Y-m-d', strtotime('monday next week'));
        $sunday_this_week = date('Y-m-d', strtotime('sunday next week'));
        $this->db->where("(start BETWEEN '$monday_this_week' and '$sunday_this_week')");
        $this->db->where('(userid = ' . get_staff_user_id() . ' OR public = 1)');

        return $this->db->count_all_results(db_prefix() . 'events');
    }

    /**
     * @param  mixed
     * @return array
     * Used in home dashboard page, currency passed from javascript (undefined or integer)
     * Displays weekly payment statistics (chart)
     */
    public function get_weekly_payments_statistics($currency)
    {
        $all_payments                 = [];
        $has_permission_payments_view = has_permission('payments', '', 'view');
        $this->db->select(db_prefix() . 'invoicepaymentrecords.id, amount,' . db_prefix() . 'invoicepaymentrecords.date');
        $this->db->from(db_prefix() . 'invoicepaymentrecords');
        $this->db->join(db_prefix() . 'invoices', '' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid');
        $this->db->where('YEARWEEK(' . db_prefix() . 'invoicepaymentrecords.date) = YEARWEEK(CURRENT_DATE)');
        $this->db->where('' . db_prefix() . 'invoices.status !=', 5);
        if ($currency != 'undefined') {
            $this->db->where('currency', $currency);
        }

        if (!$has_permission_payments_view) {
            $this->db->where('invoiceid IN (SELECT id FROM ' . db_prefix() . 'invoices WHERE addedfrom=' . get_staff_user_id() . ' and addedfrom IN (SELECT staff_id FROM ' . db_prefix() . 'staff_permissions WHERE feature="invoices" AND capability="view_own"))');
        }

        // Current week
        $all_payments[] = $this->db->get()->result_array();
        $this->db->select(db_prefix() . 'invoicepaymentrecords.id, amount,' . db_prefix() . 'invoicepaymentrecords.date');
        $this->db->from(db_prefix() . 'invoicepaymentrecords');
        $this->db->join(db_prefix() . 'invoices', '' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid');
        $this->db->where('YEARWEEK(' . db_prefix() . 'invoicepaymentrecords.date) = YEARWEEK(CURRENT_DATE - INTERVAL 7 DAY) ');

        $this->db->where('' . db_prefix() . 'invoices.status !=', 5);
        if ($currency != 'undefined') {
            $this->db->where('currency', $currency);
        }

        if (!$has_permission_payments_view) {
            $this->db->where('invoiceid IN (SELECT id FROM ' . db_prefix() . 'invoices WHERE addedfrom=' . get_staff_user_id() . ' and addedfrom IN (SELECT staff_id FROM ' . db_prefix() . 'staff_permissions WHERE feature="invoices" AND capability="view_own"))');
        }

        // Last Week
        $all_payments[] = $this->db->get()->result_array();

        $chart = [
            'labels'   => get_weekdays(),
            'datasets' => [
                [
                    'label'           => _l('this_week_payments'),
                    'backgroundColor' => 'rgba(37,155,35,0.2)',
                    'borderColor'     => '#84c529',
                    'borderWidth'     => 1,
                    'tension'         => false,
                    'data'            => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                    ],
                ],
                [
                    'label'           => _l('last_week_payments'),
                    'backgroundColor' => 'rgba(197, 61, 169, 0.5)',
                    'borderColor'     => '#c53da9',
                    'borderWidth'     => 1,
                    'tension'         => false,
                    'data'            => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                    ],
                ],
            ],
        ];


        for ($i = 0; $i < count($all_payments); $i++) {
            foreach ($all_payments[$i] as $payment) {
                $payment_day = date('l', strtotime($payment['date']));
                $x           = 0;
                foreach (get_weekdays_original() as $day) {
                    if ($payment_day == $day) {
                        $chart['datasets'][$i]['data'][$x] += $payment['amount'];
                    }
                    $x++;
                }
            }
        }

        return $chart;
    }

    public function get_weekly_finance_statistics($status, $type, $where)
    {
        $all_deposits = [];
        $this->db->select(db_prefix() . 'finance.id, amount,' . db_prefix() . 'finance.dateadded');
        $this->db->from(db_prefix() . 'finance');
        $this->db->join(db_prefix() . 'leads', '' . db_prefix() . 'leads.id = ' . db_prefix() . 'finance.client');
        $this->db->where(db_prefix().'finance.type', $type);
        $this->db->where('YEARWEEK(' . db_prefix() . 'finance.dateadded) = YEARWEEK(CURRENT_DATE)');
        $this->db->where('deleted = 0 AND blocked = 0');
        if($where) $this->db->where($where);
        if ($status != 'undefined' && $status != 'all') {
            $this->db->where(db_prefix().'finance.status', $status);
        }
        $all_deposits[] = $this->db->get()->result_array();

        $this->db->select(db_prefix() . 'finance.id, amount,' . db_prefix() . 'finance.dateadded');
        $this->db->from(db_prefix() . 'finance');
        $this->db->join(db_prefix() . 'leads', '' . db_prefix() . 'leads.id = ' . db_prefix() . 'finance.client');
        $this->db->where(db_prefix().'finance.type', $type);
        $this->db->where('YEARWEEK(' . db_prefix() . 'finance.dateadded) = YEARWEEK(CURRENT_DATE - INTERVAL 7 DAY) ');
        $this->db->where('deleted = 0 AND blocked = 0');
        if($where) $this->db->where($where);
        if ($status != 'undefined' && $status != 'all') {
            $this->db->where(db_prefix().'finance.status', $status);
        }
        $all_deposits[] = $this->db->get()->result_array();

        $chart = [
            'labels'   => get_weekdays(),
            'datasets' => [
                [
                    'label'           => _l('this_week_'.$type.'s_'),
                    'backgroundColor' => 'rgba(37,155,35,0.2)',
                    'borderColor'     => '#84c529',
                    'borderWidth'     => 1,
                    'tension'         => false,
                    'data'            => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                    ],
                ],
                [
                    'label'           => _l('last_week_'.$type.'s_'),
                    'backgroundColor' => 'rgba(197, 61, 169, 0.5)',
                    'borderColor'     => '#c53da9',
                    'borderWidth'     => 1,
                    'tension'         => false,
                    'data'            => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                    ],
                ],
            ],
        ];


        for ($i = 0; $i < count($all_deposits); $i++) {
            foreach ($all_deposits[$i] as $payment) {
                $payment_day = date('l', strtotime($payment['dateadded']));
                $x           = 0;
                foreach (get_weekdays_original() as $day) {
                    if ($payment_day == $day) {
                        $chart['datasets'][$i]['data'][$x] += $payment['amount'];
                    }
                    $chart['datasets'][$i]['data'][$x] = (float)(string) $chart['datasets'][$i]['data'][$x];
                    $x++;
                }
            }
        }

        return $chart;
    }


    /**
     * @param  mixed
     * @return array
     * Used in home dashboard page, currency passed from javascript (undefined or integer)
     * Displays monthly payment statistics (chart)
     */
    public function get_monthly_payments_statistics($currency)
    {
        $all_payments                 = [];
        $has_permission_payments_view = has_permission('payments', '', 'view');
        $this->db->select('SUM(amount) as total, MONTH(' . db_prefix() . 'invoicepaymentrecords.date) as month');
        $this->db->from(db_prefix() . 'invoicepaymentrecords');
        $this->db->join(db_prefix() . 'invoices', '' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid');
        $this->db->where('YEAR(' . db_prefix() . 'invoicepaymentrecords.date) = YEAR(CURRENT_DATE)');
        $this->db->where('' . db_prefix() . 'invoices.status !=', 5);
        $this->db->group_by('month');

        if ($currency != 'undefined') {
            $this->db->where('currency', $currency);
        }

        if (!$has_permission_payments_view) {
            $this->db->where('invoiceid IN (SELECT id FROM ' . db_prefix() . 'invoices WHERE addedfrom=' . get_staff_user_id() . ' and addedfrom IN (SELECT staff_id FROM ' . db_prefix() . 'staff_permissions WHERE feature="invoices" AND capability="view_own"))');
        }

        $all_payments = $this->db->get()->result_array();

        for ($i = 1; $i <= 12; $i++) {
            if (!isset($all_payments[$i])) {
                $all_payments[$i]['total'] = 0;
                $all_payments[$i]['month'] = $i;
            }
            $all_payments[$i]['label'] = _l(date("F", mktime(0, 0, 0, $i, 1)));
        }
        usort($all_payments, function($a, $b) {
            return (int) $a['month'] <=> (int) $b['month'];
        });

        $chart = [
            'labels'   => array_column($all_payments, 'label'),
            'datasets' => [
                [
                    'label'           => _l('report_sales_type_income'),
                    'backgroundColor' => 'rgba(37,155,35,0.2)',
                    'borderColor'     => '#84c529',
                    'borderWidth'     => 1,
                    'tension'         => false,
                    'data'            => array_column($all_payments, 'total'),
                ],
            ],
        ];
        return $chart;
    }

    public function get_monthly_finance_statistics($status, $type, $where)
    {
        @$this->db->query('SET SESSION sql_mode =
                  REPLACE(REPLACE(REPLACE(
                  @@sql_mode,
                  "ONLY_FULL_GROUP_BY,", ""),
                  ",ONLY_FULL_GROUP_BY", ""),
                  "ONLY_FULL_GROUP_BY", "")');
        $all_deposits = [];
        $this->db->select(db_prefix() . 'finance.id, SUM(amount) as total, MONTH(' . db_prefix() . 'finance.dateadded) as month');
        $this->db->from(db_prefix() . 'finance');
        $this->db->join(db_prefix() . 'leads', '' . db_prefix() . 'leads.id = ' . db_prefix() . 'finance.client');
        $this->db->where(db_prefix().'finance.type', $type);
        $this->db->where('YEAR(' . db_prefix() . 'finance.dateadded) = YEAR(CURRENT_DATE)');
        $this->db->where('deleted = 0 AND blocked = 0');
        if($where) $this->db->where($where);
        if ($status != 'undefined' && $status != 'all') {
            $this->db->where(db_prefix().'finance.status', $status);
        }
        $this->db->group_by('month');
        $all_deposits = $this->db->get()->result_array();

        for ($i = 1; $i <= 12; $i++) {
            if (!isset($all_deposits[$i])) {
                $all_deposits[$i]['total'] = 0;
                $all_deposits[$i]['month'] = $i;
            }
            $all_deposits[$i]['label'] = _l(date("F", mktime(0, 0, 0, $i, 1)));
        }
        usort($all_deposits, function($a, $b) {
            return (int) $a['month'] <=> (int) $b['month'];
        });

        $chart = [
            'labels'   => array_column($all_deposits, 'label'),
            'datasets' => [
                [
                    'label'           => _l('report_sales_type_income'),
                    'backgroundColor' => 'rgba(37,155,35,0.2)',
                    'borderColor'     => '#84c529',
                    'borderWidth'     => 1,
                    'tension'         => false,
                    'data'            => array_column($all_deposits, 'total'),
                ],
            ],
        ];
        return $chart;
    }

    public function projects_status_stats()
    {
        $this->load->model('projects_model');
        $statuses = $this->projects_model->get_project_statuses();
        $colors   = get_system_favourite_colors();

        $chart = [
            'labels'   => [],
            'datasets' => [],
        ];

        $_data                         = [];
        $_data['data']                 = [];
        $_data['backgroundColor']      = [];
        $_data['hoverBackgroundColor'] = [];
        $_data['statusLink']           = [];


        $has_permission = has_permission('projects', '', 'view');
        $sql            = '';
        foreach ($statuses as $status) {
            $sql .= ' SELECT COUNT(*) as total';
            $sql .= ' FROM ' . db_prefix() . 'projects';
            $sql .= ' WHERE status=' . $status['id'];
            if (!$has_permission) {
                $sql .= ' AND id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . ')';
            }
            $sql .= ' UNION ALL ';
            $sql = trim($sql);
        }

        $result = [];
        if ($sql != '') {
            // Remove the last UNION ALL
            $sql    = substr($sql, 0, -10);
            $result = $this->db->query($sql)->result();
        }

        foreach ($statuses as $key => $status) {
            array_push($_data['statusLink'], admin_url('projects?status=' . $status['id']));
            array_push($chart['labels'], $status['name']);
            array_push($_data['backgroundColor'], $status['color']);
            array_push($_data['hoverBackgroundColor'], adjust_color_brightness($status['color'], -20));
            array_push($_data['data'], $result[$key]->total);
        }

        $chart['datasets'][]           = $_data;
        $chart['datasets'][0]['label'] = _l('home_stats_by_project_status');

        return $chart;
    }

    public function leads_status_stats()
    {
        $chart = [
            'labels'   => [],
            'datasets' => [],
        ];

        $_data                         = [];
        $_data['data']                 = [];
        $_data['backgroundColor']      = [];
        $_data['hoverBackgroundColor'] = [];
        $_data['statusLink']           = [];

        $result = [];
        if(is_admin()){
            $result = get_leads_summary();
        }else{
            $staff_id = get_staff_user_id();
            $staff = $this->staff_model->get_with_role($staff_id);
            if(isset($staff->role_name)){
                if($staff->role_name == 'Lead'){
                    $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);
                    if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
                    $result = get_leads_summary('', implode(',', $staff_ids));
                }
                if($staff->role_name == 'Sales'){
                    $result = get_leads_summary($staff_id);
                }
            }
        }
//print_r($result);die();
        foreach ($result as $status) {
            if ($status['color'] == '') {
                $status['color'] = '#737373';
            }
            if($status['name'] != _l('lost_leads') && (!isset($status['lost']) || !$status['lost'])){
                array_push($chart['labels'], $status['name']);
                array_push($_data['backgroundColor'], $status['color']);
                if (!isset($status['junk']) && !isset($status['lost'])) {
                    array_push($_data['statusLink'], admin_url('leads?status=' . $status['id']));
                }
                array_push($_data['hoverBackgroundColor'], adjust_color_brightness($status['color'], -20));
                array_push($_data['data'], $status['total']);
            }
        }

        $chart['datasets'][] = $_data;

        return $chart;
    }

    /**
     * Display total tickets awaiting reply by department (chart)
     * @return array
     */
    public function tickets_awaiting_reply_by_department()
    {
        $this->load->model('departments_model');
        $departments = $this->departments_model->get();
        $colors      = get_system_favourite_colors();
        $chart       = [
            'labels'   => [],
            'datasets' => [],
        ];

        $_data                         = [];
        $_data['data']                 = [];
        $_data['backgroundColor']      = [];
        $_data['hoverBackgroundColor'] = [];

        $i = 0;
        foreach ($departments as $department) {
            if (!is_admin()) {
                if (get_option('staff_access_only_assigned_departments') == 1) {
                    $staff_deparments_ids = $this->departments_model->get_staff_departments(get_staff_user_id(), true);
                    $departments_ids      = [];
                    if (count($staff_deparments_ids) == 0) {
                        $departments = $this->departments_model->get();
                        foreach ($departments as $department) {
                            array_push($departments_ids, $department['departmentid']);
                        }
                    } else {
                        $departments_ids = $staff_deparments_ids;
                    }
                    if (count($departments_ids) > 0) {
                        $this->db->where('department IN (SELECT departmentid FROM ' . db_prefix() . 'staff_departments WHERE departmentid IN (' . implode(',', $departments_ids) . ') AND staffid="' . get_staff_user_id() . '")');
                    }
                }
            }
            $this->db->where_in('status', [
                1,
                2,
                4,
            ]);

            $this->db->where('department', $department['departmentid']);
            $this->db->where(db_prefix() . 'tickets.merged_ticket_id IS NULL', null, false);
            $total = $this->db->count_all_results(db_prefix() . 'tickets');

            if ($total > 0) {
                $color = '#333';
                if (isset($colors[$i])) {
                    $color = $colors[$i];
                }
                array_push($chart['labels'], $department['name']);
                array_push($_data['backgroundColor'], $color);
                array_push($_data['hoverBackgroundColor'], adjust_color_brightness($color, -20));
                array_push($_data['data'], $total);
            }
            $i++;
        }

        $chart['datasets'][] = $_data;

        return $chart;
    }

    /**
     * Display total tickets awaiting reply by status (chart)
     * @return array
     */
    public function tickets_awaiting_reply_by_status()
    {
        $this->load->model('tickets_model');
        $statuses             = $this->tickets_model->get_ticket_status();
        $_statuses_with_reply = [
            1,
            2,
            4,
        ];

        $chart = [
            'labels'   => [],
            'datasets' => [],
        ];

        $_data                         = [];
        $_data['data']                 = [];
        $_data['backgroundColor']      = [];
        $_data['hoverBackgroundColor'] = [];
        $_data['statusLink']           = [];

        foreach ($statuses as $status) {
            if (in_array($status['ticketstatusid'], $_statuses_with_reply)) {
                if (!is_admin()) {
                    if (get_option('staff_access_only_assigned_departments') == 1) {
                        $staff_deparments_ids = $this->departments_model->get_staff_departments(get_staff_user_id(), true);
                        $departments_ids      = [];
                        if (count($staff_deparments_ids) == 0) {
                            $departments = $this->departments_model->get();
                            foreach ($departments as $department) {
                                array_push($departments_ids, $department['departmentid']);
                            }
                        } else {
                            $departments_ids = $staff_deparments_ids;
                        }
                        if (count($departments_ids) > 0) {
                            $this->db->where('department IN (SELECT departmentid FROM ' . db_prefix() . 'staff_departments WHERE departmentid IN (' . implode(',', $departments_ids) . ') AND staffid="' . get_staff_user_id() . '")');
                        }
                    }
                }

                $this->db->where('status', $status['ticketstatusid']);
                $this->db->where(db_prefix() . 'tickets.merged_ticket_id IS NULL', null, false);
                $total = $this->db->count_all_results(db_prefix() . 'tickets');
                if ($total > 0) {
                    array_push($chart['labels'], ticket_status_translate($status['ticketstatusid']));
                    array_push($_data['statusLink'], admin_url('tickets/index/' . $status['ticketstatusid']));
                    array_push($_data['backgroundColor'], $status['statuscolor']);
                    array_push($_data['hoverBackgroundColor'], adjust_color_brightness($status['statuscolor'], -20));
                    array_push($_data['data'], $total);
                }
            }
        }

        $chart['datasets'][] = $_data;

        return $chart;
    }

    public function get_effectiveness_report(/*$type, $status*/){
        @$this->db->query('SET SESSION sql_mode =
                  REPLACE(REPLACE(REPLACE(
                  @@sql_mode,
                  "ONLY_FULL_GROUP_BY,", ""),
                  ",ONLY_FULL_GROUP_BY", ""),
                  "ONLY_FULL_GROUP_BY", "")');
        $join_d = '';$where_di = '';
        $join_w = '';$where_wi = '';
        /*switch ($type) {
            case 'this_week':
                $join_d .= ' AND YEARWEEK(d.dateadded) = YEARWEEK(CURRENT_DATE)';
                $where_di .= ' AND YEARWEEK(di.dateadded) = YEARWEEK(CURRENT_DATE)';
                $join_w .= ' AND YEARWEEK(w.dateadded) = YEARWEEK(CURRENT_DATE)';
                $where_wi .= ' AND YEARWEEK(wi.dateadded) = YEARWEEK(CURRENT_DATE)';
//                $this->db->where('YEARWEEK(d.dateadded) = YEARWEEK(CURRENT_DATE)');
//                $this->db->where('YEARWEEK(w.dateadded) = YEARWEEK(CURRENT_DATE)');
                break;
            case 'last_week':
                $join_d .= ' AND YEARWEEK(d.dateadded) = YEARWEEK(CURRENT_DATE - INTERVAL 7 DAY)';
                $where_di .= ' AND YEARWEEK(di.dateadded) = YEARWEEK(CURRENT_DATE - INTERVAL 7 DAY)';
                $join_w .= ' AND YEARWEEK(w.dateadded) = YEARWEEK(CURRENT_DATE - INTERVAL 7 DAY)';
                $where_wi .= ' AND YEARWEEK(wi.dateadded) = YEARWEEK(CURRENT_DATE - INTERVAL 7 DAY)';
//                $this->db->where('YEARWEEK(d.dateadded) = YEARWEEK(CURRENT_DATE - INTERVAL 7 DAY) ');
//                $this->db->where('YEARWEEK(w.dateadded) = YEARWEEK(CURRENT_DATE - INTERVAL 7 DAY) ');
                break;
            case 'this_month':
                $join_d .= ' AND YEAR(d.dateadded) = YEAR(CURRENT_DATE) AND MONTH(d.dateadded) = MONTH(CURRENT_DATE)';
                $where_di .= ' AND YEAR(di.dateadded) = YEAR(CURRENT_DATE) AND MONTH(di.dateadded) = MONTH(CURRENT_DATE)';
                $join_w .= ' AND YEAR(w.dateadded) = YEAR(CURRENT_DATE) AND MONTH(w.dateadded) = MONTH(CURRENT_DATE)';
                $where_wi .= ' AND YEAR(wi.dateadded) = YEAR(CURRENT_DATE) AND MONTH(wi.dateadded) = MONTH(CURRENT_DATE)';
//                $this->db->where('YEAR(d.dateadded) = YEAR(CURRENT_DATE)');
                break;
            case 'last_month':
                $join_d .= ' AND YEAR(d.dateadded) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(d.dateadded) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)';
                $where_di .= ' AND YEAR(di.dateadded) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(di.dateadded) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)';
                $join_w .= ' AND YEAR(w.dateadded) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(w.dateadded) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)';
                $where_wi .= ' AND YEAR(wi.dateadded) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(wi.dateadded) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)';
//                $this->db->where('YEAR(d.dateadded) = YEAR(CURRENT_DATE - INTERVAL 7 DAY)');
                break;
            default:
                throw new InvalidArgumentException("Invalid Type Provided");
        }

        if ($status != 'undefined' && $status != 'all') {
            $join_d .= ' AND d.status = "'.$status.'"';
            $where_di .= ' AND di.status = "'.$status.'"';
            $join_w .= ' AND w.status = "'.$status.'"';
            $where_wi .= ' AND wi.status = "'.$status.'"';
//            $this->db->where('d.status', $status);
//            $this->db->where('w.status', $status);
        }*/

        $this->db->select(db_prefix().'staff.staffid, concat ('.db_prefix().'staff.firstname, " ", '.db_prefix().'staff.lastname) as full_name, '.db_prefix().'roles.name as role, 
                        '.db_prefix().'customfieldsvalues.value as department, concat(coalesce(d1.total, 0), " / ", coalesce(d2.total, 0)) as deposit,
                         concat(coalesce(w1.total, 0), " / ", coalesce(w2.total, 0)) as withdrawal');
        //$this->db->from(db_prefix().'staff');


        $this->db->join(db_prefix() . 'roles', db_prefix().'roles.roleid = '.db_prefix().'staff.role', 'left');

        $this->db->join(db_prefix() . 'customfieldsvalues', db_prefix().'customfieldsvalues.relid = '.db_prefix().'staff.staffid', 'left');
        $this->db->join(db_prefix() . 'customfields', db_prefix().'customfields.id = '.db_prefix().'customfieldsvalues.fieldid', 'left');

        $this->db->join(db_prefix().'leads', db_prefix().'leads.assigned = '.db_prefix().'staff.staffid AND '.db_prefix().'leads.deleted = 0 AND blocked = 0', 'left');
        $this->db->join('(select assigned, group_concat('.db_prefix().'leads.id) as clients from '.db_prefix().'leads where '.db_prefix().'leads.deleted = 0 AND blocked = 0 group by '.db_prefix().'leads.assigned) c', 'c.assigned = '.db_prefix().'staff.staffid', 'left');

        //$this->db->join(db_prefix().'finance d', 'd.client = '.db_prefix().'leads.id AND d.type = "deposit"'.$join_d, 'left');
        $this->db->join('(select di.client, sum(di.amount) as total from '.db_prefix().'finance di where di.type = "deposit" '.$where_di.' AND di.status = "completed" group by di.client) as d1', 'd1.client = '.db_prefix().'leads.id or d1.client in (c.clients)', 'left');
        $this->db->join('(select di.client, sum(di.amount) as total from '.db_prefix().'finance di where di.type = "deposit" '.$where_di.' AND di.status = "in_process" group by di.client) as d2', 'd2.client = '.db_prefix().'leads.id or d2.client in (c.clients)', 'left');

        //$this->db->join(db_prefix().'finance w', 'w.client = '.db_prefix().'leads.id AND w.type = "withdrawal"'.$join_w, 'left');
        $this->db->join('(select wi.client, sum(wi.amount) as total from '.db_prefix().'finance wi where wi.type = "withdrawal" '.$where_wi.' AND wi.status = "completed" group by wi.client) as w1', 'w1.client = '.db_prefix().'leads.id or w1.client in (c.clients)', 'left');
        $this->db->join('(select wi.client, sum(wi.amount) as total from '.db_prefix().'finance wi where wi.type = "withdrawal" '.$where_wi.' AND wi.status = "in_process" group by wi.client) as w2', 'w2.client = '.db_prefix().'leads.id or w2.client in (c.clients)', 'left');


        $this->db->where([db_prefix().'staff.admin' => 0, db_prefix().'staff.is_not_staff' => 0]);

        $this->db->where([db_prefix().'customfields.name' => 'Department', db_prefix().'customfields.slug' => 'staff_department', db_prefix().'customfields.type' => 'select']);

        $this->db->group_by(db_prefix().'staff.staffid');

//        print_r($this->db->get(db_prefix().'staff')->result());die();
        return $this->db->get(db_prefix().'staff')->result();
    }
}
