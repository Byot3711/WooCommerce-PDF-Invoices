<?php
/**
 * Plugin Name:       WooCommerce PDF Invoices
 * Description:       Generate professional, sequential PDF invoices for WooCommerce orders, attach them to emails and let customers download them from their account.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 * WC tested up to:   9.5
 * Requires Plugins:  woocommerce
 * Author:            Woo PDF Invoice
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-pdf-invoice
 * Domain Path:       /languages
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'WPI_VERSION', '1.0.0' );
define( 'WPI_FILE', __FILE__ );
define( 'WPI_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPI_URL', plugin_dir_url( __FILE__ ) );
define( 'WPI_BASENAME', plugin_basename( __FILE__ ) );

/*
 * Composer autoloader (dompdf + PSR-4 classes shipped inside vendor/).
 */
$wpi_autoload = WPI_PATH . 'vendor/autoload.php';

if ( file_exists( $wpi_autoload ) ) {
	require_once $wpi_autoload;
} else {
	/*
	 * Defensive fallback so the plugin still loads its own classes when
	 * vendor/ is missing. PDF generation will be unavailable in that case.
	 */
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix = __NAMESPACE__ . '\\';

			if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) {
				return;
			}

			$relative = substr( $class, strlen( $prefix ) );
			$file     = WPI_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

			if ( is_readable( $file ) ) {
				require $file;
			}
		}
	);
}

/**
 * Boots the plugin once WooCommerce is available.
 *
 * @return void
 */
function wpi_boot(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			static function (): void {
				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'WooCommerce PDF Invoices requires WooCommerce to be installed and active.', 'woo-pdf-invoice' );
				echo '</p></div>';
			}
		);

		return;
	}

	Plugin::instance()->boot();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\wpi_boot', 20 );

register_activation_hook( __FILE__, [ Installer::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Installer::class, 'deactivate' ] );
