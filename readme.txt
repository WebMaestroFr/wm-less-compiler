=== Plugin Name ===
Contributors: WebMaestro.Fr
Donate link: http://webmaestro.fr/less-compiler-wordpress/
Tags: LESS, compiler
Requires at least: 3.9
Tested up to: 3.9.1
Stable tag: 1.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

LESS compiler for WordPress. Allows you to write and compile LESS, and to edit style variables straight into your WordPress dashboard.

== Description ==

Write LESS, edit your variables and compile your stylesheet from your dashboard.

The plugin also comes with a few useful PHP functions :

- ```less_set( $variable, $value );```

  Set a LESS variable value.

- ```less_get( $variable );```

  Get a LESS variable value.

- ```register_less_variables( $source );```

  Define the absolute path to your variables definition file(s).

- ```less_output( $stylesheet );```

  Define the path of the CSS file to compile (relative to your theme's directory).
  The default output is : `wm-less-[BLOG-ID].css`.
  > Do not set your theme's `style.css` as output ! You silly.

- ```less_import( $files_array );```

  Import any LESS files to compile prior to the dashboard stylesheet.

You will most likely use these in your theme's `functions.php`.

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

= 1.2.2 =
* Menu icon and cache warning

= 1.2 =
* Minor fixes (typo, dependences)

== Upgrade Notice ==

= 1.2 =
* Minor fixes (typo, dependences)
