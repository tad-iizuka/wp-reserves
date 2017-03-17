<tr>
    <th>
        <label><?= __('Display category filters') ?>:</label>
    </th>
    <td>
        <?php wpr_checkbox('wpr_show_reserves_filters') ?>
    </td>
</tr>
<tr>
    <th>
        <label><?= __('Display "View my reserves" link') ?>:</label>
    </th>
    <td>
        <?php wpr_checkbox('wpr_show_my_reserves') ?>
    </td>
</tr>
<tr>
    <th>
        <label><?= __('Allow user to cancel their reserves') ?>:</label>
    </th>
    <td>
        <?php wpr_checkbox('wpr_allow_user_cancel') ?>
    </td>
</tr>
<tr>
    <th>
        <label><?= __('Items per page') ?>:</label>
    </th>
    <td>
        <input type="number" name="wpr_per_page" id="wpr_per_page" size="10" value="<?= get_option('wpr_per_page') ?>">
    </td>
</tr>
<tr>
    <th>
        <a href="<?= get_permalink( get_page_by_path( 'reserves' ) ) ?>">Visit Reserves page</a>
    </th>
</tr>
