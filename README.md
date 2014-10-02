A LESS compiler for WordPress ! This is a plugin that will allow you to write and compile LESS, and to edit style variables straight into your WordPress dashboard.

It uses [the Less.php Compiler](http://lessphp.gpeasy.com/).

## Installation

1. Download the last release
2. Unzip it into your wp-content/plugins directory
3. Activate the plugin in WordPress

## PHP Functions

The plugin comes with few useful functions. You will most likely use these in your theme's `functions.php`.

- Import any LESS files to compile prior to the main stylesheet.
  ```php
  less_import( $files_array );
  ```

- Alternatively you can register and enqueue your LESS sheets the same way you would do for your CSS.
  ```php
  wp_enqueue_style( 'my-less-handle', 'http://example.com/css/mystyle.less', $deps, $ver, $media );
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

## Contributors

Contributors are more than welcome !

## Documentation

[Read the documentation](http://webmaestro.fr/less-compiler-wordpress/)

## License

[WTFPL](http://www.wtfpl.net/) – Do What the Fuck You Want to Public License
