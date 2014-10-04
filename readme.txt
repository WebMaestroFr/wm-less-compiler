=== Plugin Name ===
Contributors: WebMaestro.Fr
Donate link: http://webmaestro.fr/less-compiler-wordpress/
Tags: LESS, compiler
Requires at least: 3.9
Tested up to: 4.0
Stable tag: 1.5
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

LESS compiler for WordPress. Allows you to write and compile LESS, and to edit style variables straight into your WordPress dashboard.

== Description ==

Write LESS, edit your variables and compile your stylesheet from your dashboard.

[Read the documentation](http://webmaestro.fr/less-compiler-wordpress/)

  - Register and enqueue your LESS sheets the same way you would do for your CSS.
    ```
    wp_enqueue_style( 'my-less-handle', 'http://example.com/css/mystyle.less', $deps, $ver, $media );
    ```

  - Configure the plugin with the `less_configuration` filter.
    ```
    add_filter( 'less_configuration', 'my_less_config' );
    function my_less_config( $defaults ) {
      $variables = array( 'less/variables.less' );
      $imports = array(
        'less/bootstrap.less',
        'less/theme.less'
      );
      return array(
        'variables' => $variables,
        'imports'   => $imports
      );
    }
    ```
    Configuration of the plugin is optional, but you should at least register your variables if you are using a CSS framework.

  - Set a LESS variable value
    ```
    less_set( $variable, $value );
    ```

  - Get a LESS variable value
    ```
    less_get( $variable );
    ```

You will most likely use these functions in your theme's `functions.php`.

The plugin uses [the Less.php Compiler](http://lessphp.gpeasy.com/).

== Installation ==

1. Upload `less-compiler` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Find the plugin's pages under the new 'LESS' menu

== Frequently Asked Questions ==

= No question asked =

No answer to give.

== Screenshots ==

1. The 'Compiler' page
2. The 'Variables' page

== Changelog ==

= 1.5 =
* Uses filter for config
* Better use of cache
* Updated dependencies

= 1.3 =
* "wp_enqueue_style" support
* Moved cache directory to wp-content/cache

= 1.2.2 =
* Menu icon and cache warning

= 1.2 =
* Minor fixes (typo, dependences)

== Upgrade Notice ==

= 1.5 =
* Uses filter for config
* Better use of cache
* Updated dependencies

= 1.3 =
* "wp_enqueue_style" support
* Moved cache directory to wp-content/cache

= 1.2 =
* Minor fixes (typo, dependences)
