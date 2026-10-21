<?php
/**
 * Lightweight, dependency-free unit tests for the business rules.
 *
 * Run: php tests/test-calculator.php
 */

require_once dirname( __DIR__ ) . '/includes/class-arya-nsk-calculator.php';

function arya_nsk_test_assert_same( $expected, $actual, $label ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, sprintf( "FAILED: %s\nExpected: %s\nActual: %s\n", $label, var_export( $expected, true ), var_export( $actual, true ) ) );
		exit( 1 );
	}
}

$examples = array(
	array( '1912-02-04', 7, 'Métal', 8, 'Terre' ),
	array( '1958-03-06', 6, 'Métal', 4, 'Bois' ),
	array( '1994-03-03', 6, 'Métal', 5, 'Terre' ),
	array( '2001-01-05', 1, 'Eau', 7, 'Métal' ),
);

foreach ( $examples as $example ) {
	$result = Arya_NSK_Calculator::calculate( $example[0] );
	arya_nsk_test_assert_same( $example[1], $result['adult_number'], $example[0] . ' adult number' );
	arya_nsk_test_assert_same( $example[2], $result['adult_element'], $example[0] . ' adult element' );
	arya_nsk_test_assert_same( $example[3], $result['child_number'], $example[0] . ' child number' );
	arya_nsk_test_assert_same( $example[4], $result['child_element'], $example[0] . ' child element' );
}

$boundaries = array(
	array( 2, 4, array( 8, 2, 5 ) ), array( 3, 5, array( 8, 2, 5 ) ),
	array( 3, 6, array( 7, 1, 4 ) ), array( 4, 4, array( 7, 1, 4 ) ),
	array( 4, 5, array( 6, 9, 3 ) ), array( 5, 5, array( 6, 9, 3 ) ),
	array( 5, 6, array( 5, 8, 2 ) ), array( 6, 5, array( 5, 8, 2 ) ),
	array( 6, 6, array( 4, 7, 1 ) ), array( 7, 7, array( 4, 7, 1 ) ),
	array( 7, 8, array( 3, 6, 9 ) ), array( 8, 7, array( 3, 6, 9 ) ),
	array( 8, 8, array( 2, 5, 8 ) ), array( 9, 7, array( 2, 5, 8 ) ),
	array( 9, 8, array( 1, 4, 7 ) ), array( 10, 8, array( 1, 4, 7 ) ),
	array( 10, 9, array( 9, 3, 6 ) ), array( 11, 7, array( 9, 3, 6 ) ),
	array( 11, 8, array( 8, 2, 5 ) ), array( 12, 7, array( 8, 2, 5 ) ),
	array( 12, 8, array( 7, 1, 4 ) ), array( 1, 5, array( 7, 1, 4 ) ),
	array( 1, 6, array( 6, 9, 3 ) ), array( 2, 3, array( 6, 9, 3 ) ),
);

foreach ( $boundaries as $boundary ) {
	foreach ( array( 1, 2, 3 ) as $index => $adult_number ) {
		arya_nsk_test_assert_same(
			$boundary[2][ $index ],
			Arya_NSK_Calculator::child_number_for( $boundary[0], $boundary[1], $adult_number ),
			sprintf( 'boundary %02d-%02d, adult group %d', $boundary[0], $boundary[1], $adult_number )
		);
	}
}

try {
	Arya_NSK_Calculator::calculate( '2023-02-29' );
	fwrite( STDERR, "FAILED: invalid date was accepted\n" );
	exit( 1 );
} catch ( InvalidArgumentException $exception ) {
	// Expected.
}

echo "All calculator tests passed.\n";
