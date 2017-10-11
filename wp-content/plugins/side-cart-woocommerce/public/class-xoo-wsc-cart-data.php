<?php
class xoo_wsc_Cart_Data{
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0 
	 * @access   private
	 * @var      string    $xoo_wsc    The ID of this plugin.
	 */
	private $xoo_wsc;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $xoo_wsc    The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $xoo_wsc ) {

		$this->xoo_wsc = $xoo_wsc;

	}

	/**
	 * Formats the RAW woocommerce price
	 *
	 * @since     1.0.0
	 * @param  	  int $price
	 * @return    string 
	 */

	public function formatted_price($price){
		if(!$price)
			return;
		$options 	= get_option('xoo-wsc-gl-options');
		$default_wc = isset( $options['sc-price-format']) ? $options['sc-price-format'] : 0;
		
		if($default_wc == 1){
			return wc_price($price);
		}

		$thous_sep = wc_get_price_thousand_separator();
		$dec_sep   = wc_get_price_decimal_separator();
		$decimals  = wc_get_price_decimals();
		$price 	   = number_format( $price, $decimals, $dec_sep, $thous_sep );

		$format   = get_option( 'woocommerce_currency_pos' );
		$csymbol  = get_woocommerce_currency_symbol();

		switch ($format) {
			case 'left':
				$fm_price = $csymbol.$price;
				break;

			case 'left_space':
				$fm_price = $csymbol.' '.$price;
				break;

			case 'right':
				$fm_price = $price.$csymbol;
				break;

			case 'right_space':
				$fm_price = $price.' '.$csymbol;
				break;

			default:
				$fm_price = $csymbol.$price;
				break;
		}
		return $fm_price;
	}

	/**
	 * Get Side Cart HTML
	 *
	 * @since     1.0.0
	 * @return    string 
	 */

	public function get_cart_markup(){
		if(is_cart() || is_checkout()){return;}
		require_once  plugin_dir_path( dirname( __FILE__ ) ).'/public/partials/xoo-wsc-markup.php';	
	}


	/**
	 * Sends JSON data on cart update
	 *
	 * @since     1.0.0
	 */

	public function send_json_data(){

		ob_start();
		$this->get_cart_content();
		$cart_markup = ob_get_clean();
		$ajax_fragm  = $this->get_ajax_fragments();

		//Get User Settings
		$options = get_option('xoo-wsc-gl-options');
		$show_count = isset( $options['bk-show-bkcount']) ? $options['bk-show-bkcount'] : 1;

		if($show_count == 1){
			$count_value = WC()->cart->get_cart_contents_count();
		}
		else{
			$count_value = 0;
		}

		//Send JSON data back to browser
		wp_send_json(
			array(
				'ajax_fragm' 	=> $ajax_fragm,
				'items_count' 	=> $count_value,
				'cart_markup' 	=> $cart_markup
				)
			);
	}




	/**
	 * Get Side Cart Content
	 *
	 * @since     1.0.0
	 */

	public function get_cart_content(){
		$cart_data 	= WC()->cart->get_cart(); 
		$options 	= get_option('xoo-wsc-gl-options');
		$empty_cart_txt = isset( $options['sc-empty-text']) ? $options['sc-empty-text'] : __('Your cart is empty.','side-cart-woocommerce');
		

		if(WC()->cart->is_empty()){
			echo '<span class="xoo-wsc-ecnt">'.esc_attr__($empty_cart_txt,'side-cart-woocommerce').'</span>';
			return;
		}

		
		$subtotal_txt 	= isset($options['sc-subtotal-text']) ? $options['sc-subtotal-text']: __("Subtotal:",'side-cart-woocommerce');
		$shipping_txt 	= isset($options['sc-shipping-text']) ? $options['sc-shipping-text']: __("To find out your shipping cost , Please proceed to checkout.",'side-cart-woocommerce');
		$show_ptotal 	= isset( $options['sc-show-ptotal']) ? $options['sc-show-ptotal'] : 1;
	

		foreach ( $cart_data as $cart_item_key => $cart_item ) {
					$_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

					$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );


					
					$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );

					

					if ( ! $product_permalink ) {
						$product_name =  apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key ) . '&nbsp;';
					} else {
						$product_name =  apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_title() ), $cart_item, $cart_item_key );
					}
											

					$product_price = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );

					$product_subtotal = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );

					//Variation
					$attributes = wc_get_formatted_variation($_product);
					// Meta data
					$attributes .=  WC()->cart->get_item_data( $cart_item );

		?>

			<div class="xoo-wsc-product" data-xoo_wsc="<?php echo $cart_item_key; ?>">
				<div class="xoo-wsc-img-col">
					<?php echo $thumbnail; ?>
					<a href="#" class="xoo-wsc-remove"><?php _e('Remove','side-cart-woocommerce'); ?></a>
				</div>
				<div class="xoo-wsc-sum-col">
					<a href="<?php echo $product_permalink ?>" class="xoo-wsc-pname"><?php echo $product_name; ?></a>
					<?php 

					if($attributes){
						echo $attributes;
					}

					?>
					<div class="xoo-wsc-price">
						<span><?php echo $cart_item['quantity']; ?></span> X <span><?php echo $product_price; ?></span> 
						<?php if($show_ptotal == 1): ?>
							= <span><?php echo $product_subtotal; ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<?php } ?>

			<div class="xoo-wsc-subtotal">
				<span><?php esc_attr_e($subtotal_txt,'side-cart-woocommerce') ?></span> <?php echo wc_price(WC()->cart->subtotal); ?>
			</div>

			<?php if(!empty($shipping_txt)): ?>
				<span class="xoo-wsc-shiptxt"><?php esc_attr_e($shipping_txt,'side-cart-woocommerce'); ?></span>
			<?php endif; ?>

		<?php
	}



	/**
	 * Add product to cart
	 *
	 * @since     1.0.0
	 */


	public function add_to_cart(){
		
		//Form Input Values		
		$product_data   = json_decode(stripslashes($_POST['product_data']),true);
		$product_id 	= intval($product_data['product_id']);
		$variation_id 	= intval($product_data['variation_id']);
		$quantity 		= empty( $product_data['quantity'] ) ? 1 : wc_stock_amount( $product_data['quantity'] );
		$product = wc_get_product($product_id);
		$variations = array();

		if($variation_id){
			$attributes = $product->get_attributes();
			$variation_data = wc_get_product_variation_attributes($variation_id);
			$chosen_attributes = json_decode(stripslashes($product_data['attributes']),true);
			
			foreach($attributes as $attribute){

				if ( ! $attribute['is_variation'] ) {
						continue;
				}

				$taxonomy = 'attribute_' . sanitize_title( $attribute['name'] );
				

				if ( isset( $chosen_attributes[ $taxonomy ] ) ) {
					
					// Get value from post data
					if ( $attribute['is_taxonomy'] ) {
						// Don't use wc_clean as it destroys sanitized characters
						$value = sanitize_title( stripslashes( $chosen_attributes[ $taxonomy ] ) );

					} else {
						$value = wc_clean( stripslashes( $chosen_attributes[ $taxonomy ] ) );

					}

					// Get valid value from variation
					$valid_value = isset( $variation_data[ $taxonomy ] ) ? $variation_data[ $taxonomy ] : '';

					// Allow if valid or show error.
					if ( '' === $valid_value || $valid_value === $value ) {
						$variations[ $taxonomy ] = $value;
					} 
				}

			}
			$cart_success =  WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations );
			$variation = new WC_product_variation($variation_id);
			$product_image = $variation->get_image();
		}

		elseif($variation_id === 0){
			$cart_success = WC()->cart->add_to_cart($product_id,$quantity);
		}

		//Successfully added to cart.
		if($cart_success){
			$this->send_json_data();
		}
		else{
			if(wc_notice_count('error') > 0){
	    		echo wc_print_notices();
			}
	  	}
		die();
	}



	/**
	 * Update product quantity in cart.
	 *
	 * @since     1.0.0
	 */

	public function update_cart(){

		//Form Input Values
		$cart_key = sanitize_text_field($_POST['cart_key']);
		$new_qty  = 0;

		//If empty return error
		if(!$cart_key){
			wp_send_json(array('error' => __('Something went wrong','side-cart-woocommerce')));
		}
		
		$cart_success = WC()->cart->set_quantity($cart_key,$new_qty);
		
		if($cart_success){
			$this->send_json_data();
		}
		else{
			if(wc_notice_count('error') > 0){
	    		echo wc_print_notices();
			}
		}
		die();
	}

	/**
	 * Get Cart fragments on update
	 *
	 * @since     1.0.0
	 * @return    array
	 */

	public function get_ajax_fragments(){

	  	// Get mini cart
	    ob_start();

	    woocommerce_mini_cart();

	    $mini_cart = ob_get_clean();

	    // Fragments and mini cart are returned
	    $data = array(
	        'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array(
	                'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>'
	            )
	        ),
	        'cart_hash' => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_for_session() ? md5( json_encode( WC()->cart->get_cart_for_session() ) ) : '', WC()->cart->get_cart_for_session() )
	    );
	    return $data;
	}
}
?>