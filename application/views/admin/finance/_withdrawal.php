<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="<?php if ($openEdit == true) {
    echo 'open-edit ';
} ?>lead-wrapper">

    <?php if (isset($withdrawal)) { ?>
        <?php if(has_permission('finance', '', 'delete_withdrawal') || is_admin()){ ?>
            <div class="btn-group pull-right mleft5" id="lead-more-btn">
                <a href="#" class="btn btn-default dropdown-toggle lead-top-btn" data-toggle="dropdown" aria-haspopup="true"
                   aria-expanded="false">
                    <?php echo _l('more'); ?>
                    <span class="caret"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-left" id="lead-more-dropdown">
                    <?php //if (has_permission('finance', '', 'delete_withdrawal') || is_admin()) { ?>
                    <li>
                        <a href="<?php echo admin_url('finance/delete_withdrawal/' . $withdrawal->id); ?>" class="text-danger delete-text _delete"
                           data-toggle="tooltip" title="">
                            <i class="fa fa-remove"></i>
                            <?php echo _l('withdrawal_edit_delete_tooltip_'); ?>
                        </a>
                    </li>
                    <?php //} ?>
                </ul>

            </div>
        <?php } ?>

        <a data-toggle="tooltip" class="btn btn-default pull-right lead-print-btn lead-top-btn lead-view mleft5"
           onclick="print_finance_information(); return false;" data-placement="top" title="<?php echo _l('print'); ?>"
           href="#">
            <i class="fa fa-print"></i>
        </a>

        <?php if(has_permission('finance', '', 'edit_withdrawal') || is_admin()){?>
            <div class="mleft5 pull-right">
                <a href="#" withdrawal-edit data-toggle="tooltip" data-title="<?php echo _l('edit'); ?>"
                   class="btn btn-default lead-top-btn">

                    <i class="fa-regular fa-pen-to-square"></i>
                </a>
            </div>
        <?php } ?>

        <div class="withdrawal-edit<?php if (isset($withdrawal)) {
            echo ' hide';
        } ?>">
            <!--<button type="button" class="btn btn-primary pull-right lead-top-btn finance-save-btn"
            onclick="document.getElementById('lead-form-submit').click();">
            <?php /*echo _l('submit'); */?>
        </button>-->
        </div>

    <?php } ?>

    <div class="clearfix no-margin"></div>

    <?php if (isset($withdrawal)) { ?>

        <div class="row mbot15" style="margin-top:12px;">
            <hr class="no-margin" />
        </div>

    <?php } ?>
    <?php echo form_open((isset($withdrawal) ? admin_url('finance/withdrawal/' . $withdrawal->id) : admin_url('finance/withdrawal')), ['id' => 'finance_form']); ?>
    <div class="row">
        <div class="lead-view<?php if (!isset($withdrawal)) {
            echo ' hide';
        } ?>" id="leadViewWrapper">
            <div class="col-md-4 col-xs-12 lead-information-col">
                <div class="lead-info-heading">
                    <h4>
                        <?php echo _l('withdrawal_info_'); ?>
                    </h4>
                </div>
                <dl>
                    <dt class="lead-field-heading tw-font-medium tw-text-neutral-500">
                        <?php echo _l('client_'); ?></dt>
                    <dd class="tw-text-neutral-900 tw-mt-1">
                        <?php echo(isset($client) && $client->name != '' ? $client->name : '-') ?></dd>
                    <dt class="lead-field-heading tw-font-medium tw-text-neutral-500">
                        <?php echo _l('account_number_'); ?></dt>
                    <dd class="tw-text-neutral-900 tw-mt-1">
                        <?php echo(isset($withdrawal) && $withdrawal->account_number != '' ? $withdrawal->account_number : '-') ?></dd>
                    <!--<dt class="lead-field-heading tw-font-medium tw-text-neutral-500">
                        <?php /*echo _l('currency'); */?></dt>
                    <dd class="tw-text-neutral-900 tw-mt-1">
                        <?php /*echo(isset($withdrawal) && $withdrawal->c_name != '' && $withdrawal->symbol != '' ? $withdrawal->c_name.' ('.$withdrawal->symbol.')' : '-') */?></dd>-->
                    <dt class="lead-field-heading tw-font-medium tw-text-neutral-500">
                        <?php echo _l('currency'); ?></dt>
                    <dd class="tw-text-neutral-900 tw-mt-1">
                        <?php echo(isset($withdrawal) && $withdrawal->currency != '' ? $withdrawal->currency : '-') ?></dd>
                    <dt class="lead-field-heading tw-font-medium tw-text-neutral-500">
                        <?php echo _l('amount_'); ?></dt>
                    <dd class="tw-text-neutral-900 tw-mt-1">
                        <?php echo(isset($withdrawal) && $withdrawal->amount != '' ? app_format_money($withdrawal->amount, $withdrawal->currency/*$withdrawal->c_name*/) : '-') ?></dd>
                    <dt class="lead-field-heading tw-font-medium tw-text-neutral-500">
                        <?php echo _l('status_'); ?></dt>
                    <dd class="tw-text-neutral-900 tw-mt-1">
                        <?php echo(isset($withdrawal) && $withdrawal->status != '' ? _l($withdrawal->status.'_') : '-') ?></dd>
                    <dt class="lead-field-heading tw-font-medium tw-text-neutral-500">
                        <?php echo _l('leads_dt_datecreated'); ?></dt>
                    <dd class="tw-text-neutral-900 tw-mt-1">
                        <?php echo(isset($withdrawal) && $withdrawal->dateadded != '' ? '<span class="text-has-action" data-toggle="tooltip" data-title="' . _dt($withdrawal->dateadded) . '">' . time_ago($withdrawal->dateadded) . '</span>' : '-') ?>
                    </dd>
                </dl>
            </div>
            <div class="col-md-4 col-xs-12 lead-information-col">
                <div class="lead-info-heading">
                    <h4>
                        <?php echo _l('finance_general_info_'); ?>
                    </h4>
                </div>
                <dl>
                    <dt class="lead-field-heading tw-font-medium tw-text-neutral-500">
                        <?php echo _l('lead_add_edit_assigned'); ?></dt>
                    <dd class="tw-text-neutral-900 tw-mt-1 mbot15">
                        <?php echo (isset($client) && $client->assigned != 0) ? get_staff_full_name($client->assigned) : '-'; ?>
                    </dd>
                    <dt class="lead-field-heading tw-font-medium tw-text-neutral-500">
                        <?php echo _l('balance_'); ?></dt>
                    <dd class="tw-text-neutral-900 tw-mt-1 mbot15">
                        <?php echo (isset($account) && $account->balance != 0) ? app_format_money($account->balance, $withdrawal->currency/*$withdrawal->c_name*/) : '-'; ?>
                    </dd>
                    <dt class="lead-field-heading tw-font-medium tw-text-neutral-500">
                        <?php echo _l('account_type_'); ?></dt>
                    <dd class="tw-text-neutral-900 tw-mt-1 mbot15">
                        <?php echo (isset($account) && $account->is_demo != '0') ? _l('real_') : _l('demo_'); ?>
                    </dd>
                </dl>
            </div>
            <div class="col-md-4 col-xs-12 lead-information-col">
                <?php if (total_rows(db_prefix() . 'customfields', ['fieldto' => 'finance', 'active' => 1]) > 0 && isset($lead)) { ?>
                <div class="lead-info-heading">
                    <h4>
                        <?php echo _l('custom_fields'); ?>
                    </h4>
                </div>
                <dl>
                    <?php
                    $custom_fields = get_custom_fields('finance');
                    foreach ($custom_fields as $field) {
                        $value = get_custom_field_value($withdrawal->id, $field['id'], 'finance'); ?>
                        <dt class="lead-field-heading tw-font-medium tw-text-neutral-500 no-mtop">
                            <?php echo $field['name']; ?></dt>
                        <dd class="tw-text-neutral-900 tw-mt-1 tw-break-words"><?php echo($value != '' ? $value : '-') ?>
                        </dd>
                        <?php
                    } ?>
                    <?php } ?>
                </dl>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="withdrawal-edit<?php if (isset($withdrawal)) {
            echo ' hide';
        } ?>">
            <!--<div class="col-md-4">
                <?php
            /*            $selected = '';
                        if (isset($withdrawal)) {
                            $selected = $withdrawal->status;
                        } elseif (isset($status_id)) {
                            $selected = $status_id;
                        }
                        echo render_finance_status_select($statuses, $selected, 'finance_add_edit_status_');
                      */?>
            </div>-->
            <div class="col-md-6">
                <?php
                $assigned_attrs = [];
                $selected       = (isset($withdrawal) ? $withdrawal->client : '');
                if (isset($withdrawal)
                    /*&& $withdrawal->assigned == get_staff_user_id()
                    && $withdrawal->addedfrom != get_staff_user_id()
                    && !is_admin($withdrawal->assigned)
                    && !has_permission('finance', '', 'view')
                 */) {
                    $assigned_attrs['disabled'] = true;
                }
                echo render_select('client', $clients, ['id', ['name']], 'finance_add_edit_client_', $selected, $assigned_attrs);
                if (isset($withdrawal)) echo form_hidden('account_id', $withdrawal->account);?>
            </div>
            <div class="col-md-6">
                <?php
                $type_attrs = [];
                if (isset($deposit)) $type_attrs['disabled'] = true;
                echo render_select('account', [], [], 'finance_add_edit_account_', '', $type_attrs); ?>
            </div>
            <div class="clearfix"></div>
            <hr class="no-mtop mbot15" />

            <div class="col-md-4">
                <?php $value = (isset($withdrawal) ? $withdrawal->amount : '');
                echo render_input('amount', 'withdrawal_add_edit_amount_', $value, 'number'); ?>
            </div>
            <div class="col-md-4">
                <?php $value = (isset($withdrawal) ? $withdrawal->currency : '');
                echo render_input('currency', 'currency', $value); ?>
            </div>
            <div class="col-md-4">
                <?php
                $selected = (isset($withdrawal) ? $withdrawal->status : '');
                echo render_select('status', $statuses, ['id', 'name'], 'finance_status_', $selected);
                ?>
            </div>
            <div class="col-md-12 mtop15">
                <?php $rel_id = (isset($withdrawal) ? $withdrawal->id : false); ?>
                <?php echo render_custom_fields('finance', $rel_id); ?>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
    <div class="withdrawal-edit<?php echo isset($withdrawal) ? ' hide' : ''; ?>">
        <hr class="-tw-mx-4 tw-border-neutral-200" />
        <button type="submit" class="btn btn-primary pull-right finance-save-btn" id="finance-form-submit">
            <?php echo _l('submit'); ?>
        </button>
        <button type=" button" class="btn btn-default pull-right mright5" data-dismiss="modal">
            <?php echo _l('close'); ?>
        </button>
    </div>
    <div class="clearfix"></div>
    <?php echo form_close(); ?>
</div>