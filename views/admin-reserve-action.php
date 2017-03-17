<?php if ( $status == WPR_STATUS_WAITING ): ?>
    <a href="<?= add_query_arg(array('reserve-chstatus' => $post_id, 'status' => 'authorize')) ?>"
       title="<?= __('Authorize reserve') ?>">
        <img src="<?= plugins_url('assets/images/thumbs_up.png', __DIR__) ?>"></img>
    </a>

    <a href="<?= add_query_arg(array('reserve-chstatus' => $post_id, 'status' => 'reject')) ?>"
       title="<?= __('Reject reserve') ?>">
        <img src="<?= plugins_url('assets/images/thumbs_down.png', __DIR__) ?>"></img>
    </a>
<?php elseif ( $status == WPR_STATUS_AUTHORIZED ): ?>
    <a href="<?= add_query_arg(array('reserve-chstatus' => $post_id, 'status' => 'finish')) ?>"
       title="<?= __('Conclude reserve') ?>">
        <img src="<?= plugins_url('assets/images/finish.png', __DIR__) ?>"></img>
    </a>
<?php else: ?>
    —
<?php endif ?>
