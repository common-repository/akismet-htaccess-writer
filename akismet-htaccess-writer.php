<?php
/*

Plugin Name: Akismet htaccess writer
Plugin URI: http://blog.zodiac.vc/?p=85
Description: in connection with Akismet, write in a .htaccess file
Author: zodiac
Version: 1.0.1
Author URI: http://blog.zodiac.vc/

License: GNU General Public License

Copyright 2008 zodiac (info@blog.zodiac.vc)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

class akismet_htaccess_writer
{
  var $ID = 'akismet-htaccess-writer';
  var $version = '1.0.1';
  var $address = array(
    'plugin_url'   => 'http://blog.zodiac.vc/?p=85',
    'author_url'   => 'http://blog.zodiac.vc/',
    'author_email' => 'i&#110;f&#111;&#64;&#98;log&#46;&#122;&#111;d&#105;&#97;c&#46;&#118;&#99;'
  );
  var $plugin_f;
  var $tag = 'written by WordPress plugin - Akismet htaccess writer';
  var $options = array( );
  var $iplist = array( );


  function
  __construct( )
  {
    $this->plugin_f = $this->ahw_set_plugin_path( );

    add_action( 'activate_'.$this->plugin_f['base'], array( &$this, 'ahw_activate' ) );
    add_action( 'deactivate_'.$this->plugin_f['base'], array( &$this, 'ahw_deactivate' ) );

    if ( !( $this->options = get_option( $this->ID.'_options' ) ) )
      $this->options = array( );

    add_action( 'admin_menu', array( &$this, 'ahw_options' ) );

    add_action( 'wp_set_comment_status', array( &$this, 'ahw_wp_set_comment_status' ), 20 );
    add_action( 'edit_comment', array( &$this, 'ahw_edit_comment' ), 20 );
    add_action( 'comment_post', array( &$this, 'ahw_comment_post' ), 20 );
  }


  function
  ahw_wp_set_comment_status( $id, $status )
  {
    /*$this->_debug( "Enter : ahw_wp_set_comment_status\n" );
    $this->_debug( print_r( $id, TRUE )."\n" );
    $this->_debug( print_r( $status, TRUE )."\n" );
    $this->_debug( print_r( wp_get_comment_status($id), TRUE )."\n" );*/

    if ( wp_get_comment_status( $id ) == 'spam' ) {
      $this->ahw_update_htaccess( TRUE );
    }

    return TRUE;
  }


  function
  ahw_edit_comment( $id )
  {
    /*$this->_debug( "Enter : ahw_edit_comment\n" );
    $this->_debug( print_r( $id, TRUE )."\n" );
    $this->_debug( print_r( wp_get_comment_status($id), TRUE )."\n" );*/

    if ( wp_get_comment_status( $id ) == 'spam' ) {
      $this->ahw_update_htaccess( TRUE );
    }

    return TRUE;
  }


  function
  ahw_comment_post( $p )
  {
    /*$this->_debug( "Enter : ahw_comment_post\n" );
    $this->_debug( print_r( $p, TRUE )."\n" );
    $this->_debug( print_r( wp_get_comment_status($p), TRUE )."\n" );*/

    if ( wp_get_comment_status( $p ) == 'spam' ) {
      $this->ahw_update_htaccess( TRUE );
    }

    return TRUE;
  }


  function
  ahw_set_plugin_path( )
  {
    $r = pathinfo( __FILE__ );
    $r['f'] = __FILE__;
    if ( function_exists( 'mb_ereg_replace' ) ) {
      foreach ( $r as $k => $v ) {
        $r[$k] = mb_ereg_replace( '\\\\', '/', $r[$k] );
      }
      $r['base_dir'] = mb_ereg_replace( '^.*wp-content[\/]plugins[\/]', '', $r['dirname'] );
      $r['base'] = mb_ereg_replace( '^.*wp-content[\/]plugins[\/]', '', $r['f'] );
    }
    else {
      foreach ( $r as $k => $v ) {
        $r[$k] = preg_replace( '/\\\\/', '/', $r[$k] );
      }
      $r['base_dir'] = preg_replace( '/^.*wp-content[\/]plugins[\/]/', '', $r['dirname'] );
      $r['base'] = preg_replace( '/^.*wp-content[\/]plugins[\/]/', '', $r['f'] );
    }

    return $r;
  }


  function
  ahw_activate( )
  {
    //$this->_debug( "activate !!\n" );
    if ( !get_option( $this->ID.'_options' ) )
      add_option( $this->ID.'_options', array( ), 'Akismet htaccess writer plugin settings.', 'no' );

    return TRUE;
  }


  function
  ahw_deactivate( )
  {
    //$this->_debug( "deactivate !!\n" );
    delete_option( $this->ID.'_options' );
    return TRUE;
  }


  function
  ahw_is_writeable( )
  {
    if ( empty( $this->options['htaccess_filename'] ) )
      return FALSE;

    return is_writable( $this->options['htaccess_filename'] );
  }


  function
  ahw_get_iplist( )
  {
  global $wpdb;

    $dbr = $wpdb->get_results(
      "SELECT * FROM $wpdb->comments ".
      "WHERE comment_approved = 'spam' ".
      "GROUP BY comment_author_IP ".
      "ORDER BY comment_date DESC" );
    if ( $dbr === FALSE )
      return FALSE;

    $this->iplist = array( );
    foreach ( $dbr as $c ) {
      $this->iplist[] = $c->comment_author_IP;
    }

    if ( count( $this->iplist ) == 0 )
      return FALSE;

    return TRUE;
  }


  function
  ahw_update_htaccess( $flag = TRUE )
  {
    //$this->_debug( "Enter : ahw_update_htaccess\n" );

    if ( !$this->ahw_get_iplist( ) )
      return FALSE;

    if ( $flag ) {
      if ( !is_writable( $this->options['htaccess_filename'] ) )
        return FALSE;
    }

    foreach ( $this->iplist as $ip ) {
      if ( strlen( $ip ) )
        $output_denylist .= 'Deny From ' . $ip . "\n";
    }

    $list = @file( $this->options['htaccess_filename'], FILE_IGNORE_NEW_LINES );
    if ( $list === FALSE )
      return FALSE;

    $output_s = '';
    $output_e = '';
    $in_begin_flag = 0;
    $in_begin = FALSE;
    $in_end = FALSE;
    foreach ( $list as $v ) {
      if ( preg_match( '/^# BEGIN '.$this->tag.'/', $v ) && $in_begin_flag == 0 ) {
        $in_begin = TRUE;
        $in_begin_flag = 1;
        $output_s .= "$v\n";
        $output_s .= "Order Allow,Deny\n";
        $output_s .= "Allow From All\n";
        continue ;
      }
      elseif ( preg_match( '/^# END '.$this->tag.'/', $v ) && $in_begin_flag == 1 ) {
        $in_end = TRUE;
        $in_begin_flag = 2;
        $output_e .= "$v\n";
        continue ;
      }
      elseif ( $in_begin_flag == 1 ) {
        continue ;
      }
      elseif ( $in_begin_flag == 0 ) {
        $output_s .= "$v\n";
      }
      else {
        $output_e .= "$v\n";
      }
    }
    if ( $in_begin && $in_end ) {
      $this->output = $output_s . $output_denylist . $output_e;
    }
    elseif ( !$in_begin && !$in_end ) {
      $this->output = $output_s;
      $this->output .= "\n# BEGIN ".$this->tag."\n";
      $this->output .= "Order Allow,Deny\n";
      $this->output .= "Allow From All\n";
      $this->output .= $output_denylist;
      $this->output .= '# END '.$this->tag."\n";
    }
    else
      return FALSE;

    if ( $flag ) {
      $f = @fopen( $this->options['htaccess_filename'], 'w' );
      if ( $f === FALSE ) {
        return FALSE;
      }

      if ( @fwrite( $f, $this->output ) === FALSE ) {
        @fclose( $f );
        return FALSE;
      }

      @fclose( $f );
    }

    $this->options['iplist_sha1'] = sha1( $iplist );

    return array(
      'iplist' => $this->iplist,
      'output' => $this->output,
    );
  }


  function
  ahw_options( )
  {
    //load_plugin_textdomain( $this->ID, PLUGINDIR.'/'.$this->ID );
    load_plugin_textdomain( $this->ID, PLUGINDIR.'/'.$this->plugin_f['base_dir'] );

    add_submenu_page(
      'plugins.php',
      __( 'Akismet htaccess writer', $this->ID ),
      __( 'Akismet htaccess writer', $this->ID ),
      'manage_options',
      $this->ID.'_options',
      array( &$this, 'ahw_options_page' ) );
//      $this->plugin_f['dirname'] . '/options.php' );

  }


  function
  ahw_options_page( )
  {
echo '<div class="wrap"><h2>' . __( 'Akismet htaccess writer', $this->ID ) . ' : ' . $this->version . "</h2>\n";

if ( empty( $_REQUEST['ahw_options'] ) ) {
  if ( empty( $this->options['htaccess_filename'] ) ) {
    $this->_ahw_options_errorout( 3 );
echo "<ul>\n";
echo "<li>".__( 'perhaps, the setting is not preserved. please check the file with the "check file" button, and preserve the setting with the "save setting" button if it is OK', $this->ID )."</li>\n";
echo "<li>".__( 'the filename set to the text input field is a filename that Akismet htaccess writer plug-in guessed. please correct it according to the environment used', $this->ID )."</li>\n";
echo "</ul>\n";

    $this->options['htaccess_filename'] = ABSPATH . '.htaccess';
  }
  elseif ( !$this->ahw_is_writeable( ) ) {
    $this->_ahw_options_errorout( 2 );
  }
}
else {
  $awh_options = $_REQUEST['ahw_options'];

  if ( !empty( $awh_options['check'] ) ) {
    $this->options['htaccess_filename'] = $awh_options['h_fname'];
    $this->_ahw_options_errorout( $this->_ahw_options_checkfile( $this->options['htaccess_filename'] ) );
  }
  elseif ( !empty( $awh_options['save'] ) ) {
    $this->options['htaccess_filename'] = $awh_options['h_fname'];

    /* re-check and save setting */
    $r = $this->_ahw_options_checkfile( $this->options['htaccess_filename'] );
    if ( $r != 0 ) {
      $this->_ahw_options_errorout( $r );
    }
    elseif ( update_option( $this->ID.'_options', $this->options ) ) {
      echo '<p style="color:#ff1493; font-weight:bold;">' . __( 'save done', $this->ID ) . "</p>\n";
    }
    else {
      echo '<p style="color:#ff1493; font-weight:bold;">' . __( 'save fail', $this->ID ) . "</p>\n";
    }

  }
  elseif ( !empty( $awh_options['write'] ) ) {
    $this->ahw_update_htaccess( TRUE );
    echo '<p style="color:#ff1493; font-weight:bold;">' . __( 'it wrote it in the .htaccess file', $this->ID ) . "</p>\n";
  }
}

echo "<form id=\"ahw_options\" method=\"post\">\n";

echo '<p><label for="h_fname">' . __( '.htaccess filename to be written', $this->ID ) . "</label><br />\n";
echo '<input type="text" id="h_fname" name="ahw_options[h_fname]" size="80" value="'. $this->options['htaccess_filename'] . "\" /></p>\n";

echo '<p style="font-weight:bold; color:#ff1493;">';
_e( 'the filename that should be set here should be a filename composed of a complete pathname on the filesystem of the server. please note the thing that is not a virtual filesystem from the Web system', $this->ID );
echo "</p>\n";

echo '<input type="submit" class="button" name="ahw_options[save]" value="' . __( 'save setting', $this->ID ) . "\" />\n";
echo '<input type="submit" class="button" name="ahw_options[check]" value="' . __( 'check file', $this->ID ) . "\" />\n";
echo '<input type="submit" class="button" name="ahw_options[write]" value="' . __( 'write now', $this->ID ) . "\" /><br />\n";

echo "</form>\n";

if ( $this->_ahw_options_checkfile( $this->options['htaccess_filename'] ) == 0 ) {
echo '<p>' . __( 'the content of .htaccess is as follows by pushing the "write now" button', $this->ID ) . "</p>\n";
echo '<textarea cols="80" rows="10" readonly="readonly">';
$rc = $this->ahw_update_htaccess( FALSE );
echo $rc['output'];
echo "</textarea>\n";
}

if ( $this->ahw_get_iplist( ) ) {
echo '<p>' . __( 'list of SPAM transmission former IP address', $this->ID ) . "</p>\n";
echo '<textarea cols="80" rows="10" readonly="readonly">';
foreach ( $this->iplist as $ip ) {
  echo "$ip\n";
}
echo "</textarea>\n";
}

echo '<div style="margin:2em; border:none; border-top:1px solid #dadada; text-align:center;">';
echo 'copyright &copy; 2008 <a href="mailto:' . $this->address['author_email'] . '">zodiac</a>';
echo "</div>\n";

echo "</div>\n";

//echo "<pre>";
//echo "</pre>";
  }


  function
  _ahw_options_checkfile( $f )
  {
    if ( !file_exists( $f ) )
      return 1;

    elseif ( !is_writable( $f ) )
      return 2;

    else {
      return 0;
    }
  }


  function
  _ahw_options_errorout( $rc )
  {
    switch ( $rc ) {
      case 0:
        echo '<p style="color:#ff1493; font-weight:bold;">' . __( 'check OK', $this->ID ) . "</p>\n";
      break;

      case 1:
        echo '<p style="color:#ff1493; font-weight:bold;">' . __( 'file not found', $this->ID ) . "</p>\n";
      break;

      case 2:
        echo '<p style="color:#ff1493; font-weight:bold;">' . __( 'not writeable .htaccess file', $this->ID ) . "</p>\n";
      break;

      case 3:
        echo '<p style="color:#ff1493; font-weight:bold;">' . __( 'not specified .htaccess file', $this->ID ) . "</p>\n";
      break;
    }
  }


  function
  _debug (
    $text,
    $display = FALSE )
  {
    $LogFile = $this->plugin_f['dirname'] . '/' . $this->plugin_f['filename'] . '.log';
    $LogDateTime = 'Y-m-d(D) H:i:s';

    $log = fopen( $LogFile, 'a' );
    $buffer = '[' . date( $LogDateTime ) . '] ' . $text;
    fwrite( $log, $buffer );
    fclose( $log );

    if ( $display )
      echo $buffer;
  }


} $akismet_htaccess_writer = new akismet_htaccess_writer( );
?>
