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
		self::load_media_helpers();

		$url = 'https://picsum.photos/seed/' . rawurlencode( $seed ) . '/1200/800';

		add_filter( 'http_request_args', array( __CLASS__, 'allow_long_timeout' ), 10, 2 );
		$result = media_sideload_image( $url, $product_id, null, 'id' );
		remove_filter( 'http_request_args', array( __CLASS__, 'allow_long_timeout' ), 10 );

		if ( is_wp_error( $result ) ) {
			return null;
		}

		$attachment_id = (int) $result;
		self::normalize_attachment( $attachment_id );

		return $attachment_id;
	}

	/**
	 * Sideload an explicit image URL (e.g. a curated Unsplash photo) using
	 * download_url() + media_handle_sideload() so URLs without a file extension
	 * (Unsplash CDN: images.unsplash.com/photo-<id>?...) are accepted. If the
	 * download fails, fall back to picsum.photos using $fallback_seed.
	 *
	 * @param string $url            Direct image URL.
	 * @param int    $product_id     Product ID to attach the image to.
	 * @param string $fallback_seed  Stable seed used for picsum fallback.
	 * @return int|null Attachment ID, or null on total failure.
	 */
	public static function sideload_url( $url, $product_id, $fallback_seed ) {
		self::load_media_helpers();

		add_filter( 'http_request_args', array( __CLASS__, 'allow_long_timeout' ), 10, 2 );
		$tmp = download_url( $url, 20 );
		remove_filter( 'http_request_args', array( __CLASS__, 'allow_long_timeout' ), 10 );

		if ( is_wp_error( $tmp ) ) {
			return self::sideload( $fallback_seed, $product_id );
		}

		// Normalize tmp file to 1200x800 (centre-cropped) so the parent theme
		// card template renders full-bleed instead of small-image-in-red-frame.
		self::normalize_tmp_file( $tmp );

		$file_array = array(
			'name'     => sanitize_file_name( $fallback_seed . '.jpg' ),
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload( $file_array, $product_id );

		if ( is_wp_error( $attachment_id ) ) {
			if ( file_exists( $tmp ) ) {
				@unlink( $tmp );
			}
			return self::sideload( $fallback_seed, $product_id );
		}

		return (int) $attachment_id;
	}

	/**
	 * Resize+crop a tmp image file in-place to exactly 1200x800. Skips the
	 * re-encode if the file already matches. Non-fatal on failure — caller
	 * proceeds with the original.
	 *
	 * @param string $path Filesystem path to a JPEG/PNG file.
	 * @return bool True on success or no-op; false on editor failure.
	 */
	protected static function normalize_tmp_file( $path ) {
		if ( ! file_exists( $path ) ) {
			return false;
		}

		$size = @getimagesize( $path );
		if ( $size && (int) $size[0] === 1200 && (int) $size[1] === 800 ) {
			return true;
		}

		$editor = wp_get_image_editor( $path );
		if ( is_wp_error( $editor ) ) {
			return false;
		}

		$editor->resize( 1200, 800, true ); // crop = true (centre-cover)
		$saved = $editor->save( $path );

		return ! is_wp_error( $saved );
	}

	/**
	 * Normalize an already-attached image to 1200x800 and refresh its metadata.
	 * Used by the picsum.photos path where the file is in the Media Library
	 * before we can intervene.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return bool True on success or no-op; false on failure.
	 */
	protected static function normalize_attachment( $attachment_id ) {
		$path = get_attached_file( $attachment_id );
		if ( ! $path ) {
			return false;
		}

		$size = @getimagesize( $path );
		if ( $size && (int) $size[0] === 1200 && (int) $size[1] === 800 ) {
			return true;
		}

		if ( ! self::normalize_tmp_file( $path ) ) {
			return false;
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $path );
		if ( $metadata ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		return true;
	}

	protected static function load_media_helpers() {
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
	}

	public static function allow_long_timeout( $args, $url ) {
		if ( false !== strpos( $url, 'picsum.photos' )
			|| false !== strpos( $url, 'loremflickr.com' )
			|| false !== strpos( $url, 'staticflickr.com' )
			|| false !== strpos( $url, 'images.pexels.com' )
		) {
			$args['timeout'] = 20;
		}
		return $args;
	}

	/**
	 * Sideload a featured image + 3 gallery thumbnails for the product. If the
	 * spec carries an `image_urls` array (curated Unsplash URLs), those are
	 * tried first with a per-image picsum.photos fallback. Otherwise the legacy
	 * picsum-only flow runs.
	 *
	 * @param int   $product_id Target product.
	 * @param array $spec       Product spec from product-definitions.php.
	 * @return array{
	 *     featured: int|null,
	 *     gallery:  int[],
	 *     all:      int[]
	 * }
	 */
	public static function sideload_set( $product_id, $spec ) {
		$result = array(
			'featured' => null,
			'gallery'  => array(),
			'all'      => array(),
		);

		$slug         = isset( $spec['slug'] ) ? (string) $spec['slug'] : '';
		$seed_root    = isset( $spec['image_seed'] ) ? (string) $spec['image_seed'] : $slug;
		$urls         = isset( $spec['image_urls'] ) && is_array( $spec['image_urls'] ) ? $spec['image_urls'] : array();
		$featured_url = isset( $urls['featured'] ) ? (string) $urls['featured'] : '';
		$gallery_urls = isset( $urls['gallery'] ) && is_array( $urls['gallery'] ) ? array_values( $urls['gallery'] ) : array();

		// Featured.
		if ( '' !== $featured_url ) {
			$featured = self::sideload_url( $featured_url, $product_id, $seed_root );
		} else {
			$featured = self::sideload( $seed_root, $product_id );
		}
		if ( $featured ) {
			$result['featured'] = $featured;
			$result['all'][]    = $featured;
		}

		// Gallery — 3 entries, indexed fallback seeds (-g1, -g2, -g3).
		foreach ( array( '-g1', '-g2', '-g3' ) as $index => $suffix ) {
			$fallback_seed = $seed_root . $suffix;

			if ( isset( $gallery_urls[ $index ] ) && '' !== $gallery_urls[ $index ] ) {
				$att_id = self::sideload_url( $gallery_urls[ $index ], $product_id, $fallback_seed );
			} else {
				$att_id = self::sideload( $fallback_seed, $product_id );
			}

			if ( $att_id ) {
				$result['gallery'][] = $att_id;
				$result['all'][]     = $att_id;
			}
		}

		return $result;
	}
}
