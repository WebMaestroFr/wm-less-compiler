<?php

// These functions were used in previous versions of the plugin
// They are still defined to ensure compatibility

function register_less_variables( $files ) {
  add_action( 'admin_notices', function () {
    add_settings_error( 'less_compiler', 'depreciated_function', __( 'The function <code>register_less_variables( $files );</code> is depreciated. Variables now have to be registered with the <code>\'less_configuration\'</code> filter.', 'wm-less' ) );
  } );
  add_filter( 'less_configuration', function ( $config ) use ( $files ) {
    if ( is_string( $files ) ) { $files = array( $files ); }
    return array_merge_recursive( $config, array( 'variables' => $files ) );
  } );
}

function less_import( $files ) {
  add_action( 'admin_notices', function () {
    add_settings_error( 'less_compiler', 'depreciated_function', __( 'The function <code>less_import( $files );</code> is depreciated. Imports now have to be configured with the <code>\'less_configuration\'</code> filter.', 'wm-less' ) );
  } );
  add_filter( 'less_configuration', function ( $config ) use ( $files ) {
    if ( is_string( $files ) ) { $files = array( $files ); }
    return array_merge_recursive( $config, array( 'imports' => $files ) );
  } );
}

function less_output() {
  add_action( 'admin_notices', function () {
    add_settings_error( 'less_compiler', 'depreciated_function', __( 'The function <code>less_output( $stylesheet );</code> is depreciated. Stylesheets will from now be generated within the cache directory.', 'wm-less' ) );
  } );
}

if ( $stylesheet = get_setting( 'less', 'compiler' ) ) {
  add_option( 'less_compiler', array( 'stylesheet' => $stylesheet ) );
  delete_option( 'less' );
}

if ( $variables = get_option( 'less_vars' ) ) {
  add_option( 'less_variables', $variables );
  delete_option( 'less_vars' );
}
