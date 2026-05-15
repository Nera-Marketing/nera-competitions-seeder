<?php
/**
 * Demo fixture data — reusable name pool for tickets and winners.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GG_Demo_Fixtures {

	/**
	 * Returns a 30-name pool used for both ticket buyers and instant winners.
	 *
	 * Names match the style of the parent theme's demo-instant-winner utility
	 * so both data sets feel like they came from the same store.
	 */
	public static function names() {
		return array(
			'Tom Baxter',
			'Jake Massey',
			'Darren Wheeler',
			'Keeley Hill',
			'Tia Rigby',
			'Isha Mullings',
			'Josiah Bond',
			'Ashley Sallis',
			'Joe Tilsley',
			'Ben Kennan',
			'Sarah Webb',
			'Emma Thompson',
			'Oliver Smith',
			'Sophie Wilson',
			'James Anderson',
			'Emily Brown',
			'Michael Davis',
			'Charlotte Taylor',
			'Daniel Martinez',
			'Amelia Garcia',
			'Harry Patel',
			'Olivia Clarke',
			'Liam Walker',
			'Mia Roberts',
			'Noah Bennett',
			'Ava Foster',
			'Ethan Hughes',
			'Isabella King',
			'Lucas Wright',
			'Grace Murphy',
		);
	}

	/**
	 * Pick the Nth name from the pool (wraps around).
	 */
	public static function name_at( $index ) {
		$pool = self::names();
		$count = count( $pool );
		return $pool[ $index % $count ];
	}

	/**
	 * Convert a display name into a demo email at example.com.
	 */
	public static function email_for( $name ) {
		$slug = strtolower( str_replace( ' ', '.', $name ) );
		return sanitize_email( $slug . '@example.com' );
	}
}
