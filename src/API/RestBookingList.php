<?php

namespace CBAppApi\API;

use Exception;

/* Based on CommonsBooking\View\Booking; */
class RestBookingList {

	/**
	 * @return array|false|mixed
	 * @throws Exception
	 */
	public static function getBookingListData( $params ) {
		$postsPerPage = 0; //without paging
		if ( array_key_exists( 'posts_per_page', $params ) ) {
			$postsPerPage = sanitize_text_field( $params['posts_per_page'] );
		}

		$page = 1;
		if ( array_key_exists( 'page', $params ) ) {
			$page = sanitize_text_field( $params['page'] );
		}

		$search = false;
		if ( array_key_exists( 'search', $params ) ) {
			$search = sanitize_text_field( $params['search'] );
		}

		$sort = 'startDate';
		if ( array_key_exists( 'sort', $params ) ) {
			$sort = sanitize_text_field( $params['sort'] );
		}

		$order = 'asc';
		if ( array_key_exists( 'order', $params ) ) {
			$order = sanitize_text_field( $params['order'] );
		}

		$filters = array(
			'location'  => false,
			'item'      => false,
			'user'      => false,
			'startDate' => time(),
			'endDate'   => false,
			'status'    => false,
		);

		foreach ( $filters as $key => $value ) {
			if ( array_key_exists( $key, $params ) ) {
				$filters[ $key ] = sanitize_text_field( $params[ $key ] );
			}
		}

		$bookingDataArray             = array();
		$bookingDataArray['page']     = $page;
		$bookingDataArray['per_page'] = $postsPerPage;
		$bookingDataArray['filters']  = array(
			'user'     => array(),
			'item'     => array(),
			'location' => array(),
			'status'   => array(),
		);

		$posts = \CommonsBooking\Repository\Booking::getForCurrentUser(
			true,
			$filters['startDate'] ?: null
		);

		if ( ! $posts ) {
			return array();// false;
		}

		// Prepare Templatedata and remove invalid posts
		foreach ( $posts as $booking ) {

			// Get user infos
			$userInfo = get_userdata( $booking->post_author );

			// Decide which edit link to use
			$editLink = get_permalink( $booking->ID );

			$actions = '<a class="cb-button small" href="' . $editLink . '">' .
						commonsbooking_sanitizeHTML( __( 'Details', 'api-for-cb-app' ) ) .
						'</a>';

			$item          = $booking->getItem();
			$item_id       = $item ? $item->ID : '';
			$itemTitle     = $item ? $item->post_title : commonsbooking_sanitizeHTML( __( 'Not available', 'api-for-cb-app' ) );
			$itemThumbnail = $item ? get_the_post_thumbnail_url( $item->ID ) : '';
			$location      = $booking->getLocation();
			$location_id   = $location ? $location->ID : '';
			if ( $location ) {
				$locationTitle                              = $location->post_title;
				$locationFormattedContactInfoOneLine        = $location->formattedContactInfoOneLine();
				$locationFormattedPickupInstructionsOneLine = $location->formattedPickupInstructionsOneLine();
				$locationFormattedAddressOneLine            = $location->formattedAddressOneLine();
			} else {
				$locationTitle                              = $locationFormattedContactInfoOneLine =
				$locationFormattedPickupInstructionsOneLine = $locationFormattedAddressOneLine = commonsbooking_sanitizeHTML( __( 'Not available', 'api-for-cb-app' ) );
			}

			// Prepare row data
			$rowData = array(
				'booking_id'                         => $booking->ID,
				'startDate'                          => $booking->getStartDate(),
				'endDate'                            => $booking->getEndDate(),
				'startDateFormatted'                 => gmdate( 'd.m.Y H:i', $booking->getStartDate() ),
				'endDateFormatted'                   => gmdate( 'd.m.Y H:i', $booking->getEndDate() ),
				'item'                               => $itemTitle,
				'item_id'                            => $item_id,
				'item_thumbnail'                     => $itemThumbnail,
				'location'                           => $locationTitle,
				'location_id'                        => $location_id,
				'formattedContactInfoOneLine'        => $locationFormattedContactInfoOneLine,
				'formattedPickupInstructionsOneLine' => $locationFormattedPickupInstructionsOneLine,
				'formattedAddressOneLine'            => $locationFormattedAddressOneLine,
				'bookingDate'                        => gmdate( 'd.m.Y H:i', strtotime( $booking->post_date ) ),
				'user'                               => $userInfo->user_login,
				'status'                             => $booking->post_status,
				'comment'                            => $booking->returnComment(),
				'content'                            => array(
					'user'   => array(
						'label' => commonsbooking_sanitizeHTML( __( 'User', 'api-for-cb-app' ) ),
						'value' => $userInfo->first_name . ' ' . $userInfo->last_name . ' (' . $userInfo->user_login . ')',
					),
					'status' => array(
						'label' => commonsbooking_sanitizeHTML( __( 'Status', 'api-for-cb-app' ) ),
						'value' => $booking->post_status,
					),
				),
			);

			// Add booking code if there is one
			if ( $booking->getBookingCode() ) {
				$rowData['bookingCode'] = array(
					'label' => commonsbooking_sanitizeHTML( __( 'Code', 'api-for-cb-app' ) ),
					'value' => $booking->getBookingCode(),
				);
			}

			$continue = false;
			foreach ( $filters as $key => $value ) {
				if ( $value ) {
					if ( ! in_array( $key, array( 'startDate', 'endDate' ) ) ) {
						if ( $rowData[ $key ] != $value ) {
							$continue = true;
						}
					} elseif (
							( $key == 'startDate' && $value > intval( $booking->getEndDate() ) ) ||
							( $key == 'endDate' && $value < intval( $booking->getStartDate() ) )
						) {

							$continue = true;
					}
				}
			}
			if ( $continue ) {
				continue;
			}

			foreach ( array_keys( $bookingDataArray['filters'] ) as $key ) {
				$bookingDataArray['filters'][ $key ][] = $rowData[ $key ];
			}

			// If search term was submitted, filter for it.
			if ( ! $search || count( preg_grep( '/.*' . $search . '.*/i', $rowData ) ) > 0 ) {
				$rowData['actions']         = $actions;
				$bookingDataArray['data'][] = apply_filters( 'commonsbooking_booking_filter', $rowData, $booking );
			}
		}

		$bookingDataArray['total']       = 0;
		$bookingDataArray['total_pages'] = 0;

		if ( array_key_exists( 'data', $bookingDataArray ) && count( $bookingDataArray['data'] ) ) {
			$totalCount                      = count( $bookingDataArray['data'] );
			$bookingDataArray['total']       = $totalCount;
			$bookingDataArray['total_pages'] = $postsPerPage > 0 ? ceil( $totalCount / $postsPerPage ) : 1;

			foreach ( $bookingDataArray['filters'] as &$filtervalues ) {
				$filtervalues = array_unique( $filtervalues );
				sort( $filtervalues );
			}

			// Init function to pass sort and order param to sorting callback
			$sorter = function ( $sort, $order ) {
				return function ( $a, $b ) use ( $sort, $order ) {
					if ( $order == 'asc' ) {
						return strcasecmp( $a[ $sort ], $b[ $sort ] );
					} else {
						return strcasecmp( $b[ $sort ], $a[ $sort ] );
					}
				};
			};

			// Sorting
			uasort( $bookingDataArray['data'], $sorter( $sort, $order ) );

			if ( $postsPerPage > 0 ) {
				// Apply pagination...
				$index       = 0;
				$pageCounter = 0;

				$offset = ( $page - 1 ) * $postsPerPage;

				foreach ( $bookingDataArray['data'] as $key => $post ) {
					if ( $offset > $index++ ) {
						unset( $bookingDataArray['data'][ $key ] );
						continue;
					}
					if ( $postsPerPage && $postsPerPage <= $pageCounter++ ) {
						unset( $bookingDataArray['data'][ $key ] );
					}
				}
			}
			$bookingDataArray['data'] = array_values( $bookingDataArray['data'] );

			return $bookingDataArray;
		}
	}
}
