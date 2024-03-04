<?php
/**
 * Plugin Name:         API for CB App
 * Version:             0.5.0
 * Requires at least:   5.2
 * Tested up to:        6.4.2
 * Requires PHP:        7.4
 * Plugin URI:          https://printpagestopdf.github.io/cb_app/
 * Description:         Adds REST Api to Commons Booking to support App.
 * Author:              The Ripper
 * Author URI:          https://profiles.wordpress.org/theripper
 * Domain Path:         /languages/
 * Text Domain:         api-for-cb-app
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 *
 *
 * Copyright (C) 2024 The Ripper
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */

namespace CBAppApi;

use CBAppApi\Plugin;

defined( 'ABSPATH' ) || die( 'Thanks for visting' );


add_action(
	'plugins_loaded',
	function () {
		if ( defined( 'COMMONSBOOKING_PLUGIN_SLUG' ) ) {
			define( 'CBAPPAPI_URI', plugin_dir_url( __FILE__ ) );
			define( 'CBAPPAPI_PLUGIN_SLUG', 'commonsbooking' );

			load_plugin_textdomain( 'api-for-cb-app', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

			$options     = get_option( 'cb_app_settings', array() );
			$authvariant = array_key_exists( 'cbappapi_select_auth_variants', $options ) ? $options['cbappapi_select_auth_variants'] : 'system';

			switch ( $authvariant ) {
				case 'restrict':
					add_filter(
						'wp_is_application_passwords_available_for_user',
						function ( $available, $user ) use ( $options ) {
							$roles     = (array) $user->roles;
							$authroles = array_key_exists( 'cbappapi_select_authroles', $options ) ? $options['cbappapi_select_authroles'] : array( 'administrator', 'subscriber', 'cb_manager' );
							$available = ! ( count( array_intersect( $roles, $authroles ) ) === 0 );
							return $available;
						},
						10,
						2
					);
					break;
				case 'all':
					add_filter( 'wp_is_application_passwords_available', '__return_true' );
					break;
				case 'forbidden':
					add_filter( 'wp_is_application_passwords_available', '__return_false' );
					break;
				case 'system':
				default:
					break;
			}

			spl_autoload_register(
				function ( $local_class ) {
					// project-specific namespace prefix
					$prefix = 'CBAppApi\\';

					// base directory for the namespace prefix
					$base_dir = __DIR__ . '/src/';

					// does the class use the namespace prefix?
					$len = strlen( $prefix );
					// if (strncmp($prefix, $class, $len) !== 0) {
					if ( strpos( $local_class, $prefix ) !== 0 ) {
						// no, move to the next registered autoloader
						return;
					}

					// get the relative class name
					$relative_class = substr( $local_class, $len );

					// replace the namespace prefix with the base directory, replace namespace
					// separators with directory separators in the relative class name, append
					// with .php
					$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

					// if the file exists, require it
					if ( file_exists( $file ) ) {
						require $file;
					}
				}
			);

			Plugin::run();
		}
	}
);
