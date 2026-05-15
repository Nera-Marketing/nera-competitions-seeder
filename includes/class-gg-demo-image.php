<?php
/**
 * Sideload helper for demo product images.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GG_Demo_Image {

	/**
	 * Sideload a deterministic placeholder image from picsum.photos and attach
	 * it to the given product.
	 *
	 * @param string $seed       Stable seed string (use the product slug).
	 * @param int    $product_id Product ID to attach the image to.
	 * @return int|null Attachment ID, or null on failure.
	 */
	public static function sideload( $seed, $product_id ) {
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$url = 'https://picsum.photos/seed/' . rawurlencode( $seed ) . '/1200/800';

		add_filter( 'http_request_args', array( __CLASS__, 'allow_long_timeout' ), 10, 2 );
		$result = media_sideload_image( $url, $product_id, null, 'id' );
		remove_filter( 'http_request_args', array( __CLASS__, 'allow_long_timeout' ), 10 );

		if ( is_wp_error( $result ) ) {
			return null;
		}

		return (int) $result;
	}

	public static function allow_long_timeout( $args, $url ) {
		if ( false !== strpos( $url, 'picsum.photos' ) ) {
			$args['timeout'] = 20;
		}
		return $args;
	}

	/**
	 * Sideload a featured image + 3 gallery thumbnails for the product, each
	 * with a deterministic seed derived from the product slug.
	 *
	 * @param int    $product_id Target product.
	 * @param string $slug       Product slug — used as the seed root.
	 * @return array{
	 *     featured: int|null,
	 *     gallery:  int[],
	 *     all:      int[]
	 * }
	 */
	public static function sideload_set( $product_id, $slug ) {
		$result = array(
			'featured' => null,
			'gallery'  => array(),
			'all'      => array(),
		);

		$featured = self::sideload( $slug, $product_id );
		if ( $featured ) {
			$result['featured'] = $featured;
			$result['all'][]    = $featured;
		}

		foreach ( array( '-g1', '-g2', '-g3' ) as $suffix ) {
			$att_id = self::sideload( $slug . $suffix, $product_id );
			if ( $att_id ) {
				$result['gallery'][] = $att_id;
				$result['all'][]     = $att_id;
			}
		}

		return $result;
	}
}
