<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Reports_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *  Leads conversions monthly report
     * @param   mixed $month  which month / chart
     * @return  array          chart data
     */
    public function leads_monthly_report($month)
    {
        $month_dates = [];
        $data        = [];
        for ($d = 1; $d <= 31; $d++) {
            $time = mktime(12, 0, 0, $month, $d, date('Y'));
            if (date('m', $time) == $month) {
                $month_dates[] = _d(date('Y-m-d', $time));
                $data[]        = 0;
            }
        }
        $chart = [
            'labels'   => $month_dates,
            'datasets' => [
                [
                    'label'           => _l('clients_'),
                    'backgroundColor' => 'rgba(197, 61, 169, 0.5)',
                    'borderColor'     => '#c53da9',
                    'borderWidth'     => 1,
                    'tension'         => false,
                    'data'            => $data,
                ],
            ],
        ];

        $where = ' AND lost = 0 AND deleted = 0 AND blocked = 0 ';
        if(!is_admin()){
            $staff_id = get_staff_user_id();
            $staff = $this->staff_model->get_with_role($staff_id);
            if(isset($staff->role_name)){
                if($staff->role_name == 'Lead'){
                    $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);
                    if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
                    $where .= 'AND assigned in ('.implode(',', $staff_ids).')';
                }else return $chart;
            }
        }

        $result      = $this->db->query('select dateadded from ' . db_prefix() . 'leads where MONTH(dateadded) = ' . $month . ' '.$where)->result_array();//AND status = 1 and lost = 0')->result_array();

        foreach ($result as $lead) {
            $i = 0;
            foreach ($chart['labels'] as $date) {
                if (_d(date('Y-m-d', strtotime($lead['dateadded']))) == $date) {
                    $chart['datasets'][0]['data'][$i]++;
                }
                $i++;
            }
        }

        return $chart;
    }

    public function get_stats_chart_data($label, $where, $dataset_options, $year)
    {
        $chart = [
            'labels'   => [],
            'datasets' => [
                [
                    'label'       => $label,
                    'borderWidth' => 1,
                    'tension'     => false,
                    'data'        => [],
                ],
            ],
        ];

        foreach ($dataset_options as $key => $val) {
            $chart['datasets'][0][$key] = $val;
        }
        $this->load->model('expenses_model');
        $categories = $this->expenses_model->get_category();
        foreach ($categories as $category) {
            $_where['category']   = $category['id'];
            $_where['YEAR(date)'] = $year;
            if (count($where) > 0) {
                foreach ($where as $key => $val) {
                    $_where[$key] = $this->db->escape_str($val);
                }
            }
            array_push($chart['labels'], $category['name']);
            array_push($chart['datasets'][0]['data'], total_rows(db_prefix() . 'expenses', $_where));
        }

        return $chart;
    }

    public function get_expenses_vs_income_report($year = '')
    {
        $this->load->model('expenses_model');

        $months_labels  = [];
        $total_expenses = [];
        $total_income   = [];
        $i              = 0;
        if (!is_numeric($year)) {
            $year = date('Y');
        }
        for ($m = 1; $m <= 12; $m++) {
            array_push($months_labels, _l(date('F', mktime(0, 0, 0, $m, 1))));
            $this->db->select('id')->from(db_prefix() . 'expenses')->where('MONTH(date)', $m)->where('YEAR(date)', $year);
            $expenses = $this->db->get()->result_array();
            if (!isset($total_expenses[$i])) {
                $total_expenses[$i] = [];
            }
            if (count($expenses) > 0) {
                foreach ($expenses as $expense) {
                    $expense = $this->expenses_model->get($expense['id']);
                    $total   = $expense->amount;
                    // Check if tax is applied
                    if ($expense->tax != 0) {
                        $total += ($total / 100 * $expense->taxrate);
                    }
                    if ($expense->tax2 != 0) {
                        $total += ($expense->amount / 100 * $expense->taxrate2);
                    }
                    $total_expenses[$i][] = $total;
                }
            } else {
                $total_expenses[$i][] = 0;
            }
            $total_expenses[$i] = array_sum($total_expenses[$i]);
            // Calculate the income
            $this->db->select('amount');
            $this->db->from(db_prefix() . 'invoicepaymentrecords');
            $this->db->join(db_prefix() . 'invoices', '' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid');
            $this->db->where('MONTH(' . db_prefix() . 'invoicepaymentrecords.date)', $m);
            $this->db->where('YEAR(' . db_prefix() . 'invoicepaymentrecords.date)', $year);
            $payments = $this->db->get()->result_array();
            if (!isset($total_income[$m])) {
                $total_income[$i] = [];
            }
            if (count($payments) > 0) {
                foreach ($payments as $payment) {
                    $total_income[$i][] = $payment['amount'];
                }
            } else {
                $total_income[$i][] = 0;
            }
            $total_income[$i] = array_sum($total_income[$i]);
            $i++;
        }
        $chart = [
            'labels'   => $months_labels,
            'datasets' => [
                [
                    'label'           => _l('report_sales_type_income'),
                    'backgroundColor' => 'rgba(37,155,35,0.2)',
                    'borderColor'     => '#84c529',
                    'borderWidth'     => 1,
                    'tension'         => false,
                    'data'            => $total_income,
                ],
                [
                    'label'           => _l('expenses'),
                    'backgroundColor' => 'rgba(252,45,66,0.4)',
                    'borderColor'     => '#fc2d42',
                    'borderWidth'     => 1,
                    'tension'         => false,
                    'data'            => $total_expenses,
                ],
            ],
        ];

        return $chart;
    }

    /**
     * Chart leads weeekly report
     * @return array  chart data
     */
    public function leads_this_week_report()
    {
        $colors = get_system_favourite_colors();
        $chart  = [
            'labels' => [
                _l('wd_monday'),
                _l('wd_tuesday'),
                _l('wd_wednesday'),
                _l('wd_thursday'),
                _l('wd_friday'),
                _l('wd_saturday'),
                _l('wd_sunday'),
            ],
            'datasets' => [
                [
                    'data' => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                    ],
                    'backgroundColor' => [
                        $colors[0],
                        $colors[1],
                        $colors[2],
                        $colors[3],
                        $colors[4],
                        $colors[5],
                        $colors[6],
                    ],
                    'hoverBackgroundColor' => [
                        adjust_color_brightness($colors[0], -20),
                        adjust_color_brightness($colors[1], -20),
                        adjust_color_brightness($colors[2], -20),
                        adjust_color_brightness($colors[3], -20),
                        adjust_color_brightness($colors[4], -20),
                        adjust_color_brightness($colors[5], -20),
                        adjust_color_brightness($colors[6], -20),
                    ],
                ],
            ],
        ];

        $where = ' AND lost = 0 AND deleted = 0 AND blocked = 0 ';
        if(!is_admin()){
            $staff_id = get_staff_user_id();
            $staff = $this->staff_model->get_with_role($staff_id);
            if(isset($staff->role_name)){
                if($staff->role_name == 'Lead'){
                    $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);
                    if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
                    $where .= 'AND assigned in ('.implode(',', $staff_ids).')';
                }else return $chart;
            }
        }

        $this->db->where('CAST(dateadded as DATE) >= "' . date('Y-m-d', strtotime('monday this week')) . '" 
                AND CAST(dateadded as DATE) <= "' . date('Y-m-d', strtotime('sunday this week')) . '" '.$where);//AND status = 1 and lost = 0');
        $weekly = $this->db->get(db_prefix() . 'leads')->result_array();
        foreach ($weekly as $weekly) {
            $lead_status_day = _l(mb_strtolower('wd_' . date('l', strtotime($weekly['dateadded']))));
            $i               = 0;
            foreach ($chart['labels'] as $dat) {
                if ($lead_status_day == $dat) {
                    $chart['datasets'][0]['data'][$i]++;
                }
                $i++;
            }
        }

        return $chart;
    }

    public function leads_staff_report($from_date = '', $to_date = '', $department = '', $where = '')
    {
        if($where == '') $where = db_prefix().'leads.deleted = 0 AND blocked = 0';
        $this->load->model('staff_model');
        $staff = $this->staff_model->get();
        $chart = [
            'labels'   => [],
            'datasets' => [
                [
                    'label'           => _l('leads_staff_report_created_'),
                    'backgroundColor' => 'rgba(3,169,244,0.2)',
                    'borderColor'     => '#03a9f4',
                    'borderWidth'     => 1,
                    'tension'         => false,
                    'data'            => [],
                ],
            ],
        ];
        if(!is_admin()){
            $staff_id = get_staff_user_id();
            $staff_ = $this->staff_model->get_with_role($staff_id);
            if(isset($staff_->role_name)){
                if($staff_->role_name == 'Lead'){
                    $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);
                    if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
                    $staff = $this->staff_model->get('', ' staffid in ('.implode(',', $staff_ids).')');
                }else return $chart;
            }
        }
        if($department){
            $staffs = $this->db->select(db_prefix().'customfieldsvalues.relid')->where(['value' => $this->db->escape_str($department)])->from(db_prefix().'customfieldsvalues')->get()->result_array();
            if(!empty($staffs)) $staffs = array_map('implode', $staffs);
            $staff = !empty($staffs) ? $this->staff_model->get('', ' staffid in ('.implode(',', $staffs).')') : [];
        }

        foreach ($staff as $member) {
            array_push($chart['labels'], $member['firstname'] . ' ' . $member['lastname']);
            $temp = $where;
            $temp .= $temp == '' ? ' ' : ' AND ';
            $temp .= db_prefix().'customfields.name = "Department" AND '.db_prefix().'customfields.slug = "staff_department" AND '.db_prefix().'customfields.type = "select" ';
            if($department){
                $temp .= ' AND '.db_prefix().'customfieldsvalues.value = "'.$this->db->escape_str($department).'" ';
            }

            if (/*!isset($to_date) && !isset($from_date) && */$to_date == '' && $from_date == '') {
                $temp .= ' AND '.db_prefix().'leads.addedfrom = '.$member['staffid'];
                $this->db->join(db_prefix() . 'customfieldsvalues', db_prefix() . 'customfieldsvalues.relid=' . db_prefix() . 'leads.addedfrom', 'left');
                $this->db->join(db_prefix() . 'customfields', db_prefix() . 'customfields.id=' . db_prefix() . 'customfieldsvalues.fieldid', 'left');
                $this->db->where($temp);
                $total_rows_created = $this->db->count_all_results(db_prefix() . 'leads');
            } elseif($to_date == '' && $from_date != '') {
                $temp .= ' AND dateadded > "' . $this->db->escape_str($from_date) . '" AND addedfrom=" '. $member['staffid'].'"';
                $sql = 'SELECT COUNT(' . db_prefix() . 'leads.id) as total FROM ' . db_prefix() . 'leads 
                        LEFT JOIN `' . db_prefix() . 'customfieldsvalues` ON `' . db_prefix() . 'customfieldsvalues`.`relid`=`' . db_prefix() . 'leads`.`addedfrom` 
                        LEFT JOIN `' . db_prefix() . 'customfields` ON `' . db_prefix() . 'customfields`.`id`=`' . db_prefix() . 'customfieldsvalues`.`fieldid` 
                        WHERE '.$temp;
                $total_rows_created = $this->db->query($sql)->row()->total;
            } elseif($to_date != '' && $from_date == '') {
                $temp .= ' AND dateadded < "' . $this->db->escape_str($to_date) . '" AND addedfrom=" '. $member['staffid'].'"';
                $sql = 'SELECT COUNT(' . db_prefix() . 'leads.id) as total FROM ' . db_prefix() . 'leads 
                        LEFT JOIN `' . db_prefix() . 'customfieldsvalues` ON `' . db_prefix() . 'customfieldsvalues`.`relid`=`' . db_prefix() . 'leads`.`addedfrom` 
                        LEFT JOIN `' . db_prefix() . 'customfields` ON `' . db_prefix() . 'customfields`.`id`=`' . db_prefix() . 'customfieldsvalues`.`fieldid` 
                        WHERE '.$temp;
                $total_rows_created = $this->db->query($sql)->row()->total;
            } else {
                $temp .= ' AND DATE(dateadded) BETWEEN "' . $this->db->escape_str($from_date) . '" AND "' . $this->db->escape_str($to_date) . '" AND addedfrom=" '. $member['staffid'].'"';
                $sql = 'SELECT COUNT(' . db_prefix() . 'leads.id) as total FROM ' . db_prefix() . 'leads 
                        LEFT JOIN `' . db_prefix() . 'customfieldsvalues` ON `' . db_prefix() . 'customfieldsvalues`.`relid`=`' . db_prefix() . 'leads`.`addedfrom` 
                        LEFT JOIN `' . db_prefix() . 'customfields` ON `' . db_prefix() . 'customfields`.`id`=`' . db_prefix() . 'customfieldsvalues`.`fieldid` 
                        WHERE '.$temp;
                $total_rows_created = $this->db->query($sql)->row()->total;
            }

            array_push($chart['datasets'][0]['data'], $total_rows_created);
        }

        return $chart;
    }

    function get_departments(){
        $deps = [];
        $where = ['name' => 'Department', 'slug' => 'staff_department', 'type' => 'select', 'fieldto' => 'staff', 'active' => 1];
        $cf = $this->db->where($where)->from(db_prefix().'customfields')->get()->row();
        if(!is_admin()){
            $staff_id = get_staff_user_id();
            $staff = $this->staff_model->get_with_role($staff_id);
            if(isset($staff->role_name)){
                if($staff->role_name == 'Lead'){
                    $department = $this->db->where($where)->from(db_prefix().'customfields')->get()->row();
                    $cfv = $this->db->where(['relid' => $staff_id, 'fieldid' => $department->id])->from(db_prefix().'customfieldsvalues')->get()->row();
                    $deps[] = ['name' => $cfv->value];
                }else return $deps;
            }
        }else{
            if($cf && $cf->options) $options = explode(',', $cf->options);
            foreach ($options as $o){
                $deps[] = ['name' => trim($o)];
            }
        }
        return $deps;
    }

    function get_clients_grouped_assigned(){
        $sql = 'SELECT
        distinct count(*) as clients
        FROM ' . db_prefix() . 'leads
        WHERE deleted = 0 and blocked = 0 and assigned != 0 group by assigned';

        $clients = $this->db->query($sql)->result_array();

        if ($clients === null) {
            $clients = [];
        }else{
            sort($clients);
            array_unshift($clients , ['clients' => _l('none_')]);
        }

        return $clients;
    }

    /**
     * Lead conversion by sources report / chart
     * @return arrray chart data
     */
    public function leads_sources_report($where = [])
    {
        $this->load->model('leads_model');
        $sources = $this->leads_model->get_source();
        $chart   = [
            'labels'   => [],
            'datasets' => [
                [
                    'label'           => _l('clients_'),
                    'backgroundColor' => 'rgba(124, 179, 66, 0.5)',
                    'borderColor'     => '#7cb342',
                    'data'            => [],
                ],
            ],
        ];
        $where['deleted'] = 0;
        $where['blocked'] = 0;
        $where = !empty($where) ? $where : [
            'status' => 1,
            'lost'   => 0,
        ];
        if(!is_admin()){
            $staff_id = get_staff_user_id();
            $staff = $this->staff_model->get_with_role($staff_id);
            if(isset($staff->role_name)){
                if($staff->role_name == 'Lead'){
                    $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);
                    if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
                    if(empty($where)) $where = ' assigned in ('.implode(',', $staff_ids).')';
                    else{
                        $w_text = ' assigned in ('.implode(',', $staff_ids).')';
                        foreach($where as $k => $v){
                            $w_text .= $w_text == '' ? ' ' : ' AND ';
                            $w_text .= $k.' = '.$v;
                        }
                        $where = $w_text;
                    }
                }else return $chart;
            }
        }
        foreach ($sources as $source) {
            $temp = $where;
            if(is_array($temp)) $temp['source'] = $source['id'];
            else $temp = $where.' AND source = '.$source['id'];
            array_push($chart['labels'], $source['name']);
            array_push($chart['datasets'][0]['data'], total_rows(db_prefix() . 'leads', $temp));
        }

        return $chart;
    }

    public function report_by_customer_groups()
    {
        $months_report = $this->input->post('months_report');
        $groups        = $this->clients_model->get_groups();
        if ($months_report != '') {
            $custom_date_select = '';
            if (is_numeric($months_report)) {
                // Last month
                if ($months_report == '1') {
                    $beginMonth = date('Y-m-01', strtotime('first day of last month'));
                    $endMonth   = date('Y-m-t', strtotime('last day of last month'));
                } else {
                    $months_report = (int) $months_report;
                    $months_report--;
                    $beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
                    $endMonth   = date('Y-m-t');
                }

                $custom_date_select = '(' . db_prefix() . 'invoicepaymentrecords.date BETWEEN "' . $beginMonth . '" AND "' . $endMonth . '")';
            } elseif ($months_report == 'this_month') {
                $custom_date_select = '(' . db_prefix() . 'invoicepaymentrecords.date BETWEEN "' . date('Y-m-01') . '" AND "' . date('Y-m-t') . '")';
            } elseif ($months_report == 'this_year') {
                $custom_date_select = '(' . db_prefix() . 'invoicepaymentrecords.date BETWEEN "' .
                    date('Y-m-d', strtotime(date('Y-01-01'))) .
                    '" AND "' .
                    date('Y-m-d', strtotime(date('Y-12-31'))) . '")';
            } elseif ($months_report == 'last_year') {
                $custom_date_select = '(' . db_prefix() . 'invoicepaymentrecords.date BETWEEN "' .
                    date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01'))) .
                    '" AND "' .
                    date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31'))) . '")';
            } elseif ($months_report == 'custom') {
                $from_date = to_sql_date($this->input->post('report_from'));
                $to_date   = to_sql_date($this->input->post('report_to'));
                if ($from_date == $to_date) {
                    $custom_date_select = db_prefix() . 'invoicepaymentrecords.date ="' . $from_date . '"';
                } else {
                    $custom_date_select = '(' . db_prefix() . 'invoicepaymentrecords.date BETWEEN "' . $from_date . '" AND "' . $to_date . '")';
                }
            }
            $this->db->where($custom_date_select);
        }
        $this->db->select('amount,' . db_prefix() . 'invoicepaymentrecords.date,' . db_prefix() . 'invoices.clientid,(SELECT GROUP_CONCAT(name) FROM ' . db_prefix() . 'customers_groups LEFT JOIN ' . db_prefix() . 'customer_groups ON ' . db_prefix() . 'customer_groups.groupid = ' . db_prefix() . 'customers_groups.id WHERE customer_id = ' . db_prefix() . 'invoices.clientid) as customerGroups');
        $this->db->from(db_prefix() . 'invoicepaymentrecords');
        $this->db->join(db_prefix() . 'invoices', db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid');
        $this->db->where(db_prefix() . 'invoices.clientid IN (select customer_id FROM ' . db_prefix() . 'customer_groups)');
        $this->db->where(db_prefix() . 'invoices.status !=', 5);
        $by_currency = $this->input->post('report_currency');
        if ($by_currency) {
            $this->db->where('currency', $by_currency);
        }
        $payments       = $this->db->get()->result_array();
        $data           = [];
        $data['temp']   = [];
        $data['total']  = [];
        $data['labels'] = [];
        foreach ($groups as $group) {
            if (!isset($data['groups'][$group['name']])) {
                $data['groups'][$group['name']] = $group['name'];
            }
        }

        // If any groups found
        if (isset($data['groups'])) {
            foreach ($data['groups'] as $group) {
                foreach ($payments as $payment) {
                    $p_groups = explode(',', $payment['customerGroups']);
                    foreach ($p_groups as $p_group) {
                        if ($p_group == $group) {
                            $data['temp'][$group][] = $payment['amount'];
                        }
                    }
                }
                array_push($data['labels'], $group);
                if (isset($data['temp'][$group])) {
                    $data['total'][] = array_sum($data['temp'][$group]);
                } else {
                    $data['total'][] = 0;
                }
            }
        }

        $chart = [
            'labels'   => $data['labels'],
            'datasets' => [
                [
                    'label'           => _l('total_amount'),
                    'backgroundColor' => 'rgba(197, 61, 169, 0.2)',
                    'borderColor'     => '#c53da9',
                    'borderWidth'     => 1,
                    'tension'         => false,
                    'data'            => $data['total'],
                ],
            ],
        ];

        return $chart;
    }

    public function report_by_payment_modes()
    {
        $this->load->model('payment_modes_model');
        $modes  = $this->payment_modes_model->get('', [], true, true);
        $year   = $this->input->post('year');
        $colors = get_system_favourite_colors();
        $this->db->select('amount,' . db_prefix() . 'invoicepaymentrecords.date');
        $this->db->from(db_prefix() . 'invoicepaymentrecords');
        $this->db->where('YEAR(' . db_prefix() . 'invoicepaymentrecords.date)', $year);
        $this->db->join(db_prefix() . 'invoices', '' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid');
        $by_currency = $this->input->post('report_currency');
        if ($by_currency) {
            $this->db->where('currency', $by_currency);
        }
        $all_payments = $this->db->get()->result_array();
        $chart        = [
            'labels'   => [],
            'datasets' => [],
        ];
        $data           = [];
        $data['months'] = [];
        foreach ($all_payments as $payment) {
            $month   = date('m', strtotime($payment['date']));
            $dateObj = DateTime::createFromFormat('!m', $month);
            $month   = $dateObj->format('F');
            if (!isset($data['months'][$month])) {
                $data['months'][$month] = $month;
            }
        }
        usort($data['months'], function ($a, $b) {
            $month1 = date_parse($a);
            $month2 = date_parse($b);

            return $month1['month'] - $month2['month'];
        });

        foreach ($data['months'] as $month) {
            array_push($chart['labels'], _l($month) . ' - ' . $year);
        }
        $i = 0;
        foreach ($modes as $mode) {
            if (total_rows(db_prefix() . 'invoicepaymentrecords', [
                    'paymentmode' => $mode['id'],
                ]) == 0) {
                continue;
            }
            $color = '#4B5158';
            if (isset($colors[$i])) {
                $color = $colors[$i];
            }
            $this->db->select('amount,' . db_prefix() . 'invoicepaymentrecords.date');
            $this->db->from(db_prefix() . 'invoicepaymentrecords');
            $this->db->where('YEAR(' . db_prefix() . 'invoicepaymentrecords.date)', $year);
            $this->db->where(db_prefix() . 'invoicepaymentrecords.paymentmode', $mode['id']);
            $this->db->join(db_prefix() . 'invoices', '' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid');
            $by_currency = $this->input->post('report_currency');
            if ($by_currency) {
                $this->db->where('currency', $by_currency);
            }
            $payments = $this->db->get()->result_array();

            $datasets_data          = [];
            $datasets_data['total'] = [];
            foreach ($data['months'] as $month) {
                $total_payments = [];
                if (!isset($datasets_data['temp'][$month])) {
                    $datasets_data['temp'][$month] = [];
                }
                foreach ($payments as $payment) {
                    $_month  = date('m', strtotime($payment['date']));
                    $dateObj = DateTime::createFromFormat('!m', $_month);
                    $_month  = $dateObj->format('F');
                    if ($month == $_month) {
                        $total_payments[] = $payment['amount'];
                    }
                }
                $datasets_data['total'][] = array_sum($total_payments);
            }
            $chart['datasets'][] = [
                'label'           => $mode['name'],
                'backgroundColor' => $color,
                'borderColor'     => adjust_color_brightness($color, -20),
                'tension'         => false,
                'borderWidth'     => 1,
                'data'            => $datasets_data['total'],
            ];
            $i++;
        }

        return $chart;
    }

    /**
     * Total income report / chart
     * @return array chart data
     */
    public function total_income_report()
    {
        $year = $this->input->post('year');
        $this->db->select('amount,' . db_prefix() . 'invoicepaymentrecords.date');
        $this->db->from(db_prefix() . 'invoicepaymentrecords');
        $this->db->where('YEAR(' . db_prefix() . 'invoicepaymentrecords.date)', $year);
        $this->db->join(db_prefix() . 'invoices', '' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid');
        $by_currency = $this->input->post('report_currency');

        if ($by_currency) {
            $this->db->where('currency', $by_currency);
        }

        $payments       = $this->db->get()->result_array();
        $data           = [];
        $data['months'] = [];
        $data['temp']   = [];
        $data['total']  = [];
        $data['labels'] = [];

        foreach ($payments as $payment) {
            $month   = date('m', strtotime($payment['date']));
            $dateObj = DateTime::createFromFormat('!m', $month);
            $month   = $dateObj->format('F');
            if (!isset($data['months'][$month])) {
                $data['months'][$month] = $month;
            }
        }

        usort($data['months'], function ($a, $b) {
            $month1 = date_parse($a);
            $month2 = date_parse($b);

            return $month1['month'] - $month2['month'];
        });

        foreach ($data['months'] as $month) {
            foreach ($payments as $payment) {
                $monthNumber = date('m', strtotime($payment['date']));
                $dateObj     = DateTime::createFromFormat('!m', $monthNumber);
                $_month      = $dateObj->format('F');
                if ($month == $_month) {
                    $data['temp'][$month][] = $payment['amount'];
                }
            }

            array_push($data['labels'], _l($month) . ' - ' . $year);

            $data['total'][] = array_sum($data['temp'][$month]) - $this->calculate_refunded_amount($year, $monthNumber, $by_currency);
        }

        $chart = [
            'labels'   => $data['labels'],
            'datasets' => [
                [
                    'label'           => _l('report_sales_type_income'),
                    'backgroundColor' => 'rgba(37,155,35,0.2)',
                    'borderColor'     => '#84c529',
                    'tension'         => false,
                    'borderWidth'     => 1,
                    'data'            => $data['total'],
                ],
            ],
        ];

        return $chart;
    }

    public function get_distinct_payments_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'invoicepaymentrecords')->result_array();
    }

    public function get_distinct_customer_invoices_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'invoices WHERE clientid=' . get_client_user_id())->result_array();
    }

    protected function calculate_refunded_amount($year, $month, $currency)
    {
        $sql = 'SELECT
        SUM(' . db_prefix() . 'creditnote_refunds.amount) as refunds_amount
        FROM ' . db_prefix() . 'creditnote_refunds
        WHERE YEAR(refunded_on) = ' . $year . ' AND MONTH(refunded_on) = ' . $month;

        if ($currency) {
            $sql .= ' AND credit_note_id IN (SELECT id FROM ' . db_prefix() . 'creditnotes WHERE currency=' . $currency . ')';
        }

        $refunds_amount = $this->db->query($sql)->row()->refunds_amount;

        if ($refunds_amount === null) {
            $refunds_amount = 0;
        }

        return $refunds_amount;
    }
}
