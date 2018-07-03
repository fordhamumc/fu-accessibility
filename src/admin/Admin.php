<?php

namespace FU_Accessibility\admin;

use FU_Accessibility as NS;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      0.0.1
 *
 * @author    Michael Foley
 */
class Admin {
  /**
   * Singleton
   *
   * @since   0.1.0
   * @access  protected
   * @var     Admin|null
   */
  protected static $instance = null;

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $version    The current version of this plugin.
	 */
	protected $version;

	/**
	 * The text domain of this plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $text_domain    The text domain of this plugin.
	 */
	protected $text_domain;

  /**
   * Localized filter labels
   *
   * @since    0.0.1
   * @access   protected
   * @var      array
   */
  protected $labels;


  /**
   * Singleton
   *
   * @since   0.1.0
   * @return  Admin|null
   */
  public static function instance() {
    if ( is_null( self::$instance ) ) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
	 * Initialize the class and set its properties.
	 *
	 * @since       0.0.1
	 */
	public function __construct() {

    $this->plugin_name = NS\PLUGIN_NAME;
    $this->version = NS\PLUGIN_VERSION;
    $this->text_domain = NS\PLUGIN_TEXT_DOMAIN;

    $this->set_labels(array(
      'authors'     => __('Authors', $this->text_domain),
      'authorsAll'  => __('All authors', $this->text_domain),
      'alt'         => __('No alt tag', $this->text_domain)
    ) );
	}

	/**
   * Action: admin_enqueue_scripts
	 * Register the stylesheets for the admin area.
	 *
	 * @since       0.0.1
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/fu-accessibility-admin.css', array(), $this->version, 'all' );
	}

	/**
   * Action: admin_enqueue_scripts
	 * Register the JavaScript for the admin area.
	 *
	 * @since       0.0.1
	 */
	public function enqueue_scripts() {
    wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/fu-accessibility-admin.min.js', array( 'media-editor', 'media-views', 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'MediaLibraryAuthorFilterData', array( 'authors' => $this->get_authors() ) );
    wp_localize_script( $this->plugin_name, 'MediaLibraryAdditionalFilterLabels', $this->get_labels() );

    /**
     * Filter the disclaimer copy shown when attempting to insert an image without ALT text.
     *
     * @since 1.1.3
     *
     * @param string $disclaimer_copy The copy shown in the warning box.
     */
    wp_localize_script(
      $this->plugin_name,
      'AltTagsCopy',
      array(
        'txt'        => apply_filters( 'fu_alt_tag_txt', esc_html__( 'The following image(s) are missing alt text', $this->text_domain ) ),
        'editTxt'    => apply_filters( 'fu_alt_tag_edittxt', esc_html__( 'You must enter alt text for the image', $this->text_domain ) ),
        'disclaimer' => apply_filters( 'fu_alt_tag_disclaimer', esc_html__( 'Please include an ‘Alt Text’ before proceeding with inserting your image.', $this->text_domain ) ),
      )
    );
	}

  /**
   * Add labels for localization
   *
   * @since       0.0.1
   * @param       array $labels              Associative array of labels for localization
   */
	protected function set_labels($labels) {
    if (!is_array($labels)) return;
    foreach ($labels as $key => $value) {
      $this->labels[$key] = $value;
    }
  }

  /**
   * Fetches the labels
   *
   * @since       0.0.1
   * @return      array
   */
	protected function get_labels() {
	  return apply_filters('fu_accessibility_get_labels', $this->labels);
  }

  /**
   * Fetches a list of the authors. Setting dropdown to true returns
   * the args for other methods like wp_dropdown_users
   *
   * @since       0.0.1
   * @param       array $args                 Arguments to pass to get_users or wp_dropdown_users
   * @param       bool  $passthrough          Whether to return the args to pass to another method or the users array
   * @return      array
   */
  protected function get_authors($args = array(), $passthrough = false) {
    $args = array_merge(array(
      'who'     => 'authors',
      'orderby' => 'display_name'
    ), $args);

    if ( !$passthrough ) {
      if ( !array_key_exists('fields', $args) ) {
        $args['fields'] = array( 'ID', 'display_name' );
      }
      return get_users( $args );
    }
    return apply_filters('fu_accessibility_get_authors', $args);
  }

  /**
   * Action: restrict_manage_posts
   * Adds authors to the filter bar for posts, pages, and attachments list tables
   *
   * @since       0.0.1
   * @param       array $post_type            The post type for the current list
   */
  public function add_author_filter($post_type) {
    if (!in_array($post_type, array('post', 'page', 'attachment'))) return;

    $labels = $this->get_labels();
    $args = array(
      'name'            => 'author',
      'id'              => 'author-filter',
      'show_option_all' => $labels['authorsAll']
    );

    if ( isset( $_GET[ 'user' ] ) ) {
      $args[ 'selected' ] = $_GET[ 'user' ];
    }
    $args = $this->get_authors($args, true);

    echo "<label for=\"author-filter\" class=\"screen-reader-text\">{$labels['authors']}</label>";
    wp_dropdown_users( $args );
  }

  /**
   * Action: restrict_manage_posts
   * Adds a checkbox to the attachments list table to show only attachments without an alt tag
   *
   * @since       0.0.1
   */
  public function add_alt_media_filter() {
    $scr = get_current_screen();
    if ( $scr->base !== 'upload' ) return;

    $labels = $this->get_labels();
    $checked = '';

    if ( isset( $_GET[ 'no_alt' ] ) ) {
      $checked = 'checked';
    }

    echo "<input {$checked} type=\"checkbox\" id=\"media-attachment-alt-filter\" class=\"attachment-filters\" name=\"no_alt\" value=\"1\">";
    echo "<label for=\"media-attachment-alt-filter\" class=\"attachment-filters-label\">{$labels['alt']}</label>";
  }

  /**
   * Gets a meta_query to show only attachments that don't have an alt tag
   *
   * @since       0.0.1
   * @return      array
   */
  protected function get_meta_query() {
    return apply_filters('fu_accessibility_meta_query', array(
      'relation' => 'OR',
      array(
        'key' => '_wp_attachment_image_alt',
        'compare' => 'NOT EXISTS'
      ),
      array(
        'key' => '_wp_attachment_image_alt',
        'compare' => '=',
        'value' => ''
      ),
    ));
  }

  /**
   * Action: pre_get_posts
   * Updates queries containing a no_alt property to filter out posts with an alt tag
   *
   * @since       0.0.1
   */
  public function update_attachments ($query) {
    if (isset($_GET['no_alt']) && $_GET['no_alt']) {
      $query->set('meta_query', $this->get_meta_query());
    }
  }

  /**
   * Filter ajax_query_attachments_args
   * Updates ajax queries containing a no_alt property to filter out posts with an alt tag
   * Updates ajax queries containing an author property to return only attachments from that author
   *
   * @since       0.0.1
   * @return      array
   */
  public function update_ajax_attachments( $query ) {
    if ($_POST['query']['no_alt']) {
      $query['meta_query'] = $this->get_meta_query();
    }
    if ( is_numeric($_POST['query']['author']) ) {
      $query['author'] = $_POST['query']['author'];
    }
    return $query;
  }

}