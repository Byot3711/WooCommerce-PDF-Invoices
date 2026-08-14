<?php
/**
 * Invoice persistence via order meta (HPOS + classic compatible).
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice\Invoice;

use WC_Order;

/**
 * Class InvoiceRepository
 */
final class InvoiceRepository {

	/**
	 * Meta key storing the invoice number.
	 *
	 * @var string
	 */
	public const META_NUMBER = '_wpi_invoice_number';

	/**
	 * Meta key storing the invoice date.
	 *
	 * @var string
	 */
	public const META_DATE = '_wpi_invoice_date';

	/**
	 * Meta key storing the guest access token.
	 *
	 * @var string
	 */
	public const META_TOKEN = '_wpi_invoice_token';

	/**
	 * Number generator.
	 *
	 * @var NumberGenerator
	 */
	private NumberGenerator $generator;

	/**
	 * InvoiceRepository constructor.
	 *
	 * @param NumberGenerator $generator Number generator.
	 */
	public function __construct( NumberGenerator $generator ) {
		$this->generator = $generator;
	}

	/**
	 * Returns the invoice for an order, creating it if necessary.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return Invoice
	 */
	public function get_or_create( WC_Order $order ): Invoice {
		$existing = $this->find( $order );

		return $existing ?? $this->create( $order );
	}

	/**
	 * Returns an existing invoice, or null.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return Invoice|null
	 */
	public function find( WC_Order $order ): ?Invoice {
		$number = (string) $order->get_meta( self::META_NUMBER );

		if ( '' === $number ) {
			return null;
		}

		$token = (string) $order->get_meta( self::META_TOKEN );

		return new Invoice(
			$number,
			(string) $order->get_meta( self::META_DATE ),
			$order->get_id(),
			'' === $token ? null : $token
		);
	}

	/**
	 * Creates and persists a new invoice.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return Invoice
	 */
	public function create( WC_Order $order ): Invoice {
		$number = $this->generator->next();
		$date   = current_time( 'mysql' );
		$token  = wp_generate_password( 32, false, false );

		$order->update_meta_data( self::META_NUMBER, $number );
		$order->update_meta_data( self::META_DATE, $date );
		$order->update_meta_data( self::META_TOKEN, $token );
		$order->save();

		return new Invoice( $number, $date, $order->get_id(), $token );
	}

	/**
	 * Removes the invoice from an order.
	 *
	 * The counter is intentionally not decremented: gaps in numbering are
	 * expected (and desirable) in accounting sequences.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function delete( WC_Order $order ): void {
		$order->delete_meta_data( self::META_NUMBER );
		$order->delete_meta_data( self::META_DATE );
		$order->delete_meta_data( self::META_TOKEN );
		$order->save();
	}

	/**
	 * Manually sets (or clears) the invoice number.
	 *
	 * @param WC_Order $order  Order.
	 * @param string   $number Number, or empty string to clear.
	 *
	 * @return void
	 */
	public function set_number( WC_Order $order, string $number ): void {
		$number = trim( $number );

		if ( '' === $number ) {
			$this->delete( $order );

			return;
		}

		$order->update_meta_data( self::META_NUMBER, $number );

		if ( '' === (string) $order->get_meta( self::META_DATE ) ) {
			$order->update_meta_data( self::META_DATE, current_time( 'mysql' ) );
		}

		if ( '' === (string) $order->get_meta( self::META_TOKEN ) ) {
			$order->update_meta_data( self::META_TOKEN, wp_generate_password( 32, false, false ) );
		}

		$order->save();
	}
}
