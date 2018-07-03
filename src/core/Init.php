<?php

namespace FU_Accessibility\core;
use FU_Accessibility as NS;
use FU_Accessibility\admin as Admin;

/**
 * The core plugin class.
 * Defines internationalization, admin-specific hooks, and public-facing site hooks.
 *
 * @since      0.0.1
 *
 * @author     Michael Foley
 */
class Init {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $plugin_base_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_basename;

	/**
	 * The current version of the plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The text domain of the plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $text_domain;

	/**
	 * Initialize and define the core functionality of the plugin.
	 */
	public function __construct() {

		$this->plugin_name = NS\PLUGIN_NAME;
		$this->version = NS\PLUGIN_VERSION;
    $this->plugin_basename = NS\PLUGIN_BASENAME;
    $this->text_domain = NS\PLUGIN_TEXT_DOMAIN;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Loads the following required dependencies for this plugin.
	 *
	 * - Loader - Orchestrates the hooks of the plugin.
	 * - Internationalization_I18n - Defines internationalization functionality.
	 * - Admin - Defines all hooks for the admin area.
	 * - Frontend - Defines all hooks for the public side of the site.
	 *
	 * @access    private
	 */
	private function load_dependencies() {
		$this->loader = new Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Internationalization_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @access    private
	 */
	private function set_locale() {

		$plugin_i18n = new Internationalization_I18n( $this->text_domain );
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @access    private
	 */
	private function define_admin_hooks() {

		$admin = Admin\Admin::instance();
    $settings = Admin\Settings::instance();
    $alt_list = Admin\Alt_List::instance();

		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
    $this->loader->add_action( 'pre_get_posts', $admin, 'update_attachments' );
    $this->loader->add_filter( 'ajax_query_attachments_args', $admin, 'update_ajax_attachments' );
    $this->loader->add_action( 'restrict_manage_posts', $admin, 'add_author_filter' );
    $this->loader->add_action( 'restrict_manage_posts', $admin, 'add_alt_media_filter' );

    $this->loader->add_action( 'admin_menu', $settings, 'add_admin_menu' );
    $this->loader->add_action( 'admin_init', $settings, 'add_sections' );
    $this->loader->add_action( 'admin_init', $settings, 'add_fields' );
    $this->loader->add_action( 'admin_post_fu_accessibility_update', $settings, 'form_response');
    $this->loader->add_filter( 'removable_query_args', $settings, 'update_removable_query_args');

    $this->loader->add_filter( 'pre_get_posts', $alt_list, 'pre_get_media_posts');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     0.0.1
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Retrieve the text domain of the plugin.
	 *
	 * @since     0.0.1
	 * @return    string    The text domain of the plugin.
	 */
	public function get_text_domain() {
		return $this->text_domain;
	}

}
