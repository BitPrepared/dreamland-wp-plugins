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
    add_role( 'utente_eg', 'Esploratore / Guida', array( "read" => true) );
    add_role( 'capo_reparto', 'Capo Reparto', array( "read" => true) );
    add_role( 'iabz', 'Incaricato alle branche di zona', array( "read" => true) );
    add_role( 'iabr', 'Incaricato alle branche di regione', array( "read" => true) );
    add_role( 'referente_regionale', 'Referente Sfide Regionali', array( "read" => true) );
}

add_action( 'wp_dashboard_setup', 'remove_wp_dashboard_widgets' );
function remove_wp_dashboard_widgets() {
  
  if ( !current_user_can('create_users') ) {
    //Plugins
    wp_unregister_sidebar_widget( 'dashboard_plugins' );
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal');

    //Right Now - At Glace
    wp_unregister_sidebar_widget( 'dashboard_right_now' );
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');

    //Recent Comments
    wp_unregister_sidebar_widget( 'dashboard_recent_comments' );
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

    //Incoming Links
    wp_unregister_sidebar_widget( 'dashboard_incoming_links' );
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');

    //WordPress Blog
    wp_unregister_sidebar_widget( 'dashboard_primary' );
    remove_meta_box('dashboard_primary', 'dashboard', 'side');

    //Other WordPress News
    wp_unregister_sidebar_widget( 'dashboard_secondary' ); 
    remove_meta_box('dashboard_secondary', 'dashboard', 'side');

    //Quick Press
    wp_unregister_sidebar_widget( 'dashboard_quick_press' ); 
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');

    //Recent Drafts
    wp_unregister_sidebar_widget( 'dashboard_recent_drafts' ); 
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');

    //Activity
    wp_unregister_sidebar_widget( 'dashboard_activity' ); 
    remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');//since 3.8

  }

}

function showMessage($message, $errormsg = false)
{
    if ($errormsg) {
        echo '<div id="message" class="error">';
    }
    else {
        echo '<div id="message" class="updated fade">';
    }
    echo "<p><strong>$message</strong></p></div>";
} 
 
function showAdminMessages()
{

    if ( isset($_GET) && isset($_GET['errore']) ){
        $errore = htmlentities($_GET['errore']);
        switch ($errore) {
            case 'no_new_sfida':
                $testo = 'Non puoi inserire un racconto senza prima aver richiesto la chiusura di una sfida';
                break;
            default:
                $testo = 'Errore generico';
                break;
        }
        showMessage($testo, true);
    }
}

add_action('admin_notices', 'showAdminMessages');

add_action('init', 'dreamer_session', 1);
function dreamer_session() {
    if(!session_id()) {
        session_start();
    }
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
