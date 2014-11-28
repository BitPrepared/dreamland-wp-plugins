<?php
/**
 * Plugin Name: Return to Dreamland
 * Plugin URI: http://github.com/BitPrepared/dreamland-wp-plugins
 * Description: Plugin base di dreamland
 * Version: 0.1
 * Author: BitPrepared
 * Author URI: http://github.com/BitPrepared 
 * License: GPLv3
 */

//SETUP
function rtd_install(){

    //add_role( $role_name, $display_name, $capabilities );
    add_role( 'utente_eg', 'Esploratore / Guida', array() );
    add_role( 'capo_reparto', 'Capo Reparto', array() );
    add_role( 'iabz', 'Incaricato alle branche di zona', array() );
    add_role( 'iabr', 'Incaricato alle branche di regione', array() );
    add_role( 'referente_regionale', 'Referente Sfide Regionali', array() );

}

function rtd_uninstall(){

    remove_role('utente_eg');
    remove_role('capo_reparto');
    remove_role('iabz');
    remove_role('iabr');
    remove_role('referente_regionale');
    

}

register_activation_hook(__FILE__,'rtd_install');

register_deactivation_hook(__FILE__,'rtd_uninstall');

if(!function_exists('_log')){
  function _log( $message ) {
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}
