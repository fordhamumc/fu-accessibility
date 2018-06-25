<?php

namespace FU_Accessibility\admin;

use WP_Queue\Job;

class Alt_Job extends Job {

  /**
   * @var int
   */
  public $post_id;

  /**
   * Image_Processing_Job constructor.
   *
   * @param int   $post_id
   */
  public function __construct( $post_id ) {
    $this->post_id = $post_id;
  }

  public function handle() {
    $image_meta = Queue::get_image_meta( $this->post_id );


    unset( $image_meta['fu_locked'] );
    wp_update_attachment_metadata( $this->post_id, $image_meta );
  }
}