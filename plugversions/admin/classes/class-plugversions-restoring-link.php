<?php
/**
 * Class to add the restoring link.
 *
 * @package Plugversions
 */

defined( 'PLUGIN_REVISIONS_PLUGIN_DIR' ) || exit; // Exit if not accessed from PlugVersions.

/**
 * Class PlugVersions Restoring Link
 *
 *
 * @version  0.0.6
 * @package  PlugVersions
 */
class PlugVersions_Restoring_Link {

	/**
	 * Key.
	 *
	 * @var string $this->key
	 * @since  0.0.6
	 */	
	public $key;

    /**
	 * Plugins.
	 *
	 * @var array $plugins
	 * @since  0.0.8
	 */	
	public $plugins;

    /**
	 * Main Constructor
	 *
	 * @since  0.0.6
	 */	
    public function __construct() {
        $this->key = eos_plugin_revision_key();
		$this->plugins = array();
		if( $this->key && current_user_can( 'activate_plugin' ) ) {
			$this->plugins = $this->populate_plugins();
			if( ! empty( $this->plugins ) ) {
				add_filter( 'plugin_action_links' , array( $this, 'add_link' ), 10, 4 );
			}
			add_filter( 'all_plugins', array( $this, 'clean_plugins_list' ) );
		}
    }

    /**
	 * Populate plugins data
	 *
	 * @since  0.0.6
	 */	
	public function populate_plugins() {
		$all_plugin_files = scandir( WP_PLUGIN_DIR );
		if( $all_plugin_files && is_array( $all_plugin_files ) && ! empty( $all_plugin_files ) ) {
			$plugins = array();
			foreach( $all_plugin_files as $plugin ) {
				if( 'zip' === pathinfo( $plugin, PATHINFO_EXTENSION ) ) {
					if( false !== strpos( $plugin, 'pr-' . $this->key  .'-' ) && false !== strpos( $plugin, '-ver-' ) ) {
					$arr = explode( 'ver-', $plugin );
					$arr = explode( '-', $arr[0] );
					if( 'pr' === $arr[0] && isset( $arr[1] ) && $this->key === $arr[1] && isset( $arr[2] ) ) {
						$version = $arr[2];
						$plugin_name = str_replace( array( 'pr-' . $this->key . '-' . $version . '-ver-', '.zip' ), array( '', '' ), $plugin );
						if( is_dir( WP_PLUGIN_DIR . '/' . $plugin_name ) ) {
							if( ! isset( $plugins[$plugin_name]['versions'] ) ) {
								$plugins[$plugin_name]['versions'] = array();
							}
							if( ! isset( $plugins[$plugin_name]['zips'] ) ) {
								$plugins[$plugin_name]['zips'] = array();
							}
							$plugins[$plugin_name]['versions'][] = $version;
							$plugins[$plugin_name]['zips'][] = $plugin;
						}
					}
					}
				}
			}
			return $plugins;
		}
	}

    /**
	 * Remove unzipped versions from the plugins list
	 *
	 * @param array $plugins
	 * @param  0.0.8
	 */	    
    public function clean_plugins_list( $plugins ) {
		foreach( $plugins as $plugin => $arr ) {
			if( false !== strpos( '_' . $plugin, 'pr-' . $this->key ) ) {
				unset( $plugins[$plugin] );
			}
		}
		return $plugins;
	}

    /**
	 * Add action link to restore plugin version.
	 *
	 * @param array $actions
	 * @param string $plugin_file
	 * @param array $plugin_data
	 * @param string $context
	 * @param  0.0.6
	 */	    
    public function add_link( $actions, $plugin_file, $plugin_data, $context ) {
		if( isset( $this->plugins[dirname( $plugin_file )] ) ) {
			$plugin_vers = $this->plugins[dirname( $plugin_file )];
			if( $plugin_vers && ! empty( $plugin_vers ) ) {
				$links = '';
				$n = 0;
				foreach( $plugin_vers['versions'] as $version ) {
					if( isset( $plugin_vers['zips'][$n] ) ) {
						$zip_name = $plugin_vers['zips'][$n];
						/* translators: version of the plugin. */
						$links .= '<a class="plugin-revision-action" href="#" data-parent_plugin="'.esc_attr( $plugin_file ).'" data-dir="'.esc_attr( $zip_name ).'">'.sprintf( esc_html__( 'Replace with version: %s','plugversions' ), esc_attr( $version ) ).'</a> ';
					}
					++$n;
				}
				/* translators: the plugin revisions. */
				$actions['versions'] = '<span class="plugin-revision-wrp"><a href="#">' . esc_html__( 'Revisions','plugversions' ) . '</a><span class="plugin-revisions-vers">' . rtrim( $links, ' ' ) . '</span></span>';
				
			}
		}
        return $actions;
    }
}