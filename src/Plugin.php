<?php

namespace CBAppApi;

use CBAppApi\API\LocationItemsRoute;
use CBAppApi\API\AppUtilsRoute;
use CBAppApi\API\BookingsRoute;
use CBAppApi\View\Settings;

class Plugin {
	public static function run() {
		add_action(
			'rest_api_init',
			function () {
				$routes = array(
					new LocationItemsRoute(),
					new AppUtilsRoute(),
					new BookingsRoute(),
				);
				foreach ( $routes as $route ) {
					$route->register_routes();
				}
			}
		);

		if ( is_admin() && commonsbooking_isCurrentUserAdmin() ) {
			Settings::init();
		}
	}
}
