<?php if($msg_type == 'error'): ?>
    <div id="message" class="error notice is-dismissible"><p><?= $msg_text ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button></div>
<?php elseif($msg_type == 'warning'): ?>
    <div id="message" class="error notice is-dismissible"><p><?= $msg_text ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button></div>
<?php elseif($msg_type == 'success'): ?>
    <div id="message" class="updated notice is-dismissible"><p><?= $msg_text ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button></div>
<?php endif ?>