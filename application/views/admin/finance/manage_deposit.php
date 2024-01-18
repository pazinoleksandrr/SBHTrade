<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_buttons tw-mb-2 sm:tw-mb-4">
                    <?php if (has_permission('finance', '', 'create_deposit')) { ?>
                        <a href="#" onclick="init_deposit(); return false;"
                           class="btn btn-primary mright5 pull-left display-block">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('new_deposit_'); ?>
                        </a>
                    <?php } ?>
                    <div class="clearfix"></div>
                </div>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tab-content">
                            <div class="row" id="finance-table">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <p class="bold"><?php echo _l('filter_by'); ?></p>
                                        </div>
                                        <!--                                        --><?php //if (has_permission('leads', '', 'view')) { ?>
                                        <div class="col-md-3 finance-filter-column">
                                            <?php echo render_select('view_client', $client, ['id', 'name'], '', '', ['data-width' => '100%', 'data-none-selected-text' => _l('finance_dt_client_')], [], 'no-mbot'); ?>
                                        </div>
                                        <!--                                        --><?php //} ?>
                                        <!--                                        --><?php //if (has_permission('finance', '', 'view')) { ?>
                                        <div class="col-md-3 finance-filter-column">
                                            <?php echo render_select('view_assigned', $staff, ['staffid', ['firstname', 'lastname']], '', '', ['data-width' => '100%', 'data-none-selected-text' => _l('finance_dt_assigned_')], [], 'no-mbot'); ?>
                                        </div>
                                        <!--                                        --><?php //} ?>
                                        <div class="col-md-3 finance-filter-column">
                                            <?php
                                            $selected = [];
                                            if ($this->input->get('status')) {
                                                $selected[] = $this->input->get('status');
                                            }
                                            echo '<div id="finance-filter-status">';
                                            echo render_select('view_status[]', $statuses, ['id', 'name'], '', $selected, ['data-width' => '100%', 'data-none-selected-text' => _l('status_all_'), 'multiple' => true, 'data-actions-box' => true], [], 'no-mbot', '', false);
                                            echo '</div>';
                                            ?>
                                        </div>
                                    </div>
                                    <div class="row tw-mt-5">
                                        <div class="col-md-3 leads-filter-column">
                                            <?php echo render_date_input('deposit_from_date', '', $this->input->post('deposit_from_date'), ['placeholder' => _l('from_date')]); ?>
                                        </div>
                                        <div class="col-md-3 leads-filter-column">
                                            <?php echo render_date_input('deposit_to_date', '', $this->input->post('deposit_to_date'), ['placeholder' => _l('to_date')]); ?>
                                        </div>
                                    </div>
                                    <hr class="hr-panel-separator" />
                                </div>
                                <div class="clearfix"></div>

                                <div class="col-md-12">
                                    <?php

                                    $table_data  = [];
                                    $_table_data = [
                                        [
                                            'name'     => _l('the_number_sign'),
                                            'th_attrs' => ['class' => 'toggleable', 'id' => 'th-number'],
                                        ],
                                        [
                                            'name'     => _l('date_'),
                                            'th_attrs' => [
                                                'style' => 'width:75px',
                                                'class' => 'dateadded',
                                            ],
                                        ],
                                        [
                                            'name'     => _l('full_name_'),
                                            'th_attrs' => ['class' => 'toggleable', 'id' => 'th-name'],
                                        ],
                                    ];
                                    $_table_data[] = [
                                        'name'     => _l('email_'),
                                        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-email'],
                                    ];
                                    $_table_data[] = [
                                        'name'     => _l('account_number_'),
                                        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-account_number'],
                                    ];
                                    $_table_data[] = [
                                        'name'     => _l('assigned_staff_'),
                                        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-assigned'],
                                    ];
                                    $_table_data[] = [
                                        'name'     => _l('department_'),
                                        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-department'],
                                    ];
                                    $_table_data[] = [
                                        'name'     => _l('currency'),
                                        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-currency'],
                                    ];
                                    $_table_data[] = [
                                        'name'     => _l('amount_'),
                                        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-amount'],
                                    ];
                                    $_table_data[] = [
                                        'name'     => _l('status_'),
                                        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-status'],
                                    ];
                                    /*$_table_data[] = [
                                        'name'     => _l('type_'),
                                        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-type'],
                                    ];*/
                                    foreach ($_table_data as $_t) {
                                        array_push($table_data, $_t);
                                    }
                                    $custom_fields = get_custom_fields('finance', ['show_on_table' => 1]);
                                    foreach ($custom_fields as $field) {
                                        array_push($table_data, [
                                            'name'     => $field['name'],
                                            'th_attrs' => ['data-type' => $field['type'], 'data-custom-field' => 1],
                                        ]);
                                    }
                                    $table_data = hooks()->apply_filters('deposit_table_columns', $table_data);
                                    ?>
                                    <div class="panel-table-full">
                                        <?php
                                        render_datatable(
                                            $table_data,
                                            'deposit',
                                            ['customizable-table number-index-2'],
                                            [
                                                'id'                         => 'table-deposit',
                                                'data-last-order-identifier' => 'deposit',
                                                'data-default-order'         => get_table_last_order('deposit'),
                                            ]
                                        );
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script id="hidden-columns-table-deposit" type="text/json">
<?php echo get_staff_meta(get_staff_user_id(), 'hidden-columns-table-deposit'); ?>
</script>
<?php //include_once(APPPATH . 'views/admin/finance/status.php'); ?>
<?php init_tail(); ?>
<script>
    // Add additional server params $_POST
    var DepositServerParams = {
        client: "[name='view_client']",
        assigned: "[name='view_assigned']",
        status: "[name='view_status[]']",
        from_date: "[name='deposit_from_date']",
        to_date: "[name='deposit_to_date']",
    };

    // Init the table
    table_deposit = $("table.table-deposit");
    if (table_deposit.length) {
        var tableDepositConsentHeading = table_deposit.find("#th-consent");
        var depositTableNotSortable = [0];
        var depositTableNotSearchable = [0, table_deposit.find("#th-assigned").index()];

        if (tableDepositConsentHeading.length > 0) {
            depositTableNotSortable.push(tableDepositConsentHeading.index());
            depositTableNotSearchable.push(tableDepositConsentHeading.index());
        }

        _table_api = initDataTable(
            table_deposit,
            admin_url + "finance/table_deposit",
            depositTableNotSearchable,
            depositTableNotSortable,
            DepositServerParams,
            [table_deposit.find("th.dateadded").index(), "desc"]
        );

        if (_table_api && tableDepositConsentHeading.length > 0) {
            _table_api.on("draw", function () {
                var tableData = table_deposit.find("tbody tr");
                $.each(tableData, function () {
                    $(this).find("td:eq(3)").addClass("bg-neutral");
                });
            });
        }

        $.each(DepositServerParams, function (i, obj) {
            if(i === 'from_date' || i === 'to_date'){
                $("input" + obj).on("change", function () {
                    table_deposit.DataTable().ajax.reload();
                });
            }else{
                $("select" + obj).on("change", function () {
                    table_deposit.DataTable().ajax.reload();
                });
            }
        });
    }

    // Init deposit for add/edit/view or refresh data
    function init_deposit(id, isEdit) {
        if ($("#finance-modal").is(":visible")) {
            $("#finance-modal").modal("hide");
        }
        // In case header error
        if (init_deposit_modal_data(id, undefined, isEdit)) {
            $("#finance-modal").modal("show");
        }
    }

    // Fetches deposit modal data, can be edit/add/view
    function init_deposit_modal_data(id, url, isEdit) {
        var requestURL =
            (typeof url != "undefined" ? url : "finance/deposit/") +
            (typeof id != "undefined" ? id : "new");

        if (isEdit === true) {
            var concat = "?";
            if (requestURL.indexOf("?") > -1) {
                concat += "&";
            }
            requestURL += concat + "edit=true";
        }

        requestGetJSON(requestURL)
            .done(function (response) {
                _deposit_init_data(response, id);
            })
            .fail(function (data) {
                alert_float("danger", data.responseText);
            });
    }

    function _deposit_init_data(data, id) {
        var hash = window.location.hash;

        var $financeModal = $("#finance-modal");

        $financeModal.find(".data").html(data.financeView.data);

        $financeModal.modal({
            show: true,
            backdrop: "static",
        });

        init_selectpicker();
        init_datepicker();
        custom_fields_hyperlink();
        validate_deposit_form();

        initDataTableInline($("#consentHistoryTable"));
    }

    // Deposit form validation
    function validate_deposit_form() {
        var validationObject = {
            client: "required",
            account_type: "required",
            amount: "required",
            currency: "required",
            status: "required",
        };

        var messages = {};

        appValidateForm(
            $("#finance_form"),
            validationObject,
            deposit_form_handler,
            messages
        );
    }
    function deposit_form_handler(form) {
        form = $(form);
        var data = form.serialize();
        $(".finance-save-btn").addClass("disabled");
        $.post(form.attr("action"), data)
            .done(function (response) {
                response = JSON.parse(response);
                if (response.message !== "") {
                    var type = response.success === true ? 'success' : 'danger';
                    alert_float(type, response.message);
                }
                if (response.proposal_warning && response.proposal_warning != false) {
                    $("body").find("#finance-modal").animate(
                        {
                            scrollTop: 0,
                        },
                        800
                    );
                } else {
                    _deposit_init_data(response, response.id);
                }
                table_deposit.DataTable().ajax.reload(null, false);
            })
            .fail(function (data) {
                alert_float("danger", data.responseText);
                return false;
            });
        return false;
    }

    function print_finance_information() {
        var $leadViewWrapper = $("#leadViewWrapper").clone();
        var name = $leadViewWrapper.find(".lead-name").text().trim();

        $leadViewWrapper
            .find("p")
            .css("font-size", "100%")
            .css("font", "inherit")
            .css("vertical-align", "baseline")
            .css("margin", "0px");

        $leadViewWrapper.find("h4").css("font-size", "100%");

        $leadViewWrapper
            .find(".lead-field-heading")
            .css("color", "#777")
            .css("margin-bottom", "3px");
        $leadViewWrapper.find(".lead-field-heading + p").css("margin-bottom", "15px");

        var mywindow = _create_print_window(name);

        mywindow.document.write("<html><head><title>" + "<?php echo _l('deposit_'); ?>" + "</title>");
        _add_print_window_default_styles(mywindow);
        mywindow.document.write("<style>");
        mywindow.document.write(
            ".lead-information-col { " + "float: left; width: 33.33333333%;" + "}" + ""
        );
        mywindow.document.write("</style>");

        mywindow.document.write("</head><body>");
        mywindow.document.write("<h1>" + name + "</h1>");
        mywindow.document.write(
            '<div id="#leadViewWrapper">' + $leadViewWrapper.html() + "</div>"
        );
        mywindow.document.write("</body></html>");

        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10*/

        mywindow.print();
        setTimeout(function () {
            mywindow.close();
        }, 1000);
    }

    $("body").on("click", "[deposit-edit]", (function(e) {
            e.preventDefault();
            var t = $("body .deposit-edit");$("body .lead-view").toggleClass("hide")
            t.toggleClass("hide")
            let client = $("#client");
            if(client.val() !== "" && client.val() !== "0" && client !== null) {
                let element = $("#account");
                let account_id = $("body").find('#finance-modal input[name="account_id"]').val();
                $.ajax({
                    url:"<?php echo base_url('admin/finance/get_accounts'); ?>",
                    method:"POST",
                    data:{client_id: client.val()},
                    success:function(response)
                    {
                        element.empty().trigger('change');
                        let res = JSON.parse(response);
                        if(typeof res.data !== 'undefined'){
                            element.empty();
                            element.append('<option value=""></option>');
                            for (const [key, value] of Object.entries(res.data)) {
                                let selected = "";
                                if(value.id === account_id) selected = 'selected="selected"';
                                element.append('<option value="'+value.id+'" '+selected+'>'+$.trim(value.account_number) + ' - ' + $.trim(value.currency)+'</option>');
                            }
                            element.selectpicker("refresh");
                        }else{
                            element.empty();
                            element.append('<option value=""></option>');
                            element.selectpicker("refresh");
                        }
                    }
                })
            }
        }
    ))
    $("body").on("shown.bs.modal", "#finance-modal", function (e) {
        custom_fields_hyperlink();
        if ($("body").find('#finance-modal input[name="id"]').length === 0) {
            $("body").find('#finance-modal input[name="amount"]').focus();
        }
        let client = $("#client");
        if(client.val() !== "" && client.val() !== "0" && client !== null) {
            let element = $("#account");
            let account_id = $("body").find('#finance-modal input[name="account_id"]').val();
            $.ajax({
                url:"<?php echo base_url('admin/finance/get_accounts'); ?>",
                method:"POST",
                data:{client_id: client.val()},
                success:function(response)
                {
                    element.empty().trigger('change');
                    let res = JSON.parse(response);
                    if(typeof res.data !== 'undefined'){
                        element.empty();
                        element.append('<option value=""></option>');
                        for (const [key, value] of Object.entries(res.data)) {
                            let selected = "";
                            if(value.id === account_id) selected = 'selected="selected"';
                            element.append('<option value="'+value.id+'" '+selected+'>'+$.trim(value.account_number) + ' - ' + $.trim(value.currency)+'</option>');
                        }
                        element.selectpicker("refresh");
                    }else{
                        element.empty();
                        element.append('<option value=""></option>');
                        element.selectpicker("refresh");
                    }
                }
            })
        }
        init_tabs_scrollable();
        if ($("body").find(".lead-wrapper").hasClass("open-edit")) {
            $("body").find("a[deposit-edit]").click();
        }
    });

    $(document).on('change', '#client', function(){
        let element = $("#account");
        let account_id = $("body").find('#finance-modal input[name="account_id"]').val();
        if($(this).val() !== "" && $(this).val() !== "0" && $(this).val() !== null) {
            $.ajax({
                url:"<?php echo base_url('admin/finance/get_accounts'); ?>",
                method:"POST",
                data:{client_id: $(this).val()},
                // dataType:'JSON',
                success:function(response)
                {
                    element.empty().trigger('change');
                    let res = JSON.parse(response);
                    // console.log(res.data)
                    if(typeof res.data !== 'undefined'){
                        element.empty();
                        element.append('<option value=""></option>');
                        for (const [key, value] of Object.entries(res.data)) {
                            /*var option = new Option($.trim(value.account_number), value.id, false, false);
                            option.title = $.trim(value.account_number) + ' - ' + $.trim(value.c_name);
                            element.append(option).trigger('change');*/
                            let selected = "";
                            if(value.id === account_id) selected = 'selected="selected"';
                            element.append('<option value="'+value.id+'" '+selected+'>'+$.trim(value.account_number) + ' - ' + $.trim(value.currency)+'</option>');
                        }
                        // element.val('').trigger('change');
                        element.selectpicker("refresh");
                    }else{
                        // $('#account').val('').trigger('change');
                        element.empty();
                        element.append('<option value=""></option>');
                        element.selectpicker("refresh");
                    }
                }
            })
        }
    });
</script>
</body>

</html>