<?php

namespace FU_Accessibility\admin;
use WP_Query;

class Queue {

  /**
   * Singleton
   *
   * @var Queue|null
   */
  protected static $instance = null;

  /**
   * Singleton
   *
   * @return Queue|null
   */
  public static function instance() {
    if ( is_null( self::$instance ) ) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Queue constructor.
   */
  public function __construct() {

  }

  /**
   * Get all images that don't have an alt tag
   *
   * @return array
   */
  protected function get_images() {
    $query_images_args = array(
      'post_type'      => 'attachment',
      'post_mime_type' => 'image',
      'post_status'    => 'inherit',
      'posts_per_page' => get_option( 'fu_azure_limit', 5000 ),
      'orderby'        => 'modified',
      'fields'         => 'ids',
      'meta_query'     => array(
        'relation' => 'OR',
        array(
          'key' => '_wp_attachment_image_alt',
          'compare' => 'NOT EXISTS'
        ),
        array(
          'key' => '_wp_attachment_image_alt',
          'compare' => '=',
          'value' => ''
        )
      )
    );

    $images = new WP_Query( $query_images_args );
    return $images->posts;
  }


  /**
   * Process a batch of images
   *
   * @return int    Number of images queued
   */
  public function queue_images() {
    $image_ids = self::get_images();
    foreach ($image_ids as $post_id) {
      self::queue_image($post_id);
    }
    return count($image_ids);
  }


  /**
   * Push image to the queue
   *
   * @param int   $post_id
   */
  protected function queue_image($post_id) {
    if ( self::is_attachment_locked( $post_id ) ) {
      return;
    }

    wp_queue()->push( new Alt_Job( $post_id ) );
    $lock_attachment = true;

    if ( $lock_attachment ) {
      self::lock_attachment( $post_id );
    }

  }

  /**
   * Get an image's post meta data.
   *
   * @param int $post_id ID of the image post.
   * @return mixed Post meta field. False on failure.
   */
  public static function get_image_meta( $post_id ) {
    return wp_get_attachment_metadata( $post_id );
  }

  /**
   * Is an attachment locked?
   *
   * @param int $post_id
   * @return bool
   */
  public static function is_attachment_locked( $post_id ) {
    $image_meta = self::get_image_meta( $post_id );

    if ( isset( $image_meta['fu_locked'] ) && $image_meta['fu_locked'] ) {
      return true;
    }

    return false;
  }

  /**
   * Lock an attachment to prevent multiple queue jobs being created.
   *
   * @param int $post_id
   */
  public static function lock_attachment( $post_id ) {
    $image_meta = self::get_image_meta( $post_id );

    $image_meta['fu_locked'] = true;
    wp_update_attachment_metadata( $post_id, $image_meta );
  }

  /**
   * Unlock an attachment.
   *
   * @param int $post_id
   */
  public static function unlock_attachment( $post_id ) {
    $image_meta = self::get_image_meta( $post_id );

    unset( $image_meta['fu_locked'] );
    wp_update_attachment_metadata( $post_id, $image_meta );
  }
}