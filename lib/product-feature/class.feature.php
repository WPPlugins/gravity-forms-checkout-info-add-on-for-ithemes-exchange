<?php

/**
 *
 * @package Exchange Addon Gravity Forms Checkout Info
 * @subpackage Product Feature
 * @since 1.0
 */
class LDMW_Conference_Exchange_Feature extends IT_Exchange_Product_Feature_Abstract {
	/**
	 * Register our product feature for selecting a Gravity Form
	 *
	 * @param array $args
	 */
	function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @param $post WP_Post
	 *
	 * @since 1.7.27
	 * @return void
	 */
	function print_metabox( $post ) {
		$form_id = it_exchange_get_product_feature( $post->ID, $this->slug, array( 'field' => 'form_id' ) );
		?>
		<p><?php echo $this->description; ?></p>

		<label for="ibd_gravity_forms_info_form_select">Select the Gravity Form to display</label>
		<select id="ibd_gravity_forms_info_form_select" name="ibd_gravity_forms_info_form">
			<?php foreach ( self::get_gravity_forms_select_data() as $id => $title ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $form_id ); ?>><?php echo esc_html( $title ); ?></option>
			<?php endforeach; ?>
	    </select>

	<?php
	}

	/**
	 * This saves the value
	 *
	 * @since 1.7.27
	 *
	 * @return void
	 */
	function save_feature_on_product_save() {
		// Abort if we don't have a product ID
		$product_id = empty( $_POST['ID'] ) ? false : $_POST['ID'];
		if ( !$product_id )
			return;

		$form_id = $_POST['ibd_gravity_forms_info_form'];

		if ( GFFormsModel::get_form( $form_id ) )
			$form_id = (int) $form_id;
		else
			$form_id = false;

		$data = array( 'form_id' => $form_id );

		it_exchange_update_product_feature( $product_id, $this->slug, $data );
	}

	/**
	 * This updates the feature for a product
	 *
	 * @since 1.7.27
	 *
	 * @param integer $product_id the product id
	 * @param mixed $new_value the new value
	 * @param array $options
	 *
	 * @return boolean
	 */
	function save_feature( $product_id, $new_value, $options = array() ) {
		return update_post_meta( $product_id, '_it_exchange_product_feature_' . $this->slug, $new_value );
	}

	/**
	 * Return the product's features
	 *
	 * @since 1.7.27
	 *
	 * @param mixed $existing the values passed in by the WP Filter API. Ignored here.
	 * @param integer $product_id the WordPress post ID
	 * @param array $options
	 *
	 * @return string product feature
	 */
	function get_feature( $existing, $product_id, $options = array() ) {

		$raw_meta = get_post_meta( $product_id, '_it_exchange_product_feature_' . $this->slug, true );

		$defaults = array(
		  'form_id' => false
		);

		$raw_meta = wp_parse_args( $raw_meta, $defaults );

		if ( !isset( $options['field'] ) ) // if we aren't looking for a particular field
			return $raw_meta;

		$field = $options['field'];

		if ( isset( $raw_meta[$field] ) ) { // if the field exists with that name just return it
			return $raw_meta[$field];
		}
		else if ( strpos( $field, "." ) !== false ) { // if the field name was passed using array dot notation
			$pieces = explode( '.', $field );
			$context = $raw_meta;
			foreach ( $pieces as $piece ) {
				if ( !is_array( $context ) || !array_key_exists( $piece, $context ) ) {
					// error occurred
					return null;
				}
				$context = & $context[$piece];
			}

			return $context;
		}
		else {
			return null; // we didn't find the data specified
		}
	}

	/**
	 * Does the product have the feature?
	 *
	 * @since 1.7.27
	 *
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @param array $options
	 *
	 * @return boolean
	 */
	function product_has_feature( $result, $product_id, $options = array() ) {
		if ( false === it_exchange_product_supports_feature( $product_id, $this->slug ) )
			return false;

		return (boolean) it_exchange_get_product_feature( $product_id, $this->slug, array( 'field' => 'form_id' ) );
	}

	/**
	 * Does the product support this feature?
	 *
	 * This is different than if it has the feature, a product can
	 * support a feature but might not have the feature set.
	 *
	 * @since 1.7.27
	 *
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @param array $options
	 *
	 * @return boolean
	 */
	function product_supports_feature( $result, $product_id, $options = array() ) {
		$product_type = it_exchange_get_product_type( $product_id );
		if ( !it_exchange_product_type_supports_feature( $product_type, $this->slug ) )
			return false;

		return true;
	}

	public static function get_gravity_forms_select_data() {
		$select_data = array();
		$select_data[- 1] = "--Disabled--";

		foreach ( GFFormsModel::get_forms( 1 ) as $form ) {
			$select_data[$form->id] = $form->title;
		}

		return $select_data;
	}

}

new LDMW_Conference_Exchange_Feature( array(
	'slug'          => 'ibd-gravity-forms-info',
	'description'   => 'Add a Gravity Form for customers to fill out during the checkout process',
	'metabox_title' => 'Gravity Forms Checkout Info'
  )
);