<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="widget relative" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('staff_report_'); ?>" style="/*margin-top: 25px;*/">
    <div class="widget-dragger"></div>
    <?php if (is_admin() || (is_staff_member() && is_lead_staff())) { ?>
        <div class="row">
            <div class="col-md-12 animated fadeIn">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open($this->uri->uri_string() . '?type=staff'); ?>
                        <div class="row">
                            <div class="col-md-3">
                                <?php echo render_date_input('staff_report_from_date', 'from_date', $this->input->post('staff_report_from_date')); ?>
                            </div>
                            <div class="col-md-3">
                                <?php echo render_date_input('staff_report_to_date', 'to_date', $this->input->post('staff_report_to_date')); ?>
                            </div>
                            <?php if(is_admin()){ ?>
                            <div class="col-md-3 text-left">
                                <?php echo render_select('department', $departments, ['name', 'name'], 'department_', '', ['data-width' => '100%', 'data-none-selected-text' => ''], [], 'no-mbot'); ?>
                            </div>
                            <?php } ?>
                            <div class="col-md-3 text-left">
                                <button id="generate" type="submit" class="btn btn-primary label-margin"><?php echo _l('generate'); ?></button>
                            </div>
                        </div>
                        <?php echo form_close(); ?>
                        <hr />
                        <div class="relative" style="max-height:380px">
                            <canvas class="leads-staff-report mtop20" height="380" id="leads-staff-report"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</div>