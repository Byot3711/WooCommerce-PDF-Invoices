<?php
/**
 * Immutable invoice value object.
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice\Invoice;

/**
 * Class Invoice
 */
final class Invoice {

	/**
	 * Formatted invoice number (prefix + padded number + suffix).
	 *
	 * @var string
	 */
	private string $number;

	/**
	 * Invoice creation date (MySQL format).
	 *
	 * @var string
	 */
	private string $date;

	/**
	 * Related order ID.
	 *
	 * @var int
	 */
	private int $order_id;

	/**
	 * Secret access token for guest download links.
	 *
	 * @var string|null
	 */
	private ?string $token;

	/**
	 * Invoice constructor.
	 *
	 * @param string      $number   Invoice number.
	 * @param string      $date     Invoice date (MySQL).
	 * @param int         $order_id Order ID.
	 * @param string|null $token    Access token.
	 */
	public function __construct( string $number, string $date, int $order_id, ?string $token = null ) {
		$this->number   = $number;
		$this->date     = $date;
		$this->order_id = $order_id;
		$this->token    = $token;
	}

	/**
	 * Returns the invoice number.
	 *
	 * @return string
	 */
	public function number(): string {
		return $this->number;
	}

	/**
	 * Returns the invoice date (MySQL format).
	 *
	 * @return string
	 */
	public function date(): string {
		return $this->date;
	}

	/**
	 * Returns the order ID.
	 *
	 * @return int
	 */
	public function order_id(): int {
		return $this->order_id;
	}

	/**
	 * Returns the access token.
	 *
	 * @return string|null
	 */
	public function token(): ?string {
		return $this->token;
	}
}
