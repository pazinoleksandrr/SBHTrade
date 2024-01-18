<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<table class="table dt-table table-effectiveness-reports" data-order-col="0" data-order-type="asc">
    <thead>
    <tr>
        <th><?php echo _l('full_name_'); ?></th>
        <th><?php echo _l('role_'); ?></th>
        <th><?php echo _l('department_'); ?></th>
        <th title="<?php echo _l('completed_').' / '._l('in_process_'); ?>"><?php echo _l('total_deposit_'); ?></th>
        <th title="<?php echo _l('completed_').' / '._l('in_process_'); ?>"><?php echo _l('total_withdrawal_'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($effectiveness_report as $e_r) { ?>
        <tr>
            <td>
                <a href="<?php
                echo admin_url("profile/$e_r->staffid"); ?>">
                    <?php
                    echo "$e_r->full_name"; ?>
                </a>
            </td>
            <td><?php echo $e_r->role; ?></td>
            <td><?php echo $e_r->department; ?></td>
            <td><?php echo $e_r->deposit; ?></td>
            <td><?php echo $e_r->withdrawal; ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
