<?php
/**
 * It includes the code for the backend.

 * @package Plugversions
 */
defined( 'PLUGIN_REVISIONS_PLUGIN_DIR' ) || exit; // Exit if not accessed from Plugversions.

if( wp_doing_ajax() ){
  //* Include the Ajax admin file.
  require_once PLUGIN_REVISIONS_PLUGIN_DIR . '/admin/pr-ajax-admin.php';
}

register_activation_hook( __FILE__, function() {
  /**
   * Actions triggered after plugin activation or after a new site of a multisite installation is created
   *
   * @since  0.0.1
   */  
  if( ! eos_plugin_revision_key() ){
    update_site_option( 'plugin_revisions',array( 'time' => time() ) );
  }
} );

add_action( 'admin_head',function(){
  /**
   * Add style to properly show the revisions on the page of plugins
   *
   * @since  0.0.1
   */    
  global $pagenow;
  if( $pagenow && 'plugins.php' === sanitize_text_field( $pagenow ) ){
  ?>
  <style id="plugin-revisions-css" type="text/css">
  .plugin-revision-wrp{
    position:relative
  }
  .plugin-revisions-vers{
    display:none;
    position:absolute;
    <?php echo is_rtl() ? 'right' : 'left'; ?>:0;
    top:0
  }
  .plugin-revision-wrp:hover .plugin-revisions-vers{
    display:block;
    min-width:200px;
    min-width:max-content;
    background:#fff;
    margin-top:15px;
    padding:10px 10px;
    z-index:9
  }
  .plugin-revision-wrp:hover .plugin-revisions-vers a{
    display:block;
    border-bottom:1px dashed;
    margin-bottom:5px;
    background-image:url(<?php echo esc_url( PLUGIN_REVISIONS_PLUGIN_URL ); ?>/admin/assets/images/ajax-loader.gif );
    background-position:-9999px -9999px;
    background-repeat:no-repeat;
    background-size:16px 16px
  }
  </style>
  <?php
  }
} );

add_action( 'admin_footer',function(){
  /**
   * Add JS to restore a revision via Ajax
   * This script is added to the plugins.php and plugin-editor.php pages.
   *
   * @since  0.0.1
   */    
  global $pagenow;
  if( $pagenow && 'plugins.php' === sanitize_text_field( $pagenow ) ){
    wp_nonce_field( 'plugin_reviews_restore_version','plugin_reviews_restore_version' );
  ?>
  <script id="plugin-revisions-js">
  function eos_plugin_revisions(){
    var as = document.getElementsByClassName('plugin-revision-action'),n=0,req = new XMLHttpRequest(),fd=new FormData(),nonce=document.getElementById('plugin_reviews_restore_version').value;
    for(n;n<as.length;++n){
      as[n].addEventListener('click',function(e){
        e.preventDefault();
        this.style.backgroundPosition = 'center center';
        for(var k=0;k<as.length;++k){
          as[k].style.pointerEvents = 'none';
        }
        fd.append("dir",this.dataset.dir);
        fd.append("parent_plugin",this.dataset.parent_plugin);
        fd.append("nonce",nonce);
        req.open("POST","<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>" + '?action=eos_plugin_reviews_restore_version',true);
        req.send(fd);
        return false;
      });
    }
    req.onload = function(e) {
      for(n;n<as.length;++n){
        as[n].style.backgroundPosition = '-9999px -9999px';
      }
  		if(this.readyState === 4) {
  			window.location.reload();
  		}
      else{
        alert('Something went wrong!');
      }
  		return false;
  	};
    return false;
  }
  eos_plugin_revisions();
  </script>
  <?php
  }
  if( $pagenow && 'plugin-editor.php' === sanitize_text_field( $pagenow ) ){
    $key = eos_plugin_revision_key();
  ?>
  <script id="plugin-revisions-file-editor-js">
  function eos_plugin_revisions_fild_editor(){
    var os=document.getElementById('plugin').getElementsByTagName('option'),n=0,ver='';
    for(n;n<os.length;++n){
      if(os[n].value.indexOf("<?php echo esc_js( $key ); ?>") > 0){
        os[n].innerHTML += ' ' + os[n].value.split('-ver-')[0].split('-')[2];
      }
    }
  }
  eos_plugin_revisions_fild_editor();
  </script>
  <?php
  }
} );

/**
 * Remove all plugin revisions
 * This function removes all revisions of a specific plugin or all plugins if no plugin name is provided.
 *
 * @since  0.0.1
 */  
function eos_plugin_revisions_remove_versions( $N = false,$plugin_name = false ){
  $key = eos_plugin_revision_key();
  $all_dirs = eos_plugin_revisions_scandir( dirname( PLUGIN_REVISIONS_PLUGIN_DIR ) );
  global $wp_filesystem;
  if( empty( $wp_filesystem ) ){
    require_once ABSPATH . 'wp-admin/includes/file.php';
    WP_Filesystem();
  }
  if( $all_dirs && !empty( $all_dirs ) ){
    foreach( $all_dirs as $all_dir ){
      if( substr( $all_dir,-strlen( $plugin_name ),strlen( $plugin_name ) ) !== $plugin_name ){
        unset( $all_dirs[array_search( $all_dir,$all_dirs )] );
      }
    }
    $n = 0;
    foreach( $all_dirs as $dir ){
      if( false !== strpos( $dir,'pr-'.$key.'-' ) ){
        if( $N && $n < ( count( $all_dirs ) - absint( $N ) ) ){
          $result =  $wp_filesystem->delete( dirname( PLUGIN_REVISIONS_PLUGIN_DIR ).'/'.$dir,true );
        }
      }
      ++$n;
    }
  }
}

/**
 * Remove all revisions of all the plugins
 *
 * @since  0.0.1
 */ 
function eos_plugin_revisions_remove_all_versions(){
  $key = eos_plugin_revision_key();
  $all_dirs = eos_plugin_revisions_scandir( dirname( PLUGIN_REVISIONS_PLUGIN_DIR ) );
  global $wp_filesystem;
  if( empty( $wp_filesystem ) ){
    require_once ABSPATH . 'wp-admin/includes/file.php';
    WP_Filesystem();
  }
  if( $all_dirs && !empty( $all_dirs ) ){
    foreach( $all_dirs as $dir ){
      if( false !== strpos( $dir,'pr-'.$key.'-' ) ){
        $result =  $wp_filesystem->delete( dirname( PLUGIN_REVISIONS_PLUGIN_DIR ).'/'.$dir,true );
      }
    }
  }
}

/**
 * Revisions scandir
 * This function scans the plugin revisions directory and returns an array of files sorted by modification time.
 *
 * @since  0.0.1
 */ 
function eos_plugin_revisions_scandir( $dir ) {
  $ignored = array('.', '..', '.svn', '.htaccess');
  $files = array();
  foreach( scandir( $dir ) as $file ){
    if( in_array( $file,$ignored ) ) continue;
    $files[$file] = filemtime( $dir.'/'.$file );
  }
  asort( $files );
  $files = array_keys( $files );
  return ( $files ) ? $files : false;
}

add_action( 'admin_init', 'eos_pv_restore_revision_links' );
/**
 * Add links to restore previous revisions from zipped plugins.
 * This function initializes the restoring link functionality for plugin revisions.
 *
 * @since 0.0.6
 */
function eos_pv_restore_revision_links() {
  require_once PLUGIN_REVISIONS_PLUGIN_DIR . '/admin/classes/class-plugversions-restoring-link.php';
  $link = new PlugVersions_Restoring_Link();
}



add_filter(
	'upgrader_package_options',
	function ( $options ) {
		//*
		//* Update revisions after plugin update
    //* This filter is used to create a backup of the plugin before it is updated.
		//*
		//* @since  0.0.1
		//* //
if ( isset( $options['destination'] ) && isset( $options['hook_extra'] ) ) {
	$hook_extra = $options['hook_extra'];
	if ( isset( $hook_extra['plugin'] ) ) {
		$plugin      = $hook_extra['plugin'];
		$plugin_name = dirname( $plugin );
		$path        = sanitize_option( 'upload_path', $options['destination'] . '/' . $plugin );
		if ( file_exists( $path ) ) {
				$plugin_data = get_plugin_data( $path );
				$version     = $plugin_data['Version'];
			if ( ! class_exists( 'WP_Upgrader' ) ) {
				if ( file_exists( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' ) ) {
							require_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
				}
			}
			if ( class_exists( 'WP_Upgrader' ) ) {
				$upgrader = new WP_Upgrader();
				$key      = eos_plugin_revision_key();
				if ( $key ) {
					$zip_dirname = 'pr-' . $key . '-' . sanitize_option( 'upload_path', $version ) . '-ver-' . dirname( $plugin );
					$destination = str_replace( dirname( $plugin ), $zip_dirname, sanitize_option( 'upload_path', plugin_dir_path( $path ) ) );
					if ( ! is_dir( $destination ) ) {
						global $wp_filesystem;
						if ( empty( $wp_filesystem ) ) {
							require_once ABSPATH . '/wp-admin/includes/file.php';
							WP_Filesystem();
						}
						$wp_filesystem->mkdir( $destination );
					}
					if ( ! empty( $wp_filesystem ) && $wp_filesystem->is_dir( $destination ) ) {
						$result       = copy_dir( plugin_dir_path( $path ), $destination );
						$zip_dirname .= '.zip';
						if ( eos_pv_create_zip_pclzip( $destination, $zip_dirname ) ) {
							// If zipped archive created delete the unzipped directory.
							$wp_filesystem->delete( $destination, true );
						}
						eos_plugin_revisions_remove_versions( apply_filters( 'max_plugin_revisions', 4 ), $plugin_name );
					}
				}
			}
		}
	}
}
		return $options;
	},
	10,
	4
);


/**
 * Backup the plugin before update.
 * This filter allows you to catch all possible update scenarios, including bulk and manual replacements, as well as Ajax updates.
 *
 * @param string $source Plugin source path.
 * @param string $remote_source Plugin remote source path.
 * @param object $upgrader Upgrader object.
 * @param array  $hook_extra Hook extra data.
 * @return string
 *
 * @author Vincenzo Casu.
 */
function eos_plugin_unversal_backup( $source, $remote_source, $upgrader, $hook_extra ) {
	// error_log( 'HOOK_EXTRA: ' . json_encode( $hook_extra ) );

	// Load get_plugin_data() if not exists.
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// Get plugins to backup.
	$plugins_to_backup = array();

	if ( ! empty( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ) {
		// Bulk update (hook_extra['plugins']).
		// error_log( 'Bulk update plugins: ' . json_encode( $hook_extra['plugins'] ) );
		$plugins_to_backup = $hook_extra['plugins'];
	} elseif ( ! empty( $hook_extra['plugin'] ) ) {
		// Single AJAX update (hook_extra['plugin']).
		// error_log( 'Single plugin update: ' . $hook_extra['plugin'] );
		$plugins_to_backup = array( $hook_extra['plugin'] );
	} elseif ( ! empty( $hook_extra['type'] ) && $hook_extra['type'] === 'plugin'
		&& ! empty( $hook_extra['action'] )
		&& in_array( $hook_extra['action'], array( 'update', 'install' ), true ) ) {
		// Fallback “replace from ZIP” or other plugin-based cases.

		// Get slug from source.
		$slug = basename( $source );
		// error_log( 'Fallback slug: ' . $slug );

		$all = get_plugins( '/' . $slug );
		// error_log( 'Found via get_plugins: ' . json_encode( array_keys( $all ) ) );

		if ( ! empty( $all ) ) {
			$main_file           = key( $all );
			$plugins_to_backup[] = $slug . '/' . $main_file;
		} else {
			// error_log( 'No plugin data for slug ' . $slug );
			return $source;
		}
	} else {
		// error_log( 'Nothing to backup, return.' );
		return $source;
	}

	// Preparing Filesystem & Upgrader.
	if ( ! class_exists( 'WP_Upgrader' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}

	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}

	$key = eos_plugin_revision_key();
	// error_log( 'Revision key: ' . $key );
	if ( ! $key ) {
		return $source;
	}

	foreach ( $plugins_to_backup as $plugin_file ) {
		// error_log( 'Backing up: ' . $plugin_file );

		$plugin_dir = WP_PLUGIN_DIR . '/' . dirname( $plugin_file );
		$full_path  = WP_PLUGIN_DIR . '/' . $plugin_file;

		if ( ! file_exists( $full_path ) ) {
			// error_log( 'Main file non trovato: ' . $full_path );
			continue;
		}

		// Metadata & version.
		$plugin_data = get_plugin_data( $full_path );
		$version     = $plugin_data['Version'];
		// error_log( 'Version: ' . $version );

		$slug        = dirname( $plugin_file );
		$zip_dirname = 'pr-' . $key . '-' . sanitize_file_name( $version ) . '-ver-' . $slug;
		// error_log( 'zip_dirname: ' . $zip_dirname );

		// Base dir & Backup destination.
		$base_dir = plugin_dir_path( $full_path );
		// error_log( 'base_dir: ' . $base_dir );
		$backup_dir = trailingslashit( str_replace( $slug, $zip_dirname, $base_dir ) );
		// error_log( 'backup_dir: ' . $backup_dir );

		// Make Backup dir and move plugin files into it.
		if ( ! $wp_filesystem->is_dir( $backup_dir ) ) {
			$wp_filesystem->mkdir( $backup_dir );
		}
		if ( $wp_filesystem->is_dir( $backup_dir ) ) {
			copy_dir( $plugin_dir, $backup_dir );

			$zip_file = $zip_dirname . '.zip';
			// error_log( 'zip_file: ' . $zip_file );

			if ( eos_pv_create_zip_pclzip( $backup_dir, $zip_file ) ) {
				$wp_filesystem->delete( untrailingslashit( $backup_dir ), true );
			}

			// Remove old versions of the plugin.
			eos_plugin_revisions_remove_versions(
				apply_filters( 'max_plugin_revisions', 4 ),
				$slug
			);
		}
	}

	return $source;
}
add_filter( 'upgrader_source_selection', 'eos_plugin_unversal_backup', 10, 4 );