<?php
/*Plugin Name: Post Purchase Experience Addon
Plugin URI: https://www.tychesoftwares.com/store/premium-plugins
Description: This plugin allows you to send post purchase experience email to the customers on the next day of the delivery date. This plugin is an addon for <a href="https://www.tychesoftwares.com/store/premium-plugins/order-delivery-date-for-woocommerce-pro-21/" target="_blank">Order Delivery Date Pro for WooCommerce</a> plugin.
Author: Tyche Softwares
Version: 1.0
Author URI: http://www.tychesoftwares.com/about
Contributor: Tyche Softwares, http://www.tychesoftwares.com/
*/

// Schedule an action if it's not already scheduled

define( 'PPE_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );

wp_clear_scheduled_hook( 'ppe_send_post_purchase_email' );
if ( ! wp_next_scheduled( 'ppe_send_post_purchase_email' ) ) {
    wp_schedule_event( time(), 'daily_once', 'ppe_send_post_purchase_email' );    
}

include_once( 'class-ppe-email-manager.php' );
include_once( 'license.php' );

class post_purchase_experience {
	public function __construct() {

		//Delete Plugin
	    register_uninstall_hook( __FILE__, array( 'post_purchase_experience' , 'ppe_deactivate' ) );

		//Check for Order Delivery Date Pro for WooCommerce
		add_action( 'admin_init', array( &$this, 'ppe_check_if_plugin_active' ) );

        //License
        add_action( 'orddd_add_submenu', array( &$this, 'ppe_addon_for_orddd_menu' ) );
        add_action( 'admin_init', array( 'ppe_license', 'ppe_register_option' ) );
        add_action( 'admin_init', array( 'ppe_license', 'ppe_deactivate_license' ) );
        add_action( 'admin_init', array( 'ppe_license', 'ppe_activate_license' ) );

		//Cron to run script for deleting past date lockouts
	    add_filter( 'cron_schedules', array( &$this, 'ppe_add_cron_schedule' ) );
		add_action( 'orddd_general_settings_links', array( &$this, 'ppe_links' ) );	
		add_action( 'admin_init', array( &$this, 'ppe_settings' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'ppe_my_enqueue_css' ) );

		add_action( 'ppe_send_post_purchase_email', array( &$this, 'ppe_send_post_purchase_email' ) );

		add_action( 'woocommerce_after_main_content', array( &$this, 'ppe_load_review_page' ));
	}	

	/**
	 * Delete all the options from the database when plugin uninstalled
	 */
	public static function ppe_deactivate() {
		delete_option( 'ppe_enable_post_experience_email' );
	}

	public function ppe_check_if_plugin_active() {
        if ( !is_plugin_active( 'order-delivery-date/order_delivery_date.php' ) ) {
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
        if( !is_plugin_active( 'order-delivery-date/order_delivery_date.php' ) ) {
            $message = __( '<b>Post Purchase Experience Addon</b> requires <b>Order Delivery Date Pro for WooCommerce</b> plugin installed and activate.', 'order-delivery-date' );
        }
        printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
    }

    public function ppe_addon_for_orddd_menu() {
        $page = add_submenu_page( 'order_delivery_date', __( 'Activate Post Purchase Experience License', 'order-delivery-date' ), __( 'Activate Post Purchase Experience License', 'order-delivery-date' ), 'manage_woocommerce', 'ppe_license_page', array( 'ppe_license', 'ppe_sample_license_page' ) );
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

	public function ppe_links( $section ) {
		$email_notifications_class = '';
		if ( $section == 'ppe_settings' ) {
	        $email_notifications_class = "current";
	    }
		?>
		<li>
	        | <a href="admin.php?page=order_delivery_date&action=general_settings&section=ppe_settings" class="<?php echo $email_notifications_class; ?>"><?php _e( 'Emails', 'order-delivery-date' );?> </a>
	    </li>
	    <?php
		if( $section == 'ppe_settings' ) {
	        print( '<div id="content">
                <form method="post" action="options.php">' );
                    settings_fields( "ppe_settings" );
                    do_settings_sections( "ppe_settings_page" );
                    submit_button ( __( 'Save Settings', 'ppe_woocommerce' ), 'primary', 'save', true );
                print('</form>
            </div>');
        }	    
	}

	public function ppe_settings() {
		add_settings_section(
            'ppe_settings_section',		// ID used to identify this section and with which to register options
            __( 'Post Purchase Experience Settings', 'ppe-woocommerce' ),		// Title to be displayed on the administration page
            array( &$this, 'ppe_setting' ),		// Callback used to render the description of the section
            'ppe_settings_page'				// Page on which to add this section of options
        );

        add_settings_field(
            'ppe_enable_post_experience_email',
            __( 'Send post purchase email:', 'ppe-woocommerce' ),
            array( &$this, 'ppe_enable_post_experience_email_callback' ),
            'ppe_settings_page',
            'ppe_settings_section',
            array ( __( 'Set post purchase experience notification email to customers on next day of delivery.', 'order-delivery-date' ) )
        );

        register_setting(
        	'ppe_settings',
        	'ppe_enable_post_experience_email'
		);
	}

	public function ppe_setting() {

	}

	public function ppe_enable_post_experience_email_callback( $args ) {
		$ppe_enable_post_experience_email = "";
		if ( get_option( 'ppe_enable_post_experience_email' ) == 'on' ) {
			$ppe_enable_post_experience_email = "checked";
		}
		
		echo '<input type="checkbox" name="ppe_enable_post_experience_email" id="ppe_enable_post_experience_email" class="day-checkbox" ' . $ppe_enable_post_experience_email . '/>';
		
		$html = '<label for="ppe_enable_post_experience_email"> ' . $args[0] . '</label>';
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