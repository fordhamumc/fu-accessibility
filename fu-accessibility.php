<?php
/**
 * @package           FU_Accessibility
 * @since             0.0.1
 *
 * @wordpress-plugin
 * Plugin Name:       Fordham Accessibility Helper
 * Plugin URI:        http://news.fordham.edu
 * Description:       Adds accessibility helpers to Wordpress.
 * Version:           0.1.0
 * Author:            Michael Foley
 * Author URI:        https://michaeldfoley.com
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       fu-accessibility
 * Domain Path:       /languages
 */

namespace FU_Accessibility;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Constants
 */

define( __NAMESPACE__ . '\NS', __NAMESPACE__ . '\\' );

define( NS . 'PLUGIN_NAME', 'fu_accessibility' );

define( NS . 'PLUGIN_VERSION', '0.1.0' );

define( NS . 'PLUGIN_NAME_DIR', plugin_dir_path( __FILE__ ) );

define( NS . 'PLUGIN_NAME_URL', plugin_dir_url( __FILE__ ) );

define( NS . 'PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

define( NS . 'PLUGIN_TEXT_DOMAIN', 'fu-accessibility' );


/**
 * Autoload Classes
 */
require_once( PLUGIN_NAME_DIR . 'vendor/autoload.php');

/**
 * Register Activation and Deactivation Hooks
 * This action is documented in inc/core/class-activator.php
 */

register_activation_hook( __FILE__, array( NS . 'Src\Core\Activator', 'activate' ) );

/**
 * The code that runs during plugin deactivation.
 * This action is documented inc/core/class-deactivator.php
 */

register_deactivation_hook( __FILE__, array( NS . 'Src\Core\Deactivator', 'deactivate' ) );


/**
 * Plugin Singleton Container
 *
 * Maintains a single copy of the plugin app object
 *
 * @since    0.0.1
 */
class FU_Accessibility {

	/**
	 * The instance of the plugin.
	 *
	 * @since    0.0.1
	 */
	private static $init;
	/**
	 * Loads the plugin
	 *
	 * @access    public
	 */
	public static function init() {

		if ( null === self::$init ) {
			self::$init = new core\Init();
			self::$init->run();
		}

		return self::$init;
	}

}

/**
 * Begins execution of the plugin
 **/
function fu_accessibility_init() {
  return FU_Accessibility::init();
}

$min_php = '5.6.0';

// Check the minimum required PHP version and run the plugin.
if ( version_compare( PHP_VERSION, $min_php, '>=' ) ) {
  fu_accessibility_init();
}
