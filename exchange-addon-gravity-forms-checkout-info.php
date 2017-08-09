<?php
/*
Plugin Name: iThemes Exchange – Gravity Forms Checkout Info Add-on
Plugin URI: http://www.ironbounddesigns.com
Description: Add a Gravity Form for customers to fill out during the checkout process
Version: 1.6
Author: Iron Bound Designs
Author URI: http://www.ironbounddesigns.com
License: GPL2
Domain: ibd_gravity_forms_checkout_info
*/

/**
 * This registers our plugin as a product feature add-on
 *
 * @since 1.0.0
 *
 * @return void
 */
function it_exchange_register_gravity_forms_checkout_form() {
	$options = array(
	  'name'                => __( 'Gravity Forms Checkout Info', 'ibd_gravity_forms_checkout_info' ),
	  'description'         => __( 'Harness the full power of Gravity Forms during your checkout process.', 'ibd_gravity_forms_checkout_info' ),
	  'author'              => 'Iron Bound Designs',
	  'author_url'          => 'http://www.ironbounddesigns.com',
	  'file'                => dirname( __FILE__ ) . '/init.php',
	  'icon'                => IBD_GFCI_Plugin::$url . '/assets/icon-50x50.png',
	  'category'            => 'product-feature',
	  'settings-callback'   => 'it_exchange_gfci_addon_settings',
	  'basename'            => plugin_basename( __FILE__ ),
	  'labels'              => array(
		'singular_name' => __( 'Gravity Forms Checkout Form', 'ibd_gravity_forms_checkout_info' ),
	  )
	);

	if ( ibd_gfci_deps_met() )
		it_exchange_register_addon( 'ibd-gravity-forms-info-product-feature', $options );
}

add_action( 'it_exchange_register_addons', 'it_exchange_register_gravity_forms_checkout_form' );

/**
 * Loads the translation data for WordPress
 *
 * @uses load_plugin_textdomain()
 * @since 1.0.3
 * @return void
 */
function it_exchange_gfci_set_textdomain() {
	load_plugin_textdomain( 'ibd_gravity_forms_checkout_info', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}

add_action( 'plugins_loaded', 'it_exchange_gfci_set_textdomain' );

/**
 *
 */
function it_exchange_gfci_addon_show_deps_nag() {
	if ( !ibd_gfci_deps_met() ) {
		?>
		<div id="it-exchange-add-on-deps-nag" class="it-exchange-nag">
			<?php _e( 'You must have Gravity Forms active to use the Gravity Forms Checkout Info Exchange Add-on.', 'ibd_gravity_forms_checkout_info' ); ?>
		</div>
	<?php
	}
}

add_action( 'admin_notices', 'it_exchange_gfci_addon_show_deps_nag' );

/**
 * Determine if all of our deps are met
 *
 * @return bool
 */
function ibd_gfci_deps_met() {
	return class_exists( 'GFForms' );
}

/**
 * Class IBD_GFCI_Plugin
 */
class IBD_GFCI_Plugin {
	/**
	 *
	 */
	const SLUG = 'ibd_gravity_forms_checkout_info';

	/**
	 * @var string
	 */
	static $dir;

	/**
	 * @var string
	 */
	static $url;

	/**
	 *
	 */
	public function __construct() {
		self::$dir = plugin_dir_path( __FILE__ );
		self::$url = plugin_dir_url( __FILE__ );
		spl_autoload_register( array( "IBD_GFCI_Plugin", "autoload" ) );
	}

	/**
	 * Autoloader
	 *
	 * @param $class_name string
	 */
	public static function autoload( $class_name ) {
		if ( substr( $class_name, 0, 8 ) != "IBD_GFCI" ) {
			$path = self::$dir . "lib/classes";
			$class = strtolower( $class_name );

			$name = str_replace( "_", "-", $class );
		}
		else {
			$path = self::$dir . "lib";

			$class = substr( $class_name, 8 );
			$class = strtolower( $class );

			$parts = explode( "_", $class );
			$name = array_pop( $parts );

			$path .= implode( "/", $parts );
		}

		$path .= "/class.$name.php";

		if ( file_exists( $path ) ) {
			require( $path );

			return;
		}

		if ( file_exists( str_replace( "class.", "abstract.", $path ) ) ) {
			require( str_replace( "class.", "abstract.", $path ) );

			return;
		}

		if ( file_exists( str_replace( "class.", "interface.", $path ) ) ) {
			require( str_replace( "class.", "interface.", $path ) );

			return;
		}
	}
}

new IBD_GFCI_Plugin();