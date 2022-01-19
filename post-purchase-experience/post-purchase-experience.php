<?php
/*Plugin Name: Post Delivery Product Reviews Addon
Plugin URI: https://www.tychesoftwares.com/store/premium-plugins
Description: This plugin allows you to send post delivery product reviews email to the customers on the next day of the delivery date. This plugin is an addon for <a href="https://www.tychesoftwares.com/store/premium-plugins/order-delivery-date-for-woocommerce-pro-21/" target="_blank">Order Delivery Date Pro for WooCommerce</a> & <a href="https://wordpress.org/plugins/order-delivery-date-for-woocommerce/" target="_blank">Order Delivery Date for WooCommerce Lite</a>plugin.
Author: Tyche Softwares
Version: 1.0
Author URI: http://www.tychesoftwares.com/about
Contributor: Tyche Softwares, http://www.tychesoftwares.com/
*/

// Schedule an action if it's not already scheduled

include_once( 'class-ppe-email-manager.php' );
include_once( 'license.php' );

if ( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
    // load our custom updater if it doesn't already exist
    include( dirname( __FILE__ ) . '/plugin-updates/EDD_SL_Plugin_Updater.php' );
}

// retrieve our license key from the DB
$license_key = trim( get_option( 'ppe_sample_license_key' ) );
// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
// IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system
define( 'PPE_SL_STORE_URL', 'http://www.tychesoftwares.com/' ); 

// the name of your product. This is the title of your product in EDD and should match the download title in EDD exactly
// IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system
define( 'PPE_SL_ITEM_NAME', 'Post Delivery Product Reviews Addon for Order Delivery Date for WooCommerce' ); 
// setup the updater
$edd_updater = new EDD_SL_Plugin_Updater( PPE_SL_STORE_URL, __FILE__, array(
    'version' 	=> '6.8', 		// current version number
    'license' 	=> $license_key, 	// license key (used get_option above to retrieve from DB)
    'item_name' => PPE_SL_ITEM_NAME, 	// name of this plugin
    'author' 	=> 'Ashok Rane'  // author of this plugin
)
);

define( 'PPE_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );

wp_clear_scheduled_hook( 'ppe_send_post_purchase_email' );
if ( ! wp_next_scheduled( 'ppe_send_post_purchase_email' ) ) {
    wp_schedule_event( time(), 'daily', 'ppe_send_post_purchase_email' );
}

class post_purchase_experience {
	public function __construct() {

		//Delete Plugin
	    register_uninstall_hook( __FILE__, array( 'post_purchase_experience' , 'ppe_deactivate' ) );

		//Check for Order Delivery Date Pro for WooCommerce
		add_action( 'admin_init', array( &$this, 'ppe_check_if_plugin_active' ) );

        //License
        add_action( 'orddd_add_submenu', array( &$this, 'ppe_addon_for_orddd_menu' ) );
        add_action( 'orddd_lite_add_submenu', array( &$this, 'ppe_addon_for_orddd_lite_menu' ) );
        add_action( 'admin_init', array( 'ppe_license', 'ppe_register_option' ) );
        add_action( 'admin_init', array( 'ppe_license', 'ppe_deactivate_license' ) );
        add_action( 'admin_init', array( 'ppe_license', 'ppe_activate_license' ) );

		//Cron to run script for deleting past date lockouts
	    add_filter( 'cron_schedules', array( &$this, 'ppe_add_cron_schedule' ) );
		add_action( 'orddd_addon_settings', array( &$this, 'ppe_settings' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'ppe_my_enqueue_css' ) );

		add_action( 'ppe_send_post_purchase_email', array( &$this, 'ppe_send_post_purchase_email' ) );

		add_action( 'woocommerce_after_main_content', array( &$this, 'ppe_load_review_page' ));
		add_filter( 'orddd_additional_settings_rest_api', array( &$this, 'orddd_add_settings_to_api' ) );
	}	

	/**
	 * Delete all the options from the database when plugin uninstalled
	 */
	public static function ppe_deactivate() {
		delete_option( 'ppe_enable_post_delivery_email' );
	}

	public function ppe_check_if_plugin_active() {
        if ( !is_plugin_active( 'order-delivery-date/order_delivery_date.php' ) && !class_exists('order_delivery_date_lite') ) {
            if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
                deactivate_plugins( plugin_basename( __FILE__ ) );
                add_action( 'admin_notices', array( &$this, 'ppe_error_notice' ) );
                if ( isset( $_GET[ 'activate' ] ) ) {
                    unset( $_GET[ 'activate' ] );
                }
            }
        }
    }

    public function ppe_error_notice() {
        $class = 'notice notice-error';
         if ( !is_plugin_active( 'order-delivery-date/order_delivery_date.php' ) && !class_exists('order_delivery_date_lite') ) {
            $message = __( '<b>Post Delivery Product Reviews Addon</b> requires <b>Order Delivery Date Pro for WooCommerce</b> or <b>Order Delivery Date for WooCommerce Lite</b> plugin installed and activate.', 'order-delivery-date' );
        }
        printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
    }

    public function ppe_addon_for_orddd_menu() {
        $page = add_submenu_page( 'order_delivery_date', __( 'Activate Post Delivery Product Reviews Addon License', 'order-delivery-date' ), __( 'Activate Post Delivery Product Reviews Addon License', 'order-delivery-date' ), 'manage_woocommerce', 'ppe_license_page', array( 'ppe_license', 'ppe_sample_license_page' ) );
    }

    public function ppe_addon_for_orddd_lite_menu() {
    	$page = add_submenu_page( 'order_delivery_date_lite', __( 'Activate Post Delivery Product Reviews Addon License', 'order-delivery-date' ), __( 'Activate Post Delivery Product Reviews Addon License', 'order_delivery_date_lite' ), 'manage_woocommerce', 'ppe_license_page', array( 'ppe_license', 'ppe_sample_license_page' ) );
    }

	public function ppe_add_cron_schedule( $schedules ) {
		$schedules[ 'daily_once' ] = array(
                'interval' => 86400,  // one week in seconds
                'display'  => __( 'Once in a Day', 'order-delivery-date' ),
        );
        return $schedules;
	}

	public function ppe_my_enqueue_css( $hook ) {
		if ( 'woocommerce_page_ppe_woocommerce' == $hook ) {
			wp_enqueue_style( 'ppe_settings', plugins_url( '/css/ppe_settings.css', __FILE__ ) , '', '1.0', false );
			/*this has been done to make plugin compatible with WooCommerce Order Status & Actions Manager plugin*/
	        wp_dequeue_script( 'wc-enhanced-select' );
	        wp_register_script( 'select2', plugins_url() . '/woocommerce/assets/js/select2/select2.min.js', array( 'jquery', 'jquery-ui-widget', 'jquery-ui-core' ), '1.0' );
	        wp_enqueue_script( 'select2' );
		}
	}

	public function ppe_settings( $settings ) {
		$settings = array(
			array(
				'settings_id'	 => 'ppe_enable_post_delivery_email',
				'settings_label' => __( 'Post Delivery Product Reviews', 'ppe-woocommerce' ),
				'tooltip'		 => __( 'Send post delivery product reviews notification email to customers on next day of delivery.', 'order-delivery-date' ),
			),
		);
		return $settings;
	}

	public static function orddd_add_settings_to_api( $settings ) {
		$settings['ppe_enable_post_delivery_email']  = false !== get_option( 'ppe_enable_post_delivery_email' ) ? get_option( 'ppe_enable_post_delivery_email' ) : '';

		return $settings;
	}

	public function ppe_enable_post_delivery_email_callback( $args ) {
		$ppe_enable_post_delivery_email = "";
		if ( get_option( 'ppe_enable_post_delivery_email' ) == 'on' ) {
			$ppe_enable_post_delivery_email = "checked";
		}
		
		echo '<input type="checkbox" name="ppe_enable_post_delivery_email" id="ppe_enable_post_delivery_email" class="day-checkbox" ' . $ppe_enable_post_delivery_email . '/>';
		
		$html = '<label for="ppe_enable_post_delivery_email"> ' . $args[0] . '</label>';
		echo $html;
	}

	public function ppe_send_post_purchase_email() {
		PPE_Email_Manager::ppe_send_email();
	}

	public function ppe_load_review_page( $comment_form ) {
		if( isset( $_GET[ 'rating' ] ) && '' != $_GET[ 'rating' ] ) {
			$rating = $_GET[ 'rating' ];
			echo '<input type="hidden" id="orddd_rating" name="orddd_rating" value="' . $rating . '">';
			?>
			<script type='text/javascript'>
			window.onload = function() {
				var rating = jQuery( '#orddd_rating' ).val();
				jQuery( ".stars" ).addClass( 'selected' );
				if( '5' == rating ) {
					jQuery( ".star-5" ).addClass( 'active' );
					jQuery( "#respond #rating" ).val( '5' );
				} else if( '4' == rating ) {
					jQuery( ".star-4" ).addClass( 'active' );
					jQuery( "#respond #rating" ).val( '4' );
				} else if( '3' == rating ) {
					jQuery( ".star-3" ).addClass( 'active' );
					jQuery( "#respond #rating" ).val( '3' );
				} else if( '2' == rating ) {
					jQuery( ".star-2" ).addClass( 'active' );
					jQuery( "#respond #rating" ).val( '2' );
				} else if( '1' == rating ) {
					jQuery( ".star-1" ).addClass( 'active' );	
					jQuery( "#respond #rating" ).val( '1' );
				}
			}
			</script>
			<?php
		}
	}
}

$post_purchase_experience = new post_purchase_experience();