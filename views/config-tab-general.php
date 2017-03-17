<tr>
    <th>
        <label><?= __('Administrator e-mail') ?>:</label>
    </th>
    <td>
        <input type="text" name="wpr_admin_mail" id="wpr_admin_mail" size="20" value="<?= get_option('wpr_admin_mail') ?>">
    </td>
</tr>
<tr>
    <th>
        <label><?= __('Log e-mail messages') ?>:</label>
    </th>
    <td>
        <?php wpr_checkbox('wpr_log_mail') ?>
    </td>
</tr>
<tr>
    <th>
        <label><?= __('Log reserves activity') ?>:</label>
    </th>
    <td>
        <?php wpr_checkbox('wpr_log_reserves') ?>
    </td>
</tr>
<tr>
    <th>
        <label><?= __('Keep logs of last') ?>:</label>
    </th>
    <td>
        <input type="number" name="wpr_log_days" id="wpr_log_days" size="10" value="<?= get_option('wpr_log_days') ?>"> days
    </td>
</tr>
