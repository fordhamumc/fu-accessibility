<?php

namespace FU_Accessibility\admin;

class Settings {

  /**
   * Singleton
   *
   * @since   0.1.0
   * @access   protected
   * @var Queue|null
   */
  protected static $instance = null;

  /**
   * The text domain of this plugin.
   *
   * @since   0.1.0
   * @access   private
   * @var      string    $plugin_text_domain    The text domain of this plugin.
   */
  private $plugin_text_domain;

  /**
   * Singleton
   *
   * @since   0.1.0
   * @return Queue|null
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
   * @param   string    $plugin_text_domain   The text domain of this plugin.
   */
  public function __construct($plugin_text_domain) {
    $this->plugin_text_domain = $plugin_text_domain;
  }

  /**
   * Register the plugin settings page
   * 
   * @since   0.1.0
   */
  public function add_admin_menu() {
    add_menu_page(
      __( 'Accessibility', $this->plugin_text_domain ),
      __( 'Accessibility', $this->plugin_text_domain ),
      'manage_options',
      'fu_accessibility_settings',
      'theme_settings_page');

    add_submenu_page(
      'fu_accessibility_settings',
      __( 'Alt Tag Generator', $this->plugin_text_domain ),
      __( 'Alt Tags', $this->plugin_text_domain ),
      'manage_options',
      'fu_accessibility_settings',
      array($this, 'add_options_wrapper')
    );
  }

  /**
   * Add the settings page wrapper
   *
   * @since   0.1.0
   */
  public function add_options_wrapper($f) { ?>
    <div class="wrap">
      <h2><?php _e( 'Accessibility', $this->plugin_text_domain ); ?></h2>
      <form action="options.php" method="post">
        <?php
        settings_fields( 'fu_accessibility_settings' );
        do_settings_sections( 'fu_accessibility_settings' );
        submit_button();
        ?>
      </form>
    </div>
  <?php
  }

  /**
   * Add sections to the settings page
   *
   * @since   0.1.0
   */
  public function add_sections() {
    add_settings_section(
      'fu_alt_tag_generator',
      __('Alt Tag Generator', $this->plugin_text_domain),
      array( $this, 'section_callback' ),
      'fu_accessibility_settings'
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
      case 'fu_alt_tag_generator':
        esc_html_e('Uses Microsoft Azure Computer Vision API to generate alt tags.', $this->plugin_text_domain);
        break;
    }
  }


  /**
   * Add fields to the settings page
   *
   * @since   0.1.0
   */
  public function add_fields() {
    $fields = array(
      array(
        'uid' => 'fu_azure_key',
        'label' => __('Azure Computer Vision Key', $this->plugin_text_domain),
        'section' => 'fu_alt_tag_generator',
        'type' => 'text'
      ),
      array(
        'uid' => 'fu_azure_limit',
        'label' => __('Transaction Limit', $this->plugin_text_domain),
        'section' => 'fu_alt_tag_generator',
        'type' => 'number',
        'supplemental' => 'Max number of images to process',
        'default' => 5000
      )
    );

    foreach( $fields as $field ) {
      add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'fu_accessibility_settings', $field['section'], $field );
      register_setting( 'fu_accessibility_settings', $field['uid'] );
    }

  }


  /**
   * Builds the field html
   *
   * @since   0.1.0
   * @param   array    $args   The parameters of the add_fields method
   */
  public function field_callback( $args ) {
    $args = array_merge( array(
      'default' => '',
      'placeholder' => '',
      'type' => 'text'
    ), $args);
    $value = get_option( $args['uid'] );
    if( ! $value ) {
      $value = $args['default'];
    }
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

}