<?php
/*
Plugin Name: LESS Compiler
Plugin URI: http://webmaestro.fr/less-compiler-wordpress/
Author: Etienne Baudry
Author URI: http://webmaestro.fr
Description: Less Compiler for Wordpress
Version: 1.2.2
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
	WM_Less::$sources[] = $source;
}
function less_output( $stylesheet ) {
	// Path to CSS file to compile, relative to get_stylesheet_directory()
	// Default : 'wm-less-' . get_current_blog_id() . '.css'
	// DO NOT SET YOUR THEME'S "style.css" AS OUTPUT ! You silly.
	WM_Less::$output = '/' . ltrim( $stylesheet, '/' );
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
		self::$output = self::$output ? self::$output : '/wm-less-' . get_current_blog_id() . '.css';
		self::apply_settings();
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'less_settings_updated', array( __CLASS__, 'compile' ) );
		add_action( 'less_variables_settings_updated', array( __CLASS__, 'compile' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		// add_editor_style( get_stylesheet_directory_uri() . self::$output );
	}

	private static function apply_settings()
	{
		create_settings_page( 'less', __( 'Compiler', 'wm-less' ), array(
			'parent' => false,
			'title' => __( 'LESS', 'wm-less' ),
			'icon_url' => plugin_dir_url( __FILE__ ) . 'img/menu-icon.png',
		), array(
			'less' => array(
				'description' => '<p>' . sprintf( __( 'The resulting CSS will be compiled into <strong>%s</strong>. You can use the PHP function <code>less_output( $css_file );</code> to output an other file instead (relative to <strong>%s</strong>).', 'wm-less' ), self::$output, get_stylesheet_directory() ) . '</p><p>' . __( 'Import any LESS files to compile prior to this stylesheet with <code>less_import( $files_array );</code>.', 'wm-less' ) . '</p>',
				'fields' => array(
					'compiler' => array(
						'label' => __( 'Stylesheet', 'wm-less' ),
						'type' => 'textarea',
						'description' => sprintf( __( 'From this very stylesheet, <strong>@import</strong> urls are relative to <code>%s</code>.', 'wm-less' ), get_template_directory() )
					)
				)
			)
		), array(
			'submit' => __( 'Compile', 'wm-less' ),
			'reset' => false
		) );
		create_settings_page( 'less_variables', __( 'Variables', 'wm-less' ), array(
			'parent' => 'less'
		), array(
			'less_vars' => array(
				'description' => '<p>' . __( 'Set definition files (often something like "<strong>/less/variables.less</strong>") with <code>register_less_variables( $path_to_file );</code>, and then edit your LESS variables from this very page.', 'wm-less' ) . '</p><p>' . __( 'You can also define (or override) any variable with <code>less_set( $variable, $value );</code>, and access any of these values with <code>less_get( $variable );</code>.', 'wm-less' ) . '</p>',
				'fields' => self::get_variables_fields()
			)
		), array(
			'submit' => __( 'Update Variables', 'wm-less' ),
			'reset' => __( 'Reset Variables', 'wm-less' )
		) );
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
		return $fields;
	}

	public static function cache_permissions_notice() { ?>
    <div id="setting-error-cache_permissions" class="updated settings-error" style="border-left-color: #ffba00;">
			<p><strong><?php echo sprintf( __( 'The cache directory (<code>%s</code>) is not writable. No big deal, but caching would make the compiling step a bit smoother.', 'wm-less' ), plugin_dir_path( __FILE__ ) . 'cache' ); ?></strong></p>
		</div>
	<?php }

	public static function compile()
	{
		require_once( plugin_dir_path( __FILE__ ) . 'libs/less-parser/Less.php' );
		try {
			$cache_dir = plugin_dir_path( __FILE__ ) . 'cache';
			if ( ! is_writable( $cache_dir ) ) {
				add_action( 'admin_notices', array( __CLASS__, 'cache_permissions_notice' ) );
			}
			$parser = new Less_Parser( array(
				'compress' => true,
				'cache_dir' => is_writable( $cache_dir ) ? $cache_dir : null
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
add_action( 'init', array( 'WM_Less', 'init' ) );

?>
