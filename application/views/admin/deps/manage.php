<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php if(is_admin()){ ?>
                    <div class="tw-mb-2 sm:tw-mb-4">
                        <a href="#" onclick="new_department(); return false;" class="btn btn-primary">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('new_department'); ?>
                        </a>
                    </div>
                <?php } ?>

                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                            '#',
                            _l('department_list_name'),
                            _l('number_staff_'),
                            _l('lead'),
                        ], 'departments'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="department" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('deps/department'), ['id' => 'department-form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <span class="edit-title"><?php echo _l('edit_department'); ?></span>
                    <span class="add-title"><?php echo _l('new_department'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="additional"></div>
                        <?php echo render_input('name', 'department_name'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
        </div><!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- staff_list-->
<div class="modal fade" id="staff_list" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <span class="staff_modal_title"><?php echo _l('staff'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <?php render_datatable([
                            _l('full_name_'),
                            _l('email_'),
                            _l('clients_'),
                            _l('last_login_'),], 'staff-list-table'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-departments', window.location.href, [], [1,2,3], undefined, [0, 'asc']);
        appValidateForm($('form'), {
            name: 'required',
        }, manage_departments);
        $('#department').on('hidden.bs.modal', function(event) {
            $('#additional').html('');
            $('#department input[type="text"]').val('');
            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
        });
    });

    function manage_departments(form) {
        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            response = JSON.parse(response);
            if (response.success == true) {
                alert_float('success', response.message);
            }
            $('.table-departments').DataTable().ajax.reload();
            $('#department').modal('hide');
        }).fail(function(data) {
            var error = JSON.parse(data.responseText);
            alert_float('danger', error.message);
        });
        return false;
    }

    function new_department() {
        $('#department').modal('show');
        $('.edit-title').addClass('hide');
    }

    function edit_dep(invoker, id) {

        $('#additional').append(hidden_input('id', id));

        $('#department input[name="name"]').val($(invoker).data('name'));
        $('#department').modal('show');
        $('.add-title').addClass('hide');
    }

    //staff list pop-up
    function init_staff_modal(cfv_id, dep_name) {
        if(dep_name !== '' && dep_name !== 'undefined') $('.staff_modal_title').text(dep_name);
        $("#staff_list").modal("show");
        init_staff_table(cfv_id, dep_name);
    }

    function init_staff_table(rel_id, rel_type, selector) {
        if (typeof selector == "undefined") {
            selector = ".table-staff-list-table";
        }
        var $selector = $("body").find(selector);
        if ($selector.length === 0) {
            return;
        }
        $(selector).dataTable().fnDestroy()

        var url = admin_url + "deps/init_staff_table/" + rel_id + "?v=" + rel_type;

        initDataTable(
            $selector,
            url
        );
    }
</script>
</body>

</html>