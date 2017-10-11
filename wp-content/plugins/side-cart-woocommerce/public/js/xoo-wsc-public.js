jQuery(document).ready(function($){
	'use strict';

	//Toggle Side Cart
	function toggle_sidecart(){
		$('.xoo-wsc-modal , body').toggleClass('xoo-wsc-active');
	}
	$('.xoo-wsc-basket').on('click',toggle_sidecart);

	//Auto open Side Cart when item added to cart without ajax
	if(xoo_wsc_localize.added_to_cart){
		toggle_sidecart();
	}


	//Close Side Cart
	function close_sidecart(e){
		$.each(e.target.classList,function(key,value){
			if(value != 'xoo-wsc-container' && (value == 'xoo-wsc-close' || value == 'xoo-wsc-opac' || value == 'xoo-wsc-basket' || value == 'xoo-wsc-cont')){
				$('.xoo-wsc-modal , body').removeClass('xoo-wsc-active');
			}
		})
	}

	$('.xoo-wsc-close , .xoo-wsc-opac , .xoo-wsc-cont').click(function(e){
		e.preventDefault();
		close_sidecart(e);
	});

	//Set Cart content height
	function content_height(){
		var header = $('.xoo-wsc-header').outerHeight(); 
		var footer = $('.xoo-wsc-footer').outerHeight();
		var screen = $(window).height();
		$('.xoo-wsc-body').outerHeight(screen-(header+footer));
	};
	content_height();
	$(window).resize(function(){
    	content_height();
	});
	
	//Refresh ajax fragments
	function refresh_ajax_fragm(ajax_fragm){
		var fragments = ajax_fragm.fragments;
		var cart_hash = ajax_fragm.cart_hash;
		var cart_html = ajax_fragm.fragments["div.widget_shopping_cart_content"];
		$('.woofc-trigger').css('transform','scale(1)');
		$('.shopping-cart-inner').html(cart_html);
		var cart_count = $('.cart_list:first').find('li').length;
		$('.shopping-cart span.counter , ul.woofc-count li').html(cart_count);
	}


	//Add to cart function
	function add_to_cart(atc_btn,product_data){
		$.ajax({
				url: xoo_wsc_localize.adminurl,
				type: 'POST',
				data: {action: 'add_to_cart',
					   product_data: product_data},
			    success: function(response,status,jqXHR){
			   		atc_btn.find('.xoo-wsc-icon-atc').attr('class','xoo-wsc-icon-checkmark xoo-wsc-icon-atc');
			   		if(xoo_wsc_localize.auto_open_cart == 1){
						toggle_sidecart();
					}
					on_cart_success(response);
			    }
			})
	}

	function on_cart_success(response){
			if(response.items_count === 0){
				$('a.xoo-wsc-chkt,a.xoo-wsc-cart').hide();
			}
			else{
				$('a.xoo-wsc-chkt,a.xoo-wsc-cart').show();
			}
	   		$('.xoo-wsc-content').html(response.cart_markup);
	   		$('.xoo-wsc-items-count').html(response.items_count);
	   		content_height();
	   		
	   		if(response.ajax_fragm)
	   			refresh_ajax_fragm(response.ajax_fragm);
	}

	//Update cart
	function update_cart(cart_key,new_qty){
		$('.xoo-wsc-updating').show();
		$.ajax({
			url: xoo_wsc_localize.adminurl,
			type: 'POST',
			data: {
				action: 'update_cart',
				cart_key: cart_key,
				new_qty: new_qty
			},
			success: function(response){
				on_cart_success(response);
		   		$('.xoo-wsc-updating').hide();
			}

		})
	}


	//Remove item from cart
	$(document).on('click','.xoo-wsc-remove',function(e){
		e.preventDefault();
		var product_row = $(this).parents('.xoo-wsc-product');
		var cart_key = product_row.data('xoo_wsc');
		update_cart(cart_key,0);
	})

	//Add to cart on single page
	if(xoo_wsc_localize.ajax_atc == 1){
		$(document).on('submit','form.cart',function(e){
			e.preventDefault();
			var atc_btn  = $(this).find('.single_add_to_cart_button');
			if(atc_btn.find('.xoo-wsc-icon-atc').length !== 0){
				atc_btn.find('.xoo-wsc-icon-atc').attr('class','xoo-wsc-icon-spinner xoo-wsc-icon-atc xoo-wsc-active');
			}
			else{
				atc_btn.append('<span class="xoo-wsc-icon-spinner xoo-wsc-icon-atc xoo-wsc-active"></span>');
			}

			var variation_id = parseInt($(this).find('[name=variation_id]').val());
			var product_id = parseInt($(this).find('[name=add-to-cart]').val());
			var quantity = parseInt($(this).find('.quantity').find('.qty').val());
			var product_data = {};

			if(variation_id){
				var attributes_select = $(this).find('.variations select');
				var	attributes = {};
				attributes_select.each(function(){
					attributes[$(this).data('attribute_name')] = $(this).val();
				})
				attributes = JSON.stringify(attributes);
				product_data['attributes'] = attributes;
				product_data['variation_id'] =variation_id;
			}

			product_data['product_id'] = product_id;
			product_data['quantity'] = quantity;

			add_to_cart(atc_btn,JSON.stringify(product_data));//Ajax add to cart
		})
	}

	//Add to cart on shop page
	$('.add_to_cart_button').on('click',function(e){
		var atc_btn = $(this);
		var product_id,quantity;

		if(atc_btn.hasClass('product_type_variable')){return;}
		e.preventDefault();
		
		
		if(atc_btn.find('.xoo-wsc-icon-atc').length !== 0){
			atc_btn.find('.xoo-wsc-icon-atc').attr('class','xoo-wsc-icon-spinner xoo-wsc-icon-atc xoo-wsc-active');
		}
		else{
			atc_btn.append('<span class="xoo-wsc-icon-spinner xoo-wsc-icon-atc xoo-wsc-active"></span>');
		}
		
		product_id = atc_btn.data('product_id');

		//If data-product_id attribute is not set or custom add to cart link is used
		if(product_id === undefined || product_id === null){
			var atc_link = $(this).attr('href');
			if(atc_link.indexOf("?add-to-cart") === -1)
				return;

			var atc_link_params_str = atc_link.substring(atc_link.indexOf("?add-to-cart")+1).split('&');
			var atc_link_params = [];

			$.each(atc_link_params_str,function(key,value){
				atc_link_params.push(value.split('=')); 
			});
			
			$.each(atc_link_params,function(key,value){
				if(value[0] == 'add-to-cart'){
					product_id = value[1];
				}
				else if(value[0] == 'quantity'){
					quantity = value[1];
				}
			})

			if(!product_id)
				return;
		}

		var product_data = {};
		
		product_data['product_id']  = product_id;
		product_data['quantity'] 	= quantity || 1;



		add_to_cart(atc_btn,JSON.stringify(product_data));//Ajax add to cart
	})
	

})