<div id="content" role="main">
<?php if ($show_my_reserves): ?>
    <a href="<?= add_query_arg(array('my-reserves' => 1)) ?>"><?= __('View my reserves') ?> &rarr;</a>
    <br/>
<?php endif ?>
    
<?php if ($show_filters): ?>
    <ul class="subsubsub">
    <?php foreach ($categories as $cat): ?>
        <li class="<?= $cat->name ?>">
            <a href="<?= add_query_arg(array('_cat' => $cat->name)) ?>"
               class="<?php if ($cat_filter == $cat->name): ?>current<?php endif ?>">
                <?= $cat->name ?> <span class="count">(<?= $cat->count ?>)</span>
            </a>
            <?php if ( $cat !== end($categories) ): ?>|<?php endif ?>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif ?>
    
<?php if ($query->have_posts()): ?>
    <table class="" cellspacing="0">
        <thead>
            <tr>
                <th class="product-thumbnail">&nbsp;</th>
                <th class="product-name"><?= __('Item') ?></th>
                <th class="product-avail"><?= __('Available') ?></th>
                <th class="product-reserve">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($query->have_posts()): $query->the_post(); ?>
            <?php $slots = wpr_free_slots($query->post->ID); ?>
            <tr class="">
                <td class="product-thumbnail">
                    <?php if (has_post_thumbnail()): ?>
                        <a href="<?= the_permalink() ?>"><?php the_post_thumbnail('thumbnail') ?></a>
                    <?php else: ?>
                        <a href="<?= the_permalink() ?>"><img src="<?= plugins_url('assets/images/placeholder.png', __DIR__) ?>" alt="<?php the_title(); ?>" /></a>
                    <?php endif; ?>
                </td>
                <td class="product-name">
                    <a href="<?= the_permalink() ?>"><?= the_title() ?></a>
                </td>
                <td class="product-avail"><?= $slots ?></td>
                <td class="product-reserve">
                    <?php if ( $slots > 0 ): ?>
                        <a href="<?= add_query_arg(array('add-reserve' => $query->post->ID)) ?>" class="btn-reserve"><?= __('Reserve') ?></a>
                    <?php endif ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?= $paginator; ?>
<?php else: ?>
    <br/>
    <h2><?= __('Oops, there are no reserves available.') ?></h2>
    <br/>
<?php endif; ?>
</div>
