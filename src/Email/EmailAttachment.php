<?php
/**
 * Attaches generated invoice PDFs to WooCommerce customer emails.
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice\Email;

use WC_Order;
use WooPdfInvoice\Admin\Settings;
use WooPdfInvoice\Invoice\InvoiceRepository;
use WooPdfInvoice\Pdf\Engine;
use WooPdfInvoice\Pdf\Renderer;

/**
 * Class EmailAttachment
 */
final class EmailAttachment {

	/**
	 * Invoice repository.
	 *
	 * @var InvoiceRepository
	 */
	private InvoiceRepository $repository;

	/**
	 * HTML renderer.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;

	/**
	 * PDF engine.
	 *
	 * @var Engine
	 */
	private Engine $engine;

	/**
	 * Temporary files pending cleanup.
	 *
	 * @var string[]
	 */
	private static array $temp_files = array();

	/**
	 * Whether the shutdown cleanup has been registered.
	 *
	 * @var bool
	 */
	private static bool $cleanup_registered = false;

	/**
	 * EmailAttachment constructor.
	 *
	 * @param InvoiceRepository $repository Repository.
	 * @param Renderer          $renderer   Renderer.
	 * @param Engine            $engine     PDF engine.
	 */
	public function __construct( InvoiceRepository $repository, Renderer $renderer, Engine $engine ) {
		$this->repository = $repository;
		$this->renderer   = $renderer;
		$this->engine     = $engine;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'woocommerce_email_attachments', array( $this, 'attach' ), 10, 4 );
	}

	/**
	 * Attaches the invoice PDF to the configured emails.
	 *
	 * @param array     $attachments Existing attachment paths.
	 * @param string    $email_id    Email ID.
	 * @param mixed     $object      Email object (WC_Order for customer emails).
	 * @param \WC_Email $email       Email instance.
	 *
	 * @return array
	 */
	public function attach( array $attachments, string $email_id, $object, $email = null ): array {
		$enabled = (array) Settings::value( 'attach_emails', array() );

		if ( ! in_array( $email_id, $enabled, true ) || ! $object instanceof WC_Order ) {
			return $attachments;
		}

		try {
			$invoice = $this->repository->get_or_create( $object );
			$html    = $this->renderer->render( $object, $invoice );
			$pdf     = $this->engine->generate( $html );
		} catch ( \Throwable $e ) {
			$this->log( $email_id, $e->getMessage() );

			return $attachments;
		}

		$filename = sanitize_file_name( sprintf( 'invoice-%s-%d.pdf', $invoice->number(), $object->get_id() ) );
		$path     = $this->write_temp( $pdf, $filename );

		if ( '' !== $path ) {
			$attachments[] = $path;
		}

		return $attachments;
	}

	/**
	 * Writes the PDF to a temporary file and schedules its cleanup.
	 *
	 * @param string $pdf      PDF bytes.
	 * @param string $filename Base filename.
	 *
	 * @return string Absolute path, or empty string on failure.
	 */
	private function write_temp( string $pdf, string $filename ): string {
		$dir  = get_temp_dir();
		$path = $dir . $filename;

		if ( file_exists( $path ) ) {
			$path = $dir . wp_unique_filename( $dir, $filename );
		}

		if ( false === file_put_contents( $path, $pdf ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return '';
		}

		self::$temp_files[] = $path;
		self::register_cleanup();

		return $path;
	}

	/**
	 * Registers the one-time shutdown cleanup.
	 *
	 * @return void
	 */
	private static function register_cleanup(): void {
		if ( self::$cleanup_registered ) {
			return;
		}

		self::$cleanup_registered = true;

		register_shutdown_function(
			static function (): void {
				foreach ( self::$temp_files as $file ) {
					if ( is_string( $file ) && is_file( $file ) ) {
						@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					}
				}

				self::$temp_files = array();
			}
		);
	}

	/**
	 * Logs a generation failure.
	 *
	 * @param string $email_id Email ID.
	 * @param string $message  Error message.
	 *
	 * @return void
	 */
	private function log( string $email_id, string $message ): void {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->warning(
				sprintf( 'Invoice attachment failed for %s: %s', $email_id, $message ),
				array( 'source' => 'sequential-pdf-invoices' )
			);

			return;
		}

		error_log( sprintf( '[woo-pdf-invoice] %s: %s', $email_id, $message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}
