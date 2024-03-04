<?php

namespace CBAppApi\API;

use CommonsBooking\Model\Day;
use CommonsBooking\View\Calendar;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;

/**
 * copy of CommonsBooking\Map\MapItemAvailable
 * but adds maxDays to $item
 */
class RestMapItemAvailable {


	/**
	 * item is available
	 */
	const ITEM_AVAILABLE = 'available';


	/**
	 * location closed because of holiday / official holiday
	 */
	const LOCATION_HOLIDAY = 'location-holiday';

	/**
	 * item is partially booked
	 */
	const ITEM_PARTIALLY_BOOKED = 'partially-booked';

	/**
	 * item is partially locked
	 */
	const ITEM_LOCKED = 'locked';


	/**
	 * item is booked or blocked
	 */
	const ITEM_BOOKED = 'booked';

	/**
	 * no timeframe for item set
	 */
	const OUT_OF_TIMEFRAME = 'no-timeframe';

	/**
	 * @param $locations
	 * @param $date_start
	 * @param $date_end
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function create_items_availabilities( $locations, $date_start, $date_end ) {

		$startDayRequest = new Day( $date_start );
		$endDayRequest   = new Day( $date_end );

		$filter_period = new DatePeriod(
			new DateTime( $date_start ),
			new DateInterval( 'P1D' ),
			new DateTime( $date_end . ' +1 day' )
		);

		foreach ( $locations as $location_id => &$location ) {

			foreach ( $location['items'] as &$item ) {

				$bookableTimeframes = \CommonsBooking\Repository\Timeframe::getBookableForCurrentUser(
					array( $location_id ),
					array( $item['id'] ),
					null,
					true,
					\CommonsBooking\Helper\Helper::getLastFullHourTimestamp()
				);

				if ( count( $bookableTimeframes ) ) {
					$closestBookableTimeframe = self::getClosestBookableTimeFrameForToday( $bookableTimeframes );
					$advanceBookingDays       = intval( $closestBookableTimeframe->getFieldValue( 'timeframe-advance-booking-days' ) );
					$firstBookableDay         = $closestBookableTimeframe->getFirstBookableDay();
					$startDay                 = new Day( $firstBookableDay );

					$latestPossibleBookingDateTimestamp = $closestBookableTimeframe->getLatestPossibleBookingDateTimestamp();
					$endDay                             = new Day( gmdate( 'Y-m-d', $latestPossibleBookingDateTimestamp ) );

				} else {
					$startDay         = $startDayRequest;
					$endDay           = $endDayRequest;
					$firstBookableDay = $latestPossibleBookingDateTimestamp = $advanceBookingDays = null;
				}

				$calendarData = Calendar::prepareJsonResponse(
					$startDay,
					$endDay,
					array( $location_id ),
					array( $item['id'] ),
					$advanceBookingDays, //60,
					$latestPossibleBookingDateTimestamp, //1706101369,
					$firstBookableDay
				);

				//mark days in timeframe
				$availability = self::buildAvailability( $calendarData, $latestPossibleBookingDateTimestamp );

				$item['availability'] = $availability;

				$item['maxDays'] = @$calendarData['maxDays'];

			}
		}

		return $locations;
	}

	/**
	 * @param $calendarData
	 * @param $maxTimestamp
	 *
	 * @return mixed
	 */
	protected static function buildAvailability( $calendarData, $maxTimestamp ) {
		$availabilities = array();
		//mark days which are inside a timeframe
		if ( array_key_exists( 'days', $calendarData ) ) {
			foreach ( $calendarData['days'] as $strDate => $day ) {
				if ( $maxTimestamp != null && strtotime( $strDate ) > $maxTimestamp ) {
					continue;
				}
				$availability = array( 'date' => $strDate );
				if ( ! count( $day['slots'] ) ) {
					$availability['status'] = self::ITEM_LOCKED;
				} elseif ( $day['holiday'] ) {
					$availability['status'] = self::LOCATION_HOLIDAY;
				} elseif ( $day['locked'] && $day['firstSlotBooked'] && $day['lastSlotBooked'] ) {
					$availability['status'] = self::ITEM_BOOKED;
				} elseif ( $day['locked'] && $day['partiallyBookedDay'] ) {
					$availability['status'] = self::ITEM_PARTIALLY_BOOKED;
				} else {
					$availability['status'] = self::ITEM_AVAILABLE;
				}

				$availabilities[] = $availability;
			}
		}

		return $availabilities;
	}


	/**
	 * Returns closest timeframe from date/time perspective.
	 *
	 * @param $bookableTimeframes
	 *
	 * @return \CommonsBooking\Model\Timeframe|null
	 */
	private static function getClosestBookableTimeFrameForToday( $bookableTimeframes ): ?\CommonsBooking\Model\Timeframe {
		// Sort timeframes by startdate
		usort(
			$bookableTimeframes,
			function ( \CommonsBooking\Model\Timeframe $item1, \CommonsBooking\Model\Timeframe $item2 ) {
				$item1StartDateDistance = abs( time() - $item1->getStartDate() );
				$item1EndDateDistance   = abs( time() - $item1->getEndDate() );
				$item1SmallestDistance  = min( $item1StartDateDistance, $item1EndDateDistance );

				$item2StartDateDistance = abs( time() - $item2->getStartDate() );
				$item2EndDateDistance   = abs( time() - $item2->getEndDate() );
				$item2SmallestDistance  = min( $item2StartDateDistance, $item2EndDateDistance );

				return $item2SmallestDistance <=> $item1SmallestDistance;
			}
		);

		return array_pop( $bookableTimeframes );
	}
}
