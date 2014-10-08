<?php
/*
Plugin Name: LESS Compiler
Plugin URI: http://webmaestro.fr/less-compiler-wordpress/
Author: Etienne Baudry
Author URI: http://webmaestro.fr
Description: Less Compiler for Wordpress.
Version: 1.6
License: GNU General Public License
License URI: license.txt
Text Domain: wm-less
GitHub Plugin URI: https://github.com/WebMaestroFr/wm-less-compiler
GitHub Branch: master
*/


require_once( plugin_dir_path( __FILE__ ) . 'libs/wm-settings/wm-settings.php' );
require_once( plugin_dir_path( __FILE__ ) . 'compatibility.php' );

function less_set( $variable, $value = null ) {
	// Set a LESS variable value
	$variable = sanitize_key( $variable );
	WM_Less::$variables[$variable] = $value;
}

function less_get( $variable ) {
	// Return a LESS variable value
	$variable = sanitize_key( $variable );
	if ( isset( WM_Less::$variables[$variable] ) ) {
		return WM_Less::$variables[$variable];
	}
	return null;
}

class WM_Less
{
	public static	$variables = array();

	private static $cache,
		$imports = array(),
		$output = false;

	public static function init()
	{
		$defaults = array(
			'variables' => array(),
			'imports'   => array(),
			'cache'     => ABSPATH . 'wp-content/cache',
			'search'    => true
		);
		$config = apply_filters( 'less_configuration', $defaults );
		self::$imports = self::valid_files( $config, 'imports' );
		self::$cache = empty( $config['cache'] ) ? $defaults['cache'] : $config['cache'];
		if ( is_admin() ) {
			$page = create_settings_page(
				'less_compiler',
				__( 'Less Compiler', 'wm-less' ),
				array(
					'parent' => false,
					'title' => __( 'LESS', 'wm-less' ),
					'icon_url' => plugin_dir_url( __FILE__ ) . 'img/menu-icon.png'
				),
				array(
					'less_compiler' => array(
						'title'       => __( 'Stylesheet', 'wm-less' ),
						'fields' => array(
							'stylesheet' => array(
								'label'       => false,
								'type'        => 'textarea',
								'description' => sprintf( __( 'From this very stylesheet, <strong>@import</strong> urls are relative to <code>%s</code>.', 'wm-less' ), get_template_directory() ),
								'attributes'  => array(
									'placeholder' => esc_attr( '/* LESS stylesheet */', 'wm-less' )
								)
							)
						)
					)
				),
				array(
					'description' => '<a href="http://lesscss.org/" target="_blank">' . __( 'Getting started with LESS', 'wm-less' ) . '</a> | <a href="http://webmaestro.fr/less-compiler-wordpress/" target="_blank">' . __( 'Configure with PHP', 'wm-less' ) . '</a>',
					'tabs'        => true,
					'submit'      => __( 'Compile', 'wm-less' ),
					'reset'       => false,
					'updated'     => false
				)
			);
			$sources = self::valid_files( $config, 'variables' );
			if ( empty( self::$sources ) ) {
				$page->add_notice( __( 'In order to edit your LESS variables from this page, you must <a href="http://webmaestro.fr/less-compiler-wordpress/" target="_blank">register your definition file(s)</a>.', 'wm-less' ) );
			} else {
				$section = array(
					'title'       => __( 'Variables', 'wm-less' ),
					'description' => empty( $config['search'] ) ? false : '<input type="search" id="variable-search" placeholder="' . __( 'Search Variable', 'wm-less' ) . '">',
					'fields'      => array()
				);
				foreach ( $sources as $source ) {
					$fields = array();
					if ( $lines = file( $source ) ) {
						foreach ( $lines as $line ) {
							if ( preg_match( '/^@([a-zA-Z-_]+?)\s?:\s?(.+?);/', $line, $matches ) ) {
								$name = sanitize_key( $matches[1] );
								$default = trim( $matches[2] );
								self::$variables[$name] = $default;
								$fields[$name] = array(
									'label' => '@' . $name,
									'attributes' => array( 'placeholder' => esc_attr( $default ) )
								);
							}
						}
					}
					if ( empty( $fields ) ) {
						$page->add_notice( sprintf( __( 'No variables were found in the registered definition file <code>%s</code>.', 'wm-less' ), $source ), 'warning' );
					} else {
						$section['fields'] = array_merge( $section['fields'], $fields );
					}
				};
				if ( ! empty( self::$variables ) ) {
					$page->apply_settings( array(
						'less_variables' => $section
					) );
				}
			}
			update_option( 'less_variables_defaults', self::$variables );
			if ( ! is_dir( self::$cache ) && ! mkdir( self::$cache, 0755 ) ) {
				$page->add_notice( sprintf( __( 'The cache directory <code>%s</code> does not exist and cannot be created. Please create it with <code>0755</code> permissions.', 'wm-less' ), self::$cache ), 'error' );
			} else if ( ! is_writable( self::$cache ) && ! chmod( self::$cache, 0755 ) ) {
				$page->add_notice( sprintf( __( 'The cache directory <code>%s</code> is not writable. Please apply <code>0755</code> permissions to it.', 'wm-less' ), self::$cache ), 'error' );
			}
		} else {
			self::$variables = get_option( 'less_variables_defaults', array() );
		}
		self::$variables = array_merge( self::$variables, array_filter( get_setting( 'less_variables' ) ) );
		if ( is_writable( self::$cache ) ) {
			self::$output = self::$cache . '/wm-less-' . get_current_blog_id() . '.css';
			add_action( 'less_compiler_settings_updated', array( __CLASS__, 'compile' ) );
			add_filter( 'style_loader_src', array( __CLASS__, 'style_loader_src' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		}
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
	}
	private static function valid_files( $array, $key )
	{
		$valid = array();
		if ( ! empty( $array[$key] ) ) {
			$files = $array[$key];
			if ( ! is_array( $files ) ) {
				$files = array( (string) $files );
			}
			foreach ( $files as $file ) {
				if ( $path = self::valid_file( $file ) ) {
					$valid[] = $path;
				}
			}
		}
		return $valid;
	}
	private static function valid_file( $path )
	{
		if ( empty( $path ) ) { return false; }
		if ( strpos( $path, site_url() ) === 0 ) {
			$path = str_replace( trailingslashit( site_url() ), ABSPATH, $path );
		} else if ( strpos( $path, ABSPATH ) !== 0 ) {
			$path = trailingslashit( get_template_directory() ) . ltrim( $path, '/' );
		}
		if ( ! is_file( $path ) ) {
			add_action( 'admin_notices', function () use ( $path ) {
				add_settings_error( 'less_compiler', 'file_not_found', sprintf( __( 'The file <code>%s</code> cannot be found.', 'wm-less' ), $path ) );
			} );
			return false;
		}
		return $path;
	}

	public static function compile()
	{
		require_once( plugin_dir_path( __FILE__ ) . 'libs/less.php/Less.php' );
		$parser = new Less_Parser( array(
			'compress'  => true,
			'cache_dir' => self::$cache
		) );
		$parser->SetImportDirs( array(
			get_stylesheet_directory() => '',
			get_template_directory()   => '',
			ABSPATH                    => '',
			''                         => ''
		) );
		try {
			foreach ( self::$imports as $file ) {
				$parser->parse( "@import '{$file}';" );
			}
			$parser->parse( get_setting( 'less_compiler', 'stylesheet' ) );
			$parser->ModifyVars( self::$variables );
			$css = $parser->getCss();
			file_put_contents( self::$output, $css );
			add_settings_error( 'less_compiler', 'less_compiled', __( 'LESS successfully compiled.', 'wm-less' ), 'updated' );
		} catch ( exception $e ) {
			add_settings_error( 'less_compiler', $e->getCode(), sprintf( __( 'Compiler result with the following error : <pre>%s</pre>', 'wm-less' ), $e->getMessage() ) );
		}
	}

	public static function admin_enqueue_scripts( $hook_suffix )
	{
		if ( 'toplevel_page_less_compiler' === $hook_suffix ) {
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
		if ( ! empty( self::$imports ) || get_setting( 'less_compiler', 'stylesheet' ) ) {
			if ( ! is_file( self::$output ) ) {
				self::compile();
			}
			wp_enqueue_style( 'wm-less', str_replace( ABSPATH, trailingslashit( site_url() ), self::$output ) );
		}
	}

	public static function style_loader_src( $src )
	{
		$input = strtok( $src, '?' );
    if ( preg_match( '/\.less$/', $input ) ) {
			if ( $file = self::valid_file( $input ) ) {
				require_once( plugin_dir_path( __FILE__ ) . 'libs/less.php/Less.php' );
				$css = Less_Cache::Get( array(
						$file => dirname( $file )
					), array(
						'compress'  => true,
						'cache_dir' => self::$cache
					), self::$variables );
				$path = str_replace( ABSPATH, trailingslashit( site_url() ), self::$cache );
				return trailingslashit( $path ) . $css;
			}
			return null;
    }
    return $src;
	}
}
add_action( 'init', array( 'WM_Less', 'init' ) );

?>
