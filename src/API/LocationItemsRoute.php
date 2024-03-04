<?php
namespace CBAppApi\API;

use CommonsBooking\Wordpress\CustomPostType\Map;
use CommonsBooking\Repository\Item;
use CommonsBooking\Map\MapShortcode;
use CommonsBooking\Map\MapData;
use CommonsBooking\Map\MapAdmin;
use CBAppApi\API\RestMapItemAvailable;
use CBAppApi\API\AppBaseRoute;
use CBAppApi\API\BookingStats;
use Exception;
use stdClass;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class LocationItemsRoute extends AppBaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'location_items';


	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$namespace = self::ROUTE_BASE . '/v' . self::ROUTE_VERSION;

		register_rest_route(
			$namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_items' ),
					'args'     => array(
						'availabilities' => array(
							'required'          => false,
							'validate_callback' => function ( $param, $request, $key ) {
								return ( $param === 'true' );
							},
							'sanitize_callback' => function ( $param, $request, $key ) {
								return sanitize_text_field( $param );
							},
						),
					),
				),
			)
		);
	}


	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$data = new stdClass();
		try {

			$mapID = $this->getMapID();

			$includeAvailabilities = array_key_exists( 'availabilities', $request->get_params() );
			$data->map             = $this->getMapLocations( $mapID, $includeAvailabilities );
			if ( method_exists( MapShortcode::class, 'get_settings' ) ) {
				$data->settings = MapShortcode::get_settings( $mapID );
			} else {
				$data->settings = MapData::get_settings( $mapID );
			}

			return new WP_REST_Response( $data, 200 );

		} catch ( Exception $ex ) {
			$data->code    = 'Data error';
			$data->message = $ex->getMessage();
			return new WP_REST_Response( $data, 400 );

		}
	}

	/**
	 * Retrieves locations (availabilities) for this map
	 *
	 * @param int $cb_map_id
	 * @param bool$includeAvailabilities should availabilities included
	 *
	 * @return array
	 *
	 */
	protected function getMapLocations( $cb_map_id, $includeAvailabilities = true ) {
		if ( method_exists( MapShortcode::class, 'get_settings' ) ) {
			$settings = MapShortcode::get_settings( $cb_map_id );
		} else {
			$settings = MapData::get_settings( $cb_map_id );
		}

		if ( method_exists( MapShortcode::class, 'getItemCategoryTerms' ) ) {
			$itemTerms = MapShortcode::getItemCategoryTerms( $settings );
		} else {
			$itemTerms = MapData::getItemCategoryTerms( $settings );
		}

		$default_date_start = $settings['filter_availability']['date_min'];
		$default_date_end   = $settings['filter_availability']['date_max'];
		// $itemTerms          = MapShortcode::getItemCategoryTerms( $settings );
		$locations = Map::get_locations( $cb_map_id, $itemTerms );

		//create availabilities
		if ( $includeAvailabilities ) {
			$show_item_availability        = MapAdmin::get_option( $cb_map_id, 'show_item_availability' );
			$show_item_availability_filter = MapAdmin::get_option( $cb_map_id, 'show_item_availability_filter' );

			if ( $show_item_availability || $show_item_availability_filter ) {
				$locations = RestMapItemAvailable::create_items_availabilities(
					$locations,
					$default_date_start,
					$default_date_end
				);
			}

			$locations = Map::cleanup_location_data( $locations, '<br>' );
			foreach ( $locations as $locationID => &$location ) {
				$modelLocation = new \CommonsBooking\Model\Location( $locationID );

				$location['formattedContactInfoOneLine']        = $modelLocation->formattedContactInfoOneLine();
				$location['formattedPickupInstructionsOneLine'] = $modelLocation->formattedPickupInstructionsOneLine();
				$location['formattedAddressOneLine']            = $modelLocation->formattedAddressOneLine();
				$location['description']                        = $modelLocation->post_content;

				foreach ( BookingStats::overbookingSetting( $locationID ) as $key => $value ) {
					$location[ $key ] = $value;
				}

				foreach ( $location['items'] as &$item ) {
					$locationItem        = Item::getPostById( $item['id'] );
					$item['description'] = $locationItem->post_content;
				}
			}
		}

		return $locations;
	}
}
