<tr>
    <th>
        <h3><?= __('Reserves manager') ?></h3>
    </th>
</tr>
<?php wpr_messages_box('wpr_message_admin_new', __('New reserve alert')) ?>

<tr>
    <th>
        <h3><?= __('Reserve requester') ?></h3>
    </th>
</tr>

<?php wpr_messages_box('wpr_message_waiting', __('Reserve waiting message')) ?>

<?php wpr_messages_box('wpr_message_authorized', __('Reserve authorization message')) ?>

<?php wpr_messages_box('wpr_message_rejected', __('Reserve rejection message')) ?>
