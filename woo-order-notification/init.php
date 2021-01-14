<?php
/*
Plugin Name:  Woo Order Notification
Plugin URI:   https://codecanyon.net/item/woo-order-notification-wordpress-plugin-for-woocommerce/21077301?s_rank=1
Description:  WooCommerce Order Notification for Admin
Version:      20171017
Author:       nath4n
Author URI:   http://bitdrobe.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wporg
Domain Path:  /languages
*/

// Enable WP_DEBUG mode
define( 'WP_DEBUG', true );

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	add_action('admin_enqueue_scripts','woo_order_notification_enqueue', 11);
	
	if (!function_exists('woo_order_notification_enqueue'))   {
		function woo_order_notification_enqueue(){	
			wp_enqueue_script('jquery');
			wp_register_script('toastr-scripts', plugins_url('toastr/toastr.js', __FILE__), array('jquery'));
			wp_enqueue_script('toastr-scripts' );
			wp_enqueue_style('toastr-min-style', PLUGINS_URL('toastr/toastr.min.css', __FILE__ ));
		}
	}

	add_action( 'admin_notices', 'woo_order_notification_toastr', 10, 1);
	
	if (!function_exists('woo_order_notification_toastr'))   {
		function woo_order_notification_toastr(){
			
			$query = new WC_Order_Query( array(
				'limit' => -1,
				'type' => 'shop_order',
				'status' => 'on-hold',
				'return' => 'ids', //if I comment this line, the result will be empty
			) );
			
			$processing_orders = $query->get_orders();
			
			if(!empty($processing_orders)){
				foreach($processing_orders as $order_id){
					$confirmRead = get_post_meta( $order_id, 'woo_order_notification', true );
					if ($confirmRead == 'unread') {
					$order = wc_get_order( intval($order_id) );
					$order_data = $order->get_data(); // The Order data
					// Get the order billing information
					$order_billing_first_name = $order_data['billing']['first_name'];
					$order_billing_last_name = $order_data['billing']['last_name'];
					?>
					<script>
					jQuery(function() {
						// toastr notification
						var order_id = <?php echo $order_id; ?>;
						var first_name = '<?php echo $order_billing_first_name; ?>';
						var last_name = '<?php echo $order_billing_last_name; ?>';
						toastr.options.closeButton = true;
						toastr.options.positionClass = 'toast-top-right';
						toastr.options.showDuration = 1000;
						toastr.options.onclick = function() { confirmRead(order_id); }
						toastr['info']('You have a new order!<br>Order #'+order_id+' (<strong><i>'+first_name+' '+last_name+'</i></strong>)<br><i>Click to remove this notification</i>','Woo Order Notification');
					});
					function confirmRead(order_id){
						var data = {
							'action': 'action_mark_as_read',
							'order_id': order_id
						};

						jQuery.post(ajaxurl, data);
						window.location.href = 'post.php?post='+order_id+'&action=edit';
					}
					</script>
					<?php
					}
				}
			}
		}
	}

	add_action( 'wp_ajax_action_mark_as_read', 'action_mark_as_read' );

	if (!function_exists('action_mark_as_read'))   {
		function action_mark_as_read() {
			$order_id = intval( $_POST['order_id'] );
			delete_post_meta( $order_id, 'woo_order_notification', 'unread' );
			wp_die();
		}
	}

	if (!function_exists('woo_order_notification_mark_as_unread'))   {
		function woo_order_notification_mark_as_unread( $post_id ) {
			update_post_meta( $post_id, 'woo_order_notification', 'unread' );
		}
	}

	// Hook email notification
	add_action( 'woocommerce_order_status_pending_to_on-hold_notification', 'woo_order_notification_mark_as_unread', 10, 2 );
} else {
	add_action( 'admin_notices', 'woo_order_notification_notices', 10, 1);
	if (!function_exists('woo_order_notification_notices'))   {
		function woo_order_notification_notices(){
		?>
			<div class="notice notice-error is-dismissible">
			<p><strong>Woo Order Notification: WooCommerce is not Active</strong></p>
			<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
			</div>
		<?php
		}
	}
}
?>