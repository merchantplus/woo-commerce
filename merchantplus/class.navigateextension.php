
<?php
/*
 * Title   : MerchantPlus NaviGate Payment Gateway extension for Woo-Commerece
 * Author  : merchantplus
 */

class dinkum_MerchantPlus extends WC_Payment_Gateway 
{
    protected $GATEWAY_NAME                 = "MerchantPlus NaviGate";
    protected $NAVIGATE_URL_LIVE            = "https://gateway.merchantplus.com/cgi-bin/PAWebClient.cgi";
    protected $NAVIGATE_API_VERSION         = "3.1";
    protected $NAVIGATE_TRX_TYPE            = "AUTH_CAPTURE";
    protected $NAVIGATE_TRX_METHOD          = "CC";
    protected $NAVIGATE_RELAY_RESPONSE      = "FALSE";
    protected $NAVIGATE_DELIMITED_DATA      = "TRUE";
    protected $NAVIGATE_DELIMITED_CHAR      = ",";
    protected $NAVIGATE_SUCCESS_ACK         = 1;   
    protected $NAVIGATE_RESPONSE_TRX_ID_INX = 6;   
    protected $NAVIGATE_RESPONSE_AUTH_INX   = 4;   
    protected $NAVIGATE_RESPONSE_ACK_INX    = 0;   
    protected $NAVIGATE_RESPONSE_REASON_INX = 3;   

    protected $naviGateApiLoginId     = '';
    protected $naviGateTransactionKey = '';

    protected $instructions               = '';
    protected $order                      = null;
    protected $acceptableCards            = null;
    protected $transactionId              = null;
    protected $authorizationCode          = null;
    protected $transactionErrorMessage    = null;
    protected $usesandboxapi              = true;
    
    public function __construct() 
    { 
        $this->id              = 'NaviGate';
        $this->has_fields      = true;
        
        $this->init_form_fields();
        $this->init_settings();
        
        $this->title                      = $this->settings['title'];
        $this->description                = '';
        $this->icon                       = WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/credits.png';
        $this->usesandboxapi              = strcmp($this->settings['debug'], 'yes') == 0;
        $this->naviGateApiLoginId     = $this->settings['navigateloginid'       ];
        $this->naviGateTransactionKey = $this->settings['naviGateTransactionKey'];
        $this->instructions               = $this->settings['instructions'              ];
        $this->acceptableCards            = $this->settings['cardtypes'                 ];
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('admin_notices'                                           , array($this, 'perform_ssl_check'    ));
        add_action('woocommerce_thankyou'                                    , array($this, 'thankyou_page'        ));
    } 

    function perform_ssl_check() {
         
         if (!$this->usesandboxapi && get_option('woocommerce_force_ssl_checkout') == 'no' && $this->enabled == 'yes') :
            echo '<div class="error"><p>'.sprintf(__('Navigate sandbox testing is disabled and can perform live transactions but the <a href="%s">Force secure checkout</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'woocommerce'), admin_url('admin.php?page=woocommerce_settings&tab=general')).'</p></div>';
         endif;
    }
    
    public function init_form_fields() 
    {
        $this->form_fields = array(
            'enabled' => array(
                'type'        => 'checkbox', 
                'title'       => __('Enable/Disable', 'woocommerce'), 
                'label'       => __('Enable Credit Card Payments', 'woocommerce'), 
                'default'     => 'yes'
            ), 
            'debug' => array(
                'type'        => 'checkbox', 
                'title'       => __('NaviGate testing', 'woocommerce'), 
                'label'       => __('Enable NaviGate Sandbox', 'woocommerce'), 
                'default'     => 'true'
            ), 
            'title' => array(
                'type'        => 'text', 
                'title'       => __('Title', 'woocommerce'), 
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'), 
                'default'     => __('Credit Card Payment', 'woocommerce')
            ),
            'instructions' => array(
                'type'        => 'textarea', 
                'title'       => __('Customer Message', 'woocommerce'), 
                'description' => __('This message is displayed on the buttom of the Order Recieved Page.', 'woocommerce'), 
                'default'     => ''
            ),
            'navigateloginid' => array(
                'type'        => 'text', 
                'title'       => __('NaviGate API Login ID', 'woocommerce'), 
                'default'     => __('', 'woocommerce')
            ),
            'naviGateTransactionKey' => array(
                'type'        => 'text', 
                'title'       => __('NaviGate Transaction Key', 'woocommerce'), 
                'default'     => __('', 'woocommerce')
            ),
            'cardtypes' => array(
                'title'       => __( 'Accepted Cards', 'woocommerce' ), 
                'type'        => 'multiselect', 
                'description' => __( 'Select which card types to accept.', 'woocommerce' ), 
                'default'     => '',
                'options'     => array(
                    'Visa'             => 'Visa',
                    'MasterCard'       => 'MasterCard', 
                    'Discover'         => 'Discover',
                    'American Express' => 'American Express'
                )
            )       
       );
    }
    
    public function admin_options() 
    {
        include_once('form.admin.php');
    }

    public function payment_fields() 
    {
        include_once('form.payment.php');
    }
    
    public function thankyou_page($order_id) 
    {
        if ($this->instructions) 
            echo wpautop(wptexturize($this->instructions));
    }
    
    public function validate_fields()
    {
        global $woocommerce;

        if (!$this->isCreditCardNumber($_POST['billing_credircard'])) 
            $woocommerce->add_error(__('(Credit Card Number) is not valid.', 'woocommerce')); 
        
        if (!$this->isCorrectCardType($_POST['billing_cardtype']))    
            $woocommerce->add_error(__('(Card Type) is not valid.', 'woocommerce')); 

        if (!$this->isCorrectExpireDate($_POST['billing_expdatemonth'], $_POST['billing_expdateyear']))    
            $woocommerce->add_error(__('(Card Expire Date) is not valid.', 'woocommerce')); 

        if (!$this->isCCVNumber($_POST['billing_ccvnumber'])) 
            $woocommerce->add_error(__('(Card Verification Number) is not valid.', 'woocommerce')); 
    }
    
    public function process_payment($order_id) 
    {
        global $woocommerce;
        $this->order        = &new WC_Order($order_id);
        $gatewayRequestData = $this->getNavigateRequestData();

        if ($gatewayRequestData AND $this->geNavigateApproval($gatewayRequestData))
        {
            $this->completeOrder();

            return array(
            'result'    => 'success',
            'redirect'  => $this->get_return_url($this->order )
            );
        }
        else
        {
            $this->markAsFailedPayment();
            $woocommerce->add_error(__('(Transaction Error) something is wrong.', 'woocommerce')); 
        }
    }

    protected function markAsFailedPayment()
    {
        $this->order->add_order_note(
            sprintf(
                "%s Credit Card Payment Failed with message: '%s'", 
                $this->GATEWAY_NAME, 
                $this->transactionErrorMessage
            )
        );
    }

    protected function completeOrder()
    {
        global $woocommerce;

        if ($this->order->status == 'completed') 
            return;
        
        $this->order->payment_complete();
        $woocommerce->cart->empty_cart();

        $this->order->add_order_note(
            sprintf(
                "%s payment completed with Transaction Id of '%s' and Authorization Id '%s'", 
                $this->GATEWAY_NAME, 
                $this->transactionId, 
                $this->authorizationCode
            )
        );    
        
        unset($_SESSION['order_awaiting_payment']);
    }

    protected function geNavigateApproval($gatewayRequestData)
    {
        global $woocommerce;

        $erroMessage = "";
        $api_url     = $this->NAVIGATE_URL_LIVE;
        $request     = array(
            'method'    => 'POST',
            'timeout'   => 45,
            'blocking'  => true,
            'sslverify' => false,
            'body'      => $gatewayRequestData
        );
        
        $response = wp_remote_post($api_url, $request);  
        
        if (!is_wp_error($response))
        {
            $parsedResponse = $this->parseNavigateResponse($response);

            if ($this->NAVIGATE_SUCCESS_ACK === (int)$parsedResponse[$this->NAVIGATE_RESPONSE_ACK_INX])
            {
                $this->transactionId     = $parsedResponse[$this->NAVIGATE_RESPONSE_TRX_ID_INX];
                $this->authorizationCode = $parsedResponse[$this->NAVIGATE_RESPONSE_AUTH_INX  ];
                return true;
            }
            else
            {
                $this->transactionErrorMessage = $erroMessage = $parsedResponse[$this->NAVIGATE_RESPONSE_REASON_INX];
            }
        }
        else
        {
            // Uncomment to view the http error
            $this->transactionErrorMessage = $erroMessage = print_r($response->errors, true); //'Something went wrong while performing your request. Please contact website administrator to report this problem.'; 
        }
 
        $woocommerce->add_error($erroMessage); 
        return false;
    }
    
    protected function parseNavigateResponse($response)
    {
        return explode($this->NAVIGATE_DELIMITED_CHAR, $response['body']);
    }
    
    protected function getNavigateRequestData()
    {
        if ($this->order AND $this->order != null)
        {
            return array(
                "x_login"          => $this->naviGateApiLoginId,
                "x_tran_key"       => $this->naviGateTransactionKey,
                "x_method"         => $this->NAVIGATE_TRX_METHOD,
                "x_type"           => $this->NAVIGATE_TRX_TYPE,
                "x_amount"         => $this->order->get_total(),
                "x_card_num"       => $_POST['billing_credircard'],
                "x_exp_date"       => sprintf('%s%s'  , $_POST['billing_expdatemonth'], $_POST['billing_expdateyear']),
                "x_card_code"      => $_POST['billing_ccvnumber' ],
                "x_trans_id"       => $this->transactionId,
                "x_test_request"   => $this->usesandboxapi,
                "x_invoice_num"    => $this->order->id,
                "x_first_name"     => $this->order->billing_first_name,
                "x_last_name"      => $this->order->billing_last_name,
                "x_company"        => $this->order->billing_company,
                "x_address"        => sprintf('%s, %s', $_POST['billing_address_1'   ], $_POST['billing_address_2'  ]),
                "x_city"           => $this->order->billing_city,
                "x_state"          => $this->order->billing_state,
                "x_zip"            => $this->order->billing_postcode,
                "x_country"        => $this->order->billing_country,
                "x_phone"          => $this->order->billing_phone,
                "x_fax"            => $this->order->billing_fax,
                "x_email"          => $this->order->billing_email,
                "x_cust_id"        => $this->order->user_id,
                "x_customer_ip"    => $_SERVER['REMOTE_ADDR'],
                "x_ship_to_first_name" => $this->order->shipping_first_name,
                "x_ship_to_last_name" => $this->order->shipping_last_name,
                "x_ship_to_company" => $this->order->shipping_company,
                "x_ship_to_address" => $this->order->shipping_address,
                "x_ship_to_city" => $this->order->shipping_city,
                "x_ship_to_state" => $this->order->shipping_state,
                "x_ship_to_zip" => $this->order->shipping_zip,
                "x_ship_to_country" => $this->order->shipping_country,
                "x_version"        => $this->NAVIGATE_API_VERSION,
                "x_delim_data"     => $this->NAVIGATE_DELIMITED_DATA,
                "x_delim_char"     => $this->NAVIGATE_DELIMITED_CHAR,
                "x_relay_response" => $this->NAVIGATE_RELAY_RESPONSE,
                "x_card_typ"       => $_POST['billing_cardtype'  ]
            );
        }
        
        return false;
    }
    
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

    private function isCorrectCardType($toCheck)
    {
        return $toCheck AND in_array($toCheck, $this->acceptableCards);
    }    

    private function isCorrectExpireDate($month, $year) 
    {
        $now        = time();
        $thisYear   = (int)date('Y', $now);
        $thisMonth  = (int)date('m', $now);
        
        if (is_numeric($year) && is_numeric($month))
        {
            $thisDate   = mktime(0, 0, 0, $thisMonth, 1, $thisYear); 
            $expireDate = mktime(0, 0, 0, $month    , 1, $year    ); 
            
            return $thisDate <= $expireDate;
        }
        
        return false;
    }
}

function add_Navigate_gateway($methods) 
{
    array_push($methods, 'dinkum_MerchantPlus'); 
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_Navigate_gateway');


