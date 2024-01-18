<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="widget relative" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('quick_stats'); ?>">
    <div class="widget-dragger"></div>
    <?php if (is_staff_member()) { ?>
        <div class="row">
            <?php
            $staff_id = get_staff_user_id();
            $staff = $this->staff_model->get_with_role($staff_id);
            $initial_column = 'col-lg-3';
            if ($staff->role_name != 'Lead' && !is_admin()) {
                $initial_column = 'col-lg-4';
            }

            $CI = & get_instance();
            $CI->load->helper('dashboard');
            $client_ids = [];
            if(isset($staff->role_name)){
                if($staff->role_name == 'Lead'){
                    $staff_ids = $this->staff_model->get_staff_ids_by_lead_id($staff_id);
                    if(!empty($staff_ids)) $staff_ids = array_map('implode', $staff_ids);
                    $client_ids = get_client_ids_by_assigned($staff_ids);
                    if(!empty($client_ids)) $client_ids = array_map('implode', $client_ids);
                }
                if($staff->role_name == 'Sales'){
                    $client_ids = get_client_ids_by_assigned($staff_id);
                    if(!empty($client_ids)) $client_ids = array_map('implode', $client_ids);
                }
            }
            ?>
            <div class="quick-stats-invoices col-xs-12 col-md-6 col-sm-6 <?php echo $initial_column; ?> tw-mb-2 sm:tw-mb-0">
                <div class="top_stats_wrapper">
                    <?php
                    $where_total = 'type = "deposit"';
                    if(!is_admin()) $where_total .= ' AND client in ('.implode(',', $client_ids).')';
                    $total_deposit                           = app_format_number(sum_from_table_(db_prefix() . 'finance', ['field' => 'amount', 'where' => $where_total]));
                    $in_process_deposit                      = app_format_number(sum_from_table_(db_prefix() . 'finance', ['field' => 'amount', 'where' => $where_total.' AND '.db_prefix().'finance.status = "in_process"']));
                    //$percent_total_in_process_deposit = $total_deposit > 0 ? (($in_process_deposit * 100) / $total_deposit) : 0;
                    //$percent_total_in_process_deposit = number_format($percent_total_in_process_deposit > 0 && $percent_total_in_process_deposit < 1 ? ceil($percent_total_in_process_deposit) : $percent_total_in_process_deposit, 2);
                    ?>
                    <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
                        <div class="tw-font-medium tw-inline-flex text-neutral-600 tw-items-center">
                            <!--<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="tw-w-6 tw-h-6 tw-mr-3 rtl:tw-ml-3 tw-text-neutral-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>-->
                            <?php echo _l('in_process_total_deposit_'); ?>
                        </div>
                        <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0">
                        <span class="text-warning"><?php echo $in_process_deposit ?? 0; ?></span> /
                        <span class="text-success"><?php echo $total_deposit ?? 0; ?></span>
                    </span>
                    </div>

                    <!--<div class="progress tw-mb-0 tw-mt-4 progress-bar-mini">
                    <div class="progress-bar progress-bar-danger no-percent-text not-dynamic" role="progressbar"
                        aria-valuenow="<?php /*echo $percent_total_in_process_deposit; */?>" aria-valuemin="0"
                        aria-valuemax="100" style="width: 0%"
                        data-percent="<?php /*echo $percent_total_in_process_deposit; */?>">
                    </div>
                </div>-->
                </div>
            </div>
            <div class="quick-stats-leads col-xs-12 col-md-6 col-sm-6 <?php echo $initial_column; ?> tw-mb-2 sm:tw-mb-0">
                <div class="top_stats_wrapper">
                    <?php
                    $where_total = 'type = "withdrawal"';
                    if(!is_admin()) $where_total .= ' AND client in ('.implode(',', $client_ids).')';
                    $total_withdrawal                           = app_format_number(sum_from_table_(db_prefix() . 'finance', ['field' => 'amount', 'where' => $where_total]));
                    $in_process_withdrawal                      = app_format_number(sum_from_table_(db_prefix() . 'finance', ['field' => 'amount', 'where' => $where_total.' AND '.db_prefix().'finance.status = "in_process"']));
                    //$percent_total_in_process_withdrawal = $total_withdrawal > 0 ? (($in_process_withdrawal * 100) / $total_withdrawal) : 0;
                    //$percent_total_in_process_withdrawal = number_format($percent_total_in_process_withdrawal > 0 && $percent_total_in_process_withdrawal < 1 ? ceil($percent_total_in_process_withdrawal) : $percent_total_in_process_withdrawal, 2);
                    ?>
                    <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
                        <div class="tw-font-medium tw-inline-flex text-neutral-600 tw-items-center">
                            <!--<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="tw-w-6 tw-h-6 tw-mr-3 rtl:tw-ml-3 tw-text-neutral-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>-->
                            <?php echo _l('in_process_total_withdrawal_'); ?>
                        </div>
                        <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0">
                        <span class="text-warning"><?php echo $in_process_withdrawal ?? 0; ?></span> /
                        <span class="text-success"><?php echo $total_withdrawal ?? 0; ?></span>
                    </span>
                    </div>

                    <!--<div class="progress tw-mb-0 tw-mt-4 progress-bar-mini">
                    <div class="progress-bar progress-bar-danger no-percent-text not-dynamic" role="progressbar"
                        aria-valuenow="<?php /*echo $percent_total_in_process_withdrawal; */?>" aria-valuemin="0"
                        aria-valuemax="100" style="width: 0%"
                        data-percent="<?php /*echo $percent_total_in_process_withdrawal; */?>">
                    </div>
                </div>-->
                </div>
            </div>
            <?php if (is_admin() || $staff->role_name == 'Lead') { ?>
                <div class="quick-stats-projects col-xs-12 col-md-6 col-sm-6 <?php echo $initial_column; ?> tw-mb-2 sm:tw-mb-0">
                    <div class="top_stats_wrapper">
                        <?php
                        $staff_count = 0;
                        if(is_admin()){
                            $staffs = $this->staff_model->get('', ['is_not_staff' => 0, 'active' => 1]);
                            $staff_count = count($staffs);
                        }else{
                            $staff_count = isset($staff_ids) ? count($staff_ids) : 0;
                        }
                        ?>
                        <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
                            <div class="tw-font-medium tw-inline-flex tw-items-center text-neutral-500">
                                <!--<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor" class="tw-w-6 tw-h-6 tw-mr-3 rtl:tw-ml-3 tw-text-neutral-600">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                                </svg>-->
                                <?php echo is_admin() ? _l('sellers_').' ('._l('all_').')' : _l('sellers_'); ?>
                            </div>
                            <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0">
                        <?php echo $staff_count; ?>
                    </span>
                        </div>

                        <!--                <div class="tw-mt-5"></div>-->
                    </div>
                </div>
            <?php } ?>
            <div class="quick-stats-tasks col-xs-12 col-md-6 col-sm-6 <?php echo $initial_column; ?>">
                <div class="top_stats_wrapper">
                    <?php
                    $client_count = 0;
                    if(is_admin()){
                        $clients = $this->leads_model->get();//'', ['deleted' => 0, 'blocked' => 0]);
                        $client_count = count($clients);
                    }else{
                        $client_count = isset($client_ids) ? count($client_ids) : 0;
                    }
                    ?>
                    <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
                        <div class="tw-font-medium tw-inline-flex text-neutral-600 tw-items-center">
                            <!--<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="tw-w-6 tw-h-6 tw-mr-3 rtl:tw-ml-3 tw-text-neutral-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25A3.375 3.375 0 0113.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 013.375 3.375M9 15l2.25 2.25L15 12" />
                            </svg>-->
                            <?php echo is_admin() ? _l('clients_').' ('._l('all_').')' : _l('clients_'); ?>
                        </div>
                        <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0">
                        <?php echo $client_count; ?>
                    </span>
                    </div>
                    <!--                <div class="tw-mt-5"></div>-->
                </div>
            </div>
        </div>
    <?php } ?>
</div>