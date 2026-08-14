<?php
/**
 * Uninstall routine.
 *
 * Removes every option, order meta entry and cached PDF the plugin created.
 * Runs in a standalone WordPress context: WooCommerce may already be
 * deactivated, so we operate directly on the database tables.
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$option_names = array(
	'wpi_settings',
	'wpi_invoice_counter',
	'wpi_db_version',
);

foreach ( $option_names as $option ) {
	delete_option( $option );
}

$meta_keys = array(
	'_wpi_invoice_number',
	'_wpi_invoice_date',
	'_wpi_invoice_token',
);

/*
 * Classic post storage (orders as wp_posts).
 */
foreach ( $meta_keys as $meta_key ) {
	$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->postmeta,
		array( 'meta_key' => $meta_key ),
		array( '%s' )
	);
}

/*
 * High-Performance Order Storage (orders in custom tables).
 */
$hpos_meta_table = $wpdb->prefix . 'wc_orders_meta';

if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $hpos_meta_table ) ) === $hpos_meta_table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( $meta_keys as $meta_key ) {
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$hpos_meta_table,
			array( 'meta_key' => $meta_key ),
			array( '%s' )
		);
	}
}

/*
 * Delete generated PDF cache directory, if any.
 */
$upload_dir = wp_upload_dir();

if ( ! empty( $upload_dir['basedir'] ) ) {
	$cache_dir = trailingslashit( $upload_dir['basedir'] ) . 'wpi-invoices';

	if ( is_dir( $cache_dir ) ) {
		$files = glob( trailingslashit( $cache_dir ) . '*' ) ?: array();

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		}

		@rmdir( $cache_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
}
