<?php
class WC_Voguepay_Woocommerce extends WC_Payment_Gateway
{
	public function __construct()
	{
		$this -> id = 'voguepay';
		$this -> icon = VPG_URL . 'images/buynow_blue.png';
		$this->has_fields  = false;
		$this->liveurl 		= 'https://voguepay.com/pay/';
		$this->method_title     = __( 'Voguepay' , 'vpay' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();


		$this->title = $this->settings['title'];
		$this->description = $this->settings['description'];
		$this->merchant_id = $this->settings['merchant_id'];
		$this->memo = $this -> settings['memo'];
		$this->success_page_id = $this -> settings['success_page_id'];
		$this->failure_page_id = $this -> settings['failure_page_id'];

		add_action('init', array($this, 'check_voguepay_response'));
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
		} else {
			add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
		}
		add_action('woocommerce_receipt_voguepay', array(&$this, 'receipt_page'));

	}



	function init_form_fields(){
		$blog_title= get_bloginfo('name');

		$this -> form_fields = array(
				'enabled' => array(
						'title' => __('Enable/Disable', 'vpay'),
						'type' => 'checkbox',
						'label' => __('Enable Voguepay Payment Module.', 'vpay'),
						'default' => 'yes'),
				'title' => array(
						'title' => __('Title:', 'vpay'),
						'type'=> 'text',
						'description' => __('This controls the title which the user sees during checkout.', 'vpay'),
						'default' => __('Voguepay', 'vpay')),
				'description' => array(
						'title' => __('Description:', 'vpay'),
						'type' => 'textarea',
						'description' => __('This controls the description which the user sees during checkout.', 'vpay'),
						'default' => __('Pay securely using your Naira ATM card', 'vpay')),
				'memo' => array(
						'title' => __('Memo:', 'vpay'),
						'type' => 'text',
						'description' => __('This is the memo that will be saved on your voguepay account', 'vpay'),
						'default' => __('New order from ' .$blog_title, 'vpay')),
				'success_page_id' => array(
						'title' => __('Success Page'),
						'type' => 'select',
						'options' => $this -> get_pages('Select Page'),
						'description' => "When a payment is sucessfull,define a URL to redirect users to."
				),
				'failure_page_id' => array(
						'title' => __('Failure Page'),
						'type' => 'select',
						'options' => $this -> get_pages('Select Page'),
						'description' => "Incase of a failure in payment,define a URL to redirect users to."
				),
				'merchant_id' => array(
						'title' => __('Merchant ID', 'vpay'),
						'type' => 'text',
						'description' => __('This is your Merchant ID obtainable from Voguepay', 'vpay')
				)
		);
	}

	function is_valid_for_use() {
		$return = true;

		if (!in_array(get_option('woocommerce_currency'), array('NGN'))) {
			$return = false;
		}

		return $return;
	}
	
	public function admin_options(){
		echo '<h3>'.__('Voguepay Payment Gateway', 'vpay').'</h3>';
		echo '<p>'.__('Voguepay is one of the most reliable payment gateways in Nigeria').'</p>';
		echo '<p>'.__('If you don\'t have a Voguepay account,kindly sign up <a href="https://voguepay.com" target="_blank">here</a>').'</p>';
		echo '<table class="form-table">';
		// Generate the HTML For the settings form.
		$this -> generate_settings_html();
		echo '</table>';

	}

	function payment_fields(){
		if($this -> description) echo wpautop(wptexturize($this -> description));
	}
	function generate_voguepay_form( $order_id ) 
	{
		global $woocommerce;

		$order = new WC_Order( $order_id );
		$success_url = ($this -> success_page_id=="" || $this->success_page_id == 0) ? home_url('index.php') : get_permalink($this->success_page_id);
		$fail_url = ($this->failure_page_id == "" || $this->failure_page_id == 0) ? home_url('index.php') : get_permalink($this->failure_page_id);
			
		$vpay_args = array(
					'v_merchant_id' => $this->merchant_id,
  					'memo' => $this->memo,
					'total' => $order->order_total,
					'notify_url' => home_url('index.php?vpnotify=1&order_id='.$order_id),
  					'success_url' => $success_url,
  					'fail_url' => $fail_url
		);

		$vpay_args_array = array();
		foreach($vpay_args as $key => $value)
		{
			$vpay_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
		}

		$woocommerce->add_inline_js('
			jQuery("body").block({
					message: "<img src=\"' . esc_url( apply_filters( 'woocommerce_ajax_loader_url', $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif' ) ) . '\" alt=\"Redirecting&hellip;\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to Voguepay to make payment.', 'vpay').'",
					overlayCSS:
					{
						background: "#fff",
						opacity: 0.6
					},
					css: {
				        padding:        20,
				        textAlign:      "center",
				        color:          "#555",
				        border:         "3px solid #aaa",
				        backgroundColor:"#fff",
				        cursor:         "wait",
				        lineHeight:		"32px"
				    }
				});
			jQuery("#submit_paypal_payment_form").click();
		');

		return '<form action="'.$this -> liveurl.'" method="post" id="voguepay_payment_form">
            ' . implode('', $vpay_args_array) . '
				<input type="submit" class="button cancel" id="submit_paypal_payment_form" value="'.__('Pay via Voguepay', 'woocommerce').'" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>
			</form>';

	}
	function process_payment($order_id)
	{
		global $woocommerce;
		$order = new WC_Order( $order_id );
		if (  $this->form_submission_method ) {
			$vpay_adr = $this->liveurl . '?';
			return array(
					'result' 	=> 'success',
					'redirect'	=> $vpay_adr
			);
		} else {

			return array(
					'result' 	=> 'success',
					'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
			);

		}
	}


	function receipt_page( $order ) {

		echo '<p>'.__('Thank you for your order, please click the button below to pay securely with Voguepay.', 'vpay').'</p>';
		$vpayurl=VPG_URL . 'images/vpay_logo.jpg';?>
		<img src= "<?php echo $vpayurl ?>" />
        <?php
		//echo $success_url;
		//echo $fail_url;
		echo $this->generate_voguepay_form( $order );
	}

	protected function _log($str)
	{
		$log_file = dirname(__FILE__) . '/vp.log';
		$fh = file_exists($log_file) ? fopen($log_file, 'a+') : fopen($log_file, 'w+');
		fwrite($fh, print_r($str, 1) . "\n");
		fclose($fh);
	}

	function check_voguepay_response ()
	{
		global $woocommerce;
		if( !isset($_REQUEST['vpnotify']) )
			return false;
		$this->_log($_REQUEST);
		$order_id = (int)$_REQUEST['order_id'];
		//get the full transaction details as an xml from voguepay
		$json = json_decode(file_get_contents_curl_voguepay('https://voguepay.com/?v_transaction_id='.$_REQUEST['transaction_id'].'&type=json'));
		$this->_log("JSON response");
		$this->_log($json);

		if( $json->status == 'Approved' )
		{
			$order = new WC_Order($order_id);
			$order->update_status('completed');
			$order->add_order_note( __( 'IPN payment completed', 'woocommerce' ) );
		}
		
	}
	
	//get title of pages
	function get_pages($title = false, $indent = true) {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while($has_parent) {
                    $prefix .=  ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }	
} 