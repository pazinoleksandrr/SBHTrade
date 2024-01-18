<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('home_withdrawal_records_'); ?>">
    <?php if (is_staff_member()) { ?>
    <div class="row" id="withdrawals">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body padding-10">
                    <div class="widget-dragger"></div>

                    <div class="tw-flex tw-justify-between tw-items-center tw-p-1.5">
                        <p class="tw-font-medium tw-flex tw-items-center tw-mb-0 tw-space-x-1.5 rtl:tw-space-x-reverse">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="tw-w-6 tw-h-6 tw-text-neutral-500">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25A3.375 3.375 0 0113.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 013.375 3.375M9 15l2.25 2.25L15 12" />
                            </svg>

                            <span class="tw-text-neutral-700">
                                <?php echo _l('home_withdrawal_records_'); ?>
                            </span>
                        </p>
                        <div class="fc-toolbar-chunk"><h4 class="fc-toolbar-title"><?php echo _l('home_withdrawal_chart_'); ?></h4></div>
                        <div class="tw-divide-x tw-divide-solid tw-divide-neutral-300 tw-space-x-2 tw-flex tw-items-center">
                            <select class="selectpicker tw-pr-2" name="status_withdrawal"
                                data-none-selected-text="<?php echo _l('status_'); ?>">
                                <option value="all"><?php echo ucfirst(_l('all_')); ?></option>
                                <option value="in_process"><?php echo _l('in_process_'); ?></option>
                                <option value="completed"><?php echo _l('completed_'); ?></option>
                            </select>
                            <div class="dropdown pull-right mright10">
                                <a href="#" id="WithdrawalChartmode" class="dropdown-toggle tw-pl-2" data-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                    <span id="Withdrawal-chart-name" data-active-chart="weekly">
                                        <?php echo _l('weekly') ?>
                                    </span>
                                    <i class="fa fa-caret-down" aria-hidden="true"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="WithdrawalChartmode">
                                    <li>
                                        <a href="#" data-type="weekly"
                                            onclick="update_withdrawal_statistics(this); return false;">
                                            <?php echo _l('weekly') ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" data-type="monthly"
                                            onclick="update_withdrawal_statistics(this); return false;">
                                            <?php echo _l('monthly') ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <hr class="-tw-mx-3 tw-mt-2 tw-mb-4">

                    <canvas height="130" class="payments-chart-dashboard" id="withdrawal-statistics"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>