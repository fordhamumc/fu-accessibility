<?php

namespace FU_Accessibility\admin;

use FU_Accessibility as NS;
use WP_Query;

class Alt_List  {
  /**
   * Singleton
   *
   * @since   0.1.0
   * @access  protected
   * @var     Alt_List|null
   */
  protected static $instance = null;

  /**
   * The text domain of this plugin.
   *
   * @since   0.1.0
   * @access  protected
   * @var     string
   */
  protected $text_domain;

  /**
   * The image list table
   *
   * @since   0.1.0
   * @access  protected
   * @var     Media_List
   */
  protected $image_table;


  /**
   * Singleton
   *
   * @since   0.1.0
   * @return  Alt_List|null
   */
  public static function instance() {
    if ( is_null( self::$instance ) ) {
      self::$instance = new self();
    }
    return self::$instance;
  }


  /**
   * Initialize the settings and set its properties.
   *
   * @since   0.1.0
   */
  public function __construct() {
    $this->text_domain = NS\PLUGIN_TEXT_DOMAIN;
  }


  /**
   * Action: load-($list_hook)
   * Screen options for the alt tag list
   *
   * @since   0.1.0
   */
  public function load_alt_list_screen_options() {
    $args = array(
      'label'		=>	__( 'Images Per Page', $this->text_domain ),
      'default'	=>	50,
      'option'	=>	'upload_per_page'
    );

    add_screen_option( 'per_page', $args );

    $this->image_table = new Media_List();
  }

  public function load_alt_list_wrapper(){
    $this->image_table->prepare_items();
    include_once( 'views/alt-list-table.php' );
  }


  /**
   * Handles the author column output.
   *
   * @since 4.3.0
   *
   * @param WP_Query $wp_query The current WP_Query object.
   */

  public function pre_get_media_posts( $wp_query ) {
    global $pagenow;

    if( $pagenow !== 'admin.php' || get_current_screen()->id !== Settings::instance()->get_screens()['list'] )
      return;

    $wp_query->set( 'meta_query', array(
      array(
        'key' => 'fu_alt_status',
        'compare' => 'EXISTS'
      )
    ) );
  }
}