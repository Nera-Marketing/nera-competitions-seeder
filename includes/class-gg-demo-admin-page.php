<?php
/**
 * Tools → Nera Competitions Seeder admin page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GG_Demo_Admin_Page {

	const MENU_SLUG    = 'nera-competitions-seeder';
	const NONCE_ACTION = 'gg_demo_products_action';
	const CAPABILITY   = 'manage_woocommerce';

	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
	}

	public function add_menu() {
		add_management_page(
			__( 'Nera Competitions Seeder', 'nera-competitions-seeder' ),
			__( 'Nera Competitions Seeder', 'nera-competitions-seeder' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function handle_actions() {
		if ( empty( $_POST['gg_demo_action'] ) ) {
			return;
		}
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		check_admin_referer( self::NONCE_ACTION );

		$action = sanitize_key( wp_unslash( $_POST['gg_demo_action'] ) );
		$slug   = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';

		$created      = 0;
		$skipped      = 0;
		$failed       = 0;
		$wiped        = 0;
		$missing_deps = 0;

		$tally = function ( $result ) use ( &$created, &$skipped, &$failed, &$missing_deps ) {
			if ( is_wp_error( $result ) ) {
				switch ( $result->get_error_code() ) {
					case 'already_seeded':
						$skipped++;
						break;
					case 'gg_demo_plugin_missing':
						$missing_deps++;
						break;
					default:
						$failed++;
				}
			} else {
				$created++;
			}
		};

		switch ( $action ) {
			case 'seed_one':
				if ( $slug ) {
					foreach ( GG_Demo_Seeder::get_definitions() as $spec ) {
						if ( $spec['slug'] !== $slug ) {
							continue;
						}
						$tally( GG_Demo_Seeder::seed_one( $spec ) );
						break;
					}
				}
				break;

			case 'seed_all':
				foreach ( GG_Demo_Seeder::get_definitions() as $spec ) {
					$tally( GG_Demo_Seeder::seed_one( $spec ) );
				}
				break;

			case 'wipe_all':
				$wiped = GG_Demo_Seeder::wipe_all();
				break;
		}

		$redirect = add_query_arg(
			array(
				'page'         => self::MENU_SLUG,
				'gg_result'    => 1,
				'created'      => $created,
				'skipped'      => $skipped,
				'failed'       => $failed,
				'wiped'        => $wiped,
				'missing_deps' => $missing_deps,
			),
			admin_url( 'tools.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	public function render_notices() {
		if ( empty( $_GET['gg_result'] ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->id !== 'tools_page_' . self::MENU_SLUG ) {
			return;
		}

		$created      = isset( $_GET['created'] ) ? (int) $_GET['created'] : 0;
		$skipped      = isset( $_GET['skipped'] ) ? (int) $_GET['skipped'] : 0;
		$failed       = isset( $_GET['failed'] ) ? (int) $_GET['failed'] : 0;
		$wiped        = isset( $_GET['wiped'] ) ? (int) $_GET['wiped'] : 0;
		$missing_deps = isset( $_GET['missing_deps'] ) ? (int) $_GET['missing_deps'] : 0;

		$bits = array();
		if ( $created ) {
			$bits[] = sprintf( _n( '%d product created.', '%d products created.', $created, 'nera-competitions-seeder' ), $created );
		}
		if ( $skipped ) {
			$bits[] = sprintf( _n( '%d product already existed (skipped).', '%d products already existed (skipped).', $skipped, 'nera-competitions-seeder' ), $skipped );
		}
		if ( $wiped ) {
			$bits[] = sprintf( _n( '%d product wiped.', '%d products wiped.', $wiped, 'nera-competitions-seeder' ), $wiped );
		}

		if ( $bits ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( implode( ' ', $bits ) )
			);
		}

		if ( $failed ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html( sprintf( _n( '%d product failed to seed. Check that WooCommerce and Lottery for WooCommerce are both active.', '%d products failed to seed. Check that WooCommerce and Lottery for WooCommerce are both active.', $failed, 'nera-competitions-seeder' ), $failed ) )
			);
		}

		if ( $missing_deps ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
				esc_html( sprintf(
					_n(
						'%d product skipped — required plugin is not active. Check the table below for details.',
						'%d products skipped — required plugin is not active. Check the table below for details.',
						$missing_deps,
						'nera-competitions-seeder'
					),
					$missing_deps
				) )
			);
		}
	}

	public function render_page() {
		$defs       = GG_Demo_Seeder::get_definitions();
		$lottery_ok = class_exists( 'WC_Product_Lottery' );

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Nera Competitions Seeder', 'nera-competitions-seeder' ); ?></h1>

			<p>
				<?php esc_html_e( 'Seeds representative lottery / giveaway products covering every variant supported by Lottery for WooCommerce. Each active product is scheduled to end one year from creation, gets a featured image plus three gallery thumbnails, and is populated with 50 demo "purchased" tickets so the Entry List table is realistic. The instant-win product additionally has one winner per instant-prize rule already marked as won. All seeded products are assigned to the "Demo Competitions" category.', 'nera-competitions-seeder' ); ?>
			</p>

			<?php if ( ! $lottery_ok ) : ?>
				<div class="notice notice-error inline">
					<p><?php esc_html_e( 'Lottery for WooCommerce is not active. Activate it before seeding.', 'nera-competitions-seeder' ); ?></p>
				</div>
			<?php endif; ?>

			<p style="margin: 1.5em 0;">
				<form method="post" style="display: inline-block; margin-right: 0.5em;">
					<?php wp_nonce_field( self::NONCE_ACTION ); ?>
					<input type="hidden" name="gg_demo_action" value="seed_all">
					<?php submit_button( __( 'Seed All Demo Competitions', 'nera-competitions-seeder' ), 'primary', 'submit', false, $lottery_ok ? array() : array( 'disabled' => 'disabled' ) ); ?>
				</form>

				<form method="post" style="display: inline-block;"
					onsubmit="return confirm('<?php echo esc_js( __( 'Delete all seeded demo competitions and their images? This cannot be undone.', 'nera-competitions-seeder' ) ); ?>');">
					<?php wp_nonce_field( self::NONCE_ACTION ); ?>
					<input type="hidden" name="gg_demo_action" value="wipe_all">
					<?php submit_button( __( 'Wipe All Demo Competitions', 'nera-competitions-seeder' ), 'delete', 'submit', false ); ?>
				</form>
			</p>

			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 38%;"><?php esc_html_e( 'Title', 'nera-competitions-seeder' ); ?></th>
						<th style="width: 22%;"><?php esc_html_e( 'Variant', 'nera-competitions-seeder' ); ?></th>
						<th style="width: 20%;"><?php esc_html_e( 'Status', 'nera-competitions-seeder' ); ?></th>
						<th style="width: 20%;"><?php esc_html_e( 'Action', 'nera-competitions-seeder' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $defs as $spec ) :
						$record       = GG_Demo_Seeder::get_record( $spec['slug'] );
						$id           = (int) $record['product_id'];
						$exists       = $id && get_post( $id );
						$tickets      = count( $record['ticket_ids'] );
						$winners      = count( $record['winner_log_ids'] );
						$dep_basename = isset( $spec['requires_plugin'] ) ? (string) $spec['requires_plugin'] : '';
						$dep_missing  = $dep_basename && ! is_plugin_active( $dep_basename );
					?>
						<tr>
							<td><strong><?php echo esc_html( $spec['title'] ); ?></strong></td>
							<td><code><?php echo esc_html( $spec['variant'] ); ?></code></td>
							<td>
								<?php if ( $exists ) : ?>
									<span style="color: #1a7f37;">&#10003;</span>
									<a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">
										#<?php echo (int) $id; ?>
									</a>
									&middot;
									<a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank">
										<?php esc_html_e( 'View', 'nera-competitions-seeder' ); ?>
									</a>
									<?php if ( $tickets || $winners ) : ?>
										<br>
										<small style="color: #555;">
											<?php
											$bits = array();
											if ( $tickets ) {
												$bits[] = sprintf( _n( '%d ticket', '%d tickets', $tickets, 'nera-competitions-seeder' ), $tickets );
											}
											if ( $winners ) {
												$bits[] = sprintf( _n( '%d winner', '%d winners', $winners, 'nera-competitions-seeder' ), $winners );
											}
											echo esc_html( implode( ' &middot; ', $bits ) );
											?>
										</small>
									<?php endif; ?>
								<?php elseif ( $dep_missing ) : ?>
									<span style="color: #b32d2e;">
										<?php
										printf(
											/* translators: %s = plugin basename */
											esc_html__( 'Requires plugin: %s', 'nera-competitions-seeder' ),
											'<code>' . esc_html( $dep_basename ) . '</code>'
										);
										?>
									</span>
								<?php else : ?>
									<span style="color: #777;"><?php esc_html_e( 'Not seeded', 'nera-competitions-seeder' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! $exists && $lottery_ok && ! $dep_missing ) : ?>
									<form method="post" style="display: inline;">
										<?php wp_nonce_field( self::NONCE_ACTION ); ?>
										<input type="hidden" name="gg_demo_action" value="seed_one">
										<input type="hidden" name="slug" value="<?php echo esc_attr( $spec['slug'] ); ?>">
										<button type="submit" class="button"><?php esc_html_e( 'Seed', 'nera-competitions-seeder' ); ?></button>
									</form>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p style="margin-top: 1.5em; color: #666; font-size: 12px;">
				<?php esc_html_e( 'Placeholder images are sideloaded from picsum.photos. If your server cannot reach the internet, products will be seeded without images.', 'nera-competitions-seeder' ); ?>
			</p>
		</div>
		<?php
	}
}
