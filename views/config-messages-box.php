<tr>
    <th>
        <label><?= $label ?>:</label>
    </th>
    <td>
        <input type="checkbox" name="messages[<?= $name ?>][enabled]" id="<?= $name ?>"
            <?php if ($opt['enabled'] == 'yes'): ?>checked<?php endif ?>
            >
        <?= __('enable') ?>
        <br/>
        <input type="text" size="30" placeholder="title" name="messages[<?= $name ?>][title]" id="<?= $name ?>_title" value="<?= $opt['title'] ?>">
        <br/>
        <textarea name="messages[<?= $name ?>][message]" id="<?= $name ?>_message" rows="3" cols="50" placeholder="message"><?= $opt['message'] ?></textarea>
    </td>
    <td>
        <h3>Available tags:</h3>
        <b>[item]</b>: The name of the item<br/>
        <?php if ( $name == 'wpr_message_admin_new'): ?><b>[manage_link]</b>: Link to manage the reserves<br/><?php endif ?>
    </td>
</tr>
<script type="text/javascript">
jQuery(document).ready(function($){
    $('#<?= $name ?>').click(function(){
       $("#<?= $name ?>_title").prop('readonly', !$(this).is(':checked'));
       $("#<?= $name ?>_message").prop('readonly', !$(this).is(':checked'));
    }).click().click();
});
</script>
