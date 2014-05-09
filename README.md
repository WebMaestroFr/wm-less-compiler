A LESS compiler for WordPress ! This is a plugin that will allow you to write and compile LESS, and to edit style variables straight into your WordPress dashboard.

It uses [**leafo**'s LESS compiler written in PHP](https://github.com/leafo/lessphp).

## Installation

1. Download and unzip the last release
2. Move it into your wp-content/plugins directory
3. Activate the plugin in WordPress

## Functions

The plugin comes with few useful functions. You will most likely use these in your theme's `functions.php`.

- Import any LESS files to compile prior to the main stylesheet.
  ```php
  less_import( $files_array );
  ```

- To edit variables from the WordPress dashboard, you will have to define the absolute path to your variables definition file(s).
  ```php
  register_less_variables( $source );
  ```

- Set a LESS variable value
  ```php
  less_set( $variable, $value );
  ```

- Get a LESS variable value
  ```php
  less_get( $variable );
  ```

- Define path to the CSS file to compile (relative to your theme's directory).
  ```php
  less_output( $stylesheet );
  ```
  The default output is : `wm-less-[BLOG-ID].css`.
  > Do not set your theme's `style.css` as output ! You silly.

## Contributors

Contributors are more than welcome !

## License

[WTFPL](http://www.wtfpl.net/) – Do What the Fuck You Want to Public License
