<?php
/*
 * Title   : MerchantPlus Navigate Payment extension for Woo-Commerece
 * Author  : merchantplus
 */

?>

<h3>
    <?php _e('Credit Card Payment', 'woocommerce'); ?>
</h3>

<p><?php _e('Allows Credit Card payments.', 'woocommerce'); ?></p>

<table class="form-table">
    <?php $this->generate_settings_html(); ?>
</table>
