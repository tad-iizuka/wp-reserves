<?php if($msg_type == 'error'): ?>
    <div class="alert alert-danger"><?= $msg_text ?></div>
<?php elseif($msg_type == 'warning'): ?>
    <div class="alert alert-warning"><?= $msg_text ?></div>
<?php elseif($msg_type == 'success'): ?>
    <div class="alert alert-success"><?= $msg_text ?></div>
<?php endif ?>
