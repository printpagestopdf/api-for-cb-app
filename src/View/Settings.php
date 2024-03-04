<?php

namespace CBAppApi\View;

class Settings {

	protected static $kses_allowed_option = array(
		'option' => array(
			'value'    => true,
			'selected' => true,
			'name'     => true,
		),
	);

	public static function init() {
		add_action(
			'admin_menu',
			function () {

				add_submenu_page(
					'cb-dashboard',
					esc_html__( 'App API Settings', 'api-for-cb-app' ),
					esc_html__( 'App API Settings', 'api-for-cb-app' ),
					'manage_' . COMMONSBOOKING_PLUGIN_SLUG,
					'api-for-cb-app',
					array( self::class, 'options_page' ),
				);
			}
		);

		add_action(
			'admin_enqueue_scripts',
			function () {
				wp_enqueue_style( 'cbappapi__multiselect', CBAPPAPI_URI . 'assets/public/css/jquery.multiselect.css' );
				wp_enqueue_style( 'cbappapi__admin', CBAPPAPI_URI . 'assets/public/css/admin.css' );
				wp_enqueue_script( 'cbappapi__multiselect', CBAPPAPI_URI . 'assets/public/js/jquery.multiselect.js', array(), '1.0.0', true );
				wp_enqueue_script( 'cbappapi__admin', CBAPPAPI_URI . 'assets/public/js/admin.js', array( 'cbappapi__multiselect' ), '1.0.0', true );
			}
		);

		add_action( 'admin_init', array( self::class, 'settings_init' ) );
	}

	public static function settings_init() {

		register_setting( 'cbAppPluginPage', 'cb_app_settings' );

		add_settings_section(
			'cb_app_pluginPage_section',
			__( 'General', 'api-for-cb-app' ),
			array( self::class, 'settings_section_callback' ),
			'cbAppPluginPage'
		);

		add_settings_field(
			'cbappapi_select_maps',
			__( 'Map to use', 'api-for-cb-app' ),
			array( self::class, 'cbappapi__select_maps_render' ),
			'cbAppPluginPage',
			'cb_app_pluginPage_section',
			array( 'label_for' => 'cbappapi_select_maps' )
		);

		add_settings_field(
			'cbappapi_select_auth_variants',
			__( 'Application Passwords', 'api-for-cb-app' ),
			array( self::class, 'cbappapi__select_auth_variants_render' ),
			'cbAppPluginPage',
			'cb_app_pluginPage_section',
			array( 'label_for' => 'cbappapi_select_auth_variants' )
		);

		add_settings_field(
			'cbappapi_select_authroles',
			__( 'Roles allowed to login', 'api-for-cb-app' ),
			array( self::class, 'cbappapi__select_authroles_render' ),
			'cbAppPluginPage',
			'cb_app_pluginPage_section',
			array( 'label_for' => 'cbappapi_select_authroles' )
		);

		add_settings_field(
			'cb_field_deny_cors',
			__( 'Deny images for Web App (CORS)', 'api-for-cb-app' ),
			array( self::class, 'cb_field_deny_cors_render' ),
			'cbAppPluginPage',
			'cb_app_pluginPage_section',
			array( 'label_for' => 'cb_field_deny_cors' )
		);

		register_setting( 'cbAppPluginPage', 'cb_app_booking_restrictions' );

		add_settings_section(
			'cb_app_booking_restriction_section',
			__( '<hr />Booking restrictions', 'api-for-cb-app' ),
			array( self::class, 'booking_section_callback' ),
			'cbAppBookingSection'
		);

		add_settings_field(
			'cbappapi__txt_max_days_week',
			__( 'Max. days per week', 'api-for-cb-app' ),
			array( self::class, 'cbappapi__txt_max_days_week_render' ),
			'cbAppBookingSection',
			'cb_app_booking_restriction_section',
			array( 'label_for' => 'cbappapi__txt_max_days_week' )
		);

		add_settings_field(
			'cbappapi__txt_max_days_month',
			__( 'Max. days per month', 'api-for-cb-app' ),
			array( self::class, 'cbappapi__txt_max_days_month_render' ),
			'cbAppBookingSection',
			'cb_app_booking_restriction_section',
			array( 'label_for' => 'cbappapi__txt_max_days_month' )
		);

		add_settings_field(
			'cbappapi__txt_max_bookings_week',
			__( 'Max. bookings per week', 'api-for-cb-app' ),
			array( self::class, 'cbappapi__txt_max_bookings_week_render' ),
			'cbAppBookingSection',
			'cb_app_booking_restriction_section',
			array( 'label_for' => 'cbappapi__txt_max_bookings_week' )
		);

		add_settings_field(
			'cbappapi__txt_max_bookings_month',
			__( 'Max. bookings per month', 'api-for-cb-app' ),
			array( self::class, 'cbappapi__txt_max_bookings_month_render' ),
			'cbAppBookingSection',
			'cb_app_booking_restriction_section',
			array( 'label_for' => 'cbappapi__txt_max_bookings_month' )
		);
	}

	public static function cbappapi__txt_max_bookings_month_render() {
		$options = get_option( 'cb_app_booking_restrictions' );
		?>
		<input class="num_input" type="number" min="0" max="31" inputmode="numeric" pattern="\d*" name='cb_app_booking_restrictions[max_bookings_month]' value='<?php echo esc_attr( $options['max_bookings_month'] ); ?>'>
		<?php
	}

	public static function cbappapi__txt_max_bookings_week_render() {
		$options = get_option( 'cb_app_booking_restrictions' );
		?>
		<input class="num_input" type="number" min="0" max="7" inputmode="numeric" pattern="\d*" name='cb_app_booking_restrictions[max_bookings_week]' value='<?php echo esc_attr( $options['max_bookings_week'] ); ?>'>
		<?php
	}

	public static function cbappapi__txt_max_days_month_render() {
		$options = get_option( 'cb_app_booking_restrictions' );
		?>
		<input class="num_input" type="number" min="0" max="31" inputmode="numeric" pattern="\d*" name='cb_app_booking_restrictions[max_days_month]' value='<?php echo esc_attr( $options['max_days_month'] ); ?>'>
		<?php
	}

	public static function cbappapi__txt_max_days_week_render() {
		$options = get_option( 'cb_app_booking_restrictions' );
		?>
		<input class="num_input" type="number" min="0" max="7" inputmode="numeric" pattern="\d*" name='cb_app_booking_restrictions[max_days_week]' value='<?php echo esc_attr( $options['max_days_week'] ); ?>'>
		<?php
	}

	public static function cb_field_deny_cors_render() {
		$options = get_option( 'cb_app_settings', array() );
		$denied  = array_key_exists( 'cb_field_deny_cors', $options ) ? $options['cb_field_deny_cors'] : 'allowed';
		?>
	<input type="checkbox" name="cb_app_settings[cb_field_deny_cors]" value="denied"<?php checked( 'denied', $denied ); ?> />
	<div><span class='description'><?php esc_html_e( 'If checked, Web Apps are denied to retrieve item images (if CORS restricts it)', 'api-for-cb-app' ); ?></span></div>

		<?php
	}

	public static function cbappapi__select_auth_variants_render() {
		$options     = get_option( 'cb_app_settings', array() );
		$authvariant = array_key_exists( 'cbappapi_select_auth_variants', $options ) ? $options['cbappapi_select_auth_variants'] : 'system';
		?>
		<select name='cb_app_settings[cbappapi_select_auth_variants]' id='cbappapi_select_auth_variants'>
			<option value='system' <?php selected( 'system', $authvariant ); ?> ><?php esc_html_e( 'Use system setting', 'api-for-cb-app' ); ?></option>
			<option value='all'  <?php selected( 'all', $authvariant ); ?> ><?php esc_html_e( 'Allow all', 'api-for-cb-app' ); ?></option>
			<option value='restrict'  <?php selected( 'restrict', $authvariant ); ?> ><?php esc_html_e( 'Restrict to Roles', 'api-for-cb-app' ); ?></option>
			<option value='forbidden'  <?php selected( 'forbidden', $authvariant ); ?> ><?php esc_html_e( 'Forbidden for all', 'api-for-cb-app' ); ?></option>
		</select>
		<div><span class='description'><?php esc_html_e( 'Allows/denies users to use an Application Password for App authentication (e.g. for Booking)', 'api-for-cb-app' ); ?></span></div>
		<?php
	}

	public static function cbappapi__select_authroles_render() {
			global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = wp_roles();
		}

		$options   = get_option( 'cb_app_settings' );
		$authroles = ( $options !== false && array_key_exists( 'cbappapi_select_authroles', $options ) ) ? $options['cbappapi_select_authroles'] : array( 'administrator', 'subscriber', 'cb_manager' );
		?>
		<input type="hidden" name="cb_app_settings[cbappapi_select_authroles][]" value="dummy_for_empty_list">
		<select name="dont_save_me" multiple id="cbappapi_select_authroles">
		<?php
		foreach ( $wp_roles->roles as $key => $role ) {
			$selected = in_array( $key, $authroles ) ? ' selected ' : '';
			echo wp_kses( "<option value='{$key}' {$selected} name='cb_app_settings[cbappapi_select_authroles][]'>{$role['name']}</option>", self::$kses_allowed_option );
		}

		?>
				</select>
		<div><span class='description'><?php esc_html_e( 'Roles that are allowed to use Application Password', 'api-for-cb-app' ); ?></span></div>
		<?php
	}

	public static function options_page() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// check if the user have submitted the settings
		// WordPress will add the "settings-updated" $_GET parameter to the url
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( 'cb_app_messages', 'cb_app_message_updated', __( 'Settings Saved', 'api-for-cb-app' ), 'updated' );
		}

		// show error/update messages
		settings_errors( 'cb_app_messages' );

		?>
	<div class="wrap">

		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action='options.php' method='post'>

		<?php
			settings_fields( 'cbAppPluginPage' );
			do_settings_sections( 'cbAppPluginPage' );

			do_settings_sections( 'cbAppBookingSection' );

			submit_button();
		?>

		</form>
		<?php
	}

	public static function cbappapi__select_maps_render() {

		$maps = get_posts(
			array(
				'numberposts' => -1,
				'orderby'     => 'ID',
				'order'       => 'ASC',
				'post_type'   => 'cb_map',
				'post_status' => 'publish',
			)
		);

		if ( empty( $maps ) ) {
			?>
			<div style='color: red;'>No maps found, please configure a map</div>
			<?php
			return;
		}

		$options = get_option( 'cb_app_settings', array( 'cbappapi_select_maps' => $maps[0]->ID ) );
		?>
		<select name='cb_app_settings[cbappapi_select_maps]' id='cbappapi_select_maps'>
		<?php
		foreach ( $maps as $map ) {
			$selected = selected( $options['cbappapi_select_maps'], $map->ID, false );
			echo wp_kses( "<option value='{$map->ID}' {$selected} >{$map->post_title}</option>", self::$kses_allowed_option );
		}
		?>
				</select>
		<div><span class='description'><?php esc_html_e( 'Select CB Map thats configuration should be used for App', 'api-for-cb-app' ); ?></span></div>
		<?php
	}


	public static function settings_section_callback() {

		esc_html_e( 'Settings for the CB App API', 'api-for-cb-app' );
	}

	public static function booking_section_callback() {
		esc_html_e( 'Per User booking restrictions', 'api-for-cb-app' );
	}
}

?>
