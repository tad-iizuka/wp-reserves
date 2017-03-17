<?= $table->display(); ?>

<form method="post">
    <input name="btn_clear" class="button" type="submit" value="<?= __( 'Delete all logs' ); ?>" onclick="return confirm('Are you sure?')"/>
<form>
