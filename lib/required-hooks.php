<?php
/**
 *
 * @package Exchange Addon Gravity Forms Checkout Info
 * @subpackage Lib
 * @since 1.0
 */

/**
 * Register the purchase requirement
 */
function ibd_gfci_register_purchase_requirement() {
	$properties = array(
	  'priority'               => 4.5,
	  'requirement-met'        => 'ibd_gfci_all_checkout_gravity_forms_submitted',
	  'sw-template-part'       => 'gravity-forms-checkout-info',
	  'checkout-template-part' => 'gravity-forms-checkout-info',
	  'notification'           => 'We need some more information from you before you can checkout',
	);
	it_exchange_register_purchase_requirement( 'gravity-forms-checkout-info', $properties );
}

add_action( 'init', 'ibd_gfci_register_purchase_requirement' );

/**
 * Determine if all of the checkout gravity forms have been submitted
 *
 * @return bool
 */
function ibd_gfci_all_checkout_gravity_forms_submitted() {

	$products = it_exchange_get_cart_products();

	foreach ( $products as $product ) {
		if ( !it_exchange_product_has_feature( $product['product_id'], 'ibd-gravity-forms-info' ) )
			continue;

		$forms = it_exchange_get_session_data( 'ibd_gfci_checkout_forms' );

		if ( !isset( $forms[$product['product_id']] ) )
			return false;
	}

	return true;
}

/**
 * Force the gravity forms checkout info as a valid SW state
 * during non multi item cart because the purchase requirements seem to get wiped.
 *
 * @param $valid_states array
 *
 * @return array
 */
function ibd_gfci_force_sw_valid_states_on_multi_item_cart( $valid_states ) {
	if ( ! it_exchange_is_multi_item_cart_allowed() )
		$valid_states[] = 'gravity-forms-checkout-info';

	return $valid_states;
}

add_filter( 'it_exchange_super_widget_valid_states', 'ibd_gfci_force_sw_valid_states_on_multi_item_cart' );

/**
 * Add a hidden field to the gravity form
 * so that we can detect this is a purchase requirement
 * related form during submission
 *
 * @param $form_html string
 * @param $form array
 *
 * @return string
 */
function ibd_gfci_add_hidden_field_to_gravity_form_during_checkout( $form_html, $form ) {

	$form_id = $form['id'];
	$products = it_exchange_get_cart_products();
	$product_id = null;

	foreach ( $products as $product ) {
		if ( $form_id == it_exchange_get_product_feature( $product['product_id'], 'ibd-gravity-forms-info',
				array( 'field' => 'form_id' ) ) ) {

			$product_id = $product['product_id'];
			break;
		}
	}

	if ( ! isset( $product_id ) ) {
		return $form_html;
	}

	$form_html .= "<input type='hidden' name='ibd_gravity_forms_info_product_id' value='$product_id'>";

	return $form_html;
}

add_filter( 'gform_form_tag', 'ibd_gfci_add_hidden_field_to_gravity_form_during_checkout', 10, 2 );

/**
 * Save gravity form submission
 *
 * @param $lead array
 * @param $form array
 */
function ibd_gfci_process_purchase_requirement_gravity_form_submission( $lead, $form ) {
	if ( !isset( $_POST['ibd_gravity_forms_info_product_id'] ) )
		return;

	$forms = it_exchange_get_session_data( 'ibd_gfci_checkout_forms' );

	if ( !is_array( $forms ) )
		$forms = array();

	$forms[$_POST['ibd_gravity_forms_info_product_id']] = $lead['id'];

	it_exchange_update_session_data( 'ibd_gfci_checkout_forms', $forms );
}

add_action( 'gform_after_submission', 'ibd_gfci_process_purchase_requirement_gravity_form_submission', 10, 2 );

/**
 * Save our GFCI submission with its product
 * in the exchange transaction object
 *
 * @param $products array
 * @param $key string
 * @param $product array
 *
 * @return object
 */
function ibd_gfci_save_gravity_form_submission_to_transaction_product( $products, $key, $product ) {

	$forms = it_exchange_get_session_data( 'ibd_gfci_checkout_forms' );

	foreach ( $forms as $product_id => $submission )
		if ( $product_id == $product['product_id'] )
			$products[$key]['ibd_gfci_entry_id'] = $submission;

	return $products;
}

add_filter( 'it_exchange_generate_transaction_object_products', 'ibd_gfci_save_gravity_form_submission_to_transaction_product', 10, 3 );

/**
 * Clear our gravity form submission data
 * after the transaction object has been generated
 *
 * @param $transaction_object object
 *
 * @return object
 */
function ibd_gfci_clear_gravity_form_session( $transaction_object ) {
	it_exchange_clear_session_data( 'ibd_gfci_checkout_forms' );

	return $transaction_object;
}

add_filter( 'it_exchange_transaction_object', 'ibd_gfci_clear_gravity_form_session' );

/**
 * When the shopping cart is emptied, clear the session.
 *
 * @since 1.6
 */
function ibd_gfci_clear_session_on_empty_cart() {
	it_exchange_clear_session_data( 'ibd_gfci_checkout_forms' );
}

add_action( 'it_exchange_empty_shopping_cart', 'ibd_gfci_clear_session_on_empty_cart' );

/**
 * When a product is removed from the cart, clear the session for that product.
 *
 * @since 1.6
 *
 * @param string $cart_product_id
 */
function ibd_gfci_clear_session_on_product_removed( $cart_product_id ) {

	$forms = it_exchange_get_session_data( 'ibd_gfci_checkout_forms' );

	unset( $forms[ (int) $cart_product_id ] );

	it_exchange_update_session_data( 'ibd_gfci_checkout_forms', $forms );
}

add_action( 'it_exchange_delete_cart_product', 'ibd_gfci_clear_session_on_product_removed' );

/**
 * Display the gravity forms submission data in the transaction admin panel
 *
 * @param WP_Post $post
 * @param $transaction_product array
 */
function ibd_gfci_display_gravity_forms_submission_data_on_transaction_admin( $post, $transaction_product ) {
	if ( !isset( $transaction_product['ibd_gfci_entry_id'] ) )
		return;

	$lead_id = $transaction_product['ibd_gfci_entry_id'];
	$lead = RGFormsModel::get_lead( $lead_id );
	$form_id = $lead['form_id'];
	$form = RGFormsModel::get_form($form_id);

	$url = "admin.php?page=gf_entries&view=entry&id=$form_id&lid=$lead_id";
	echo "<h4><a href='{$url}'>" . sprintf( __( 'View Gravity Form Checkout Info: %s', IBD_GFCI_Plugin::SLUG ), $form->title ) . "</a></h4>";
}

add_action( 'it_exchange_transaction_details_end_product_details', 'ibd_gfci_display_gravity_forms_submission_data_on_transaction_admin', 10, 2 );

/**
 * Register our template paths
 *
 * @param array $paths existing template paths
 *
 * @return array
 */
function it_exchange_ibd_gfci_addon_add_template_paths( $paths = array() ) {
	$paths[] = IBD_GFCI_Plugin::$dir . "lib/templates";

	return $paths;
}

add_filter( 'it_exchange_possible_template_paths', 'it_exchange_ibd_gfci_addon_add_template_paths' );

/**
 * Enqueue Gravity Forms JS on checkout pages
 * with a Gravity Form Checkout Info product.
 *
 * @since 1.3
 */
function it_exchange_ibd_gfci_enqueue_gravity_forms_scripts_on_checkout() {

	$form_ids = array();

	if ( it_exchange_in_superwidget() || it_exchange_is_page( 'checkout' ) ) {
		$products = it_exchange_get_cart_products();

		// loop through products, and if a product has the feature, then enqueue the scripts
		foreach ( $products as $product ) {
			if ( it_exchange_product_has_feature( $product['product_id'], 'ibd-gravity-forms-info' ) ) {
				$form_ids[] = it_exchange_get_product_feature( $product['product_id'], 'ibd-gravity-forms-info', array( 'field' => 'form_id' ) ) ;
			}
		}
	} else {
		return;
	}

	$req = it_exchange_get_next_purchase_requirement();

	if ( $req && $req['slug'] == 'gravity-forms-checkout-info' ) {
		require_once( GFCommon::get_base_path() . "/form_display.php" );

		foreach ( $form_ids as $form_id ) {
			$form = GFFormsModel::get_form_meta( $form_id );

			if ( it_exchange_in_superwidget() ) {
				GFFormDisplay::print_form_scripts( $form, true );

				wp_enqueue_script( 'gform_gravityforms' );
				wp_print_scripts( array( 'gform_gravityforms' ) );
			} else {
				GFFormDisplay::enqueue_form_scripts( $form, true );

				wp_enqueue_script( 'gform_gravityforms' );
			}
		}
	}
}

add_action( 'wp_enqueue_scripts', 'it_exchange_ibd_gfci_enqueue_gravity_forms_scripts_on_checkout' );
add_action( 'it_exchange_super_widget_ajax_top', 'it_exchange_ibd_gfci_enqueue_gravity_forms_scripts_on_checkout' );

/**
 * Append the Gravity Forms fields to the admin store confirmation email.
 *
 * @since 1.5
 *
 * @param string $content
 * @param IT_Exchange_Transaction $transaction
 *
 * @return string
 */
function it_exchange_ibd_gfci_append_fields_to_admin_email( $content, $transaction ) {

	$options = it_exchange_get_option( 'addon_ibd_gfci' );

	if ( isset( $options['add-fields-to-admin-email'] ) && $options['add-fields-to-admin-email'] ) {

		$content .= "<h3>" . __( "Gravity Forms Checkout Info", IBD_GFCI_Plugin::SLUG ) . "</h3>";

		foreach ( $transaction->get_products() as $product ) {
			if ( it_exchange_product_has_feature( $product['product_id'], 'ibd-gravity-forms-info' ) ) {

				$lead_id = $product['ibd_gfci_entry_id'];
				$lead = RGFormsModel::get_lead( $lead_id );

				$form_id = $lead['form_id'];
				$form = RGFormsModel::get_form_meta( $form_id );

				$title = $product['product_name'] . " – " . $form['title'];
				$gf_content = GFCommon::replace_variables( '{all_fields}', (array) $form, $lead );

				$content .= "<h4>$title</h4>";
				$content .= $gf_content;
			}
		}
	}

	return $content;
}

add_filter( 'send_admin_emails_body', 'it_exchange_ibd_gfci_append_fields_to_admin_email', 10, 2 );