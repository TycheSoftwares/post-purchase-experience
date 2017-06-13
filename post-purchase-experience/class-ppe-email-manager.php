<?php /**
 * Handles email sending
 */
class PPE_Email_Manager {

	public function __construct() {

	}

	public static function ppe_send_email() {
	    if( 'on' == get_option( 'ppe_enable_post_delivery_email' ) ) {
	    	global $wpdb;
	        $current_timestamp = current_time( 'timestamp' );
	        $ppe_enabled_timestamp = get_option( 'ppe_checkbox_activate_timestamp', true );
	        $site_title = get_option( 'blogname' );

	        $ppe_query = "SELECT ID, post_status FROM `" . $wpdb->prefix . "posts` WHERE post_type = 'shop_order' AND post_status NOT IN ( 'wc-cancelled', 'wc-refunded', 'trash', 'wc-failed' ) AND ID IN ( SELECT post_id FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = '_orddd_timestamp' )";
	        $results = $wpdb->get_results( $ppe_query );
	        foreach ( $results as $key => $value ) {
	        	$email_sent_to_orders = get_option( 'ppe_post_purchase_email_orders' );
	        	if( '' == $email_sent_to_orders || '[]' == $email_sent_to_orders || '{}' == $email_sent_to_orders ) {
		            $email_sent_to_orders = array();
		        }

		        $order = new WC_Order( $value->ID );
		        $order_id = $value->ID;
		        $delivery_timestamp = get_post_meta( $order_id, '_orddd_timestamp', true );
		        if( $delivery_timestamp == '' ) {
		        	$delivery_timestamp = get_post_meta( $order_id, '_orddd_lite_timestamp', true );
		        }
		        $next_delivery_timestamp = $delivery_timestamp + 86400;
	            if( !in_array( $order_id, $email_sent_to_orders ) && $current_timestamp >= $next_delivery_timestamp && $delivery_timestamp > $ppe_enabled_timestamp ) {
	            	$message = '';    
	                //Subject
	                $subject = PPE_Email_Manager::get_subject();
	                $first_name    = $order->get_billing_first_name();
					$last_name     = $order->get_billing_last_name();
	                $subject = str_replace( '{{customer_fullname}}', $first_name . " " . $last_name, $subject );
	                $subject = str_replace( '{{website_title}}', $site_title, $subject );
	                $subject = str_replace( '{{order_number}}', $order_id, $subject );

	                //Recipient
	                $recipient  = $order->get_billing_email();

	                //Body
					$message .= PPE_Email_Manager::get_template( $order );	                

					//headers
					$headers = PPE_Email_Manager::get_headers();

	                $email_sent_to_orders[] = $value->ID;
	                
	                wp_mail( $recipient, $subject, $message, $headers );

	                update_option( 'ppe_post_purchase_email_orders', $email_sent_to_orders );
	            }
	        }
	    }
	}

	public static function get_subject() {
		$subject = __( '{{customer_fullname}}, regarding your recent order #{{order_number}} at {{website_title}}', 'ppe-woocommerce' );
		return $subject;
	}

	public static function get_headers() {
		$headers = array();
		$headers[] = "From:" . get_option( 'admin_email' );
		$headers[] = "Content-type: text/html"; 
		return $headers;
	}

	public static function get_template( $order ) {
		ob_start();
		wc_get_template( 'ppe_post_experience_email.php', 
			array( 'order_obj' => $order,
				'blog_title'   => get_bloginfo( 'title' ),
				'plugins_url'  => plugins_url()
			),
			'', 
			PPE_TEMPLATE_PATH );
		return ob_get_clean( );
	}
}// end of class
$PPE_Email_Manager = new PPE_Email_Manager();
?>
