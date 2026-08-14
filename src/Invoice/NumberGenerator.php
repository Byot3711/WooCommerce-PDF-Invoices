<?php
/**
 * Sequential invoice number generator.
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice\Invoice;

use WooPdfInvoice\Admin\Settings;

/**
 * Class NumberGenerator
 */
final class NumberGenerator {

	/**
	 * Option key holding the next invoice counter.
	 *
	 * @var string
	 */
	public const COUNTER_OPTION = 'wpi_invoice_counter';

	/**
	 * Assigns the next number and advances the counter atomically.
	 *
	 * @return string
	 */
	public function next(): string {
		$settings = Settings::get();

		$counter = max( 1, (int) get_option( self::COUNTER_OPTION, 1 ) );
		$padding = max( 1, min( 12, (int) ( $settings['invoice_padding'] ?? 4 ) ) );

		$formatted = str_pad( (string) $counter, $padding, '0', STR_PAD_LEFT );

		update_option( self::COUNTER_OPTION, $counter + 1, false );

		return $this->compose( $settings, $formatted );
	}

	/**
	 * Previews the next number without consuming it.
	 *
	 * @return string
	 */
	public function preview(): string {
		$settings = Settings::get();
		$counter  = max( 1, (int) get_option( self::COUNTER_OPTION, 1 ) );
		$padding  = max( 1, min( 12, (int) ( $settings['invoice_padding'] ?? 4 ) ) );

		return $this->compose( $settings, str_pad( (string) $counter, $padding, '0', STR_PAD_LEFT ) );
	}

	/**
	 * Builds the final number from prefix + core + suffix.
	 *
	 * @param array  $settings Settings array.
	 * @param string $core     Padded numeric core.
	 *
	 * @return string
	 */
	private function compose( array $settings, string $core ): string {
		$prefix = (string) ( $settings['invoice_prefix'] ?? '' );
		$suffix = (string) ( $settings['invoice_suffix'] ?? '' );

		return $prefix . $core . $suffix;
	}
}
