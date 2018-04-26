<?php

namespace FU_Accessibility\Inc\Admin;

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
	 * The ID of this plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The text domain of this plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 * @var      string    $plugin_text_domain    The text domain of this plugin.
	 */
	private $plugin_text_domain;

  /**
   * Localized filter labels
   *
   * @since    0.0.1
   * @access   private
   * @var      array
   */
  private $labels;

  /**
	 * Initialize the class and set its properties.
	 *
	 * @since       0.0.1
	 * @param       string $plugin_name        The name of this plugin.
	 * @param       string $version            The version of this plugin.
	 * @param       string $plugin_text_domain The text domain of this plugin.
	 */
	public function __construct( $plugin_name, $version, $plugin_text_domain ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin_text_domain = $plugin_text_domain;

    $this->set_labels(array(
      'authors'     => __('Authors', $this->plugin_text_domain),
      'authorsAll'  => __('All authors', $this->plugin_text_domain),
      'alt'         => __('No alt tag', $this->plugin_text_domain)
    ) );

    add_action('restrict_manage_posts', array($this, 'add_author_filter') );
    add_action('restrict_manage_posts', array($this, 'add_alt_media_filter') );
	}

	/**
   * Action admin_enqueue_scripts
	 * Register the stylesheets for the admin area.
	 *
	 * @since       0.0.1
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/fu-accessibility-admin.css', array(), $this->version, 'all' );
	}

	/**
   * Action admin_enqueue_scripts
	 * Register the JavaScript for the admin area.
	 *
	 * @since       0.0.1
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/fu-accessibility-media-filter.js', array( 'media-editor', 'media-views' ), $this->version, false );
    wp_localize_script( $this->plugin_name, 'MediaLibraryAuthorFilterData', array( 'authors' => $this->get_authors() ) );
    wp_localize_script( $this->plugin_name, 'MediaLibraryAdditionalFilterLabels', $this->get_labels() );

	}

  /**
   * Add labels for localization
   *
   * @since       0.0.1
   */
	private function set_labels($labels) {
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
	private function get_labels() {
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
  private function get_authors($args = array(), $passthrough = false) {
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
   * Action restrict_manage_posts
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
   * Action restrict_manage_posts
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
  private function get_meta_query() {
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
   * Action pre_get_posts
   * Updates queries containing a no_alt property to filter out posts with an alt tag
   *
   * @since       0.0.1
   */
  public function update_attachments ($query) {
    if ($_GET['no_alt']) {
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
