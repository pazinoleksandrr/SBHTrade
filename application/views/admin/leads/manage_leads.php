<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_buttons tw-mb-2 sm:tw-mb-4">
                    <?php if (is_admin()){ ?>
                        <a href="<?php echo admin_url('leads/clean_leads'); ?>" class="btn btn-danger mright5 pull-left display-block">
                            <i class="fa-regular fa-trash-can tw-mr-1"></i>
                            <?php echo _l('reset_clients_'); ?>
                        </a>
                    <?php } ?>
                    <a href="#" onclick="init_lead(); return false;"
                       class="btn btn-primary mright5 pull-left display-block">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?php echo _l('new_client_'); ?>
                    </a>
                    <?php if (is_admin() || get_option('allow_non_admin_members_to_import_leads') == '1') { ?>
                        <a href="<?php echo admin_url('leads/import'); ?>"
                           class="btn btn-primary pull-left display-block hidden-xs">
                            <i class="fa-solid fa-upload tw-mr-1"></i>
                            <?php echo _l('import_clients_'); ?>
                        </a>
                    <?php } ?>
                    <div class="row">
                        <div class="col-sm-5 ">
                            <a href="#" class="btn btn-default btn-with-tooltip" data-toggle="tooltip"
                               data-title="<?php echo _l('clients_summary_'); ?>" data-placement="top"
                               onclick="slideToggle('.leads-overview'); return false;"><i
                                    class="fa fa-bar-chart"></i></a>
                            <a href="<?php echo admin_url('leads/switch_kanban/' . $switch_kanban); ?>"
                               class="btn btn-default mleft5 hidden-xs" data-toggle="tooltip" data-placement="top"
                               data-title="<?php echo $switch_kanban == 1 ? _l('leads_switch_to_kanban') : _l('switch_to_list_view'); ?>">
                                <?php if ($switch_kanban == 1) { ?>
                                    <i class="fa-solid fa-grip-vertical"></i>
                                <?php } else { ?>
                                    <i class="fa-solid fa-table-list"></i>
                                <?php }; ?>
                            </a>
                        </div>
                        <div class="col-sm-4 col-xs-12 pull-right leads-search">
                            <?php if ($this->session->userdata('leads_kanban_view') == 'true') { ?>
                                <div data-toggle="tooltip" data-placement="top"
                                     data-title="<?php echo _l('search_by_tags'); ?>">
                                    <?php echo render_input('search', '', '', 'search', ['data-name' => 'search', 'onkeyup' => 'leads_kanban();', 'placeholder' => _l('clients_search_')], [], 'no-margin') ?>
                                </div>
                            <?php } ?>
                            <?php echo form_hidden('sort_type'); ?>
                            <?php echo form_hidden('sort', (get_option('default_leads_kanban_sort') != '' ? get_option('default_leads_kanban_sort_type') : '')); ?>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="hide leads-overview tw-mt-2 sm:tw-mt-4 tw-mb-4 sm:tw-mb-0">
                        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg">
                            <?php echo _l('clients_summary_'); ?>
                        </h4>
                        <div class="tw-flex tw-flex-wrap tw-flex-col lg:tw-flex-row tw-w-full tw-gap-3 lg:tw-gap-6">
                            <?php
                            foreach ($summary as $status) { if(!isset($status['lost'])){ ?>
                                <div
                                    class="lg:tw-border-r lg:tw-border-solid lg:tw-border-neutral-300 tw-flex-1 tw-flex tw-items-center last:tw-border-r-0">
                                <span class="tw-font-semibold tw-mr-3 rtl:tw-ml-3 tw-text-lg">
                                    <?php
                                    if (isset($status['percent'])) {
                                        echo '<span data-toggle="tooltip" data-title="' . $status['total'] . '">' . $status['percent'] . '%</span>';
                                    } else {
                                        // Is regular status
                                        echo $status['total'];
                                    }
                                    ?>
                                </span>
                                    <span style="color:<?php echo $status['color']; ?>"
                                          class="<?php echo isset($status['junk']) || isset($status['lost']) ? 'text-danger' : ''; ?>">
                                    <?php echo $status['name']; ?>
                                </span>
                                </div>
                            <?php }} ?>
                        </div>

                    </div>
                </div>
                <div class="<?php echo $isKanBan ? '' : 'panel_s' ; ?>">
                    <div class="<?php echo $isKanBan ? '' : 'panel-body' ; ?>">
                        <div class="tab-content">
                            <?php
                            if ($isKanBan) { ?>
                                <div class="active kan-ban-tab" id="kan-ban-tab" style="overflow:auto;">
                                    <div class="kanban-leads-sort">
                                        <span class="bold"><?php echo _l('leads_sort_by'); ?>: </span>
                                        <a href="#" onclick="leads_kanban_sort('dateadded'); return false"
                                           class="dateadded">
                                            <?php if (get_option('default_leads_kanban_sort') == 'dateadded') {
                                                echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_leads_kanban_sort_type')) . '"></i> ';
                                            } ?><?php echo _l('leads_sort_by_datecreated'); ?>
                                        </a>
                                        |
                                        <a href="#" onclick="leads_kanban_sort('leadorder');return false;"
                                           class="leadorder">
                                            <?php if (get_option('default_leads_kanban_sort') == 'leadorder') {
                                                echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_leads_kanban_sort_type')) . '"></i> ';
                                            } ?><?php echo _l('leads_sort_by_kanban_order'); ?>
                                        </a>
                                        |
                                        <a href="#" onclick="leads_kanban_sort('lastcontact');return false;"
                                           class="lastcontact">
                                            <?php if (get_option('default_leads_kanban_sort') == 'lastcontact') {
                                                echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_leads_kanban_sort_type')) . '"></i> ';
                                            } ?><?php echo _l('leads_sort_by_lastcontact'); ?>
                                        </a>
                                    </div>
                                    <div class="row">
                                        <div class="container-fluid leads-kan-ban">
                                            <div id="kan-ban"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="row" id="leads-table">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p class="bold"><?php echo _l('filter_by'); ?></p>
                                            </div>
                                            <?php if (has_permission('leads', '', 'view') || (isset($role) && $role == 'Lead')) { ?>
                                                <div class="col-md-3 leads-filter-column">
                                                    <?php echo render_select('view_assigned', $staff, ['staffid', ['firstname', 'lastname']], '', '', ['data-width' => '100%', 'data-none-selected-text' => _l('leads_dt_assigned')], [], 'no-mbot'); ?>
                                                </div>
                                            <?php } ?>
                                            <div class="col-md-3 leads-filter-column">
                                                <?php
                                                $selected = [];
                                                if ($this->input->get('status')) {
                                                    $selected[] = $this->input->get('status');
                                                } else {
                                                    foreach ($statuses as $key => $status) {
                                                        if ($status['isdefault'] == 0) {
                                                            $selected[] = $status['id'];
                                                        } else {
                                                            $statuses[$key]['option_attributes'] = ['data-subtext' => _l('leads_converted_to_client')];
                                                        }
                                                    }
                                                }
                                                echo '<div id="leads-filter-status">';
                                                echo render_select('view_status[]', $statuses, ['id', 'name'], '', $selected, ['data-width' => '100%', 'data-none-selected-text' => _l('leads_all'), 'multiple' => true, 'data-actions-box' => true], [], 'no-mbot', '', false);
                                                echo '</div>';
                                                ?>
                                            </div>
                                            <div class="col-md-3 leads-filter-column">
                                                <?php
                                                echo render_select('view_source', $sources, ['id', 'name'], '', '', ['data-width' => '100%', 'data-none-selected-text' => _l('leads_source')], [], 'no-mbot');
                                                ?>
                                            </div>
                                            <div class="col-md-3 leads-filter-column">
                                                <div class="select-placeholder">
                                                    <select name="custom_view"
                                                            title="<?php echo _l('additional_filters'); ?>" id="custom_view"
                                                            class="selectpicker" data-width="100%">
                                                        <option value=""></option>
                                                        <!--<option value="lost"><?php /*echo _l('lead_lost'); */?></option>-->
                                                        <!--<option value="junk"><?php /*echo _l('lead_junk'); */?></option>
                                                    <option value="public"><?php /*echo _l('lead_public'); */?></option>-->
                                                        <option value="contacted_today">
                                                            <?php echo _l('lead_add_edit_contacted_today'); ?></option>
                                                        <option value="created_today"><?php echo _l('created_today'); ?>
                                                        </option>
                                                        <?php if (has_permission('leads', '', 'edit')) { ?>
                                                            <option value="not_assigned"><?php echo _l('leads_not_assigned'); ?>
                                                            </option>
                                                        <?php } ?>
                                                        <?php if (isset($consent_purposes)) { ?>
                                                            <optgroup label="<?php echo _l('gdpr_consent'); ?>">
                                                                <?php foreach ($consent_purposes as $purpose) { ?>
                                                                    <option value="consent_<?php echo $purpose['id']; ?>">
                                                                        <?php echo $purpose['name']; ?>
                                                                    </option>
                                                                <?php } ?>
                                                            </optgroup>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row tw-mt-5">
                                            <div class="col-md-3 leads-filter-column">
                                                <?php echo render_date_input('clients_from_date', '', $this->input->post('clients_from_date'), ['placeholder' => _l('from_date')]); ?>
                                            </div>
                                            <div class="col-md-3 leads-filter-column">
                                                <?php echo render_date_input('clients_to_date', '', $this->input->post('clients_to_date'), ['placeholder' => _l('to_date')]); ?>
                                            </div>
                                            <div class="col-md-3 leads-filter-column">
                                                <?php echo render_select('country', $countries, ['country_id', 'short_name'], '', '', ['data-width' => '100%', 'data-none-selected-text' => _l('country_')], [], 'no-mbot'); ?>
                                            </div>
                                            <?php if(is_admin()){ ?>
                                                <div class="col-md-3 leads-filter-column">
                                                    <?php echo render_select('department', $departments, ['name', 'name'], '', '', ['data-width' => '100%', 'data-none-selected-text' => _l('department_')], [], 'no-mbot'); ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <hr class="hr-panel-separator" />
                                    </div>
                                    <div class="clearfix"></div>

                                    <div class="col-md-12">
                                        <a href="#" data-toggle="modal" data-table=".table-leads"
                                           data-target="#leads_bulk_actions"
                                           class="hide bulk-actions-btn table-btn"><?php echo _l('bulk_actions'); ?></a>
                                        <div class="modal fade bulk_actions" id="leads_bulk_actions" tabindex="-1"
                                             role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close"><span
                                                                aria-hidden="true">&times;</span></button>
                                                        <h4 class="modal-title"><?php echo _l('bulk_actions'); ?></h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php if (has_permission('leads', '', 'delete')) { ?>
                                                            <div class="checkbox checkbox-danger" id="mass_delete_div">
                                                                <input type="checkbox" name="mass_delete" id="mass_delete">
                                                                <label
                                                                    for="mass_delete"><?php echo _l('mass_delete'); ?></label>
                                                            </div>
                                                            <div class="checkbox checkbox-success tw-mt-5" id="mass_sync_div">
                                                                <input type="checkbox" name="mass_sync" id="mass_sync">
                                                                <label
                                                                    for="mass_sync"><?php echo _l('mass_sync'); ?></label>
                                                            </div>
                                                            <hr class="mass_delete_separator" />
                                                        <?php } ?>
                                                        <div id="bulk_change">
                                                            <!--<div class="form-group">
                                                            <div class="checkbox checkbox-primary checkbox-inline">
                                                                <input type="checkbox" name="leads_bulk_mark_lost"
                                                                    id="leads_bulk_mark_lost" value="1">
                                                                <label for="leads_bulk_mark_lost">
                                                                    <?php /*echo _l('lead_mark_as_lost'); */?>
                                                                </label>
                                                            </div>
                                                        </div>-->
                                                            <?php echo render_select('move_to_status_leads_bulk', $statuses, ['id', 'name'], 'ticket_single_change_status'); ?>
                                                            <?php
                                                            echo render_select('move_to_source_leads_bulk', $sources, ['id', 'name'], 'client_source_');
                                                            echo render_datetime_input('leads_bulk_last_contact', 'leads_dt_last_contact');
                                                            echo render_select('assign_to_leads_bulk', $staff, ['staffid', ['firstname', 'lastname']], 'leads_dt_assigned');
                                                            ?>
                                                            <div class="form-group">
                                                                <?php echo '<p><b><i class="fa fa-tag" aria-hidden="true"></i> ' . _l('tags') . ':</b></p>'; ?>
                                                                <input type="text" class="tagsinput" id="tags_bulk"
                                                                       name="tags_bulk" value="" data-role="tagsinput">
                                                            </div>
                                                            <hr />
                                                            <div class="form-group no-mbot">
                                                                <div class="radio radio-primary radio-inline">
                                                                    <input type="radio" name="leads_bulk_visibility"
                                                                           id="leads_bulk_public" value="public">
                                                                    <label for="leads_bulk_public">
                                                                        <?php echo _l('lead_public'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="radio radio-primary radio-inline">
                                                                    <input type="radio" name="leads_bulk_visibility"
                                                                           id="leads_bulk_private" value="private">
                                                                    <label for="leads_bulk_private">
                                                                        <?php echo _l('private'); ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default"
                                                                data-dismiss="modal"><?php echo _l('close'); ?></button>
                                                        <a href="#" class="btn btn-primary"
                                                           onclick="leads_bulk_action(this); return false;"><?php echo _l('confirm'); ?></a>
                                                    </div>
                                                </div>
                                                <!-- /.modal-content -->
                                            </div>
                                            <!-- /.modal-dialog -->
                                        </div>
                                        <!-- /.modal -->
                                        <?php

                                        $table_data  = [];
                                        $_table_data = [
                                            '<span class="hide"> - </span><div class="checkbox mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="leads"><label></label></div>',
                                            [
                                                'name'     => _l('the_number_sign'),
                                                'th_attrs' => ['class' => 'toggleable', 'id' => 'th-number'],
                                            ],
                                            [
                                                'name'     => _l('trader_'),
                                                'th_attrs' => ['class' => 'toggleable', 'id' => 'th-trader'],
                                            ],
                                            [
                                                'name'     => _l('leads_dt_name'),
                                                'th_attrs' => ['class' => 'toggleable', 'id' => 'th-name'],
                                            ],
                                        ];
                                        if (is_gdpr() && get_option('gdpr_enable_consent_for_leads') == '1') {
                                            $_table_data[] = [
                                                'name'     => _l('gdpr_consent') . ' (' . _l('gdpr_short') . ')',
                                                'th_attrs' => ['id' => 'th-consent', 'class' => 'not-export'],
                                            ];
                                        }
                                        /*$_table_data[] = [
                                         'name'     => _l('lead_company'),
                                         'th_attrs' => ['class' => 'toggleable', 'id' => 'th-company'],
                                        ];*/
                                        $_table_data[] = [
                                            'name'     => _l('leads_dt_email'),
                                            'th_attrs' => ['class' => 'toggleable', 'id' => 'th-email'],
                                        ];
                                        $_table_data[] = [
                                            'name'     => _l('leads_dt_phonenumber'),
                                            'th_attrs' => ['class' => 'toggleable', 'id' => 'th-phone'],
                                        ];
                                        $_table_data[] = [
                                            'name'     => _l('clients_dt_country_'),
                                            'th_attrs' => ['class' => 'toggleable', 'id' => 'th-country'],
                                        ];
                                        /*$_table_data[] = [
                                           'name'     => _l('leads_dt_lead_value'),
                                           'th_attrs' => ['class' => 'toggleable', 'id' => 'th-lead-value'],
                                          ];*/
                                        $_table_data[] = [
                                            'name'     => _l('tags'),
                                            'th_attrs' => ['class' => 'toggleable', 'id' => 'th-tags'],
                                        ];
                                        $_table_data[] = [
                                            'name'     => _l('leads_dt_assigned'),
                                            'th_attrs' => ['class' => 'toggleable', 'id' => 'th-assigned'],
                                        ];
                                        $_table_data[] = [
                                            'name'     => _l('leads_dt_status'),
                                            'th_attrs' => ['class' => 'toggleable', 'id' => 'th-status'],
                                        ];
                                        $_table_data[] = [
                                            'name'     => _l('leads_source'),
                                            'th_attrs' => ['class' => 'toggleable', 'id' => 'th-source'],
                                        ];
                                        $_table_data[] = [
                                            'name'     => _l('leads_dt_last_contact'),
                                            'th_attrs' => ['class' => 'toggleable', 'id' => 'th-last-contact'],
                                        ];
                                        $_table_data[] = [
                                            'name'     => _l('leads_dt_datecreated'),
                                            'th_attrs' => ['class' => 'date-created toggleable', 'id' => 'th-date-created'],
                                        ];
                                        foreach ($_table_data as $_t) {
                                            array_push($table_data, $_t);
                                        }
                                        $custom_fields = get_custom_fields('leads', ['show_on_table' => 1]);
                                        foreach ($custom_fields as $field) {
                                            array_push($table_data, [
                                                'name'     => $field['name'],
                                                'th_attrs' => ['data-type' => $field['type'], 'data-custom-field' => 1],
                                            ]);
                                        }
                                        $table_data = hooks()->apply_filters('leads_table_columns', $table_data);
                                        ?>
                                        <div class="panel-table-full">
                                            <?php
                                            render_datatable(
                                                $table_data,
                                                'leads',
                                                ['customizable-table number-index-2'],
                                                [
                                                    'id'                         => 'table-leads',
                                                    'data-last-order-identifier' => 'leads',
                                                    'data-default-order'         => get_table_last_order('leads'),
                                                ]
                                            );
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script id="hidden-columns-table-leads" type="text/json">
<?php echo get_staff_meta(get_staff_user_id(), 'hidden-columns-table-leads'); ?>
</script>
<?php include_once(APPPATH . 'views/admin/leads/status.php'); ?>
<?php init_tail(); ?>
<script>
    var openLeadID = '<?php echo $leadid; ?>';
    $(function() {
        leads_kanban();
        $('#leads_bulk_mark_lost').on('change', function() {
            $('#move_to_status_leads_bulk').prop('disabled', $(this).prop('checked') == true);
            $('#move_to_status_leads_bulk').selectpicker('refresh')
        });
        $('#move_to_status_leads_bulk').on('change', function() {
            if ($(this).selectpicker('val') != '') {
                $('#leads_bulk_mark_lost').prop('disabled', true);
                $('#leads_bulk_mark_lost').prop('checked', false);
            } else {
                $('#leads_bulk_mark_lost').prop('disabled', false);
            }
        });
    });
    //finance
    function init_rel_finance_table(rel_id, rel_type, selector) {
        if (typeof selector == "undefined") {
            selector = ".table-rel-finance";
        }
        var $selector = $("body").find(selector);
        if ($selector.length === 0) {
            return;
        }

        var FinanceServerParams = {},
            financeRelationTableNotSortable = [0], // bulk actions
            FinanceFilters;

        FinanceFilters = $("body").find(
            "._hidden_inputs._filters._finance_filters input"
        );

        $.each(FinanceFilters, function () {
            FinanceServerParams[$(this).attr("name")] =
                '[name="' + $(this).attr("name") + '"]';
        });

        var url = admin_url + "finance/init_relation_finance/" + rel_id + "/" + rel_type;

        if ($selector.attr("data-new-rel-type") == "project") {
            url += "?bulk_actions=true";
        }

        initDataTable(
            $selector,
            url,
            financeRelationTableNotSortable,
            financeRelationTableNotSortable,
            FinanceServerParams,
            [$selector.find("th.dateadded").index(), "asc"]
        );
    }
    // $("input[name='finance_related_to[]']").on('change', function() {
    //     var finance_related_values = []
    //     $('#finance_related_filter :checkbox:checked').each(function(i) {
    //         finance_related_values[i] = $(this).val();
    //     });
    //     $('input[name="finance_related_to"]').val(finance_related_values.join());
    //     $('.table-rel-finance').DataTable().ajax.reload();
    // });
    function init_finance_modal(finance_id, comment_id) {
        var queryStr = "";
        var $leadModal = $("#lead-modal");
        var $financeAddEditModal = $("#_finance_modal");
        if ($leadModal.is(":visible")) {
            queryStr +=
                "?opened_from_lead_id=" + $leadModal.find('input[name="leadid"]').val();
            $leadModal.modal("hide");
        } else if ($financeAddEditModal.attr("data-lead-id") != undefined) {
            queryStr +=
                "?opened_from_lead_id=" + $financeAddEditModal.attr("data-lead-id");
        }

        requestGet("finance/get_finance_data/" + finance_id + queryStr)
            .done(function (response) {
                _finance_append_html(response);
                if (typeof comment_id != "undefined") {
                    setTimeout(function () {
                        $('[data-finance-comment-href-id="' + comment_id + '"]').click();
                    }, 1000);
                }
            })
            .fail(function (data) {
                $("#finance-modal").modal("hide");
                alert_float("danger", data.responseText);
            });
    }

    //orders
    function init_rel_orders_table(rel_id, rel_type, selector) {
        if (typeof selector == "undefined") {
            selector = ".table-rel-orders";
        }
        var $selector = $("body").find(selector);
        if ($selector.length === 0) {
            return;
        }

        var OrdersServerParams = {},
            ordersRelationTableNotSortable = [0], // bulk actions
            OrdersFilters;

        OrdersFilters = $("body").find(
            "._hidden_inputs._filters._orders_filters input"
        );

        $.each(OrdersFilters, function () {
            OrdersServerParams[$(this).attr("name")] =
                '[name="' + $(this).attr("name") + '"]';
        });

        var url = admin_url + "orders/init_relation_orders/" + rel_id + "/" + rel_type;

        if ($selector.attr("data-new-rel-type") == "project") {
            url += "?bulk_actions=true";
        }

        initDataTable(
            $selector,
            url,
            ordersRelationTableNotSortable,
            ordersRelationTableNotSortable,
            OrdersServerParams,
            [$selector.find("th.dateadded").index(), "asc"]
        );
    }

    //positions
    function init_rel_positions_table(rel_id, rel_type, selector) {
        if (typeof selector == "undefined") {
            selector = ".table-rel-positions";
        }
        var $selector = $("body").find(selector);
        if ($selector.length === 0) {
            return;
        }

        var PositionsServerParams = {},
            positionsRelationTableNotSortable = [0], // bulk actions
            PositionsFilters;

        PositionsFilters = $("body").find(
            "._hidden_inputs._filters._positions_filters input"
        );

        $.each(PositionsFilters, function () {
            PositionsServerParams[$(this).attr("name")] =
                '[name="' + $(this).attr("name") + '"]';
        });

        var url = admin_url + "positions/init_relation_positions/" + rel_id + "/" + rel_type;

        if ($selector.attr("data-new-rel-type") == "project") {
            url += "?bulk_actions=true";
        }

        initDataTable(
            $selector,
            url,
            positionsRelationTableNotSortable,
            positionsRelationTableNotSortable,
            PositionsServerParams,
            [$selector.find("th.dateadded").index(), "asc"]
        );
    }

    //accounts
    function init_rel_accounts_table(rel_id, rel_type, selector) {
        if (typeof selector == "undefined") {
            selector = ".table-rel-accounts";
        }
        var $selector = $("body").find(selector);
        if ($selector.length === 0) {
            return;
        }

        var AccountsServerParams = {},
            accountsRelationTableNotSortable = [0], // bulk actions
            AccountsFilters;

        AccountsFilters = $("body").find(
            "._hidden_inputs._filters._accounts_filters input"
        );

        $.each(AccountsFilters, function () {
            AccountsServerParams[$(this).attr("name")] =
                '[name="' + $(this).attr("name") + '"]';
        });

        var url = admin_url + "accounts/init_relation_accounts/" + rel_id + "/" + rel_type;

        if ($selector.attr("data-new-rel-type") == "project") {
            url += "?bulk_actions=true";
        }

        initDataTable(
            $selector,
            url,
            accountsRelationTableNotSortable,
            accountsRelationTableNotSortable,
            AccountsServerParams,
            []
        );
    }

    //leads
    // Add additional server params $_POST
    var LeadsServerParams = {
        custom_view: "[name='custom_view']",
        assigned: "[name='view_assigned']",
        status: "[name='view_status[]']",
        source: "[name='view_source']",
        from_date: "[name='clients_from_date']",
        to_date: "[name='clients_to_date']",
        country: "[name='country']",
        department: "[name='department']",
    };

    // Init the table
    table_leads = $("table.table-leads");
    if (table_leads.length) {
        var tableLeadsConsentHeading = table_leads.find("#th-consent");
        var leadsTableNotSortable = [0];
        var leadsTableNotSearchable = [0, table_leads.find("#th-assigned").index()];

        if (tableLeadsConsentHeading.length > 0) {
            leadsTableNotSortable.push(tableLeadsConsentHeading.index());
            leadsTableNotSearchable.push(tableLeadsConsentHeading.index());
        }

        _table_api = initDataTable(
            table_leads,
            admin_url + "leads/table",
            leadsTableNotSearchable,
            leadsTableNotSortable,
            LeadsServerParams,
            [table_leads.find("th.date-created").index(), "desc"]
        );

        if (_table_api && tableLeadsConsentHeading.length > 0) {
            _table_api.on("draw", function () {
                var tableData = table_leads.find("tbody tr");
                $.each(tableData, function () {
                    $(this).find("td:eq(3)").addClass("bg-neutral");
                });
            });
        }

        $.each(LeadsServerParams, function (i, obj) {
            if(i === 'from_date' || i === 'to_date'){
                $("input" + obj).on("change", function () {
                    table_leads.DataTable().ajax.reload();
                });
            }else{
                $("select" + obj).on("change", function () {
                    $("[name='view_status[]']")
                        .prop("disabled", $(this).val() == "lost" || $(this).val() == "junk")
                        .selectpicker("refresh");

                    table_leads.DataTable().ajax.reload();
                });
            }
        });
    }

    //confirm sync
    $("body").on("click", "._sync", function (e) {
        if (confirm_sync()) {
            return true;
        }
        return false;
    });

    function confirm_sync() {
        var message = "<?php echo _l('confirm_sync_text_'); ?>";

        var r = confirm(message);
        if (r == false) {
            return false;
        }
        return true;
    }

    $(".bulk_actions").on("change", 'input[name="mass_delete"]', function () {
        var $bulkChange = $("#bulk_change");
        if ($(this).prop("checked") === true) {
            $bulkChange.find("select").selectpicker("val", "");
            $('#mass_sync_div').addClass("hide");
            $bulkChange.addClass("hide");
            $(".mass_delete_separator, merge_tickets_checkbox").addClass("hide");
            $("#merge_tickets").prop("checked", false);
        } else {
            $('#mass_sync_div').removeClass("hide");
            $bulkChange.removeClass("hide");
            $(".mass_delete_separator, merge_tickets_checkbox").removeClass("hide");
        }
    });
    $(".bulk_actions").on("change", 'input[name="mass_sync"]', function () {
        var $bulkChange = $("#bulk_change");
        if ($(this).prop("checked") === true) {
            $bulkChange.find("select").selectpicker("val", "");
            $('#mass_delete_div').addClass("hide");
            $bulkChange.addClass("hide");
            $(".mass_delete_separator, merge_tickets_checkbox").addClass("hide");
            $("#merge_tickets").prop("checked", false);
        } else {
            $('#mass_delete_div').removeClass("hide");
            $bulkChange.removeClass("hide");
            $(".mass_delete_separator, merge_tickets_checkbox").removeClass("hide");
        }
    });
    function leads_bulk_action(event) {
        if (confirm_delete()) {
            var url = "leads/bulk_action";
            var mass_delete = $("#mass_delete").prop("checked");
            var mass_sync = $("#mass_sync").prop("checked");
            var ids = [];
            var data = {};
            if(mass_delete === true){
                data.mass_delete = true;
            }else if(mass_sync === true){
                url = "leads/sync";
                data.mass_sync = true;
            }else{
                data.lost = $("#leads_bulk_mark_lost").prop("checked");
                data.status = $("#move_to_status_leads_bulk").val();
                data.assigned = $("#assign_to_leads_bulk").val();
                data.source = $("#move_to_source_leads_bulk").val();
                data.last_contact = $("#leads_bulk_last_contact").val();
                data.tags = $("#tags_bulk").tagit("assignedTags");
                data.visibility = $('input[name="leads_bulk_visibility"]:checked').val();

                data.assigned = typeof data.assigned == "undefined" ? "" : data.assigned;
                data.visibility =
                    typeof data.visibility == "undefined" ? "" : data.visibility;

                if (
                    data.status === "" &&
                    data.lost === false &&
                    data.assigned === "" &&
                    data.source === "" &&
                    data.last_contact === "" &&
                    data.tags.length == 0 &&
                    data.visibility === ""
                ) {
                    return;
                }
            }

            var rows = table_leads.find("tbody tr");
            $.each(rows, function () {
                var checkbox = $($(this).find("td").eq(0)).find("input");
                if (checkbox.prop("checked") === true) {
                    ids.push(checkbox.val());
                }
            });
            data.ids = ids;
            $(event).addClass("disabled");
            setTimeout(function () {
                $.post(admin_url + url, data)
                    .done(function () {
                        window.location.reload();
                    })
                    .fail(function (data) {
                        $("#lead-modal").modal("hide");
                        alert_float("danger", data.responseText);
                    });
            }, 200);
        }
    }
</script>
</body>

</html>