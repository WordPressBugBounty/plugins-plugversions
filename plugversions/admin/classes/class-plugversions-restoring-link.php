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
	 * @var string $key
	 * @since  0.0.6
	 */	
	public $key;

    /**
	 * Version.
	 *
	 * @var string $version
	 * @since  0.0.6
	 */	
	public $version;

    /**
	 * Zip Name.
	 *
	 * @var string $zip_name
	 * @since  0.0.6
	 */	
	public $zip_name;

    /**
	 * Plugin Name.
	 *
	 * @var string $plugin_name
	 * @since  0.0.6
	 */	
	public $plugin_name;

    /**
	 * Class constructor.
	 *
	 * @param string $key
	 * @param string $version
	 * @param string $zip_name
	 * @param string $plugin_name
	 * @param  0.0.6
	 */	
    public function __construct( $key, $version, $zip_name, $plugin_name ) {
        $this->key = $key;
        $this->version = $version;
        $this->zip_name = $zip_name;
        $this->plugin_name = $plugin_name;
        add_filter( 'plugin_action_links' , array( $this, 'add_link' ), 10, 4 );
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
        if( $this->plugin_name !== dirname( $plugin_file ) || ! current_user_can( 'activate_plugin' ) ) return $actions;
        $links = isset( $actions['versions'] ) ? $actions['versions'] : '';
        $links .= '<a class="plugin-revision-action" href="#" data-parent_plugin="'.esc_attr( $plugin_file ).'" data-dir="'.esc_attr( $this->zip_name ).'">'.sprintf( esc_html__( 'Replace with version: %s','plugversions' ), esc_attr( $this->version ) ).'</a> ';
        $actions['versions'] = '<span class="plugin-revision-wrp"><a href="#">' . esc_html__( 'Revisions','plugin-revisioons' ) . '</a><span class="plugin-revisions-vers">' . rtrim( $links, ' ' ) . '</span></span>';
        return $actions;
    }
}