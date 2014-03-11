<?php
/*
Plugin Name: NaviGate Gateway
Plugin URI: 
Description: Provides a Credit Card Payment Gateway through Merchan Plus for woo-commerece.
Version: 1.0
Author: MerchantPlus
Author URI: http://www.merchantplus.com
Tested up to: 3.4.2
*/

/*
 * Title   : MerchantPlus Navigate Payment extension for Woo-Commerece
 * Author  : merchantplus
 */


function init_navigate_gateway() 
{
    if (class_exists('WC_Payment_Gateway'))
    {
        include_once('class.navigateextension.php');
    }
}

add_action('plugins_loaded', 'init_navigate_gateway', 0);