<?php 
class WC_PayWorks_Payment_Gateway extends WC_Payment_Gateway{
	public function __construct(){
		$this->id = 'payworks_payment';
		$this->method_title = __('PayWorks&trade; Payment','woocommerce-payworks-payment-gateway');
		$this->title = __('PayWorks Payment','woocommerce-payworks-payment-gateway');
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');
		$this->payworkskey = $this->get_option('payworkskey');
		$this->payworksuid = $this->get_option('payworksuid');
		$this->description = $this->get_option('description');
		$this->hide_text_box = $this->get_option('hide_text_box');
		$this->pro_feature = $this->get_option('pro_feature');
		add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
	}
	public function init_form_fields(){
				$this->form_fields = array(
					'enabled' => array(
					'title' 		=> __( 'Enable/Disable', 'woocommerce-payworks-payment-gateway' ),
					'type' 			=> 'checkbox',
					'label' 		=> __( 'Enable PayWorks&trade; Payment', 'woocommerce-payworks-payment-gateway' ),
					'default' 		=> 'yes'
					),
					'title' => array(
						'title' 		=> __( 'Method Title', 'woocommerce-payworks-payment-gateway' ),
						'type' 			=> 'text',
						'description' 	=> __( 'This controls the title', 'woocommerce-payworks-payment-gateway' ),
						'default'		=> __( 'PayWorks&trade; Payment', 'woocommerce-payworks-payment-gateway' ),
						'desc_tip'		=> true,
					),
					'description' => array(
						'title' => __( 'Customer Message', 'woocommerce-payworks-payment-gateway' ),
						'type' => 'textarea',
						'css' => 'width:500px;',
						'default' => 'You can pay via PayWorks&trade;.',
						'description' 	=> __( 'The message which you want it to appear to the customer in the checkout page.', 'woocommerce-payworks-payment-gateway' ),
					),
					'payworkskey' => array(
						'title' => __( 'PayWorks Key', 'woocommerce-payworks-payment-gateway' ),
						'type' => 'text',
						'description' 	=> __( 'Paste your PayWorks secure key here.' ),
						'desc_tip'		=> true,
					),
					'payworksuid' => array(
						'title' => __( 'PayWorks User ID', 'woocommerce-payworks-payment-gateway' ),
						'type' => 'text',
						'description' 	=> __( 'Paste your PayWorks User ID here.' ),
						'desc_tip'		=> true,
					),
					'hide_text_box' => array(
						'title' 		=> __( 'Hide The Comment Field', 'woocommerce-payworks-payment-gateway' ),
						'type' 			=> 'checkbox',
						'label' 		=> __( 'Hide', 'woocommerce-payworks-payment-gateway' ),
						'default' 		=> 'no',
						'description' 	=> __( 'If you do not need to show the comment box for customers at all, enable this option.', 'woocommerce-payworks-payment-gateway' ),
					),
					'pro_feature' => array(
						'title' 		=> __( 'PayWorks Pro Feature', 'woocommerce-payworks-payment-gateway' ),
						'type' 			=> 'checkbox',
						'label' 		=> __( 'Enable', 'woocommerce-payworks-payment-gateway' ),
						'default' 		=> 'no',
						'description' 	=> __( 'PayWorks Pro customers can take payments on their website without redirecting the users to PayWorks Interface', 'woocommerce-payworks-payment-gateway' ),
					)
			 );
	}
	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_options() {
		?>
		<h3><?php _e( 'PayWorks Payment Settings', 'woocommerce-payworks-payment-gateway' ); ?></h3>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<table class="form-table">
							<?php $this->generate_settings_html();?>
						</table><!--/.form-table-->
					</div>
					<div id="postbox-container-1" class="postbox-container">
	                        <div id="side-sortables" class="meta-box-sortables ui-sortable"> 
	                            <div class="postbox ">
	                                <div class="handlediv" title="Click to toggle"><br></div>
	                                <h3 class="hndle"><span><i class="dashicons dashicons-editor-help"></i>&nbsp;&nbsp;Plugin Support</span></h3>
	                            </div>
	                        </div>
	                    </div>
                    </div>
				</div>
				<div class="clear"></div>
				<style type="text/css">
				.wpruby_button{
					background-color:#4CAF50 !important;
					border-color:#4CAF50 !important;
					color:#ffffff !important;
					width:100%;
					padding:5px !important;
					text-align:center;
					height:35px !important;
					font-size:12pt !important;
				}
				</style>
				<?php
	}
	public function process_payment( $order_id ) {
		global $woocommerce;
		$options = get_option('woocommerce_payworks_payment_settings');
		$pro_feature = $options['pro_feature'];
		$my_secure_key = $options['payworkskey'];
		$my_secure_uid = $options['payworksuid'];
		$order = new WC_Order( $order_id );
        
		// DO NOT CHANGE THE KEY FOR EMAIL
		
        if($pro_feature == 'no'){
            // Mark as on-hold (we're awaiting the cheque)
		    $order->update_status('on-hold', __( 'Awaiting payment', 'woocommerce-payworks-payment-gateway' ));
		    // Reduce stock levels
		    wc_reduce_stock_levels( $order_id );
		    if(isset($_POST[ $this->id.'-admin-note']) && trim($_POST[ $this->id.'-admin-note'])!=''){
			    $order->add_order_note(esc_html($_POST[ $this->id.'-admin-note']),1);
		    }
		    // Remove cart
		    $woocommerce->cart->empty_cart();
		    // Return thankyou redirect
		    return array(
			    'result' => 'success',
			    'redirect' => $this->get_return_url( $order )
		    );
        } else {
		    $buyernotes = $_POST[ $this->id.'-admin-note'];
		    $cardnumber = $_POST[ $this->id.'-admin-card-number'];
		    $cardexpmonth = $_POST[ $this->id.'-admin-card-exp-month'];
		    $cardexpyear = $_POST[ $this->id.'-admin-card-exp-year'];
		    $nameoncard = $_POST[ $this->id.'-admin-card-name'];
		    $cardcvv = $_POST[ $this->id.'-admin-card-cvv'];
		
		    // Login Information
    	    $query = '';
    	    $query .= "userID=" . urlencode($my_secure_uid) . "&";
    	    $query .= "secret_key=" . urlencode($my_secure_key) . "&";
		    $query .= "otp=" . urlencode('') . "&";
		    $query .= "sendEmail=" . urlencode('yes') . "&";
    	    // Sales Information
    	    $query .= "cardnumber=" . urlencode($cardnumber) . "&";
    	    $query .= "cardexpmonth=" . urlencode($cardexpmonth) . "&";
		    $query .= "cardexpyear=" . urlencode($cardexpyear) . "&";
    	    $query .= "amount=" . urlencode(number_format($order->get_subtotal(),2,".","")) . "&";
    	    $query .= "cvvnumber=" . urlencode($cardcvv) . "&";
    	    // Order Information
    	    $query .= "ipaddress=" . urlencode($order->get_customer_ip_address()) . "&";
    	    // Billing Information
    	    $query .= "nameoncard=" . urlencode($nameoncard) . "&";
    	    $query .= "address1=" . urlencode($order->get_billing_address_1()) . "&";
    	    $query .= "address2=" . urlencode($order->get_billing_address_2()) . "&";
    	    $query .= "city=" . urlencode($order->get_billing_city()) . "&";
    	    $query .= "state=" . urlencode($order->get_billing_state()) . "&";
    	    $query .= "zip=" . urlencode($order->get_billing_postcode()) . "&";
    	    $query .= "country=" . urlencode($order->get_billing_country()) . "&";
    	    $query .= "phone=" . urlencode($order->get_billing_phone()) . "&";
    	    $query .= "clientemail=" . urlencode($order->get_billing_email()) . "&";
    	    $query .= "orderid=" . urlencode($order_id) . "&";
			$query .= "parenturl=" . urlencode(get_site_url()) . "&";
    	    $query .= "type=sale";
		
		    $ch = curl_init();
    	    curl_setopt($ch, CURLOPT_URL, "https://www.payworks.bs/paywork/payworks_point_pro_pay.html");
    	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    	    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	    curl_setopt($ch, CURLOPT_HEADER, 0);
    	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    	    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    	    curl_setopt($ch, CURLOPT_POST, 1);

    	    if (!($data = curl_exec($ch))) {
        	    return ERROR;
    	    }
    	    curl_close($ch);
    	    unset($ch);
    	    $data = explode("&",$data);
		    $response = json_decode($data[0]);
		    //echo $response->ERROR_CODE;
		    if($response->TYPE == 0){
		    	wc_add_notice( __( $response->MESSAGE ), 'error' );
		        return array(
                    'result'   => 'failure',
                    'messages' => 'Payment attempt failed'
                );
		    } else if($response->TYPE == 1) {
		        $order->update_status('processing', __( 'Processing', 'woocommerce-payworks-payment-gateway' ));
		        // Reduce stock levels
		        wc_reduce_stock_levels( $order_id );
		        if(isset($_POST[ $this->id.'-admin-note']) && trim($_POST[ $this->id.'-admin-note'])!=''){
			        $order->add_order_note(esc_html($_POST[ $this->id.'-admin-note'].'-'.$response->TOKENID),1);
		        }
		        // Remove cart
		        $woocommerce->cart->empty_cart();
		        // Return thankyou redirect
		        return array(
			        'result' => 'success',
			        'redirect' => $this->get_return_url( $order )
		        );
		    } else {
		        return array(
                    'result'   => 'failure',
                    'messages' => 'Unable to communicate with the processor'
                );
		    }
        }
	}

	public function payment_fields(){
		if($this->pro_feature !== 'no'){
			global $woocommerce;
			$subtotal = $woocommerce->cart->total;
			$tax = ($subtotal * (4/100))+0.99;
			$websitefee = round($tax,2);
			$gatewaytotal = $websitefee + $subtotal;
	    ?>

		<fieldset>
			<p class="form-row form-row-wide">
			    <label for="<?php echo $this->id; ?>-admin-card-name">Name on Card <span class="required">*</span></label>
				<input id="<?php echo $this->id; ?>-admin-card-name" class="input-text" maxlength="32" name="<?php echo $this->id; ?>-admin-card-name" required="" placeholder="John Doe"/>
				<img src="https://www.merchantequip.com/image/?logos=v|m|d&height=32" alt="Merchant Equipment Store Credit Card Logos"/>
				<label for="<?php echo $this->id; ?>-admin-card-number">Credit Card Number <span class="required">*</span></label>
				<input id="<?php echo $this->id; ?>-admin-card-number" class="input-text" maxlength="16" name="<?php echo $this->id; ?>-admin-card-number" required="" placeholder="XXXX-XXXX-XXXX-XXXX"/>
				<label for="<?php echo $this->id; ?>-admin-card-cvv">CVV Number <span class="required">*</span></label>
				<input id="<?php echo $this->id; ?>-admin-card-cvv" class="input-text" maxlength="4" name="<?php echo $this->id; ?>-admin-card-cvv" required=""  placeholder="XXX"/>
				<label for="<?php echo $this->id; ?>-admin-card-exp-month">Card Expiry Month  <span class="required">*</span></label>
				<select required="" class="input-text" name="<?php echo $this->id; ?>-admin-card-exp-month" id="<?php echo $this->id; ?>-admin-card-exp-month">
	            	<option value="">Month</option>
					<option value="01">01</option>
                    <option value="02">02</option>
                    <option value="03">03</option>
                    <option value="04">04</option>
                    <option value="05">05</option>
                    <option value="06">06</option>
                    <option value="07">07</option>
                    <option value="08">08</option>
                    <option value="09">09</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                </select>
				<label for="<?php echo $this->id; ?>-admin-card-exp-year">Card Expiry Year <span class="required">*</span></label>
				<select required="" class="input-text" name="<?php echo $this->id; ?>-admin-card-exp-year" id="<?php echo $this->id; ?>-admin-card-exp-year">
                	<option value="">Year</option>
                	<option value="18" selected="selected">2018</option>
                	<option value="19">2019</option>
                	<option value="20">2020</option>
                	<option value="21">2021</option>
                	<option value="22">2022</option>
                	<option value="23">2023</option>
                	<option value="24">2024</option>
                	<option value="25">2025</option>
                	<option value="26">2026</option>
                	<option value="27">2027</option>
                	<option value="28">2028</option>
                </select>
				<label for="<?php echo $this->id; ?>-admin-note"><?php echo esc_attr($this->description); ?> <span class="required">*</span></label>
				<textarea id="<?php echo $this->id; ?>-admin-note" class="input-text" type="text" name="<?php echo $this->id; ?>-admin-note">Paying via PayWorks</textarea>
				<label for="<?php echo $this->id; ?>-admin-note">Pay or Buy via PayWorks&trade;</label>
				<label for="<?php echo $this->id; ?>-admin-note"><small>Transaction Fee (4% + $0.99) + VAT (12%, if applicable)</small></label>
				<label for="<?php echo $this->id; ?>-total-amount"> </label>
			</p>						
			<div class="clear"></div>
		</fieldset>
		<h4>Total  - <?php echo wc_price($gatewaytotal);?></h4>
		<?php
		}
	}
}
?>