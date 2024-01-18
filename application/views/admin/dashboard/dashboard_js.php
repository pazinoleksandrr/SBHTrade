<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
    var weekly_payments_statistics;
    var monthly_payments_statistics;
    var weekly_deposits_statistics;
    var monthly_deposits_statistics;
    var weekly_withdrawals_statistics;
    var monthly_withdrawals_statistics;
    var staff_report;
    var user_dashboard_visibility = <?php echo $user_dashboard_visibility; ?>;
    $(function() {
        $("[data-container]").sortable({
            connectWith: "[data-container]",
            helper: 'clone',
            handle: '.widget-dragger',
            tolerance: 'pointer',
            forcePlaceholderSize: true,
            placeholder: 'placeholder-dashboard-widgets',
            start: function(event, ui) {
                $("body,#wrapper").addClass('noscroll');
                $('body').find('[data-container]').css('min-height', '20px');
            },
            stop: function(event, ui) {
                $("body,#wrapper").removeClass('noscroll');
                $('body').find('[data-container]').removeAttr('style');
            },
            update: function(event, ui) {
                if (this === ui.item.parent()[0]) {
                    var data = {};
                    $.each($("[data-container]"), function() {
                        var cId = $(this).attr('data-container');
                        data[cId] = $(this).sortable('toArray');
                        if (data[cId].length == 0) {
                            data[cId] = 'empty';
                        }
                    });
                    $.post(admin_url + 'staff/save_dashboard_widgets_order', data, "json");
                }
            }
        });

        // Read more for dashboard todo items
        $('.read-more').readmore({
            collapsedHeight: 150,
            moreLink: "<a href=\"#\"><?php echo _l('read_more'); ?></a>",
            lessLink: "<a href=\"#\"><?php echo _l('show_less'); ?></a>",
        });

        $('body').on('click', '#viewWidgetableArea', function(e) {
            e.preventDefault();

            if (!$(this).hasClass('preview')) {
                $(this).html("<?php echo _l('hide_widgetable_area'); ?>");
                $('[data-container]').append(
                    '<div class="placeholder-dashboard-widgets pl-preview"></div>');
            } else {
                $(this).html("<?php echo _l('view_widgetable_area'); ?>");
                $('[data-container]').find('.pl-preview').remove();
            }

            $('[data-container]').toggleClass('preview-widgets');
            $(this).toggleClass('preview');
        });

        var $widgets = $('.widget');
        var widgetsOptionsHTML = '';
        widgetsOptionsHTML += '<div id="dashboard-options">';
        widgetsOptionsHTML +=
            "<div class=\"tw-flex tw-space-x-4 tw-items-center\"><h4 class='tw-font-medium tw-text-neutral-600 tw-text-lg'><i class='fa-regular fa-circle-question' data-toggle='tooltip' data-placement=\"bottom\" data-title=\"<?php echo _l('widgets_visibility_help_text'); ?>\"></i> <?php echo _l('widgets'); ?></h4><a href=\"<?php echo admin_url('staff/reset_dashboard'); ?>\" class=\"tw-text-sm\"><?php echo _l('reset_dashboard'); ?></a>";

        widgetsOptionsHTML +=
            ' <a href=\"#\" id="viewWidgetableArea" class=\"tw-text-sm\"><?php echo _l('view_widgetable_area'); ?></a></div>';

        $.each($widgets, function() {
            var widget = $(this);
            var widgetOptionsHTML = '';
            if (widget.data('name') && widget.html().trim().length > 0) {
                widgetOptionsHTML += '<div class="checkbox">';
                var wID = widget.attr('id');
                wID = wID.split('widget-');
                wID = wID[wID.length - 1];
                var checked = ' ';
                var db_result = $.grep(user_dashboard_visibility, function(e) {
                    return e.id == wID;
                });
                if (db_result.length >= 0) {
                    // no options saved or really visible
                    if (typeof(db_result[0]) == 'undefined' || db_result[0]['visible'] == 1) {
                        checked = ' checked ';
                    }
                }
                widgetOptionsHTML += '<input type="checkbox" class="widget-visibility" value="' + wID +
                    '"' + checked + 'id="widget_option_' + wID + '" name="dashboard_widgets[' + wID + ']">';
                widgetOptionsHTML += '<label for="widget_option_' + wID + '">' + widget.data('name') +
                    '</label>';
                widgetOptionsHTML += '</div>';
            }
            widgetsOptionsHTML += widgetOptionsHTML;
        });

        $('.screen-options-area').append(widgetsOptionsHTML);
        $('body').find('#dashboard-options input.widget-visibility').on('change', function() {
            if ($(this).prop('checked') == false) {
                $('#widget-' + $(this).val()).addClass('hide');
            } else {
                $('#widget-' + $(this).val()).removeClass('hide');
            }

            var data = {};
            var options = $('#dashboard-options input[type="checkbox"]').map(function() {
                return {
                    id: this.value,
                    visible: this.checked ? 1 : 0
                };
            }).get();

            data.widgets = options;
            /*
                    if (typeof(csrfData) !== 'undefined') {
                        data[csrfData['token_name']] = csrfData['hash'];
                    }
            */
            $.post(admin_url + 'staff/save_dashboard_widgets_visibility', data).fail(function(data) {
                // Demo usage, prevent multiple alerts
                if ($('body').find('.float-alert').length == 0) {
                    alert_float('danger', data.responseText);
                }
            });
        });

        var tickets_chart_departments = $('#tickets-awaiting-reply-by-department');
        var tickets_chart_status = $('#tickets-awaiting-reply-by-status');
        var leads_chart = $('#leads_status_stats');
        var projects_chart = $('#projects_status_stats');

        if (tickets_chart_departments.length > 0) {
            // Tickets awaiting reply by department chart
            var tickets_dep_chart = new Chart(tickets_chart_departments, {
                type: 'doughnut',
                data: <?php echo $tickets_awaiting_reply_by_department; ?>,
            });
        }
        if (tickets_chart_status.length > 0) {
            // Tickets awaiting reply by department chart
            new Chart(tickets_chart_status, {
                type: 'doughnut',
                data: <?php echo $tickets_reply_by_status; ?>,
                options: {
                    onClick: function(evt) {
                        onChartClickRedirect(evt, this);
                    }
                },
            });
        }
        if (leads_chart.length > 0) {
            // Leads overview status
            new Chart(leads_chart, {
                type: 'doughnut',
                data: <?php echo $leads_status_stats; ?>,
                options: {
                    maintainAspectRatio: false,
                    onClick: function(evt) {
                        onChartClickRedirect(evt, this);
                    }
                }
            });
        }
        if (projects_chart.length > 0) {
            // Projects statuses
            new Chart(projects_chart, {
                type: 'doughnut',
                data: <?php echo $projects_status_stats; ?>,
                options: {
                    maintainAspectRatio: false,
                    onClick: function(evt) {
                        onChartClickRedirect(evt, this);
                    }
                }
            });
        }

        if ($(window).width() < 500) {
            // Fix for small devices weekly payment statistics
            //$('#payment-statistics').attr('height', '250');
            $('#deposit-statistics').attr('height', '250');
            $('#withdrawal-statistics').attr('height', '250');
        }

        fix_user_data_widget_tabs();
        $(window).on('resize', function() {
            $('.horizontal-scrollable-tabs ul.nav-tabs-horizontal').removeAttr('style');
            fix_user_data_widget_tabs();
        });
        // Payments statistics
        //init_weekly_payment_statistics(<?php //echo $weekly_payment_stats; ?>);
        init_weekly_deposit_statistics(<?php echo $weekly_deposit_stats; ?>);
        init_weekly_withdrawal_statistics(<?php echo $weekly_withdrawal_stats; ?>);

        /*$('select[name="currency"]').on('change', function() {
            let $activeChart = $('#Payment-chart-name').data('active-chart');

            if (typeof(weekly_payments_statistics) !== 'undefined') {
                weekly_payments_statistics.destroy();
            }

            if (typeof(monthly_payments_statistics) !== 'undefined') {
                monthly_payments_statistics.destroy();
            }

            if ($activeChart == 'weekly') {
                init_weekly_payment_statistics();
            } else if ($activeChart == 'monthly') {
                init_monthly_payment_statistics();
            }

        });*/
        $('select[name="status_deposit"]').on('change', function() {
            let $activeChart = $('#Deposit-chart-name').data('active-chart');

            if (typeof(weekly_deposits_statistics) !== 'undefined') {
                weekly_deposits_statistics.destroy();
            }

            if (typeof(monthly_deposits_statistics) !== 'undefined') {
                monthly_deposits_statistics.destroy();
            }

            if ($activeChart == 'weekly') {
                init_weekly_deposit_statistics();
            } else if ($activeChart == 'monthly') {
                init_monthly_deposit_statistics();
            }

        });
        $('select[name="status_withdrawal"]').on('change', function() {
            let $activeChart = $('#Withdrawal-chart-name').data('active-chart');

            if (typeof(weekly_withdrawals_statistics) !== 'undefined') {
                weekly_withdrawals_statistics.destroy();
            }

            if (typeof(monthly_withdrawals_statistics) !== 'undefined') {
                monthly_withdrawals_statistics.destroy();
            }

            if ($activeChart == 'weekly') {
                init_weekly_withdrawal_statistics();
            } else if ($activeChart == 'monthly') {
                init_monthly_withdrawal_statistics();
            }

        });
        /*$('select[name="status_effectiveness"]').on('change', function() {
            var el = $('a.selected_type');
            update_effectiveness_report_table(el);
        });*/
        init_staff_report(<?php echo $leads_staff_report; ?>);
        $('#generate').on('click', function(e) {
            e.preventDefault();
            init_staff_report();
        });
    });

    function fix_user_data_widget_tabs() {
        if ((app.browser != 'firefox' &&
            isRTL == 'false' && is_mobile()) || (app.browser == 'firefox' &&
            isRTL == 'false' && is_mobile())) {
            $('.horizontal-scrollable-tabs ul.nav-tabs-horizontal').css('margin-bottom', '26px');
        }
    }

    function init_weekly_payment_statistics(data) {
        if ($('#payment-statistics').length > 0) {

            if (typeof(weekly_payments_statistics) !== 'undefined') {
                weekly_payments_statistics.destroy();
            }
            if (typeof(data) == 'undefined') {
                var currency = $('select[name="currency"]').val();
                $.get(admin_url + 'dashboard/weekly_payments_statistics/' + currency, function(response) {
                    weekly_payments_statistics = new Chart($('#payment-statistics'), {
                        type: 'bar',
                        data: response,
                        options: {
                            responsive: true,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                    }
                                }]
                            },
                        },
                    });
                }, 'json');
            } else {
                weekly_payments_statistics = new Chart($('#payment-statistics'), {
                    type: 'bar',
                    data: data,
                    options: {
                        responsive: true,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                }
                            }]
                        },
                    },
                });
            }

        }
    }

    function init_monthly_payment_statistics() {
        if ($('#payment-statistics').length > 0) {

            if (typeof(monthly_payments_statistics) !== 'undefined') {
                monthly_payments_statistics.destroy();
            }

            var currency = $('select[name="currency"]').val();
            $.get(admin_url + 'dashboard/monthly_payments_statistics/' + currency, function(response) {
                monthly_payments_statistics = new Chart($('#payment-statistics'), {
                    type: 'bar',
                    data: response,
                    options: {
                        responsive: true,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                }
                            }]
                        },
                    },
                });
            }, 'json');
        }
    }

    function update_payment_statistics(el) {
        let type = $(el).data('type');
        let $chartNameWrapper = $('#Payment-chart-name');
        $chartNameWrapper.data('active-chart', type);
        $chartNameWrapper.text($(el).text());

        if (typeof(weekly_payments_statistics) !== 'undefined') {
            weekly_payments_statistics.destroy();
        }

        if (typeof(monthly_payments_statistics) !== 'undefined') {
            monthly_payments_statistics.destroy();
        }

        console.log(type);

        if (type == 'weekly') {
            init_weekly_payment_statistics();
        } else if (type == 'monthly') {
            init_monthly_payment_statistics();
        }

    }

    function init_weekly_deposit_statistics(data) {
        if ($('#deposit-statistics').length > 0) {

            if (typeof(weekly_deposits_statistics) !== 'undefined') {
                weekly_deposits_statistics.destroy();
            }
            if (typeof(data) == 'undefined') {
                var status = $('select[name="status_deposit"]').val();
                $.get(admin_url + 'dashboard/weekly_deposits_statistics/' + status, function(response) {
                    weekly_deposits_statistics = new Chart($('#deposit-statistics'), {
                        type: 'bar',
                        data: response,
                        options: {
                            responsive: true,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                    }
                                }]
                            },
                        },
                    });
                }, 'json');
            } else {
                weekly_deposits_statistics = new Chart($('#deposit-statistics'), {
                    type: 'bar',
                    data: data,
                    options: {
                        responsive: true,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                }
                            }]
                        },
                    },
                });
            }

        }
    }

    function init_monthly_deposit_statistics() {
        if ($('#deposit-statistics').length > 0) {

            if (typeof(monthly_deposits_statistics) !== 'undefined') {
                monthly_deposits_statistics.destroy();
            }

            var status = $('select[name="status_deposit"]').val();
            $.get(admin_url + 'dashboard/monthly_deposits_statistics/' + status, function(response) {
                monthly_deposits_statistics = new Chart($('#deposit-statistics'), {
                    type: 'bar',
                    data: response,
                    options: {
                        responsive: true,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                }
                            }]
                        },
                    },
                });
            }, 'json');
        }
    }

    function update_deposit_statistics(el) {
        let type = $(el).data('type');
        let $chartNameWrapper = $('#Deposit-chart-name');
        $chartNameWrapper.data('active-chart', type);
        $chartNameWrapper.text($(el).text());

        if (typeof(weekly_deposits_statistics) !== 'undefined') {
            weekly_deposits_statistics.destroy();
        }

        if (typeof(monthly_deposits_statistics) !== 'undefined') {
            monthly_deposits_statistics.destroy();
        }

        console.log(type);

        if (type == 'weekly') {
            init_weekly_deposit_statistics();
        } else if (type == 'monthly') {
            init_monthly_deposit_statistics();
        }

    }

    function init_weekly_withdrawal_statistics(data) {
        if ($('#withdrawal-statistics').length > 0) {

            if (typeof(weekly_withdrawals_statistics) !== 'undefined') {
                weekly_withdrawals_statistics.destroy();
            }
            if (typeof(data) == 'undefined') {
                var status = $('select[name="status_withdrawal"]').val();
                $.get(admin_url + 'dashboard/weekly_withdrawals_statistics/' + status, function(response) {
                    weekly_withdrawals_statistics = new Chart($('#withdrawal-statistics'), {
                        type: 'bar',
                        data: response,
                        options: {
                            responsive: true,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                    }
                                }]
                            },
                        },
                    });
                }, 'json');
            } else {
                weekly_withdrawals_statistics = new Chart($('#withdrawal-statistics'), {
                    type: 'bar',
                    data: data,
                    options: {
                        responsive: true,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                }
                            }]
                        },
                    },
                });
            }

        }
    }

    function init_monthly_withdrawal_statistics() {
        if ($('#withdrawal-statistics').length > 0) {

            if (typeof(monthly_withdrawals_statistics) !== 'undefined') {
                monthly_withdrawals_statistics.destroy();
            }

            var status = $('select[name="status_withdrawal"]').val();
            $.get(admin_url + 'dashboard/monthly_withdrawals_statistics/' + status, function(response) {
                monthly_withdrawals_statistics = new Chart($('#withdrawal-statistics'), {
                    type: 'bar',
                    data: response,
                    options: {
                        responsive: true,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                }
                            }]
                        },
                    },
                });
            }, 'json');
        }
    }

    function update_withdrawal_statistics(el) {
        let type = $(el).data('type');
        let $chartNameWrapper = $('#Withdrawal-chart-name');
        $chartNameWrapper.data('active-chart', type);
        $chartNameWrapper.text($(el).text());

        if (typeof(weekly_withdrawals_statistics) !== 'undefined') {
            weekly_withdrawals_statistics.destroy();
        }

        if (typeof(monthly_withdrawals_statistics) !== 'undefined') {
            monthly_withdrawals_statistics.destroy();
        }

        console.log(type);

        if (type == 'weekly') {
            init_weekly_withdrawal_statistics();
        } else if (type == 'monthly') {
            init_monthly_withdrawal_statistics();
        }

    }

    /*function update_tickets_report_table(el) {
        var $el = $(el);
        var type = $el.data('type')
        $('#tickets-report-mode-name').text($el.text())

        $('#tickets-report-table-wrapper').load(admin_url + 'dashboard/ticket_widget/' + type, function(data) {
            $('.table-ticket-reports').dataTable().fnDestroy()
            initDataTableInline('.table-ticket-reports')
        });
        return false
    }*/

    function update_effectiveness_report_table(el) {
        /*var $el = $(el);
        $("a.type_a").removeClass('selected_type');
        $el.addClass('selected_type');
        var type = $el.data('type')
        $('#effectiveness-report-mode-name').text($el.text())

        var status = $('select[name="status_effectiveness"]').val();
        $('#effectiveness-report-table-wrapper').load(admin_url + 'dashboard/effectiveness_widget/' + type + '/' + status, function(data) {*/
        $('#effectiveness-report-table-wrapper').load(admin_url + 'dashboard/effectiveness_widget', function(data) {
            $('.table-effectiveness-reports').dataTable().fnDestroy()
            initDataTableInline('.table-effectiveness-reports')
        });
        return false
    }

    function init_staff_report(data) {
        if ($('#leads-staff-report').length > 0) {

            if (typeof(staff_report) !== 'undefined') {
                staff_report.destroy();
            }

            if (typeof(data) == 'undefined') {
                var from_date = $('#staff_report_from_date').val();
                var to_date = $('#staff_report_to_date').val();
                var department = $('#department').val();
                $.post(admin_url + 'dashboard/staff_report', {from_date, to_date, department}, function(response) {
                    staff_report = new Chart($('#leads-staff-report'), {
                        type: 'bar',
                        data: response,
                        options: {
                            responsive: true,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                    }
                                }]
                            },
                            maintainAspectRatio: false,
                        },
                    });
                }, 'json');
            } else {
                staff_report = new Chart($('#leads-staff-report'), {
                    type: 'bar',
                    data: data,
                    options: {
                        responsive: true,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                }
                            }]
                        },
                        maintainAspectRatio: false,
                    },
                });
            }
        }
    }

    /*window.onload = function(){
        new Chart($('#leads-staff-report'),{
            data:<?php //echo $leads_staff_report; ?>,
        type:'bar',
        options:{responsive:true,maintainAspectRatio:false}
    })
};*/

    var calendar_selector = $("#calendar")
    if (calendar_selector.length > 0) {
        validate_calendar_form();

        var calendar_settings = {
            customButtons: {},
            locale: app.locale,
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,timeGridWeek,timeGridDay",
            },
            editable: false,
            dayMaxEventRows: parseInt(app.options.calendar_events_limit) + 1,

            views: {
                day: {
                    dayMaxEventRows: false,
                },
            },

            direction: isRTL == "true" ? "rtl" : "ltr",
            eventStartEditable: false,
            firstDay: parseInt(app.options.calendar_first_day),
            initialView: app.options.default_view_calendar,
            timeZone: app.options.timezone,

            loading: function (isLoading, view) {
                !isLoading
                    ? $(".dt-loader").addClass("hide")
                    : $(".dt-loader").removeClass("hide");
            },

            eventSources: [
                function (info, successCallback, failureCallback) {
                    var params = {};
                    $("#calendar_filters")
                        .find("input:checkbox:checked")
                        .map(function () {
                            params[$(this).attr("name")] = true;
                        })
                        .get();

                    if (!jQuery.isEmptyObject(params)) {
                        params["calendar_filters"] = true;
                    }

                    return $.getJSON(
                        admin_url + "utilities/get_calendar_data",
                        $.extend({}, params, {
                            start: info.startStr,
                            end: info.endStr,
                        })
                    ).then(function (data) {
                        successCallback(
                            data.map(function (e) {
                                return $.extend({}, e, {
                                    start: e.start || e.date,
                                    end: e.end || e.date,
                                });
                            })
                        );
                    });
                },
            ],

            moreLinkClick: function (info) {
                calendar.gotoDate(info.date);
                calendar.changeView("dayGridDay");

                setTimeout(function () {
                    $(".fc-popover-close").click();
                }, 250);
            },

            eventDidMount: function (data) {
                var $el = $(data.el);
                $el.attr("title", data.event.extendedProps._tooltip);
                $el.attr("onclick", data.event.extendedProps.onclick);
                $el.attr("data-toggle", "tooltip");
                if (!data.event.extendedProps.url) {
                    $el.on("click", function () {
                        view_event(data.event.extendedProps.eventid);
                    });
                }
            },

            dateClick: function (info) {
                if (info.dateStr.length <= 10) {
                    // has not time
                    info.dateStr += "T00:00:00";
                }

                var fmt = new DateFormatter();

                var d1 = fmt.formatDate(
                    new Date(info.dateStr),
                    (vformat =
                        app.options.time_format == 24
                            ? app.options.date_format + " H:i"
                            : app.options.date_format + " g:i A")
                );

                $("input[name='start'].datetimepicker").val(d1);
                $("#newEventModal").modal("show");

                return false;
            },
        };

        if ($("body").hasClass("dashboard")) {
            calendar_settings.customButtons.viewFullCalendar = {
                text: app.lang.calendar_expand,
                click: function () {
                    window.location.href = admin_url + "utilities/calendar";
                },
            };

            calendar_settings.headerToolbar.left += ",viewFullCalendar";
        }

        /*calendar_settings.customButtons.calendarFilter = {
            text: app.lang.filter_by.toLowerCase(),
            click: function () {
                slideToggle("#calendar_filters");
            },
        };

        calendar_settings.headerToolbar.right += ",calendarFilter";*/

        if (app.user_is_staff_member == 1) {
            if (app.options.google_api !== "") {
                calendar_settings.googleCalendarApiKey = app.options.google_api;
            }

            if (app.calendarIDs !== "") {
                app.calendarIDs = JSON.parse(app.calendarIDs);
                if (app.calendarIDs.length != 0) {
                    if (app.options.google_api !== "") {
                        for (var i = 0; i < app.calendarIDs.length; i++) {
                            var _gcal = {};
                            _gcal.googleCalendarId = app.calendarIDs[i];
                            calendar_settings.eventSources.push(_gcal);
                        }
                    } else {
                        console.error(
                            "You have setup Google Calendar IDs but you dont have specified Google API key. To setup Google API key navigate to Setup->Settings->Google"
                        );
                    }
                }
            }
        }

        var calendar = new FullCalendar.Calendar(
            calendar_selector[0],
            calendar_settings
        );
        calendar.render();

        var new_event = get_url_param("new_event");

        if (new_event) {
            $("input[name='start'].datetimepicker").val(get_url_param("date"));
            $("#newEventModal").modal("show");
        }
    }

</script>
