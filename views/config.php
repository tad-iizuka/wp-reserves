<div class="wrap">
    <div class="icon32">
    <br />
    </div>
    <h2 class="nav-tab-wrapper">
        <?php foreach ( $tabs as $key => $label ): ?>
            <a href="<?= admin_url( 'admin.php?page=wpr-config&tab=' . $key ) ?>"
               class="nav-tab
                      <?php if ( $tab == $key ): ?>nav-tab-active<?php endif ?>">
                <?= $label ?>
            </a>
        <?php endforeach ?>
    </h2>
    <form method="post" id="mainform" action="" enctype="multipart/form-data">
        <table class="form-table">
            <tbody>				
                <?php echo wpr_render("config-tab-{$tab}.php") ?>
            </tbody>
        </table>
        <input name="save" class="button-primary" type="submit" value="<?= __( 'Save changes' ); ?>" />
        <input name="save" class="button" type="submit" value="<?= __( 'Reset all settings' ); ?>" onclick="return confirm('Are you sure?')"/>
    </form>
</div>
