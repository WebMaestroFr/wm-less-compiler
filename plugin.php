<?php
/*
Plugin Name: LESS Compiler
Plugin URI: http://webmaestro.fr/less-compiler-wordpress/
Author: Etienne Baudry
Author URI: http://webmaestro.fr
Description: Less Compiler for Wordpress.
Version: 1.4
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

function register_less_variables( $files ) {
	// Absolute path to variables definition file(s)
	// Example : register_less_variables( array( 'less/variables.less' ) );
	if ( is_string( $files ) ) { $files = array( $files ); }
	foreach ( $files as $file ) {
		WM_Less::$sources[] = '/' . ltrim( $file, '/' );
	}
}
function less_import( $files ) {
	// File(s) to call with the @import directive
	// Example : less_import( array( 'less/bootstrap.less', 'less/theme.less' ) );
	if ( is_string( $files ) ) { $files = array( $files ); }
	foreach ( $files as $file ) {
		WM_Less::$imports[] = $file;
	}
}
function less_output() {
	// This function does not apply anymore
	add_settings_error( 'less_compiler', 'depreciated_function', __( 'The function <code>less_output( $stylesheet );</code> is depreciated. Stylesheets will from now be generated within the cache directory.', 'wm-less' ) );
}


require_once( plugin_dir_path( __FILE__ ) . 'libs/wm-settings/wm-settings.php' );


class WM_Less
{
	public static	$variables = array(),
		$sources = array(),
		$imports = array();

	private static $cache,
		$output;

	public static function init()
	{
		self::$cache = ABSPATH . 'wp-content/cache';
		if ( ! is_dir( self::$cache ) && ! mkdir( self::$cache, 0755 ) ) {
			add_settings_error( 'less_compiler', 'no_cache_dir', sprintf( __( 'The cache directory (<code>%s</code>) does not exist and cannot be created. Please create it with <code>0755</code> permissions.', 'wm-less' ), self::$cache ), 'error' );
		} else if ( ! is_writable( self::$cache ) && ! chmod( self::$cache, 0755 ) ) {
			add_settings_error( 'less_compiler', 'cache_not_writable', sprintf( __( 'The cache directory (<code>%s</code>) is not writable. Please apply <code>0755</code> permissions to it.', 'wm-less' ), self::$cache ), 'error' );
		} else {
			self::$output = self::$cache . '/wm-less-' . get_current_blog_id() . '.css';
			add_action( 'less_compiler_settings_updated', array( __CLASS__, 'compile' ) );
			add_action( 'less_variables_settings_updated', array( __CLASS__, 'compile' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
			add_filter( 'style_loader_src', array( __CLASS__, 'style_loader_src' ) );
		}
		self::apply_settings();
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
	}

	private static function apply_settings()
	{
		if ( is_admin() ) {
			create_settings_page( 'less_compiler', __( 'Compiler', 'wm-less' ), array(
				'parent' => false,
				'title' => __( 'LESS', 'wm-less' ),
				'icon_url' => plugin_dir_url( __FILE__ ) . 'img/menu-icon.png',
			), array(
				'less' => array(
					'description' => '<a href="http://lesscss.org/" target="_blank">' . __( 'Getting started with LESS', 'wm-less' ) . '</a> | <a href="http://webmaestro.fr/less-compiler-wordpress/" target="_blank">' . __( 'Configure with PHP', 'wm-less' ) . '</a>',
					'fields' => array(
						'compiler'      => array(
							'label'       => __( 'Stylesheet', 'wm-less' ),
							'type'        => 'textarea',
							'description' => sprintf( __( 'From this very stylesheet, <strong>@import</strong> urls are relative to <code>%s</code>.', 'wm-less' ), get_template_directory() ),
							'attributes'  => array(
								'placeholder' => esc_attr( '/* LESS stylesheet */' )
							)
						)
					)
				)
			), array(
				'submit'  => __( 'Compile', 'wm-less' ),
				'reset'   => false,
				'updated' => false
			) );
			$vars_page = create_settings_page( 'less_variables', __( 'Variables', 'wm-less' ), array(
				'parent' => 'less_compiler'
			), null, array(
				'submit'  => __( 'Update Variables', 'wm-less' ),
				'reset'   => __( 'Reset Variables', 'wm-less' ),
				'updated' => __( 'Variables updated.')
			) );
			if ( empty( self::$sources ) ) {
				$vars_page->add_notice( __( 'In order to edit your LESS variables from this page, you must <a href="http://webmaestro.fr/less-compiler-wordpress/" target="_blank">register your definition file(s)</a> with <code>register_less_variables( $files );</code>.' ) );
			} else {
				$fields = array();
				foreach ( self::$sources as $source ) {
					$source = get_template_directory() . $source;
					if ( is_file( $source ) && $lines = file( $source ) ) {
						foreach ( $lines as $line ) {
							if ( preg_match( '/^@([a-zA-Z-_]+?)\s?:\s?(.+?);/', $line, $matches ) ) {
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
				if ( empty( $fields ) ) {
					$vars_page->add_notice( __( 'No variables were found in the registered definition files.' ), 'warning' );
				} else {
					$vars_page->apply_settings( array( 'less_vars' => array( 'fields' => $fields ) ) );
				}
			}
		} else {
			self::$variables = get_setting( 'less_vars' );
		}
	}

	public static function compile()
	{
		require_once( plugin_dir_path( __FILE__ ) . 'libs/less.php/Less.php' );
		$parser = new Less_Parser( array(
			'compress' => true,
			'cache_dir' => self::$cache
		) );
		$parser->SetImportDirs( array(
			get_stylesheet_directory() => '',
			get_template_directory() => ''
		) );
		try {
			foreach ( self::$imports as $file ) {
				$parser->parse( "@import '{$file}';" );
			}
			$parser->parse( get_setting( 'less', 'compiler' ) );
			$parser->ModifyVars( self::$variables );
			$css = $parser->getCss();
			file_put_contents( self::$output, $css );
			add_settings_error( 'less_compiler', 'less_compiled', __( 'LESS successfully compiled.', 'wm-less' ), 'updated' );
		} catch ( exception $e ) {
			add_settings_error( 'less_compiler', $e->getCode(), sprintf( __( 'Compiler result with the following error :<pre>%s</pre>', 'wm-less' ), $e->getMessage() ) );
		}
	}

	public static function admin_enqueue_scripts( $hook_suffix )
	{
		if ( 'toplevel_page_less_compiler' == $hook_suffix ) {
			wp_enqueue_script( 'codemirror', plugin_dir_url( __FILE__ ) . 'js/codemirror.js' );
			wp_enqueue_script( 'codemirror-css', plugin_dir_url( __FILE__ ) . 'js/codemirror.css.js', array( 'codemirror' ) );
			wp_enqueue_script( 'codemirror-placeholder', plugin_dir_url( __FILE__ ) . 'js/codemirror.placeholder.js', array( 'codemirror' ) );
			wp_enqueue_script( 'less-compiler', plugin_dir_url( __FILE__ ) . 'js/less-compiler.js', array( 'codemirror' ) );
			wp_enqueue_style( 'codemirror', plugin_dir_url( __FILE__ ) . 'css/codemirror.css' );
			wp_enqueue_style( 'less-compiler', plugin_dir_url( __FILE__ ) . 'css/less-compiler.css' );
		}
	}

	public static function enqueue_scripts()
	{
		if ( ! is_file( self::$output ) ) { self::compile(); }
		wp_enqueue_style( 'wm-less', str_replace( ABSPATH, trailingslashit( site_url() ), self::$output ) );
	}

	public static function style_loader_src( $src )
	{
		$input = strtok( $src, '?' );
    if ( preg_match( '/\.less$/', $input ) ) {
			$input = str_replace( trailingslashit( site_url() ), ABSPATH, $input );
			if ( is_file( $input ) ) {
				require_once( plugin_dir_path( __FILE__ ) . 'libs/less.php/Less.php' );
				$file = Less_Cache::Get( array( $input => dirname( $input ) ), array( 'cache_dir' => self::$cache ) );
				$path = str_replace( ABSPATH, trailingslashit( site_url() ), self::$cache );
				return trailingslashit( $path ) . $file;
			}
			return null;
    }
    return $src;
	}
}
add_action( 'init', array( 'WM_Less', 'init' ) );

?>
