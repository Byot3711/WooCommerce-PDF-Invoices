<?php
/**
 * Activation / deactivation hooks.
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice;

use WooPdfInvoice\Admin\Settings;

/**
 * Class Installer
 */
final class Installer {

	/**
	 * Database version key.
	 *
	 * @var string
	 */
	public const DB_VERSION_OPTION = 'wpi_db_version';

	/**
	 * Current schema version.
	 *
	 * @var string
	 */
	private const DB_VERSION = '1.0.0';

	/**
	 * Runs on plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( false === get_option( 'wpi_settings', false ) ) {
			update_option( 'wpi_settings', Settings::defaults(), false );
		}

		if ( false === get_option( NumberGenerator::COUNTER_OPTION, false ) ) {
			update_option( NumberGenerator::COUNTER_OPTION, 1, false );
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );

		self::maybe_upgrade();
	}

	/**
	 * Runs on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Intentionally empty: keep options and meta for reactivation.
	}

	/**
	 * Runs any pending schema upgrades.
	 *
	 * @return void
	 */
	private static function maybe_upgrade(): void {
		$installed = (string) get_option( self::DB_VERSION_OPTION, '' );

		if ( version_compare( $installed, self::DB_VERSION, '>=' ) ) {
			return;
		}

		// Future migrations land here.
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
	}
}
