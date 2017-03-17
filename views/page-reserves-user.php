<div id="content" role="main">
<?php if ($query->have_posts()): ?>
    <h3>Reserves of <?= wp_get_current_user()->display_name ?></h3>
    <a href="<?= remove_query_arg('my-reserves') ?>">&larr; <?= __('Back to reserves page') ?></a>
    <br/>
    <table class="" cellspacing="0">
        <thead>
            <tr>
                <th class="product-name"><?= __('Item') ?></th>
                <th class="product-date"><?= __('Date') ?></th>
                <th class="product-status"><?= __('Status') ?></th>
                <th class="product-actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php while ($query->have_posts()): $query->the_post(); ?>
            <tr class="">
                <td class="product-name">
                    <a href="<?= wpr_reserve_link($query->post->ID) ?>"><?= the_title() ?></a>
                </td>
                <td class="product-date"><?= get_the_date() ?></td>
                <td class="product-status"><?= wpr_status_get($query->post->post_status) ?></td>
                <td class="product-actions">
                    <?php if ( $query->post->post_status == WPR_STATUS_WAITING ): ?>
                        <?php if ( $allow_cancel ): ?>
                            <a href="<?= add_query_arg(array('cancel-reserve' => $query->post->ID)) ?>" class="">
                                <img src="<?= plugins_url('assets/images/cancel.png', __DIR__) ?>" title="<?= __('Cancel reserve') ?>" />
                            </a>
                        <?php endif ?>
                    <?php else: ?>
                    <?php endif ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <br/>
    <h2><?= __('Sorry, but you don\'t have reserves yet.') ?></h2>
    <a href="<?= remove_query_arg('my-reserves') ?>">&larr; <?= __('Back') ?></a>
    <br/>
<?php endif; ?>
</div>
