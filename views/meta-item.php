<input type="hidden" name="nonce_item" id="nonce_item" value="<?= wp_create_nonce( plugin_basename('wpreserves') ) ?>" />
<style>
    .wprlabel {  }
    .wprfield {  }
</style>
<label for="_quantity" class="wprlabel"><?= __('Quantity') ?>: </label>
<input type="number" name="_quantity" class="wprfield" value="<?= $quantity ?>" size="10" required/>
<!--<br/>
<label for="_type" class="wprlabel"><?= __('Type') ?>: </label>
<select name="_type" class="wprfield">
    <?php foreach ( wpr_item_types() as $key => $val ): ?>
    <option value="<?= $key ?>" <?= selected( $type, $key ); ?>><?= $val ?></option>
    <?php endforeach; ?>
</select>-->
