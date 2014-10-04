A LESS compiler for WordPress ! This is a plugin that will allow you to write and compile LESS, and to edit style variables straight into your WordPress dashboard.

It uses [the Less.php Compiler](http://lessphp.gpeasy.com/).

## Installation

1. Download the last release
2. Unzip it into your wp-content/plugins directory
3. Activate the plugin in WordPress

## Documentation

[Read the documentation](http://webmaestro.fr/less-compiler-wordpress/)

## How to use

- Register and enqueue your LESS sheets the same way you would do for your CSS.
  ```php
  wp_enqueue_style( 'my-less-handle', 'http://example.com/css/mystyle.less', $deps, $ver, $media );
  ```

- Configure the plugin with the `less_configuration` filter.
  ```php
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
  ```php
  less_set( $variable, $value );
  ```

- Get a LESS variable value
  ```php
  less_get( $variable );
  ```

## Contributors

Contributors are more than welcome !

## License

[WTFPL](http://www.wtfpl.net/) – Do What the Fuck You Want to Public License
