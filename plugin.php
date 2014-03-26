<?php
/*
Plugin Name: WebMaestro Less Compiler
Plugin URI: http://#
Author: Etienne Baudry
Author URI: http://webmaestro.fr
Description: Less Compiler for Wordpress
Version: 1.0
License: GNU General Public License
License URI: license.txt
Text Domain: wm-less
GitHub Plugin URI: https://github.com/WebMaestroFr/wm-less-compiler
GitHub Branch: master
*/


function less_set( $variable, $value = null ) {
	// Set a LESS variable value
	WM_Less::$variables[$variable] = $value;
}
function less_get( $variable ) {
	// Return a LESS variable value
	return WM_Less::$variables[$variable];
}


// Utility functions, to call before or during the 'init' hook.

function register_less_variables( $source ) {
	// Absolute path to variables definition file
	// Default : get_template_directory() . '/less/variables.less'
	WM_Less::$sources[] = $source;
}
function less_output( $stylesheet ) {
	// Path to CSS file to compile, relative to get_stylesheet_directory()
	// Default : 'wm-less-' . get_current_blog_id() . '.css'
	// DO NOT SET YOUR THEME'S "style.css" AS OUTPUT ! You silly.
	WM_Less::$output = $stylesheet;
}
function less_import( $files ) {
	// Array of file paths to call with the @import LESS function
	// Example : less_import( array( 'less/bootstrap.less', 'less/theme.less' ) );
	WM_Less::$imports = array_merge( WM_Less::$imports, $files );
}


class WM_Less
{
	public static	$variables = array(),
					$sources = array(),
					$output = false,
					$imports = array();

	public static function init()
	{
		require_once( plugin_dir_path( __FILE__ ) . 'libs/wm-settings/wm-settings.php' );
		self::$output = self::$output ? '/' . ltrim( self::$output, '/' ) : '/wm-less-' . get_current_blog_id() . '.css';
		self::apply_settings();
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'less_settings_updated', array( __CLASS__, 'compile' ) );
		add_action( 'less_variables_settings_updated', array( __CLASS__, 'compile' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_editor_style( get_stylesheet_directory_uri() . self::$output );
	}

	private static function apply_settings()
	{
		create_settings_page( 'less', __( 'Compiler', 'wm-less' ), array(
			'parent' => false,
			'title' => __( 'LESS', 'wm-less' ),
			'icon_url' => 'dashicons-art',
		), array(
			'less' => array(
				'fields' => array(
					'compiler' => array(
						'type' => 'textarea',
						'description' => sprintf( __( 'Paths of images and <strong>@import</strong> urls are relative to <kbd>%s</kbd>', 'wm-less' ), get_template_directory() )
					)
				)
			)
		), array(
			'submit' => __( 'Compile', 'wm-less' ),
			'reset' => false
		) );
		if ( $fields = self::get_variables_fields() ) {
			create_settings_page( 'less_variables', __( 'Variables', 'wm-less' ), array(
				'parent' => 'less'
			), array(
				'less_vars' => array(
					'description' => __( 'Edit your LESS variables from this very dashboard.', 'wm-less' ),
					'fields' => $fields
				)
			), array(
				'submit' => __( 'Update Variables', 'wm-less' ),
				'reset' => __( 'Reset Variables', 'wm-less' )
			) );
		}
	}

	private static function get_variables_fields()
	{
		$fields = array();
		foreach ( self::$sources as $source ) {
			if ( is_file( $source ) && $lines = file( $source ) ) {
				foreach ( $lines as $line ) {
					if ( preg_match( '/^@([a-zA-Z-]+?)\s?:\s?(.+?);/', $line, $matches ) ) {
						$name = sanitize_key( $matches[1] );
						$label = '@' . $name;
						$default = trim( $matches[2] );
						$fields[$name] = array(
							'label' => $label,
							'attributes' => array( 'placeholder' => $default )
						);
						self::$variables[$name] = ( $var = get_setting( 'less_vars', $name ) ) ? $var : $default;
					}
				}
			}
		}
		return empty( $fields ) ? false : $fields;
	}

	public static function compile()
	{
		require_once( plugin_dir_path( __FILE__ ) . 'libs/less-parser/Less.php' );
		try {
			$parser = new Less_Parser( array(
				'compress' => true,
				'cache_dir' => plugin_dir_path( __FILE__ ) . 'cache'
			) );
			$parser->SetImportDirs( array(
				get_stylesheet_directory() => '',
				get_template_directory() => ''
			) );
			foreach ( self::$imports as $file ) {
				$parser->parse( "@import '{$file}';" );
			}
			$parser->parse( get_setting( 'less', 'compiler' ) );
			$parser->ModifyVars( self::$variables );
			file_put_contents( get_stylesheet_directory() . self::$output, $parser->getCss() );
			add_settings_error( 'less_compiler', 'less_compiled', __( 'LESS successfully compiled.', 'wm-less' ), 'updated' );
		} catch ( exception $e ) {
			add_settings_error( 'less_compiler', $e->getCode(), sprintf( __( 'Compiler result with the following error :<pre>%s</pre>', 'wm-less' ), $e->getMessage() ) );
		}
	}

	public static function admin_enqueue_scripts( $hook_suffix )
	{
		if ( 'toplevel_page_less' == $hook_suffix ) {
			wp_enqueue_script( 'codemirror', plugin_dir_url( __FILE__ ) . '/js/codemirror.js' );
			wp_enqueue_script( 'codemirror-less', plugin_dir_url( __FILE__ ) . '/js/codemirror-less.js', array( 'codemirror' ) );
			wp_enqueue_script( 'less-compiler', plugin_dir_url( __FILE__ ) . '/js/less-compiler.js', array( 'codemirror-less' ) );
			wp_enqueue_style( 'codemirror', plugin_dir_url( __FILE__ ) . '/css/codemirror.css' );
			wp_enqueue_style( 'less-compiler', plugin_dir_url( __FILE__ ) . '/css/less-compiler.css' );
		}
	}

	public static function enqueue_scripts()
	{
		if ( is_file( get_stylesheet_directory() . self::$output ) ) {
			wp_enqueue_style( 'wm-less', get_stylesheet_directory_uri() . self::$output );
		}
	}
}
add_action( 'init', array( WM_Less, 'init' ) );
register_activation_hook( __FILE__, array( WM_Less, 'compile' ) );
