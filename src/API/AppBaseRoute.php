<?php
namespace CBAppApi\API;

use CommonsBooking\API\BaseRoute;
use Exception;


class AppBaseRoute extends BaseRoute {

	const ROUTE_BASE    = 'cbappapi';
	const ROUTE_VERSION = '1';

	/**
	 * Returns true if current user is logged in and allowed to use app Passwords.
	 * @return bool
	 */
	protected static function loggedInAppPasswordAllowed() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		return self::appPasswordAllowed();
	}


	/**
	 * Returns true if current user is allowed to use app Passwords.
	 *
	 * @param optional WP_User (will used instead of wp_get_current_user).
	 *
	 * @return bool
	 */
	protected static function appPasswordAllowed( $user = null ) {

		$options     = get_option( 'cb_app_settings', array() );
		$authvariant = array_key_exists( 'cbappapi_select_auth_variants', $options ) ? $options['cbappapi_select_auth_variants'] : 'system';
		$result      = false;
		switch ( $authvariant ) {
			case 'restrict':
				// if(!is_user_logged_in()) return false;
				if ( null === $user ) {
					$user = wp_get_current_user();
				}
				if ( 0 === $user->ID ) {
					return false;
				}
				$roles     = (array) $user->roles;
				$authroles = array_key_exists( 'cbappapi_select_authroles', $options ) ? $options['cbappapi_select_authroles'] : array( 'administrator', 'subscriber', 'cb_manager' );
				$result    = ! ( count( array_intersect( $roles, $authroles ) ) === 0 );
				break;
			case 'all':
				$result = true;
				break;
			case 'forbidden':
				$result = false;
				break;
			case 'system':
			default:
				$result = true;
				break;
		}

		return $result;
	}

	/**
	 * Get the map to use
	 *
	 *
	 * @return int
	 * @throws \Exception
	 */
	protected function getMapID() {
		$options = get_option( 'cb_app_settings' );

		if ( is_array( $options ) && array_key_exists( 'cbappapi_select_maps', $options ) ) {
			return (int) $options['cbappapi_select_maps'];
		}

		$maps = get_posts(
			array(
				'numberposts' => -1,
				'orderby'     => 'ID',
				'order'       => 'ASC',
				'post_type'   => 'cb_map',
				'post_status' => 'publish',
			)
		);

		if ( ! empty( $maps ) ) {
			return (int) $maps[0]->ID;
		} else {
			throw new Exception( esc_html__( 'No maps configured!', 'api-for-cb-app' ) );
		}
	}
}
