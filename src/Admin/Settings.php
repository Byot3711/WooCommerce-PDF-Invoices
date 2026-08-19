<?php
/**
 * Plugin settings: option storage, admin page and sanitization.
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice\Admin;

/**
 * Class Settings
 */
final class Settings {

	/**
	 * Option key.
	 *
	 * @var string
	 */
	public const OPTION_KEY = 'wpi_settings';

	/**
	 * Settings group (for the Settings API).
	 *
	 * @var string
	 */
	private const GROUP = 'wpi_settings_group';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private const PAGE = 'wpi-settings';

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'company_name'      => '',
			'company_address'   => '',
			'company_city'      => '',
			'company_postcode'  => '',
			'company_country'   => '',
			'company_tax_id'    => '',
			'company_reg_no'    => '',
			'company_email'     => '',
			'company_phone'     => '',
			'company_bank'      => '',
			'company_iban'      => '',
			'company_logo'      => 0,
			'invoice_prefix'    => 'F-',
			'invoice_suffix'    => '',
			'invoice_padding'   => 4,
			'invoice_due_days'  => 15,
			'invoice_footer'    => '',
			'tax_label'         => __( 'VAT', 'sequential-pdf-invoices' ),
			'pdf_paper_size'    => 'A4',
			'pdf_orientation'   => 'portrait',
			'attach_emails'     => array( 'customer_processing_order', 'customer_completed_order' ),
		);
	}

	/**
	 * Returns merged settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( self::defaults(), $stored );
	}

	/**
	 * Returns a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value.
	 *
	 * @return mixed
	 */
	public static function value( string $key, $default = null ) {
		$all = self::get();

		return $all[ $key ] ?? $default;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Registers the settings page under WooCommerce.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'PDF Invoices', 'sequential-pdf-invoices' ),
			__( 'PDF Invoices', 'sequential-pdf-invoices' ),
			'manage_woocommerce',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueues assets on our settings page only.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'woocommerce_page_' . self::PAGE !== $hook ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'wpi-admin',
			WPI_URL . 'assets/css/admin.css',
			array(),
			WPI_VERSION
		);

		wp_enqueue_script(
			'wpi-admin',
			WPI_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WPI_VERSION,
			true
		);

		wp_localize_script(
			'wpi-admin',
			'wpiAdmin',
			array(
				'logoTitle'     => __( 'Select logo', 'sequential-pdf-invoices' ),
				'useThisImage'  => __( 'Use this image', 'sequential-pdf-invoices' ),
				'deleteConfirm' => __( 'Delete this invoice? This action cannot be undone.', 'sequential-pdf-invoices' ),
			)
		);
	}

	/**
	 * Registers the settings API fieldset.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::GROUP,
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		$this->add_sections();
		$this->add_fields();
	}

	/**
	 * Adds settings sections.
	 *
	 * @return void
	 */
	private function add_sections(): void {
		add_settings_section(
			'wpi_company',
			__( 'Company details', 'sequential-pdf-invoices' ),
			static function (): void {
				echo '<p>' . esc_html__( 'These details appear in the invoice header and footer.', 'sequential-pdf-invoices' ) . '</p>';
			},
			self::PAGE
		);

		add_settings_section(
			'wpi_numbering',
			__( 'Invoice numbering', 'sequential-pdf-invoices' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Control the sequential invoice number format.', 'sequential-pdf-invoices' ) . '</p>';
			},
			self::PAGE
		);

		add_settings_section(
			'wpi_pdf',
			__( 'PDF & appearance', 'sequential-pdf-invoices' ),
			null,
			self::PAGE
		);

		add_settings_section(
			'wpi_email',
			__( 'Emails', 'sequential-pdf-invoices' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Attach the invoice PDF to the selected customer emails.', 'sequential-pdf-invoices' ) . '</p>';
			},
			self::PAGE
		);
	}

	/**
	 * Registers every settings field.
	 *
	 * @return void
	 */
	private function add_fields(): void {
		$texts = array(
			'company_name'      => array( 'wpi_company', __( 'Company name', 'sequential-pdf-invoices' ) ),
			'company_address'   => array( 'wpi_company', __( 'Address', 'sequential-pdf-invoices' ) ),
			'company_city'      => array( 'wpi_company', __( 'City', 'sequential-pdf-invoices' ) ),
			'company_postcode'  => array( 'wpi_company', __( 'Postcode', 'sequential-pdf-invoices' ) ),
			'company_country'   => array( 'wpi_company', __( 'Country', 'sequential-pdf-invoices' ) ),
			'company_tax_id'    => array( 'wpi_company', __( 'Tax ID / VAT (CUI)', 'sequential-pdf-invoices' ) ),
			'company_reg_no'    => array( 'wpi_company', __( 'Trade register no.', 'sequential-pdf-invoices' ) ),
			'company_email'     => array( 'wpi_company', __( 'Email', 'sequential-pdf-invoices' ) ),
			'company_phone'     => array( 'wpi_company', __( 'Phone', 'sequential-pdf-invoices' ) ),
			'company_bank'      => array( 'wpi_company', __( 'Bank', 'sequential-pdf-invoices' ) ),
			'company_iban'      => array( 'wpi_company', __( 'IBAN', 'sequential-pdf-invoices' ) ),
		);

		foreach ( $texts as $key => $cfg ) {
			add_settings_field(
				$key,
				$cfg[1],
				array( $this, 'field_text' ),
				self::PAGE,
				$cfg[0],
				array(
					'label_for' => $key,
					'key'       => $key,
				)
			);
		}

		add_settings_field(
			'company_logo',
			__( 'Logo', 'sequential-pdf-invoices' ),
			array( $this, 'field_logo' ),
			self::PAGE,
			'wpi_company',
			array( 'label_for' => 'company_logo' )
		);

		add_settings_field(
			'invoice_prefix',
			__( 'Prefix', 'sequential-pdf-invoices' ),
			array( $this, 'field_text' ),
			self::PAGE,
			'wpi_numbering',
			array( 'label_for' => 'invoice_prefix', 'key' => 'invoice_prefix' )
		);

		add_settings_field(
			'invoice_suffix',
			__( 'Suffix', 'sequential-pdf-invoices' ),
			array( $this, 'field_text' ),
			self::PAGE,
			'wpi_numbering',
			array( 'label_for' => 'invoice_suffix', 'key' => 'invoice_suffix' )
		);

		add_settings_field(
			'invoice_padding',
			__( 'Number padding', 'sequential-pdf-invoices' ),
			array( $this, 'field_number' ),
			self::PAGE,
			'wpi_numbering',
			array(
				'label_for' => 'invoice_padding',
				'key'       => 'invoice_padding',
				'min'       => 1,
				'max'       => 12,
				'help'      => __( 'Zeros before the number, e.g. 4 -> F-0001.', 'sequential-pdf-invoices' ),
			)
		);

		add_settings_field(
			'invoice_due_days',
			__( 'Payment due (days)', 'sequential-pdf-invoices' ),
			array( $this, 'field_number' ),
			self::PAGE,
			'wpi_numbering',
			array(
				'label_for' => 'invoice_due_days',
				'key'       => 'invoice_due_days',
				'min'       => 0,
				'max'       => 365,
				'help'      => __( '0 hides the due date.', 'sequential-pdf-invoices' ),
			)
		);

		add_settings_field(
			'pdf_paper_size',
			__( 'Paper size', 'sequential-pdf-invoices' ),
			array( $this, 'field_select' ),
			self::PAGE,
			'wpi_pdf',
			array(
				'label_for' => 'pdf_paper_size',
				'key'       => 'pdf_paper_size',
				'options'   => array(
					'A4'     => 'A4',
					'A5'     => 'A5',
					'Letter' => 'Letter',
					'Legal'  => 'Legal',
				),
			)
		);

		add_settings_field(
			'pdf_orientation',
			__( 'Orientation', 'sequential-pdf-invoices' ),
			array( $this, 'field_select' ),
			self::PAGE,
			'wpi_pdf',
			array(
				'label_for' => 'pdf_orientation',
				'key'       => 'pdf_orientation',
				'options'   => array(
					'portrait'  => __( 'Portrait', 'sequential-pdf-invoices' ),
					'landscape' => __( 'Landscape', 'sequential-pdf-invoices' ),
				),
			)
		);

		add_settings_field(
			'tax_label',
			__( 'Tax label', 'sequential-pdf-invoices' ),
			array( $this, 'field_text' ),
			self::PAGE,
			'wpi_pdf',
			array( 'label_for' => 'tax_label', 'key' => 'tax_label' )
		);

		add_settings_field(
			'invoice_footer',
			__( 'Footer text', 'sequential-pdf-invoices' ),
			array( $this, 'field_textarea' ),
			self::PAGE,
			'wpi_pdf',
			array(
				'label_for' => 'invoice_footer',
				'key'       => 'invoice_footer',
				'help'      => __( 'Payment terms, thank-you note, etc. Basic HTML allowed.', 'sequential-pdf-invoices' ),
			)
		);

		add_settings_field(
			'attach_emails',
			__( 'Attach to emails', 'sequential-pdf-invoices' ),
			array( $this, 'field_emails' ),
			self::PAGE,
			'wpi_email',
			array( 'label_for' => 'attach_emails' )
		);
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		echo '<div class="wrap wpi-settings">';
		echo '<h1>' . esc_html__( 'Sequential PDF Invoices', 'sequential-pdf-invoices' ) . '</h1>';

		echo '<form action="options.php" method="post">';
		settings_fields( self::GROUP );
		do_settings_sections( self::PAGE );
		submit_button();
		echo '</form>';

		echo '</div>';
	}

	/**
	 * Renders a text field.
	 *
	 * @param array $args Field args.
	 *
	 * @return void
	 */
	public function field_text( array $args ): void {
		$key = $args['key'] ?? '';

		printf(
			'<input type="text" class="regular-text" id="%1$s" name="%2$s[%1$s]" value="%3$s" />',
			esc_attr( $key ),
			esc_attr( self::OPTION_KEY ),
			esc_attr( (string) self::value( $key, '' ) )
		);
	}

	/**
	 * Renders a number field.
	 *
	 * @param array $args Field args.
	 *
	 * @return void
	 */
	public function field_number( array $args ): void {
		$key = $args['key'] ?? '';
		$min = (int) ( $args['min'] ?? 0 );
		$max = (int) ( $args['max'] ?? 999 );

		printf(
			'<input type="number" class="small-text" id="%1$s" name="%2$s[%1$s]" value="%3$s" min="%4$d" max="%5$d" />',
			esc_attr( $key ),
			esc_attr( self::OPTION_KEY ),
			esc_attr( (string) self::value( $key, '' ) ),
			esc_attr( (string) $min ),
			esc_attr( (string) $max )
		);

		if ( ! empty( $args['help'] ) ) {
			echo '<p class="description">' . esc_html( $args['help'] ) . '</p>';
		}
	}

	/**
	 * Renders a select field.
	 *
	 * @param array $args Field args.
	 *
	 * @return void
	 */
	public function field_select( array $args ): void {
		$key     = $args['key'] ?? '';
		$options = (array) ( $args['options'] ?? array() );
		$current = (string) self::value( $key, '' );

		printf( '<select id="%1$s" name="%2$s[%1$s]">', esc_attr( $key ), esc_attr( self::OPTION_KEY ) );

		foreach ( $options as $value => $label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( (string) $value ),
				selected( $current, (string) $value, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Renders a textarea field.
	 *
	 * @param array $args Field args.
	 *
	 * @return void
	 */
	public function field_textarea( array $args ): void {
		$key = $args['key'] ?? '';

		printf(
			'<textarea class="large-text" id="%1$s" name="%2$s[%1$s]" rows="4">%3$s</textarea>',
			esc_attr( $key ),
			esc_attr( self::OPTION_KEY ),
			esc_textarea( (string) self::value( $key, '' ) )
		);

		if ( ! empty( $args['help'] ) ) {
			echo '<p class="description">' . esc_html( $args['help'] ) . '</p>';
		}
	}

	/**
	 * Renders the logo picker.
	 *
	 * @return void
	 */
	public function field_logo(): void {
		$id    = (int) self::value( 'company_logo', 0 );
		$thumb = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';

		echo '<div class="wpi-logo-field">';
		printf(
			'<input type="hidden" id="company_logo" name="%1$s[company_logo]" value="%2$d" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( (string) $id )
		);

		echo '<div class="wpi-logo-preview">';
		if ( $thumb ) {
			printf( '<img src="%s" alt="" style="max-width:220px;height:auto;" />', esc_url( $thumb ) );
		}
		echo '</div>';

		echo '<p>';
		printf(
			'<button type="button" class="button wpi-select-logo">%s</button> ',
			esc_html__( 'Select logo', 'sequential-pdf-invoices' )
		);
		printf(
			'<button type="button" class="button-link-delete wpi-remove-logo">%s</button>',
			esc_html__( 'Remove', 'sequential-pdf-invoices' )
		);
		echo '</p>';

		echo '<p class="description">' . esc_html__( 'Recommended: PNG or JPG, max ~800px wide.', 'sequential-pdf-invoices' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Renders the email attachment checkboxes.
	 *
	 * @return void
	 */
	public function field_emails(): void {
		$emails  = $this->available_emails();
		$current = (array) self::value( 'attach_emails', array() );

		foreach ( $emails as $id => $label ) {
			printf(
				'<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="%1$s[attach_emails][]" value="%2$s" %3$s /> %4$s</label>',
				esc_attr( self::OPTION_KEY ),
				esc_attr( $id ),
				checked( in_array( $id, $current, true ), true, false ),
				esc_html( $label )
			);
		}
	}

	/**
	 * Lists WooCommerce customer emails available for attachment.
	 *
	 * @return array<string, string>
	 */
	private function available_emails(): array {
		return array(
			'customer_processing_order' => __( 'Processing order', 'sequential-pdf-invoices' ),
			'customer_completed_order'  => __( 'Completed order', 'sequential-pdf-invoices' ),
			'customer_on_hold_order'    => __( 'On-hold order', 'sequential-pdf-invoices' ),
			'customer_invoice'          => __( 'Customer invoice / payment request', 'sequential-pdf-invoices' ),
		);
	}

	/**
	 * Sanitizes the settings array on save.
	 *
	 * @param mixed $input Raw input.
	 *
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$input  = is_array( $input ) ? $input : array();
		$output = self::defaults();

		$text_keys = array(
			'company_name',
			'company_address',
			'company_city',
			'company_postcode',
			'company_country',
			'company_tax_id',
			'company_reg_no',
			'company_email',
			'company_phone',
			'company_bank',
			'company_iban',
			'invoice_prefix',
			'invoice_suffix',
			'tax_label',
		);

		foreach ( $text_keys as $key ) {
			$output[ $key ] = sanitize_text_field( (string) ( $input[ $key ] ?? '' ) );
		}

		$output['company_email'] = sanitize_email( $output['company_email'] );
		$output['company_logo']  = absint( $input['company_logo'] ?? 0 );
		$output['invoice_padding'] = min( 12, max( 1, absint( $input['invoice_padding'] ?? 4 ) ) );
		$output['invoice_due_days'] = min( 365, max( 0, absint( $input['invoice_due_days'] ?? 15 ) ) );
		$output['invoice_footer'] = wp_kses_post( (string) ( $input['invoice_footer'] ?? '' ) );

		$output['pdf_paper_size'] = in_array( $input['pdf_paper_size'] ?? '', array( 'A4', 'A5', 'Letter', 'Legal' ), true )
			? sanitize_key( $input['pdf_paper_size'] )
			: 'A4';

		$output['pdf_orientation'] = in_array( $input['pdf_orientation'] ?? '', array( 'portrait', 'landscape' ), true )
			? sanitize_key( $input['pdf_orientation'] )
			: 'portrait';

		$emails = isset( $input['attach_emails'] ) && is_array( $input['attach_emails'] )
			? $input['attach_emails']
			: array();

		$output['attach_emails'] = array_values(
			array_filter(
				array_map( 'sanitize_key', $emails ),
				function ( string $id ): bool {
					return array_key_exists( $id, $this->available_emails() );
				}
			)
		);

		return $output;
	}
}
