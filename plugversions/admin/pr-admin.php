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
  <style id="plugin-revisions-js-css" type="text/css">
  .plugin-revision-processing{position:relative;cursor:wait;}
  .plugin-revision-processing:after{
    content:' ';
    position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    background-color:rgba(255,255,255,0.8);
    border-radius:50%;
    box-shadow:0 0 5px rgba(0,0,0,0.2);
    z-index:9999;
    background-size:16px 16px;
    background-image:url(<?php echo esc_url( PLUGIN_REVISIONS_PLUGIN_URL ); ?>/admin/assets/images/ajax-loader.gif );
    background-position:center center;
    background-repeat:no-repeat;
    width:16px;
    height:16px;
    display:block;
    opacity:1
  }
  </style>
  <script id="plugin-revisions-js">
  function eos_plugin_revisions(){
    var as = document.getElementsByClassName('plugin-revision-action'),n=0,req = new XMLHttpRequest(),fd=new FormData(),nonce=document.getElementById('plugin_reviews_restore_version').value;
    for(n;n<as.length;++n){
      as[n].addEventListener('click',function(e){
        e.preventDefault();
        this.closest('td').classList.add('plugin-revision-processing');
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
	// Load get_plugin_data() if not exists.
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// Get plugins to backup.
	$plugins_to_backup = array();

	if ( ! empty( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ) {
		// Bulk update (hook_extra['plugins']).
		$plugins_to_backup = $hook_extra['plugins'];
	} elseif ( ! empty( $hook_extra['plugin'] ) ) {
		// Single AJAX update (hook_extra['plugin']).
		$plugins_to_backup = array( $hook_extra['plugin'] );
	} elseif ( ! empty( $hook_extra['type'] ) && $hook_extra['type'] === 'plugin'
		&& ! empty( $hook_extra['action'] )
		&& in_array( $hook_extra['action'], array( 'update', 'install' ), true ) ) {
		// Fallback “replace from ZIP” or other plugin-based cases.

		// Get slug from source.
		$slug = basename( $source );
		$all = get_plugins( '/' . $slug );

		if ( ! empty( $all ) ) {
			$main_file           = key( $all );
			$plugins_to_backup[] = $slug . '/' . $main_file;
		} else {
		  // If no plugins found, return the source.
			return $source;
		}
	} else {
    // No plugins to backup.
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
	if ( ! $key ) {
		return $source;
	}
	foreach ( $plugins_to_backup as $plugin_file ) {
		$plugin_dir = WP_PLUGIN_DIR . '/' . dirname( $plugin_file );
		$full_path  = WP_PLUGIN_DIR . '/' . $plugin_file;
		if ( ! file_exists( $full_path ) ) {
			continue;
		}

		// Metadata & version.
		$plugin_data = get_plugin_data( $full_path );
		$version     = $plugin_data['Version'];
		$slug        = dirname( $plugin_file );
		$zip_dirname = 'pr-' . $key . '-' . sanitize_option( 'upload_path', $version ) . '-ver-' . $slug;

		// Base dir & Backup destination.
		$base_dir = plugin_dir_path( $full_path );
		$backup_dir = trailingslashit( str_replace( $slug, $zip_dirname, $base_dir ) );
		// Make Backup dir and move plugin files into it.
		if ( ! $wp_filesystem->is_dir( $backup_dir ) ) {
			$wp_filesystem->mkdir( $backup_dir );
		}
		if ( $wp_filesystem->is_dir( $backup_dir ) ) {
			copy_dir( $plugin_dir, $backup_dir );
			$zip_file = $zip_dirname . '.zip';
      // Create a zip file of the plugin.

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

/**
 * Rollback WordPress core to a specific version.
 * This function allows you to rollback the WordPress core to a specified version.
 * It downloads the specified version, unzips it, and replaces the current core files,
 * while preserving the wp-content directory and wp-config.php file.
 *
 * @param string $version The version of WordPress to rollback to. Default is '6.4.3'.
 * @return bool|WP_Error Returns true on success or WP_Error on failure.
 *
 * @since  1.0.0
 */
function eos_pv_rollback_wordpress_core($version) {
    if ( ! current_user_can('update_core') ) {
        wp_die( esc_html__( 'You are not allowed to update the core.', 'plugversions' ) );
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/update.php';

    WP_Filesystem();
    global $wp_filesystem;

    // Put site in maintenance mode
    $maintenance_string = '<?php $upgrading = ' . time() . '; ?>';
    $wp_filesystem->put_contents(ABSPATH . '.maintenance', $maintenance_string, FS_CHMOD_FILE);

    // Download WordPress version zip
    $zip_url = "https://wordpress.org/wordpress-{$version}.zip";
    $tmp_file = download_url($zip_url);

    if ( is_wp_error($tmp_file) ) {
        $wp_filesystem->delete(ABSPATH . '.maintenance');
        return new WP_Error('download_failed', 'Failed to download WordPress version.');
    }

    // Unzip into temp folder
    $unzip_dir = WP_TEMP_DIR . "wordpress-rollback-{$version}";
    $result = unzip_file($tmp_file, $unzip_dir);
    unlink($tmp_file); // Remove zip file

    if ( is_wp_error($result) ) {
        $wp_filesystem->delete(ABSPATH . '.maintenance');
        return $result;
    }

    // Copy files from the unzipped wordpress/ directory
    $source = trailingslashit($unzip_dir) . 'wordpress';
    $dest = ABSPATH;

    $items = $wp_filesystem->dirlist($source);
    foreach ($items as $item => $details) {
        if ($item === 'wp-content' || $item === 'wp-config.php') {
            continue; // Skip user files
        }
        $source_path = trailingslashit($source) . $item;
        $dest_path = trailingslashit($dest) . $item;

        // Remove existing file or folder
        if ($wp_filesystem->exists($dest_path)) {
            $wp_filesystem->delete($dest_path, true);
        }

        // Copy new one
        $wp_filesystem->copy($source_path, $dest_path, true, FS_CHMOD_FILE);
    }

    // Cleanup
    $wp_filesystem->delete($unzip_dir, true);
    $wp_filesystem->delete(ABSPATH . '.maintenance');

    return true;
}

add_action('admin_init', function() {
    /**
     * Handle rollback request for WordPress core.
     * This function checks if the user has permission to update the core,
     * verifies the nonce, and processes the rollback request.
     *
     * @since  1.0.0
     */

    if (isset($_GET['rollback_wp']) && current_user_can('update_core') && check_admin_referer('eos_pv_core_rollback_nonce')) {
        $version = sanitize_text_field($_GET['rollback_wp']);
        // Validate version format
        // Example: 6.4.3
        // Ensure it matches the expected format of major.minor.patch
        // This regex checks for three groups of digits separated by dots.
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            wp_die(esc_html__('Invalid version format.', 'plugversions'));
        }

        // Perform the rollback
        $result = eos_pv_rollback_wordpress_core( $version );
        if (is_wp_error($result)) {
            echo 'Error: ' . $result->get_error_message();
        } else {
            esc_html_e( 'Rollback completed. Please manually check your site.', 'plugversions' );
        }
        exit;
    }
});

add_action('upgrader_process_complete', function ($upgrader, $hook_extra) {
    /**
     * Save the current WordPress core version after an update.
     * This function saves the current WordPress version to an option after a core update.
     * It prevents duplicates and limits the saved versions to the last 3.
     *
     * @param WP_Upgrader $upgrader The upgrader instance.
     * @param array $hook_extra Extra data passed to the hook.
     *
     * @since  1.0.0
     */

    // Check if this is a core update
    if ( ! is_a( $upgrader, 'Core_Upgrader' ) || ! isset( $hook_extra['type'], $hook_extra['action'] ) ) {
        return;
    }

    // Check if the action is an update
    // and the type is core.
    // This ensures that we only save versions after a core update.
    if (
        isset($hook_extra['type'], $hook_extra['action']) &&
        $hook_extra['type'] === 'core' &&
        $hook_extra['action'] === 'update'
    ) {
        $current_version = get_bloginfo('version');
        $option_name = 'eos_pv_core_versions';

        // Get existing saved versions
        $saved_versions = get_option($option_name, []);

        // Prevent duplicates
        if (!in_array($current_version, $saved_versions, true)) {
            array_unshift($saved_versions, $current_version); // Add to beginning
        }

        // Optional: limit to last 3 versions
        $saved_versions = array_slice($saved_versions, 0, 3);

        update_option($option_name, $saved_versions);
    }
}, 10, 2);

add_action('admin_notices', function () {
  return; // Disable admin notices for now, as the core rollback functionality is still not ready.
    /**
     * Display rollback notice on the update-core page.
     * This function displays a notice with a dropdown to select a previous WordPress core version
     * for rollback, if available.
     *
     * @since  1.0.0
     */

    // Only show on the update-core screen

    if (get_current_screen()->id !== 'update-core') return;

    $versions = get_option('eos_pv_core_versions', []); // Get saved versions
    // Ensure versions are unique and sorted
    $versions = array_unique($versions);
    sort($versions);

    // If no versions are available, do not display the notice
    if (empty($versions)) return;

    ?>
    <div class="notice notice-info">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <h3><?php esc_html_e( 'Rollback WordPress Core', 'plugversions' ); ?></h3>
            <p><?php esc_html_e( 'Select a previously stored version to roll back to:', 'plugversions' ); ?></p>
            <select name="eos_pv_version_to_rollback">
                <?php foreach ($versions as $version): ?>
                    <option value="<?php echo esc_attr($version); ?>">
                        <?php echo esc_html($version); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php wp_nonce_field('eos_pv_rollback_wp_core', 'eos_pv_nonce'); ?>
            <input type="hidden" name="action" value="eos_pv_rollback_core">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Rollback', 'plugversions' ); ?></button>
        </form>
    </div>
    <?php
});

add_filter( 'plugin_action_links_plugversions/plugversions.php', function( $links ) {
  /**
   * Add plugin action link for Annual Protection Plan.
   *
   * @param array $links Array of plugin action links
   * @return array Modified array of plugin action links
   */
  $plan_link = '<a href="https://shop.josemortellaro.com/downloads/plugin-update-rescue-annual-protection-plan/" style="color: #d63638; font-weight: bold;">' . esc_html__( 'Protection Plan', 'plugversions' ) . '</a>'; 
  // Add to the beginning of the links array
  array_unshift( $links, $plan_link );
  return $links;
} );