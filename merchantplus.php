<?php
	/*
		Plugin Name: MerchantPlus Payment Gateway For WooCommerce
		Description: Extends WooCommerce to Process Payments with MerchantPlus gateway
		Version: 1.0
		Plugin URI: http://merchantplus.com
		Author: MerchantPlus
		Author URI: http://merchantplus.com
		License: Under GPL2   
		
	*/
	
	add_action('plugins_loaded', 'woocommerce_merchantplus_init', 0);
	
	function woocommerce_merchantplus_init() {
		
		if ( !class_exists( 'WC_Payment_Gateway' ) ) 
		return;
		
		/**
			* Localisation
		*/
		load_plugin_textdomain('merchantplus', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
		
		class MerchantpPlus extends WC_Payment_Gateway 
		{
			protected $msg = array();
			
			public function __construct(){
				
				$this->id               = 'merchantplus';
				$this->method_title     = __('MerchantPlus Gateway', 'merchantplus');
				$this->icon             = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.gif';
				$this->has_fields       = true;
				$this->init_form_fields();
				$this->init_settings();
				$this->title            = $this->settings['title'];
				$this->description      = $this->settings['description'];
				$this->login            = $this->settings['login_id'];
				$this->mode             = $this->settings['working_mode'];
				$this->transaction_key  = $this->settings['transaction_key'];
				$this->method			= $this->settings['transaction_method'];
				$this->success_message  = $this->settings['success_message'];
				$this->failed_message   = $this->settings['failed_message'];
				$this->gateway_url      = 'https://gateway.merchantplus.com/cgi-bin/PAWebClient.cgi';         
				$this->msg['message']   = "";
				$this->msg['class']     = "";
				
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
					} else {
					add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
				}
				
				add_action('woocommerce_receipt_merchantplus', array(&$this, 'receipt_page'));
				add_action('woocommerce_thankyou_merchantplus',array(&$this, 'thankyou_page'));
			}
			
			function init_form_fields()
			{
				
				$this->form_fields = array(
					'enabled'           => array(
						'title'         	=> __('Enable/Disable', 'merchantplus'),
						'type'          	=> 'checkbox',
						'label'         	=> __('Enable MerchantPlus Payment Module.', 'merchantplus'),
						'default'       	=> 'no'),
					'title'             => array(
						'title'         	=> __('Title:', 'merchantplus'),
						'type'         		=> 'text',
						'description'   	=> __('This controls the title which the user sees during checkout.', 'merchantplus'),
						'default'       	=> __('MerchantPlus', 'merchantplus')),
					'description'       => array(
						'title'         	=> __('Description:', 'merchantplus'),
						'type'          	=> 'textarea',
						'description'   	=> __('This controls the description which the user sees during checkout.', 'merchantplus'),
						'default'      		=> __('Pay securely by Credit or Debit Card through MerchantPlus Payment Gateway.', 'merchantplus')),
					'login_id'          => array(
						'title'         	=> __('Login ID', 'merchantplus'),
						'type'          	=> 'text',
						'description'   	=> __('This is API Login ID')),
					'transaction_key'   => array(
						'title'         	=> __('Transaction Key', 'merchantplus'),
						'type'          	=> 'text',
						'description'   	=> __('API Transaction Key', 'merchantplus')),
					'transaction_method'=> array(
						'title'         	=> __('Transaction Method', 'merchantplus'),
						'type'          	=> 'select',
						'options'       	=> array('AUTH_CAPTURE'=>'CAPTURE', 'AUTH_ONLY'=>'AUTHORIZATION'),
						'description'   	=> __('', 'merchantplus')),
					'success_message'   => array(
						'title'         	=> __('Transaction Success Message', 'merchantplus'),
						'type'          	=> 'textarea',
						'description'   	=> __('Message to be displayed on successful transaction.', 'merchantplus'),
						'default'       	=> __('Your payment has been procssed successfully.', 'merchantplus')),
					'failed_message'    => array(
						'title'         	=> __('Transaction Failed Message', 'merchantplus'),
						'type'          	=> 'textarea',
						'description'   	=> __('Message to be displayed on failed transaction.', 'merchantplus'),
						'default'       	=> __('Your transaction has been declined.', 'merchantplus')),
					'working_mode'      => array(
						'title'         	=> __('API Mode'),
						'type'         	 	=> 'select',
						'options'       	=> array('false' =>'Live Mode', 'true' =>'Test Mode'),
						'description'   	=> "Live/Test Mode" )
				);
			}
			
			/**
				* Admin Panel Options
				* 
			**/
			public function admin_options()
			{
				echo '<h3>'.__('MerchantPlus Payment Gateway', 'merchantplus').'</h3>';
				echo '<p>'.__('MerchantPlus is most popular payment gateway for online payment processing').'</p>';
				echo '<table class="form-table">';
				$this->generate_settings_html();
				echo '</table>';
				
			}
			
			/**
				*  Fields for MerchantPlus
			**/
			function payment_fields()
			{
				if ( $this->description ) 
				echo wpautop(wptexturize($this->description));
				
				?>
				<p class="form-row form-row-first">
					<label for="credit-card" class="">Card Number</label>
					<input type="text" class="input-text input-required" name="x_card_num" id="x_card_num">
				</p>
				<p class="form-row form-row-last">
					<label for="credit-card" class="">Card Security Code</label>
					<input type="text" class="input-text input-required" name="x_card_code" id="x_card_num">
				</p>
				<p class="form-row form-row-first">
					<label for="credit-card" class="">Card Expiry Date </label>
					<select name="x_exp_m">
						<?php
							for ($i = 1; $i <= 12; $i++) {
								echo '<option value="'.sprintf('%02d', $i).'">'.strftime('%B', mktime(0, 0, 0, $i, 1, 2000)).'</option>';					
							}
						?>
					</select>
					<select name="x_exp_y">
						<?php					
							$today = getdate();
							for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
								echo '<option value="'.strftime('%y', mktime(0, 0, 0, 1, 1, $i)).'">'.strftime('%Y', mktime(0, 0, 0, 1, 1, $i)).'</option>';
							}
						?>
					</select>
				</p>
				<div style="clear:both"></div>
				<?php				
			}
			
			/*
				* Basic Card validation
			*/
			public function validate_fields()
			{
				global $woocommerce;
				
				if (!$this->isCreditCardNumber($_POST['x_card_num'])) 
				wc_add_notice(__('(Credit Card Number) is not valid.', 'merchantplus')); 
				
				
				if (!$this->isCorrectExpireDate($_POST['x_exp_m'].$_POST['x_exp_y']))    
				wc_add_notice(__('(Card Expiry Date) is not valid.', 'merchantplus')); 
				
				if (!$this->isCCVNumber($_POST['x_card_code'])) 
				wc_add_notice(__('(Card Verification Number) is not valid.', 'merchantplus')); 
			}
			
			/*
				* Check card 
			*/
			private function isCreditCardNumber($toCheck) 
			{
				if (!is_numeric($toCheck))
				return false;
				
				$number = preg_replace('/[^0-9]+/', '', $toCheck);
				$strlen = strlen($number);
				$sum    = 0;
				
				if ($strlen < 13)
				return false; 
				
				for ($i=0; $i < $strlen; $i++)
				{
					$digit = substr($number, $strlen - $i - 1, 1);
					if($i % 2 == 1)
					{
						$sub_total = $digit * 2;
						if($sub_total > 9)
						{
							$sub_total = 1 + ($sub_total - 10);
						}
					} 
					else 
					{
						$sub_total = $digit;
					}
					$sum += $sub_total;
				}
				
				if ($sum > 0 AND $sum % 10 == 0)
				return true; 
				
				return false;
			}
			
			private function isCCVNumber($toCheck) 
			{
				$length = strlen($toCheck);
				return is_numeric($toCheck) AND $length > 2 AND $length < 5;
			}
			
			/*
				* Check expiry date
			*/
			private function isCorrectExpireDate($date) 
			{
				
				if (is_numeric($date) && (strlen($date) == 4)){
					return true;
				}
				return false;
			}
			
			public function thankyou_page($order_id) 
			{
				
			}
			
			/**
				* Receipt Page
			**/
			function receipt_page($order)
			{
				echo '<p>'.__('Thank you for your order.', 'merchantplus').'</p>';				
			}
			
			/**
				* Process the payment and return the result
			**/
			function process_payment($order_id)
			{
				global $woocommerce;
				$order = new WC_Order($order_id);       
				
				$params = $this->generate_merchantplus_params($order);

				$response = wp_remote_post($this->gateway_url, array( 
																'body' => http_build_query($params, '', '&'),
																'method' => 'POST',
																'headers' => array( 
																'Content-Type' => 'application/x-www-form-urlencoded' ),
																'sslverify' => FALSE
																)
				);
				
				if (!is_wp_error($response) && $response['response']['code']>=200 && $response['response']['code']<300){										
					$data = $response['body'];
					$delim = $data{1};					
					$data = explode($delim, $data);	
					
					if($data[0] == 1){
						$order->payment_complete();
						$woocommerce->cart->empty_cart();
						
						$order->add_order_note($this->success_message. $data[3] . 'Transaction ID: '. $data[6] );
						unset($_SESSION['order_awaiting_payment']);
						
						return array(
								'result' 	=> 'success',
								'redirect'	=> get_site_url().'/checkout/order-received/'.$order->id.'/?key='.$order->order_key
						);
					}else{
						$error = $this->error_status();								
						$order->add_order_note($this->failed_message .$error[$data[0]][$data[2]] );
						wc_add_notice(__('(Transaction Error) '. $error[$data[0]][$data[2]]." ".$data[3], 'merchantplus'));
					}
					
				}else{					
					$order->add_order_note($this->failed_message);
					$order->update_status('failed');					
					// wc_add_notice(__('(Transaction Error) Error processing payment.', 'merchantplus')); 
					wc_add_notice(__('(Transaction Error) Error processing payment.', 'error')); 
				}				
			}
			
			public function generate_merchantplus_params($order)
			{
				if($this->mode == 'true'){
					$test = 'TRUE';
				}else{
					$test = 'FALSE';
				}
				// Merchant Info
				$mp_arg['x_login']             = $this->login;
				$mp_arg['x_tran_key']          = $this->transaction_key;
				
				// TEST TRANSACTION
				$mp_arg['x_test_request']      = $test;
				
				// AIM Head
				$mp_arg['x_version']           = '3.1';
				
				// TRUE Means that the Response is going to be delimited
				$mp_arg['x_delim_data']        = 'TRUE';
				$mp_arg['x_delim_char']        = '|';
				$mp_arg['x_relay_response']    = 'FALSE';
				
				// Transaction Info
				$mp_arg['x_method']            = 'CC';
				$mp_arg['x_type']              = $this->method;
				$mp_arg['x_amount']            = $order->order_total;
				
				// Test Card				
				$mp_arg['x_card_num']          = $_POST['x_card_num'];
				$mp_arg['x_exp_date']          = $_POST['x_exp_m'].$_POST['x_exp_y'];
				$mp_arg['x_card_code']         = $_POST['x_card_code'];
				$mp_arg['x_trans_id']          = $order->id;
				
				// Order Info
				$mp_arg['x_invoice_num']       = $order->order_key;
				$mp_arg['x_description']       = '';
				
				// Customer Info
				$mp_arg['x_first_name']        = $order->billing_first_name;
				$mp_arg['x_last_name']         = $order->billing_last_name;
				$mp_arg['x_company']           = $order->billing_company;
				$mp_arg['x_address']           = $order->billing_address_1.' '.$order->billing_address_2;
				$mp_arg['x_city']              = $order->billing_city;
				$mp_arg['x_state']             = $order->billing_state;
				$mp_arg['x_zip']               = $order->billing_postcode;
				$mp_arg['x_country']           = $order->billing_country;
				$mp_arg['x_phone']             = $order->billing_phone;
				$mp_arg['x_fax']               = '';
				$mp_arg['x_email']             = $order->billing_email;
				$mp_arg['x_cust_id']           = $order->user_id;
				$mp_arg['x_customer_ip']       = '';
				
				// shipping info
				$mp_arg['x_ship_to_first_name']= $order->shipping_first_name;
				$mp_arg['x_ship_to_last_name'] = $order->shipping_last_name;
				$mp_arg['x_ship_to_company']   = $order->shipping_company;
				$mp_arg['x_ship_to_address']   = $order->shipping_address_1.' '.$order->shipping_address_2;
				$mp_arg['x_ship_to_city']      = $order->shipping_city;
				$mp_arg['x_ship_to_state']     = $order->shipping_state;
				$mp_arg['x_ship_to_zip']       = $order->shipping_postcode;
				$mp_arg['x_ship_to_country']   = $order->shipping_country;
				
				return $mp_arg;
			}
			
			function error_status() {
				$error[2][2] 	= 'This transaction has been declined.';
				$error[3][6] 	= 'The credit card number is invalid.';
				$error[3][7] 	= 'The credit card expiration date is invalid.';
				$error[3][8] 	= 'The credit card expiration date is invalid.';
				$error[3][13] 	= 'The merchant Login ID or Password or TransactionKey is invalid or the account is inactive.';
				$error[3][15] 	= 'The transaction ID is invalid.';
				$error[3][16] 	= 'The transaction was not found';
				$error[3][17] 	= 'The merchant does not accept this type of credit card.';
				$error[3][19] 	= 'An error occurred during processing. Please try again in 5 minutes.';
				$error[3][33] 	= 'A required field is missing.';
				$error[3][42] 	= 'There is missing or invalid information in a parameter field.';
				$error[3][47] 	= 'The amount requested for settlement may not be greater than the original amount authorized.';
				$error[3][49] 	= 'A transaction amount equal or greater than $100000 will not be accepted.';
				$error[3][50] 	= 'This transaction is awaiting settlement and cannot be refunded.';
				$error[3][51] 	= 'The sum of all credits against this transaction is greater than the original transaction amount.';
				$error[3][57] 	= 'A transaction amount less than $1 will not be accepted.';
				$error[3][64] 	= 'The referenced transaction was not approved.';
				$error[3][69] 	= 'The transaction type is invalid.';
				$error[3][70] 	= 'The transaction method is invalid.';
				$error[3][72] 	= 'The authorization code is invalid.';
				$error[3][73] 	= 'The driver\'s license date of birth is invalid.';
				$error[3][84] 	= 'The referenced transaction was already voided.';
				$error[3][85] 	= 'The referenced transaction has already been settled and cannot be voided.';
				$error[3][86] 	= 'Your settlements will occur in less than 5 minutes. It is too late to void any existing transactions.';
				$error[3][87] 	= 'The transaction submitted for settlement was not originally an AUTH_ONLY.';
				$error[3][88] 	= 'Your account does not have access to perform that action.';
				$error[3][89] 	= 'The referenced transaction was already refunded.';
				$error[3][90] 	= 'Data Base Error.';
				
				return $error;
			}
			
		}
		
		/**
			* Add this Gateway to WooCommerce
		**/
		function woocommerce_add_merchantplus_gateway($methods) 
		{
			$methods[] = 'MerchantpPlus';
			return $methods;
		}
		
		add_filter('woocommerce_payment_gateways', 'woocommerce_add_merchantplus_gateway' );
	}
