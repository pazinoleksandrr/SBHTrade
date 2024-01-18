<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php echo form_open(
            (admin_url('voip')),
            ['id' => 'voip-form', 'class' => '']
        );
        ?>
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-mt-0 tw-text-neutral-800">
                    <?php echo _l('voip_settings'); ?>
                </h4>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo render_input('settings[commpeak_crm_id]','voip_settings_commpeak_crm_id',get_option('commpeak_crm_id')); ?>
                                <hr />
                                <?php echo render_input('settings[commpeak_client_id]','voip_settings_commpeak_client_id',get_option('commpeak_client_id')); ?>
                                <hr />
                                <?php echo render_input('','voip_settings_commpeak_api_key', get_option('api_key'),$type = 'text',['disabled'=>'disabled']); ?>
                                <hr />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="btn-bottom-toolbar text-right">
                    <button type="submit" class="btn btn-primary">
                        <?php echo _l('save'); ?>
                    </button>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <?php echo form_close(); ?>
        <div class="btn-bottom-pusher"></div>
    </div>
</div>
<div id="new_version"></div>
<?php init_tail(); ?>
<?php hooks()->do_action('settings_group_end', 'voip'); ?>
</body>

</html>