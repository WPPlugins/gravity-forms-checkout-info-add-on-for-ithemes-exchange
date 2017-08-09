<?php
/**
 * Main add-on settings page.
 *
 * @author Iron Bound Designs
 * @since  1.5
 */

/**
 * Load the plugin settings page.
 *
 * @since 1.5
 */
function it_exchange_gfci_addon_settings() {
	$settings = new IBD_GFCI_Settings();
	$settings->print_settings_page();
}

/**
 * Class IT_Exchange_gfci_Add_On_Settings
 */
class IBD_GFCI_Settings {
	/**
	 * @var boolean $_is_admin true or false
	 * @since 0.1.0
	 */
	var $_is_admin;
	/**
	 * @var string $_current_page Current $_GET['page'] value
	 * @since 0.1.0
	 */
	var $_current_page;
	/**
	 * @var string $_current_add_on Current $_GET['add-on-settings'] value
	 * @since 0.1.0
	 */
	var $_current_add_on;
	/**
	 * @var string $status_message will be displayed if not empty
	 * @since 0.1.0
	 */
	var $status_message;
	/**
	 * @var string $error_message will be displayed if not empty
	 * @since 0.1.0
	 */
	var $error_message;

	/**
	 * Class constructor
	 *
	 * Sets up the class.
	 *
	 * @since 1.0
	 */
	function __construct() {
		$this->_is_admin       = is_admin();
		$this->_current_page   = empty( $_GET['page'] ) ? false : $_GET['page'];
		$this->_current_add_on = empty( $_GET['add-on-settings'] ) ? false : $_GET['add-on-settings'];
		if ( ! empty( $_POST ) && $this->_is_admin && 'it-exchange-addons' == $this->_current_page
		     && 'ibd-gravity-forms-info-product-feature' == $this->_current_add_on
		) {
			add_action( 'it_exchange_save_add_on_settings_gfci', array(
				$this,
				'save_settings'
			) );
			do_action( 'it_exchange_save_add_on_settings_gfci' );
		}
	}

	/**
	 * Prints settings page
	 *
	 * @since 0.4.5
	 * @return void
	 */
	function print_settings_page() {
		$settings     = it_exchange_get_option( 'addon_ibd_gfci', true );
		$form_values  = empty( $this->error_message ) ? $settings : ITForm::get_post_data();
		$form_options = array(
			'id'     => apply_filters( 'it_exchange_add_on_gfci', 'it-exchange-add-on-gfci-settings' ),
			'action' => 'admin.php?page=it-exchange-addons&add-on-settings=ibd-gravity-forms-info-product-feature',
		);

		$form = new ITForm( $form_values, array( 'prefix' => 'ibd_gfci' ) );
		if ( ! empty ( $this->status_message ) ) {
			ITUtility::show_status_message( $this->status_message );
		}

		if ( ! empty( $this->error_message ) ) {
			ITUtility::show_error_message( $this->error_message );
		}
		?>
		<div class="wrap">
			<h2><?php _e( 'Gravity Forms Checkout Info Settings', IBD_GFCI_Plugin::SLUG ); ?></h2>

			<?php do_action( 'it_exchange_gfci_settings_page_top' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_top' ); ?>
			<?php $form->start_form( $form_options, 'it-exchange-gfci-settings' ); ?>
			<?php do_action( 'it_exchange_gfci_settings_form_top' ); ?>
			<?php $this->get_form_table( $form, $form_values ); ?>
			<?php do_action( 'it_exchange_gfci_settings_form_bottom' ); ?>

			<p class="submit">
				<?php $form->add_submit( 'submit', array(
					'value' => __( 'Save Changes', IBD_GFCI_Plugin::SLUG ),
					'class' => 'button button-primary button-large'
				) ); ?>
			</p>

			<?php $form->end_form(); ?>
			<?php do_action( 'it_exchange_gfci_settings_page_bottom' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
		</div>
	<?php
	}

	/**
	 * Render the settings table
	 *
	 * @param ITForm $form
	 * @param array  $settings
	 *
	 * @return void
	 */
	function get_form_table( $form, $settings = array() ) {
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $key => $var ) {
				$form->set_option( $key, $var );
			}
		}
		?>

		<div class="it-exchange-addon-settings it-exchange-gfci-addon-settings">
			<label>
				<?php $form->add_check_box( 'add-fields-to-admin-email' ); ?>
				<?php _e( "Append the submitted Gravity Form fields to the admin order email notification.", IBD_GFCI_Plugin::SLUG ); ?>
			</label>
		</div>
	<?php
	}

	/**
	 * Save settings
	 *
	 * @since 0.1.0
	 * @return void
	 */
	function save_settings() {
		$defaults   = it_exchange_get_option( 'addon_ibd_gfci' );
		$new_values = wp_parse_args( ITForm::get_post_data(), $defaults );

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'it-exchange-gfci-settings' ) ) {
			$this->error_message = __( 'Error. Please try again', IBD_GFCI_Plugin::SLUG );

			return;
		}

		$errors = apply_filters( 'it_exchange_add_on_gfci_validate_settings', $this->get_form_errors( $new_values ), $new_values );

		if ( ! $errors && it_exchange_save_option( 'addon_ibd_gfci', $new_values ) ) {
			ITUtility::show_status_message( __( 'Settings saved.', IBD_GFCI_Plugin::SLUG ) );
		} else if ( $errors ) {
			$errors              = implode( '<br />', $errors );
			$this->error_message = $errors;
		} else {
			$this->status_message = __( 'Settings not saved.', IBD_GFCI_Plugin::SLUG );
		}
	}

	/**
	 * Validates for values
	 *
	 * Returns string of errors if anything is invalid
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_form_errors( $values ) {
		$errors = array();

		return $errors;
	}
}