<?php

namespace FU_Accessibility\admin;

use WP_Queue\Job;
use HTTP_Request2;
use HttpException;

class Alt_Job extends Job {

  /**
   * WP Post Id
   *
   * @since   0.1.0
   * @var int
   */
  protected $post_id;

  /**
   * MS URI Base
   *
   * @since   0.1.0
   * @var int
   */
  protected $uri_base = "https://westcentralus.api.cognitive.microsoft.com/vision/v2.0";


  /**
   * Image_Processing_Job constructor.
   *
   * @param int   $post_id
   */
  public function __construct( $post_id ) {
    $this->post_id = $post_id;
  }


  public function handle() {
    $image = $this->get_image();
    $request = new Http_Request2($this->uri_base . '/analyze');
    $url = $request->getUrl();

    $request->setHeader( array(
      'Content-Type' => 'application/json',
      'Ocp-Apim-Subscription-Key' => $this->get_key()
    ) );

    $url->setQueryVariables( array(
      'visualFeatures' => 'Description',
      'details' => '',
      'language' => 'en'
    ) );

    $request->setMethod(HTTP_Request2::METHOD_POST);
    $request->setBody(json_encode(array('url' => $image)));
    try {
      $response = $request->send();
      $success = json_decode($response->getBody(), true);
      update_post_meta($this->post_id, 'fu_alt_response', json_encode($success));

      if (200 == $response->getStatus()) {
        update_post_meta($this->post_id, 'fu_alt_status', 'success');
        update_post_meta($this->post_id, '_wp_attachment_image_alt', $success['description']['captions'][0]['text']);
      } else {
        update_post_meta($this->post_id, 'fu_alt_status', 'error');
      }
    }
    catch (HttpException $ex) {
      update_post_meta($this->post_id, 'fu_alt_status', 'error');
      update_post_meta($this->post_id, 'fu_alt_response', json_encode(json_decode($ex, true)));
    }
    finally {
      Queue::unlock_attachment( $this->post_id );
    }
  }


  /**
   * Retrieves the Azure Vision API Key
   *
   * @since   0.1.0
   * @return string
   */
  protected function get_key() {
    return get_option( 'fu_azure_key' );
  }


  /**
   * Get an image string
   *
   * @return string
   */
  protected function get_image() {
    return wp_get_attachment_image_src( $this->post_id, 'full', false )[0];
  }
}