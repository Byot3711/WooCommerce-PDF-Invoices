<?php
/**
 * Main plugin container.
 *
 * Wires dependencies together and registers every module on `boot()`.
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice;

use WooPdfInvoice\Admin\OrderActions;
use WooPdfInvoice\Admin\Settings;
use WooPdfInvoice\Core\Download;
use WooPdfInvoice\Email\EmailAttachment;
use WooPdfInvoice\Frontend\MyAccount;
use WooPdfInvoice\Invoice\InvoiceRepository;
use WooPdfInvoice\Invoice\NumberGenerator;
use WooPdfInvoice\Pdf\Engine;
use WooPdfInvoice\Pdf\Renderer;

/**
 * Class Plugin
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Sequential number generator.
	 *
	 * @var NumberGenerator
	 */
	private NumberGenerator $number_generator;

	/**
	 * Invoice persistence layer.
	 *
	 * @var InvoiceRepository
	 */
	private InvoiceRepository $invoice_repository;

	/**
	 * dompdf wrapper.
	 *
	 * @var Engine
	 */
	private Engine $pdf_engine;

	/**
	 * HTML renderer.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;

	/**
	 * Download endpoint.
	 *
	 * @var Download
	 */
	private Download $download;

	/**
	 * Admin order screen integration.
	 *
	 * @var OrderActions
	 */
	private OrderActions $order_actions;

	/**
	 * Customer-facing download links.
	 *
	 * @var MyAccount
	 */
	private MyAccount $my_account;

	/**
	 * Email attachment integration.
	 *
	 * @var EmailAttachment
	 */
	private EmailAttachment $email_attachment;

	/**
	 * Returns the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor: build the dependency graph.
	 */
	private function __construct() {
		$this->settings            = new Settings();
		$this->number_generator    = new NumberGenerator();
		$this->invoice_repository  = new InvoiceRepository( $this->number_generator );
		$this->pdf_engine          = new Engine();
		$this->renderer            = new Renderer();
		$this->download            = new Download( $this->invoice_repository, $this->renderer, $this->pdf_engine );
		$this->order_actions       = new OrderActions( $this->invoice_repository );
		$this->my_account          = new MyAccount( $this->invoice_repository );
		$this->email_attachment    = new EmailAttachment( $this->invoice_repository, $this->renderer, $this->pdf_engine );
	}

	/**
	 * Boots the plugin.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->load_textdomain();
		$this->settings->register_hooks();
		$this->download->register_hooks();
		$this->order_actions->register_hooks();
		$this->my_account->register_hooks();
		$this->email_attachment->register_hooks();
	}

	/**
	 * Loads translation files.
	 *
	 * @return void
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain( 'sequential-pdf-invoices', false, dirname( WPI_BASENAME ) . '/languages' );
	}

	/**
	 * Invoice repository accessor.
	 *
	 * @return InvoiceRepository
	 */
	public function invoice_repository(): InvoiceRepository {
		return $this->invoice_repository;
	}

	/**
	 * Settings accessor.
	 *
	 * @return Settings
	 */
	public function settings(): Settings {
		return $this->settings;
	}

	/**
	 * PDF engine accessor.
	 *
	 * @return Engine
	 */
	public function pdf_engine(): Engine {
		return $this->pdf_engine;
	}

	/**
	 * HTML renderer accessor.
	 *
	 * @return Renderer
	 */
	public function renderer(): Renderer {
		return $this->renderer;
	}
}
