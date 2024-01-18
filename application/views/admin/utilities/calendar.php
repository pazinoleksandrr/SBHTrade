<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body" style="overflow-x: auto;">
						<div class="dt-loader hide"></div>
						<?php //$this->load->view('admin/utilities/calendar_filters'); ?>
						<div id="calendar"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php $this->load->view('admin/utilities/calendar_template'); ?>
<?php hooks()->do_action('after_calendar_loaded');?>
<script>
	app.calendarIDs = '<?php echo json_encode($google_ids_calendars); ?>';
</script>
<?php init_tail(); ?>
<script>
	$(function(){
		if(get_url_param('eventid')) {
			view_event(get_url_param('eventid'));
		}
	});

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
</body>
</html>
