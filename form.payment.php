<?php
/*
 * Title   : MerchantPlus Navigate Payment extension for Woo-Commerece
 * Author  : merchantplus
 */

?>

<p class="form-row">
    <label>Card Number <span class="required">*</span></label>
    
    <input class="input-text" type="text" size="19" maxlength="19" name="billing_creditcard" />
</p>
<p class="form-row form-row-first">
    <label>Card Type <span class="required">*</span></label>
    <select name="billing_cardtype" >
        <?php foreach($this->acceptableCards as $type) : ?>
            <option value="<?php echo $type ?>"><?php _e($type, 'woocommerce'); ?></option>
        <?php endforeach; ?>
    </select>
</p>
<div class="clear"></div>
<p class="form-row form-row-first">
    <label>Expiration Month <span class="required">*</span></label>
    <select name="billing_expdatemonth">
        <option value=01>01</option>
        <option value=02>02</option>
        <option value=03>03</option>
        <option value=04>04</option>
        <option value=05>05</option>
        <option value=06>06</option>
        <option value=07>07</option>
        <option value=08>08</option>
        <option value=09>09</option>
        <option value=10>10</option>
        <option value=11>11</option>
        <option value=12>12</option>
    </select>
</p>
<p class="form-row form-row-last">
    <label>Expiration Year  <span class="required">*</span></label>
    <select name="billing_expdateyear">
<?php
    $today = (int)date('Y', time());
    for($i = 0; $i < 8; $i++){
?>
        <option value="<?php echo $today; ?>"><?php echo $today; ?></option>
<?php
        $today++;
    }
?>
    </select>
</p>
<div class="clear"></div>
<p class="form-row form-row-first">
    <label>Card Verification Number <span class="required">*</span></label>
    <input class="input-text" type="text" maxlength="4" name="billing_ccvnumber" value="" />
</p>
<div class="clear"></div>
