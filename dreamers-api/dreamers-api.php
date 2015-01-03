<?php
/**
 * Plugin Name: Return To Dreamland API JSON (WP-API)
 * Plugin URI: http://github.com/BitPrepared/dreamland-wp-plugins
 * Description: Plugin per la fornire API da usare esternamente
 * Version: 0.2
 * Author: Bit Prepared
 * Author URI: http://github.com/BitPrepared
 * License: GPLv3
 */


require_once 'portal-api.php';

//SETUP
function rtd_api_install(){

    /**
     * Detect plugin. For use in Admin area only.
     */
    if ( !is_plugin_active( 'dreamers-game/dreamers-game.php' ) ) {
        //plugin is activated
        wp_die('<p>The <strong>Dreamers Api</strong> plugin requires plugin Dreamers-game','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
    }

    /**
     * Detect plugin. For use in Admin area only.
     */
    if ( !is_plugin_active( 'dreamers-manager/dreamers-manager.php' ) ) {
        //plugin is activated
        wp_die('<p>The <strong>Dreamers Api</strong> plugin requires plugin Dreamers-manager','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
    }

    /**
     * Detect plugin. For use in Admin area only.
     */
    if ( !is_plugin_active( 'dreamers-sfide/dreamers-sfide.php' ) ) {
        //plugin is activated
        wp_die('<p>The <strong>Dreamers Api</strong> plugin requires plugin Dreamers-sfide','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
    }

}

function rtd_api_uninstall(){

}

register_activation_hook(__FILE__,'rtd_api_install');

register_deactivation_hook(__FILE__,'rtd_api_uninstall');

function dreamers_api_init() {
    global $dreamers_api;
    $dreamers_api = new Portal_API();
    add_filter( 'json_endpoints', array( $dreamers_api, 'register_routes' ) );
}

add_action( 'wp_json_server_before_serve', 'dreamers_api_init' );

