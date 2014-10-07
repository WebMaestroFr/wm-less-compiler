<?php

// These functions were used in previous versions of the plugin
// They are still defined to ensure compatibility

function register_less_variables( $files ) {
  if ( is_string( $files ) ) { $files = array( $files ); }
  add_action( 'admin_notices', function () {
    add_settings_error( 'less_compiler', 'depreciated_function', __( 'The function <code>register_less_variables( $files );</code> is depreciated. Variables now have to be registered with the <code>\'less_configuration\'</code> filter.', 'wm-less' ) );
  } );
  add_filter( 'less_configuration', function ( $config ) use ( $files ) {
    $sources = array_merge( $config['variables'], $files );
    return array_merge( $config, array( 'variables' => $sources ) );
  } );
}

function less_import( $files ) {
  if ( is_string( $files ) ) { $files = array( $files ); }
  add_action( 'admin_notices', function () {
    add_settings_error( 'less_compiler', 'depreciated_function', __( 'The function <code>less_import( $files );</code> is depreciated. Imports now have to be configured with the <code>\'less_configuration\'</code> filter.', 'wm-less' ) );
  } );
  add_filter( 'less_configuration', function ( $config ) use ( $files ) {
    if ( is_string( $files ) ) { $files = array( $files ); }
    $imports = array_merge( $config['imports'], $files );
    return array_merge( $config, array( 'imports' => $imports ) );
  } );
}

function less_output() {
  add_settings_error( 'less_compiler', 'depreciated_function', __( 'The function <code>less_output( $stylesheet );</code> is depreciated. Stylesheets will from now be generated within the cache directory.', 'wm-less' ) );
}

if ( $stylesheet = get_settings( 'less', 'compiler' ) ) {
  add_option( 'less_compiler', array(
    'stylesheet' => $stylesheet
  ) );
  delete_option( 'less' );
}

if ( $variables = get_option( 'less_vars' ) ) {
  add_option( 'less_variables', $variables );
  delete_option( 'less_vars' );
}
