<?php
/**
 * orddd_license Class
 *
 * @class orddd_license
 */

class ppe_license {
	
	/** 
    * Activate License if License key is valid  
    */
	public static function ppe_activate_license() {		
		// listen for our activate button to be clicked
		if ( isset( $_POST[ 'ppe_license_activate' ] ) ) {
			// run a quick security check
			if ( ! check_admin_referer( 'ppe_sample_nonce', 'ppe_sample_nonce' ) )
				return; // get out if we didn't click the Activate button
			// retrieve the license from the database
			$license = trim( get_option( 'ppe_sample_license_key' ) );
			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license' 	 => $license,
				'item_name'  => urlencode( ppe_SL_ITEM_NAME ) // the name of our product in EDD
			);

			// Call the custom API.
			$response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, ppe_SL_STORE_URL ) ), array( 'timeout' => 15, 'sslverify' => false ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "active" or "inactive"
			update_option( 'ppe_sample_license_status', $license_data->license );
		}
	}

	/** 
    * Deactivate the License  
    */
	
	public static function ppe_deactivate_license() {
		// listen for our activate button to be clicked
		if ( isset( $_POST[ 'ppe_license_deactivate' ] ) ) {
			// run a quick security check
			if ( ! check_admin_referer( 'ppe_sample_nonce', 'ppe_sample_nonce' ) )
				return; // get out if we didn't click the Activate button
	
			// retrieve the license from the database
			$license = trim( get_option( 'ppe_sample_license_key' ) );
			
			// data to send in our API request
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license' 	 => $license,
				'item_name'  => urlencode( ppe_SL_ITEM_NAME ) // the name of our product in EDD
			);
	
			// Call the custom API.
			$response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, ppe_SL_STORE_URL ) ), array( 'timeout' => 15, 'sslverify' => false ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "deactivated" or "failed"
			if ( $license_data->license == 'deactivated' )
				delete_option( 'ppe_sample_license_status' );
		}
	}
	
	/**
	* Checks if License key is valid or not
	*/

	public static function ppe_sample_check_license() {
		global $wp_version;
		$license = trim( get_option( 'ppe_sample_license_key' ) );

		$api_params = array(
			'edd_action' => 'check_license',
			'license'	 => $license,
			'item_name'	 => urlencode( ppe_SL_ITEM_NAME )
		);
		// Call the custom API.
		$response = wp_remote_get( esc_url_raw( add_query_arg( $api_params, ppe_SL_STORE_URL ) ), array( 'timeout' => 15, 'sslverify' => false ) );

		if ( is_wp_error( $response ) )
			return false;

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $license_data->license == 'valid' ) {
			echo 'valid'; exit;
			// this license is still valid
		} else {
			echo 'invalid'; exit;
			// this license is no longer valid
		}
	}

	/**
    * Stores the license key in database of the site once the plugin is installed and the license key saved.
    */
	public static function ppe_register_option() {
		// creates our settings in the options table
		register_setting( 'ppe_sample_license', 'ppe_sample_license_key',  array( 'ppe_license', 'ppe_get_sanitize_license' ) );
	}

    /**
    * Checks if a new license has been entered, if yes plugin must be reactivated.
    */	
	public static function ppe_get_sanitize_license( $new ) {
		$old = get_option( 'ppe_sample_license_key' );
		if( $old && $old != $new ) {
			delete_option( 'ppe_sample_license_status' ); // new license has been entered, so must reactivate
		}
		return $new;
	}

	/**
    * Add the license page in the Order delivery date menu.
    */
	public static function ppe_sample_license_page() {
		$license 	= get_option( 'ppe_sample_license_key' );
		$status 	= get_option( 'ppe_sample_license_status' );
	
		?>
		<div class="wrap">
			<h2><?php _e( 'Plugin License Options', 'order-delivery-date' ); ?></h2>
				<form method="post" action="options.php">
					<?php settings_fields( 'ppe_sample_license' ); ?>
						<table class="form-table">
							<tbody>
								<tr valign="top">	
									<th scope="row" valign="top">
										<?php _e( 'License Key', 'order-delivery-date' ); ?>
									</th>
									<td>
										<input id="ppe_sample_license_key" name="ppe_sample_license_key" type="text" class="regular-text"	value="<?php esc_attr_e( $license ); ?>" />
											<label class="description" for="ppe_sample_license_key"><?php _e( 'Enter your license key', 'order-delivery-date' ); ?></label>
									</td>
								</tr>
								<?php if ( false !== $license ) { ?>
								<tr valign="top">	
									<th scope="row" valign="top">
										<?php _e( 'Activate License', 'order-delivery-date' ); ?>
									</th>
									<td>
									<?php if ( $status !== false && $status == 'valid' ) { ?>
										<span style="color:green;"><?php _e( 'active', 'order-delivery-date' ); ?></span>
										<?php wp_nonce_field( 'ppe_sample_nonce', 'ppe_sample_nonce' ); ?>
										<input type="submit" class="button-secondary" name="ppe_license_deactivate" value="<?php _e( 'Deactivate License', 'order-delivery-date' ); ?>"/>
									<?php } else {
											wp_nonce_field( 'ppe_sample_nonce', 'ppe_sample_nonce' ); ?>
											<input type="submit" class="button-secondary" name="ppe_license_activate" value="<?php _e( 'Activate License', 'order-delivery-date' ); ?>"/>
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>	
					<?php submit_button(); ?>
				</form>
		<?php
	}
}