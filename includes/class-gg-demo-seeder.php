<?php
/**
 * Per-product seeder for demo lottery products.
 *
 * Tracking option shape (gg_demo_products_seeded_ids):
 *
 *   [
 *     slug => [
 *       'product_id'     => int,
 *       'ticket_ids'     => int[],
 *       'winner_log_ids' => int[],
 *       'attachment_ids' => int[],
 *     ],
 *     ...
 *   ]
 *
 * Older v1.0 installs may have a flat [slug => int] shape; get_record() and
 * wipe_all() handle that transparently.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GG_Demo_Seeder {

	const TICKETS_PER_PRODUCT = 50;

	public static function get_definitions() {
		return require GG_DEMO_PRODUCTS_DIR . 'includes/data/product-definitions.php';
	}

	public static function get_seeded_ids() {
		return (array) get_option( GG_DEMO_PRODUCTS_OPTION, array() );
	}

	/**
	 * Normalise a tracking entry into the structured shape, handling the legacy
	 * flat int form left behind by v1.0.
	 *
	 * @return array{product_id:int,ticket_ids:int[],winner_log_ids:int[],attachment_ids:int[]}
	 */
	public static function get_record( $slug ) {
		$all = self::get_seeded_ids();
		if ( empty( $all[ $slug ] ) ) {
			return self::empty_record();
		}
		$raw = $all[ $slug ];
		if ( is_int( $raw ) || is_numeric( $raw ) ) {
			return array_merge( self::empty_record(), array( 'product_id' => (int) $raw ) );
		}
		return array_merge( self::empty_record(), (array) $raw );
	}

	private static function empty_record() {
		return array(
			'product_id'     => 0,
			'ticket_ids'     => array(),
			'winner_log_ids' => array(),
			'attachment_ids' => array(),
		);
	}

	private static function save_record( $slug, array $record ) {
		$all = self::get_seeded_ids();
		$all[ $slug ] = $record;
		update_option( GG_DEMO_PRODUCTS_OPTION, $all );
	}

	/**
	 * Seed a single product.
	 *
	 * @return int|WP_Error Product ID on success, WP_Error on failure or skip.
	 */
	public static function seed_one( array $spec ) {
		if ( ! class_exists( 'WC_Product_Lottery' ) ) {
			return new WP_Error( 'lottery_missing', __( 'Lottery for WooCommerce is not active.', 'nera-competitions-seeder' ) );
		}

		$existing = self::get_record( $spec['slug'] );
		if ( $existing['product_id'] && get_post( $existing['product_id'] ) ) {
			return new WP_Error(
				'already_seeded',
				sprintf( __( 'Already seeded: %1$s (ID #%2$d). Wipe first to recreate.', 'nera-competitions-seeder' ), $spec['title'], $existing['product_id'] )
			);
		}

		$product = new WC_Product_Lottery();
		$product->set_name( $spec['title'] );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_description( $spec['description'] );
		$product->set_short_description( $spec['short'] );
		$product->set_regular_price( (string) $spec['price'] );
		$product->set_price( (string) $spec['price'] );
		$product->set_manage_stock( true );
		$product->set_sold_individually( false );

		$product_id = $product->save();

		if ( ! $product_id ) {
			return new WP_Error( 'save_failed', __( 'Could not save product.', 'nera-competitions-seeder' ) );
		}

		$record               = self::empty_record();
		$record['product_id'] = (int) $product_id;

		self::apply_lottery_meta( $product_id, $spec );

		$term_id = self::ensure_category();
		if ( $term_id ) {
			wp_set_object_terms( $product_id, array( $term_id ), 'product_cat' );
		}

		// Featured + gallery thumbnails.
		$images = GG_Demo_Image::sideload_set( $product_id, $spec['image_seed'] );
		if ( $images['featured'] ) {
			set_post_thumbnail( $product_id, $images['featured'] );
		}
		if ( ! empty( $images['gallery'] ) ) {
			$gallery_product = wc_get_product( $product_id );
			if ( $gallery_product ) {
				$gallery_product->set_gallery_image_ids( $images['gallery'] );
				$gallery_product->save();
			}
		}
		$record['attachment_ids'] = $images['all'];

		// Instant-win rules and their seeded winner logs.
		if ( $spec['variant'] === 'instant_win' && ! empty( $spec['instant_rules'] ) ) {
			$rule_ids = self::create_instant_rules( $product_id, $spec['instant_rules'] );
			if ( $rule_ids ) {
				$record['winner_log_ids'] = self::seed_instant_winners( $product_id, $rule_ids, $spec['instant_rules'] );
			}
		}

		// 50 demo tickets for every active variant.
		if ( $spec['variant'] !== 'ended' ) {
			$record['ticket_ids'] = self::seed_tickets( $product_id, $spec, self::TICKETS_PER_PRODUCT );
		}

		self::save_record( $spec['slug'], $record );

		wp_cache_delete( $product_id, 'posts' );
		clean_post_cache( $product_id );

		return (int) $product_id;
	}

	/**
	 * Write all lottery-specific meta for the given product.
	 */
	private static function apply_lottery_meta( $product_id, array $spec ) {
		$now_local_ts = (int) current_time( 'timestamp' );
		$now_gmt_ts   = time();

		if ( $spec['variant'] === 'ended' ) {
			$start_local_ts = $now_local_ts - ( 60 * DAY_IN_SECONDS );
			$start_gmt_ts   = $now_gmt_ts - ( 60 * DAY_IN_SECONDS );
			$end_local_ts   = $now_local_ts - DAY_IN_SECONDS;
			$end_gmt_ts     = $now_gmt_ts - DAY_IN_SECONDS;
			$status         = 'lty_lottery_closed';
		} else {
			$start_local_ts = $now_local_ts;
			$start_gmt_ts   = $now_gmt_ts;
			$end_local_ts   = strtotime( '+1 year', $now_local_ts );
			$end_gmt_ts     = strtotime( '+1 year', $now_gmt_ts );
			$status         = 'lty_lottery_started';
		}

		$fmt = 'Y-m-d H:i:s';

		$meta = array(
			'_lty_start_date'             => gmdate( $fmt, $start_local_ts ),
			'_lty_start_date_gmt'         => gmdate( $fmt, $start_gmt_ts ),
			'_lty_end_date'               => gmdate( $fmt, $end_local_ts ),
			'_lty_end_date_gmt'           => gmdate( $fmt, $end_gmt_ts ),
			'_lty_lottery_schedule_type'  => '1',
			'_lty_minimum_tickets'        => (string) $spec['min_tickets'],
			'_lty_maximum_tickets'        => (string) $spec['max_tickets'],
			'_lty_ticket_price_type'      => '1',
			'_lty_regular_price'          => (string) $spec['price'],
			'_lty_preset_tickets'         => '1',
			'_lty_user_minimum_tickets'   => '1',
			'_lty_user_maximum_tickets'   => (string) min( 100, $spec['max_tickets'] ),
			'_lty_winners_count'          => '1',
			'_lty_winner_selection_method' => '1',
			'_lty_winning_product_selection' => '2',
			'_lty_winner_outside_gift_items' => $spec['title'],
			'_lty_ticket_start_number'    => '1',
			'_lty_ticket_length'          => '5',
			'_lty_lottery_unique_winners' => 'yes',
			'_manage_stock'               => 'yes',
			'_stock'                      => (string) $spec['max_tickets'],
			'_stock_status'               => 'instock',
			'_lty_lottery_status'         => $status,
		);

		switch ( $spec['variant'] ) {
			case 'standard_random':
				$meta['_lty_ticket_generation_type'] = '1';
				$meta['_lty_ticket_number_type']     = '1';
				break;

			case 'standard_sequential':
				$meta['_lty_ticket_generation_type']        = '1';
				$meta['_lty_ticket_number_type']            = '2';
				$meta['_lty_ticket_sequential_start_number'] = '1';
				break;

			case 'standard_shuffled':
				$meta['_lty_ticket_generation_type']      = '1';
				$meta['_lty_ticket_number_type']          = '3';
				$meta['_lty_ticket_shuffled_start_number'] = '1';
				break;

			case 'manual_pick':
				$meta['_lty_ticket_generation_type'] = '2';
				$meta['_lty_ticket_number_type']     = '2';
				$meta['_lty_tickets_per_tab']        = '100';
				break;

			case 'instant_win':
				$meta['_lty_ticket_generation_type']      = '1';
				$meta['_lty_ticket_number_type']          = '1';
				$meta['_lty_instant_winners']             = 'yes';
				$meta['_lty_display_instant_winner_image'] = 'yes';
				$meta['_lty_instant_winner_display_mode'] = '1';
				break;

			case 'skill_qa':
				$meta['_lty_ticket_generation_type']     = '1';
				$meta['_lty_ticket_number_type']         = '1';
				$meta['_lty_manage_question']            = 'yes';
				$meta['_lty_question_answer_selection_type'] = '1';
				$meta['_lty_force_answer']               = 'yes';
				$meta['_lty_validate_correct_answer']    = 'yes';
				$meta['_lty_restrict_incorrectly_selected_answer'] = 'yes';
				$meta['_lty_question_answer_display_type'] = '1';
				$meta['_lty_question_answer_time_limit_type'] = '1';
				if ( ! empty( $spec['questions'] ) ) {
					$meta['_lty_questions'] = $spec['questions'];
				}
				break;

			case 'ended':
				$meta['_lty_ticket_generation_type'] = '1';
				$meta['_lty_ticket_number_type']     = '1';
				$meta['_lty_closed']                 = 'yes';
				break;
		}

		foreach ( $meta as $key => $value ) {
			update_post_meta( $product_id, $key, $value );
		}
	}

	/**
	 * Create instant-winner rules for an instant_win product.
	 *
	 * @return int[] Created rule post IDs.
	 */
	private static function create_instant_rules( $product_id, array $rules ) {
		if ( ! function_exists( 'lty_create_new_instant_winner_rule' ) ) {
			return array();
		}
		$ids = array();
		foreach ( $rules as $rule_meta ) {
			$rule_id = lty_create_new_instant_winner_rule(
				$rule_meta,
				array(
					'post_parent' => $product_id,
					'post_status' => 'publish',
				)
			);
			if ( $rule_id ) {
				$ids[] = (int) $rule_id;
			}
		}
		return $ids;
	}

	/**
	 * Create an instant-winner LOG entry per rule and mark each as "won" by a
	 * demo user. Mirrors the parent theme's demo-instant-winner flow + the
	 * plugin's own importer flow (rule import explicitly creates the log).
	 *
	 * @param int   $product_id Lottery product ID.
	 * @param int[] $rule_ids   Instant winner rule IDs returned by create_instant_rules().
	 * @param array $rule_specs Original rule meta specs (parallel array to $rule_ids).
	 * @return int[] Log post IDs that were created.
	 */
	private static function seed_instant_winners( $product_id, array $rule_ids, array $rule_specs ) {
		$log_ids = array();

		if ( ! function_exists( 'lty_create_new_instant_winner_log' ) ) {
			return $log_ids;
		}

		foreach ( $rule_ids as $i => $rule_id ) {
			$spec  = isset( $rule_specs[ $i ] ) ? $rule_specs[ $i ] : array();
			$name  = GG_Demo_Fixtures::name_at( $i );
			$email = GG_Demo_Fixtures::email_for( $name );

			$meta_args = array_merge(
				$spec,
				array(
					'lty_lottery_id'           => $product_id,
					'lty_user_name'            => $name,
					'lty_user_email'           => $email,
					'lty_user_id'              => 0,
					'lty_order_id'             => 0,
					'lty_current_relist_count' => 0,
					'lty_prize_assigned'       => 'yes',
				)
			);

			$log_id = lty_create_new_instant_winner_log(
				$meta_args,
				array(
					'post_parent' => $rule_id,
					'post_status' => 'lty_won',
				)
			);

			if ( $log_id ) {
				$log_ids[] = (int) $log_id;
			}
		}

		return $log_ids;
	}

	/**
	 * Create N demo "purchased" tickets attached to the given product.
	 *
	 * @return int[] Ticket post IDs.
	 */
	public static function seed_tickets( $product_id, array $spec, $count ) {
		if ( ! function_exists( 'lty_create_new_lottery_ticket' ) ) {
			return array();
		}

		$created    = array();
		$used_nums  = array();
		$currency   = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD';
		$price      = (string) $spec['price'];
		$is_skill   = $spec['variant'] === 'skill_qa';
		$variant    = $spec['variant'];
		$pad_length = 5;

		for ( $i = 0; $i < $count; $i++ ) {
			$ticket_number = self::generate_ticket_number( $variant, $i, $used_nums, $pad_length );
			if ( null === $ticket_number ) {
				break;
			}
			$used_nums[ $ticket_number ] = true;

			$name  = GG_Demo_Fixtures::name_at( $i );
			$email = GG_Demo_Fixtures::email_for( $name );

			$meta_args = array(
				'lty_user_id'      => 0,
				'lty_product_id'   => $product_id,
				'lty_amount'       => $price,
				'lty_user_name'    => $name,
				'lty_user_email'   => $email,
				'lty_currency'     => $currency,
				'lty_order_id'     => 0,
				'lty_ticket_number' => $ticket_number,
				'lty_ip_address'   => '127.0.0.1',
			);

			if ( $is_skill ) {
				$meta_args['lty_valid_answer'] = 'yes';
				$meta_args['lty_answers']      = array(
					0 => array(
						'label' => 'Geneva',
						'valid' => 'yes',
					),
				);
				$meta_args['lty_answer'] = 'Geneva';
			}

			$ticket_id = lty_create_new_lottery_ticket(
				$meta_args,
				array(
					'post_parent' => $product_id,
					'post_status' => 'lty_ticket_buyer',
				)
			);

			if ( $ticket_id ) {
				$created[] = (int) $ticket_id;
			}
		}

		return $created;
	}

	/**
	 * Generate a unique ticket number for the given variant.
	 */
	private static function generate_ticket_number( $variant, $index, array $used, $pad_length ) {
		switch ( $variant ) {
			case 'standard_sequential':
			case 'manual_pick':
				return str_pad( (string) ( $index + 1 ), $pad_length, '0', STR_PAD_LEFT );

			case 'standard_shuffled':
				$candidate = str_pad( (string) ( ( $index * 7 ) % 100 + 1 ), $pad_length, '0', STR_PAD_LEFT );
				$tries     = 0;
				while ( isset( $used[ $candidate ] ) && $tries < 200 ) {
					$candidate = str_pad( (string) wp_rand( 1, 99999 ), $pad_length, '0', STR_PAD_LEFT );
					$tries++;
				}
				return $candidate;

			default: // random, instant_win, skill_qa, etc.
				$tries     = 0;
				$candidate = str_pad( (string) wp_rand( 1, 99999 ), $pad_length, '0', STR_PAD_LEFT );
				while ( isset( $used[ $candidate ] ) && $tries < 200 ) {
					$candidate = str_pad( (string) wp_rand( 1, 99999 ), $pad_length, '0', STR_PAD_LEFT );
					$tries++;
				}
				return $candidate;
		}
	}

	public static function ensure_category() {
		$term = get_term_by( 'name', GG_DEMO_PRODUCTS_CATEGORY, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			return (int) $term->term_id;
		}
		$created = wp_insert_term( GG_DEMO_PRODUCTS_CATEGORY, 'product_cat', array(
			'description' => __( 'Demo competition products seeded by Nera Competitions Seeder.', 'nera-competitions-seeder' ),
		) );
		if ( is_wp_error( $created ) ) {
			return 0;
		}
		return (int) $created['term_id'];
	}

	/**
	 * Delete every seeded product, its tickets, its instant-winner rule+log
	 * posts, and all sideloaded attachments.
	 *
	 * @return int Number of products wiped.
	 */
	public static function wipe_all() {
		$seeded = self::get_seeded_ids();
		$count  = 0;

		foreach ( $seeded as $slug => $raw ) {
			$record = is_array( $raw )
				? array_merge( self::empty_record(), $raw )
				: array_merge( self::empty_record(), array( 'product_id' => (int) $raw ) );

			$product_id = (int) $record['product_id'];
			if ( ! $product_id ) {
				continue;
			}

			// Tracked tickets.
			foreach ( $record['ticket_ids'] as $ticket_id ) {
				wp_delete_post( (int) $ticket_id, true );
			}

			// Tracked winner logs (in case they get orphaned before rules go).
			foreach ( $record['winner_log_ids'] as $log_id ) {
				wp_delete_post( (int) $log_id, true );
			}

			// Instant-winner rules attached to this product (and any remaining
			// child logs the rule didn't already cascade).
			$rules = get_posts( array(
				'post_type'   => 'lty_instant_winners',
				'post_parent' => $product_id,
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			) );
			foreach ( $rules as $rule_id ) {
				$child_logs = get_posts( array(
					'post_type'   => 'lty_ins_winner_log',
					'post_parent' => $rule_id,
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
				) );
				foreach ( $child_logs as $child_log_id ) {
					wp_delete_post( (int) $child_log_id, true );
				}
				wp_delete_post( (int) $rule_id, true );
			}

			// Tracked attachments (featured + gallery).
			if ( ! empty( $record['attachment_ids'] ) ) {
				foreach ( $record['attachment_ids'] as $att_id ) {
					wp_delete_attachment( (int) $att_id, true );
				}
			} else {
				// Back-compat: v1.0 only stored the featured image via thumbnail.
				$thumb_id = (int) get_post_thumbnail_id( $product_id );
				if ( $thumb_id ) {
					wp_delete_attachment( $thumb_id, true );
				}
			}

			if ( wp_delete_post( $product_id, true ) ) {
				$count++;
			}
		}

		delete_option( GG_DEMO_PRODUCTS_OPTION );
		return $count;
	}
}
