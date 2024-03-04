<?php
namespace CBAppApi\API;

use CBAppApi\API\AppBaseRoute;
use stdClass;
use WP_REST_Request;
use WP_REST_Response;
use CBAppApi\API\RestBooking;
use CBAppApi\API\RestBookingList;
use CommonsBooking\Wordpress\CustomPostType\Booking;

class BookingsRoute extends AppBaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'bookings';


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
					'methods'             => 'POST',
					'callback'            => array( $this, 'booking_new_update' ),
					'args'                => array(),
					'permission_callback' => function () {
						return self::loggedInAppPasswordAllowed();
					},
				),
			)
		);

		register_rest_route(
			$namespace,
			'/' . $this->rest_base . '/(?P<booking_id>[\d]+)',
			array(
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'booking_new_update' ),
					'args'                => array(
						'booking_id' => array(
							'required' => true,
						),
					),
					'permission_callback' => function () {
						return self::loggedInAppPasswordAllowed();
					},
				),
			)
		);

		register_rest_route(
			$namespace,
			'/' . $this->rest_base . '/(?P<booking_name>.+)',
			array(
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'booking_new_update' ),
					'args'                => array(
						'booking_name' => array(
							'required' => true,
						),
					),
					'permission_callback' => function () {
						return self::loggedInAppPasswordAllowed();
					},
				),
			)
		);

		register_rest_route(
			$namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_items' ),
					'args'                => array(),
					'permission_callback' => function () {
						return self::loggedInAppPasswordAllowed();
					},
				),
			)
		);

		register_rest_route(
			$namespace,
			'/' . $this->rest_base . '/booking_stats',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'booking_stats' ),
					'args'                => array(),
					'permission_callback' => function () {
						return self::loggedInAppPasswordAllowed();
					},
				),
			)
		);
	}


	/**
	 * Get a collection of booking items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$data          = new stdClass();
		$response_code = 200;

		$bookingList = RestBookingList::getBookingListData( $request->get_params() );
		if ( $bookingList === false ) {
			$response_code = 400;
			$data->code    = 'Data error';
			$data->message = __( 'Error creating booking list', 'api-for-cb-app' );
			$data->data    = new stdClass();
		} else {
			$data->code    = 'Bookinglist successfully retrieved';
			$data->message = __( 'Booking list retrieved', 'api-for-cb-app' );
			$data->data    = $bookingList;
		}

		return new WP_REST_Response( $data, $response_code );
	}

	public function booking_new_update( $request ) {
		$data = new stdClass();

		try {
			if ( method_exists( Booking::class, 'handleFormRequest' ) ) {
				$rb = $this->static_booking_new_update( $request->get_params() );
			} else {
				$rb = new RestBooking( $request->get_params() );
			}
			$data->code    = 'booking successfull';
			$data->message = __( 'Booking/Update successfull', 'api-for-cb-app' );
			return new WP_REST_Response( $data, 200 );
		} catch ( \Exception $ex ) {
			$data->code    = 'Data error';
			$data->message = $ex->getMessage();
			return new WP_REST_Response( $data, 400 );
		}
	}

	protected function static_booking_new_update( $params ) {
		$itemId      = isset( $params['item-id'] ) && $params['item-id'] != '' ? sanitize_text_field( $params['item-id'] ) : null;
		$locationId  = isset( $params['location-id'] ) && $params['location-id'] != '' ? sanitize_text_field( $params['location-id'] ) : null;
		$comment     = isset( $params['comment'] ) && $params['comment'] != '' ? sanitize_text_field( $params['comment'] ) : null;
		$post_status = isset( $params['post_status'] ) && $params['post_status'] != '' ? sanitize_text_field( $params['post_status'] ) : null;
		$post_ID     = isset( $params['booking_id'] ) && $params['booking_id'] != '' ? sanitize_text_field( $params['booking_id'] ) : null;
		$postType    = isset( $params['type'] ) ? sanitize_text_field( wp_unslash( $params['type'] ) ) : null;
		$postName    = isset( $params['booking_name'] ) ? sanitize_text_field( wp_unslash( $params['booking_name'] ) ) : null;

		if ( ! get_post( $itemId ) ) {
			throw new \Exception( esc_html( 'Item does not exist. (' . $itemId . ')' ) );
		}
		if ( ! get_post( $locationId ) ) {
			throw new \Exception( esc_html( 'Location does not exist. (' . $locationId . ')' ) );
		}

		$repetitionStart = null;
		if ( isset( $params['repetition-start'] ) && $params['repetition-start'] != '' ) {
			$repetitionStart = sanitize_text_field( $params['repetition-start'] );
		}

		$repetitionEnd = null;
		if (
			isset( $params[ \CommonsBooking\Model\Timeframe::REPETITION_END ] ) &&
			$params[ \CommonsBooking\Model\Timeframe::REPETITION_END ] != ''
		) {
			$repetitionEnd = sanitize_text_field( $params[ \CommonsBooking\Model\Timeframe::REPETITION_END ] );
		}

		$isNew = ( empty( $post_ID ) && empty( $postName ) && $post_status == 'confirmed' );

		$postId = Booking::handleBookingRequest(
			$itemId,
			$locationId,
			$isNew ? 'unconfirmed' : $post_status,
			$post_ID,
			$comment,
			$repetitionStart,
			$repetitionEnd,
			$postName,
			$postType
		);

		if ( $isNew ) {
			// notification only fired on update, comment only set on update
			$postId = Booking::handleBookingRequest(
				$itemId,
				$locationId,
				$post_status,
				$postId,
				$comment,
				$repetitionStart,
				$repetitionEnd,
				$postName,
				$postType
			);

		}

		return $postId;
	}

	public function booking_stats( $request ) {
		// wp_set_current_user(4177, "PetrMaier");
		// wp_set_current_user(4178, "halligalli");

		$mapID = $this->getMapID();

		$result = \CBAppApi\API\BookingStats::getUserBookingStats( $mapID );

		return new WP_REST_Response( $result, 200 );
	}
}
