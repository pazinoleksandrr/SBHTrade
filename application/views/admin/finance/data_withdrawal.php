<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">&times;</span></button>
    <h4 class="modal-title">
<?php if (isset($withdrawal)) {
    if (!empty($withdrawal->account_number)) {
        $name = $withdrawal->account_number;
    } else {
        $name = _l('withdrawal_');
    }
    echo '#' . $withdrawal->id . ' - ' . $name;
} else {
    echo _l('add_new', _l('withdrawal_lowercase_'));
}

?>
    </h4>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            <?php if (isset($withdrawal)) {
    echo form_hidden('id', $withdrawal->id);
} ?>
            <?php $this->load->view('admin/finance/_withdrawal'); ?>
        </div>
    </div>
</div>
<?php hooks()->do_action('finance_modal_profile_bottom', (isset($withdrawal) ? $withdrawal->id : '')); ?>