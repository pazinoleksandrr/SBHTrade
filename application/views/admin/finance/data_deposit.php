<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">
            <?php if (isset($deposit)) {
                if (!empty($deposit->account_number)) {
                    $name = $deposit->account_number;
                } else {
                    $name = _l('deposit_');
                }
                echo '#' . $deposit->id . ' - ' . $name;
            } else {
                echo _l('add_new', _l('deposit_lowercase_'));
            }

            ?>
        </h4>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <?php if (isset($deposit)) {
                    echo form_hidden('id', $deposit->id);
                } ?>
                <?php $this->load->view('admin/finance/_deposit'); ?>
            </div>
        </div>
    </div>
<?php hooks()->do_action('finance_modal_profile_bottom', (isset($deposit) ? $deposit->id : '')); ?>