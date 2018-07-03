<?php

namespace FU_Accessibility\admin;
use FU_Accessibility as NS;
use FU_Accessibility\libraries;
use WP_Post;

class Media_List extends libraries\WP_Media_List_Table {

  /**
   * The text domain of this plugin.
   *
   * @since    0.1.0
   * @access   protected
   * @var      string    $text_domain    The text domain of this plugin.
   */
  protected $text_domain;

  /**
   * Constructor
   *
   * @since    0.1.0
   * @see      WP_List_Table::__construct() for more information on default arguments.
   * @param    array    $args     An associative array of arguments.
   */
  public function __construct( $args = array() ) {
    $this->text_domain = NS\PLUGIN_TEXT_DOMAIN;
    parent::__construct( $args );
  }


  /**
   *
   * @return array
   */
  public function get_columns() {
    $posts_columns = array();
    $posts_columns['cb'] = '<input type="checkbox" />';
    $posts_columns['title'] = _x( 'File', 'column name' );
    $posts_columns['alt'] = __( 'Alt Tag' );
    $posts_columns['author'] = __( 'Author' );
    $posts_columns['date'] = _x( 'Date', 'column name' );

    return apply_filters( 'manage_alt_list_media_columns', $posts_columns, $this->detached );
  }


  /**
   * Handles the author column output.
   *
   * @since 4.3.0
   *
   * @param WP_Post $post The current WP_Post object.
   */
  public function column_alt( $post ) {
    $alt = get_post_meta($post->ID, '_wp_attachment_image_alt', true);
    $response = get_post_meta($post->ID, 'fu_alt_response', true);
    $error_message = ( isset($response) ) ? json_decode($response, true)['message'] : __('An unknown error has occured.', $this->text_domain);

    if ( empty($alt) ) {
      printf('<svg class="fu-icon %1$s" role="presentation"><use xlink:href="#%1$s" /></svg> <div class="fu-status fu-status-error"><strong>%2$s:</strong> %3$s</div>', 'fu-icon-warning', __('Error processing image', $this->text_domain), $error_message);
    } else {
      echo $alt;
    }
  }

}