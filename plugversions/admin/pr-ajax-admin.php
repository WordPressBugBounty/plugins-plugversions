<?php
/**
 * It includes the code for the Ajax activities.

 * @package Plugversions
 */

defined( 'PLUGIN_REVISIONS_PLUGIN_DIR' ) || exit; // Exit if not accessed from Plugversions.

add_action( 'wp_ajax_eos_plugin_reviews_restore_version','eos_plugin_reviews_restore_version' );
/**
 * Restore plugin version
 *
 * @since  0.0.1
 */ 
function eos_plugin_reviews_restore_version(){
  if( isset( $_POST['nonce'] ) && isset( $_POST['dir'] ) && isset( $_POST['parent_plugin'] ) && wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ),'plugin_reviews_restore_version' ) ){
    $key = eos_plugin_revision_key();
    if( $key ){
      global $wp_filesystem;
      if( empty( $wp_filesystem ) || ! function_exists( 'unzip_file' ) ) {
        require_once ABSPATH .'/wp-admin/includes/file.php';
        WP_Filesystem();
      }
      $time = time();
      $dir = sanitize_text_field( $_POST['dir'] ); // $dir is the path of the plugin versoin that will be restored.
      $plugin = sanitize_text_field( $_POST['parent_plugin'] );
      $plugin_data = get_plugin_data( dirname( PLUGIN_REVISIONS_PLUGIN_DIR ) . '/' . $plugin );
      $version = $plugin_data['Version'];
      $plugin_version = dirname( PLUGIN_REVISIONS_PLUGIN_DIR ) . '/pr-' . $key . '-' . sanitize_option( 'upload_path',$version ) . '-ver-' . $time . dirname( $plugin );
      // $plugin_version is the path assigned to the plugin that will be replaced.

      $r_unzip = true;
      if( 'zip' === pathinfo( $dir, PATHINFO_EXTENSION ) ) {
        if( ! function_exists( 'unzip_file' ) ) {
          die();
          exit;
        }
        $r_unzip = unzip_file( 
          dirname( PLUGIN_REVISIONS_PLUGIN_DIR ) . '/' . $dir, 
          dirname( PLUGIN_REVISIONS_PLUGIN_DIR )
        ); // Unzip the chosen version.
        if( $r_unzip ) {
          unlink( dirname( PLUGIN_REVISIONS_PLUGIN_DIR ) . '/' . $dir ); // Delete the zip after unzipping it.
        }
      }
      $r1 = rename( dirname( PLUGIN_REVISIONS_PLUGIN_DIR ) . '/' . dirname( $plugin ), $plugin_version ); // Rename the actual plugin that will be replaced giving a unique title.
      $r2 = rename( dirname( PLUGIN_REVISIONS_PLUGIN_DIR ) . '/' . str_replace( '.zip', '', $dir ) , dirname( PLUGIN_REVISIONS_PLUGIN_DIR ) . '/' . dirname( $plugin ) ); // Rename the chosen plugin version with the official plugin name.
      $replaced_version_path = str_replace( $time,'',$plugin_version ); // Remove the time() from the title in the replaced plugin.
      $r3 = rename( $plugin_version, $replaced_version_path );
      $r_zip = eos_pv_create_zip_pclzip( $replaced_version_path, '/pr-' . $key . '-' . sanitize_option( 'upload_path', $version ) . '-ver-' . dirname( $plugin ) );
      if( $r_zip ) {
        $wp_filesystem->delete( $replaced_version_path, true );
      }
      do_action( 'activate_plugin',$plugin );
      do_action( "activate_{$plugin}" );
      do_action( 'activated_plugin', $plugin );
      echo (bool) ( $r_unzip && $r1 && $r2 && $r3 && $r_zip ); // Echo true if everything was successfull.
    }
  }
  die();
  exit;
}
