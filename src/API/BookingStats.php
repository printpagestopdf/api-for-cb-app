<?php

namespace CBAppApi\API;

use CommonsBooking\Settings\Settings;
use CommonsBooking\Map\MapShortcode;
use CommonsBooking\Map\MapData;
use DateTimeImmutable;
use DatePeriod;
use DateInterval;


class BookingStats {


	public static function getUserBookingStats( $mapID ): array {

		[$dtMin, $dtMax] = self::dateRangeFromMap( $mapID );

		$result  = array();
		$options = get_option( 'cb_app_booking_restrictions', array() );

		$max_bookings_month = @$options['max_bookings_month'];
		$max_bookings_week  = @$options['max_bookings_week'];
		$max_days_month     = @$options['max_days_month'];
		$max_days_week      = @$options['max_days_week'];

		$tsMinMonth = $tsMaxMonth = $tsMinWeek = $tsMaxWeek = 0;

		if ( ! empty( $max_days_month ) || ! empty( $max_bookings_month ) ) {
			$tsMin                        = $tsMinMonth = $dtMin->modify( 'first day of this month midnight' )->getTimestamp();
			$tsMax                        = $tsMaxMonth = $dtMax->modify( 'last day of this month 23:59:59' )->getTimestamp();
			$hasMonthRestriction          = true;
			$result['month_restrictions'] = array();
			if ( ! empty( $max_bookings_month ) ) {
				$result['month_restrictions']['bookings'] = array(
					'limit'  => (int) $max_bookings_month,
					'booked' => array(),
				);
			}
			if ( ! empty( $max_days_month ) ) {
				$result['month_restrictions']['days'] = array(
					'limit'  => (int) $max_days_month,
					'booked' => array(),
				);
			}
		} else {
			$hasMonthRestriction = false;
		}

		if ( ! empty( $max_bookings_week ) || ! empty( $max_days_week ) ) {
			$tsMin                       = $tsMinWeek = $dtMin->modify( 'Monday this week midnight' )->getTimestamp();
			$tsMax                       = $tsMaxWeek = $dtMax->modify( 'Sunday this week 23:59:59' )->getTimestamp();
			$hasWeekRestriction          = true;
			$result['week_restrictions'] = array();
			if ( ! empty( $max_bookings_week ) ) {
				$result['week_restrictions']['bookings'] = array(
					'limit'  => (int) $max_bookings_week,
					'booked' => array(),
				);
			}
			if ( ! empty( $max_days_week ) ) {
				$result['week_restrictions']['days'] = array(
					'limit'  => (int) $max_days_week,
					'booked' => array(),
				);
			}
		} else {
			$hasWeekRestriction = false;
		}

		if ( ! $hasWeekRestriction && ! $hasMonthRestriction ) {
			return $result;
		}

		if ( $hasWeekRestriction && $hasMonthRestriction ) {
			$tsMin = min( $tsMinMonth, $tsMinWeek );
			$tsMax = max( $tsMaxMonth, $tsMaxWeek );
		}

		if ( ! is_user_logged_in() ) {
			return $result;
		}
		$bookings = \CommonsBooking\Repository\Booking::getForCurrentUser(
			true,
			$tsMin
		);

		// $result["params"] = array();
		foreach ( $bookings as $booking ) {
			if ( ! $booking->isConfirmed() || $booking->getStartDate() > $tsMax ) {
				continue;
			}
			// $result = array_merge($result, self::getBookedDays($booking));
			if ( ! empty( $result['month_restrictions']['bookings'] ) ) {
				$key = gmdate( 'Y-m', $booking->getStartDate() );
				if ( array_key_exists( $key, $result['month_restrictions']['bookings']['booked'] ) ) {
					++$result['month_restrictions']['bookings']['booked'][ $key ];
				} else {
					$result['month_restrictions']['bookings']['booked'][ $key ] = 1;
				}
			}
			if ( ! empty( $result['week_restrictions']['bookings'] ) ) {
				// $key = date("o-W", $booking->getStartDate());
				$key = \DateTime::createFromFormat( 'U', $booking->getStartDate() )->modify( 'Monday this week midnight' )->format( 'Y-m-d' );
				if ( array_key_exists( $key, $result['week_restrictions']['bookings']['booked'] ) ) {
					++$result['week_restrictions']['bookings']['booked'][ $key ];
				} else {
					$result['week_restrictions']['bookings']['booked'][ $key ] = 1;
				}
			}
			if ( ! empty( $result['month_restrictions']['days'] ) || ! empty( $result['week_restrictions']['days'] ) ) {
				$overbookParams = self::overbookingSetting( $booking->getLocation() );
				// $result["params"][] = self::overbookingSetting($booking->getLocation());
				self::buildResult( $result, self::getBookedDays( $booking, $overbookParams ) );
			}
		}

		return $result;
	}

	public static function dateRangeFromMap( $mapID ) {

		if ( method_exists( MapShortcode::class, 'get_settings' ) ) {
			$mapSettings = MapShortcode::get_settings( $mapID );
		} else {
			$mapSettings = MapData::get_settings( $mapID );
		}
		if (
			array_key_exists( 'filter_availability', $mapSettings ) &&
			! empty( $mapSettings['filter_availability']['date_min'] ) &&
			! empty( $mapSettings['filter_availability']['date_max'] )
		) {
			$dtMin = new \DateTimeImmutable( $mapSettings['filter_availability']['date_min'] );
			$dtMax = new \DateTimeImmutable( $mapSettings['filter_availability']['date_max'] . '  23:59:59' );
		} else {
			$dtMin = new DateTimeImmutable( 'now midnight' );
			$dtMax = $dtMin->add( new \DateInterval( 'P30DT23H59M59S' ) );
		}

		return array( $dtMin, $dtMax );
	}

	public static function overbookingSetting( $location ) {
		$locationID     = ( $location == null ) ? 0 : ( is_numeric( $location ) ? $location : $location->ID );
		$overbookParams = array();
		// are overbooking allowed in location options?
		$useGlobalSettings = ( $location == null ) ? true : get_post_meta( $locationID, COMMONSBOOKING_METABOX_PREFIX . 'use_global_settings', true ) === 'on';
		if ( $useGlobalSettings ) {
			$allowLockedDaysInRange = Settings::getOption( 'commonsbooking_options_general', COMMONSBOOKING_METABOX_PREFIX . 'allow_lockdays_in_range' );
		} else {
			$allowLockedDaysInRange = get_post_meta(
				$locationID,
				COMMONSBOOKING_METABOX_PREFIX . 'allow_lockdays_in_range',
				true
			);
		}
		$overbookParams['disallowLockDaysInRange'] = ! ( $allowLockedDaysInRange === 'on' );

		// should overbooked non bookable days be counted into maxdays selection?
		if ( $useGlobalSettings ) {
			$countLockedDaysInRange = Settings::getOption( 'commonsbooking_options_general', COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_in_range' );
		} else {
			$countLockedDaysInRange = get_post_meta(
				$locationID,
				COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_in_range',
				true
			);
		}
		$overbookParams['countLockDaysInRange'] = $countLockedDaysInRange === 'on';

		//if yes, what is the maximum amount of days they should count?
		if ( $useGlobalSettings ) {
			$countLockdaysMaximum = Settings::getOption( 'commonsbooking_options_general', COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_maximum' );
		} else {
			$countLockdaysMaximum = get_post_meta( $locationID, COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_maximum', true );
		}
		$overbookParams['countLockDaysMaxDays'] = (int) $countLockdaysMaximum;

		return $overbookParams;
	}


	public static function buildResult( &$result, $bookedDays ) {
		if ( ! empty( $result['month_restrictions']['days'] ) ) {
			foreach ( $bookedDays as $bookedDay ) {
				$key = $bookedDay->format( 'Y-m' );
				if ( array_key_exists( $key, $result['month_restrictions']['days']['booked'] ) ) {
					++$result['month_restrictions']['days']['booked'][ $key ];
				} else {
					$result['month_restrictions']['days']['booked'][ $key ] = 1;
				}
			}
		}
		if ( ! empty( $result['week_restrictions']['days'] ) ) {
			foreach ( $bookedDays as $bookedDay ) {
				// $key = $bookedDay->format("o-W");
				$key = $bookedDay->modify( 'Monday this week midnight' )->format( 'Y-m-d' );
				if ( array_key_exists( $key, $result['week_restrictions']['days']['booked'] ) ) {
					++$result['week_restrictions']['days']['booked'][ $key ];
				} else {
					$result['week_restrictions']['days']['booked'][ $key ] = 1;
				}
			}
		}
	}

	public static function getBookedDays( $booking, $overbookParams ): array {
		$bookingDays = array();

		if ( $overbookParams['disallowLockDaysInRange'] || ( $overbookParams['countLockDaysInRange'] && $overbookParams['countLockDaysMaxDays'] == 0 ) ) {
			$period = new DatePeriod( new DateTimeImmutable( '@' . $booking->getStartDate() ), new DateInterval( 'P1D' ), $booking->getEndDateDateTime(), 2 /*INCLUDE_END_DATE*/ );
			return iterator_to_array( $period );
		}

		$bookableTf = $booking->getBookableTimeFrame();

		if ( $bookableTf == null ) {
			$timeframesBookable = \CommonsBooking\Repository\Timeframe::getInRange(
				$booking->getStartDate(),
				$booking->getEndDate(),
				array( $booking->getLocation()->ID ),
				array( $booking->getItem()->ID ),
				array(
					\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				),
				true
			);
			if ( ! is_array( $timeframesBookable ) || empty( $timeframesBookable ) ) {
				return $bookingDays;
			}

			$bookableTf = $timeframesBookable[0];
		}

		$backlog = ( ! $overbookParams['disallowLockDaysInRange'] && $overbookParams['countLockDaysInRange'] && $overbookParams['countLockDaysMaxDays'] > 0 ) ?
			$overbookParams['countLockDaysMaxDays'] : 0;

		$period = new DatePeriod( new DateTimeImmutable( '@' . $booking->getStartDate() ), new DateInterval( 'P1D' ), $booking->getEndDateDateTime(), 2 /*INCLUDE_END_DATE*/ );
		foreach ( $period as $date ) {
			if ( self::isInTimeframe( $date, $bookableTf ) ) {
				$bookingDays[] = $date;
			} elseif ( $backlog > 0 ) {
				$bookingDays[] = $date;
				--$backlog;
			}
		}

		//sort out hollidays (overbookable)
		$timeframes = \CommonsBooking\Repository\Timeframe::getInRange(
			$booking->getStartDate(),
			$booking->getEndDate(),
			array( $booking->getLocation()->ID ),
			array( $booking->getItem()->ID ),
			array(
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::OFF_HOLIDAYS_ID,
			),
			true
		);

		foreach ( $timeframes as $tf ) {
			$bookingDays = array_filter(
				$bookingDays,
				function ( $day ) use ( $tf, $backlog ) {
					if ( self::isInTimeframe( $day, $tf ) ) {
						if ( $backlog > 0 ) {
							$backlog--;
							return true;
						} else {
							return false;
						}
					} else {
						return true;
					}
					// return !self::isInTimeframe($day, $tf);
				}
			);
		}

		return $bookingDays;
	}

	//modified code from CommonsBooking\Model\Day
	public static function isInTimeframe( $day, $timeframe ): bool {
		if ( ! is_object( $timeframe ) ) {
			return false;
		}

		$repetitionType = get_post_meta( $timeframe->ID, 'timeframe-repetition', true );

		if ( $repetitionType ) {
			switch ( $repetitionType ) {
					// Weekly Rep
				case 'w':
					$dayOfWeek         = intval( $day->format( 'w' ) );
					$timeframeWeekdays = get_post_meta( $timeframe->ID, 'weekdays', true );

					// Because of different day of week calculation we need to recalculate
					if ( $dayOfWeek == 0 ) {
						$dayOfWeek = 7;
					}

					if ( is_array( $timeframeWeekdays ) && in_array( $dayOfWeek, $timeframeWeekdays ) ) {
						return true;
					} else {
						return false;
					}

					// Monthly Rep
				case 'm':
					$dayOfMonth               = intval( $day->format( 'j' ) );
					$timeframeStartDayOfMonth = gmdate( 'j', $timeframe->getStartDate() );

					if ( $dayOfMonth == $timeframeStartDayOfMonth ) {
						return true;
					} else {
						return false;
					}

					// Yearly Rep
				case 'y':
					$date          = intval( $day->format( 'dm' ) );
					$timeframeDate = gmdate( 'dm', $timeframe->getStartDate() );
					if ( $date == $timeframeDate ) {
						return true;
					} else {
						return false;
					}
				case 'norep':
					$timeframeStartTimestamp = intval( $timeframe->getMeta( \CommonsBooking\Model\Timeframe::REPETITION_START ) );
					$timeframeEndTimestamp   = intval( $timeframe->getMeta( \CommonsBooking\Model\Timeframe::REPETITION_END ) );

					$currentDayStartTimestamp = strtotime( 'midnight', $day->getTimestamp() );
					$currentDayEndTimestamp   = strtotime( '+1 day midnight', $day->getTimestamp() ) - 1;

					$timeframeStartsBeforeEndOfToday = $timeframeStartTimestamp <= $currentDayEndTimestamp;
					$timeframeEndsAfterStartOfToday  = $timeframeEndTimestamp >= $currentDayStartTimestamp;

					if ( ! $timeframeEndTimestamp ) {
						return $timeframeStartsBeforeEndOfToday;
					} else {
						return $timeframeStartsBeforeEndOfToday && $timeframeEndsAfterStartOfToday;
					}
			}
		}

		return true;
	}
}
