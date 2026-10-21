<?php
/**
 * Pure calculation rules for the Arya NSK calculator.
 *
 * @package Arya_NSK_Calculator
 */

class Arya_NSK_Calculator {

	/**
	 * Return the adult and child results for an ISO birth date.
	 *
	 * @param string $birth_date Birth date in Y-m-d format.
	 * @return array<string, int|string>
	 * @throws InvalidArgumentException When the date is invalid.
	 */
	public static function calculate( $birth_date ) {
		$date          = self::create_date( $birth_date );
		$adult_number  = self::adult_number_for_year( (int) $date->format( 'Y' ) );
		$child_number  = self::child_number_for( (int) $date->format( 'n' ), (int) $date->format( 'j' ), $adult_number );
		$element_names = self::element_names();

		return array(
			'adult_number'  => $adult_number,
			'adult_element' => $element_names[ $adult_number ],
			'child_number'  => $child_number,
			'child_element' => $element_names[ $child_number ],
		);
	}

	/**
	 * Calculate the adult number from a birth year.
	 *
	 * @param int $year Birth year.
	 * @return int
	 */
	public static function adult_number_for_year( $year ) {
		$last_two_digits = abs( $year ) % 100;
		$reduced_digits  = self::reduce_number( intdiv( $last_two_digits, 10 ) + ( $last_two_digits % 10 ) );
		$adult_number    = ( $year >= 2001 ? 11 : 10 ) - $reduced_digits;

		// The business rule requires a final one-digit result (10 becomes 1).
		return self::reduce_number( $adult_number );
	}

	/**
	 * Get the child number for a calendar day and adult number.
	 *
	 * @param int $month        Month from 1 to 12.
	 * @param int $day          Day in the month.
	 * @param int $adult_number Adult number from 1 to 9.
	 * @return int
	 * @throws InvalidArgumentException When an argument is invalid.
	 */
	public static function child_number_for( $month, $day, $adult_number ) {
		if ( $month < 1 || $month > 12 || $day < 1 || $day > 31 ) {
			throw new InvalidArgumentException( 'Invalid calendar day.' );
		}

		if ( $adult_number < 1 || $adult_number > 9 ) {
			throw new InvalidArgumentException( 'Invalid adult number.' );
		}

		$date_key = ( $month * 100 ) + $day;
		$rows     = array(
			array( 204, 305, array( 8, 2, 5 ) ),
			array( 306, 404, array( 7, 1, 4 ) ),
			array( 405, 505, array( 6, 9, 3 ) ),
			array( 506, 605, array( 5, 8, 2 ) ),
			array( 606, 707, array( 4, 7, 1 ) ),
			array( 708, 807, array( 3, 6, 9 ) ),
			array( 808, 907, array( 2, 5, 8 ) ),
			array( 908, 1008, array( 1, 4, 7 ) ),
			array( 1009, 1107, array( 9, 3, 6 ) ),
			array( 1108, 1207, array( 8, 2, 5 ) ),
		);

		if ( $date_key >= 1208 || $date_key <= 105 ) {
			$numbers = array( 7, 1, 4 );
		} elseif ( $date_key >= 106 && $date_key <= 203 ) {
			$numbers = array( 6, 9, 3 );
		} else {
			$numbers = array();

			foreach ( $rows as $row ) {
				if ( $date_key >= $row[0] && $date_key <= $row[1] ) {
					$numbers = $row[2];
					break;
				}
			}
		}

		if ( empty( $numbers ) ) {
			throw new InvalidArgumentException( 'No child-number range was found.' );
		}

		if ( in_array( $adult_number, array( 1, 4, 7 ), true ) ) {
			return $numbers[0];
		}

		if ( in_array( $adult_number, array( 2, 5, 8 ), true ) ) {
			return $numbers[1];
		}

		return $numbers[2];
	}

	/**
	 * Map each number to its associated element.
	 *
	 * @return array<int, string>
	 */
	public static function element_names() {
		return array(
			1 => 'Eau',
			2 => 'Terre',
			3 => 'Bois',
			4 => 'Bois',
			5 => 'Terre',
			6 => 'Métal',
			7 => 'Métal',
			8 => 'Terre',
			9 => 'Feu',
		);
	}

	/**
	 * Create and strictly validate a date.
	 *
	 * @param string $birth_date Birth date in Y-m-d format.
	 * @return DateTimeImmutable
	 * @throws InvalidArgumentException When the date is invalid.
	 */
	private static function create_date( $birth_date ) {
		$date   = DateTimeImmutable::createFromFormat( '!Y-m-d', $birth_date );
		$errors = DateTimeImmutable::getLastErrors();

		if ( false === $date || ( false !== $errors && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) || $date->format( 'Y-m-d' ) !== $birth_date ) {
			throw new InvalidArgumentException( 'Invalid birth date.' );
		}

		return $date;
	}

	/**
	 * Reduce an integer by adding digits until one digit remains.
	 *
	 * @param int $number Number to reduce.
	 * @return int
	 */
	private static function reduce_number( $number ) {
		$number = abs( $number );

		while ( $number > 9 ) {
			$number = intdiv( $number, 10 ) + ( $number % 10 );
		}

		return $number;
	}
}
