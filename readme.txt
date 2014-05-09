=== Plugin Name ===
Contributors: WebMaestro.Fr
Donate link: http://webmaestro.fr/less-compiler-wordpress/
Tags: LESS, compiler
Requires at least: 3.9
Tested up to: 3.9.1
Stable tag: 1.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

LESS compiler for WordPress. Allows you to write and compile LESS, and to edit style variables straight into your WordPress dashboard.

== Description ==

The 'Compiler' page can be used to write LESS. The 'Variables' page, once set, allows you to edit your LESS variables.

It uses [**leafo**'s LESS compiler written in PHP](https://github.com/leafo/lessphp).

The plugin comes with few useful functions. You will most likely use these in your theme's `functions.php`.

- ```less_output( $stylesheet );```

  Define path to the CSS file to compile (relative to your theme's directory).
  The default output is : `wm-less-[BLOG-ID].css`.
  > Do not set your theme's `style.css` as output ! You silly.

- ```less_import( $files_array );```

  Import any LESS files to compile prior to the main stylesheet.

- ```register_less_variables( $source );```

  To edit variables from the WordPress dashboard, you will have to define the absolute path to your variables definition file(s).

- ```less_set( $variable, $value );```

  Set a LESS variable value

- ```less_get( $variable );```

  Get a LESS variable value

== Installation ==

1. Upload `wm-less-compiler` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Find the plugin's pages under the new 'LESS' menu

== Frequently Asked Questions ==

= No question asked =

No answer to give.

== Screenshots ==

1. The 'Compiler' page
2. The 'Variables' page

== Changelog ==

= 1.0 =
* First stable release

== Upgrade Notice ==

= 1.0 =
First stable release
