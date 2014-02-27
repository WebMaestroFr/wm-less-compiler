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
*/


function wm_less( $variable, $value = null ) {
	// Return the LESS variable value
	if ( $value ) {
		WM_Less::$variables[$variable] = $value;
	}
	return WM_Less::$variables[$variable];
}


// Utility functions, to call before or during 'admin_init'.

function wm_less_set_variables( $source ) {
	// Absolute path to variables definition file
	// Default : get_template_directory() . '/less/variables.less'
	WM_Less::$source = $source;
}
function wm_less_set_css( $output ) {
	// Path to CSS file to compile, relative to get_template_directory()
	// Default : 'css/wm-less-' . get_current_blog_id() . '.css'
	// DO NOT SET YOUR THEME'S "style.css" AS OUTPUT ! You silly.
	WM_Less::$output = $output;
}
function wm_less_import( $stylesheets ) {
	// Array of file paths to call with the @import LESS function
	// Example : wm_less_import( array( 'less/bootstrap.less', 'less/theme.less' ) );
	WM_Less::$imports = array_merge( WM_Less::$imports, $stylesheets );
}


class WM_Less
{
	public static	$variables = array(),
					$source = false,
					$output = false,
					$imports = array();

	public static function init()
	{
		if ( ! class_exists( WM_Settings ) ) {
			add_action( 'admin_notices', function () {
				echo '<div class="error"><p>' . __( 'The plugin <strong>Less Compiler</strong> requires the plugin <strong><a href="https://github.com/WebMaestroFr/wm-settings">WebMaestro Settings</a></strong> in order to display the options pages.', 'wm-less' ) . '</p></div>';
			});
			return;
		}
		if ( ! self::$source ) { self::$source = get_template_directory() . '/less/variables.less'; }
		if ( ! self::$output ) { self::$output = 'css/wm-less-' . get_current_blog_id() . '.css'; }
		self::apply_settings();
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'wm_less_settings_updated', array( __CLASS__, 'compile' ) );
		add_action( 'wm_less_variables_settings_updated', array( __CLASS__, 'compile' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	private static function apply_settings()
	{
		new WM_Settings( 'wm_less', __( 'Compiler', 'wm-less' ), array(
			'parent'	=> false,
			'title'		=> __( 'LESS', 'wm-less' )
		), array(
			'wm_less' => array(
				'fields' => array(
					'compiler'	=> array(
						'type' => 'textarea',
						'description'	=> sprintf( __( 'Paths of images and <strong>@import</strong> urls are relative to <kbd>%s</kbd>', 'wm-less' ), get_template_directory() )
					)
				)
			)
		), array(
			'submit'	=> __( 'Compile', 'wm-less' ),
			'reset'		=> false
		) );
		if ( self::$source && $fields = self::get_fields() ) {
			new WM_Settings( 'wm_less_variables', __( 'Variables', 'wm-less' ), array(
				'parent'	=> 'wm_less'
			), array(
				'wm_less_vars' => array(
					'description'	=> __( 'Edit your LESS variables from this very dashboard.', 'wm-less' ),
					'fields'		=> $fields
				)
			), array(
				'submit'	=> __( 'Update Variables', 'wm-less' ),
				'reset'		=> __( 'Reset Variables', 'wm-less' )
			) );
		}
	}

	private static function get_fields()
	{
		if ( is_file( self::$source ) && $lines = file( self::$source ) ) {
			$fields = array();
			foreach ( $lines as $line ) {
				if ( preg_match( '/^@([a-zA-Z-]+?)\s?:\s?(.+?);/', $line, $matches ) ) {
					$name = sanitize_key( $matches[1] );
					$label = '@' . $name;
					$default = trim( $matches[2] );
					$fields[$name] = array(
						'label'			=> $label,
						'attributes'	=> array( 'placeholder' => $default )
					);
					self::$variables[$name] = ( $var = wm_get_option( 'wm_less_vars', $name ) ) ? $var : $default;
				}
			}
			return $fields;
		}
		return false;
	}

	public static function compile()
	{
		require_once( plugin_dir_path( __FILE__ ) . 'lib/Less.php' );
		try {
			$parser = new Less_Parser( array(
				'compress'	=> true,
				'cache_dir'	=>	plugin_dir_path( __FILE__ ) . 'cache'
			) );
			$parser->SetImportDirs( array(
				get_stylesheet_directory() => '',
				get_template_directory() => ''
			) );
			foreach ( self::$imports as $stylesheet ) {
				$parser->parse( "@import '{$stylesheet}';" );
			}
			$parser->parse( wm_get_option( 'wm_less', 'compiler' ) );
			$parser->ModifyVars( self::$variables );
			file_put_contents( get_template_directory() . '/' . ltrim( self::$output, '/' ), $parser->getCss() );
			add_settings_error( 'wm_less_compiler', 'less_compiled', __( 'LESS successfully compiled.', 'wm-less' ), 'updated' );
		} catch ( exception $e ) {
			add_settings_error( 'wm_less_compiler', $e->getCode(), sprintf( __( 'Compiler result with the following error :<pre>%s</pre>', 'wm-less' ), $e->getMessage() ) );
		}
	}

	public static function admin_enqueue_scripts( $hook_suffix )
	{
		if ( 'toplevel_page_wm_less' == $hook_suffix ) {
			wp_enqueue_script( 'codemirror', plugin_dir_url( __FILE__ ) . '/js/codemirror.js' );
			wp_enqueue_script( 'codemirror-less', plugin_dir_url( __FILE__ ) . '/js/codemirror-less.js', array( 'codemirror' ) );
			wp_enqueue_script( 'wm-less-compiler', plugin_dir_url( __FILE__ ) . '/js/wm-less-compiler.js', array( 'codemirror-less' ) );
			wp_enqueue_style( 'codemirror', plugin_dir_url( __FILE__ ) . '/css/codemirror.css' );
			wp_enqueue_style( 'wm-less-compiler', plugin_dir_url( __FILE__ ) . '/css/wm-less-compiler.css' );
		}
	}

	public static function enqueue_scripts()
	{
		wp_enqueue_style( 'wm-less', get_template_directory_uri() . '/' . ltrim( self::$output, '/' ) );
	}
}
add_action( 'init', array( WM_Less, 'init' ) );
