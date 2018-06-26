<?php

namespace FU_Accessibility\admin;

use WP_Queue\Job;
use HTTP_Request2;

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
    $image_meta = $this->get_image_meta();
    try {
      $response = $request->send();
      $success = json_decode($response->getBody(), true);
      $image_meta['fu_alt_response'] = json_encode($success);

      if (200 == $response->getStatus()) {
        $image_meta['fu_alt_status'] = 'success';
        update_post_meta($this->post_id, '_wp_attachment_image_alt', $success['description']['captions'][0]['text']);
      } else {
        $image_meta['fu_alt_status'] = 'error';
      }
    }
    catch (HttpException $ex) {
      $image_meta['fu_alt_status'] = 'error';
      $image_meta['fu_alt_response'] = json_encode(json_decode($ex));
    }
    finally {
      wp_update_attachment_metadata( $this->post_id, $image_meta );
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


  /**
   * Get an image's post meta data
   *
   * @return array
   */
  protected function get_image_meta() {
    return wp_get_attachment_metadata( $this->post_id ) ?: array();
  }


  /**
   * Retrieves the Azure Vision API Key
   *
   * @since   0.1.0
   * @param   string   $alt   Computer generated alt tag
   * @return  string
   */
  protected function update_alt($alt) {
    $image_meta = $this->get_image_meta();
    $image_meta['alt_response'] = $alt;
    wp_update_attachment_metadata( $this->post_id, $image_meta );
  }
}