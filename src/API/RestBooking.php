<?php

namespace CBAppApi\API;

use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Helper\Helper;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class RestBooking extends Booking {
	public $params = array();

	public function __construct( $params ) {
		$this->params = $params;
		parent::__construct();
	}

	/**
	 * Handles frontend save-Request for timeframe.
	 * @throws Exception
	 */
	public function handleFormRequest() {

		$itemId      = isset( $this->params['item-id'] ) && $this->params['item-id'] != '' ? sanitize_text_field( $this->params['item-id'] ) : null;
		$locationId  = isset( $this->params['location-id'] ) && $this->params['location-id'] != '' ? sanitize_text_field( $this->params['location-id'] ) : null;
		$comment     = isset( $this->params['comment'] ) && $this->params['comment'] != '' ? sanitize_text_field( $this->params['comment'] ) : null;
		$post_status = isset( $this->params['post_status'] ) && $this->params['post_status'] != '' ? sanitize_text_field( $this->params['post_status'] ) : null;
		$booking_id  = isset( $this->params['booking_id'] ) && $this->params['booking_id'] != '' ? sanitize_text_field( $this->params['booking_id'] ) : null;

		if ( ! get_post( $itemId ) ) {
			throw new \Exception( esc_html( 'Item does not exist. (' . $itemId . ')' ) );
		}
		if ( ! get_post( $locationId ) ) {
			throw new \Exception( esc_html( 'Location does not exist. (' . $locationId . ')' ) );
		}

		$startDate = null;
		if ( isset( $this->params['repetition-start'] ) && $this->params['repetition-start'] != '' ) {
			$startDate = sanitize_text_field( $this->params['repetition-start'] );
		}

		$endDate = null;
		if (
			isset( $this->params[ \CommonsBooking\Model\Timeframe::REPETITION_END ] ) &&
			$this->params[ \CommonsBooking\Model\Timeframe::REPETITION_END ] != ''
		) {
			$endDate = sanitize_text_field( $this->params[ \CommonsBooking\Model\Timeframe::REPETITION_END ] );
		}

		if ( $startDate == null || $endDate == null ) {
			throw new \Exception( 'Start- and/or enddate missing.' );
		}

		// Validate booking -> check if there are no existing bookings in timerange.
		if (
			( $existingBookings =
				\CommonsBooking\Repository\Booking::getByTimerange(
					$startDate,
					$endDate,
					$locationId,
					$itemId,
					array(),
					array( 'confirmed' )
				))
		) {
			if ( count( $existingBookings ) > 0 ) {
				$requestedPostname = array_key_exists( 'cb_booking', $this->params ) ? $this->params['cb_booking'] : '';

				// checks if it's an edit, but ignores exact start/end time
				$isEdit = count( $existingBookings ) === 1 &&
					array_values( $existingBookings )[0]->getPost()->post_name === $requestedPostname &&
					array_values( $existingBookings )[0]->getPost()->post_author == get_current_user_id();

				if ( ( ! $isEdit || count( $existingBookings ) > 1 ) && $post_status != 'canceled' ) {
					throw new Exception( esc_html__( 'There is already a booking in this timerange.', 'api-for-cb-app' ) );
				}
			}
		}

		//if booking_id is available use existing booking
		$booking = ( $booking_id ) ? \CommonsBooking\Repository\Booking::getPostById( $booking_id ) : null;

		$requestedPostStatus = sanitize_text_field( $this->params['post_status'] );
		$postarr             = array(
			'type'       => sanitize_text_field( $this->params['type'] ),
			'post_type'  => self::getPostType(),
			'post_title' => esc_html__( 'Booking', 'api-for-cb-app' ),
			'meta_input' => array(
				'comment' => $comment,
			),
		);

		// New booking
		if ( empty( $booking ) ) {
			$postarr['post_status'] = ( $requestedPostStatus == 'confirmed' ) ? 'unconfirmed' : $requestedPostStatus;
			$postarr['post_name']   = Helper::generateRandomString();
			$postarr['meta_input']  = array(
				'location-id'      => $locationId,
				'item-id'          => $itemId,
				'repetition-start' => $startDate,
				'repetition-end'   => $endDate,
				'type'             => Timeframe::BOOKING_ID,
				'comment'          => $comment,
			);
			$postId                 = wp_insert_post( $postarr, true );

			//Force post_updated hook
			if ( $requestedPostStatus == 'confirmed' ) {
				wp_update_post(
					array(
						'ID'          => $postId,
						'post_status' => 'confirmed',
					)
				);
			}
			// Existing booking
		} else {
			$postarr['ID']          = $booking->ID;
			$postarr['post_status'] = $requestedPostStatus;
			$postId                 = wp_update_post( $postarr );
		}

		$this->saveGridSizes( $postId, $locationId, $itemId, $startDate, $endDate );

		$bookingModel = new \CommonsBooking\Model\Booking( $postId );
		// we need some meta-fields from bookable-timeframe, so we assign them here to the booking-timeframe
		$bookingModel->assignBookableTimeframeFields();

		// get slug as parameter
		$post_slug = get_post( $postId )->post_name;

		// wp_redirect( add_query_arg( self::getPostType(), $post_slug, home_url() ) );
		// exit;
	}
	/**
	 * Multi grid size
	 * We need to save the grid size for timeframes with full slot grid.
	 *
	 * @param $postId
	 * @param $locationId
	 * @param $itemId
	 * @param $startDate
	 * @param $endDate
	 */
	private function saveGridSizes( $postId, $locationId, $itemId, $startDate, $endDate ): void {
		$startTimeFrame = \CommonsBooking\Repository\Timeframe::getByLocationItemTimestamp( $locationId, $itemId, $startDate );
		if ( $startTimeFrame && $startTimeFrame->getGrid() == 0 ) {
			update_post_meta(
				$postId,
				\CommonsBooking\Model\Booking::START_TIMEFRAME_GRIDSIZE,
				$startTimeFrame->getGridSize()
			);
		}
		$endTimeFrame = \CommonsBooking\Repository\Timeframe::getByLocationItemTimestamp( $locationId, $itemId, $endDate );
		if ( $endTimeFrame && $endTimeFrame->getGrid() == 0 ) {
			update_post_meta(
				$postId,
				\CommonsBooking\Model\Booking::END_TIMEFRAME_GRIDSIZE,
				$endTimeFrame->getGridSize()
			);
		}
	}
}
