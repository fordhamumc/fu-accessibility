<?php

namespace FU_Accessibility\admin;

use FU_Accessibility as NS;

class Settings {
  /**
   * Singleton
   *
   * @since   0.1.0
   * @access   protected
   * @var Settings|null
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
   * Slug name to refer to the menu
   *
   * @since    0.1.0
   * @access   protected
   * @var      string
   */
  protected $menu_slug;

  /**
   * Name of the form action
   *
   * @since    0.1.0
   * @access   protected
   * @var      string
   */
  protected $action;

  /**
   * The text domain of this plugin.
   *
   * @since    0.1.0
   * @access   protected
   * @var      string
   */
  protected $text_domain;

  /**
   * The settings screen names
   *
   * @since    0.1.0
   * @access   protected
   * @var      Array
   */
  protected $screens;

  /**
   * Singleton
   *
   * @since    0.1.0
   * @return   Settings|null
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
    $this->plugin_name = NS\PLUGIN_NAME;
    $this->text_domain = NS\PLUGIN_TEXT_DOMAIN;
    $this->menu_slug = $this->plugin_name . "_settings";
    $this->action = $this->plugin_name . "_update";
  }


  /**
   * Action: admin_menu
   * Register the plugin settings page
   * 
   * @since   0.1.0
   */
  public function add_admin_menu() {
    $alt_list = Alt_List::instance();
    add_menu_page(
      __( 'Accessibility', $this->text_domain ),
      __( 'Accessibility', $this->text_domain ),
      'manage_options',
      $this->menu_slug,
      'theme_settings_page'
    );

    $this->screens['settings'] = add_submenu_page(
      $this->menu_slug,
      __( 'Accessibility Settings', $this->text_domain ),
      __( 'Settings', $this->text_domain ),
      'manage_options',
      $this->menu_slug,
      array($this, 'load_options_wrapper')
    );

    $this->screens['list'] = add_submenu_page(
      $this->menu_slug,
      __( 'Generated Alt Tags', $this->text_domain ),
      __( 'Alt Tags', $this->text_domain ),
      'edit_posts',
      $this->plugin_name . '_list',
      array($alt_list, 'load_alt_list_wrapper')
    );

    add_action( 'load-'.$this->screens['list'], array( $alt_list, 'load_alt_list_screen_options' ) );
  }


  /**
   * Add the settings page wrapper
   *
   * @since   0.1.0
   */
  public function load_options_wrapper() {
    if (!current_user_can('manage_options'))  {
      wp_die( __('You do not have sufficient privileges to access this page.', $this->text_domain) );
    } ?>
    <div class="wrap">
      <h2><?php _e( 'Accessibility', $this->text_domain ); ?></h2>
      <?php
      if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ){
        $this->admin_notice( (isset($_GET['fu-alt-generator'])) ? $_GET['fu-alt-generator'] : false );
      }
      ?>
      <form action="<?php echo admin_url( 'admin-post.php' ) ?>" method="post">
        <?php wp_nonce_field( $this->action, $this->action . '_nonce', FALSE ); ?>
        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( remove_query_arg( wp_removable_query_args(), wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ?>" />
        <input type="hidden" name="action" value="<?php echo $this->action ?>" />

        <?php do_settings_sections( $this->menu_slug ); ?>
        <p class="submit">
          <?php submit_button('Save', 'secondary', 'submit', false); ?> &nbsp;
          <?php submit_button('Save and Run', 'primary', 'submit_run', false); ?>
        </p>
      </form>
    </div>
  <?php
  }


  /**
   * Display a settings updated page
   *
   * @since   0.1.0
   * @param   string|boolean     $queued   Number of queued images or false if not queued
   */
  public function admin_notice($queued) {
    $class = 'notice-success';
    if ($queued === '0') {
      $message = __("There were no images to add to the queue.", $this->text_domain);
      $class = 'notice-error';
    } else if ($queued) {
      $image_txt = ($queued === '1') ? "image" : "images";
      $message = __('Successfully added', $this->text_domain) . " {$queued}  {$image_txt} " . __('to the queue!', $this->text_domain) .
        " " . __('Images will be processed in the background. You can navigate away from this page.', $this->text_domain);
    } else {
      $message = __('Your settings have been updated!', $this->text_domain);
    }
    echo "<div class='notice {$class} is-dismissible'><p>{$message}</p></div>";
  }


  /**
   * Action: admin_init
   * Add sections to the settings page
   *
   * @since   0.1.0
   */
  public function add_sections() {
    add_settings_section(
      'fu_alt_generator',
      __('Alt Tag Generator', $this->text_domain),
      array( $this, 'section_callback' ),
      $this->menu_slug
    );
  }


  /**
   * Add descriptive text to the section
   *
   * @since   0.1.0
   * @param   array    $args   The parameters of add_settings_section
   */
  public function section_callback( $args ) {
    switch( $args['id'] ){
      case 'fu_alt_generator':
        esc_html_e('Uses Microsoft Azure Computer Vision API to generate alt tags.', $this->text_domain);
        break;
    }
  }


  /**
   * Action: admin_init
   * Add fields to the settings page
   *
   * @since   0.1.0
   */
  public function add_fields() {
    $fields = array(
      array(
        'uid' => 'fu_azure_key',
        'label' => __('Azure Computer Vision Key', $this->text_domain),
        'section' => 'fu_alt_generator',
        'type' => 'text'
      ),
      array(
        'uid' => 'fu_azure_limit',
        'label' => __('Transaction Limit', $this->text_domain),
        'section' => 'fu_alt_generator',
        'type' => 'number',
        'supplemental' => 'Max number of images to process',
        'default' => 5000
      )
    );

    foreach( $fields as $field ) {
      add_settings_field( $field['uid'], $field['label'], array( $this, 'load_field' ), $this->menu_slug, $field['section'], $field );
      register_setting( $this->menu_slug, $field['uid'] );
    }

  }


  /**
   * Builds the field html
   *
   * @since   0.1.0
   * @param   array    $args   The parameters of the add_fields method
   */
  public function load_field( $args ) {
    $args = array_merge( array(
      'default' => '',
      'placeholder' => '',
      'type' => 'text'
    ), $args);
    $value = get_option( $args['uid'], $args['default']);

    switch( $args['type'] ){
      case 'text':
      case 'password':
      case 'number':
        printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $args['uid'], $args['type'], $args['placeholder'], $value );
        break;
      case 'textarea':
        printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $args['uid'], $args['placeholder'], $value );
        break;
      case 'select':
      case 'multiselect':
        if( ! empty ( $args['options'] ) && is_array( $args['options'] ) ){
          $attributes = '';
          $options_markup = '';
          foreach( $args['options'] as $key => $label ){
            $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value[ array_search( $key, $value, true ) ], $key, false ), $label );
          }
          if( $args['type'] === 'multiselect' ){
            $attributes = ' multiple="multiple" ';
          }
          printf( '<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $args['uid'], $attributes, $options_markup );
        }
        break;
      case 'radio':
      case 'checkbox':
        if( ! empty ( $args['options'] ) && is_array( $args['options'] ) ){
          $options_markup = '';
          $iterator = 0;
          foreach( $args['options'] as $key => $label ){
            $iterator++;
            $options_markup .= sprintf( '<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', $args['uid'], $args['type'], $key, checked( $value[ array_search( $key, $value, true ) ], $key, false ), $label, $iterator );
          }
          printf( '<fieldset>%s</fieldset>', $options_markup );
        }
        break;
    }
    if( isset( $args['helper'] ) ){
      printf( '<span class="helper"> %s</span>', $args['helper'] );
    }
    if( isset( $args['supplemental'] ) ){
      printf( '<p class="description">%s</p>', $args['supplemental'] );
    }
  }


  /**
   * Action: admin_post_fu_accessibility_update
   * Handle form response
   *
   * @since   0.1.0
   */
  public function form_response() {
    $redirect = $_POST['_wp_http_referer'] ?: 'admin.php?page=' . $this->menu_slug;

    if( isset( $_POST[$this->action . '_nonce'] ) && wp_verify_nonce( $_POST[$this->action . '_nonce'], $this->action) ) {
      $goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
      $key = sanitize_key( $_POST['fu_azure_key'] );
      $limit = max( intval( $_POST['fu_azure_limit'] ), 0);

      update_option( 'fu_azure_key', $key );
      update_option( 'fu_azure_limit', $limit );

      if ( isset( $_POST['submit_run'] ) ) {
        $count = Queue::instance()->queue_images();
        $goback = add_query_arg( 'fu-alt-generator', $count, $goback );
      }
      wp_redirect( $goback );
      exit;
    }
    else {
      wp_die(
        __( 'Invalid nonce specified', $this->text_domain ),
        __( 'Error', $this->text_domain ),
        array(
          'response' 	=> 403,
          'back_link' => $redirect
        )
      );
    }
  }


  /**
   * Get the screen
   *
   * @since   0.1.0
   * @return  array
   */
  public function get_screens() {
    return $this->screens;
  }


  /**
   * Action: removable_query_args
   * Update the list of single-use query variable names that can be removed from a URL.
   *
   * @since   0.1.0
   */
  public function update_removable_query_args($args) {
    $args[] = 'fu-alt-generator';
    return $args;
  }
}